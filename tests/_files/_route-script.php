<?php

if (@file_exists( __DIR__ . $_SERVER["REQUEST_URI"])) {
    return false;
}

if (preg_match('#^/repositories/(?<repoName>.*)/data/code-coverage#', $_SERVER["REQUEST_URI"], $urlMatches)) {
    require 'code-coverage.php';
} else {
    echo "testtest2";
}