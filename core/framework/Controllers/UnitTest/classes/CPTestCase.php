<?php

use RightNow\Utils\Text,
    RightNow\Utils\FileSystem,
    RightNow\UnitTest\Helper,
    RightNow\Connect\v1_4 as ConnectPHP;

require_once  CPCORE . 'Controllers/UnitTest/classes/Fixture.php';

/**
 * Extends UnitTestCase and provides functionality for commonly-needed
 * test operations like reflection, logging in, and http requests.
 */
class CPTestCase extends UnitTestCase {
    /**
     * Y'know.
     * @var object
     */
    public $CI;
    /**
     * Name of the class being tested, including namespace.
     * Children are required to set this if using any
     * of the reflection methods.
     * @var string
     */
    public $testingClass;
    /**
     * Path to temporary directory
     * to write files for a test
     * @var string
     */
    public $testDir;
    /**
     * Temp variable for session when
     * session mocking is taking place
     * @var object
     */
    private $realSession;
    /**
     * Name of class where handler function is located
     * @var string
     */
    protected $hookEndpointClass;
    /**
     * relative file path to file where handler function
     * is located (eg. Models/tests/Contact.test.php)
     * @var string
     */
    protected $hookEndpointFilePath;
    /**
     * Variable to hold data for processing and verification in
     * tests that involve hooks.
     * @var object
     */
    protected static $hookData;
    /**
     * Generic error message used for holding the current hook
     * error.
     * @var string
     */
    protected static $hookErrorMsg = 'Error encountered calling hook';

    function __construct($label = false) {
        parent::__construct($label);
        $this->CI = get_instance();
    }

    function __destruct() {
        // Clean up after sloppy tests
        if ($this->testDir) {
            $this->eraseTempDir();
        }
        $this->unsetMockSession();
        $this->restoreUrlParameters();
    }

    function run($reporter) {
        $this->setUpBeforeClass();
        parent::run($reporter);
        $this->tearDownAfterClass();
    }

    /**
     * Method that is called before any tests in a suite are ran
     */
    function setUpBeforeClass() {
    }

    /**
     * Method that is called after all tests in a suite are ran
     */
    function tearDownAfterClass() {
    }

    function setUp() {
        $hooks = Helper::getHooks();
        $this->oldHooksValue = $hooks->getValue();
        parent::setUp();
    }

    function tearDown() {
        $this->logOut();
        ConnectPHP\ConnectAPI::rollback();
        $this->unsetMockSession();
        $hooks = Helper::getHooks();
        $hooks->setValue($this->oldHooksValue);
        parent::tearDown();
    }

    /**
     * Potentially restrict tests to just those specified via the URL
     * @return array List of tests to run
     */
    function getTests(){
        $listOfAvailableTests = parent::getTests();
        get_instance()->router->setUriData();
        $url = get_instance()->uri->uri_string();
        $listOfSubTests = trim(Text::getSubstringAfter($url, '/subtests/'));
        if($listOfSubTests){
            $listOfSubTests = explode(',', $listOfSubTests);
            foreach($listOfSubTests as $key => &$subTest){
                $subTest = trim($subTest);
                if(!in_array($subTest, $listOfAvailableTests)){
                    unset($listOfSubTests[$key]);
                    echo "Test '$subTest' does not exist as a test within " . $this->getLabel() . "<br/>";
                }
            }
            $listOfSkippedTests = array_diff($listOfAvailableTests, $listOfSubTests);
            $this->reporter->paintSkip(count($listOfSkippedTests) . " tests not run.");
            $listOfAvailableTests = $listOfSubTests;
        }
        return $listOfAvailableTests;
    }

    /**
     * Override the assert method to handle strings which do not contain %s. If the message doesn't
     * have an insertion point, just use the message directly.
     *
     * @param SimpleExpectation $expectation  Expectation subclass.
     * @param mixed $compare                  Value to compare.
     * @param string $message                 Message to display.
     * @return boolean                        True on pass
     */
    function assert($expectation, $compare, $message = '%s') {
        $outputMessage = Text::stringContains($message, '%s')
            ? sprintf($message, $expectation->overlayMessage($compare, $this->reporter->getDumper()))
            : $message;

        if ($expectation->test($compare)) {
            return $this->pass($outputMessage);
        }
        else {
            return $this->fail($outputMessage);
        }
    }

