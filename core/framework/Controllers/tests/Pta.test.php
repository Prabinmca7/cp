<?php
use RightNow\Utils\Text,
    RightNow\Api,
    RightNow\Utils\Config,
    RightNow\UnitTest\Helper,
    RightNow\Internal\Libraries\Encryption;

Helper::loadTestedFile(__FILE__);

class PtaTest extends CPTestCase {
    public $testingClass = 'RightNow\Controllers\Pta';
    protected $hookEndpointClass = __CLASS__;
    protected $hookEndpointFilePath = __FILE__;
    private static $ptaSecretKey = 'IJGaZMkMmuEoMs3pFpdGfpJpFHsMiwWk';
    private $originalConfigValues;
    private $time;

    function __construct() {
        $this->originalConfigValues = Helper::getConfigValues(array(
            'PTA_ENABLED',
            'PTA_ENCRYPTION_METHOD',
            'PTA_ENCRYPTION_SALT',
            'PTA_ENCRYPTION_IV',
            'PTA_ENCRYPTION_KEYGEN',
            'PTA_ENCRYPTION_PADDING',
            'PTA_SECRET_KEY',
            'PTA_EXTERNAL_POST_LOGOUT_URL',
            'CP_FORCE_PASSWORDS_OVER_HTTPS',
        ));
        $this->time = time();
        parent::__construct();
    }

    function setUpBeforeClass() {
        $this->setMinimalPtaConfigs();
        parent::setUpBeforeClass();
    }

    function tearDownAfterClass() {
        $this->resetConfigs();
        parent::tearDownAfterClass();
    }

    function setMinimalPtaConfigs() {
        $this->setConfigs(array(
            'PTA_ENABLED' => 1,
            'CP_FORCE_PASSWORDS_OVER_HTTPS' => 0,
            'PTA_SECRET_KEY' => self::$ptaSecretKey,
            'PTA_EXTERNAL_POST_LOGOUT_URL' => '/app/home'
        ));
    }

    /**
     * Sets PTA configs to their original, pre-test values.
     * @param Boolean $ptaEnabled If true, set the minimal PTA configs to allow certain tests to run.
     */
    function resetConfigs($ptaEnabled = false) {
        $this->setConfigs($this->originalConfigValues);
        if ($ptaEnabled) {
            $this->setMinimalPtaConfigs();
        }
    }

    function setConfigs(array $configs, $save = true){
        if ($save) {
            // Set at the database level
            Helper::setConfigValues($configs, true);
        }
        // Set locally
        Helper::setConfigValues($configs);
    }

    function getPtaToken($encode = true, $extras = '') {
        static $counter = 0;
        $counter++;
        $userID = "pta_test{$counter}" . $this->time;
        $tokens = array(
            "p_userid={$userID}",
            "p_passwd=",
            "p_email={$userID}@email.null",
            "p_li_passwd=" . self::$ptaSecretKey
        );
        $tokenString = implode('&', $tokens) . $extras;
        return array($userID, $encode ? Api::encode_base64_urlsafe($tokenString) : $tokenString);
    }

    function testLogin() {
        list(,$ptaToken) = $this->getPtaToken();
        $output = $this->makeRequest("/ci/pta/login/p_li/$ptaToken", array('justHeaders' => true));
        list($match, $profileCookieString) = $this->extractProfileCookie($output);

        $profileCookieString = urldecode($profileCookieString);
        $profileCookie = json_decode(Api::ver_ske_decrypt($profileCookieString));
        $this->assertIsA($profileCookie, 'stdClass');
        $this->assertNotNull($profileCookie->p);
        $this->assertNotNull($profileCookie->c);
        $this->assertIsA($profileCookie->p, 'bool');
        $this->assertIsA($profileCookie->c, 'string');
    }
    // QA 230607-000075 - Account creation with user name more than 80 characters using PTA Unit test
    function getLongPtaToken($encode = true, $extras = '') {
        static $counter = 0;
        $counter++;
        $userID = "pta_test_pta_test_pta_test_pta_test_pta_test_pta_test_pta_test_pta_test_pta_test_pta_test_pta_test_pta_test_pta_test_pta_test_pta_test_pta_test_pta_test_pta_test_pta_test_pta_test_pta_test_pta_test{$counter}" . $this->time;
        $tokens = array(
            "p_userid={$userID}",
            "p_passwd=",
            "p_email={$userID}@email.null",
            "p_li_passwd=" . self::$ptaSecretKey
        );
        $tokenString = implode('&', $tokens) . $extras;
        return array($userID, $encode ? Api::encode_base64_urlsafe($tokenString) : $tokenString);
    }

