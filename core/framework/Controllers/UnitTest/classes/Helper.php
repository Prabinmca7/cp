<?php

namespace RightNow\UnitTest;

use RightNow\Api,
    RightNow\Utils\Url,
    RightNow\Utils\Text,
    RightNow\Utils\Config,
    RightNow\Utils\FileSystem,
    RightNow\Connect\v1_4 as ConnectPHP;

class Helper {
    /**
     * Be verbose when calling
     * #makeRequest
     * @var bool
     */
    public static $verbose;
    private static $fileExtension;
    private static $validExtensions = array('test', 'slowtest');

    /**
     * Requires the php file corresponding to the current test file by
     * going up a directory and requiring a file with the same name.
     * @param string $unitTestPath Basically always send in __FILE__
     */
    static function loadTestedFile($unitTestPath) {
        self::setFileExtension($unitTestPath);

        require_once str_replace('/tests/', '/', str_replace(self::$fileExtension, '.php', $unitTestPath));
    }

    /**
     * Returns an anonymous function that is used to invoke the specified method using a variable-length argument list.
     * This is generally used for private methods as the accessibility is enabled via the ReflectionClass.
     * @param string $className The name of the class containing the method
     * @param string $methodName The name of the method to invoke
     * @param array $constructorArgs An array of optional arguments sent to the class constructor
     * @param object $instance Object on which the method to be invoked
     * @return function
     */
    static function getMethodInvoker($className, $methodName, array $constructorArgs = array(), $instance = null) {
        $class = new \ReflectionClass($className);
        if (!$instance) {
            $instance = $constructorArgs ? $class->newInstanceArgs($constructorArgs) : $class->newInstance();
        }
        $method = $class->getMethod($methodName);
        $method->setAccessible(true);

        return function() use ($instance, $method) {
            $arguments = func_get_args();
            $params = $method->getParameters();
            for ($i = 0; $i < count($arguments); $i++) {
                if ($params[$i] && $params[$i]->isPassedByReference()) {
                    $arguments[$i] = &$arguments[$i];
                }
            }
            return $method->invokeArgs($instance, $arguments);
        };
    }

    /**
     * Returns an anonymous function which is used to invoke the specified static method using a variable-length argument list.
     * This is generally used for private methods as the accessibility is enabled via the ReflectionClass.
     *
     * @param string $className The name of the class containing the method.
     * @param string $methodName The name of the method to invoke.
     * @return function
     */
    static function getStaticMethodInvoker($className, $methodName){
        $method = new \ReflectionMethod($className, $methodName);
        $method->setAccessible(true);

        return function() use ($method) {
            $arguments = func_get_args();
            $params = $method->getParameters();
            for ($i = 0; $i < count($arguments); $i++) {
                if ($params[$i] && $params[$i]->isPassedByReference()) {
                    $arguments[$i] = &$arguments[$i];
                }
            }
            return $method->invokeArgs(null, $arguments);
        };
    }

    /**
     * Sets the value of a property to the provided value
     * @param object $reflectionClass Instance of ReflectionClass
     * @param object $reflectionInstance Instance of class used in $reflectionClass
     * @param string $name Name of property to set
     * @param mixed $value Value to set property to
     */
    static function setInstanceProperty($reflectionClass, $reflectionInstance, $name, $value){
        $property = $reflectionClass->getProperty($name);
        $property->setAccessible(true);
        $property->setValue($reflectionInstance, $value);
    }

    /**
     * Returns value of class property
     * @param object $reflectionClass Instance of ReflectionClass
     * @param object $reflectionInstance Instance of class used in $reflectionClass
     * @param string $name Name of property to retrieve
     * @return mixed Value of property
     */
    static function getInstanceProperty($reflectionClass, $reflectionInstance, $propertyName){
        $property = $reflectionClass->getProperty($propertyName);
        $property->setAccessible(true);
        return $property->getValue($reflectionInstance);
    }