    /**
     * Because SimpleUnit's #assertTrue basically
     * asserts truthiness, and we want The Truth,
     * this overrides and asserts strict equal to boolean true.
     * @param bool $value Should be true
     */
    function assertTrue($value, $message = '%s should be TRUE') {
        return $this->assert(
            new IdenticalExpectation(true),
            $value,
            $message
        );
    }

    /**
     * Because SimpleUnit's #assertFalse basically
     * asserts falsiness, and we want The False,
     * this overrides and asserts strict equal to boolean false.
     * @param bool $value Should be false
     */
    function assertFalse($value, $message = '%s should be FALSE') {
        return $this->assert(
            new IdenticalExpectation(false),
            $value,
            $message
        );
    }

    /**
     * Because we override #assertTrue above, override #assertNull.
     * @param mixed $value Should be null
     */
    function assertNull($value, $message = '%s should be NULL') {
        return $this->assert(
            new IdenticalExpectation(null),
            $value,
            $message
        );
    }

    /**
     * Because we override #assertTrue above, override #assertNotNull.
     * @param mixed $value Should not be null
     */
    function assertNotNull($value, $message = '%s should not be NULL') {
        return $this->assert(
            new NotIdenticalExpectation(null),
            $value,
            $message
        );
    }

    /**
     * Assert that a string begins with another string.
     * @param  String $haystack String to search within
     * @param  String $needle   Substring that should start $haystack
     * @param  string $message  Optional error message
     */
    function assertBeginsWith($haystack, $needle, $message = '') {
        return $this->assertTrue(
            Text::beginsWith($haystack, $needle),
            $message ?: "String '$haystack' should start with '$needle'"
        );
    }

    /**
     * Assert that a string ends with another string.
     * @param  String $haystack String to search within
     * @param  String $needle   Substring that should end $haystack
     * @param  string $message  Optional error message
     */
    function assertEndsWith($haystack, $needle, $message = '') {
        return $this->assertTrue(
            Text::endsWith($haystack, $needle),
            $message ?: "String '$haystack' should end with '$needle'"
        );
    }

    /**
     * Assert that a string contains another string.
     * @param  String $haystack String to search within
     * @param  String $needle   Substring that should be contained in $haystack
     * @param  string $message  Optional error message
     */
    function assertStringContains($haystack, $needle, $message = '') {
        return $this->assertTrue(
            Text::stringContains($haystack, $needle),
            $message ?: "String '$haystack' should contain '$needle'"
        );
    }

    /**
     * Assert that a string doesn't contain another string.
     * @param  String $haystack String to search within
     * @param  String $needle   Substring that should not be contained in $haystack
     * @param  string $message  Optional error message
     */
    function assertStringDoesNotContain($haystack, $needle, $message = '') {
        return $this->assertFalse(
            Text::stringContains($haystack, $needle),
            $message ?: "String '$haystack' should not contain '$needle'"
        );
    }

    /**
     * Checks the boilerplate assertions that a given response object is valid and has no errors or warnings.
     * @param object $responseObject ResponseObject instance to check
     * @param string $returnValidationMethodOrClassName Method to run on the sub result property of the response object or object classname to verify. If a method
     *                                                  is passed it's expected to return true. If an instance is passed, it will be verified with assertIsA()
     * @param int $errorCount Number of errors to expect within the response object
     * @param int $warningCount Number of warnings to expect within the response object
     */
    function assertResponseObject($responseObject, $returnValidationMethodOrClassName = null, $errorCount = 0, $warningCount = 0){
        $this->assertIsA($responseObject, 'RightNow\Libraries\ResponseObject');
        if (!$returnValidationMethodOrClassName) $returnValidationMethodOrClassName = $responseObject->validationFunction;
        if (!$returnValidationMethodOrClassName || !is_string($returnValidationMethodOrClassName)) $returnValidationMethodOrClassName = 'is_object';
        if(is_callable($returnValidationMethodOrClassName)){
            $this->assertTrue($returnValidationMethodOrClassName($responseObject->result), "Validation method [$returnValidationMethodOrClassName] returned false");
        }
        else{
            $this->assertIsA($responseObject->result, $returnValidationMethodOrClassName);
        }

        $this->assertSame($errorCount, count($responseObject->errors), print_r($responseObject->errors, true) . " --- $callingFunction");
        $this->assertSame($warningCount, count($responseObject->warnings), print_r($responseObject->warnings, true) . " --- $callingFunction");
        return $responseObject;
    }