    function testLongLogin() {
        list(,$ptaToken) = $this->getLongPtaToken();
        $output = $this->makeRequest("/ci/pta/login/p_li/$ptaToken", array('justHeaders' => true));
        list($match, $profileCookieString) = $this->extractProfileCookie($output);

        $profileCookieString = urldecode($profileCookieString);
        $profileCookie = json_decode(Api::ver_ske_decrypt($profileCookieString));
        $this->assertIsA($profileCookie, 'stdClass');
        $this->assertNotNull($profileCookie->p);
        $this->assertNotNull($profileCookie->c);
        $this->assertIsA($profileCookie->p, 'bool');
        $this->assertIsA($profileCookie->c, 'string');
    }
    /**
     * The following tokens were generated via Python using pyCrypto and pbkdf2
     * and can be successfully decrypted via ske_buffer_decrypt.
     *
     * ---------------------------------------------------------------------
     * #!/nfs/project/spage/python/python27/bin/python
     * import base64
     *
     * # https://www.dlitz.net/software/python-pbkdf2/
     * # Used for key derivation based on secretKey and salt values.
     * from pbkdf2 import PBKDF2
     *
     * # https://www.dlitz.net/software/pycrypto/
     * from Crypto.Cipher import DES3
     * from Crypto.Cipher import AES
     *
     * raw = 'p_email=pta_test1@email.null&p_userid=pta_test1&p_li_passwd='
     * secretKey = 'a0b1c2d3e4f5g6h7i8j9k0l1m2n3o4p5q6r7' # 36 bytes
     * input = raw + secretKey # (96 bytes)
     * salt = 'abcd0123' # 8 bytes
     */
    function getExternallyGeneratedTokens() {
        return array(
            // ## des3 ##
            // iv = '3210dcba' # 8 bytes
            // key = PBKDF2(secretKey, salt).read(24)
            // token = base64.b64encode(DES3.new(key, DES3.MODE_CBC, iv).encrypt(input))
            array(
                'input' => 'p_email=pta_test1@email.null&p_userid=pta_test1&p_li_passwd=a0b1c2d3e4f5g6h7i8j9k0l1m2n3o4p5q6r7',
                'token' => 'rLNQ6won5rZ2PKoUMIpzI13vLhCDY0rEgB1YdH3jLX4iXNJzONn6GjHcDe+ht3fdNiYcNN4GB8Knyesx3dhLJe03aZgyk9HGmI4IA8lCQUtDWVE5tFcWa/moswyaisFC',
                'secretKey' => 'a0b1c2d3e4f5g6h7i8j9k0l1m2n3o4p5q6r7',
                'encryptionMethod' => 'des3',
                'salt' => 'abcd0123',
                'initializationVector' => '3210dcba',
            ),
            // ## des3 - RANDOM_[SALT|IV] ##
            // key = PBKDF2(secretKey, salt).read(24)
            // token = base64.b64encode(salt + iv + DES3.new(key, DES3.MODE_CBC, iv).encrypt(input))
            array(
                'input' => 'p_email=pta_test1@email.null&p_userid=pta_test1&p_li_passwd=a0b1c2d3e4f5g6h7i8j9k0l1m2n3o4p5q6r7',
                'token' => 'YWJjZDAxMjMzMjEwZGNiYayzUOsKJ+a2djyqFDCKcyNd7y4Qg2NKxIAdWHR94y1+IlzSczjZ+hox3A3vobd33TYmHDTeBgfCp8nrMd3YSyXtN2mYMpPRxpiOCAPJQkFLQ1lRObRXFmv5qLMMmorBQg==',
                'secretKey' => 'a0b1c2d3e4f5g6h7i8j9k0l1m2n3o4p5q6r7',
                'encryptionMethod' => 'des3',
                'salt' => 'ENCODED', // RANDOM_SALT',
                'initializationVector' => 'ENCODED', // RANDOM_IV',
            ),
            // ## aes128 ##
            // iv = '76543210hgfedcba' # 16 bytes
            // key = PBKDF2(secretKey, salt).read(16)
            // token = base64.b64encode(AES.new(key, AES.MODE_CBC, iv).encrypt(input))
            array(
                'input' => 'p_email=pta_test1@email.null&p_userid=pta_test1&p_li_passwd=a0b1c2d3e4f5g6h7i8j9k0l1m2n3o4p5q6r7',
                'token' => 'xe0zIWm1e6Xv8NWgK5cKQ5I3VcdjojpGVbsrWHc+yvGAq8BHNTwjWEDZjsSc6IKpNgjGpvoOOHDvHMUgon7Ou49bsjFWOx1EKLuyG6f4nAzbAqGFJ8vvVQxq861yACpl',
                'secretKey' => 'a0b1c2d3e4f5g6h7i8j9k0l1m2n3o4p5q6r7',
                'encryptionMethod' => 'aes128',
                'salt' => 'abcd0123',
                'initializationVector' => '76543210hgfedcba',
            ),
            // ## aes192 ##
            // iv = '76543210hgfedcba' # 16 bytes
            // key = PBKDF2(secretKey, salt).read(24)
            // token = base64.b64encode(AES.new(key, AES.MODE_CBC, iv).encrypt(input))
            array(
                'input' => 'p_email=pta_test1@email.null&p_userid=pta_test1&p_li_passwd=a0b1c2d3e4f5g6h7i8j9k0l1m2n3o4p5q6r7',
                'token' => 'v1DrzxbcEBrNV+dYXCV1kbLFWz2bwmuoUVYuSdDMsgoEWOUlyMQQ8lPLCpfK2mKduJ90xFGWR77zwBFwDJZr5rzSU6AHgXjiVZbRH/mYKUucPA67F/T0obcLNfDwGSC6',
                'secretKey' => 'a0b1c2d3e4f5g6h7i8j9k0l1m2n3o4p5q6r7',
                'encryptionMethod' => 'aes192',
                'salt' => 'abcd0123',
                'initializationVector' => '76543210hgfedcba',
            ),
            // ## aes256 ##
            // iv = '76543210hgfedcba' # 16 bytes
            // key = PBKDF2(secretKey, salt).read(32)
            // token = base64.b64encode(AES.new(key, AES.MODE_CBC, iv).encrypt(input))
            array(
                'input' => 'p_email=pta_test1@email.null&p_userid=pta_test1&p_li_passwd=a0b1c2d3e4f5g6h7i8j9k0l1m2n3o4p5q6r7',
                'token' => 'PiqPckgwA9dQqxiDFjjU2soeYyQHuQB6g+6JxUmPjVLw2xyLyoRXIuK8k+4Bp/00kS7zXYG/I1/+P5x1oDSy0ytp5eWyq7/52mzzy1D/Rng1+8IU+3WIJoRn6z1h6M1U',
                'secretKey' => 'a0b1c2d3e4f5g6h7i8j9k0l1m2n3o4p5q6r7',
                'encryptionMethod' => 'aes256',
                'salt' => 'abcd0123',
                'initializationVector' => '76543210hgfedcba',
            ),
            // ## aes RANDOM_[SALT|IV] ##
            // iv = '76543210hgfedcba' # 16 bytes
            // key = PBKDF2(secretKey, salt).read(32)
            // token = base64.b64encode(salt + iv + AES.new(key, AES.MODE_CBC, iv).encrypt(input))
            array(
                'input' => 'p_email=pta_test1@email.null&p_userid=pta_test1&p_li_passwd=a0b1c2d3e4f5g6h7i8j9k0l1m2n3o4p5q6r7',
                'token' => 'YWJjZDAxMjM3NjU0MzIxMGhnZmVkY2JhPiqPckgwA9dQqxiDFjjU2soeYyQHuQB6g+6JxUmPjVLw2xyLyoRXIuK8k+4Bp/00kS7zXYG/I1/+P5x1oDSy0ytp5eWyq7/52mzzy1D/Rng1+8IU+3WIJoRn6z1h6M1U',
                'secretKey' => 'a0b1c2d3e4f5g6h7i8j9k0l1m2n3o4p5q6r7',
                'encryptionMethod' => 'aes256',
                'salt' => 'ENCODED', // RANDOM_SALT',
                'initializationVector' => 'ENCODED', // RANDOM_IV',
            ),
        );
    }

