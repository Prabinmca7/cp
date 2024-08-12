<?php

namespace RightNow\Controllers\Admin;

use RightNow\Utils\FileSystem,
    RightNow\Utils\Text,
    RightNow\Utils\Config,
    RightNow\Internal\Utils\Deployment,
    RightNow\Utils\Url,
    RightNow\Utils\Widgets,
    RightNow\Internal\Libraries,
    RightNow\Connect\v1_4 as Connect,
    RightNow\Api;

require_once CPCORE . 'Internal/Libraries/Staging.php';
require_once CPCORE . 'Internal/Libraries/Deployer.php';
require_once CPCORE . 'Internal/Libraries/Logging.php';
require_once CPCORE . 'Internal/Utils/Admin.php';
require_once CPCORE . 'Internal/Utils/Deployment.php';

class Deploy extends Base {
    /**
     * How long to wait for deploy to finish- 3 minutes
     */
    const DEPLOY_LOCK_WAIT_THRESHOLD = 180;
    const DEPLOY_LOG_RETRY_THRESHOLD = 20;
    const DEPLOY_LOG_WAIT_INTERVAL = 1;
    private $buttonArray = null;
    private $fileArray = null;
    private $hasSentHtmlHead = false;
    protected $account;
    private $accountID;
    private $menuOptions = array(false, true, true);
    private $defaultMenuOption = 0;
    private $isAgentConsoleRequest;

    /**
     * Constructor.
     * @param boolean $sendNoCacheHeaders Whether to send no cache headers
     */
    function __construct($sendNoCacheHeaders = true) {
        if ($sendNoCacheHeaders) {
            // Following headers to fix undesired caching of pages from M&C browser control.
            header('Cache-Control: no-cache, no-store, must-revalidate');
            header('Expires: Mon, 19 May 1997 07:00:00 GMT'); // Date in the past
        }

        parent::__construct(true, '_verifyLoginWithCPEditPermission', array(
            'initializeStaging' => '_verifyLoginWithCPStagePermission',
            'stageAndPromote' => '_verifyLoginWithCPPromotePermission',
            'upgradeDeploy' => '_validateConfigPassword',
            'servicePackDeploy' => '_validateConfigPassword',
            'unitTestDeploy' => '_validateConfigPassword',
            'prepareDeploy' => '_verifyLoginBySessionIdAndRequireCPPromote',
            'commitDeploy' => '_verifyLoginBySessionIdAndRequireCPPromote',
            'modifyProductionPageSetMappingFilesToMatchDB' => '_validateConfigPassword',
            'selectFiles' => '_verifyLoginWithCPStagePermission',
            'selectVersions' => '_verifyLoginWithCPStagePermission',
            'selectConfigs' => '_verifyLoginWithCPStagePermission',
            'stage' => '_verifyLoginWithCPStagePermission',
            'stageSubmit' => '_verifyLoginWithCPStagePermission',
            'stageStatus' => '_verifyLoginWithCPStagePermission',
            'promote' => '_verifyLoginWithCPPromotePermission',
            'promoteSubmit' => '_verifyLoginWithCPPromotePermission',
            'promoteStatus' => '_verifyLoginWithCPPromotePermission',
            'rollback' => '_verifyLoginWithCPPromotePermission',
            'rollbackSubmit' => '_verifyLoginWithCPPromotePermission',
            'rollbackStatus' => '_verifyLoginWithCPPromotePermission',
            ));
        $this->account = $this->_getAgentAccount();
        $this->accountID = $this->account->acct_id;
        $this->isAgentConsoleRequest = $this->_isAgentConsoleRequest();
    }

    /**
     * Do a 'stage' (all files and configs) followed by a 'promote'. (internal use only)
     */
    function stageAndPromote() {
        // Don't allow get requests from other sites
        if(($_SERVER['REQUEST_METHOD'] === 'GET') && ($referer = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '') && !Text::stringContains($referer, Config::getConfig(OE_WEB_SERVER, 'COMMON'))) {
            header($_SERVER['SERVER_PROTOCOL'] . ' 403 Forbidden');
            \RightNow\Utils\Framework::writeContentWithLengthAndExit("Stage & promote not allowed via GET from other interfaces or sites"); // I don't think we need to internationalize this because HTTP errors usually aren't.
        }
        if (!IS_HOSTED) $start = microtime(true);
        $lockData = Deployment::createDeployLock($this->accountID, Config::getMessage(STAGING_DEPLOYMENT_LBL));
        if ($lockData['lock_obtained'] !== true) {
            echo "{$lockData['message']}<br/>{$lockData['lock_details']}";
            return;
        }
        if ($this->initializeStaging(null, $lockData['created_time'])) {
            $promote = new Libraries\Promote($this->_getSelectedStagingName());
            echo '<h3>' . Config::getMessage(PROMOTING_STAGING_TO_PRODUCTION_LBL) . '</h3><br/>';
            $promote->startPromote();
        }
        if (!IS_HOSTED) printf("ELAPSED: %6.2f seconds<br/>", microtime(true) - $start);
        $this->_scrollToEndOfLog();
    }

    /**
     * Prepares and commits a deploy.  HMS uses this to perform upgrade deploys.
     * Requires the url-endoded configuration password.
     */
    function upgradeDeploy() {
        // different versions in staging and production? Get production going, but kill staging optimized in both v2 and v3
        if (!$this->_doStagingAndProductionVersionsMatch()) {
            $this->_removeOptimizedStagingFiles();
            exit($this->_combinedDeploy(new Libraries\UpgradeProductionDeployOptions(), false) === 0 ? 0 : 1);
        }

        if ($this->_combinedDeploy(new Libraries\UpgradeProductionDeployOptions(), false) === 0) {
            exit($this->_upgradeStagingEnvironments($_SERVER['FROM_VER']) ? 0 : 1);
        }
        exit(1);
    }

    /**
     * Prepares and commits a deploy.  HMS uses this to perform service pack deploys.
     * Requires the url-endoded configuration password.
     */
    function servicePackDeploy() {
        $exitCode = true;
        // different versions in staging and production? Fail without even trying!
        if (!$this->_doStagingAndProductionVersionsMatch()) {
            if (FileSystem::isReadableFile(DOCROOT.'/cp/customer/development/allowMixedFrameworkSpPatching')) {
                $this->_removeOptimizedStagingFiles();
                exit($this->_combinedDeploy(new Libraries\UpgradeProductionDeployOptions(), false) === 0 ? 0 : 1);
            }
            exit(1);
        }

        foreach($this->_stagingEnvironments() as $stagingName) {
            if ($this->_combinedDeploy(new Libraries\ServicePackStagingDeployOptions(new Libraries\Staging($stagingName)), false)) {
                $exitCode = false;
            }
        }

        if ($exitCode === true) {
            exit($this->_servicePackProductionEnvironment() ? 0 : 1);
        }

        exit(1);
    }

    /**
     * Removes optimized staging files in both v2 and v3.
     */
    private function _removeOptimizedStagingFiles() {
        list($stagingFrameworkVersion) = $this->_getStagingAndProductionFrameworkVersions();

        $getOptimizedPath = function($path, $stagingName) {
            return DOCROOT . "$path/staging/$stagingName/optimized/";
        };

        // remove v3 optimized staging files, but preserve frameworkVersion file
        $optimizedStagingFolder = $getOptimizedPath('/cp/generated', $this->_getSelectedStagingName());
        if (FileSystem::isReadableDirectory($optimizedStagingFolder))
            FileSystem::removeDirectory($optimizedStagingFolder, false);
        $oldUmask = umask(IS_HOSTED ? 0002 : 0);
        file_put_contents($optimizedStagingFolder . 'frameworkVersion', $stagingFrameworkVersion);
        umask($oldUmask);

        // remove v2 optimized staging files
        $optimizedStagingFolder = $getOptimizedPath('/euf/application', $this->_getSelectedStagingName());
        if (FileSystem::isReadableDirectory($optimizedStagingFolder))
            FileSystem::removeDirectory($optimizedStagingFolder, false);
    }

