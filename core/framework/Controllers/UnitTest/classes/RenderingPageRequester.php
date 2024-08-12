<?
use RightNow\Api,
    RightNow\Utils\Text,
    RightNow\Utils\FileSystem,
    RightNow\Utils\Framework,
    RightNow\Connect\v1_4\ConnectAPI;

require_once  CPCORE . 'Controllers/UnitTest/classes/Fixture.php';

/**
 * Requests test pages for rendering tests.
 */
class RenderingPageRequester {
    public $skipDeploy = false;
    public $serialRequests = false;
    public $subTestPaths = null;
    private $configBaseBackups = array();
    private $skippedTests = array();
    private $fixtureSplit = 'FIXTURE_';
    private $lastTimestamp;
    private $sessionID;
    private $sessionData;
    private $testParser;

    function __construct($testParser) {
        $this->testParser = $testParser;
        $this->lastTimestamp = time();
        $this->sessionID = Api::generate_session_id();
    }

    function requestTestPages($tests) {
        $class = new \ReflectionClass('RightNow\Libraries\Session');
        $property = $class->getProperty('sessionData');
        $property->setAccessible(true);
        $this->sessionData = $this->sessionData ?: new \stdClass();
        $this->sessionData = $property->getValue(get_instance()->session);

        if ($this->subTestPaths !== null) {
            $subTests = array(
                'subTestPath1' => array(
                    'standard/chat/'
                ),
                'subTestPath2' => array(
                    'standard/login/'
                ),
                'subTestPath3' => array(
                    'standard/navigation/'
                ),
                'subTestPath4' => array(
                    'standard/reports/'
                ),
                'subTestPath5' => array(
                    'standard/search/'
                ),
                'subTestPath6' => array(
                    'custom/'
                ),
                'subTestPath7' => array(
                    'standard/knowledgebase/'
                ),
                'subTestPath8' => array(
                    'standard/okcs/'
                ),
                'subTestPath9' => array(
                    'standard/output/'
                ),
                'subTestPath10' => array(
                    'standard/social/'
                ),
                'subTestPath11' => array(
                    'standard/surveys/'
                ),
                'subTestPath12' => array(
                    'standard/utils/'
                ),
                'subTestPath13' => array(
                    'standard/input/'
                ),
                'subTestPath14' => array(
                    'standard/discussion/'
                ),
                'subTestPath15' => array(
                    'standard/moderation/'
                ),
            );
            foreach ($tests as $key => $test) {
                if (!($test instanceof RenderingTestCase))
                    continue;
                if (array_key_exists($this->subTestPaths, $subTests)) {
                    $isSubTest = array_reduce($subTests[$this->subTestPaths], function($carry, $item) use ($test) {
                        if ($carry)
                            return $carry;
                        return Text::beginsWith($test->testPath, $item);
                    });
                    if (!$isSubTest) {
                        unset($tests[$key]);
                    }
                }
                else {
                    foreach ($subTests as $subTestKey => $subTestPaths) {
                        $isSubTest = array_reduce($subTestPaths, function($carry, $item) use ($test) {
                            if ($carry)
                                return $carry;
                            return Text::beginsWith($test->testPath, $item);
                        });
                        if ($isSubTest) {
                            unset($tests[$key]);
                            break;
                        }
                    }
                }
            }
            $tests = array_values($tests);
        }
        self::addClickstreamEntries(array(
            'SOURCE',
            'SOURCE_CLIENT',
            '/ResultList',
            '/page/CPIncidentCreate',
            '/KFAPISASuggest',
            '/IncidentCreate',
            '/KFAPISAAsk',
            '/SAResultList',
            '/ajaxRequest/getCommunityData',
            '/ajaxRequest/checkForExistingContact',
            '/ajaxRequest/CPAnswerRating',
            '/ajaxRequest/FormTokenUpdate',
            '/ajaxRequest/AccountLogin',
            '/ajaxRequest/emailPassword',
            '/ajaxRequest/emailUsername',
            '/ajaxRequest/doLogout',
            '/ajaxRequest/ReportData/Search',
            '/Search',
            '/ContentSearch',
        ));

        list($testsByUrl, $jobsByConfig) = $this->getRequestsPerConfig($tests);

        $this->enableAllPagesets();

        // Ensure that the DQA inserts and page set database changes are actually present when the child requests run.
        ConnectAPI::commit();

        foreach ($jobsByConfig as $configs => $jobs) {
            $fixtureInstance = new \RightNow\UnitTest\Fixture();
            list($configs, $fixtureNames) = explode($this->fixtureSplit, $configs);
            $this->setConfigs($configs);
            $jobsWithSql = array();
            $fixture = $variables = null;
            if ($fixtureNames) {
                foreach(explode(',', $fixtureNames) as $fixtureName) {
                    if($fixtureName = trim($fixtureName)) {
                        $fixtureInstance->make($fixtureName);
                    }
                }
                ConnectAPI::commit();
                $variables = $fixtureInstance->variables;
            }
            foreach($jobs as $key => $value){
                if($value['hasPreOrPostSql']){
                    $jobsWithSql []= $value;
                    unset($jobs[$key]);
                }
            }
            try {
                $jobResults = $this->requestJobsInParallel($jobs, false, $variables);
                foreach($jobsWithSql as $job){
                    $jobResult = $this->requestJobsInParallel(array($job), false, $variables);
                    $jobResults = array_merge($jobResults, $jobResult);
                }
                $this->restoreConfigBaseBackups();
            }
            catch (\Exception $ex) {
                $this->restoreConfigBaseBackups();
                throw $ex;
            }

            // Assigns the output of the request to the test case.
            foreach ($jobResults as $url => $output) {
                $testsByUrl[$url]->output = $output['body'];
                $testsByUrl[$url]->statusCode = $output['statusCode'];
                $testsByUrl[$url]->outputHeaders = $output['headers'];
                $testsByUrl[$url]->variables = $variables;
            }
            $fixtureInstance->destroy();
        }
        $this->restorePagesets();

        // Ensure that the page set database (reset) changes are actually committed.
        ConnectAPI::commit();

        return array_merge(array_values($testsByUrl), $this->skippedTests);
    }

