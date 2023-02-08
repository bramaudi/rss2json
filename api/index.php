<?php

$url = $_GET['url'];

if (empty($url)) {
    die('Parameter `url` cannot be empty');
}

// Storing the elements of the webpage into an array
$source_code = file($url);
$xml = '';

header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json');

// 1. traversing through each element of the array
// 2. printing their subsequent HTML entities
foreach ($source_code as $line_number => $last_line) {
    // echo nl2br(htmlspecialchars($last_line) . "\n");
    $xml .= $last_line;
}

$xml = simplexml_load_string($xml);
$json = json_encode($xml);

echo $json;