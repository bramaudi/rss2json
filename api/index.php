<?php

use FeedIo\Feed;
use FeedIo\Parser\XmlParser;
use FeedIo\Reader\Document;
use FeedIo\Standard\Rss;

require_once __DIR__ . '/../vendor/autoload.php';

$url = $_GET['url'];
$error = 0;

if (empty($url)) {
    $error = 1;
}

// new DateTimeBuilder : it will be used by the parser to convert formatted dates into DateTime instances
$dateTimeBuilder = new \FeedIo\Rule\DateTimeBuilder();

// new Standard\\Rss : it will provide all standard specific rules to the parser
$standard = new Rss($dateTimeBuilder);

$feedIo = new XmlParser($standard, new \Psr\Log\NullLogger());

// we load it using the Dom library
$document = new Document(file_get_contents($url));

$result = $feedIo->parse($document, new Feed);

header('Content-type: application/json');
echo json_encode($result);