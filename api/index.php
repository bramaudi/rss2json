<?php

use FeedIo\Adapter\Http\Client;
use FeedIo\FeedIo;

require_once __DIR__ . '/../vendor/autoload.php';

$url = $_GET['url'];
$error = 0;

if (empty($url)) {
    $error = 1;
}

$client = new Client(new Symfony\Component\HttpClient\HttplugClient());
$feedIo = new FeedIo($client);

$result = $feedIo->read($url);

header('Content-type: application/json');
echo json_encode($result->getFeed());