    /**
     * Checks that the passed in object is an instance of the provided Connect class name
     * @param  object $object Instance of object to test
     * @param  string $expectedClassName Connect class name. Full Connect namespace will be prefixed
     */
    function assertConnectObject($object, $expectedClassName) {
        $this->assertIsA($object, CONNECT_NAMESPACE_PREFIX . "\\$expectedClassName");
    }

    /**
     * Checks that the response contains the expected status code
     * @param string $response Response string
     * @param string|int $expectedStatusCode Expected status code to find in the response
     */
    function assertStatusCode($response, $expectedStatusCode) {
        // proxies (e.g. CI Hudson VMs) will use HTTP/1.0 instead of HTTP/1.1, it seems
        $this->assertTrue(Text::stringContains($response, "HTTP/1.1 $expectedStatusCode") || Text::stringContains($response, "HTTP/1.0 $expectedStatusCode"));
    }

    /**
     * Returns an anonymous function that's used to invoke the specified method using a variable-length argument list.
     * This is generally used for private methods as the accessibility is enabled via the ReflectionClass.
     * @param string $name name of the method to invoke
     * @param bool|array $isStatic Specify true if the method is static; if the method isn't static, and the class constructor
     * needs arguments passed to it, send an array of args
     * @param object $instance Object instance to use
     * @return function
     * @throws \Exception If testingClass property is not set on test class
     */
    function getMethod($name, $isStatic = false, $instance = null) {
        if (!$this->testingClass) {
            throw new \Exception("This object (" . get_class($this) . ") must have a testingClass property set to the class name of the class being tested");
        }
        if ($isStatic === true) {
            $method = new \ReflectionMethod($this->testingClass, $name);
            $instance = null;
        }
        else {
            $class = new \ReflectionClass($this->testingClass);
            if (!$instance) {
                $instance = is_array($isStatic)
                    ? $class->newInstanceArgs($isStatic)
                    : $class->newInstance();
            }
            $method = $class->getMethod($name);
        }

        $method->setAccessible(true);

        return function() use ($instance, $method) {
            $args = func_get_args();
            $params = $method->getParameters();
            for ($i = 0; $i < count($args); $i++) {
                if ($params[$i] && $params[$i]->isPassedByReference()) {
                    $args[$i] = &$args[$i];
                }
            }
            return $method->invokeArgs($instance, $args);
        };
    }

    /**
     * Returns a function that invokes the static method specified when called.
     * @param  string $name name of the method to invoke
     * @return function
     */
    function getStaticMethod($name) {
        return $this->getMethod($name, true);
    }

    /**
     * Provides quick reflection, usually so that the caller can access private
     * methods and properties in a single pass.
     *
     * If called with no args, then the \ReflectionClass of
     * the `$this->testingClass` class is returned.
     * Accepts any number of additional Strings that are names of properties or
     * methods to make accessible and return. Methods should be prefixed with 'method:'.
     * The return value is an array containing the mapped reflection class and the properties / methods.
     *
     * Example:
     *
     *          list($class, $someMethod, $privateVar) = reflect('method:someMethod', 'privateVar')
     *
     * @return mixed Value
     */
    function reflect() {
        $return = array();
        $class = new \ReflectionClass($this->testingClass);
        $return []= $class;

        if (func_num_args() > 0) {
            $args = func_get_args();

            foreach ($args as $name) {
                if (Text::beginsWith($name, 'method:')) {
                    $property = $class->getMethod(Text::getSubstringAfter($name, 'method:'));
                }
                else {
                    $property = $class->getProperty($name);
                }
                $property->setAccessible(true);
                $return []= $property;
            }
        }
        if (count($return) === 1) {
            return $class;
        }
        return $return;
    }

    /**
     * Helper function to call a function with arguments and return the result of the
     * function call and any echo'ed content.
     * @return array Array of the result of calling the function and any echo'ed content
     */
    function returnResultAndContent() {
        $function = func_get_arg(0);
        ob_start();
        if (func_num_args() > 1)
            $result = call_user_func_array($function, array_slice(func_get_args(), 1));
        else
            $result = call_user_func($function);
        $content = ob_get_clean();
        return array($result, $content);
    }

    /**
     * Helper function to echo content wrapped in a div with visibility that can be toggled.
     * @param string Content to echo
     */
    function echoContent($content) {
        if (method_exists($this->reporter, 'paintHTML')) {
            $this->reporter->paintHTML("<div class='rn_Hidden rn_verboseContent'>$content</div>");
        }
        else {
            $this->dump($content);
        }
    }

