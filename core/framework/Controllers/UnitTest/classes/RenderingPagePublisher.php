<?

use RightNow\Api,
    RightNow\Utils\Url,
    RightNow\Utils\Text,
    RightNow\Utils\Config,
    RightNow\Utils\FileSystem;

require_once __DIR__ . '/CPTestCase.php';
require_once __DIR__ . '/RenderingTestCase.php';

/**
 * Deals with Creating and deploying test pages for
 * rendering tests.
 */
class RenderingPagePublisher {
    public $skipDeploy = false;
    public $functionalTests = array();

    // Cookie data cached for similar requests.
    private $contactCookies = array();
    private $sessionCookies = array();

    private static $templates = array(
        'mobile' => array(
            'name'   => 'unitTest/rendering/mobile.php',
            'source' => 'tests/testBoilerplates/mobile.php'
        ),
        'basic' => array(
            'name'   => 'unitTest/rendering/basic.php',
            'source' => 'tests/testBoilerplates/basic.php',
        ),
        'standard' => array(
            'name'   => 'unitTest/rendering/standard.php',
            'source' => 'tests/testBoilerplates/standard.php',
        ),
    );

    /**
     * Constructor.
     * @param RenderingTestParser $testParser    test parser
     * @param RenderingTestRequester $testRequester test requester
     */
    function __construct($testParser, $testRequester) {
        // Make sure that PHP isn't creating files that developers can't modify.
        umask(0);

        $this->baseWorkDir = sprintf("%s/unitTest/%s", get_cfg_var('upload_tmp_dir'), "rendering");
        FileSystem::mkdirOrThrowExceptionOnFailure($this->baseWorkDir, true);

        $this->testPagesPath = APPPATH . "views/pages/unitTest/rendering/";
        $this->testPageBaseUrl = Url::getShortEufAppUrl(false, '/unitTest/rendering/');

        $this->testParser = $testParser;
        $this->testRequester = $testRequester;
    }

    function clean() {
        FileSystem::removeDirectory($this->baseWorkDir, false);
    }

    function publishTestPages($testTypes, $allTestTypes) {
        $this->deleteTestPages();

        $this->createTestTemplates();

        $tests = $this->createTestPages($testTypes, $allTestTypes);
        if (!$this->skipDeploy && !$this->deploy()) {
            exit(1);
        }
        if(!is_array($tests) || count($tests) === 0) {
            echo "No test pages were created in `createTestPages`. You should check permissions to make sure your views directory is writable.";
            exit(1);
        }
        return $tests;
    }

    function getTestCasesForPublishedPages($testTypes, $allTestTypes) {
        $tests = $this->createTestPages($testTypes, $allTestTypes, false);
        if(!is_array($tests) || count($tests) === 0) {
            echo "No test pages were created in `createTestPages`. You should check permissions to make sure your views directory is writable.";
            exit(1);
        }
        return $tests;
    }

    function deleteTestPages() {
        FileSystem::removeDirectory(APPPATH . 'views/pages/unitTest', true);
        FileSystem::removeDirectory($this->testPagesPath, true);
    }

    function saveOutputForJSWidget($tests) {
        $siteBase = Url::convertInsecureUrlToNetworkPathReference(Url::getShortEufBaseUrl());

        foreach ($tests as $test) {
            if (Text::beginsWith($test->fullTestPath, CORE_WIDGET_FILES)) {
                // Make all absolute urls fully qualified.
                $output = $test->output;
                $output = preg_replace("/(\'|\"|\()\/(rnt|ci|euf|cgi-bin)\//", "\\1$siteBase/\\2/", $test->output);
                FileSystem::filePutContentsOrThrowExceptionOnFailure($this->testPagesPath . str_replace('.test', '.html', Text::getSubstringStartingWith($test->fullTestPath, 'widgets/')), $output);
            }
        }
    }

    private function createTestTemplates() {
        $CI = get_instance();
        foreach (self::$templates as $key => $template) {
            $source = $CI->load->view($template['source'], array(
                'coreAssetPrefix' => Url::getCoreAssetPath(),
            ), true);
            FileSystem::filePutContentsOrThrowExceptionOnFailure(APPPATH . 'views/templates/' . $template['name'], $source);
        }
    }

    /**
     *  Render the widget/admin pages in /cp/customer/development/view/pages/unitTest/rendering
     *  the pages created by this function are rendered before being deployed and contain the
     *  'input:' data as well as the 'jstestfile:' data combined into one file.
     */
    private function createTestPages($currentTestTypes, $allTestTypes, $write = true) {
        $tests = array();

        foreach ($currentTestTypes as $testType) {
            assert(Text::stringContains($allTestTypes[$testType]['basePath'], $allTestTypes[$testType]['corePath']));

            if ($currentTests = $this->setupTests($testType, $allTestTypes, $write)) {
                $tests = array_merge($tests, $currentTests);
            }

            if ($allTestTypes[$testType]['customBasePath']) {
                assert(Text::stringContains($allTestTypes[$testType]['customBasePath'], $allTestTypes[$testType]['customPath']));

                if ($currentTests = $this->setupTests($testType, $allTestTypes, $write, true)) {
                    $tests = array_merge($tests, $currentTests);
                }
            }
        }

        return $tests;
    }