    function testExternallyGeneratedTokens() {
        $method = $this->getMethod('_convertPtaStringToArray');

        $this->setConfigs(array(
            'PTA_ENCRYPTION_KEYGEN' => 2,
            'PTA_ENCRYPTION_PADDING' => 2, // RSSL_PAD_NONE
        ), false);

        $url = '/ci/pta/login/redirect/home/p_li';

        foreach ($this->getExternallyGeneratedTokens() as $args) {
            $salt = $args['salt'] === 'ENCODED' ? 'ENCODED' : bin2hex($args['salt']);
            $iv = $args['initializationVector'] === 'ENCODED' ? 'ENCODED' : bin2hex($args['initializationVector']);
            $this->setConfigs(array(
                'PTA_ENCRYPTION_METHOD' => $args['encryptionMethod'],
                'PTA_ENCRYPTION_SALT' => $salt,
                'PTA_ENCRYPTION_IV' => $iv,
                'PTA_SECRET_KEY' => $args['secretKey'],
            ), false);

            $token = strtr($args['token'], array('+' => '_', '/' => '~', '=' => '!'));
            $output = $method($token);
            $expected = array();
            foreach(explode('&', $args['input']) as $line) {
                list($key, $value) = explode('=', $line);
                $expected[$key] = $value;
            }
            $this->assertIdentical($expected, $output);

            // TODO: figure how to get makeRequest to work, as the same url/token from the encryptionGenerator works..
            // $output = $this->makeRequest("$url/$token" , array('justHeaders' => true));
            // Location: /app/error/error_id/4 (ERROR_INVALID_DATA_FORMAT)

        }
        $this->resetConfigs(true);
    }