    /**
     * Alias to RightNow\UnitTest\Helpers::makeRequest
     * so that it can be called without the verbosity and ugliness
     * of namespaces and statics.
     */
    function makeRequest($url, $options = array(), $verbose = false) {
        if ($options["admin"]) {
            $acc = \RightNow\Api::account_data_get();
            $tokenPart = "--post-data='formToken=" . (\RightNow\Internal\Utils\Framework::createAdminPageCsrfToken(1, $acc->acct_id));
            foreach ($options as $key => $value) {
                if (($pos = strpos($value, "--post-data=")) !== -1) {
                    $part1 = substr($value, 0, $pos);
                    $part2 = substr($value, $pos + strlen("--post-data="));
                    $options[$key] = $part1 . $tokenPart . "&" . trim($part2, "'") . "'";
                }
            }
        }
        return Helper::makeRequest($url, $options, $verbose);
    }

    /**
     * Alias to RightNow\UnitTest\Helpers::postArrayToParams
     */
    function postArrayToParams($post) {
        return Helper::postArrayToParams($post);
    }

    /**
     * Logs in as a contact.
     * @param string $userName name of user; defaults to slatest
     * if omitted
     * @param array $profileProperties Any properties to assign onto the logged-in profile
     * @return  object \ReflectionProperty session's profileData property
     */
    function logIn($userName = 'slatest', array $profileProperties = array()) {
        $session = new \ReflectionClass('RightNow\Libraries\Session');
        $profileData = new \ReflectionProperty('RightNow\Libraries\Session', 'profileData');
        $profileData->setAccessible(true);

        $profile = $this->CI->model('Contact')->getProfileSid($userName, '', $this->CI->session->getSessionData('sessionID'))->result ?: (object) array();
        foreach ($profileProperties as $name => $val) {
            $profile->{$name} = $val;
        }
        $profileData->setValue($this->CI->session, $profile);

        return $profileData;
    }

    /**
     * Logs out the current contact.
     * @return  object \ReflectionProperty session's profileData property
     */
    function logOut() {
        $profileData = new \ReflectionProperty('RightNow\Libraries\Session', 'profileData');
        $profileData->setAccessible(true);

        if($this->CI && $this->CI->session) {
            if ($authToken = $this->CI->session->getProfileData('authToken')){
                \RightNow\Api::contact_logout(array(
                    'cookie'    => $authToken,
                    'sessionid' => $this->CI->session->getSessionData('sessionID')
                ));
            }
            $profileData->setValue($this->CI->session, null);
        }

        return $profileData;
    }

    /**
     * Inject a URL parameter into the CodeIgniter framework that can be read by Url::getParameter()
     * Note: This function should be used in conjunction with restoreUrlParameters to reset the data once a test is complete
     * @param array $parameters An array of key/value pairs to set parameters (e.g. array('kw' => 'test'))
     */
    function addUrlParameters(array $parameters) {
        $this->CI = $this->CI ?: get_instance();
        $this->parameterSegment = $this->CI->config->item('parm_segment');
        $this->routerSegments = $segments = $this->CI->router->segments;
        $firstKey = null;
        foreach($parameters as $key => $value) {
            $firstKey = $firstKey ?: $key;
            $segments[] = $key;
            $segments[] = $value;
        }
        $this->CI->router->segments = $segments;
        $this->CI->router->setUriData();
        if (!\RightNow\Utils\Url::getParameter($firstKey)) {
            $this->CI->config->set_item('parm_segment', $this->parameterSegment - 1);
        }
    }

    /**
     * Reset the original URL parameters stashed during the call to addUrlParameter
     */
    function restoreUrlParameters() {
        if (isset($this->parameterSegment))
            $this->CI->config->set_item('parm_segment', $this->parameterSegment);
        if (isset($this->routerSegments)){
            $this->CI->router->segments = $this->routerSegments;
            $this->CI->router->setUriData();
        }
    }

    /**
     * Sets the session to avoid writing out cookies and invoking
     * previously sent header errors.
     */
    function setMockSession() {
        if (!class_exists('\RightNow\Libraries\MockSession')) {
            Mock::generate('\RightNow\Libraries\Session');
        }
        $session = new \RightNow\Libraries\MockSession;
        $this->realSession = $this->CI->session;
        $this->CI->session = $session;
        $session->setSessionData(array('sessionID' => 'garbage'));
    }