    /**
     * Diffs the given values.
     * @param  string|object|array|number $expected expected value
     * @param  string|object|array|number $actual   actual value
     * @param  array  $options Optional options:
     *                         - trailingLines: number of trailing lines of
     *                             context to show (defaults to 2)
     *                         - leadingLines: number of leading lines of
     *                             context to show (defaults to 15)
     *                         - expectedLabel: Heading to show for the
     *                             expected value column (defaults to Expected)
     *                         - actualLabel: Heading to show for the
     *                             actual value column (defaults to Actual)
     *                         - type: 'text' or 'html' (defaults to 'html')
     * @return string|boolean HTML table ready for echoing or false if
     *                             $expected and $actual are identical
     */
    public static function diff($expected, $actual, array $options = array()) {
        self::initializeTextDiffPackage();

        if (!is_string($expected)) $expected = var_export($expected, true);
        if (!is_string($actual)) $actual = var_export($actual, true);
        $expected = explode("\n", $expected);
        $actual = explode("\n", $actual);

        $diff = new \Text_MappedDiff($expected, $actual, array_map('trim', $expected), array_map('trim', $actual));

        if ($diff->isEmpty()) return false;

        $renderer = ($options['type'] === 'text') ? new \Text_Diff_Renderer_unified() : new \Text_Diff_Renderer_cvsweb();
        if ($options['trailingLines']) {
            $renderer->_trailing_context_lines = $options['trailingLines'];
        }
        $renderer->_leading_context_lines = $options['leadingLines'] ?: 15;
        if ($options['expectedLabel']) {
            $renderer->expectedLabel = $options['expectedLabel'];
        }
        if ($options['actualLabel']) {
            $renderer->actualLabel = $options['actualLabel'];
        }
        $diff = $renderer->render($diff);

        return $diff;
    }

    /**
     * Makes a request to the specified URL via wget.
     * @param $url String URL; should be absolute and shouldn't contain a host
     * @param $options Array request options; keys include:
     *  -dontFollowRedirects: When true, do not follow redirects. By default, wget follows up to 20 redirects
     *  -flags: String flags to set in the wget command
     *  -cookie: String cookie data to send; appended to default test mode cookie
     *  -post: String post data to send
     *  -justHeaders: Boolean whether just response headers should be returned
     *  -includeHeaders: Boolean whether the response should include headers (in addition to the response body)
     *  -admin: Boolean whether to send along basic auth header to spoof an admin request
     *  -headers: Associative array of key-value headers to send
     *  -referer: String http referer to set
     *  -useHttps: Boolean whether to use https. Defaults to false.
     *  -noDevCookie: Boolean if true then the request is to Production pages; if false then the request
     *                  is to Development. Defaults to false.
     *  -userAgent: String user agent to set
     * @param $verbose Boolean Whether or not verbose output should be included
     * @return String response
     * @throws \Exception If includeHeaders and justHeaders are both specified
     */
    static function makeRequest($url, $options = array(), $verbose = false) {
        $verbose = self::$verbose || $verbose;
        if ($verbose) {
            echo "Calling $url<br><br>";
        }
        $options['headers'] || ($options['headers'] = array());
        parse_str($options['post'], $postParams);

        // add CSRF token header to ajax requests which do not send form token in post params.
        if((substr($url, 0, strlen("/ci/ajax/widget")) === "/ci/ajax/widget") && !array_key_exists('rn_formToken', $postParams)) {
            $options['headers']['X-CSRF-TOKEN'] = \RightNow\Utils\Framework::createTokenWithExpiration(0);
            if(strpos($options['Cookie'], 'cp_session') === false) {
                $sessionCookie = get_instance()->sessionCookie;
                $options['cookie'] = $options['cookie'] ? 'cp_session=' . $sessionCookie . ';' . $options['cookie'] : 'cp_session=' . $sessionCookie;
            }
        }
        $options['headers']['Host'] = Config::getConfig(OE_WEB_SERVER, 'COMMON');
        $options['headers']['Cookie'] = $options['cookie'] ? $options['cookie'] . ';' : '';
        if (!$options['noDevCookie'])
            $options['headers']['Cookie'] .= "location=development~" . \RightNow\Utils\Framework::createLocationToken("development");

        $headers = '';
        foreach ($options['headers'] as $key => $value) {
            $headers .= "--header='{$key}: $value' ";
        }

        if ($options['includeHeaders'] && $options['justHeaders']) {
            throw new \Exception('Error: The `includeHeaders` and `justHeaders` options can not be combined.');
        }

        $command = "wget $headers"
            . " {$options['flags']} --no-check-certificate "
            . (($options['dontFollowRedirects']) ? " --max-redirect 0" : '')
            . (($options['referer']) ? " --referer {$options['referer']}" : '')
            . (($options['admin']) ? self::createAgentHeader() : '')
            . (($options['post']) ? " --post-data '{$options['post']}' " : '')
            . (($options['userAgent']) ? " --user-agent='{$options['userAgent']}' " : '')
            . (($options['includeHeaders']) ? ' -S --output-document=- ' : ($options['justHeaders'] ? ' -S -O /dev/null ' : ' -q --output-document=- '))
            . "'http" . ($options['useHttps'] ? 's' : '') . '://' . Config::getConfig(OE_WEB_SERVER, 'COMMON') . "{$url}' 2>&1";

        if ($verbose) {
            echo "wget command: <pre>\n$command\n</pre>\n<br>\n";
        }

        $handle = popen($command, 'r');
        $output = stream_get_contents($handle);
        pclose($handle);

        if ($verbose) {
            echo "Output:\n<pre>\n" . htmlspecialchars($output) . "</pre>\n<br>\n";
        }

        return $output;
    }