    // @@@ QA 130411-000037 - make sure the contact does not exist and is created
    function testContactCreation() {
        // first make sure the contact doesn't already exist
        try {
            $contact = \RightNow\Connect\v1_4\Contact::find("Login = 'pta_test02'");
            $this->assertIdentical(0, count($contact));
        }
        catch (\Exception $e) {
            $this->fail($e->getMessage());
        }

        $customFields = array(
            'date1'     => array('cfID' => 46, 'value' => strtotime("2013-05-25")),
            'datetime1' => array('cfID' => 47, 'value' => strtotime("2013-05-25 10:30:15")),
            'int1'      => array('cfID' => 48, 'value' => 8), // possible values are 1-10
            'menu1'     => array('cfID' => 49, 'value' => 24), // possible values are 23 or 24
            'optin'     => array('cfID' => 50, 'value' => true),
            'textarea1' => array('cfID' => 51, 'value' => "this is a text area\nwith a few\nnew lines in it"),
            'text1'     => array('cfID' => 52, 'value' => "this is a text field"),
            'yesno1'    => array('cfID' => 53, 'value' => true),
        );

        list($userID, $ptaToken) = $this->getPtaToken(true, $this->formatCustomFields($customFields));

        // create the contact via PTA
        list ($class, $convertPtaStringToArray, $liDataToPairs, $getProfileFromPairdata) = $this->reflect('method:_convertPtaStringToArray', 'method:_liDataToPairs', 'method:_getProfileFromPairdata');
        $instance = $class->newInstance();
        $instance->session = $this->CI->session;

        $contactDataArray = $liDataToPairs->invoke($instance, $convertPtaStringToArray->invoke($instance, $ptaToken));
        $getProfileFromPairdata->invoke($instance, $contactDataArray);

        // finally, check that the contact was created successfully and that the custom fields were set properly
        try {
            $contact = \RightNow\Connect\v1_4\Contact::find("Login = '$userID'");
            $this->assertSame(1, count($contact));
            $this->assertSame($userID, $contact[0]->Login);

            foreach ($customFields as $fieldName => $fieldData) {
                if ($fieldName == 'menu1') {
                    $this->assertSame($contact[0]->CustomFields->c->$fieldName->ID, $fieldData['value']);
                }
                else if ($fieldName == 'date1') {
                    $timestamp = strtotime($contact[0]->CustomFields->c->$fieldName);
                    $this->assertSame($timestamp, $fieldData['value']);
                }
                else {
                    $this->assertSame($contact[0]->CustomFields->c->$fieldName, $fieldData['value']);
                }
            }

            // account_login() gets called above and calls sql_commit(), so clean up manually
            $this->destroyObject($contact[0]);
            \RightNow\Connect\v1_4\ConnectAPI::commit();
        }
        catch (\Exception $e) {
            $this->fail($e->getMessage());
        }
    }