    /**
     * Returns whether or not staging and production versions match.
     * @return bool False if framework versions don't match
     */
    private function _doStagingAndProductionVersionsMatch() {
        list($stagingFrameworkVersion, $productionFrameworkVersion) = $this->_getStagingAndProductionFrameworkVersions();

        return $stagingFrameworkVersion === $productionFrameworkVersion;
    }

    /**
     * Returns array of staging and production framework versions. If the framework versions are the same, array('0.0', '0.0')
     * will be returned instead of the actual framework versions.
     * @return array Array of staging and production framework versions
     */
    private function _getStagingAndProductionFrameworkVersions() {
        $frameworkVersions = $this->_getFrameworkVersionDifferences(
            DOCROOT . '/cp/generated/staging/' . $this->_getSelectedStagingName() . '/optimized/',
            DOCROOT . '/cp/generated/production/optimized/'
        );
        return $frameworkVersions ? array($frameworkVersions['source'][''], $frameworkVersions['destination']['']) : array('0.0', '0.0');
    }

    private function _servicePackProductionEnvironment() {
        return ($this->_combinedDeploy(new Libraries\ServicePackDeployOptions(), false) === 0);
    }

    private function _stagingEnvironments() {
        return array_keys(\RightNow\Internal\Utils\Admin::getStagingEnvironments());
    }

    /**
     * Deploy staging_xx/source -> staging_xx/optimized
     * for all staging environments found on disk.
     * @param string $fromVersion Set by HMS ($_SERVER['FROM_VER'])
     * @return bool True on success, false on error.
     */
    private function _upgradeStagingEnvironments($fromVersion = null) {
        $exitCode = true;
        $initialize = false;
        if ($fromVersion) {
            // If this is the initial upgrade to 11.2, initialize staging so it is identical to production.
            require_once CPCORE . 'Internal/Libraries/Version.php';
            $fromVersionObject = new \RightNow\Internal\Libraries\Version($fromVersion);
            if ($fromVersionObject->lessThan('11.2')) {
                $initialize = true;
            }
        }

        foreach($this->_stagingEnvironments() as $stagingName) {
            if (($initialize && !$this->initializeStaging($stagingName, null, 'production')) ||
                (!$initialize && $this->_combinedDeploy(new Libraries\UpgradeStagingDeployOptions(new Libraries\Staging($stagingName)), false))) {
                $exitCode = false;
            }
        }
        return $exitCode;
    }

    /**
     * Deploys CP for unit tests
     */
    function unitTestDeploy() {
        $this->_exitIfHosted();
        $this->_combinedDeploy(new Libraries\UnitTestDeployOptions());
    }

    function _exitIfHosted() {
        if (IS_HOSTED) {
            exit('Only in development.');
        }
    }

    /**
     * NOTE: combinedDeploy will be revamped once deployment manager is on dock
     *        as there will no longer be the concept of prepare_deploy/commit_deploy
     * @param object $deployOptions Instance of deployer options
     * @param bool $exitUponCompletion If true, call exit() upon completion, otherwise return 0 on success, 1 on error.
     * @return mixed Exit code or process will exit of $exitUponCompletion is true
     */
    private function _combinedDeploy($deployOptions, $exitUponCompletion = true) {
        $this->_sendHtmlHeader();
        $deployer = new Libraries\Deployer($deployOptions);
        $exitCode = ($deployer->prepare_deploy() && $deployer->commit_deploy()) ? 0 : 1;
        $this->_sendHtmlFooter();
        if ($exitUponCompletion === true) {
            exit($exitCode);
        }
        return $exitCode;
    }

    function prepareDeploy() {
        $this->_sendHtmlHeader();
        require_once CPCORE . 'Internal/Libraries/Deployer.php';
        $deployer = new Libraries\Deployer(new Libraries\PrepareDeployOptions($this->account));
        $deployer->prepare_deploy();
        $this->_sendHtmlFooter();
    }

    function commitDeploy() {
        $this->_sendHtmlHeader();
        require_once CPCORE . 'Internal/Libraries/Deployer.php';
        $deployer = new Libraries\Deployer(new Libraries\CommitDeployOptions($this->account));
        $deployer->commit_deploy();
        $this->_sendHtmlFooter();
    }

    private function _sendHtmlHeader() {
        if (!$this->hasSentHtmlHead)
        {
            echo '<HTML><HEAD>' ,
                '<META HTTP-EQUIV=\'Pragma\' CONTENT=\'no-cache\'>',
                '<META HTTP-EQUIV=\'Expires\' CONTENT=\'-1\'>',
                '</HEAD><BODY>';
            $this->hasSentHtmlHead = true;
        }
    }

    private function _sendHtmlFooter() {
        echo '</BODY></HTML>';
    }

    /**
     * This should run on cutover and redeploys the Page Set Mapping file
     * from the production database
     */
    // @codingStandardsIgnoreStart
    function modifyProductionPageSetMappingFilesToMatchDB()
    {
        echo 'This step - modifyProductionPageSetMappingFilesToMatchDB, will be skipped.';
        exit(0);
        $path = OPTIMIZED_FILES . 'production/optimized/';
        $content = $this->model('Pageset')->getDeployedContent(null, false);
        $succeeded = (false !== @file_put_contents($path . $this->model('Pageset')->getPageSetFilePath(), $content));
        $path = OPTIMIZED_FILES . 'production/source/';
        $succeeded = ($succeeded && (false !== @file_put_contents($path . $this->model('Pageset')->getPageSetFilePath(), $content)));
        echo 'Success updating production files: ' . ($succeeded ? 'true' : 'false');
        exit($succeeded ? 0 : 1);
    }
    // @codingStandardsIgnoreEnd

    /**
     * Compares the encrypted password sent as a url parameter with the value in common config, SEC_CONFIG_PASSWD.
     * Primarily used by HMS to run things like: /ci/admin/deploy/servicePackDeploy/<encrypted password> .
     * If SEC_CONFIG_PASSWD is null (which is not allowed in production),
     * then this method will allow the requested action, which can be useful in QA and development sites.
     */
    protected function _validateConfigPassword() {
        if(!\Rnow::isValidAdminPassword(urldecode($this->uri->segment(4)))) {
            printf("\n" . Config::getMessage(ACCESS_DENIED_HDG) . "\n");
            exit(1); // HMS depends on this return code being != 0
        }
    }

    /**
     * Create directories: /www/rnt/[site]/cgi-bin/[interface].cfg/scripts/cp/generated/staging/staging_01
     *                     /www/rnt/[site]/vhosts/[interface]/euf/generated/staging/staging_01
     * Then deploy staging_01/source to staging_01/optimized.
     * This will remove  the staging directories first if they exist.
     *
     * @param string $stagingName Name of staging environment, e.g. 'staging_01'
     * @param int|null $lockCreatedTime Epoch time of deploy lock creation time
     * @return bool True upon success, else false
     */
    function initializeStaging($stagingName = null, $lockCreatedTime = null) {
        if ($stagingName === null) {
            $stagingName = $this->_getSelectedStagingName();
        }
        $stage = new Libraries\Stage($stagingName, array('initialize' => true, 'lockCreatedTime' => $lockCreatedTime));
        $deployer = new Libraries\Deployer(new Libraries\StagingDeployOptions($stage));
        return $deployer->stage();
    }

    /**
     * Remove an existing deploy lock.
     * Only allowed in non hosted environments.
     */
    function removeDeployLock() {
        $this->_exitIfHosted();
        $lockObject = new Libraries\DeployLocking();
        if ($lockObject->lockRemove()) {
            echo 'Deploy lock removed.';
        }
        else {
            echo 'An error occurred removing the deploy lock.';
        }
    }

    /**
     * Controller for ci/deploy page.
     */
    function index() {
        $this->_render('deploy/index', array_merge($this->_getPageArray(), array('lastEventLabel' => Config::getMessage(LAST_PCT_S_EVENT_PCT_S_LBL))), Config::getMessage(DEPLOYMENT_MANAGER_LBL));
    }