    function requestJobsInParallel($jobs, $includePingOutput = true, array $variables = null) {
        self::initializeCurl();

        $processFixtureData = function($job) use ($variables) {
            if (!$variables) {
                return $job;
            }
            foreach ($job as $jobKey => &$jobValue) {
                if ($jobKey === 'original_url' || !is_string($jobValue))
                    continue;
                $jobValue = \RightNow\UnitTest\Helper::processFixtureData($variables, $jobValue);
            }
            return $job;
        };

        //This max should remain below 151 which is the default maximum number of MySQL DB connections (the API breaks above 151).
        //I've arbitrarily set it to 100 because (as of this writing) we have 187 requests that we'd like to do in parallel, but because of the
        //SQL connections limit, we can never run all the requests in a single go, so I split at 100 which should cover up
        //to 200 tests in 2 rounds of requests. If for some reason we decide that 100 parallel requests is too many, we can adjust
        //this number accordingly.
        //This used to be 100, but trying to set it to 50 to see if ISEs are reduced when too many things are running at once.
        //Only do 10 for integration tests in case we're overloading things
        $maxConnections = ($this->serialRequests) ? 1 : ($this->subTestPaths && $this->subTestPaths !== 'normal' ? 10 : 25);
        $maxConnections = min($maxConnections, count($jobs));

        $multiHandle = curl_multi_init();
        $handles = array();

        //Create `maxConnections` CURL handles and add them
        for($i = 0; $i < $maxConnections; $i++) {
            $job = array_shift($jobs);
            $job = $processFixtureData($job);
            $handle = $this->getCurlHandle($job);
            $handles[(string)$handle] = $job['original_url'] ?: $job['url'];
            curl_multi_add_handle($multiHandle, $handle);
        }

        $results = array();
        do {
            //Keep calling curl_multi_exec until one of the handles has completed
            while(($status = curl_multi_exec($multiHandle, $remainingHandles)) === CURLM_CALL_MULTI_PERFORM);

            if($status !== CURLM_OK) {
                throw new \Exception("CURL Error: code $status");
            }

            //Include ping output every 60 seconds to keep apache running
            if ($includePingOutput && (time() - $this->lastTimestamp > 60)) {
                $this->writePingOutput();
                $this->lastTimestamp = time();
            }

            //At least one handle has completed, read the data
            while($completed = curl_multi_info_read($multiHandle)) {
                $handleInfo = curl_getinfo($completed['handle']);
                $response = curl_multi_getcontent($completed['handle']);

                $results[$handles[(string)$completed['handle']]] = array(
                    'body'       => substr($response, $handleInfo['header_size']),
                    'headers'    => substr($response, 0, $handleInfo['header_size']),
                    'statusCode' => $handleInfo['http_code'],
                );

                //Add in another handle to replace the completed one
                if(count($jobs)) {
                    $job = array_shift($jobs);
                    $job = $processFixtureData($job);
                    $handle = $this->getCurlHandle($job);
                    $handles[(string)$handle] = $job['original_url'] ?: $job['url'];
                    curl_multi_add_handle($multiHandle, $handle);
                }

                curl_multi_remove_handle($multiHandle, $completed['handle']);
                curl_close($completed['handle']);
            }
        } while($remainingHandles > 0);

        curl_multi_close($multiHandle);
        return $results;
    }

