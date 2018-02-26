<?php
namespace KS;
declare(ticks = 1);

class Philer extends AbstractExecutable {
    protected $root;
    protected $optional;
    protected $ignore;
    protected $phar;

    public function __construct(ExecutableConfigInterface $config)
    {
        parent::__construct($config);
        $this->setRoot();
        $this->log("Philer Initialized", LOG_DEBUG);
    }

    public function run(\Commando\Command $cmd): void
    {
        if ($cmd[0] === 'compile') {
            $this->compile();
        } else {
            $executable = isset($cmd[1]) ? $cmd[1] : null;
            $this->extract($executable);
        }
        $this->shutdown();
    }

    public function setRoot(string $root = null)
    {
        $this->log("Finding root", LOG_DEBUG);
        if (!$root) {
            $root = getcwd();
        }
        while (!file_exists("$root/philer.json") && is_dir("$root/../")) {
            $root = "$root/../";
        }
        if (!file_exists("$root/philer.json")) {
            throw new \RuntimeException("Can't find philer.json file! Searched in `$root`....");
        }
        $this->log("Setting root to `$root`", LOG_DEBUG);
        $this->root = $root;
    }

    protected function compile()
    {
        $this->log("Beginning compilation", LOG_INFO, [ "syslog", STDOUT ], true);

        $this->config->loadPhilerJson("$this->root/philer.json");

        $buildDir = "{$this->root}/{$this->config->getBuildDir()}";
        if (!is_dir($buildDir)) {
            mkdir($buildDir);
        }

        $globalIgnore = $this->config->getIgnoreList();
        $globalOptional = $this->config->getOptionalList();

        $this->log("Iterating through executable configurations.", LOG_DEBUG);
        foreach($this->config->getExecutableConfigs() as $config) {
            $this->log("Compiling executable `$config[name]` to `$buildDir/$config[name]`", LOG_DEBUG, [ "syslog", STDOUT ], true);

            //TODO: Check config

            $execFile = "$buildDir/$config[name]";
            $pharFile = $execFile;
            if (!preg_match('/\\.phar$/', $pharFile)) {
                $pharFile .= '.phar';
            }

            $ignore = $globalIgnore;
            if (isset($config['ignore'])) {
                $ignore = array_merge($globalIgnore, $ignore);
            }
            $this->ignore = $ignore;

            $optional = $globalOptional;
            if (isset($config['optional'])) {
                $optional = array_merge($globalOptional, $config['optional']);
            }
            $this->optional = $optional;

            // Delete the file if it already exists
            if (file_exists($pharFile)) {
                unlink($pharFile);
            }

            $this->phar = new \Phar($pharFile, null, $config['name']);
            $this->log("Beginning phar creation", LOG_DEBUG);
            foreach($config['phar-spec'] as $destPath => $srcPath) {
                $this->log("Adding path `$srcPath` to phar at `$destPath`", LOG_DEBUG);
                $this->addPharPath("$this->root/$srcPath", $destPath);
            }

            $stub = "#!/usr/bin/env php\n".
                "<?php\n".
                "Phar::mapPhar(\"{$config['name']}\");\n".
                "require \"phar://{$config['name']}/{$config['bootstrap-file']}\";\n".
                "__HALT_COMPILER();";

            $this->phar->setStub($stub);
            $this->log("Stub added with bootstrap file {$config['bootstrap-file']}", LOG_DEBUG);

            $this->log("Setting executable permissions on compiled phar $pharFile", LOG_DEBUG);
            chmod($pharFile, 0755);
            if ($pharFile !== $execFile) {
                $this->log("Renaming ".basename($pharFile)." to ".basename($execFile), LOG_DEBUG);
                rename($pharFile, $execFile);
            }

            $this->log("Cleaning up", LOG_DEBUG);
            unset($this->phar);
        }

        $this->log("Finished compiling", LOG_DEBUG);
    }

    protected function extract(string $executable = null): void
    {
        if (!$executable) {
            $this->config->loadPhilerJson("$this->root/philer.json");
            $exs = $this->config->getExecutableConfigs();
            if (count($exs) === 0) {
                throw new \RuntimeException("No executables configured to be compiled!");
            } elseif (count($exs) > 1) {
                throw new \RuntimeException("There is more than one executable configured in this package. Please specify which one you'd like to extract.");
            }
            $executable = $exs[0]['name'];
        }

        $buildDir = "{$this->root}/{$this->config->getBuildDir()}";
        if (!is_dir($buildDir)) {
            mkdir($buildDir);
        }

        $app = "$buildDir/$executable";
        $f = $app;
        if (!preg_match('/\\.phar$/', $f)) {
            $f = "$f.phar";
        }
        $dest = "$app.extracted";

        if (!file_exists($f)) {
            if (!file_exists($app)) {
                $this->log("No app file found. Have you built it yet? If not, run `philer compile`, then run this again.", LOG_ERR, STDOUT, true);
                exit(2);
            }
            copy($app, $f);
        }

        if (file_exists($dest)) {
            $rm = function($file) use (&$rm) {
                if (is_dir($file)) {
                    $d = dir($file);
                    while (($child = $d->read()) !== false) {
                        if ($child === '.' || $child === '..') {
                            continue;
                        }
                        $rm("$file/$child");
                    }
                } else {
                    unlink($file);
                }
            };
            $rm($dest);
        }

        $phar = new \Phar($f);
        $phar->extractTo($dest);
        unlink($f);
    }

    protected function addPharPath(string $srcPath, string $destPath): void
    {
        // Check to make sure the path exists
        if (!file_exists($srcPath)) {
            if (!$this->pathMatchesList($srcPath, $this->optional, 'optional')) {
                throw new \RuntimeException("Non-optional path `$srcPath` not found! Can't proceed.");
            } else {
                return;
            }
        }

        // If it's a directory, recurse into it
        if (is_dir($srcPath)) {
            $this->phar->addEmptyDir("$destPath");
            $d = dir($srcPath);
            while (($f = $d->read()) !== false) {
                if ($f === '.' || $f === '..') {
                    continue;
                }
                $this->addPharPath("$srcPath/$f", "$destPath/$f");
            }

        // Otherwise, if it's a file...
        } else {
            // Then if it's not ignored, add it to the phar
            if (!$this->pathMatchesList($srcPath, $this->ignore, 'ignore')) {
                $this->log("Adding file $srcPath to phar at $destPath", LOG_DEBUG);
                $this->phar->addFile($srcPath, $destPath);
            }
        }
    }


    protected function pathMatchesList(string $path, array $list, string $type)
    {
        $this->log("Checking $path against $type list....", LOG_DEBUG);
        foreach($list as $pattern) {
            if ($pattern[0] !== '*') {
                $pattern = "*$pattern";
            }
            if (fnmatch($pattern, $path)) {
                $this->log("  `$pattern` matched path $path.", LOG_DEBUG);
                return true;
            } else {
                $this->log("  `$pattern` NOT matched $path", LOG_DEBUG);
            }
        }
        return false;
    }


    public function terminate(): void
    {
        $this->log("Terminated", LOG_INFO, [ "syslog", STDOUT ], true);
        throw new Exception\Shutdown("Terminated");
    }


    public function shutdown(): void
    {
        $this->log("Done", LOG_INFO, [ "syslog", STDOUT ], true);
        throw new Exception\Shutdown("Terminated");
    }

    protected function handleSignal(int $signo, $siginfo) : void
    {
        if ($signo === SIGTERM) {
            $this->terminate();
        }
        parent::handleSignal($signo, $siginfo);
    }
}