    private function setupTests($testType, $allTestTypes, $write, $isCustom = false) {
        $basePath = $isCustom ? $allTestTypes[$testType]['customBasePath'] : $allTestTypes[$testType]['basePath'];
        $corePath = $isCustom ? $allTestTypes[$testType]['customPath'] : $allTestTypes[$testType]['corePath'];
        $tests = array();

        $testPaths = FileSystem::removeDirectoriesFromGetDirTreeResult(self::getTestsIn($basePath, $allTestTypes[$testType]['testRegex']));
        ksort($testPaths);

        foreach (array_keys($testPaths) as $testPath) {
            $fullTestPath = $basePath . "/" . $testPath;
            $relativeTestPath = Text::getSubstringAfter($fullTestPath, $corePath);

            if ($testType === 'partials') {
                $tests []= $fullTestPath;
                continue;
            }

            $originalTestData = $this->testParser->parseTest($fullTestPath);
            $javaScriptTestContent = "";

            if ($originalTestData['jstestfile']) {
                $jsTestFileFullPath = dirname($fullTestPath) . "/{$originalTestData['jstestfile']}";
                if(!FileSystem::isReadableFile($jsTestFileFullPath)) {
                    throw new \Exception("The $testPath test specifies a JavaScript file ($jsTestFileFullPath) but that file doesn't exist or isn't readable.");
                }
                else {
                    $testFile = Url::getLongEufBaseUrl('excludeProtocolAndHost', Text::getSubstringStartingWith($jsTestFileFullPath, "/core/"));
                    $javaScriptTestContent = '<?get_instance()->clientLoader->addJavaScriptInclude(' . var_export($testFile, true) . ');?>';
                    if($this->showLinks) {
                        $this->functionalTests[] = preg_replace('@\.test$@', '', "ci/unitTest/rendering/getTestPage/$relativeTestPath", 1, $extensionWasReplaced) . $originalTestData['urlparameters'];
                    }
                }
            }

            self::validateTestFile($originalTestData, $fullTestPath);

            $testData = $this->getContactCookies(self::addTemplateMetaTag($originalTestData));
            $testPagePath = preg_replace('@\.test$@', '.php', "{$this->testPagesPath}$relativeTestPath", 1, $extensionWasReplaced);
            assert($extensionWasReplaced);

            if($testData['presql']){
                $testData['input'] = "\n<?test_sql_exec_direct(\"" . $testData['presql'] . "\");?>\n" . $testData['input'];
                if(!$testData['postsql']){
                    throw new \Exception("The $fullTestPath test specifies a preSql line, but doesn't have a postSql line to clean up after itself.");
                }
            }
            if($testData['postsql']){
                $testData['input'] .= "\n<?test_sql_exec_direct(\"" . $testData['postsql'] . "\");?>\n";
                if(!$testData['presql']){
                    throw new \Exception("The $fullTestPath test specifies a postSql line, but doesn't have a preSql line....what?.");
                }
            }

            if($testData['removecontent'] !== 'false')
                $testData['input'] = "<!-- ORACLERIGHTNOWREMOVECONTENTSTART -->{$testData['input']}<!-- ORACLERIGHTNOWREMOVECONTENTEND -->";

            if ($write) {
                //This writes out the page based off the input key. The page is a development only page and will be deployed later (unless skipDeploy is enabled)
                FileSystem::filePutContentsOrThrowExceptionOnFailure($testPagePath, $testData['input'] . $javaScriptTestContent);
            }

            $urlWithoutParameters = preg_replace('@\.test@', '', $this->testPageBaseUrl . $relativeTestPath);
            $testUrl = $urlWithoutParameters . $testData['urlparameters'];

            $tests []= new \RenderingTestCase(array(
                'urlWithoutParameters' => $urlWithoutParameters,
                'originalTestData'     => $originalTestData,
                'fullTestPath'         => $fullTestPath,
                'testData'             => $testData,
                'testPath'             => $testPath,
                'testUrl'              => $testUrl,
                'label'                => $testUrl,
            ));
        }

        return $tests;
    }

    private function deploy() {
        $deployUrl = "http://localhost/ci/admin/deploy/unitTestDeploy";
        $deployJob = array(
            'url' => $deployUrl,
            'headers' => array('Host: ' . Config::getConfig(OE_WEB_SERVER)),
        );
        $results = $this->testRequester->requestJobsInParallel(array($deployJob));
        $result = $results[$deployUrl];
        $deploySucceeded = Text::stringContains($result['body'], '<cps_error_count>0</cps_error_count>');
        if (!$deploySucceeded) {
            echo "DEPLOY FAILED\n\n";
            echo $result['body'];
        }
        return $deploySucceeded;
    }

