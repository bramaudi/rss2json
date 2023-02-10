<?php

require_once '../Feed.php';

$url = $_GET['url'];

if (empty($url)) {
    die('Parameter `url` cannot be empty');
}

$rss = Feed::load($url);

echo $rss;