    /**
     * Converts an associative array of post parameters to the url parameter string expected by `makeRequest()` above.
     * @param array $post An array of post parameters
     * @example Input: ('w_id' => 123, message => 'The operation was completed successfully')
     *          Output: 'w_id=123&message=The+operation+was+completed+successfully'
     * @return string The url post parameter string
     */
    static function postArrayToParams(array $post) {
        return implode('&', array_map(function($key) use ($post){
            return "$key=" . urlencode($post[$key]);
        }, array_keys($post)));
    }

    /**
     * Posts a file to the endpoint. Uses curl instead of wget for this operation.
     * @param string $url Endpoint to hit
     * @param string $fileLocation path of the file on disk
     * @param string $contentType Content type of the file
     * @param array $headers headers to set in curl
     * @return Array Has 'body' and 'headers' keys
     */
    public static function sendFile($url, $fileLocation, $contentType = 'application/octet-stream', $headers = array()) {
        if (self::$verbose) {
            printf("Sending %s to %s%s <br><br>", $fileLocation, Url::getShortEufBaseUrl(), $url);
        }

        self::loadCurl();

        $data = array('file' => new \CURLFile($fileLocation, $contentType));

        $headers = array_merge(array('Host' => Url::getShortEufBaseUrl()), $headers);

        $ch = curl_init();
        curl_setopt_array($ch, array(
            CURLOPT_URL            => Url::getShortEufBaseUrl() . $url,
            CURLOPT_HTTPHEADER     => $headers,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_VERBOSE        => true,
            CURLOPT_HEADER         => true,
            CURLOPT_POSTFIELDS     => $data,
        ));
        $result = curl_exec($ch);

        $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        $headers = substr($result, 0, $headerSize);
        $body = substr($result, $headerSize);

        if (self::$verbose) {
            echo "<br>Headers sent:\n<pre>\n"; print_r($headers); echo "\n</pre>\n";
            if ($result === false) {
                echo 'Curl error: ' . curl_error($ch);
            }
            else {
                echo 'Operation completed without any errors<br><br>';
                echo "Response headers:\n<pre>\n" . htmlspecialchars($headers) . "</pre>\n<br>\n";
                echo "Response body:\n<pre>\n" . htmlspecialchars($body) . "</pre>\n<br>\n";
            }
        }

        curl_close($ch);
        return compact('body', 'headers');
    }

    /**
     * Generate a session cookie
     * @return Array session cookie in encrypted and raw format
     */
    static function getSessionCookie() {
        $sessionID = Api::generate_session_id();
        $sessionData = array(
            's' => array(
                's' => $sessionID,
                'e' => '/session/' . $sessionID,
                'l' => time(),
                'i' => Api::intf_id(),
            ),
        );

        return array(
            'session' => urlencode(Api::ver_ske_encrypt_urlsafe(json_encode($sessionData))),
            'rawSession' => $sessionData
        );
    }

