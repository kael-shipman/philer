<?php
namespace KS;

class Philer {
    private $config = [];
    protected $root;

    public function __construct(array $config)
    {
        $this->config = $config;
        $this->root = getcwd();
    }

    public function runCli()
    {
        $cmd = new \Commando\Command();

        $cmd->argument()
            ->require(true)
            ->must(function($str) {
                return in_array($str, [ 'compile', 'extract' ], true);
            });

        $cmd->option('c')
            ->aka('config-file');

        if ($cmd[0] === 'compile') {
            $this->compile();
        } elseif ($cmd[0] === 'extract') {
            $this->extract();
        }
    }

    public function compile()
    {
        $root = $this->root;
        while (!file_exists("$root/philer.json") && is_dir("$root/../")) {
            $root = "$root/../";
        }
        if (!file_exists("$root/philer.json")) {
            throw new \RuntimeException("Can't find philer.json file! Searched in `$root`....");
        }

        $buildDir = "$root/build";
        if (!is_dir($buildDir)) {
            mkdir($buildDir);
        }

        $config = json_decode(file_get_contents("$root/philer.json"), true);


        // TODO: check config


        $execFile = "$buildDir/$config[executable]";
        $pharFile = $execFile;
        if (!preg_match('/\\.phar$/', $pharFile)) {
            $pharFile .= '.phar';
        }


        $phar = new Phar($pharFile, null, $config['executable']);

        $stub = "#!/usr/bin/env php\n".
            "<?php\n".
            "Phar::mapPhar(\"{$config['executable']}\");\n".
            "require \"phar://{$config['executable']}/{$config['bootstrap-file']}\";\n".
            "__HALT_COMPILER();";

        $phar->setStub($stub);

        chmod($pharFile, 0755);
        if ($pharFile !== $execFile) {
            rename($pharFile, $execFile);
        }

    }

    public function extract()
    {
        echo "Extracting....\n";
    }
}