    /**
     * Restores the real session
     */
    function unsetMockSession() {
        if ($this->realSession) {
            $this->CI->session = $this->realSession;
            $this->realSession = null;
        }
    }

    /**
     * Subscribes a set of test functions as hook handler functions. Note that this is an additive
     * function and existing hooks will still remain.
     * @param array $hookInfo Array of arrays with keys 'name' and 'function'
     */
    function setHooks(array $hookInfo) {
        $hooks = Helper::getHooks();
        $hooksToSet = $hooks->getValue() ?: array();
        foreach ($hookInfo as $currentHook) {
            $hooksToSet[$currentHook['name']] = $hooksToSet[$currentHook['name']] ?: array();
            $hooksToSet[$currentHook['name']][] = array(
                'class' => isset($currentHook['class']) ? $currentHook['class'] : $this->hookEndpointClass,
                'function' => $currentHook['function'],
                'filepath' => isset($currentHook['filepath']) ? $currentHook['filepath'] : Text::getSubstringAfter($this->hookEndpointFilePath, SOURCEPATH, $this->hookEndpointFilePath),
                'use_standard_model' => isset($currentHook['use_standard_model']) ? $currentHook['use_standard_model'] : false,
            );
        }
        $hooks->setValue($hooksToSet);
    }

    /**
     * Subscribes a test function as a hook handler function
     * @param string $hookName name of hook
     * @param array $data data to be sent into the hook
     * @param string $function name of the handler function. defaults
     * to generic hookEndpoint function
     * @param boolean $callHook determines whether hook should be set
     * and fired or just set. defaults to true.
     */
    function setHook($hookName, array $data = array(), $function = 'hookEndpoint', $callHook = true) {
        $hooks = Helper::getHooks();
        $hooks->setValue(array($hookName => array(array(
            'class' => $this->hookEndpointClass,
            'function' => $function,
            'filepath' => Text::getSubstringAfter($this->hookEndpointFilePath, SOURCEPATH, $this->hookEndpointFilePath)
        ))));
        if ($callHook) {
            \RightNow\Libraries\Hooks::callHook($hookName, $data);
        }
    }

    /**
     * Sets the 'isInAbuse' cookie for testing.
     * @param String $isAbuse Usually 'true' or 'false'
     */
    function setIsAbuse($isAbuse = 'true') {
        $_COOKIE['isInAbuse'] = $isAbuse;
    }

    /**
     * Clears the 'isInAbuse' cookie
     */
    function clearIsAbuse() {
        unset($_COOKIE['isInAbuse']);
    }

    /**
     * Generic hook endpoint.
     * @param object $data data to set the member variable to
     */
    static function hookEndpoint($data) {
        self::$hookData = $data;
    }

    /**
     * Generic hook error
     * @return string hook error message
     */
    static function hookError() {
        return self::$hookErrorMsg;
    }

    /**
     * Writes contents to a file.
     * @param  string $fileName Name of file
     * @param  string $data     Data to write
     */
    function writeTempFile($fileName, $data) {
        umask(0);
        FileSystem::filePutContentsOrThrowExceptionOnFailure($this->getTestDir() . "/{$fileName}", $data);
    }

    /**
     * Creates a test dir.
     */
    function writeTempDir() {
        umask(0);
        FileSystem::mkdirOrThrowExceptionOnFailure($this->getTestDir(), true);
    }

    /**
     * Erases the temp dir housing any
     * files as used as part of the test.
     */
    function eraseTempDir() {
        FileSystem::removeDirectory($this->getTestDir(), true);
        $this->testDir = null;
    }

    /**
     * Returns a list of the fixtures instance and individual fixtures from $fixtureNames
     * @param array $fixtureNames A list of fixture names to return.
     * @return array A list of the fixtures instance and individual fixtures from $fixtureNames
     */
    function getFixtures(array $fixtureNames) {
        $instance = new \RightNow\UnitTest\Fixture();
        $fixtures = array($instance);
        foreach($fixtureNames as $fixture) {
            $fixtures[] =  $instance->make($fixture);
        }
        return $fixtures;
    }

    /**
     * Calls destroy on a Connect object
     * @param ConnectPHP\RNObject Connect object to destroy
     */
    function destroyObject(ConnectPHP\RNObject $object) {
        Helper::destroyObject($object);
    }