    function testContactCreationWithBlankDateFields() {
        $customFields = array(
            'date1'     => array('cfID' => 46, 'value' => ''),
            'datetime1' => array('cfID' => 47, 'value' => ''),
        );

        list($userID, $ptaToken) = $this->getPtaToken(true, $this->formatCustomFields($customFields));

        // create the contact via PTA
        list ($class, $convertPtaStringToArray, $liDataToPairs, $getProfileFromPairdata) = $this->reflect('method:_convertPtaStringToArray', 'method:_liDataToPairs', 'method:_getProfileFromPairdata');
        $instance = $class->newInstance();
        $instance->session = $this->CI->session;

        $contactDataArray = $liDataToPairs->invoke($instance, $convertPtaStringToArray->invoke($instance, $ptaToken));
        $getProfileFromPairdata->invoke($instance, $contactDataArray);

        // finally, check that the contact was created successfully and that the custom fields were set properly
        try {
            $contact = \RightNow\Connect\v1_4\Contact::find("Login = '$userID'");
            $this->assertSame(1, count($contact));
            $this->assertSame($userID, $contact[0]->Login);

            foreach ($customFields as $fieldName => $fieldData) {
                $this->assertNull($contact[0]->CustomFields->c->$fieldName);
            }

            // account_login() gets called above and calls sql_commit(), so clean up manually
            $contact[0]->destroy();
            \RightNow\Connect\v1_4\ConnectAPI::commit();
        }
        catch (\Exception $e) {
            $this->fail($e->getMessage());
        }
    }

    function testDuplicateEmails(){
        list($userID, $ptaToken) = $this->getPtaToken(false);
        $this->setConfigs(array('PTA_ERROR_URL' => 'http://www.example.com/errorCode=%error_code%'));

        $encodedToken = Api::encode_base64_urlsafe("{$ptaToken}&p_email_alt1={$userID}@email.null");
        $response = $this->makeRequest("/ci/pta/login/p_li/$encodedToken", array('justHeaders' => true));
        $this->assertIsA($response, 'string');
        $this->assertNotEqual($response, "");
        $this->assertTrue(Text::stringContains($response, "302 Moved Temporarily"), "Sent a PTA string with duplicate emails and didn't get a redirect response");
        $this->assertTrue(Text::stringContains($response, "Location: http://www.example.com/errorCode=17"), "Sent a PTA string with duplicate emails and didn't get the expected PTA error code 17 response");

        $encodedToken = Api::encode_base64_urlsafe("{$ptaToken}&p_email_alt2={$userID}@email.null");
        $response = $this->makeRequest("/ci/pta/login/p_li/$encodedToken", array('justHeaders' => true));
        $this->assertIsA($response, 'string');
        $this->assertNotEqual($response, "");
        $this->assertTrue(Text::stringContains($response, "302 Moved Temporarily"), "Sent a PTA string with duplicate emails and didn't get a redirect response");
        $this->assertTrue(Text::stringContains($response, "Location: http://www.example.com/errorCode=17"), "Sent a PTA string with duplicate emails and didn't get the expected PTA error code 17 response");

        $encodedToken = Api::encode_base64_urlsafe("{$ptaToken}&p_email_alt1={$userID}@email.null&p_email_alt2={$userID}@email.null");
        $response = $this->makeRequest("/ci/pta/login/p_li/$encodedToken", array('justHeaders' => true));
        $this->assertIsA($response, 'string');
        $this->assertNotEqual($response, "");
        $this->assertTrue(Text::stringContains($response, "302 Moved Temporarily"), "Sent a PTA string with duplicate emails and didn't get a redirect response");
        $this->assertTrue(Text::stringContains($response, "Location: http://www.example.com/errorCode=17"), "Sent a PTA string with duplicate emails and didn't get the expected PTA error code 17 response");

        $encodedToken = Api::encode_base64_urlsafe("{$ptaToken}&p_email_alt1=testuniqueemail@email.null&p_email_alt2=testuniqueemail@email.null");
        $response = $this->makeRequest("/ci/pta/login/p_li/$encodedToken", array('justHeaders' => true));
        $this->assertIsA($response, 'string');
        $this->assertNotEqual($response, "");
        $this->assertTrue(Text::stringContains($response, "302 Moved Temporarily"), "Sent a PTA string with duplicate emails and didn't get a redirect response");
        $this->assertTrue(Text::stringContains($response, "Location: http://www.example.com/errorCode=17"), "Sent a PTA string with duplicate emails and didn't get the expected PTA error code 17 response");

        $this->setConfigs(array('PTA_ERROR_URL' => ''));
    }

