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

$config = new PhilerConfig("/etc/philer/config.hjson", "/etc/philer/config.d", "/home/".getenv("USER")."/.config/philer/config.hjson");
$philer = new Philer($config);

try {
    $philer->run($cmd);
} catch (Exception\Shutdown $e) {
    // Shutdown gracefully
}

