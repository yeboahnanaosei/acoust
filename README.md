# API wrapper for Acoustid.org



## What this package can do  :hammer:
* Uses chromaprint to generate or retrieve fingerprint and duration of songs
* Perform a lookup using the fingerprint of the song against [acoustid.org](https://acoustid.org) database





---

### Installation

Just run
```bash
composer require yeboahnanaosei/acoust
```





---

### How to use the package

You can use this package in two ways
* To perform a lookup.  *(You will need an application API key from acoustid.org for this)*
* To generate the fingerprint and duration of the song for any other purpose you might have.



**1. <u>Perform a lookup</u>**

Performing a lookup is as simple as calling just one method.



> **<u>IMPORTANT:</u>  To perform a lookup of a song on acoustid.org database, you should have already obtained an `application API key`  from [acoustid.org](https://acoustid.org)**



```php
try {
    // Set the needed parameters
    $song   = 'Path to song';      # Always required
    $apiKey = 'API key'; 	       # Required when performing a lookup
    $format = 'json or xml'        # Optional. Defaults to 'json' if not supplied

    // Create instance of acoust
    // $format is optional and can be omitted
    // In that case $format will default to 'json'
    $acoust = new Acoust($apiKey, $song, $format);

    // All you need to do is to just call query() method
    // This method will return data from acoustid.org in
    // json format by default or in xml if you set your
    // format to xml
    $response = $acoust->query();
} catch (Throwable $e) {
    echo $e->getMessage();
}
```



**2. <u>Generate fingerprint or duration of song</u>**

To only generate fingerprints of songs or durations of songs you only need the song. No need of an API key or a response format


```php
try {
    // Set needed parameters
    $song = 'Path to song';

    // Create an instance of acoust
    $acoust = new Acoust($song)

    // Generate fingerprint or duration of the song as you wish
    $fingerprint = $acoust->getFingerprint();
    $duration = $acoust->getDuration();


} catch (Throwable $e) {
    echo $e->getMessage();
}
```





## Contributing  :heart:

* As always pull requests are graciously welcomed
* Feel free to report bugs and also suggest improvements