    function testLogout() {
        $response = $this->makeRequest("/ci/pta/logout", array('justHeaders' => true));
        $this->assertIsA($response, 'string');
        $this->assertNotEqual($response, "");
        $this->assertTrue(Text::stringContains($response, "/app/home"));
        $this->assertTrue(Text::stringContains($response, "302 Moved Temporarily"));
    }

    function testEnsureCookiesEnabled() {
        $response = $this->makeRequest("/ci/pta/ensureCookiesEnabled", array('justHeaders' => true));
        $this->assertIsA($response, 'string');
        $this->assertNotEqual($response, "");
        $this->assertTrue(Text::stringContains($response, "/app/error/error_id/7"));
        $this->assertTrue(Text::stringContains($response, "302 Moved Temporarily"));
    }

    private function extractProfileCookie($output) {
        $matches = preg_match('/Set-Cookie: cp_profile=([A-Za-z0-9_%~!]+);/', $output, $profileCookie);
        return array($matches, $profileCookie[1]);
    }

    function testPtaHooks() {
        $makeRequest = function($hookName) {
            return json_decode(\RightNow\UnitTest\Helper::makeRequest("/ci/unitTest/wgetRecipient/invokeTestMethod/"
                . urlencode(__FILE__) . "/" . __CLASS__ . "/callConvertPtaString/$hookName"));
        };

        $expectedMessage = 'Exiting convertPtaString before the redirect happens.';

        $hookName = 'pre_pta_decode';
        $output = $makeRequest($hookName);
        $this->assertEqual($expectedMessage, $output->message);
        $this->assertIsA($output->hookData->data->p_li, 'string');
        //reset this hook so we can test the next hook
        $this->setHook($hookName, array());

        $hookName = 'pre_pta_convert';
        $output = $makeRequest($hookName);
        $this->assertEqual($expectedMessage, $output->message);
        $this->assertIsA($output->hookData->data->decodedData, 'stdClass');
        $this->assertTrue(Text::beginsWith($output->hookData->data->decodedData->p_userid, 'pta_test'));
    }

    function callConvertPtaString() {
        $hookName = Text::getSubstringAfter($this->CI->uri->uri_string(), 'callConvertPtaString/');
        $this->setHook($hookName, array(), 'convertPtaStringEndpoint', false);

        list(,$ptaToken) = $this->getPtaToken();
        $convertPtaStringMethod = $this->getMethod('_convertPtaStringToArray');
        $convertPtaStringMethod($ptaToken);
    }

    function testConvertPtaStringToArray() {
        $convertPtaStringToArray = $this->getMethod('_convertPtaStringToArray');

        // No encryption method
        $userID = 'pta_test1';
        $email = "$userID@email.null";
        $secret = self::$ptaSecretKey;
        $password = false;
        $rawToken = "p_userid={$userID}&p_passwd={$password}&p_email={$email}&p_li_passwd={$secret}";
        $ptaToken = base64_encode($rawToken);
        $actual = $convertPtaStringToArray($ptaToken);
        $expected = array(
            'p_userid' => $userID,
            'p_passwd' => false,
            'p_email' => $email,
            'p_li_passwd' => $secret
        );
        $this->assertIdentical($expected, $actual);

        // des3 - no SALT or IV
        require_once(CPCORE . 'Internal/Libraries/Encryption.php');
        $encryptionMethod = 'des3';
        $this->setConfigs(array(
            'PTA_ENCRYPTION_METHOD' => $encryptionMethod,
        ), false);

        $args = Encryption::getApiCryptArgs($encryptionMethod, $rawToken);
        list($encrypted, $error) = Encryption::ApiEncrypt($args);
        $this->assertEqual(null, $error);
        $ptaToken = base64_encode($encrypted);
        $actual = $convertPtaStringToArray($ptaToken);
        $this->assertIdentical($expected, $actual);

        // des3 with SALT and IV
        $salt = 'abcd0123';
        $iv = '3210dcba';
        $encodedSalt = bin2hex($salt);
        $encodedIV = bin2hex($iv);
        $this->setConfigs(array(
            'PTA_ENCRYPTION_SALT' => $encodedSalt,
            'PTA_ENCRYPTION_IV' => $encodedIV,
        ), false);
        $args = Encryption::getApiCryptArgs($encryptionMethod, $rawToken);
        list($encrypted, $error) = Encryption::ApiEncrypt($args);
        $this->assertEqual(null, $error);
        $ptaToken = base64_encode($encrypted);
        $actual = $convertPtaStringToArray($ptaToken);
        $this->assertIdentical($expected, $actual);

        $this->resetConfigs(true);
    }

