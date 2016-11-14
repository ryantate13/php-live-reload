<?php


$start = microtime(true);

/*
    Config:
    * Extensions to check for changes
    * Data file to store hash data
    * Directory to watch for changes
    * Files exclude list
    * Function to process files exclude list
    * Path Exclude list
    * Function to process path exclude list
*/
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

/*
 * Check for changes
 */

if (!touch($dataFile)) {
    http_send_status(400);
    $response = ['error' => 'Cannot write to log file'];
}
else{
    function fileList($dir, $exts){
        $dirs = glob($dir . '/*', GLOB_ONLYDIR|GLOB_NOSORT);
        $files = glob($dir . '/*.{' . implode(",", $exts) . "}", GLOB_BRACE);
        foreach ($dirs as $key => $subDir) {
            $dirs[$key] = fileList($subDir, $exts);
        }
        $flatten = function($d) { return array_reduce($d, 'array_merge', []); };
        $dirs = $flatten($dirs);
        $files = array_merge($files, $dirs);
        return $files;
    }

    $files = fileList($watchDir, $extensions);

    if (!$files) {
        http_send_status(400);
        $response = ['error' => 'No files in watch directory'];
    }
    else{
        $files = array_filter($files, $excludeFilesFilter);

        $files = array_filter($files, $excludePathsFilter);

        $hash = json_encode(array_map(function($f){
            return [$f => hash_file('sha1', $f)];
        },$files));

        $changed = $hash != file_get_contents($dataFile);

        if($changed){
            file_put_contents($dataFile, $hash);
        }

        $end = microtime(true);

        $ms = round($end - $start,6) * 1000;

        $response = ['time' => $ms, 'changed' => $changed];
    }
}

$response = json_encode($response);

header('Content-Type: application/json');

echo $response;