    /**
     * Controller for ci/deploy/selectFiles page.
     */
    function selectFiles() {
        $this->_ensureStagingExists();
        $page = array_merge(
            $this->_getPageArray('selectFiles'),
            array(
              'files' => $this->_getFileTableView('development', $this->_getSelectedStagingName(), false),
            )
        );
        $this->_render('deploy/selectFiles', $page, Config::getMessage(SELECT_FILES_LBL), array(
          'css' => 'deploy/deploy',
          'js' => array(\RightNow\Utils\Url::getCoreAssetPath('ejs/1.0/ejs-min.js'), 'deploy/filesTable')
        ));
    }

    function selectVersions(){
        $this->_ensureStagingExists();
        $page = array_merge(
            $this->_getPageArray('selectVersions'),
            array(
                'versionsTable' => $this->_getVersionsTableView(Config::getMessage(SELECT_FRAMEWORK_WIDGET_CHANGES_LBL), false, false),
            )
        );
        $this->_render('deploy/selectVersions', $page, Config::getMessage(SELECT_VERSION_UPGRADES_LBL), array(
          'css' => 'deploy/deploy',
          'js' => 'deploy/versionsTable',
        ));
    }

    /**
     * Controller for ci/deploy/selectConfigs page.
     */
    function selectConfigs() {
        $this->_ensureStagingExists();
        $page = array_merge(
            $this->_getPageArray('selectConfigs'),
            array(
              'pageSetTable' => $this->_getPageSetTableView(Config::getMessage(PG_SET_MAPPING_DIFFERENCES_LBL), false),
            )
        );
        return $this->_render('deploy/selectConfigs', $page, Config::getMessage(SELECT_CONFIGURATIONS_LBL), array(
          'css' => 'deploy/deploy',
          'js' => array(\RightNow\Utils\Url::getCoreAssetPath('ejs/1.0/ejs-min.js'), 'deploy/pageSetTable'),
        ));
    }

    /**
     * Controller for ci/deploy/stage page.
     */
    function stage() {
        $this->_ensureStagingExists();
        $changesExist = $this->_changesExist();
        $disabledLabel = Config::getMessage(STAGING_DISABLED_FILE_CONFIG_MSG);
        $copyLabel = Config::getMessage(COPY_PCT_S_FILES_CONFIGURATIONS_CMD);
        $confirmLabel = Config::getMessage(PROCEED_COPYING_PCT_S_ITEMS_MSG);
        $loadingLabel = Config::getMessage(COPYING_PCT_S_ITEMS_STAGING_MSG);
        $initializeWarning = Config::getMessage(WARN_STAGING_ENVIRONMENT_REM_RE_CR_MSG);
        $selectedLabel = Config::getMessage(SELECTED_LBL);
        $allLabel = Config::getMessage(ALL_UC_LBL);
        $copySelectedLabel = sprintf($copyLabel, $selectedLabel);
        if ($changesExist === true) {
            $stageButtonLabel = $copySelectedLabel;
            $disabled = false;
            $stageButtonDisabled = '';
        }
        else {
            $stageButtonLabel = $disabledLabel;
            $disabled = true;
            $stageButtonDisabled = 'disabled';
        }
        $page = array(
          'accountID' => $this->accountID,
          'changesExist' => ($changesExist) ? 1 : 0,
          'disabledLabel' => $disabledLabel,
          'enabledLabel' => isset($enabledLabel) && $enabledLabel ? $enabledLabel : '',
          'copyAllLabel' => sprintf($copyLabel, Config::getMessage(ALL_UC_LBL)),
          'comment' => $this->_getCommentView(Config::getMessage(ADD_COMMENT_STORE_STAGING_LOG_FILE_CMD), $disabled),
          'initializeWarning' => Config::getMessage(STAGING_ENVIRONMENT_REM_RE_CR_MSG),
          'stageButtonLabel' => $stageButtonLabel,
          'stageButtonDisabled' => $stageButtonDisabled,
          'confirmLabel' => sprintf($confirmLabel, $selectedLabel),
          'proceedLabel' => Config::getMessage(STAGE_LBL),
          'confirmAllLabel' => sprintf($confirmLabel, $allLabel),
          'loadingLabel' => sprintf($loadingLabel, $selectedLabel),
          'loadingAllLabel' => sprintf($loadingLabel, $allLabel),
          'loadingBody' => $this->_getLoadingBody(),
          'buttonKeys' => array_merge(array_keys($this->_getButtonArray('stage')), array('stageSubmit')),
          'pageSetTable' => $this->_getPageSetTableView(Config::getMessage(SELECTED_PAGE_SET_MAPPING_ACTIONS_LBL)),
          'fileTable' => $this->_getFileTableView('development', $this->_getSelectedStagingName(), true, Config::getMessage(SELECTED_FILE_ACTIONS_LBL)),
          'versionsTable' => $this->_getVersionsTableView(Config::getMessage(SELECTED_VERSION_CHANGES_LBL), true, false),
        );

        $page = array_merge($this->_getPageArray('stage'), $page);
        return $this->_render('deploy/stage', $page, Config::getMessage(STAGE_LBL), array(
          'css' => 'deploy/deploy',
          'js' => 'deploy/stage',
        ));
    }

    /**
     * Controller for ci/deploy/promote page.
     */
    function promote() {
        $this->_createMinimumProductionDirs();
        $page = array_merge(
            $this->_getPageArray('promote', false),
            array('promoteTitle' => Config::getMessage(REPLACE_PRODION_ENVIRONMENT_MSG),
                'backupLabel' => Config::getMessage(BACKUP_WISH_ROLLBACK_EVENT_MSG),
                'promoteLabel' => Config::getMessage(PROMOTE_LBL),
                'proceedLabel' => Config::getMessage(PROMOTE_LBL),
                'confirmLabel' => Config::getMessage(PROCEED_REPLACING_PRODUCTION_MSG),
                'loadingLabel' => Config::getMessage(PROMOTING_STAGING_TO_PRODUCTION_MSG),
                'warningLabel' => $this->load->view('Admin/deploy/warning', array('warningLabel' => Config::getMessage(PROMOTING_STAGING_PRODUCTION_TAKES_MSG)), true),
                'loadingBody' => $this->_getLoadingBody(),
                'fileTable' => $this->_getFileTableView($this->_getSelectedStagingName(), 'production', true),
                'versionsTable' => $this->_getVersionsTableView(Config::getMessage(WIDGET_VERSION_DIFFERENCES_STAGING_LBL), true, true),
                'accountID' => $this->accountID,
                'comment' => $this->_getCommentView(Config::getMessage(ADD_COMMENT_STORE_PROMOTE_LOG_FILE_CMD)),
            )
        );

        return $this->_render('deploy/promote', $page, Config::getMessage(PROMOTE_LBL), array(
          'css' => 'deploy/deploy',
          'js' => 'deploy/promote',
        ));
    }

    /**
     * Controller for ci/deploy/rollback page.
     */
    function rollback() {
        list($rollbackAllowed, $reason) = Deployment::isPromoteRollbackAllowed();
        if ($rollbackAllowed) {
            list($lastPromoteDate, $logName) = Deployment::getInfoFromLastLog('promote', true);
            if ($lastPromoteDate === null) {
                $rollbackAllowed = false;
                $reason = Config::getMessage(PREV_PROMOTE_EVENT_BACKUP_RESTORE_MSG);
            }
        }

        if ($rollbackAllowed) {
            $promoteLabel = Config::getMessage(PROMOTE_LBL);
            $logLink = ($this->isAgentConsoleRequest) ? $promoteLabel : "<a href=\"/ci/admin/logs/ViewDeployLog/$logName\" target=\"_blank\">$promoteLabel</a>";

            $page = array_merge(
                $this->_getPageArray('rollback', false),
                array('rollbackTitle' => Config::getMessage(ROLLBACK_FILES_CONFIGURATIONS_PREV_MSG),
                      'rollbackLabel' => Config::getMessage(ROLLBACK_LBL),
                      'proceedLabel' => Config::getMessage(ROLLBACK_LBL),
                      'comment' => $this->_getCommentView(Config::getMessage(ADD_COMMENT_STORE_ROLLBACK_LOG_CMD)),
                      'loadingLabel' => Config::getMessage(ROLLING_BACK_MOST_RECENT_PROMOTE_MSG),
                      'loadingBody' => $this->_getLoadingBody(),
                      'confirmLabel' => Config::getMessage(PROCEED_ROLLING_FILES_MSG),
                      'rollbackDetails' => sprintf(Config::getMessage(ROLLBACK_RECENT_SUCCESSFUL_PCT_S_MSG), $logLink, $lastPromoteDate),
                      'accountID' => $this->accountID,
                      'warningLabel' => $this->load->view('Admin/deploy/warning', array('warningLabel' => Config::getMessage(ROLLBACK_TAKES_IMMEDIATELY_CHANGES_MSG)), true),
                     )
            );
        }
        else {
            $page = array(
                'rollbackDisabled' => true,
                'reason' => $reason,
                'rollbackLabel' => Config::getMessage(ROLLBACK_LBL),
            );
        }

        return $this->_render('deploy/rollback', $page, Config::getMessage(ROLLBACK_LBL), array(
          'css' => 'deploy/deploy',
          'js' => 'deploy/rollback',
        ));
    }