    function testZeroPadding() {
        // QA 150617-000166 - Make sure the data returned with RSSL_PAD_ZERO
        // is exactly the same as was given to encryption library
        $convertPtaStringToArray = $this->getMethod('_convertPtaStringToArray');

        // No encryption method
        $userID = 'pta_test1';
        $email = "$userID@email.null";
        $secret = self::$ptaSecretKey;
        $password = false;
        $rawToken = "p_userid={$userID}&p_passwd={$password}&p_email={$email}&p_li_passwd={$secret}";
        $expected = array(
            'p_userid' => $userID,
            'p_passwd' => false,
            'p_email' => $email,
            'p_li_passwd' => $secret
        );

        require_once(CPCORE . 'Internal/Libraries/Encryption.php');
        $encryptionMethod = 'aes128';
        $paddingMethod = RSSL_PAD_ZERO;
        $this->setConfigs(array(
            'PTA_ENCRYPTION_METHOD' => $encryptionMethod,
            'PTA_ENCRYPTION_PADDING' => $paddingMethod,
        ), false);

        $args = Encryption::getApiCryptArgs($encryptionMethod, $rawToken);
        list($encrypted, $error) = Encryption::ApiEncrypt($args);
        $this->assertEqual(null, $error);
        $ptaToken = base64_encode($encrypted);
        $actual = $convertPtaStringToArray($ptaToken);
        $this->assertIdentical($expected, $actual);
    }

    function convertPtaStringEndpoint($data) {
        exit(json_encode(array('message' => 'Exiting convertPtaString before the redirect happens.',
            'hookData' => $data
        )));
    }

    function testLoginHooks() {
        $makeRequest = function($hookName, $time) {
            return json_decode(\RightNow\UnitTest\Helper::makeRequest("/ci/unitTest/wgetRecipient/invokeTestMethod/"
                . urlencode(__FILE__) . "/" . __CLASS__ . "/callGetProfileFromPairdata/$hookName/$time"));
        };

        $expectedMessage = 'Exiting getProfileFromPairdata before the redirect happens.';

        $hookName = 'pre_login';
        $output = $makeRequest($hookName, $this->time);
        $this->assertEqual($expectedMessage, $output->message);
        $this->assertTrue($output->hookData->data->pta);
        $this->assertEqual($output->hookData->data->source, 'PTA');
        //reset this hook so we can test the next hook
        $this->setHook($hookName, array());

        $hookName = 'post_login';
        $output = $makeRequest($hookName, $this->time);
        $this->assertEqual($expectedMessage, $output->message);
        $this->assertIsA($output->hookData->returnValue->cookie, 'string');
        $this->assertEqual($output->hookData->returnValue->login, 'pta_test01' . $this->time);
        $this->assertEqual($output->hookData->returnValue->email, 'pta_test01' . $this->time . '@email.null');
        $this->assertEqual($output->hookData->data->source, 'PTA');
    }

