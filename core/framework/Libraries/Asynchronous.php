<?php
namespace RightNow\Libraries;

/**
 * Make asynchronous connections using sockets.
 * May be used to make both http get and post requests to external APIs.
 */
class Asynchronous {
    const DEFAULT_PORT          = 80;

    /**
     * Default request timeout - 5 minutes
     */
    const DEFAULT_TIMEOUT       = 5;

    /**
     * Default sleep interval between polling loops - 1 millisecond
     */
    const DEFAULT_SLEEP_TIME    = 1000;

    private static $results = array();
    private static $connections = array();
    private static $pollingForResults = false;

    /**
     * Begins an HTTP request. Options allowed are
     *
     *      -url: (string) URL to make the request to; required
     *      -key: (string) Index in return array to reference the result set if making more than one request
     *      -method: (string) Either 'get' or 'post'; (optional) defaults to 'get'
     *      -data: (array) Associative array of form data to post if request is a 'post' (optional)
     *      -host: (string) Host name to use for the connection, if different from the one in url (optional)
     *      -timeout: (int) Number of seconds before a connection is considered timed-out; (optional) defaults to 3
     *
     * @param array $options List of options
     * @return string The key for the connection or null if a connection wasn't made
     * @throws \Exception If there was a problem initiating a connection
     */
    static function request(array $options) {
        static $generatedKey = 0;

        if (!($url = $options['url'])) {
            throw new \Exception(\RightNow\Utils\Config::getMessage(A_URL_MUST_BE_SPECIFIED_MSG));
        }

        $key = $options['key'] ?: "CPGeneratedKey" . $generatedKey++;
        $method = strtolower($options['method'] ?: 'get');
        $timeout = $options['timeout'] ?: self::DEFAULT_TIMEOUT;
        $connection = null;

        if (array_key_exists($key, self::$results)) {
            unset(self::$results[$key]);
        }

        if ($method === 'get') {
            $connection = self::startGetHttpRequest($url, $timeout, $options['host']);
        }
        else if ($method === 'post') {
            $connection = self::startPostHttpRequest($url, $timeout, ($options['data'] ? http_build_query($options['data']) : ''), $options['host']);
        }
        if ($connection) {
            self::$connections[$key] = (object) array(
               'filePointer' => $connection,
               'parser' => new HttpResponseParser(),
            );
            return $key;
        }
    }

    /**
     * Returns the response for the given key. If no key is supplied and there's only a single request,
     * then that request's response is returned.
     * @param string $key The key value set in the request() call (optional)
     * @return string|null String response or null if there was no response for the supplied key
     */
    static function get($key = '') {
        if ($key !== '' && array_key_exists($key, self::$results)) {
            return self::$results[$key];
        }
        $results = self::getAllPendingResults();
        if (count($results) === 1 && $key === '') {
            $singleKey = array_keys($results);
            return $results[$singleKey[0]];
        }
        return $results[$key];
    }

    /**
     * Gathers results from all connections.
     * @return array List containing all responses
     */
    private static function getAllPendingResults() {
        $returnVal = array();
        $keepLooping = true;
        $start = microtime(true);
        while ($keepLooping && count(self::$connections) !== 0) {
            $keepLooping = false;
            foreach (array_keys(self::$connections) as $key) {
                $connection = self::$connections[$key];
                if($connection === null)
                    continue;
                $keepLooping = true;
                $line = fgets($connection->filePointer);

                try {
                    $connection->parser->addLine($line);
                }
                catch (\Exception $e) {
                    $returnVal[$key] = $e->getMessage();
                    fclose($connection->filePointer);
                    self::$connections[$key] = null;
                    continue;
                }
                $streamInfo = stream_get_meta_data($connection->filePointer);
                if (feof($connection->filePointer) || $streamInfo['timed_out'] || $connection->parser->done() ||
                    (microtime(true) - $start) > self::DEFAULT_TIMEOUT) {
                    $returnVal[$key] = $connection->parser->getData();
                    fclose($connection->filePointer);
                    self::$connections[$key] = null;
                }
            }
            usleep(self::DEFAULT_SLEEP_TIME); // take some naps to reduce polling loop hits
        }
        self::$results = $returnVal;
        return $returnVal;
    }