    /**
     * Check the deploy log to see if a success message can be found. Due to occasional slowness with the NFS it could take a few
     * seconds for the final entries written by the deploy process to become visible to the status process (which could be
     * running on a separate server) so we will check the log file a few times sleeping between each try.
     * @param string $logPath Log file name with the complete path
     * @param array &$response Array containing data to be sent back to the client
     *
     * @return boolean Returns true if successfull deploy operation message found, false if not found
     */
    private function checkLogFileForSuccess($logPath, array &$response) {
        for ($i = 0; $i <= self::DEPLOY_LOG_RETRY_THRESHOLD; $i++) {
            $response['logContents'] = $this->_getLogContents($logPath);
            if (Text::stringContains($response['logContents'], Config::getMessage(DEPLOY_OPERATION_SUCCESSFUL_LBL))) {
                return true;
            }
            sleep(self::DEPLOY_LOG_WAIT_INTERVAL);
        }
        return false;
    }

    /**
     * Format the success message and links depending on the deploy type and depending on whether the response is going to an
     * agent console or not.
     * @param string $statusType Used to specify the deployment type
     * @param array &$response Array containing data to be sent back to the client
     */
    private function formatSuccessResponse($statusType, array &$response) {
        if ($statusType === 'stage') {
            $response['statusMessage'] = Config::getMessage(STAGING_COMPLETED_SUCCESSFULLY_LBL);
            if (!$this->isAgentConsoleRequest) {
                $response['links']['/ci/admin/overview/set_cookie/' . $this->_getSelectedStagingName()] = Config::getMessage(VIEW_SITE_IN_STAGING_MODE_CMD);
                $response['links']['/ci/admin/deploy/promote'] = Config::getMessage(PROMOTE_TO_PRODUCTION_LBL);
            }
        }
        else if ($statusType === 'promote') {
            $response['statusMessage'] = Config::getMessage(PROMOTE_COMPLETED_SUCCESSFULLY_LBL);
            if (!$this->isAgentConsoleRequest) {
                $response['links']['/ci/admin/overview/set_cookie/production'] = Config::getMessage(VIEW_SITE_IN_PRODUCTION_MODE_CMD);
                $response['links']['/ci/admin/deploy/rollback'] = Config::getMessage(ROLLBACK_LBL);
            }
        }
        else {
            $response['statusMessage'] = Config::getMessage(ROLLBACK_COMPLETED_SUCCESSFULLY_LBL);
            if (!$this->isAgentConsoleRequest) {
                $response['links']['/ci/admin/overview/set_cookie/production'] = Config::getMessage(VIEW_SITE_IN_PRODUCTION_MODE_CMD);
            }
        }
    }

    /**
     * Checks to see if the deploy process is done by seeing if the deploy memcache lock still exists.
     * If the deploy is done a json encoded array indicating stage/promote status (success|error) and log name is returned to the browser.
     * If the deploy is still running after 3 minutes it will return a status of 'running' unless the deploy has been running longer
     * than the PHP max_execution_time parameter in which case it will return an error.
     * @param string $statusType Used to specify the AJAX request to use for the next status request
     */
    private function deployStatus($statusType) {
        ob_start();
        $response = $this->_getInitialResponseArray($statusType);
        $logPath = $_POST['logPath'];
        $lockCreatedTime = (int) $_POST['lockCreatedTime'];
        // Extract the timestamp from the log file name which is after the deploy type and  before the '.log' at the end -- i.e. stage1403882443.log
        $logCreateTime = (int) Text::getSubstringBefore(Text::getSubStringAfter($logPath, '.cfg/log/' . $statusType), '.log');

        if (FileSystem::isReadableFile($logPath) && strpos(realpath($logPath), Api::cfg_path()) === 0 && preg_match("@$statusType\d{10}.log@", $logPath)) {
            /*
            * Sometimes requests get delayed due to the server being backed up or delays connecting
            * to the DB. Compensate for the delays to keep time between requests to 3 minutes or less
            * to avoid the F5 5 minute limit
            */
            $lastResponse = (int) $_POST['lastResponse'];
            $waitTime = (!$lastResponse || $lastResponse + self::DEPLOY_LOCK_WAIT_THRESHOLD <= time()) ? 0 : self::DEPLOY_LOCK_WAIT_THRESHOLD - (time() - $lastResponse);

            if (Deployment::checkIfDeployLockRemovedWithWait($this->accountID, $lockCreatedTime, $waitTime) === true){
                $response['status'] = 'error';
                $response['links'] = array();

                if ($this->checkLogFileForSuccess($logPath, $response) === true) {
                    $response['status'] = 'success';
                    $this->formatSuccessResponse($statusType, $response);
                }
            }
            // Only resend status request if deploy process has been running less than PHP max execution time
            else if (time() < $logCreateTime + (int) ini_get('max_execution_time')) {
                $response['status'] = 'running';
                $response['logPath'] = $logPath;
                $response['statusRequest'] = '/ci/admin/deploy/' . $statusType . 'Status';
                $response['lastResponse'] = (string) time();
                ob_end_clean();

                $this->_renderJSONWithFlush($response);

                ob_start();
                exit;
            }
        }

        $response['html'] = $this->load->view('Admin/deploy/status', $response, true);

        ob_end_clean();

        $this->_renderJSONAndExit($response);
    }

    /**
     * Check on the status of a previously submitted stage submit
     */
    function stageStatus() {
        $this->deployStatus('stage');
    }