    function testLiDataToPairs(){
        $method = $this->getMethod('_liDataToPairs');

        $result = $method(array('p_userid' => 'foo', 'p_li_passwd' => self::$ptaSecretKey));
        $this->assertNull($result['addr']);
        $result = $method(array('p_userid' => 'foo', 'p_li_passwd' => self::$ptaSecretKey, 'p_country_id' => '1'));
        $this->assertIdentical(1, $result['addr']['country_id']);
        $result = $method(array('p_userid' => 'foo', 'p_li_passwd' => self::$ptaSecretKey, 'p_country_id' => ' 1 '));
        $this->assertIdentical(1, $result['addr']['country_id']);
        $result = $method(array('p_userid' => 'foo', 'p_li_passwd' => self::$ptaSecretKey, 'p_country_id' => ' 1 5 '));
        $this->assertIdentical(15, $result['addr']['country_id']);
        $result = $method(array('p_userid' => 'foo', 'p_li_passwd' => self::$ptaSecretKey, 'p_country_id' => ''));
        $this->assertIdentical(INT_NULL, $result['addr']['country_id']);
        $result = $method(array('p_userid' => 'foo', 'p_li_passwd' => self::$ptaSecretKey, 'p_country_id' => 1));
        $this->assertIdentical(INT_NULL, $result['addr']['country_id']);
        $result = $method(array('p_userid' => 'foo', 'p_li_passwd' => self::$ptaSecretKey, 'p_country_id' => '0'));
        $this->assertIdentical(INT_NULL, $result['addr']['country_id']);
        $result = $method(array('p_userid' => 'foo', 'p_li_passwd' => self::$ptaSecretKey, 'p_country_id' => false));
        $this->assertIdentical(INT_NULL, $result['addr']['country_id']);
        $result = $method(array('p_userid' => 'foo', 'p_li_passwd' => self::$ptaSecretKey, 'p_country_id' => 'United States'));
        $this->assertIdentical(INT_NOT_SET, $result['addr']['country_id']);

        $result = $method(array('p_userid' => 'foo', 'p_li_passwd' => self::$ptaSecretKey, 'p_prov_id' => '1'));
        $this->assertIdentical(1, $result['addr']['prov_id']);
        $result = $method(array('p_userid' => 'foo', 'p_li_passwd' => self::$ptaSecretKey, 'p_prov_id' => ''));
        $this->assertIdentical(INT_NULL, $result['addr']['prov_id']);
        $result = $method(array('p_userid' => 'foo', 'p_li_passwd' => self::$ptaSecretKey, 'p_prov_id' => 1));
        $this->assertIdentical(INT_NULL, $result['addr']['prov_id']);
        $result = $method(array('p_userid' => 'foo', 'p_li_passwd' => self::$ptaSecretKey, 'p_prov_id' => '0'));
        $this->assertIdentical(INT_NULL, $result['addr']['prov_id']);
        $result = $method(array('p_userid' => 'foo', 'p_li_passwd' => self::$ptaSecretKey, 'p_prov_id' => false));
        $this->assertIdentical(INT_NULL, $result['addr']['prov_id']);
        $result = $method(array('p_userid' => 'foo', 'p_li_passwd' => self::$ptaSecretKey, 'p_prov_id' => 'United States'));
        $this->assertIdentical(INT_NOT_SET, $result['addr']['prov_id']);
    }

    function callGetProfileFromPairdataForUser() {
        list($login, $email) = explode('/', Text::getSubstringAfter($this->CI->uri->uri_string(), __FUNCTION__ . '/'));
        list ($class, $getProfileFromPairdata) = $this->reflect('method:_getProfileFromPairdata');
        $instance = $class->newInstance();
        $instance->session = $this->CI->session;

        echo json_encode($getProfileFromPairdata->invoke($instance, array(
            'login'         => $login,
            'email'         => array('addr' => $email),
            'password_text' => '',
            'source_upd'    => array(
                'lvl_id1'   => SRC1_EU,
                'lvl_id2'   => SRC2_EU_PASSTHRU,
            ),
        )));
    }

    function testSocialUserAssociatedWithProfile() {
        $makeRequest = function($login, $email) {
            return json_decode(\RightNow\UnitTest\Helper::makeRequest("/ci/unitTest/wgetRecipient/invokeTestMethod/"
                . urlencode(__FILE__) . "/" . __CLASS__ . "/callGetProfileFromPairdataForUser/$login/$email"));
        };

        $output = $makeRequest('slatest', 'perpetualslacontactnoorg@invalid.com');
        $this->assertIsA($output->socialUserID, 'int');
    }

    function callGetProfileFromPairdata() {
        list($hookName, $time) = explode('/', Text::getSubstringAfter($this->CI->uri->uri_string(), 'callGetProfileFromPairdata/'));
        $this->setHook($hookName, array(), 'getProfileFromPairdataEndpoint', false);

        $ptaPairData = array(
            'login'         => 'pta_test01' . $time,
            'email'         => array('addr' => 'pta_test01' . $time . '@email.null'),
            'password_text' => '',
            'source_upd'    => array(
                'lvl_id1'   => SRC1_EU,
                'lvl_id2'   => SRC2_EU_PASSTHRU,
            ),
        );

        list ($class, $getProfileFromPairdataMethod) = $this->reflect('method:_getProfileFromPairdata');
        $instance = $class->newInstance();
        $instance->session = $this->CI->session;

        $getProfileFromPairdataMethod->invoke($instance, $ptaPairData);
    }

    function getProfileFromPairdataEndpoint($data) {
        exit(json_encode(array('message' => 'Exiting getProfileFromPairdata before the redirect happens.',
            'hookData' => $data
        )));
    }

    function formatCustomFields(array $fields) {
        $result = "";
        foreach ($fields as $fieldName => $fieldData) {
            $result .= "&p_ccf_{$fieldData['cfID']}={$fieldData['value']}";
        }
        return $result;
    }
}
