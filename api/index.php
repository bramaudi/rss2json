<?php
require_once __DIR__ . '/../vendor/autoload.php';

use FeedIo\Feed;
use FeedIo\Parser\XmlParser;
use FeedIo\Reader\Document;
use FeedIo\Standard\Atom;
use FeedIo\Standard\Rss;

$url = $_GET['url'];
$errorMsg = null;

try {
    $data = file_get_contents($url);

    // new DateTimeBuilder : it will be used by the parser to convert formatted dates into DateTime instances
    $dateTimeBuilder = new \FeedIo\Rule\DateTimeBuilder();

    $xml = new SimpleXMLElement($data, LIBXML_NOWARNING | LIBXML_NOERROR | LIBXML_NOCDATA);

    if ($xml->channel) {
        $standard = new Rss($dateTimeBuilder);
    } else {
        if (!in_array('http://www.w3.org/2005/Atom', $xml->getDocNamespaces(), true)
            && !in_array('http://purl.org/atom/ns#', $xml->getDocNamespaces(), true)
        ) {
            throw new Exception('Invalid feed.');
        }
        $standard = new Atom($dateTimeBuilder);
    }

    $feedIo = new XmlParser($standard, new \Psr\Log\NullLogger());

    $rss = $feedIo->parse(new Document($data), new Feed);

    $items = [];
    foreach ($rss as $item) {
        $items[] = $item->toArray();
    }

    $data = [
        'channel' => [
            'url' => $url,
            'title' => $rss->getTitle(),
            'link' => $rss->getLink(),
            'description' => $rss->getDescription(),
            'image' => $rss->getLogo(),
            'author' => $rss->getAuthor(),
            'language' => $rss->getLanguage(),
            'lastModified' => $rss->getLastModified(),
        ],
        'items' => $items
    ];
} catch (Exception $e) {
    $errorMsg = $e->getMessage();
}

header('Content-type: application/json');
echo json_encode([
    'status' => !$errorMsg ? 'success' : 'error',
    'message' => $errorMsg ?? null,
    ...$data
]);