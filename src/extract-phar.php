<?php

$buildDir = __DIR__."/../build";

$f = "$buildDir/test-app.phar";
$app = "$buildDir/test-app";
$dest = "$app.extracted";

if (!file_exists($f)) {
    if (!file_exists($app)) {
        echo "No app file found. Have you built it yet? If not, run `tools/compile-phar.php`, then run this again.\n";
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

$phar = new Phar($f);
$phar->extractTo($dest);