    private function getRequestsPerConfig(array $tests) {
        $jobsByConfig = $testsByUrl = array();

        foreach ($tests as $test) {
            if (!($test instanceof RenderingTestCase)) {
                $this->skippedTests []= $test;
                continue;
            }
            if ($this->jsFunctional && !$test->originalTestData['jstestfile']) {
                continue;
            }

            self::addClickstreamEntries(array(
                Text::getSubstringAfter($test->urlWithoutParameters, '/app'),
                Text::getSubstringAfter($test->urlWithoutParameters, '/app') . '/Search'
            ));

            if (isset($test->testData['config'])) {
                $config = $test->testData['config'] = $this->normalizeConfigSetterLine($test->testData['config']);
            }
            else {
                $config = '';
            }

            if ($test->testData['fixture']) {
                $fixtures = $test->testData['fixture'];
            }
            else if ($test->testData['fixtures']) {
                $fixtures = $test->testData['fixtures'];
            }
            if ($fixtures) {
                $config .= $this->fixtureSplit . $fixtures;
            }

            $testsByUrl[$test->testUrl] = $test;
            if (strlen($test->testData['cp_profile']) > 0)
                $test->testData['cookies'] .= "cp_profile={$test->testData['cp_profile']};";

            if (strlen($test->testData['cp_session']) > 0) {
                $test->testData['cookies'] .= "cp_session={$test->testData['cp_session']};";
            }
            else {
                $time = time();
                $test->testData['cookies'] .= 'cp_session=' . urlencode(Api::ver_ske_encrypt_fast_urlsafe(json_encode(array('s' => array('s' => $this->sessionID, 'e' => '/session/' . base64_encode("/time/$time/sid/" . $this->sessionID), 'l' => $time, 'i' => Api::intf_id()))))) . ';';
            }

            if ($this->skipDeploy) {
                $test->testData['cookies'] .= "location=development~" . Framework::createLocationToken("development") . ";";
            }

            if (!isset($jobsByConfig[$config])) {
                $jobsByConfig[$config] = array();
            }
            $jobsByConfig[$config][]= array(
                'original_url'    => $test->testUrl,
                'url'             => $test->testUrl,
                'cookies'         => $test->testData['cookies'],
                'post'            => $test->testData['post'],
                'postftok'        => $test->testData['postftok'],
                'validationtoken' => $test->testData['validationtoken'],
                'hasPreOrPostSql' => ($test->testData['presql'] || $test->testData['postsql']),
                'sessionID'       => $test->testData['sessionID'] ?: $this->sessionID,
            );
        }

        return array($testsByUrl, $jobsByConfig);
    }