    /**
     * Prints a json encoded array indicating stage status (success|error) and log name.
     * @throws \Exception If file list contains a widget with invalid action option specified
     */
    function stageSubmit() {
        ob_start();
        $response = $this->_getInitialResponseArray('stage');
        $logPath = null;
        $comment = null;
        $initialize = false;
        $files = array();
        $configurations = array('pageSetChanges' => array());
        $fileDifferences = $this->_getFileDifferencesObjectOrMethod('development', $this->_getSelectedStagingName(), true, 'fileDifferencesDetails');
        $fileDifferencesByID = array();
        foreach ($fileDifferences as $fileDifference) {
            $fileDifferencesByID[$fileDifference['fileID']] = $fileDifference;
        }
        $pushVersionChanges = true;
        $approvedOptions = array('1', '2'); // 1 is Copy to staging and 2 is Remove from staging
        $lockCreatedTime = null;
        foreach($_POST as $key => $value) {
            if ($key === 'lockCreatedTime') {
                $lockCreatedTime = (int) $value;
            }
            else if ($key === 'comment') {
                $comment = html_entity_decode($value);
            }
            else if ($key === 'stageInitialize' && $value === '1') {
                $initialize = true;
            }
            else if ($key === 'fileIDs') {
                foreach(json_decode($value, true) as $fileKey => $fileValue) {
                    if (Text::beginsWith($fileKey, 'f_') && in_array($fileValue, $approvedOptions) && array_key_exists($fileKey, $fileDifferencesByID)) {
                        $fileArray = $fileDifferencesByID[$fileKey];
                        if ($fileArray['isWidget']) {
                            // Expand widget files to be copied or removed.
                            if ($fileValue === 1 && $fileArray['existsInSource']) {
                                $useSource = true;
                                $listDirectoryPath = $fileArray['sourcePath'];
                            }
                            else if ($fileValue === 2 && $fileArray['existsInTarget']) {
                                $useSource = false;
                                $listDirectoryPath = $fileArray['targetPath'];
                            }
                            else {
                                throw new \Exception(Config::getMessage(UNEXPECTED_FILE_OPTION_ENC_MSG));
                            }

                            foreach(FileSystem::listDirectory($listDirectoryPath, true, true, array('method', 'isFile')) as $filePath) {
                                if ($useSource) {
                                    $files[] = array($filePath, $fileArray['targetPath'] . Text::getSubstringAfter($filePath, $fileArray['sourcePath']), 1);
                                }
                                else {
                                    $files[] = array($fileArray['sourcePath'] . Text::getSubstringAfter($filePath, $fileArray['targetPath']), $filePath, 2);
                                }
                            }
                        }
                        else {
                            $files[] = array($fileArray['sourcePath'], $fileArray['targetPath'], (int) $fileValue);
                        }
                    }
                }
            }
            else if ((($pageSetKey = Text::getSubstringAfter($key, 'ps_')) !== false) && in_array($value, $approvedOptions)) {
                $configurations['pageSetChanges'][$pageSetKey] = (int) $value;
            }
            else if ($key === 'version_selection'){
                $pushVersionChanges = (bool)$value;
            }
        }

        if ($initialize === true) {
            $files = $configurations = array();
            $pushVersionChanges = true;
        }

        try {
            $stage = new Libraries\Stage($this->_getSelectedStagingName(), array('lockCreatedTime' => $lockCreatedTime,
                                                                                                    'files' => $files,
                                                                                                    'configurations' => $configurations,
                                                                                                    'pushVersionChanges' => $pushVersionChanges,
                                                                                                    'initialize' => $initialize));
            $logPath = $stage->getLogPath();

            $response['status'] = 'running';
            $response['logPath'] = $logPath;
            $response['statusRequest'] = '/ci/admin/deploy/stageStatus';
            $response['lastResponse'] = (string) time();
            $deployer = new Libraries\Deployer(new Libraries\StagingDeployOptions($stage, $comment));
            ob_end_clean();

            $this->_renderJSONWithFlush($response);

            ob_start();
            $deployer->stage();
        }
        catch (Exception $e) {
            $response['errors'][] = $e->getMessage();
        }

        ob_end_clean();

        if ($response['status'] === 'error') {
            $response['html'] = $this->load->view('Admin/deploy/status', $response, true);
            $this->_renderJSONAndExit($response);
        }
        else {
            \RightNow\Utils\Framework::runSqlMailCommitHook();
            exit;
        }

    }

    /**
     *  Check on the status of a previously submitted promote submit
     */
    function promoteStatus() {
        $this->deployStatus('promote');
    }

    /**
     * Prints a json encoded array indicating promote status (success|error) and log name.
     */
    function promoteSubmit() {
        ob_start();
        $response = $this->_getInitialResponseArray('promote');

        $lockCreatedTime = null;
        foreach($_POST as $key => $value) {
            if ($key === 'lockCreatedTime') {
                $lockCreatedTime = (int) $value;
            }
            else if ($key === 'comment') {
                $comment = html_entity_decode($value);
            }
        }

        $logPath = null;
        try {
            $promote = new Libraries\Promote($this->_getSelectedStagingName(), $lockCreatedTime, $comment);
            $logPath = $promote->getLogPath();

            $response['status'] = 'running';
            $response['logPath'] = $logPath;
            $response['statusRequest'] = '/ci/admin/deploy/promoteStatus';
            $response['lastResponse'] = (string) time();
            ob_end_clean();

            $this->_renderJSONWithFlush($response);

            ob_start();
            $promote->startPromote();
        }
        catch (Exception $e) {
            array_push($response['errors'], $e->getMessage());
        }

        ob_end_clean();
        if ($response['status'] === 'error') {
            $response['html'] = $this->load->view('Admin/deploy/status', $response, true);
            $this->_renderJSONAndExit($response);
        }
        else {
            \RightNow\Utils\Framework::runSqlMailCommitHook();
            exit;
        }
    }

    /**
     * Check on the status of a previously submitted rollback submit
     */
    function rollbackStatus() {
        $this->deployStatus('rollback');
    }

    /**
     * Prints a json encoded array indicating stage status (success|error) and log name.
     */
    function rollbackSubmit() {
        ob_start();
        $response = $this->_getInitialResponseArray('rollback');
        $lockCreatedTime = null;
        foreach($_POST as $key => $value) {
            if ($key === 'lockCreatedTime') {
                $lockCreatedTime = (int) $value;
            }
            if ($key === 'comment') {
                $comment = html_entity_decode($value);
            }
        }

        $logPath = null;
        try {
            $rollback = new Libraries\Rollback($this->_getSelectedStagingName(), $lockCreatedTime, $comment);
            $logPath = $rollback->getLogPath();

            $response['status'] = 'running';
            $response['logPath'] = $logPath;
            $response['statusRequest'] = '/ci/admin/deploy/rollbackStatus';
            $response['lastResponse'] = (string) time();
            ob_end_clean();

            $this->_renderJSONWithFlush($response);

            ob_start();
            $rollback->startRollback();
        }
        catch (Exception $e) {
            array_push($response['errors'], $e->getMessage());
        }

        ob_end_clean();
        if ($response['status'] === 'error') {
            $response['html'] = $this->load->view('Admin/deploy/status', $response, true);
            $this->_renderJSONAndExit($response);
        }
        else {
            \RightNow\Utils\Framework::runSqlMailCommitHook();
            exit;
        }
    }

    /**
     * Return json array from createDeployLock()
     */
    function lockCreate() {
        ob_start();
        $accountID = $_POST['accountID'] ? (int) $_POST['accountID'] : null;
        $deployType = $_POST['deployType'];
        $existingLockAccountID = $_POST['existingLockAccountID'] ? (int) $_POST['existingLockAccountID'] : null;
        $existingLockCreatedTime = $_POST['existingLockCreatedTime'] ? (int) $_POST['existingLockCreatedTime'] : null;
        $lockData = Deployment::createDeployLock($accountID, $deployType, $existingLockAccountID, $existingLockCreatedTime);
        ob_end_clean();

        $this->_renderJSONAndExit($lockData);
    }

    /**
     * Return json array from removeDeployLock()
     * EG. array('lock_removed' => true)
     * If lock cannot be not removed, add additional details: account_name, created_time_human and lock_details.
     */
    function lockRemove() {
        ob_start();
        $accountID = $_POST['accountID'] ? (int) $_POST['accountID'] : null;
        list($lockWasRemoved, $lockDetails) = Deployment::removeDeployLock($accountID);
        $lockDetails['lock_removed'] = $lockWasRemoved;
        ob_end_clean();

        $this->_renderJSONAndExit($lockDetails);
    }

    /**
     * Called by M&C to determine whether to enable the rollback button.
     * Prints a json array of 'rollback_allowed' [bool] and 'reason' [string]
     */
    function promoteRollbackAllowed() {
        list($rollbackAllowed, $reasonRollbackNotAllowed) = Deployment::isPromoteRollbackAllowed();

        $this->_renderJSONAndExit(array('rollback_allowed' => $rollbackAllowed, 'reason' => $reasonRollbackNotAllowed));
    }

    private function _scrollToEndOfLog() {
        echo "<div id='endOfLog'></div>
            <script type='text/javascript'>
                window.scrollTo(0, document.getElementById('endOfLog').offsetTop);
            </script>";
    }


