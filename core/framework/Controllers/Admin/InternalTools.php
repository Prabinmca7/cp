<?php

namespace RightNow\Controllers\Admin;

use RightNow\Api,
    RightNow\Utils\Text,
    RightNow\Utils\Config,
    RightNow\Utils\VersionBump,
    RightNow\Utils\Framework,
    RightNow\Internal\Utils\Version,
    RightNow\Connect\v1_4 as Connect;

/**
* Internal tools; Do Not Ship
*/
class InternalTools extends Base {
    /**
     * The following values defines aren't exposed to PHP :(
     */
    private $configDataTypes = array(
        'INT' => 1,
        'MENU' => 2,
        'STRING' => 3,
        'DATETIME' => 4,
        'BOOLEAN' => 5
    );
    private $configAttributes = array(
        'HIDDEN' => 0x1,
        'REQUIRED' => 0x8,
    );
    private $configTypes = array(
        'SITE' => 1,
        'INTERFACE' => 2
    );

    function __construct() {
        parent::__construct(true, '_phonyLogin');
        $this->documentRoot = DOCROOT . "/cp";
        $this->bumper = new VersionBump();
    }

    /**
     * Supposed to do nothing.
     */
    protected function _phonyLogin() {}
    public function _ensureContactIsAllowed() {}

    function index() {
        $class = new \ReflectionClass($this);
        $className = $class->getName();
        $publicMethods = $class->getMethods(\ReflectionMethod::IS_PUBLIC);

        $methods = array();
        foreach ($publicMethods as $method) {
            // Grab all the public methods with doc block comments in here and extract their comment headers.
            if (($comment = $method->getDocComment()) && $method->getDeclaringClass()->getName() === $className) {
                $methods[$method->getName()] = preg_replace('/^\s*(\*\/*|\/\**)/m', ' ', $comment);
            }
        }

        ksort($methods);

        $this->load->view('Admin/internalTools/index', array(
            'methods' => $methods,
        ));
    }

    /**
     * Page where you can search for and update config values and search for message base values.
     */
    function config() {
        $fullConfigList = explode("\n", file_get_contents(DOCROOT . '/cp/extras/configbase.txt'));
        $configs = array();
        $listOfConfigNames = array();
        foreach($fullConfigList as $configRow){
            if(!strlen($configRow)){
                continue;
            }
            list($configID,
                 $name,
                 $value,
                 $defaultValue,
                 $dataType,
                 $type,
                 $maxLength,
                 $maxValue,
                 $minValue,
                 $menu,
                 $attributes,
                 $description) = explode("\t", $configRow);

            //Ensure that we actually have a define for this config, otherwise, the \RightNow\Utils\Config::getConfig( call will fail
            if(!defined($name)) {
                continue;
            }
            $currentValue = \RightNow\Utils\Config::getConfig(constant($name));

            $tempConfigDetails = array(
                'name' => $name,
                'dataType' => $this->getConfigType(intval($dataType)),
                'sitewide' => intval($type) === $this->configTypes['SITE'],
                'description' => str_replace('\n', '<br/>', htmlspecialchars($description, ENT_QUOTES, 'UTF-8')),
                'currentValue' => $currentValue,
                'defaultValue' => $defaultValue,
                'maxLength' => ($maxLength === '') ? $maxLength : intval($maxLength),
                'maxVal' => ($maxValue === '') ? $maxValue : intval($maxValue),
                'minVal' => ($minValue === '') ? $minValue : intval($minValue),
            );

            if($tempConfigDetails['dataType'] === 'String') {
                $tempConfigDetails['defaultValue'] = htmlspecialchars($defaultValue, ENT_QUOTES, 'UTF-8');
                $tempConfigDetails['currentValue'] = htmlspecialchars($currentValue, ENT_QUOTES, 'UTF-8');
            }
            else if($tempConfigDetails['dataType'] === 'Boolean') {
                $tempConfigDetails['defaultValue'] = (boolean) $defaultValue;
            }
            else {
                $tempConfigDetails['defaultValue'] = intval($defaultValue);
            }

            if($attributes & $this->configAttributes['HIDDEN']) {
                $tempConfigDetails['hidden'] = true;
            }
            if($attributes & $this->configAttributes['REQUIRED']) {
                $tempConfigDetails['required'] = true;
            }
            $configs[] = $tempConfigDetails;
            $listOfConfigNames[] = $tempConfigDetails['name'];
        }
        $this->load->view('Admin/internalTools/editConfig', array(
            'autoComplete' => $listOfConfigNames,
            'configs' => $configs,
            'groups' => $this->configGroup('all')
        ));
    }