    /**
     * Generates session and profile data for use in a request.
     * @param string $userName name of user; defaults to slatest if omitted
     * @return Array profile and session data
     */
    static function logInUser($userName = 'slatest') {
        $rawProfile = $profile = (object) Api::contact_login(array(
            'login' => $userName,
            'sessionid' => '', // Pass a bogus sessionID to custlogin so that it will create a new one.
            'cookie_set' => 1,
            'login_method' => CP_LOGIN_METHOD_LOCAL,
        ));

        $sessionData = array(
            's' => $profile->sessionid, //Add the new, real session ID
            'a' => 0,
            'n' => 0,
            'u' => array(),
            'p' => false,
            'e' => '/session/L3NpZC9HVjRtWDFsag==',
            'r' => null,
            'l' => time(),
            'i' => Api::intf_id(),
        );

        $session = \RightNow\Libraries\Session::getInstance();
        $profile = $session->createMapping($profile);
        $profile = array(
            'c' => $profile->cookie,
            'p' => $profile->pta_login_used,
            'i' => Api::intf_id(),
            's' => get_instance()->model('CommunityUser')->getForContact($profile->contactID)->result->ID,
        );

        $rawSession = new \RightNow\Libraries\SessionData($sessionData);

        // wrap the raw session so CP can tell the difference between
        // session data ('s') and flash data ('f')
        $session = array('s' => $rawSession->convertToCookie());

        return array(
            'profile' => urlencode(Api::ver_ske_encrypt_urlsafe(json_encode($profile))),
            'session' => urlencode(Api::ver_ske_encrypt_fast_urlsafe(json_encode($session))),
            'rawProfile' => $rawProfile,
            'rawSession' => $rawSession
        );
    }

    /**
     * Logs a user out based on given profile and session objects.
     * @param $rawProfile [Object] - Profile object.
     * @param $rawSession [Object] - Session object.
     */
    static function logOutUser($rawProfile, $rawSession) {
        return Api::contact_logout(array(
            'cookie' => $rawProfile->cookie,
            'sessionid' => $rawSession->sessionID,
        ));
    }

    /**
     * Returns an associative array of config names and their values.
     * @param array $configs A list of config names (not defines)
     * @return array
     */
    static function getConfigValues(array $configs) {
        $values = array();
        foreach($configs as $config) {
            $values[$config] = Config::getConfig(constant($config));
        }
        return $values;
    }

    /**
     * Sets the specified config names and values.
     * @param array $configs An associative array of config names (not defines) and their values.
     * @param boolean $save If true, actually save the value to the database, otherwise just persist for the rest of the process.
     */
    static function setConfigValues(array $configs, $save = false) {
        foreach($configs as $config => $value) {
            \Rnow::updateConfig($config, $value, !$save);
        }
    }

    /**
     * Creates a mock CI for the Hooks class so that hooks can be dynamically set within
     * unit test code.
     * @return \ReflectionProperty The ReflectionProperty object of the Hooks::$hooks array
     */
    static function getHooks(){
        $hooksClass = new \ReflectionClass('\RightNow\Libraries\Hooks');

        $CI = $hooksClass->getProperty('CI');
        $CI->setAccessible(true);
        $CI->setValue(new PhpFunctionalCIMock());

        $hooks = $hooksClass->getProperty('hooks');
        $hooks->setAccessible(true);

        return $hooks;
    }

    /**
     * Substitute variables from the fixture into the value string.
     * @param array $variables An array of key/value pairs used for variable substitution
     * @param string $value Value string to substitute into
     * @param bool $flipValues True, if values should be substituted back into the keys
     * @return string Processed value
     */
    static function processFixtureData(array $variables, $value, $flipValues = false) {
        foreach ($variables as $fixtureKey => $fixtureValue) {
            $args = array("%$fixtureKey%", $fixtureValue);
            if ($flipValues) {
                $args = array_reverse($args);
            }
            $args[] = $value;
            $value = call_user_func_array('str_replace', $args);
        }
        return $value;
    }

    /**
     * Calls destroy on a Connect object
     * @param ConnectPHP\RNObject Connect object to destroy
     * @throws \Exception If non-object is passed in or hasAbility to destroy is denied
     */
    static function destroyObject(ConnectPHP\RNObject $object) {
        if (!$object)
            throw new \Exception("non-object passed to destroyObject");

        // it's expected that non-social objects won't give us the delete permission, so don't check and just try and destroy
        $nonSocialObjects = array(
            CONNECT_NAMESPACE_PREFIX . '\\Contact',
            CONNECT_NAMESPACE_PREFIX . '\\Answer',
            CONNECT_NAMESPACE_PREFIX . '\\Incident',
        );

        list($profile, $session, $rawProfile, $rawSession) = self::logInUser('useradmin');

        if (!in_array(get_class($object), $nonSocialObjects)) {
            if ($object->hasAbility(PERMISSION_RESOURCE_ACTION_DESTROY)) {
                $object->destroy();
            }
            else {
                throw new \Exception("CANNOT DESTROY " . get_class($object));
            }
        }
        else {
            $object->destroy();
        }
        self::logOutUser($rawProfile, $rawSession);
    }