    private function _getInitialResponseArray($deployType, $statusLabel = null, $errorLabel = null) {
        return array(
          'status' => 'error',
          'statusMessage' => ($deployType === 'rollback') ? Config::getMessage(AN_ERROR_OCCURRED_DURING_ROLLBACK_LBL) : (($deployType === 'promote') ? Config::getMessage(AN_ERROR_OCCURRED_DURING_PROMOTE_LBL) : Config::getMessage(AN_ERROR_OCCURRED_DURING_STAGING_LBL)),
          'statusLabel' => $statusLabel,
          'errorLabel' => ($errorLabel === null) ? Config::getMessage(ERR_LBL) : $errorLabel,
          'logContents' => '',
          'html' => null,
          'errors' => array(),
          'links' => array(),
        );
    }

    /**
     * The production directories will generally exist, but there are certain scenarios (usually on development test sites) where they may be missing.
     * Below we create the miniumum empty directories to ensure a promote can happen.
     */
    function _createMinimumProductionDirs() {
        umask(IS_HOSTED ? 0002 : 0);
        $productionBaseDir = OPTIMIZED_FILES . 'production';
        $directories = array(
            'source',
            'source/widgets',
            'optimized',
        );
        foreach($directories as $directory) {
            $fullPath = "$productionBaseDir/$directory";
            if (!FileSystem::isReadableDirectory($fullPath)) {
                FileSystem::mkdirOrThrowExceptionOnFailure($fullPath, true);
            }
        }
    }
    private function _getLoadingBody($msg = null) {
        return '<br><img src="' . Url::getCoreAssetPath('images/indicator.gif') . '"/>&nbsp;&nbsp;' . (($msg === null) ? Config::getMessage(PLEASE_WAIT_ELLIPSIS_MSG) : $msg) . "<br>&nbsp;";
    }

    private function _getFileDifferencesObjectOrMethod($source, $target, $readOnly = true, $methodName = null) {
        $object = new Libraries\EnvironmentFileDifferences($source, $target, $readOnly);
        return ($methodName === null) ? $object : $object->$methodName;
    }

    /**
     * Return true if user selected files or configurations to copy or remove from staging.
     */
    private function _changesExist() {
        foreach ($_POST as $key => $value) {
            if ((($value === '1' || $value === '2') && Text::beginsWith($key, 'ps_')) || ($key === 'version_selection' && $value !== '0')) {
                return true;
            }
            else if ($key === 'fileIDs') {
                foreach(json_decode($_POST['fileIDs'], true) as $fKey => $fValue) {
                    if (($fValue === 1 || $fValue === 2) && Text::beginsWith($fKey, 'f_') ) {
                        return true;
                    }
                }
            }
        }
        return false;
    }

    private function _getLogContents($logPath) {
        try {
            $logData = \RightNow\Internal\Utils\Logs::getFilteredLogHtml($logPath);
            return $this->load->view('Admin/deploy/log', $logData, true);
        }
        catch (Exception $e) {
            return sprintf(Config::getMessage(ERROR_RETRIEVING_LOG_PCT_S_PCT_S_LBL), $logPath, $e);
        }
    }

    private function _getCommentView($label, $disabled = false) {
        return $this->load->view('Admin/deploy/comment', array(
          'label' => $label,
          'disabled' => ($disabled === true) ? 'disabled' : '',
          'maxLengthWarning' => Config::getMessage(TRUNCATING_COMMENT_MAXIMUM_LEN_UC_LBL) . " "), true);
    }

    private function _getPageSetTableView($pageSetLabel, $readOnly = true) {
        $pageSets = $this->getConfigurationArray();
        list($columnDefinitions, $responseSchema, $pageSetData) = $this->_getPageSetDefinitions($readOnly, $pageSets);
        return $this->load->view("Admin/deploy/pageSetTable", array('pageSetLabel' => $pageSetLabel,
                                                              'pageSetData' => $pageSetData,
                                                              'columnDefinitions' => $columnDefinitions,
                                                              'properties' => json_encode($this->_getTableProperties()),
                                                              'isAgentConsoleRequest' => $this->isAgentConsoleRequest,
                                                              'responseSchema' => $responseSchema), true);
    }

    /**
     * Generate the table for widget version changes
     * @param string $tableHeaderLabel String to display above table denoting it's purpose
     * @param boolean $readOnly Denotes if the table should be displayed in read only mode
     * @param boolean $productionDifferences Denotes if the table should display the differences between dev and staging or staging and production
     */
    private function _getVersionsTableView($tableHeaderLabel, $readOnly, $productionDifferences){
        $frameworkColumnLabels = array();
        $phpColumnLabels = array();
        $widgetColumnLabels = array(Config::getMessage(WIDGET_LBL));
        if($productionDifferences){
            array_push($frameworkColumnLabels, Config::getMessage(STAGING_VERSION_LBL), Config::getMessage(PRODUCTION_VERSION_LBL));
            array_push($phpColumnLabels, Config::getMessage(STAGING_VERSION_LBL), Config::getMessage(PRODUCTION_VERSION_LBL));
            array_push($widgetColumnLabels, Config::getMessage(STAGING_VERSION_LBL), Config::getMessage(PRODUCTION_VERSION_LBL));
            $sourcePath = $this->_getSelectedStagingPath() . 'optimized/';
            $destinationPath = OPTIMIZED_FILES . 'production/optimized/';
        }
        else{
            array_push($frameworkColumnLabels, Config::getMessage(DEVELOPMENT_VERSION_LBL), Config::getMessage(STAGING_VERSION_LBL));
            array_push($phpColumnLabels, Config::getMessage(DEVELOPMENT_VERSION_LBL), Config::getMessage(STAGING_VERSION_LBL));
            array_push($widgetColumnLabels, Config::getMessage(DEVELOPMENT_VERSION_LBL), Config::getMessage(STAGING_VERSION_LBL));
            $sourcePath = CUSTOMER_FILES;
            $destinationPath = $this->_getSelectedStagingPath() . 'optimized/';
        }

        $frameworkVersionsData = $this->_getFrameworkVersionDifferences($sourcePath, $destinationPath);
        $phpVersionsData = $this->_getPhpVersionDifferences($sourcePath, $destinationPath);
        $widgetVersionsData = self::_getWidgetVersionDifferences(Widgets::getDeclaredWidgetVersions($sourcePath), Widgets::getDeclaredWidgetVersions($destinationPath));
        $versionDataSelection = array_key_exists('version_selection', $_POST) ? $_POST['version_selection'] : '0';
        $actualVersionChanges = count(isset($phpVersionsData['source']) ? $phpVersionsData['source'] : array()) || count(isset($frameworkVersionsData['source']) ? $frameworkVersionsData['source'] : array()) || count(isset($widgetVersionsData['source']) ? $widgetVersionsData['source'] : array()) || count(isset($widgetVersionsData['destination']) ? $widgetVersionsData['destination'] : array());
        // versions will always be pushed out to production, so we need to see if there is actually a difference being pushed out
        if ($productionDifferences && $actualVersionChanges)
            $versionDataSelection = '1';
        $changingWidgets = array_unique(array_merge(array_keys($widgetVersionsData['source']), array_keys($widgetVersionsData['destination'])));
        sort($changingWidgets);

        return $this->load->view("Admin/deploy/versionsTable", array(
            'versionsLabel'            => $tableHeaderLabel,
            'pushChangesLabel'         => Config::getMessage(PUSH_ALL_FRAMEWORK_PHP_VERSION_CHANGES_LBL),
            'changesNotPushedLabel'    => $actualVersionChanges ? Config::getMessage(NOTE_FRAMEWORK_WIDGET_VERSION_MSG) : Config::getMessage(FRAMEWORK_WIDGET_VERSION_MSG),
            'changesPushedLabel'       => Config::getMessage(DIFFERENCES_WILL_BE_PUSHED_OUT_MSG),
            'actualVersionChanges'     => $actualVersionChanges,
            'frameworkUpgradesLabel'   => Config::getMessage(FRAMEWORK_CHANGES_LBL),
            'phpUpgradesLabel'         => Config::getMessage(PHP_VERSION_CHANGES_LBL),
            'frameworkColumnLabels'    => $frameworkColumnLabels,
            'phpColumnLabels'          => $phpColumnLabels,
            'frameworkVersionsData'    => $frameworkVersionsData,
            'phpVersionsData'          => $phpVersionsData,
            'widgetUpgradesLabel'      => Config::getMessage(WIDGET_CHANGES_LBL),
            'widgetColumnLabels'       => $widgetColumnLabels,
            'widgetVersionsData'       => $widgetVersionsData,
            'versionDataSelection'     => $versionDataSelection,
            'readOnly'                 => $readOnly,
            'isAgentConsoleRequest'    => $this->isAgentConsoleRequest,
            'noLabel'                  => Config::getMessage(NO_LBL),
            'yesLabel'                 => Config::getMessage(YES_LBL),
        ), true);
    }