    /**
     * Update a config.
     * Public function that's a GET rather than POST so that configs can be updated via simple url hit in browser,
     * e.g. /ci/admin/internalTools/updateConfig/cp_home_url/casa
     * Beware of the browser's escaping and such.
     * @param string $configSlot The config slot name
     * @param string $value New value to update the config (optional) defaults to empty string
     */
    function updateConfig($configSlot, $value = '') {
        $value = urldecode($value);

        header('Expires: Mon, 19 May 1997 07:00:00 GMT');

        try {
            \Rnow::updateConfig($configSlot, $value);
        }
        catch(\Exception $exception) {
            exit("There was a problem updating a config: " . $exception->getMessage());
        }
        $this->_renderJSON(array('value' => $value));
    }

    /**
     * Bump widgets or add supported framework to all widgets when a framework is bumped.
     */
    public function bumpWidgetsOrFramework() {
        $this->load->view('Admin/internalTools/bumpView');
    }

    public function processBump() {
        $this->load->view('Admin/internalTools/bumpView');
        $widgetURL = $_POST['widgetPath'];
        $bumpType = $_POST['typeOfBump'];
        if(!is_readable($this->documentRoot."/core/widgets/".$widgetURL."/info.yml")) {
            echo "The Widget URL you have entered is wrong, please ensure that it is of the form standard/widgetType/widgetName";
            return;
        }
        switch($bumpType) {
            case "nano":
                $this->bumper->nanoBumpWidget($widgetURL);
                $versions = Version::getVersionHistory(true, false);
                break;
            case "minor":
                $this->bumper->majorMinorBumpWidget($widgetURL, "minor");
                $versions = Version::getVersionHistory(true, false);
                break;
            case "major":
                $this->bumper->majorMinorBumpWidget($widgetURL, "major");
                $versions = Version::getVersionHistory(true, false);
                break;
        }

        $this->load->view('Admin/internalTools/bumpView');
        Version::writeVersionHistory($versions);
    }

    public function addSupportedFrameworkToWidgets() {
        $this->load->view('Admin/internalTools/bumpView');
        $version = $_POST['name'];
        $this->bumper->addSupportedFrameworkToWidgets($version);
        echo '<hr style="border-top: dotted 3px;" />';
    }