    private static function getTestsIn($baseDirectory, $regex) {
        return FileSystem::getDirectoryTree($baseDirectory, array('regex' => $regex));
    }

    private static function validateTestFile($testData, $testPath) {
        $testPath;
        if (!$testData['input']) {
            throw new \Exception("The test file $testPath must contain an 'Input:' section with text.");
        }
        if ($testData['urlparameters'] && (!is_string($testData['urlparameters']) || $testData['urlparameters'][0] != '/')) {
            throw new \Exception("The test file $testPath contains a 'UrlParameters:' value which does not begin with a slash.");
        }
    }

    /**
     *  Generate an artificial cookie for the tests to use
     */
    private function getContactCookies($testData) {
        $contactLogin = $testData['contact'];
        $sessionData = $testData['session'];

        if (!$contactLogin && !$sessionData) {
            return $testData;
        }

        if ($contactLogin) {
            if (!$this->contactCookies[$contactLogin]) {
                if (is_array($this->contactCookies) && count($this->contactCookies) > 0) {
                    //Because the sessionID is a combination of the PID and time(), which only has 1 second granularity, we
                    //need to sleep for at least 1 second to guarantee that subsequent calls to the contact_login API
                    //have different session IDs.
                    usleep(1001000);
                }

                $sessionID = ''; // Pass a bogus sessionID to custlogin so that it will create a new one.
                $profile = (object) Api::contact_login(array(
                    'login' => $contactLogin,
                    'sessionid' => $sessionID,
                    'cookie_set' => 1,
                    'login_method' => CP_LOGIN_METHOD_LOCAL,
                ));
                if (!$profile) {
                    return $testData;
                }

                $testData['sessionID'] = $sessionID = $profile->sessionid; // Get the new, real value here.
                $session = \RightNow\Libraries\Session::getInstance();
                $socialUser = get_instance()->model('CommunityUser')->getForContact($profile->c_id)->result;
                $profile = $session->createMapping($profile);
                $profile = json_encode(array('c' => $profile->cookie, 'p' => $profile->pta_login_used, 'i' => Api::intf_id(), 's' => $socialUser ? $socialUser->ID : null));
                $profile = Api::ver_ske_encrypt_urlsafe($profile);
                $session = new \RightNow\Libraries\SessionData(array(
                    's' => array(
                        's' => $sessionID,
                        'a' => 0,
                        'n' => 0,
                        'u' => array(),
                        'p' => false,
                        'e' => '/session/' . base64_encode("/time/$time/sid/" . $sessionID),
                        'r' => null,
                        'l' => $time,
                        'i' => Api::intf_id(),
                    ),
                ));
                $session = Api::ver_ske_encrypt_fast_urlsafe(json_encode($session->convertToCookie()));

                $this->contactCookies[$contactLogin] = array(
                    'profile' => urlencode($profile),
                    'session' => urlencode($session),
                );
            }

            $testData['cp_profile'] = $this->contactCookies[$contactLogin]['profile'];
            $testData['cp_session'] = $this->contactCookies[$contactLogin]['session'];
        }
        if ($sessionData) {
            // Session data to mock for the request.
            // May be used in combination w/ contact login.
            $sessionKey = serialize($sessionData) . $contactLogin;

            if ($cached = $this->sessionCookies[$sessionKey]) {
                $testData['cp_session'] = $cached;
            }
            else {
                $sessionID || ($sessionID = Api::generate_session_id());
                $testData['sessionID'] = $sessionID;
                $session = new \RightNow\Libraries\SessionData(array(
                    's' => array(
                        's' => $sessionID,
                        'a' => (is_array($sessionData) && $sessionData['answersViewed']) ? (int)$sessionData['answersViewed'] : 0,
                        'n' => (is_array($sessionData) && $sessionData['numberOfSearches']) ? (int)$sessionData['numberOfSearches'] : 0,
                        'u' => (is_array($sessionData) && $sessionData['urlParameters'])? $sessionData['urlParameters'] : array(),
                        'p' => (is_array($sessionData) && $sessionData['ptaUsed']) ? (bool)$sessionData['ptaUsed'] : false,
                        'e' => '/session/' . base64_encode("/time/$time/sid/" . $sessionID),
                        'r' => (is_array($sessionData) && $sessionData['previouslySeenEmail']) ? Text::escapeHtml($sessionData['previouslySeenEmail']) : null,
                        'l' => $time,
                        'i' => Api::intf_id(),
                    ),
                ));
                $testData['cp_session'] = $this->sessionCookies[$sessionKey] = urlencode(Api::ver_ske_encrypt_fast_urlsafe(json_encode($session->convertToCookie())));
            }
        }

        return $testData;
    }


    private static function addTemplateMetaTag($testData) {
        $template = $testData['template'];
        if (strtolower($template) === 'none' || Text::stringContainsCaseInsensitive($testData['input'], '<html')) {
            return $testData;
        }

        $template || ($template = 'standard');
        $template = self::$templates[$template]['name'];

        $testData['input'] .= "<rn:meta template='$template'/>";
        return $testData;
    }
}
