<?php
namespace KS;

$loader = require __DIR__."/../vendor/autoload.php";

$cmd = new \Commando\Command();
$cmd->argument()
    ->title("command")
    ->require(true)
    ->must(function($str) {
        return in_array($str, [ 'compile', 'extract' ], true);
    });

if ($cmd[0] === 'extract') {
    $cmd->argument()
        ->title("executable");
}

$configSources = ["/etc/philer/config.hjson", "/etc/philer/config.d"];
if (getenv("USER")) {
    $configSources[] = "/home/".getenv("USER")."/.config/philer/config.hjson";
}

$config = new PhilerConfig($configSources, $configSources);
$philer = new Philer($config);

try {
    $philer->run($cmd);
} catch (Exception\Shutdown $e) {
    // Shutdown gracefully
}