    /**
     * Avoid tedium by setting commonly-grouped configs.
     * e.g. /ci/admin/internalTools/configGroup/community
     * @param string|null $groupName Name of the group of configs to set;
     * @param bool $verbose If true, print out config slots and values being set
     * if not specified, a list of available groups is returned
     */
    function configGroup($groupName = null, $verbose = true) {
        $configGroups = array(
            'community' => 'Sets up community integration',
            'facebook' => 'Turns on the Facebook app',
            'siebel' => 'Set up Siebel',
            'webindexer' => 'Sets up external search',
            'va' => 'Sets up Virtual Assistant',
            'pta' => 'Sets up Pass Through Authentication',
            'ptareset' => 'Unsets Pass Through Authentication',
        );
        if ($groupName === null) {
            $this->_renderJSON($configGroups);
        }
        $configs = array();
        switch ($groupName) {
            case 'all':
                return $configGroups;
            case 'community':
                $configs = array(
                    array('COMMUNITY_ENABLED', 1),
                    array('COMMUNITY_PRIVATE_KEY', 'OXaX8ctYifSiwonq'),
                    array('COMMUNITY_PUBLIC_KEY', '5JbzLXNAJ0Y3sxAn'),
                    array('COMMUNITY_BASE_URL', 'http://den01tpo.us.oracle.com'),
                );
                break;
            case 'facebook':
                $configs = array(
                    array('FACEBOOK_ENABLED', 1),
                    array('FACEBOOK_WALL_POST_ENABLED', 1),
                    array('FACEBOOK_SUPPORT_RESOURCE_ID', 'eab55c7282'),
                    array('FACEBOOK_BUG_RESOURCE_ID', '666026e06f'),
                    array('FACEBOOK_IDEA_RESOURCE_ID', '82f1d5a55d'),
                    array('FACEBOOK_APPLICATION_ID', '142315105961140'),
                    array('FACEBOOK_APPLICATION_SECRET', 'fbb9ba280c435f9b2d6bc9d9a04a9968'),
                    array('FACEBOOK_OAUTH_APP_ID', '142315105961140'),
                    array('FACEBOOK_OAUTH_APP_SECRET', 'fbb9ba280c435f9b2d6bc9d9a04a9968'),
                );
                break;
            case 'siebel':
                $configs = array(
                    array('SIEBEL_EAI_HOST', 'slc05eol.us.oracle.com'),
                    array('SIEBEL_EAI_LANGUAGE', 'enu'),
                    array('SIEBEL_EAI_USERNAME', 'SADMIN'),
                    array('SIEBEL_EAI_PASSWORD', 'MSSQL'),
                    array('SIEBEL_EAI_VALIDATE_CERTIFICATE', 0),
                );
                break;
            case 'webindexer':
                $configs = array(
                    array('EU_WIDX_MODE', 1),
                    array('EU_WIDX_SEARCH_BY_DEFAULT', 1),
                    array('EU_WIDX_SHOW_URL', 1),
                    array('EU_WIDX_SORT_BY_DEFAULT', 1),
                    array('KB_WIDX_MODE', 2),
                    array('WIDX_INDEX_SIZE', 300000),
                    array('WIDX_LIMIT_FILTER', 'missouri.rightnowtech.com'),
                    array('WIDX_LIMIT_NORMALIZED_FILTER', 'missouri.rightnowtech.com'),
                    array('WIDX_MAX_HOPS', 10),
                    array('WIDX_MODE', 1),
                    array('WIDX_URLS', 'https://missouri.rightnowtech.com/data'),
                    array('WIDX_USE_CJK_ANALYSIS', 0),
                );
                break;
            case 'va':
                $configs = array(
                    array('MOD_VA_ENABLED', 1),
                    array('MOD_CHAT_ENABLED', 1),
                    array('MOD_ENGAGEMENT_CHANNELS_ENABLED', 1),
                    array('MOD_COBROWSE_ENABLED', 1),
                    array('MOD_ENGAGEMENT_ENGINE_ENABLED', 1),
                    array('SERVLET_HTTP_PORT', 8084),
                    array('SRV_CHAT_INT_HOST', $_SERVER['REMOTE_ADDR'] . ":8084"),
                    array('SRV_CHAT_INTERNAL_NET', $_SERVER['REMOTE_ADDR']),
                    array('SRV_CHAT_HOST', $_SERVER['REMOTE_ADDR']),
                    array('COBROWSE_ACCOUNT', "agent@rightnow.com"),
                    array('COBROWSE_PASSWORD', "instant"),
                    array('CHAT_UQ_WS_API_ENABLED', 1),
                    array('CHAT_CONSUMER_WS_API_ENABLED', 1),
                    array('SEC_END_USER_HTTPS', 0),
                    array('VA_NAME', 'livechat_en'),
                    array('VA_SERVER_DOMAIN', 'igvipcoreqa01.qa.lan'),
                    array('VA_HTTP_USERNAME', 'vachat'),
                    array('VA_HTTP_PASSWORD', 'j37vs62h30jgfeFEw'),
                );
                break;
            case 'pta':
                $configs = array(
                    array('PTA_ENABLED',                   1),
                    array('PTA_SECRET_KEY',                'IJGaZMkMmuEoMs3pFpdGfpJpFHsMiwWk'),
                    array('PTA_ENCRYPTION_METHOD',         'des3'),
                    array('PTA_ENCRYPTION_KEYGEN',          2),
                    array('PTA_ENCRYPTION_PADDING',         5),
                    array('PTA_ENCRYPTION_SALT',            '6162636430313233'),
                    array('PTA_ENCRYPTION_IV',              '3332313064636261'),
                    array('PTA_EXTERNAL_LOGOUT_SCRIPT_URL', 'http://www.google.com'),
                );
                break;
            case 'ptareset':
                $configs = array(
                    array('PTA_ENABLED',                    0),
                    array('PTA_SECRET_KEY',                 ''),
                    array('PTA_ENCRYPTION_METHOD',          ''),
                    array('PTA_ENCRYPTION_KEYGEN',          2),
                    array('PTA_ENCRYPTION_PADDING',         5),
                    array('PTA_ENCRYPTION_SALT',            ''),
                    array('PTA_ENCRYPTION_IV',              ''),
                    array('PTA_EXTERNAL_LOGOUT_SCRIPT_URL', '')
                );
                break;
            default:
                exit;
        }
        foreach ($configs as $info) {
            if ($verbose) echo "{$info[0]}: {$info[1]}<br/>";
            \Rnow::updateConfig($info[0], $info[1]);
        }
        $this->_renderJSON(array('value' => $groupName));
    }