    /**
     * All pagesets need to be enabled so that we can request certain pages
     */
    private function enableAllPagesets() {
        // enable all standard page sets in the databse for the rendering run
        $this->oldPagesetMappingDatabase = \RightNow\Internal\Sql\Pageset::get();
        foreach ($this->oldPagesetMappingDatabase as $pageSet) {
            if ($pageSet['page_set_id'] < CP_FIRST_CUSTOM_PAGESET_ID && !($pageSet['attr'] & UA_ATTR_ENABLED)) {
                // update the database with enabled flag
                \RightNow\Api::cp_ua_mapping_update(array(
                    'page_set_id' => $pageSet['page_set_id'],
                    'attr' => $pageSet['attr'] | UA_ATTR_ENABLED
                ));
            }
        }
        //If we are deploying the pages, write out a special pageset file which enables all page sets (otherwise we can't test some widgets)
        if(!$this->skipDeploy) {
            // hard-code all standard page set entries to be enabled
            $pageSetMappingPath = OPTIMIZED_FILES . 'production/optimized/config/pageSetMapping.php';
            $pageSetMappingStandardEnabled = "<?
                function getPageSetMapping() {
                    return array( );
                }
                function getRNPageSetMapping() {
                    return array( 1 => new \RightNow\Libraries\PageSetMapping(array('id' => 1, 'item' => '/iphone/i', 'description' => 'iPhone', 'value' => 'mobile', 'enabled' => true, 'locked' => true)), 2 => new \RightNow\Libraries\PageSetMapping(array('id' => 2, 'item' => '/Android/i', 'description' => 'Android', 'value' => 'mobile', 'enabled' => true, 'locked' => true)), 4 => new \RightNow\Libraries\PageSetMapping(array('id' => 4, 'item' => '/(?:docomo|kddi|softbank|vodafone|willcom|emobile)/i', 'description' => 'Basic', 'value' => 'basic', 'enabled' => true, 'locked' => true)));
                }
            ";
            $this->oldPagesetMapping = false;
            if (FileSystem::isReadableFile($pageSetMappingPath)) {
                $this->oldPagesetMapping = file_get_contents($pageSetMappingPath);
            }
            try {
                FileSystem::filePutContentsOrThrowExceptionOnFailure($pageSetMappingPath, $pageSetMappingStandardEnabled);
            }
            catch (\Exception $e) {
                //Don't throw error during unit tests
            }
        }


    }

    /**
     * Restore the original pageset state, including the database
     */
    private function restorePagesets() {
        foreach ($this->oldPagesetMappingDatabase as $pageSet) {
            if ($pageSet['page_set_id'] < CP_FIRST_CUSTOM_PAGESET_ID && !($pageSet['attr'] & UA_ATTR_ENABLED)) {
                // since the page set was previously NOT enabled, update the database without the enabled flag
                \RightNow\Api::cp_ua_mapping_update(array(
                    'page_set_id' => $pageSet['page_set_id'],
                    'attr' => $pageSet['attr'] | !UA_ATTR_ENABLED
                ));
            }
        }
        if(!$this->skipDeploy) {
            if ($this->oldPagesetMapping !== false) {
                try {
                    FileSystem::filePutContentsOrThrowExceptionOnFailure($pageSetMappingPath, $pageSetMapping);
                }
                catch (\Exception $e) {
                    //Don't throw error during unit tests
                }
            }
            else {
                @unlink($pageSetMappingPath);
            }
        }
    }

    private function normalizeConfigSetterLine($configSetterLine) {
        $tuples = $this->testParser->parseConfigSetterLine($configSetterLine);
        foreach ($tuples as &$tuple) {
            $tuple = implode('|', $tuple);
        }
        sort($tuples);
        return implode(',', $tuples);
    }

