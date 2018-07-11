Philer
==============================================================

Philer is yet another attempt at an easy phar compiler. I needed to roll my first phar the other day and set to work researching how to do it. I found a bunch of libraries, but they all involved weird php build files and stuff.

I decided to take my cue from Composer and base this compiler on a json (actually [hjson](https://hjson.org)) config file in the root of the package called `philer.hjson` that defines how to create the phar.

(The name of the project is from PHar CompILER.)


## Usage

Usuage is pretty simple. Just create your config file and call `philer compile` from the root of your repo. Depending on your config, that will put one or more phar files in the build directory of your choosing.


## Installation

I'm a strong believer in the right package manager for the job, and since this is meant to be a system-wide executable, the right package manager is the one your OS provides (apt, yum, brew, etc).

HOWEVER, the only OS package I've made for it so far is the deb package, which you can get from my [package repo](https://packages.kaelshipman.me). For the rest, you can just download the built phar directly (via the github release page or by just downloading `build/philer` out of this repo) and place it in your path.

Technically, you can also install it on a per-project basis via composer (it'll show up at `vendor/bin/philer`). That's cool and all, but I prefer having a system-wide binary.

### "Compiling" From Source

`philer` can be compiled from source by simply running `composer install && php ./src/bootstrap.php compile` from the repo root.


## Config

Configuration files are all the same, whether global or local. As mentioned above, all configuration files are written in [hjson](https://hjson.org) and should end in `.hjson`. However, hjson is backward compatible with json, so feel free to write them in plain old json if you'd like.

Configuration files are found in the following locations:

1. `/etc/philer/config.hjson` -- optional machine-global config, useful for things like global ignores, etc.
2. `/etc/philer/config.d/` -- optional machine-global config fragments (alphabetized)
3. `/home/$USER/.config/philer/config.hjson` -- optional user-specific config
4. `$REPO/philer.hjson` -- mandatory main config file

Final config is composed by merging all of these down from 4 to 1, where config values defined in 4 override those in 3, etc.

Here's a sample config file that should give you an idea of how to use the system:

```hjson
{
    // Debug levels go from 1 (Emergency) to 7 (Debug)
    log-level: 7

    // log-identifier is the string that shows up in your syslog file. You won't normally
    // set this yourself, though some crazies might like to just to assert their authority
    // over their machines.
    log-identifier: Philer

    // Items in the ignore list follow standard globbing. However, NEGATION IS NOT SUPPORTED
    ignore: [
        *.git*
        *test*
        */docs/*
        *.sw[op]
    ]

    // Files in the "optional" list won't throw errors if they're specified in executable
    // profiles, but aren't found in the project. (This isn't very useful, but is there
    // just in case.)
    optional: [
        config-defaults.local.php
    ]

    // The `executables` key holds an array of specifications for building executables.
    // Each executable spec has a name, a bootstrap file, and a phar-spec that defines
    // which files exist in the phar and what their sources are in the project folder.
    executables: [
        {
            // The name of the executable
            name: my-app

            // The bootstrap file (this is called by the phar stub to kick off the executable)
            bootstrap-file: src/bootstrap-main.php

            // Keys are paths within the phar, while values are paths in the filesystem. (Paths
            // in `values` are copied to the path `key` in the phar archive.)
            phar-spec: {
                src: src
                vendor: vendor
                config-defaults.php: configs/main-defaults.php
                config-defaults.local.php: config-defaults.local.php
            }
        }
        {
            name: my-app-debug
            bootstrap-file: src/bootstrap-debug.php
            phar-spec: {
                src: src
                vendor: vendor
                config-defaults.php: configs/debug-defaults.php
                config-defaults.local.php: config-debug-defaults.local.php
            }

            // You can have `optional` and `ignore` keys in executable specifications, too
            optional: [
                config-debug-defaults.local.php
            ]
        }
    ]
}
```

Here's a brief rundown of the demonstrated config keys:

* **log-level:** An integer level (0-7) corresponding to syslog severity levels that you'd like to log. A value of 7 (`LOG_DEBUG`) logs everything, while a value of 0 (`LOG_EMERG`) logs only the most severe events (none, in the case of `philer`). **Default:** 3 (`LOG_ERR`)
* **log-identifier:** The name that will appear in the logfile for philer. (There's not much of a reason for you to change this; it's just available for those very finicky among us.) **Default:** `Philer`.
* **ignore:** An array of patterns to ignore (works on standard shell globbing)
* **optional:** An array defining files that are optional (also works on standard shell globbing). This is really just here for situations like that demonstrated above, where you might want to allow builders to use optional custom compile-time configurations or something.
* **executables:** An array of executable specifications with the following sub-config:
    * **name:** The final name of the compiled executable
    * **bootstrap-file:** The path (inside the phar) of the file that will bootstrap your executable (the equivalent of an `index.php` file)
    * **phar-spec:** A mapping of phar-paths to source-paths. (Should be read like so: "THIS phar file comes from THAT source file.")


## To-do

* Implement command-line option overrides for at least some of the configuration.
* Source-level documentation
* OS packages