    /**
     * Trim lines in a string.
     * @param $contentToTrim String for which each of its lines will be trimed of
     *  whitespace (" \t\n\r\0\x0B")
     * @return String
     */
    static function trimLines($contentToTrim) {
        $lines = preg_split('/\r\n|\r|\n/', $contentToTrim);
        foreach($lines as &$line)
            $line = trim($line);
        return implode(PHP_EOL, $lines);
    }

    /**
     * Return an array of test files.
     * @param string $fileExtensionPattern should be test or slowtest or test|slowtest
     * @param string $basePath path to look under; defaults to framework if not specified
     * @return array contains file paths to test files
     */
    function getTestFiles($fileExtensionPattern = null, $basePath = null, $module = null) {
        list($isDirectory, $path) = $this->translatePath($basePath);

        if (!$isDirectory) return array($path);

        $testFiles = $this->listDirectory($path, $fileExtensionPattern, $module);

        // Tack on unit tests not residing in core/framework
        if ($path === CPCORE) {
            $testFiles = array_merge($testFiles,
                array_map(function ($p) {return "compatibility/$p";}, $this->listDirectory(CORE_FILES . 'compatibility', $fileExtensionPattern)),
                array_map(function ($p) {return "core_util/$p";}, $this->listDirectory(CORE_FILES . 'util', $fileExtensionPattern)),
                array_map(function ($p) {return "bootstrap/$p";}, $this->listDirectory(DOCROOT . '/bootstrap', $fileExtensionPattern))
            );
        }
        // Run tests in core/framework/Utils and core_util first as a workaround for the seg fault seen when running all tests back-to-back.
        usort($testFiles, function($a, $b) {
            $runFirst = function($c) {
                return Text::beginsWith($c, 'compatibility/Internal/Sql') || Text::beginsWith($c, 'Utils/') || Text::beginsWith($c, 'core_util/');
            };
            $aFirst = $runFirst($a);
            $bFirst = $runFirst($b);
            if ($aFirst && !$bFirst) {
                return -1;
            }
            if ($bFirst && !$aFirst) {
                return 1;
            }
            return strcmp($a, $b);
        });

        return $testFiles;
    }

    /**
     * Returns an array containing the test suite as element 0.
     * If multiple tests found in $basePath, element 1 will be the list of test files.
     *
     * @param $basePath [string]
     * @return [array]
     */
    function createSuiteForTestsIn($basePath, $fileExtensionPattern, $module = null) {
        $suite = new \TestSuite();
        list($isDirectory, $path) = $this->translatePath($basePath);
        $testFiles = $this->getTestFiles($fileExtensionPattern, $basePath, $module);
        if ($isDirectory) {
            $suite->TestSuite('Unit tests in ' . basename($path));
            foreach ($testFiles as $testFile) {
                list(, $fullPath) = $this->translatePath(($basePath ? "$basePath/" : '') . $testFile);
                $suite->addFile($fullPath);
            }
            return array($suite, $testFiles);
        }
        $suite->TestSuite('Unit tests for ' . basename($path));
        $suite->addFile($path);
        return array($suite);
    }

    /**
     * Gets all the test files.
     * @param  string $path                 file path to look under
     * @param  string $fileExtensionPattern type of test file
     * @return array list of files
     */
    private function listDirectory($path, $fileExtensionPattern = null, $module = null) {
        $pattern = "@/tests/[^./][^/]*\.(" . ($fileExtensionPattern ?: 'test') . ")\.php$@";

        return FileSystem::listDirectory($path, false, true, array('function', function($f) use ($pattern, $module) {
            if($module === 'cp') {
                return preg_match($pattern, $f->getPathname()) === 1 && stripos($f->getPathname(), 'okcs') === false;
            }
            elseif($module === 'okcs') {
                return preg_match($pattern, $f->getPathname()) === 1 && stripos($f->getPathname(), 'okcs') !== false;
            }
            else {
                return preg_match($pattern, $f->getPathname()) === 1;
            }
        }));
    }