    private function _getFileTableView($source, $target, $readOnly = true, $filesLabel = null) {
        $fileDiffObject = $this->_getFileDifferencesObjectOrMethod($source, $target, $readOnly);
        return $fileDiffObject->getFileTableView($filesLabel, array('isAgentConsoleRequest' => $this->isAgentConsoleRequest));
    }

    /**
     * Computes the difference of framework versions between the two files specified
     * @param string $sourcePath Path to source directory
     * @param string $destinationPath Path to destination directory
     */
    private function _getFrameworkVersionDifferences($sourcePath, $destinationPath){
        $sourceFrameworkVersionFile = $sourcePath . "frameworkVersion";
        $sourceFrameworkVersion = '0.0';
        $destinationFrameworkVersionFile = $destinationPath . "frameworkVersion";
        $destinationFrameworkVersion = '0.0';
        if (FileSystem::isReadableFile($sourceFrameworkVersionFile))
            $sourceFrameworkVersion = trim(file_get_contents($sourceFrameworkVersionFile));
        if (FileSystem::isReadableFile($destinationFrameworkVersionFile))
            $destinationFrameworkVersion = trim(file_get_contents($destinationFrameworkVersionFile));
        if ($sourceFrameworkVersion === $destinationFrameworkVersion)
          return array();
        return array("source" => array("" => $sourceFrameworkVersion), "destination" => array("" => $destinationFrameworkVersion));
    }

    /**
     * Computes the difference of php versions between the two files specified
     * @param string $sourcePath Path to source directory
     * @param string $destinationPath Path to destination directory
     */
    private function _getPhpVersionDifferences($sourcePath, $destinationPath){
        $phpVersionList = \RightNow\Utils\Framework::getSupportedPhpVersions();
        $sourcePhpVersionFile = $sourcePath . "phpVersion";
        $sourcePhpVersion = CP_DEFAULT_PHP_VERSION;
        $destinationPhpVersionFile = $destinationPath . "phpVersion";
        $destinationPhpVersion = CP_LEGACY_PHP_VERSION;
        if (FileSystem::isReadableFile($sourcePhpVersionFile))
            $sourcePhpVersion = trim(file_get_contents($sourcePhpVersionFile));
        if (FileSystem::isReadableFile($destinationPhpVersionFile))
            $destinationPhpVersion = trim(file_get_contents($destinationPhpVersionFile));
        if ($sourcePhpVersion === $destinationPhpVersion)
          return array();
        return array("source" => array("" => $phpVersionList[$sourcePhpVersion]), "destination" => array("" => $phpVersionList[$destinationPhpVersion]));
    }

    /**
     * Computes the difference of widget versions between $sourceVersions and $targetVersions.
     * Widgets missing in source or target will have a respective entry added, having a null value for version.
     * @param array $sourceVersions Array of versions from source directory
     * @param array $targetVersions Array of versions from target directory
     */
    private static function _getWidgetVersionDifferences(array $sourceVersions, array $targetVersions){
        $sourceDifferences = $targetDifferences = array();
        $merge = function($source, $target, &$sourceDifferences, &$targetDifferences) {
            foreach($source as $widget => $version) {
                if (array_key_exists($widget, $target)) {
                    if ($version !== $target[$widget]) {
                        $sourceDifferences[$widget] = $version;
                        $targetDifferences[$widget] = $target[$widget];
                    }
                }
                else {
                    $sourceDifferences[$widget] = $version;
                    $targetDifferences[$widget] = null;
                }
            }
        };

        $merge($sourceVersions, $targetVersions, $sourceDifferences, $targetDifferences);
        $merge($targetVersions, $sourceVersions, $targetDifferences, $sourceDifferences);
        ksort($targetDifferences);
        ksort($sourceDifferences);
        return array("source" => $sourceDifferences, "destination" => $targetDifferences);
    }

    /**
     * Return an array of YUI DataTable properties for internationalization.
     */
    private function _getTableProperties() {
        return array(
            'MSG_EMPTY' => Config::getMessage(NO_DIFFERENCES_FOUND_MSG),
            'MSG_ERROR' => Config::getMessage(DATA_ERROR_MSG),
            'MSG_LOADING' => Config::getMessage(LOADING_ELLIPSES_LBL),
            'MSG_SORTASC' => Config::getMessage(CLICK_TO_SORT_ASCEND_CMD),
            'MSG_SORTDESC' => Config::getMessage(CLICK_TO_SORT_DESCEND_CMD),
        );
    }

    private function _getButtonArray($pageName = null) {
        if ($this->buttonArray === null) {
            $this->buttonArray = array(
                'selectFiles' => array(
                    'label' => Config::getMessage(NUM1_SELECT_FILES_LBL),
                    'title' => Config::getMessage(SELECT_FILES_TO_COPY_TO_STAGING_MSG),
                    'id' => 'selectFiles',
                    'className' => ($pageName === 'selectFiles') ? 'selected' : '',
                    'disabled' => '',
                ),
                'selectVersions' => array(
                    'label' => Config::getMessage(N2_VERSION_CHANGES_LBL),
                    'title' => Config::getMessage(WIDGET_VERSION_UPGRADES_LBL),
                    'id' => 'selectVersions',
                    'className' => ($pageName === 'selectVersions') ? 'selected' : '',
                    'disabled' => '',
                ),
                'selectConfigs' => array(
                    'label' => Config::getMessage(NUM3_SELECT_CONFIGURATIONS_LBL),
                    'title' => Config::getMessage(SEL_CONFIGURATIONS_COPY_STAGING_MSG),
                    'id' => 'selectConfigs',
                    'className' => ($pageName === 'selectConfigs') ? 'selected' : '',
                    'disabled' => '',
                ),
                'stage' => array(
                    'label' => Config::getMessage(NUM4_STAGE_LBL),
                    'title' => Config::getMessage(STAGE_SEL_FILES_S_CONFIGURATIONS_MSG),
                    'id' => 'stage',
                    'className' => ($pageName === 'stage') ? 'selected' : '',
                    'disabled' => '',
                ),

            );
        }
        return $this->buttonArray;
    }

    private function _getButtons($pageName, $displayButtons=true) {
        return $this->load->view('Admin/deploy/buttons', array(
            'pageName' => $pageName,
            'buttons' => ($displayButtons === true) ? $this->_getButtonArray($pageName) : array(),
            'messages' => json_encode(array(
                'continueLabel'                   => Config::getMessage(CNTINUE_CMD),
                'deployAlreadyLockedButtonsLabel' => Config::getMessage(OVRRIDE_LOCK_CONTINUING_LBL),
                'errorRemovingLockLabel'          => Config::getMessage(ERR_ENC_REMOVING_DEPLOY_LOCK_MSG),
                'lockOverriddenLabel'             => Config::getMessage(LOCK_MAY_HAVE_BEEN_OVERRIDDEN_MSG),
                'errorObtainingLockLabel'         => Config::getMessage(ERROR_ENC_OBTAINING_DEPLOY_LOCK_MSG),
                'lockRemovedLabel'                => Config::getMessage(DEPLOY_LOCK_REMOVED_MSG),
                'attemptingToRemoveLockLabel'     => Config::getMessage(ATT_REMOVE_DEPLOY_LOCK_ELLIPSIS_MSG),
                'attemptingToObtainLockLabel'     => Config::getMessage(ATT_OBTAIN_DEPLOY_LOCK_ELLIPSIS_MSG),
                'viewEntireLogLabel'              => Config::getMessage(VIEW_ENTIRE_LOG_CMD),
                'viewPartialLogLabel'             => Config::getMessage(VIEW_PARTIAL_LOG_CMD),
                'cancelLabel'                     => Config::getMessage(CANCEL_LBL),
                'siteConfigError'                 => Config::getMessage(PLEASE_CHECK_SITE_CONFIGURATION_MSG),
                'notApplicableLabel'              => Config::getMessage(NA_LBL),
            )),
            'postData' => json_encode($_POST)
        ), true);
    }

