<?php
namespace Acoust;

use Acoust\Exception\AcoustException;
use Acoust\Error\AcoustError;

/**
 * Acoust.php
 *
 * A single one-class library wrapper for acoustid.org API
 * You can lookup an audio file by just calling one method.
 */
class Acoust
{
    /**
     * @var string $chromaprintLocation The location of chromaprint binary
     */
    private $chromaprintLocation = __DIR__ . '/../bin/';

    /**
     * @var string $song Path to the song on the file system to be looked-up
     */
    private $song;

    /**
     * @var string $apiKey The acoustid.org application API key
     */
    private $apiKey;

    /**
     * @var string $responseFormat The response format you want from acoustid.org. Either 'json' or 'xml'
     */
    private $responseFormat;

    /**
     * @var string $mimeType The mime type of the song
     */
    private $songMimeType;

    /**
     * @var array $songDetails The fingerprint and duration of the song
     */
    private $songDetails;

    /**
     * @var array $validResponseFormats An array of valid response formats
     */
    private $validResponseFormats = ['xml', 'json'];

    /**
     * @var string $response The response from acoustid.org after a lookup has been made
     */
    private $response;

    /**
     * @var array $allowedMimeTypes An array of allowed audio formats
     */
    private $allowedMimeTypes = [
        'audio/mpeg',
        'audio/x-m4a',
        'audio/wave',
        'audio/wav'
    ];

    /**
     * Create an instance of Acoust
     *
     * @param string $song Path to the song to be looked up
     * @param string $apiKey An application API key from acoustid.org
     * @param string $responseFormat The format you want the response from acoustid.org to be in.
     * Defaults to 'json'. Valid values: 'json' and 'xml'.
     */
    public function __construct(
        string $song = null,
        string $apiKey = null,
        string $responseFormat = 'json'
    ) {
        $this->song = $song;
        $this->apiKey = $apiKey;
        $this->responseFormat = strtolower($responseFormat);
    }

    /**
     * Validate API key
     *
     * Ensures an application API key has been set
     *
     * @access private
     * @return bool
     * @throws AcoustException
     */
    private function validateApiKey(): bool
    {
        if (!$this->apiKey) {
            throw new AcoustException(
                'AcoustException: You have not provided your AcoustID.org application API key<br>
                You can do so by calling &nbsp;&nbsp;<b><i><u>acoust::setApiKey($key)</u></i></b>
                &nbsp;or by adding it as the second argument to the constructor<br>
                You can get an application API key from
                <a href=\'https://acoustid.org\' target=\'_blank\'>acoustid.org</a><br>'
            );
        } else {
            return true;
        }
    }

    /**
     * Validate the song
     *
     * Performs various checks to ensure that the song that has been set for the
     * current instance of acoust is valid
     *
     * @access private
     * @return bool
     * @throws AcoustException|AcoustError If there is an issue with the song provided
     */
    private function validateSong() : bool
    {
        if (!$this->song || empty($this->song)) {
            throw new AcoustException(
                'AcoustException: No song provided<br>
                <ul>
                    <li>
                        Supply it as the <strong><u>first</u></strong>
                        argument in the constructor
                    </li>
                        <li>Or by calling: <b>acoust::setSong($song)</b>
                        before calling <b>acoust::query()</b>
                    </li>
                </ul>'
            );
        } elseif (!file_exists($this->song)) {
            throw new AcoustError(
                'AcoustError: Could not find the song you specified:
                <b><i><u>' . $this->song . '</u></i></b><br>
                <br>
                <b>Try these options:</b>
                <ul>
                    <li>Make sure the file exists on your computer in the first place</li>
                    <li>Do check and make sure you supplied the correct path to the song</li>
                    <li>Make sure there are no spelling mistakes in the path you supplied</li>
                    <li>
                        Pay attention to case sensitivity. If the file is named "track.mp3"
                        and you type "Track.mp3", it won\'t work
                    </li>
                </ul>'
            );
        } elseif (!is_readable($this->song)) {
            throw new AcoustError(
                "AcoustError: You do not have read permission on the song. Set read permissions and try again"
            );
        } elseif (is_dir($this->song)) {
            throw new AcoustException(
                'AcoustException: It appears you did not provide an audio file<br>
                You provided <b><em>' . $this->song . '</em></b>
                which appears to be a directory<br> Please check
                and provide a path to an audio file<br>'
            );
        }

        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $this->songMimeType = $finfo->file($this->song);

        if (!in_array($this->songMimeType, $this->allowedMimeTypes)) {
            throw new AcoustException(
                "AcoustException: Invalid audio format detected: Allowed formats are:
                <i><b>mp3</b></i> and <i><b>m4a</b></i><br>
                Ensure the file is either an <i><b>mp3</b></i>
                or an <i><b>m4a</b></i><br> And we don't mean the extension.
                The file must have been created in the right format
                <br>"
            );
        }

        return true;
    }