    /**
     * Sets the file extension to the one specified
     * @param string $unitTestPath file path
     * @throws \Exception If test path isn't found
     */
    private static function setFileExtension($unitTestPath) {
        foreach (self::$validExtensions as $extension) {
            $fullExtension = ".$extension.php";
            if (Text::endsWith($unitTestPath, $fullExtension)) {
                self::$fileExtension = $fullExtension;
                break;
            }
        }

        if (!isset(self::$fileExtension)) {
            throw new \Exception("Not a valid test file name: $unitTestPath");
        }
    }

    /**
     * Returns a 2 element array:
     *   - isDirectory [bool]
     *   - translated path [string]
     *
     * @param $basePath [string]
     * @return [array]
     * @throws \Exception If invalid $basePath
     */
    function translatePath($basePath = null) {
        static $cache = array();
        if ($results = $cache[$basePath ?: CPCORE]) {
            return $results;
        }

        if ($basePath && FileSystem::isReadableFile($basePath)) {
            return $cache[$basePath] = array(false, $basePath);
        }

        if ($basePath && FileSystem::isReadableDirectory($basePath)) {
            return $cache[$basePath] = array(true, $basePath);
        }
        if (!$basePath) {
            $path = CPCORE;
        }
        else if (Text::beginsWith($basePath, 'bootstrap')) {
            $path = DOCROOT . "/$basePath";
        }
        else if (Text::stringContains($basePath, 'compatibility')) {
            $path = CORE_FILES . '/compatibility/' . Text::getSubstringAfter($basePath, 'compatibility');
        }
        else if (Text::beginsWith($basePath, 'core_util')) {
            $path = CORE_FILES . 'util/' . Text::getSubstringAfter($basePath, 'core_util');
        }
        else if (Text::beginsWith($basePath, 'widgets')) {
            $path = CORE_FILES . 'widgets/' . Text::getSubstringAfter($basePath, 'widgets/');
        }
        else if (!Text::beginsWith($basePath, '/')) {
            $path = CPCORE . $basePath;
        }
        if (FileSystem::isReadableFile($path)) {
            if (!Text::stringContains($path, '/tests/')) {
                $path = dirname($path) . '/tests/' . basename($path, '.php') . self::$fileExtension;
                if (!FileSystem::isReadableFile($path)) {
                    throw new \Exception("Could not find a unit test file for $basePath (which resolved to $path).");
                }
            }
            return $cache[$basePath] = array(false, $path);
        }

        if (!FileSystem::isReadableDirectory($path)) {
            throw new \Exception("The specified path ($basePath) does not resolve to a readable directory ($path)");
        }
        return $cache[$basePath] = array(true, $path);
    }

    private static function createAgentHeader() {
        return " --header='Authorization: Basic " . base64_encode('admin:') . "'";
    }

    private static function loadCurl(){
        static $curlInitialized;

        if (!isset($curlInitialized)) {
            if (!($curlInitialized = (extension_loaded('curl') || Api::load_curl()))) {
                exit("Unable to load cURL library");
            }
        }

        return $curlInitialized;
    }

    /**
     * Requires in the diff library. Maintains a static
     * variable to determine whether they've already been
     * included. I think the hope is that it'll be faster
     * than relying on php's `require_once` checking...
     */
    private static function initializeTextDiffPackage() {
        static $hasIncludedTextDiffPackage = false;

        if ($hasIncludedTextDiffPackage) return;

        require __DIR__ . '/../Text/Diff.php';
        require __DIR__ . '/../Text/Diff/Renderer.php';
        require __DIR__ . '/../Text/Diff/Renderer/cvsweb.php';
        require __DIR__ . '/../Text/Diff/Renderer/unified.php';

        $hasIncludedTextDiffPackage = true;
    }
}

class PhpFunctionalCIMock extends \RightNow\Controllers\Base {
    function model($model) {
        if (Text::beginsWith($model, 'custom/Controllers/tests/') || Text::beginsWith($model, 'custom/Models/tests/')
            || Text::beginsWith($model, 'custom/Internal/Libraries/tests/') || Text::beginsWith($model, 'custom/Libraries/tests/')) {
            $pieces = explode('/', $model);
            $path = implode('/', array_slice($pieces, 1, -1));
            $className = end($pieces);
            require_once(CPCORE . $path);
            return new $className();
        }
        return parent::model($model);
    }
}
