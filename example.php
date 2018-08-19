<?php
use Acoust\Acoust;

require __DIR__ . '/vendor/autoload.php';


$apiKey = 'apiKeyFromAcoustID.org';     // API key from acoustID.org
$song   = '/path/to/song';              // Path to the song you want to query
$responseFormat = 'json';               // Response format you want from acoustID. Defaults to json if omitted

try {
    $acoust = new Acoust($song, $apiKey, $responseFormat);

    // Just call this method.
    // It returns the data in the format you specified.
    $response = $acoust->query();
} catch (Throwable $e) {
    echo $e->getMessage();
}