    /**
     * Search for a message base.
     * e.g. /ci/admin/internalTools/searchMessagebase/search_lbl
     * PROTIP: If you find yourself doing this a lot, add this
     * URL as one of your browser's search engines.
     * @param string $query Slot name or message text
     */
    function searchMessagebase($query) {
        header('Expires: Mon, 19 May 1997 07:00:00 GMT');

        $return = array();
        if($query = trim($query)) {
            // suppress content (blank lines) output while including mc_util.php
            ob_start();
            require_once DOCROOT . '/include/mc_util.php';
            ob_end_clean();
            $results = msgbase_search(urldecode($query), 100);
            foreach($results as $result){
                $return[$result['name']] = htmlspecialchars($result['value'], ENT_QUOTES, 'UTF-8');
            }
        }

        if (get_instance()->isAjaxRequest()) {
            $this->_renderJSON($return);
        }
        else {
            // @codingStandardsIgnoreStart
            // Pretty print the results if they're being directly requested via the browser URL
            echo "<pre>"; print_r($return); echo "</pre>";
            // @codingStandardsIgnoreEnd
        }
    }

    public function checkNanoBump($latestNano, $widgetVersion) {
        $latestNanoArray = explode('.', $latestNano);
        $widgetVersionArray = explode('.', $widgetVersion);
        if($latestNanoArray[0] > $widgetVersionArray[0])
            return 0;
        if($latestNanoArray[1] > $widgetVersionArray[1])
            return 0;
        return 1;
    }

    /**
     * Updates the cpHistory file according to your site's current state.
     * Hit this when adding a new widget or versioning an existing widget.
     * @throws \Exception If history file wasn't writable
     */
    public function updateCPHistory() {
        echo 'Updating CP history<br/>';
        $versions = Version::getVersionHistory(true, false);

        //versions which differ by a nano bump need to look identical in cpHistory
        foreach ($versions['widgetVersions'] as $widgetName => $widgetVersions) {
            $latestNano = end(array_keys($widgetVersions));
            foreach ($widgetVersions as $widgetVersion => $widgetInfo) {
                if($this->checkNanoBump($latestNano, $widgetVersion)) {
                    $versions['widgetVersions'][$widgetName][$widgetVersion] = $widgetVersions[$latestNano];
                }
            }
        }

        echo 'Writing file to ' . Version::getVersionHistoryPath() . '<br/>';
        if(Version::writeVersionHistory($versions)){
            echo '<pre>', yaml_emit($versions), '</pre>Done<br/>';
        }
        else{
            throw new \Exception("Couldn't update history file. You might need to make it writable.");
        }
    }

    /**
     * Adds the specified standard widget to the widgetVersions file.
     * e.g. /ci/admin/internalTools/addStandardWidget/foo/BarWidget
     * @param string $path Standard widget path. 'standard/' may be omitted.
     */
    public function addStandardWidget($path) {
        $args = func_num_args();
        if ($args === 1) {
            $path = urldecode($path);
        }
        else if ($args > 1) {
            $path = implode('/', func_get_args());
        }
        else {
            exit('You must specify the widget to add');
        }
        if (!Text::beginsWith($path, 'standard/')) {
            $path = "standard/$path";
        }
        echo "Adding $path to the declared widget versions:";
        $versions = \RightNow\Utils\Widgets::getDeclaredWidgetVersions();
        $versions[$path] = 'current';
        ksort($versions);
        // @codingStandardsIgnoreStart
        echo '<pre>'; print_r($versions); echo '</pre>';
        // @codingStandardsIgnoreEnd
        \RightNow\Utils\Widgets::updateDeclaredWidgetVersions($versions);
        $this->updateCPHistory();
    }