    /**
     * Returns the test dir.
     * @return string file path to test dir
     */
    protected function getTestDir() {
        $this->testDir || ($this->testDir = sprintf("%s/unitTest/%s/", get_cfg_var('upload_tmp_dir'), get_class($this)));

        return $this->testDir;
    }

    // SMC - this is an HTML-friendly var_dump clone taken from the comments at http://php.net/manual/en/function.var-dump.php
    // Since this code is taken as-is from php.net, it isn't optimized and won't meet our coding standards;
    // but that's OK since it's only ever executed in test runs and then only rarely
    // @codingStandardsIgnoreStart
    /**
     * Better GI than print_r or var_dump -- but, unlike var_dump, you can only dump one variable.
     * Added htmlentities on the var content before echo, so you see what is really there, and not the mark-up.
     *
     * Also, now the output is encased within a div block that sets the background color, font style, and left-justifies it
     * so it is not at the mercy of ambient styles.
     *
     * Inspired from:     PHP.net Contributions
     * Stolen from:       [highstrike at gmail dot com]
     * Modified by:       stlawson *AT* JoyfulEarthTech *DOT* com
     *
     * @param mixed $var  -- variable to dump
     * @param string $var_name  -- name of variable (optional) -- displayed in printout making it easier to sort out what variable is what in a complex output
     * @param string $indent -- used by internal recursive call (no known external value)
     * @param unknown_type $reference -- used by internal recursive call (no known external value)
     */
    protected function do_dump(&$var, $var_name = NULL, $indent = NULL, $reference = NULL)
    {
        $do_dump_indent = "<span style='color:#666666;'>|</span> &nbsp;&nbsp; ";
        $reference = $reference.$var_name;
        $keyvar = 'the_do_dump_recursion_protection_scheme'; $keyname = 'referenced_object_name';

        // So this is always visible and always left justified and readable
        echo "<div style='text-align:left; background-color:white; font: 100% monospace; color:black;'>";

        if (is_array($var) && isset($var[$keyvar]))
        {
            $real_var = &$var[$keyvar];
            $real_name = &$var[$keyname];
            $type = ucfirst(gettype($real_var));
            echo "$indent$var_name <span style='color:#666666'>$type</span> = <span style='color:#e87800;'>&amp;$real_name</span><br>";
        }
        else
        {
            $var = array($keyvar => $var, $keyname => $reference);
            $avar = &$var[$keyvar];

            $type = ucfirst(gettype($avar));
            if($type == "String") $type_color = "<span style='color:green'>";
            elseif($type == "Integer") $type_color = "<span style='color:red'>";
            elseif($type == "Double"){ $type_color = "<span style='color:#0099c5'>"; $type = "Float"; }
            elseif($type == "Boolean") $type_color = "<span style='color:#92008d'>";
            elseif($type == "NULL") $type_color = "<span style='color:black'>";

            if(is_array($avar))
            {
                $count = count($avar);
                echo "$indent" . ($var_name ? "$var_name => ":"") . "<span style='color:#666666'>$type ($count)</span><br>$indent(<br>";
                $keys = array_keys($avar);
                foreach($keys as $name)
                {
                    $value = &$avar[$name];
                    $this->do_dump($value, "['$name']", $indent.$do_dump_indent, $reference);
                }
                echo "$indent)<br>";
            }
            elseif(is_object($avar))
            {
                echo "$indent$var_name <span style='color:#666666'>$type</span><br>$indent(<br>";
                foreach($avar as $name=>$value) $this->do_dump($value, "$name", $indent.$do_dump_indent, $reference);
                echo "$indent)<br>";
            }
            elseif(is_int($avar)) echo "$indent$var_name = <span style='color:#666666'>$type(".strlen($avar).")</span> $type_color".htmlentities($avar)."</span><br>";
            elseif(is_string($avar)) echo "$indent$var_name = <span style='color:#666666'>$type(".strlen($avar).")</span> $type_color\"".htmlentities($avar)."\"</span><br>";
            elseif(is_float($avar)) echo "$indent$var_name = <span style='color:#666666'>$type(".strlen($avar).")</span> $type_color".htmlentities($avar)."</span><br>";
            elseif(is_bool($avar)) echo "$indent$var_name = <span style='color:#666666'>$type(".strlen($avar).")</span> $type_color".($avar == 1 ? "TRUE":"FALSE")."</span><br>";
            elseif(is_null($avar)) echo "$indent$var_name = <span style='color:#666666'>$type(".strlen($avar).")</span> {$type_color}NULL</span><br>";
            else echo "$indent$var_name = <span style='color:#666666'>$type(".strlen($avar).")</span> ".htmlentities($avar)."<br>";

            $var = $var[$keyvar];
        }

        echo "</div>";
    }
    // @codingStandardsIgnoreEnd