    /**
     * Initiates an HTTP get request.
     * @param string $url URL to request
     * @param int $timeout Timeout for request
     * @param string $host Value to send for host header if to be different from the host in $url
     * @return boolean True if the request was successfully initiated
     * @throws \Exception If there was an error establishing the connection
     */
    private static function startGetHttpRequest($url, $timeout, $host = '') {
        $urlParts = self::parseURL($url);
        return self::startRequest(
            $urlParts['host'],
            $urlParts['port'],
            self::buildHeaderString(array(
               'GET' => $urlParts['path'] . "?" . $urlParts['query'] . " HTTP/1.1",
               'Host:' => $host ?: $urlParts['host'],
               'Connection:' => 'close',
             )),
            $timeout
        );
    }

    /**
     * Initiates an HTTP post request.
     * @param string $url URL to request
     * @param int $timeout Timeout for request
     * @param string $paramString Data to post
     * @param string $host Value to send for host header if to be different from the host in $url
     * @return boolean True if the request was successfully initiated
     * @throws \Exception If there was an error establishing the connection
     */
    private static function startPostHttpRequest($url, $timeout, $paramString, $host = '') {
        $urlParts = self::parseURL($url);
        return self::startRequest(
            $urlParts['host'],
            $urlParts['port'],
            self::buildHeaderString(array(
              'POST' => $urlParts['path'] . ($urlParts['query'] ? '?' . $urlParts['query'] : '') . ' HTTP/1.1',
              'Host:' => $host ?: $urlParts['host'],
              'Connection:' => 'close',
              'Content-Type:' => 'application/x-www-form-urlencoded',
              'Content-Length:' => strlen($paramString)
            )) . $paramString,
            $timeout
        );
    }

    /**
     * Parses the given URL and adds a default port if one isn't specified.
     * @param string $url URL
     * @return array Parsed url
     * @throws \Exception If parse_url cannot parse $url
     */
    private static function parseURL($url) {
        if (($urlParts = @parse_url($url)) === false) {
            throw new \Exception("$url is not a valid URL");
        }
        if (!array_key_exists('port', $urlParts)) {
            $urlParts['port'] = self::DEFAULT_PORT;
        }
        return $urlParts;
    }

    /**
     * Builds a HTTP header string given the associative array.
     * Opposition to http_parse_headers.
     * @param array $headers Associative array
     * @return string Header string
     */
    private static function buildHeaderString(array $headers) {
        $return = '';
        foreach ($headers as $key => $value) {
            $return .= "$key $value\r\n";
        }
        return "$return\r\n\r\n";
    }

    /**
     * Sets up the socket connection and sends the request headers.
     * @param string $host Host for the request
     * @param int $port Port to make the request over
     * @param string $headers Headers to send
     * @param int $timeout Timeout for the connection
     * @return object File pointer
     * @throws \Exception If connection couldn't be opened to $host
     */
    private static function startRequest($host, $port, $headers, $timeout) {
        $errorCode = $errorMessage = '';
        $connection = @fsockopen($host, $port, $errorCode, $errorMessage, $timeout);
        if ($connection === false) {
            throw new \Exception("Couldn't open connection to $host, error number: $errorCode, message: $errorMessage");
        }
        stream_set_blocking($connection, false);
        stream_set_timeout($connection, $timeout);
        fwrite($connection, $headers);
        return $connection;
    }
}

/**
* FSM for parsing HTTP responses.
*
* @internal
*/
class HttpResponseParser {
    /**
     * List of possible states
     */
    const RESPONSE_CODE           = 0;
    const FAIL_STATE              = 1;
    const RECEIVE_HEADER          = 2;
    const RECEIVE_CHUNK_SIZE      = 3;
    const RECEIVE_CHUNKED_DATA    = 4;
    const RECEIVE_CHUNKED_FOOTER  = 5;
    const RECEIVE_NORMAL_DATA     = 6;
    const DONE                    = 7;

    private $currentState = self::RESPONSE_CODE;
    private $chunkLength = 0;
    private $chunkPosition = 0;
    private $chunked = false;
    private $headers = '';
    private $data = '';
    private $code = 0;

    /**
     * Getter for headers.
     * @return string String of response headers
     */
    function getHeaders() {
        return $this->headers;
    }

    /**
     * Getter for data.
     * @return string Response data
     */
    function getData() {
        return $this->data;
    }

