<?php
error_reporting(E_ERROR | E_PARSE);
require_once __DIR__ . '/../vendor/autoload.php';

use FeedIo\Feed;
use FeedIo\FeedIo;
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

    try {
        $xml = new SimpleXMLElement($html, LIBXML_NOWARNING | LIBXML_NOERROR | LIBXML_NOCDATA);
    } catch (\Throwable $th) {
        $client = new \FeedIo\Adapter\Http\Client(new Symfony\Component\HttpClient\HttplugClient());
        $feedIo = new FeedIo($client);
        
        $feeds = $feedIo->discover($url);
        $origin = parse_url($url, PHP_URL_SCHEME) . '://' . parse_url($url, PHP_URL_HOST);
        $feedUrl = count($feeds) ?  $origin.$feeds[0] : '';
        $message = $feedUrl ? "Found valid feed url in $feedUrl" : $th->getMessage();
        throw new Exception($message);
    }

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
            'description' => $rss->getDescription(),
        ],
        'items' => array_map(function ($item) {
            $item['url'] = $item['link'];
            unset($item['categories']);
            unset($item['publicId']);
            unset($item['host']);
            unset($item['elements']);
            unset($item['medias']);
            unset($item['link']);
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