    /**
     * You basically only want to call this in order to
     * stifle PHP's previously-sent-headers warning.
     */
    protected function downgradeErrorReporting() {
        error_reporting(~(E_NOTICE | E_WARNING));
    }

    /**
     * Sets back to default reporting level, everything
     * but notices.
     */
    protected function restoreErrorReporting() {
        error_reporting(~E_NOTICE);
    }

    /**
    * Tests CUD operations on SocialObjects for a given model.
    * The method being tested should be utilizing the getSocialUser() method of SocialObjectBase.
    * @param array $paramsToPass A list of parameters to pass to the model method
    * @param object $model RightNow\Models model to use for testing
    * @param string $methodToCall Case-sensitive name of the model method to test
    */
    protected function _testUserPermissionsOnModel(array $paramsToPass, $model, $methodToCall) {
        // can't perform CUD operations on a social object unless you are logged in
        $response = call_user_func_array(array($model, $methodToCall), $paramsToPass);
        $this->assertIsA($response, 'RightNow\Libraries\ResponseObject');
        $this->assertSame($response->errors[0]->errorCode, 'ERROR_USER_NOT_LOGGED_IN');

        // can't perform CUD operations on a social object unless you have a valid social user account
        $contact = $this->createContact();
        $this->logIn($contact->Login);
        $response = call_user_func_array(array($model, $methodToCall), $paramsToPass);
        $this->assertIsA($response, 'RightNow\Libraries\ResponseObject');
        $this->assertSame($response->errors[0]->errorCode, 'ERROR_USER_HAS_NO_SOCIAL_USER');
        $this->logOut();
        $this->destroyObject($contact);
        ConnectPHP\ConnectAPI::commit();

        // can't perform CUD operations on a social object unless social user has a valid DisplayName
        list($instance, $user) = $this->getFixtures(array('UserModActive'));
        $displayName = $user->DisplayName;
        $this->logIn($user->Login);
        $user->DisplayName = ' ';
        $response = call_user_func_array(array($model, $methodToCall), $paramsToPass);
        $this->assertIsA($response, 'RightNow\Libraries\ResponseObject');
        $this->assertSame($response->errors[0]->errorCode, 'ERROR_USER_HAS_BLANK_SOCIAL_USER');
        $user->DisplayName = $displayName;
        $this->logOut();
        $instance->destroy();
    }

    /**
     * Convenience method to create contacts for testing contexts.
     * @param string $displayName Optional display name for contact
     * @return Contact object
     */
    protected function createContact($displayName = null) {
        // call AbuseDetection so we can call $contact->save() later
        RightNow\Libraries\AbuseDetection::check();

        $displayNameToUse = $displayName ?: 'shibby' . round(microtime(true)) . rand();

        $contact = new ConnectPHP\Contact();
        $contact->Login = $displayNameToUse;
        $contact->NewPassword = '';
        $contact->save();
        ConnectPHP\ConnectAPI::commit();
        return ConnectPHP\Contact::fetch($contact->ID);
    }

    /**
     * Convenience method to create social users for testing contexts.
     * @param object $contact     Optional contact object for which to base social user
     * @param string $displayName Optional display name for social user
     * @return CommunityUser object
     */
    protected function createSocialUser(&$contact = null, $displayName = 'shibby') {
        // call AbuseDetection so we can call $contact->save() later
        RightNow\Libraries\AbuseDetection::check();

        // create a Contact so we know it doesn't have a CommunityUser
        // since $contact is pass-by-reference, the caller can get the contact back
        $contact = new ConnectPHP\Contact();
        $contact->login = $displayName;
        $contact->NewPassword = '';
        $contact->save();

        $socialUser = new ConnectPHP\CommunityUser();
        $socialUser->DisplayName = $displayName;
        $socialUser->Contact = $contact;
        $socialUser->save();
        return ConnectPHP\CommunityUser::fetch($socialUser->ID);
    }
}
