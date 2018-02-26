Philer
==============================================================

Philer is yet another attempt at an easy phar compiler. I needed to roll my first far the other day and set to work researching how to do it. I found a bunch of libraries, but they all involved weird php build files and stuff.

I decided to take my cue from Composer and base this compiler on a json config file in the root of the package (`philer.json`) that defines how to create the phar.

(The name is from PHar CompILER.)


## Usage

Usuage is pretty simple. Just create your config file and call `philer compile` from the root of your repo. (For stupid technical reasons that I'll eliminate in the next release, you'll also have to create a config file at `/etc/philer/config.hjson`. You can just put `{}` in that file unless you want to define any global options.)


## Installation

I'm a strong believer in the right package manager for the job, and since this is meant to be a system-wide executable, the right package manager is the on your OS provides (apt, yum, brew, etc).

That said, I haven't gotten around to making real packages for this yet, so for now, just clone the repo, build the executable using `php src/bootstrap.php compile`, then mv the built executable from `build/` to your path: `sudo mv build/philer /usr/local/bin/`.


## Config

Configuration files are all the same, whether global or local. I hope to accept [hjson](https://hjson.org) soon, but for now it's just regular json.

Configuration files are found in the following locations:

1. `/etc/philer/config.hjson` -- machine-global config
2. `/etc/philer/config.d/` -- machine-global config fragments (alphabetized)
3. `/home/$USER/.config/philer/config.hjson` -- user-specific config
4. `$REPO/philer.json` -- main config file

Final config is composed by merging all of these down from 4 to 1, where config values defined in 4 override those in 3, etc.

Here's a sample config file that should give you an idea of how to use the system:

```json
{
    "log-level": 7,
    "log-identifier": "Philer",
    "ignore": [
        "*.git*",
        "*test*",
        "*/docs/*",
        "*.sw[op]"
    ],
    "optional": [
        "config-defaults.local.php"
    ],
    "executables": [
        {
            "name": "my-app",
            "bootstrap-file": "src/bootstrap-main.php",
            "phar-spec": {
                "src": "src",
                "vendor": "vendor",
                "config-defaults.php": "configs/main-defaults.php",
                "config-defaults.local.php": "config-defaults.local.php"
            }
        },
        {
            "name": "my-app-debug",
            "bootstrap-file": "src/bootstrap-debug.php",
            "phar-spec": {
                "src": "src",
                "vendor": "vendor",
                "config-defaults.php": "configs/debug-defaults.php",
                "config-defaults.local.php": "config-defaults.local.php"
            }
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

Lots left to do on this. Top of the list is to switch to hjson. After that, probably eliminate the required machine config (or at least make it optional). Finally, I'd like to implement command-line option overrides for at least some of the configuration. Then there's documentation and packaging for OS distribution....

