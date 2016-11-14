# PHP Live Reload

This is a simple script intended to make live reloading in the browser for PHP projects painless, without the need for external libraries.

It is tested with Apache and the built in PHP development server but should work out of the box anywhere that the ```$_SERVER['DOCUMENT_ROOT']``` variable is available.

## Speed

The script uses polling to communicate when changes have occurred. This could be sped up by using web sockets but sockets in PHP require a special extension. I went with polling to keep it as portable as possible.

Tested on a directory with ~21,000 files, the script takes roughly 500ms to generate a hash of each file and to compare with the prior result. On the default project directory, the total time is ~50ms. YMMV.

The minimum delay between checks is set by default to 2 * the run time value returned from the last API call, or 1000ms whichever is greater. If hashing the files in the working directory begins to take a long time for a larger project, the time between checks made to the server will grow proportionally.

```
# Project Directory
Scan Time	Change Detected
9.678ms		false
9.388ms		false
9.375ms		false
13.102ms	false
10.341ms	false
12.498ms	false
11.853ms	false
11.101ms	false
```

```
# Default WordPress Installation
 Scan Time	Change Detected
 96.288ms	false
 98.005ms	false
 116.249ms	false
 96.539ms	false
 104.836ms	false
 114.176ms	false
 99.886ms	false
 88.264ms	false
```

```
# 779 MB Laravel App with no exclusion defined for /vendor or /node_modules directories
Scan Time	Change Detected
5147.079ms	false
5286.697ms	false
5023.984ms	false
4949.01ms	false
5048.196ms	false
4906.718ms	false
4689.192ms	false

# The same project with filters in place
Scan Time   Change Detected
2024.498ms  false
2029.01ms   false
2030.867ms  false
2044.81ms   false
2035.575ms  false
2024.158ms  false
2040.97ms   false
2071.742ms  false
```

## Usage

* Clone the repo
* Add php-live-reload to your web root
* Add script "```<script src="/php-live-reload/live-reload.js"></script>```" to your app

## Requirements

In order to store data about changed files the script will write to a log file, by default "live-reload.json". The web server process must have access to write this file. The web server must also have read access to the files in the watch directory in order to detect changes.

## Security Considerations

By default the script requires write access by the web server process to files within the web root in order to generate its log. Obviously it's not a good idea to have arbitrary folders be writable by the web server process in production.

The log file also contains the full path of each file it has scanned as well as the hash value of the file in JSON format. This file should also not be web-accessible in a production environment.

Given that running live-reload in production is probably not a great idea anyway, special care should be taken when using the script outside its intended use case (in a development environment) for example monitoring an arbitrary directory for changes and communicating that a change has occurred to the client.

## Configuring Watch List

The configuration for the script is at the top of ```live-reload.php``` and consists of the following sections:

* Extensions to check for changes
* Data file to store hash data
* Directory to watch for changes
* Files exclude list
* Function to process files exclude list
* Path Exclude list
* Function to process path exclude list

The functions which perform the filtering on the supplied values for files and paths to exclude are configurable as user defined values, allowing for more advanced filtering to be performed.

By default, $excludeFiles takes a list of paths, relative to the watch directory's root. Each of these files will be excluded from change detection. $excludePaths takes a list of PCRE regular expressions in a format similar to ```~^dev/foo/bar.*~```. Each path in the watch folder will be checked for a match prior to scanning, and excluded if the pattern matches the full path of the file.

The default configuration is listed below.

```php
$extensions = [
    'php',
    'js',
    'html',
    'css'
];

$dataFile = __DIR__ . '/live-reload.json';

$watchDir = $_SERVER['DOCUMENT_ROOT'];

$excludeFiles = [];

$excludePaths = [];

$excludeFilesFilter = function($f) use($dataFile, $excludeFiles){
    $filesToExclude = array_merge([$dataFile], $excludeFiles);
    return (!in_array($f,$filesToExclude));
};

$excludePathsFilter = function($path) use($excludePaths){
    foreach ($excludePaths as $exclude) {
        if (preg_match($exclude, $path)) {
            return false;
        }
    }
    return true;
};
```

## Configuring JavaScript on the Client

### Include Script

```<script src="/php-live-reload/live-reload.js"></script>```

### Arguments
Including the script will make the monitorChanges function available to your application. This function takes three parameters.

1. The amount of time between checks (1 second by default)
2. The callback to perform when receiving a response
3. The AJAX url of the change detection script

### Configuring the Callback

On change detection, the action taken is left to the user and is configurable via a callback. The script below will reload the page when a change is detected.

Any errors generated by the script (file permissions errors, etc.) will be returned to this callback.

```javascript
// Default Values
var delay = 1000;
var url = '/php-live-reload/live-reload.php';
var callback = function(err, res){
    if (err) {
        console.log(err)
    };
    if (res.body && res.body.changed) {
        window.location.reload()
    };
};
monitorChanges(delay, callback, url);

// Equivalent to Running
monitorChanges();
```

## Building the JavaScript File
Building live-reload.js requires NPM and Browserify. The following npm scripts are available from the php-live-reload directory.

Build live-reload.js from source file in live-reload-js/index.js:

```npm run build```

Serve the parent directory of php-live-reload using PHP's built in web server on port 3000:

```npm run serve```

Build && serve:

```npm start```