    /**
     * Validate response format
     *
     * Ensures a valid response format has been set
     *
     * @access private
     * @return bool
     * @throws AcoustException If the provided response format is not valid
     */
    private function validateResponseFormat(): bool
    {
        if (!$this->responseFormat) {
            throw new AcoustException(
                'AcoustException: No response format set<br>
                You can try these:<br>
                <ul>
                    <li>
                        You can set the format by providing it as the
                        <strong><u><i>third</i></u></strong> argument
                        to the constructor
                    </li>
                    <li>
                        You can also set the format by calling
                        <b><em><u>acoust::setResponseFormat($format)</u></em></b>
                        before calling <b><em><u>acoust::query()</u></em></b><br>
                        where format must be either "xml" or "json"
                    </li>
                </ul>'
            );
        } elseif (!in_array($this->responseFormat, $this->validResponseFormats)) {
            throw new AcoustException(
                "AcoustException: Invalid response format<br>
                Only 'json' and 'xml' are valid response formats.
                You entered <b><em>{$this->responseFormat}</em></b><br>"
            );
        } else {
            return true;
        }
    }

    /**
     * Generate duration and fingerprint of the song
     *
     * The fingerprint and duration of the song is needed to query acoustid.org
     * about the song
     *
     * @access private
     * @return array $songDetails An array of the fingerprint and duration of the song
     * @throws AcoustError|AcoustException
     */
    private function generateSongDetails(): array
    {
        $this->validateSong();

        // Chromaprint is found in this directory.
        // Chromaprint is needed to generate the fingerprint and duration of the song.
        if (!chdir(realpath($this->chromaprintLocation))) {
            throw new AcoustError('AcoustError: Chromaprint was not found');
        }

        if (!($details = json_decode(shell_exec("./fpcalc -json '$this->song'")))) {
            throw new AcoustError(
                'AcoustError: Unrecoverable error - Chromaprint could not generate fingerprint and duration of the song'
            );
        }

        return $this->songDetails = [
            'duration' => (int) $details->duration,
            'fingerprint' => $details->fingerprint
        ];
    }


    /**
     * Perform a lookup of a song against acoustid.org's database
     *
     * Make a query to acoustid.org to lookup a particular song.
     * This song should have already been set by using acoust::setSong($song) or
     * by providing the song as the second parameter to the constructor before
     * calling this method.
     *
     * @access public
     * @return string $response The response from acoustid.org
     * @throws AcoustError|AcoustException
     */
    public function query(): string
    {
        $this->validateApiKey();
        $this->validateResponseFormat();

        $fingerprint = $this->generateSongDetails()['fingerprint'];
        $duration = $this->generateSongDetails()['duration'];

        // Do cURL stuff
        $cURL = curl_init(
            "https://api.acoustid.org/v2/lookup?client={$this->apiKey}&duration={$duration}&fingerprint={$fingerprint}&meta=recordings+compress&format={$this->responseFormat}"
        );

        curl_setopt($cURL, CURLOPT_RETURNTRANSFER, true);
        $response = curl_exec($cURL);
        curl_close($cURL);

        if (!$response) {
            throw new AcoustError(
                'AcoustError: Could not reach Acoustid.org<br>
                Unable to connect and fetch data from acoustid.org<br>
                <ul>
                    <li>Perhaps you are having internet connection issues</li>
                    <li>Or there is an issue at acoustid.org</li>
                </ul>
                Please check and try again<br>'
            );
        } else {
            $this->response = $response;
            return $this->parseResponse($this->response);
        }
    }

