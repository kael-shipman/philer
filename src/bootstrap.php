<?php
namespace KS;

$loader = require __DIR__."/../vendor/autoload.php";

// Interpret command-line arguments
$cmd = new \Commando\Command();

// Define dash-options first, because arguments will require parsing (see https://github.com/nategood/commando/issues/85)

// Log level
$cmd->option('l')
    ->aka('log-level')
    ->describedAs("The log level for this process. Must be between 0 and 7 (0 = only log emergencies; 7 = log everything).")
    ->must(function($level) {
        if (!is_numeric($level)) {
            throw new \RuntimeException("Option `-l|--log-level` must be numeric. '$level' given.");
        } elseif ($level < 0 || $level > 7) {
            throw new \RuntimeException("Option `-l|--log-level` must be between 0 and 7 inclusive. '$level' given.");
        }
        return true;
    });

// Build dir
$cmd->option('d')
    ->aka('build-dir')
    ->describedAs("The directory where executables and other artifacts are placed. (Created if not existing.)");

// Log identifier
$cmd->option('n')
    ->aka('log-identifier')
    ->describedAs("The string identifier of this process in the logs. Must not contain whitespace.")
    ->must(function($id) {
        return !preg_match('/\s/', $id);
    });

// Ignore list
$cmd->option('i')
    ->aka('ignore')
    ->describedAs("A json-encoded array of glob patterns to ignore (must be valid json).")
    ->must(function($str) {
        $arr = json_decode($str, true);
        $e = "Option '-i|--ignore' must contain a valid json array of glob patterns to ignore.";
        if (!$arr || !is_array($arr)) {
            throw new \RuntimeException($e);
        }
        foreach($arr as $n => $i) {
            if (!is_string($i)) {
                throw new \RuntimeException($e." Invalid value for item #$n");
            }
        }
        return true;
    })
    ->map(function($val) {
        return json_decode($val, true);
    });

// Optional list
$cmd->option('o')
    ->aka('optional')
    ->describedAs("A json-encoded array of glob patterns to mark as optional (must be valid json).")
    ->must(function($str) {
        $arr = json_decode($str, true);
        $e = "Option '-o|--optional' must contain a valid json array of glob patterns to mark as optional.";
        if (!$arr || !is_array($arr)) {
            throw new \RuntimeException($e);
        }
        foreach($arr as $n => $i) {
            if (!is_string($i)) {
                throw new \RuntimeException($e." Invalid value for item #$n");
            }
        }
        return true;
    })
    ->map(function($val) {
        return json_decode($val, true);
    });


// Argument 1: command
$cmd->argument()
    ->title("command")
    ->require(true)
    ->must(function($str) {
        return in_array($str, [ 'compile', 'extract' ], true);
    });

// If command is 'extract', the next argument must be the name of the executable to extract
// **NOTE:** Right now, this has to be last because accessing `$cmd[0]` is what triggers all
// validation.
if ($cmd[0] === 'extract') {
    $cmd->argument()
        ->title("executable");
}


// Prepare configuration stack
$configSources = ["/etc/philer/config.hjson", "/etc/philer/config.d"];
if (getenv("USER")) {
    $configSources[] = "/home/".getenv("USER")."/.config/philer/config.hjson";
}

$optionalConfigs = $configSources;

$commandLineConfigs = [];
if ($cmd['l']) {
    $commandLineConfigs['log-level'] = $cmd['l'];
}
if ($cmd['n']) {
    $commandLineConfigs['log-identifier'] = $cmd['n'];
}
if ($cmd['i']) {
    $commandLineConfigs['ignore'] = $cmd['i'];
}
if ($cmd['o']) {
    $commandLineConfigs['optional'] = $cmd['o'];
}
if ($cmd['d']) {
    $commandLineConfigs['build-dir'] = $cmd['d'];
}
$configSources[] = $commandLineConfigs;


// Instantiate objects
$config = new PhilerConfig($configSources, $optionalConfigs);
$philer = new Philer($config);


// Run
try {
    $philer->run($cmd);
} catch (Exception\Shutdown $e) {
    // Shutdown gracefully
}

