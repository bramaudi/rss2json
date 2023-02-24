<?php
error_reporting(E_ERROR | E_PARSE);
require_once __DIR__ . '/../vendor/autoload.php';

use FeedIo\Feed;
use FeedIo\Parser\XmlParser;
use FeedIo\Reader\Document;
use FeedIo\Standard\Atom;
use FeedIo\Standard\Rss;

$url = $_GET['url'];
$type_hash = $_GET['type'] === 'hash' ? true : false;
$errorMsg = null;
$data = [];

try {
    if (empty($url)) throw new Exception("URL cannot be empty");
    
    $html = file_get_contents($url);

    // new DateTimeBuilder : it will be used by the parser to convert formatted dates into DateTime instances
    $dateTimeBuilder = new \FeedIo\Rule\DateTimeBuilder();

    $xml = new SimpleXMLElement($html, LIBXML_NOWARNING | LIBXML_NOERROR | LIBXML_NOCDATA);

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

    $rss = $feedIo->parse(new Document($html), new Feed);

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
        ],
        'items' => array_map(function ($item)
        {
            unset($item['categories']);
            unset($item['publicId']);
            unset($item['host']);
            unset($item['elements']);
            unset($item['medias']);
            return $item;
        }, $items)
    ];
} catch (Exception $e) {
    $errorMsg = $e->getMessage();
}

header('Content-Type: application/json');
echo json_encode([
    'status' => !$errorMsg ? 'success' : 'error',
    'message' => $errorMsg ?? null,
    'hash' => md5(json_encode($data)),
    ...($type_hash ? [] : $data)
]);