    /**
     * Parse response from acoustid for errors
     *
     * Yet to be documented
     *
     * @access private
     * @param string $response The response from acoustid.org
     * @throws AcoustError If acoustid.org returns with an error status
     */
    private function parseResponse($response)
    {
        switch ($this->responseFormat) {
            case 'json':
                $response = json_decode($this->response);
                break;
            case 'xml':
                $response = simplexml_load_string($this->response);
                break;
        }

        if (strtolower($response->status) === 'error') {
            throw new AcoustError(
                'AcoustError: Acoustid.org responded with this error: ' . $response->error->message
            );
        } else {
            return $this->response;
        }
    }


    /**
     * Set a song to be looked up
     *
     * Provide a path to a song on your file system
     *
     * @access public
     * @param string $song Path to the song.
     * @return Acoust An instance of Acoust class
     * @throws AcoustException If there is a problem with the song
     */
    public function setSong(string $song): Acoust
    {
        $this->song = $song;
        $this->validateSong();
        return $this;
    }


    /**
     * Set your application API key
     *
     * Sets the acoustid.org application api key to be used in querying.
     * This key should have been provided to you by acoustid.org.
     *
     * @access public
     * @param string $apiKey. An API key from acoustid.org
     * @return void
     */
    public function setApiKey(string $key): void
    {
        $this->apiKey = $key;
        return;
    }

    /**
     * Set the preferred data format you want acoustid.org to respond with.
     *
     * Sets the data format in which to receive response from acoustid.org
     * Do you want acoustid.org to respond in 'json' or 'xml'.
     * Valid values are 'xml' and 'json'
     *
     * @access public
     * @param string $responseFormat The response format
     * @return bool
     * @throws AcoustException If the supplied format is not valid
     */
    public function setResponseFormat(string $responseFormat): Acoust
    {
        $this->responseFormat = strtolower($responseFormat);
        $this->validateResponseFormat();
        return $this;
    }


    /**
     * Return your acoustid.org application API key
     *
     * This method returns the acoustid.or application API key that has been set
     * for the instance of acoust. The API key is something that is known to you
     * the developer. However, if you want to programmatically return it then this
     * is the method to call.
     *
     * @access public
     * @return string The API key of the current instance of acoust
     * @throws AcoustException If no API key has been set in the first place
     */
    public function getApiKey(): string
    {
        if (!is_null($this->apiKey)) {
            return $this->apiKey;
        } else {
            throw new AcoustException('AcoustException: No API key has been set');
        }
    }

    /**
     * Get the fingerprint of the song.
     *
     * Call this method to get the fingerprint of the song for an instance of
     * acoust. Ordinarily you won't need this, but if for any other purpose you
     * want to get the fingerprint of a song generated by chromaprint, then this
     * is the method to call.
     *
     * @param string $song The path to the song
     * @return string The fingerprint of the song
     * @throws AcoustException If there is an issue with the path supplied
     */
    public function getFingerprint(string $song = null): string
    {
        if (is_null($song)) {
            return $this->generateSongDetails()['fingerprint'];
        } else {
            return $this->setSong($song)->generateSongDetails()['fingerprint'];
        }
    }

    /**
     * Get the duration of the song
     *
     * Call this method to get the duration of the song for an instance of
     * acoust. Ordinarily you won't need this, but if for any other purpose you
     * want to get the duration of a song generated by chromaprint, then this
     * is the method to call.
     *
     * @param $song The path to the song
     * @return int The duration of the song (in secs)
     * @throws AcoustException If there is an issue with the path supplied
     */
    public function getDuration(string $song = null): int
    {
        if (is_null($song)) {
            return $this->generateSongDetails()['duration'];
        } else {
            return $this->setSong($song)->generateSongDetails()['duration'];
        }
    }
}
