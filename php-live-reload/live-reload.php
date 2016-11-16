<?php

require_once('config.php');

$start = microtime(true);

/*
 * Check for changes
 */

$error = false;

if (!touch($dataFile)) {
    $error = true;
    $response = ['error' => 'Cannot write to log file'];
}
else{
    function fileList($dir, $exts, $excludePathsFilter, $excludeFilesFilter){
        $dirs = glob($dir . '/*', GLOB_ONLYDIR|GLOB_NOSORT);
        $dirs = array_filter($dirs, $excludePathsFilter);
        $files = glob($dir . '/*.{' . implode(",", $exts) . "}", GLOB_BRACE);
        $files = array_filter($files, $excludeFilesFilter);
        foreach ($dirs as $key => $subDir) {
            $dirs[$key] = fileList($subDir, $exts, $excludePathsFilter, $excludeFilesFilter);
        }
        $flatten = function($d) { return array_reduce($d, 'array_merge', []); };
        $dirs = $flatten($dirs);
        $files = array_merge($files, $dirs);
        return array_unique($files);
    }

    $files = fileList($watchDir, $extensions, $excludePathsFilter, $excludeFilesFilter);

    if (!$files) {
        $error = true;
        $response = ['error' => 'No files in watch directory'];
    }
    else{
        $hash = json_encode(array_map(function($f){
            return [$f => hash_file('md4', $f)];
        },$files));

        $changed = $hash !== file_get_contents($dataFile);

        if($changed){
            file_put_contents($dataFile, $hash);
        }

        $end = microtime(true);

        $ms = round($end - $start,6) * 1000;

        $response = ['time' => $ms, 'changed' => $changed];
    }
}

if ($error) {
    http_response_code(400);
}

$response = json_encode($response);

header('Content-Type: application/json');

echo $response;