    /**
     * Redirects to the survey URL with the survey ID and authentication parameter.
     * e.g. /ci/admin/internalTools/surveyURL/surveyID
     * @param int $surveyID Survey ID
     */
    public function surveyURL($surveyID) {
        header("Location: " . Api::build_survey_url($surveyID));
        exit;
    }

    /**
     * Runs generic_track_decode on the string.
     * e.g. /ci/admin/internalTools/trackDecoder/bananas
     * @param String $trackingString Thing to run generic_track_decode on
     */
    public function trackDecoder($trackingString) {
        var_dump(Api::generic_track_decode($trackingString));
        exit;
    }

    /**
     * Hits the custom changelog controller on the QA site to fetch any recent
     * changelog entries and optionally write to the changelog(s) on disk.
     *
     * Default is to retrieve changelogs added in the past 24 hours.
     *
     * Accepts optional url parameters:
     *   - 'ago' integer A number specifying how many 'intervals' ago to search for new/modified changelogs.
     *   - 'interval' string One of 'day', 'week', 'month' or 'year'
     *   - 'commit' bool If specified as true, write to the changelogs on disk.
     *
     * e.g. /ci/admin/internalTools/addNewChangelogEntries/ago/2/interval/week/commit/false
     */
    public function addNewChangelogEntries() {
        require_once CPCORE . '/Internal/Libraries/Changelog.php';
        $params = $this->uri->uri_to_assoc(4);
        $commit = ($params['commit'] == 'true' || $params['commit'] == '1');
        $ago = (is_numeric($params['ago'])) ? intval($params['ago']) : 1;

        // Accept 'interval' parameter if we support it, otherwise default to 'day'
        foreach(array('year', 'week', 'month', 'day') as $interval) {
            if (($dynamicInterval = strtolower($params['interval'])) && ($dynamicInterval === $interval || $dynamicInterval === "{$interval}s")) {
                break;
            }
        }
        // Setup curl library and options
        if(!extension_loaded('curl')) {
            \RightNow\Api::load_curl();
        } 
        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => "https://qa.custhelp.com/cc/changelogs/report/$ago/$interval",
            CURLOPT_FAILONERROR => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => false
        ));
        // Process curl response
        $output = curl_exec($curl);
        $responseCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        if (curl_errno($curl) || $output === false || $output === null) {
            echo 'An error has occurred: ' . curl_error($curl) . PHP_EOL;
        }
        else {
            if($responseCode === 200) {
                $entries = json_decode($output);
            }
            else {
                echo "Non 200 HTTP response code received: HTTP response code $responseCode" . PHP_EOL;
            }
        }
        curl_close($curl);
        //Add entries to changeLog and render response
        if ($entries) {
            $response = \RightNow\Internal\Libraries\Changelog::addEntriesFromReport($entries, $commit, true);
        }
        else {
            $response = array();
        }
        $this->_renderJSON($response);
    }

    private function getConfigType($typeDefine) {
        if($typeDefine === $this->configDataTypes['INT']){
            return "Integer";
        }
        if($typeDefine === $this->configDataTypes['BOOLEAN']){
            return "Boolean";
        }

        /*
        * TODO: support menu/datetime types. There currently aren't any datetime configs and I think
        * I need to get an optlist for the menu types.
        * if($typeDefine === $this->configDataTypes['DATETIME']){
        *    return "Datetime";
        * }
        *
        * if($typeDefine === $this->configDataTypes['MENU']){
        *    return "Menu";
        * }
        */
        return "String";
    }
    /**
     * Generate the test data for social customer service.  It will populate the scs_question,
     * scs_discussion, scs_comments, scs_users, and other tables. You can specify how many
     * questions you want to create. Every question will have around 20 comments in different
     * levels. The default will be 100 questions and around 2000 comments, which will take about
     * 2 minutes to create.
     *
     * e.g. /ci/admin/internalTools/generateSocialTestData/100
     * @param int $numQuestions How many questions to create.
     */
    public function generateSocialTestData($numQuestions = 100) {
        echo "Creating $numQuestions social questions and associated comments.<br>";
        $minutes = $numQuestions / 50;
        echo "It will take about $minutes minute(s) to complete. <br>";

        Framework::killAllOutputBuffering();

        ob_flush();
        flush();

        $socialUsers = $this->createSocialUsers();

        $numberComments = 0;

        set_time_limit(0);

        for ($i = 0; $i < $numQuestions; $i++)
        {
            $numberComments += $this->createSocialQuestions($socialUsers);
            echo "$numberComments comments created.<br>";
            if (!($i % 10)) {
                ob_flush();
                flush();
            }
        }

        echo "<br>Created $numQuestions social questions and $numberComments social comments.";
    }

    /**
     * Generate a random string.
     * @param int $numChars Number of characters to use.
     */
    private function getRandomString($numChars) {
        $availChars = "ABCDEFGHIJKLMNOPQRSTUVWXYZ_0123456789'[];:*^%$#@!/,.";
        $numAvail   = 52;

        $numTaken = 0;
        $retChars = '';
        $idx      = mt_rand(0, $numAvail);
        $step     = mt_rand(0, $numChars);

        while ($numTaken < $numChars){
            $retChars .= $availChars[$idx % $numAvail];
            $idx += $step;
            $numTaken++;
        }

        return($retChars);
    }

    /**
     * Create some social users and return the array of CommunityUser objects
     * @return array Array of SocialUsers.
     */
    private function createSocialUsers() {
        // CommunityUser::find does not work. It should work with real
        // connect object. For right now, just adding new socail users.
        // Check if the scs_users has already been created.

        /*
        $socialUsers = Connect\CommunityUser::find("ID > 0");
        $number_users = count($socialUsers);
        echo "<br>$number_users<br>";

        $socialUsers = array_slice($socialUsers, 0, $number_users_wanted);

        var_dump($socialUsers);

        if ( $number_users < $number_users_wanted ) {
        */
        $contacts = Connect\Contact::find('ID > 0 LIMIT 100');

        $socialUsers = array();
        foreach($contacts as $contact) {
            $socialUser = new Connect\CommunityUser();
            $socialUser->DisplayName = $contact->Login;
            $socialUser->save();
            $socialUsers[] = $socialUser;
        }

        return $socialUsers;
    }
    /**
     * Create one social question and comments associated with the questions
     * @param array $socialUsers Number of characters to use.
     * @return int Number of random comments created.
     */
    private function createSocialQuestions(array $socialUsers) {
        $allProducts = array("iPhone","iPhone 4","iPhone 4S","iPhone 5","Droid","Nexus One","HTC","Blackberry","Windows Mobile 8","Galaxy");
        $questionPhases = array(
            "How to set up",
            "How to turn on",
            "How to enable",
            "How to turn off",
            "How to disable",
            "I have a question on",
            "I don't know where is",
            "How to use"
        );
        $productFeatures = array(
            "camera?",
            "airplan mode?",
            "touch screen?",
            "battery monitor? ",
            "file manager?",
            "backups?",
            "SIM card?",
            "data usage monitor?",
        );

        $countComments = 0;

        $randomProduct = $allProducts[array_rand($allProducts)];
        $randomQuestionPhase = $questionPhases[array_rand($questionPhases)];
        $randomFeature = $productFeatures[array_rand($productFeatures)];

        $newQuestion = new Connect\CommunityQuestion();
        $newQuestion->Body = $this->getRandomString(mt_rand(15, 400));
        $newQuestion->CreatedByCommunityUser = $socialUsers[array_rand($socialUsers)];
        $newQuestion->Subject = "$randomQuestionPhase $randomProduct's $randomFeature";
        $newQuestion->save();

        $maxLevels = mt_rand(1, 6);
        for ($i = 0; $i < $maxLevels; $i++) {
            $width = mt_rand(1, 10);
            for ($j = 0; $j < $width; $j++) {
                $comment = new Connect\CommunityComment();
                $comment->CreatedByCommunityUser = $socialUsers[array_rand($socialUsers)];
                $comment->Body = $this->getRandomString(mt_rand(15, 400));
                $comment->CommunityQuestion = $newQuestion;
                $comment->save();
                $countComments++;
            }
        }
        return $countComments;
    }
}