    private function getConfigurationArray() {
        $configurations = array();
        $pageSets = $this->_getPageSetMappings();
        foreach ($pageSets as $id => $data) {
            $menuItemKey = "ps_{$id}";
            $selectedOption = (array_key_exists($menuItemKey, $_POST) && $_POST[$menuItemKey] !== '') ? (int) $_POST[$menuItemKey] : null;
            $data['selectedOption'] = array(null, $selectedOption);
            $configurations[$id] = $data;
        }
        return $configurations;
    }

    /**
     * Return absolute path to selected staging environment
     */
    private function _getSelectedStagingPath() {
        return \RightNow\Internal\Utils\Admin::getStagingBasePath() . $this->_getSelectedStagingName() . '/';
    }

    /**
     * Return selected staging name: staging_xx
     * Currently hard-coded to return staging_01 until we allow more than one.
     */
    private function _getSelectedStagingName() {
        return 'staging_01';
    }

    /**
     * Initial creation of staging environment. Temporary until HMS does this at time of upgrade.
     */
    private function _ensureStagingExists() {
        if (!FileSystem::isReadableDirectory($this->_getSelectedStagingPath())) {
            $comment = 'Initial creation of staging environment.';
            printf("<h2>$comment</h1><b>Click <a href=\"/ci/admin/deploy/selectFiles\">here</a> AFTER deploy completes.</b><br /><br />");
            Deployment::createStagingDirectories($this->_getSelectedStagingName());
            $stage = new Libraries\Stage($this->_getSelectedStagingName(), array('lockCreatedTime' => $lockCreatedTime, 'initialize' => true));
            $deployer = new Libraries\Deployer(new Libraries\StagingDeployOptions($stage, $comment));
            $deployer->stage();
            exit();
        }
    }

    private function _getPageArray($pageName = null, $displayButtons = true) {
        return array(
          'buttons' => $this->_getButtons($pageName, $displayButtons),
          'deployMenuItems' => Deployment::getDeployMenuItems(),
        );
    }

    /**
     * Returns an array that looks something like
     *
     * array
     *   1 =
     *   array exists      = array[true, true],
     *         id          = array[1, 1],
     *         item        = array['/iphone/i', '/iphone/i']
     *         description = array['iPhone', 'iPhone']
     *         value       = array['mobile', 'mobile']
     *         enabled     = array[false, false]
     *         locked      = array[true, true]
     * ),
     */
    private function _getPageSetMappings() {
        return $this->model('Pageset')->getPageSetMappingComparedArray($this->_getSelectedStagingPath() . 'source/');
    }

    private function _getPageSetData($readOnly = true, $pageSets = null) {
        $pageSets = ($pageSets === null) ? $this->getConfigurationArray() : $pageSets;
        $array = array();
        $missing = Config::getMessage(DOES_NOT_EXIST_LC_LBL);
        $yes = Config::getMessage(YES_LC_LBL);
        $no = Config::getMessage(NO_LC_LBL);
        $locations = array('development' => Config::getMessage(DEVELOPMENT_LBL),
                           'staging' => Config::getMessage(STAGING_LBL));
        foreach ($pageSets as $id => $data) {
            $index = 0;
            foreach ($locations as $location => $label) {
                $columns = array(
                  'id' => "ps_{$id}_{$location}",
                  'menu' => null,
                  'location' => $label,
                  'item' => Text::escapeHtml($data['item'][$index]),
                  'description' => Text::escapeHtml($data['description'][$index]),
                  'value' => Text::escapeHtml($data['value'][$index]),
                  'exists' => $data['exists'][$index] ? $yes : $no,
                  'enabled' => $data['enabled'][$index] ? $yes : $no,
                );
                $existsInSource = ($data['exists'][0] && $data['enabled'][0]);
                $existsInTarget = $data['exists'][1];
                $changesExist = false;
                if ($existsInSource && $existsInTarget) {
                    if ($data['item'][0] !== $data['item'][1]) {
                        $changesExist = true;
                        $columns['item'] = $this->_emphasizeText($columns['item']);
                    }
                    if ($data['description'][0] !== $data['description'][1]) {
                        $changesExist = true;
                        $columns['description'] = $this->_emphasizeText($columns['description']);
                    }
                    if ($data['value'][0] !== $data['value'][1]) {
                        $changesExist = true;
                        $columns['value'] = $this->_emphasizeText($columns['value']);
                    }
                }
                $index++;
                $controls = new Libraries\StagingControls($existsInSource, $existsInTarget, $changesExist, $this->defaultMenuOption, $data['selectedOption'][1]);
                $columns['selectedOption'] = $controls->getSelectedOptionKey();
                $this->_setMenuOptions($controls->getOptionAttributes());
                if ($readOnly === true && $columns['selectedOption'] === 0) {
                    continue; // No action
                }
                if ($location === 'staging') {
                    $columns['menu'] = ($readOnly === true) ? $controls->getSelectedOption() : $controls->getDropDownMenu($columns['id'], 'configurationsDataTable');
                    $columns['enabled'] = '<span class="disabledText">' . Config::getMessage(NA_LBL) . '</span>';
                }
                array_push($array, json_encode($columns));
            }
        }
        return implode(",\n", $array);
    }

    private function _setMenuOptions($options) {
        $index = 0;
        foreach($options as $attributes) {
            if ($attributes['disabled'] === false) {
                $this->menuOptions[$index] = false;
            }
            $index++;
        }
    }

    private function _getPageSetColumns($readOnly = true) {
        return array(
          'id' => array('hidden' => true),
          'menu' => array('label' => $this->_getActionLabel($readOnly)),
          'location' => array('label' => Config::getMessage(LOCATION_LBL)),
          'item' => array('label' => Config::getMessage(REGULAR_EXPRESSION_LBL)),
          'description' => array('label' => Config::getMessage(DESCRIPTION_LBL)),
          'value' => array('label' => Config::getMessage(PAGE_SET_LBL)),
          'exists' => array('label' => Config::getMessage(EXISTS_LBL)),
          'enabled' => array('label' => Config::getMessage(ENABLED_LBL)),
          'selectedOption' => array('hidden' => true),
        );
    }

    private function _getActionLabel($readOnly = true) {
        $label = Config::getMessage(ACTION_LBL);
        if ($readOnly !== true) {
            $label .= ":<br/>" . Libraries\StagingControls::getDropDownMenuForHeader($this->menuOptions, $this->defaultMenuOption, 'configurationsDataTable');
        }
        return $label;
    }

    // Return a 3 element array of columnDefinitions, responseScheme and pageSetData.
    private function _getPageSetDefinitions($readOnly = true, $pageSets = null) {
        $columnDefinitions = array();
        $responseSchema = array();
        $template = '{key: "%s"%s}';
        // Note: It is important the _getPageSetData() call preceeeds the call to _getPageSetColumns below
        // so the select-all drop-down menu displayed from the 'Actions' column header has the correct values.
        $pageSetData = $this->_getPageSetData($readOnly, $pageSets);
        foreach ($this->_getPageSetColumns(($readOnly === true || count($pageSets) < 2)) as $key => $values) {
            array_push($responseSchema, sprintf($template, $key, ''));
            if (!(array_key_exists('hidden', $values) && $values['hidden'] === true)) {
                array_push($columnDefinitions, sprintf($template, $key, ', allowHTML: true, label: "' . Text::escapeStringForJavaScript($values['label']) . '", formatter:cellFormatter'));
            }
        }
        return array(implode(",\n", $columnDefinitions), implode(",\n", $responseSchema), $pageSetData);
    }

    private function _emphasizeText($text) {
        return "<strong>$text</strong>";
    }
}