    /**
     * Getter for code.
     * @return int Response code
     */
    function getCode() {
        return $this->code;
    }

    /**
     * Adds a line from the stream.
     * @param string $line Line from fgets
     * @return void
     * @throws \Exception If current state doesn't exist
     */
    function addLine($line) {
        if (!is_string($line) || $line === '') {
            return;
        }
        switch ($this->currentState) {
            case self::RESPONSE_CODE:
                $this->responseCode($line);
                break;
            case self::FAIL_STATE:
                $this->failState($line);
                break;
            case self::RECEIVE_HEADER:
                $this->receiveHeader($line);
                break;
            case self::RECEIVE_CHUNK_SIZE:
                $this->receiveChunkSizeIndicator($line);
                break;
            case self::RECEIVE_CHUNKED_DATA:
                $this->receiveChunkedData($line);
                break;
            case self::RECEIVE_CHUNKED_FOOTER:
                $this->receiveChunkedFooter($line);
                break;
            case self::RECEIVE_NORMAL_DATA:
                $this->receiveNormalData($line);
                break;
            case self::DONE:
                return;
            default:
                throw new \Exception("Non existent state hit.");
        }
    }

    /**
     * Adds multiple lines from the stream.
     * @param array $lines Collection of lines
     * @return void
     */
    function addLines(array $lines = array()) {
        foreach ($lines as $line) {
            $this->addLine($line);
        }
    }

    /**
    * Indicates whether all content has been received.
    * @return boolean True if all content has been received, False otherwise
    */
    function done() {
        return $this->currentState === self::DONE;
    }

    /**
     * Received a response code.
     * @param string $line Line from fgets
     * @return void
     * @throws \Exception if response code was not 200
     */
    private function responseCode($line) {
        // Expecting "HTTP/1.1 200 OK", "HTTP/1.1 404 NOT FOUND", etc.
        $this->code = (int) substr($line, 9, 3);
        // Consider 200 to be success and all else to be failure
        // but there are other return codes that may be acceptable
        if ($this->code !== 200) {
            $this->currentState = self::FAIL_STATE;
            throw new \Exception("Received code {$this->code} from the server. ($line)");
        }
        else {
            $this->currentState = self::RECEIVE_HEADER;
        }
    }

    /**
     * Fail state.
     * @return void
     * @throws \Exception Always throws exception about failed parsing
     */
    private function failState() {
        throw new \Exception("Parsing has failed");
    }

    /**
     * Receives a new header.
     * @param string $line Line from fgets
     * @return void
     */
    private function receiveHeader($line) {
        if (trim($line) === 'Transfer-Encoding: chunked') {
            $this->chunked = true;
        }
        if ($line === "\r\n") {
            if ($this->chunked) {
                $this->currentState = self::RECEIVE_CHUNK_SIZE;
            }
            else {
                $this->currentState = self::RECEIVE_NORMAL_DATA;
            }
        }
        else {
            $this->headers .= $line;
        }
    }

    /**
    * Receives the size indicator for the next chunk of data.
    * @param string $line Line from fgets
    * @return void
    */
    private function receiveChunkSizeIndicator($line) {
        $this->chunkLength = (int) hexdec($line);
        $this->chunkPosition = 0;

        $this->currentState = ($this->chunkLength === 0)
            ? self::RECEIVE_CHUNKED_FOOTER
            : self::RECEIVE_CHUNKED_DATA;
    }

    /**
     * Receives chunked data.
     * @param string $line Line from fgets
     * @return void
     */
    private function receiveChunkedData($line) {
        $this->data .= trim($line);
        $this->chunkPosition += strlen($line);

        if ($this->chunkPosition >= $this->chunkLength) {
            $this->currentState = self::RECEIVE_CHUNK_SIZE;
        }
        // Otherwise, remain in RECEIVE_CHUNKED_DATA state
    }

    /**
     * Receives chunked footer.
     * @return void
     */
    private function receiveChunkedFooter() {
        // This should always be a 0 followed by a few blank lines
        // Basically we discard this data and consider this a no-op.
        // (Actually, we'll just go to done)
        $this->currentState = self::DONE;
    }

    /**
     * Receives normal data.
     * @param string $line Line from fgets
     * @return void
     */
    private function receiveNormalData($line) {
        $this->data .= $line;
    }
}
