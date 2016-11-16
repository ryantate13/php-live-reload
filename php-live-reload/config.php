<?php
/*
    Config:
    * Extensions to check for changes
    * Data file to store hash data
    * Directory to watch for changes
    * Files exclude list
    * Filter function to process files exclude list
    * Path Exclude list
    * Filter function to process path exclude list
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
    $excludeFiles[] = $dataFile;
    return (!in_array($f,$excludeFiles));
};

$excludePathsFilter = function($path) use($excludePaths){
    foreach ($excludePaths as $exclude) {
        if (preg_match($exclude, $path)) {
            return false;
        }
    }
    return true;
};