    private function setConfigs($configSetterLine) {
        if (!strlen($configSetterLine)) {
            return;
        }
        $configs = $this->testParser->parseConfigSetterLine($configSetterLine);
        foreach ($configs as $tuple) {
            list($configKey, $newValue) = $tuple;
            if (isset($this->configBaseBackups[$configKey])) {
                throw new \Exception("I can't set the $configKey config because it's already been set.");
            }
            // Changes to make the first config parameter dynamic
            // example : Input : http://__OE_WEB_SERVER__/ci/unitTest/OkcsKmApi/endpoint/latest/
            // output : http://<site name>/ci/unitTest/OkcsKmApi/endpoint/latest/
            $tagPattern = '__';
            if (preg_match_all('/' . preg_quote($tagPattern) . '(.*?)' . preg_quote($tagPattern) . '/s', $newValue, $matches)) {
                $newValue = str_replace($matches[0][0], \RightNow\Utils\Config::getConfig(constant($matches[1][0])), $newValue);
            }
            $oldConfigValues = \Rnow::updateConfig($configKey, $newValue);
            $this->configBaseBackups[$configKey] = $oldConfigValues;
        }
    }

    private function restoreConfigBaseBackups() {
        if (count($this->configBaseBackups) === 0) {
            return;
        }
        foreach ($this->configBaseBackups as $configNumber => $oldConfigValues) {
            \Rnow::updateConfig($configNumber, $oldConfigValues);
        }
        $this->configBaseBackups = array(); // Is there a better way to clear a PHP array?
    }

    // When we request multiple pages simultaneously that haven't been previously requested before, there's a
    // race condition when not actually using DQA to get the next cs_actions.action_id value.  When the racing processes
    // sql_commit(), the loser complains about a duplicate cs_actions.action_id value.
    // I prepopulate the values to prevent that problem, allowing the pages to be requested in parallel.
    private static function addClickstreamEntries(array $actions) {
        foreach($actions as $action) {
            Api::dqa_insert(DQA_CLICKSTREAM, array(
                'cid'    => 1,
                'sid'    => '---------',
                'app'    => 1,
                'ts'     => time(),
                'action' => $action,
                'c1'     => '',
                'c2'     => '',
                'c3'     => '',
            ));
        }
    }

    /**
     * The Apache timeout is set to 5 minutes in the development and CC environments, which will end a PHP
     * process if it exceeds 5 minutes without output. In order to avoid that problem periodically write
     * output during the deploy request to make sure Apache knows that PHP is still running.
     */
    private function writePingOutput() {
        echo "# Curl connection is still running...";
        flush();
    }

    private static function initializeCurl() {
        static $curlInitialized;

        if (!isset($curlInitialized)) {
            if (!($curlInitialized = (extension_loaded('curl') || Api::load_curl()))) {
                exit("Unable to load cURL library");
            }
        }
        return $curlInitialized;
    }

    private function getCurlHandle($job) {
        if(!is_array($job) || !$job['url']) {
            throw new \Exception("Invalid job format.");
        }

        $options = array(
            CURLOPT_URL => $job['url'],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_HEADER => true,
            CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1.16) Gecko/20080702 Firefox/2.0.9.9',
        );

        if($job['cookies']) {
            $options += array(
                CURLOPT_COOKIE => $job['cookies']
            );
        }

        if ($job['headers']) {
            $options += array(
                CURLOPT_HTTPHEADER => $job['headers']
            );
        }

        // before making requests, explicitly set sessionID (necessary for BasicSmartAssistant POST tests)
        $this->sessionData = $this->sessionData ?: new \stdClass();
        $this->sessionData->sessionID = $job['sessionID'];

        //Add in any post data that we may need
        if($job['post'] || $job['postftok'] || $job['validationtoken']) {
            $postData = $job['post'] ?: '';
            if($job['postftok']) {
                $contactID = $job['postftok'] === 'true' ? 0 : (int)$job['postftok'];
                $postData .= "&f_tok=" . urlencode(\RightNow\Utils\Framework::createCsrfToken(0, 1, $contactID, false));
            }

            $validationToken = $job['validationtoken'];
            if ($validationToken) {
                //Swap out the interface
                list($constraintsHash, $interfaceName, $action, $handler) = explode('|', $validationToken);
                $validationToken = Api::encode_base64_urlsafe(Api::ver_ske_encrypt_fast_urlsafe(implode('|', array($constraintsHash, Api::intf_name(), $action, $handler))));
                $postData .= "&validationToken=" . urlencode($validationToken);
            }

            $options += array(
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => $postData
            );
        }

        $handle = curl_init();
        curl_setopt_array($handle, $options);
        return $handle;
    }
}
