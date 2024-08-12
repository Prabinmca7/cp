<?php
namespace RightNow\Internal\Libraries;
use RightNow\Utils\FileSystem,
    RightNow\Internal\Utils\Widgets,
    RightNow\Internal\Utils\Deployment,
    RightNow\Utils\Text,
    RightNow\Utils\Config,
    RightNow\Api;

require_once CPCORE . 'Internal/Libraries/Deployer.php';
require_once CPCORE . 'Internal/Utils/Admin.php';
require_once CPCORE . 'Internal/Libraries/WebDav/PathHandler.php';
require_once CPCORE . 'Internal/Utils/Deployment.php';

/**
 * A collection of classes and functions for dealing with Deployment Manager's 'stage', 'promote' and 'rollback' operations.
 */

/**
 * Base class for staging directory verification, creation and removal.
 */
class Staging{

    private $logPath = null;
    private $stagingName = null;
    protected $stagingSource = null;
    protected $docrootPath = null;
    protected $stagingPath = null;
    protected $developmentSource = null;
    protected $CI = null;
    protected $productionPath;
    protected $backupDirectory;

    public function __construct($stagingName) {
        $this->stagingName = $stagingName;
        if (!self::isValidStagingName($stagingName)) {
            throw new \Exception("Not a valid staging name: $stagingName");
        }
        $this->stagingPath = \RightNow\Internal\Utils\Admin::getStagingBasePath() . $stagingName . '/';
        $this->developmentSource = APPPATH;
        $this->stagingSource = $this->stagingPath . 'source/';
        $this->docrootPath = HTMLROOT . "/euf/generated/staging/{$this->stagingName}/";
        $this->CI = get_instance();
        $this->productionPath = OPTIMIZED_FILES . 'production/';
        $this->backupDirectory = "{$this->productionPath}backup/";
        umask(IS_HOSTED ? 0002 : 0);
    }

    public function getDocrootPath() {
        return $this->docrootPath;
    }

    public function getStagingPath() {
        return $this->stagingPath;
    }

    public function getStagingName() {
        return $this->stagingName;
    }

    public function getLogPath($logType = 'stage') {
        if ($this->logPath === null) {
            $this->logPath = Api::cfg_path() . "/log/$logType" . time() . '.log';
        }
        return $this->logPath;
    }


    /**
     * Move staging directories to their corresponding backup location.
     */
    public function backupAndRemoveStagingDirectories() {
        $this->backupOrRestoreStagingDirectories();
    }

    /**
     * Restore staging directories from their corresponding backup location.
     */
    public function restoreStagingDirectories() {
        $this->backupOrRestoreStagingDirectories(false);
    }

    /**
     * Return an array of directory pairs: array(<development dir>, <staging dir>),...
     *
     * @param bool $includeHtmlRootOptimized If true, add HTMLROOT/euf/rightnow/[*]/optimized directories.
     * @return array
     */
    public function getStagingDirectories($includeHtmlRootOptimized = false) {
        $directories = array(
            array(CUSTOMER_FILES, $this->stagingSource),
            array(HTMLROOT . '/euf/assets/themes', $this->docrootPath . 'source/assets/themes'),
        );

        if ($includeHtmlRootOptimized) {
            $directories[] = array(HTMLROOT . '/euf/generated/optimized', $this->docrootPath . 'optimized');
        }
        return $directories;
    }

    /**
     * Remove staging_xx, then copy:
     *     '.cfg/scripts/cp/generated/development/' -> '.cfg/scripts/cp/generated/staging/staging_01/source/'
     *     '<DOCROOT>/euf/assets/themes' -> '<DOCROOT>/euf/generated/staging/staging_01/source/assets/themes'
     *
     * @param bool $removeFirstIfExists Whether to remove staging directories if they exist
     * @param bool $shouldModifyPageSetConfiguration Whether to write out changes to the page set mapping file
     * @throws \Exception If staging directory already exists and $removeFirstIfExists is set to false
     */
    public function createStagingDirectories($removeFirstIfExists = false, $shouldModifyPageSetConfiguration = true) {
        if (FileSystem::isWritableDirectory($this->stagingPath) && $removeFirstIfExists !== true) {
            throw new \Exception(sprintf("Staging directory already exists '%s'. Set \$removeFirstIfExists to true if overwrite desired.", $this->stagingName));
        }
        $this->backupAndRemoveStagingDirectories();
        $directories = $this->getStagingDirectories(true);
        foreach ($directories as $pairs) {
            list($fromDir, $toDir) = $pairs;
            printf(Config::getMessage(CPYING_PCT_S_TO_PCT_S_LBL) . '<br/>', self::shortenPathName($fromDir), self::shortenPathName($toDir));
            FileSystem::mkdirOrThrowExceptionOnFailure(dirname($toDir), true);
            FileSystem::copyDirectory($fromDir, $toDir);
        }

        // Write to source/config/pageSetMapping.php with all enabled page sets so staging mirrors development.
        // Note: even if there are zero enabled page sets, we still want to write to this file in case there
        // is a development pageSetMapping.php file (ignored, but sometimes present), which could cause
        // undesired effects if left in staging.
        if ($shouldModifyPageSetConfiguration)  {
            $path = "{$this->stagingPath}source/{$this->CI->model('Pageset')->getPageSetFilePath()}";
            if (!@file_put_contents($path, $this->CI->model('Pageset')->getDeployedContent())) {
                throw new \Exception(sprintf(Config::getMessage(COULD_NOT_WRITE_TO_PCT_S_MSG), $path));
            }
        }
    }

    public static function stagingRegex() {
        return sprintf('/^%s(?!00)([0-9]{2})$/', STAGING_PREFIX);
    }

    /**
     * Remove file or empty directory specified by $targetBaseDirectory . $targetPath
     * This should generally be used to remove only files, and letting it call itself recursively to prune empty directories.
     * This function will only recursively prune a directory if A) it is now empty after removing the last file, and B)
     * the directory does not exist in the $sourceBaseDirectory.
     *
     * Calls made directly to this function to remove an empty directory will simply remove it, and bypass the 'exists-in-source' check.
     *
     * Empty directories will only be pruned back to $targetBaseDirectory.
     *
     * @param string $sourceBaseDirectory Absolute path to base source directory.
     *     This is used to determine if empty target directories should be pruned.
     *     Example: /www/rnt/{site}/cgi-bin/{interface}.cfg/script/euf/application/development/source/
     * @param string $targetBaseDirectory Absolute path to base target directory.
     *     This plus $targetPath should be the absolute path to the file or directory to be deleted.
     *     Example: /www/rnt/{site}/cgi-bin/{interface}.cfg/script/euf/application/staging/staging_01/source/
     * @param string $targetPath Relative path to file or directory to be removed.
     *     This added to $targetBaseDirectory should be the absolute path to the file or directory to be deleted.
     *     Example: views/pages/answers/list.php
     * @return array Of "removing file/dir $target" messages to be used for logging.
     * @throws \Exception If:
     *     - either $sourceBaseDirectory or $targetBaseDirectory are not valid directories.
     *     - $targetBaseDirectory . $targetPath is not a valid file or an empty directory.
     *     - an error is encountered removing the file or directory.
     */
    public static function removeFileAndPruneEmptyDirectories($sourceBaseDirectory, $targetBaseDirectory, $targetPath) {
        static $messages, $knownDirectories, $knownEmptyDirectories;

        $isDirectory = function($directory) {
            if (!isset($knownDirectories)) {
                $knownDirectories = array();
            }
            else if (array_key_exists($directory, $knownDirectories)) {
                return true;
            }
            if (FileSystem::isReadableDirectory($directory)) {
                $knownDirectories[$directory] = true;
                return true;
            }
            return false;
        };

        $isEmptyDirectory = function($directory) use ($isDirectory) {
            if (!isset($knownEmptyDirectories)) {
                $knownEmptyDirectories = array();
            }
            else if (array_key_exists($directory, $knownEmptyDirectories)) {
                return true;
            }
            if ($isDirectory($directory) && !FileSystem::listDirectory($directory)) {
                $knownEmptyDirectories[$directory] = true;
                return true;
            }
            return false;
        };

        $sourceBaseDirectory = Text::removeTrailingSlash($sourceBaseDirectory) . '/';
        $targetBaseDirectory = Text::removeTrailingSlash($targetBaseDirectory) . '/';
        foreach (array($sourceBaseDirectory, $targetBaseDirectory) as $baseDirectory) {
            if (!$isDirectory($baseDirectory)) {
                throw new \Exception(Config::getMessage(DIRECTORY_DOES_NOT_EXIST_LBL) . " " . $baseDirectory);
            }
        }

        $targetPath = ltrim($targetPath, '/');
        $target = "{$targetBaseDirectory}{$targetPath}";

        if (FileSystem::isReadableFile($target)) {
            if (!@unlink($target)) {
                throw new \Exception(Config::getMessage(ERROR_REMOVING_FILE_LBL) . $target);
            }
            $messages[] = Config::getMessage(REMOVING_FILE_LBL) . " " . self::shortenPathName($target);
        }
        else if ($isEmptyDirectory($target)) {
            FileSystem::removeDirectoryRecursivelyOrThrowExceptionOnFailure($target, true);
            $messages[] = Config::getMessage(REMOVING_EMPTY_DIRECTORY_LBL) . " " . self::shortenPathName($target);
        }
        else {
            throw new \Exception(Config::getMessage(NOT_A_VALID_TARGET_FOR_DELETION_LBL) . " " . $target);
        }

        // Determine if containing directory is now empty and suitable for deletion.
        $relativeDirectoryPath = (dirname($targetPath) === '.') ? '' : dirname($targetPath);
        $targetDirectoryPath = "{$targetBaseDirectory}$relativeDirectoryPath";
        if (($targetDirectoryPath !== $targetBaseDirectory) && $isEmptyDirectory($targetDirectoryPath) && !$isDirectory("{$sourceBaseDirectory}$relativeDirectoryPath")) {
            return self::removeFileAndPruneEmptyDirectories($sourceBaseDirectory, $targetBaseDirectory, $relativeDirectoryPath);
        }

        // Reset static variables
        $messagesToReturn = $messages;
        $messages = $knownDirectories = $knownEmptyDirectories = array();

        return $messagesToReturn;
    }

    /**
     * Returns permission mode
     * @return int Usually 509 (0775) for HOSTED, otherwise 511 (0777)
     */
    public static function modeFromCurrentUmask() {
        return 0777 & ~umask();
    }

    public static function shortenPathName($path) {
        foreach(array(CORE_FILES, OPTIMIZED_FILES, DOCROOT . "/cp/", HTMLROOT) as $basePath) {
            if (Text::beginsWith($path, $basePath)) {
                return Text::getSubstringAfter($path, $basePath);
            }
        }
        return $path;
    }

    public static function isValidStagingName($stagingName) {
        return preg_match(self::stagingRegex(), $stagingName);
    }

    public function ensureStagingPathExists() {
        if (!FileSystem::isWritableDirectory($this->stagingPath)) {
            throw new \Exception("Staging directory does not exist: $this->stagingPath");
        }
    }

    /**
     * Move staging directories to or from their corresponding backup location.
     * @param bool $backup If true, move staging to backup locations, else restore from backup.
     */
    private function backupOrRestoreStagingDirectories($backup = true) {
        if (!FileSystem::isReadableDirectory($this->backupDirectory)) {
            FileSystem::mkdirOrThrowExceptionOnFailure($this->backupDirectory, true);
        }

        $directories = array(
            $this->stagingPath => "{$this->backupDirectory}{$this->stagingName}",
            $this->docrootPath => "{$this->backupDirectory}{$this->stagingName}.docroot",
        );

        if (!$backup) {
            $directories = array_flip($directories);
        }

        foreach ($directories as $source => $target) {
            if (FileSystem::isReadableDirectory($source)) {
                if (FileSystem::isReadableDirectory($target)) {
                    FileSystem::removeDirectory($target, true);
                }
                printf(Config::getMessage(MOVING_PCT_S_GREATER_THAN_PCT_S_LBL) . '<br/>', self::shortenPathName($source), self::shortenPathName($target));
                FileSystem::renameOrThrowExceptionOnFailure($source, $target);
            }
        }
    }
}

/**
 * Class for copying specified files and configurations from development to the specified staging directoy.
 *  This class is used by Deployer from StagingDeployOptions.
 */
class Stage extends Staging {
    private $filesToCopy = array();
    private $filesToRemove = array();
    private $pageSetChanges = array();
    private $pushVersionChanges = true;
    private $validActions = null;
    private $initialize = null;
    private $account = null;
    protected $shouldDestroyLock = false;
    public $lockCreatedTime = null;
    /**
     * Constructor
     * @param string $stagingName Name of staging environment, e.g. staging_xx
     * @param array $stageData Data specified to stage. Valid indices are:
     *                 lockCreatedTime (int)
     *                 initialize (boolean)  If true, completely remove and re-create staging directories prior to deploying.
     *                 files (array)  An array of files to either copy or remove from staging.
     *                 configurations (array) An array of configurations to either copy or remove from staging. Specify null or empty array if no configuration changes.
     *                 pushVersionChanges (boolean) Whether or not to push all framework and widget versioning changes
     */
    public function __construct($stagingName, array $stageData = array()) {
        parent::__construct($stagingName);
        $files = (isset($stageData['files'])) ? $stageData['files'] : null;
        $configurations = (isset($stageData['configurations'])) ? $stageData['configurations'] : null;
        $versionPush = (isset($stageData['pushVersionChanges'])) ? $stageData['pushVersionChanges'] : null;
        $this->initialize = (isset($stageData['initialize'])) ? $stageData['initialize'] : false;
        $this->validActions = array_keys(StagingControls::getOptions());
        $this->validateFiles($files);
        $this->validateConfigurations($configurations);
        $this->validatePushVersionChanges($versionPush);
        $this->lockCreatedTime = $stageData['lockCreatedTime'];
        $this->ensureRequiredDirectoriesExist();
    }
    

    public function ensureRequiredDirectoriesExist() {
        $directories = $this->getStagingDirectories(true);
        $directories[] = array(CUSTOMER_FILES . 'widgets', "{$this->stagingSource}widgets");
        foreach ($directories as $pairs) {
            foreach ($pairs as $directory) {
                if (!FileSystem::isReadableDirectory($directory)) {
                    FileSystem::mkdirOrThrowExceptionOnFailure($directory, true);
                }
            }
        }
    }

    public function getAccount() {
        if ($this->account === null) {
            $this->account = method_exists($this->CI, '_getAgentAccount') ? $this->CI->_getAgentAccount() : false;
        }
        return $this->account;
    }

    public function shouldInitialize() {
        return ($this->initialize === true);
    }

    /**
     * Validate $files array.
     * @param array|null $files List of files
     * @throws \Exception If $files isn't an array or is empty
     */
    public function validateFiles($files) {
        if ($files === null || $files === array()) {
            return;
        }
        if (!is_array($files)) {
            throw new \Exception('Files not an array.');
        }
        if ($this->initialize === true && !empty($files)) {
            throw new \Exception('Cannot specify files when initialize is true.');
        }
        foreach ($files as $data) {
            list($developmentPath, $stagingPath, $action) = $data;
            $this->validateAction($action);
            if ($action === 1) {
                $this->validatePath($developmentPath);
                $this->filesToCopy[] = array($developmentPath, $stagingPath);
            }
            else if ($action === 2) {
                $this->validatePath($stagingPath);
                $this->filesToRemove[] = array($developmentPath, $stagingPath);
            }
        }
    }

    public function getFilesToCopy() {
        return $this->filesToCopy;
    }

    public function getFilesToRemove() {
        return $this->filesToRemove;
    }

    public function getPageSetChanges() {
        return $this->pageSetChanges;
    }

    public function shouldPushVersionChanges() {
        return $this->pushVersionChanges;
    }

    private function validatePath($path) {
        if (!is_readable($path)) {
            throw new \Exception(Config::getMessage(INVALID_PATH_LBL) . $path);
        }
    }

    private function validateAction($action) {
        if (!in_array($action, $this->validActions)) {
            throw new \Exception("Invalid page set action: $action");
        }
    }

    /**
     * Validate $configurations array.
     * Currently $configurations should only contain 'pageSetChanges' but may contain
     * other configurations (clickstream, config bases, etc.) in the future.
     * @param array|null $configurations List of configurations
     * @throws \Exception If $configurations isn't an array or is empty
     */
    private function validateConfigurations($configurations) {
        if ($configurations === null || $configurations === array()) {
            return;
        }
        if (!is_array($configurations)) {
            throw new \Exception('Configurations not an array.');
        }
        if ($this->initialize === true && !empty($configuratinos)) {
            throw new \Exception('Cannot specify configurations when initialize is true.');
        }
        if (array_key_exists('pageSetChanges', $configurations)) {
            if (!is_array($configurations['pageSetChanges'])) {
                throw new \Exception('pageSetChanges not an array.');
            }
            foreach ($configurations['pageSetChanges'] as $key => $action) {
                $this->validateAction($action);
            }
            $this->pageSetChanges = $configurations['pageSetChanges'];
        }
    }

    private function validatePushVersionChanges($pushVersionChanges) {
        if(!is_bool($pushVersionChanges))
            return;
        if ($this->initialize === true && $pushVersionChanges === false)
            throw new \Exception('Must push version changes when initialize is true.');
        $this->pushVersionChanges = $pushVersionChanges;
    }
}

/**
 * Class for copying specified staging directory to production
 */
class Promote extends Stage {
    private $logPath = null;
    private $logger = null;
    private $errorCount = 0;
    private $shouldRemoveDeployLock = false;
    protected $newTimestamp = null;
    protected $oldTimestamp = null;
    protected $versionFile;
    protected $comment;

    public function __construct($stagingName, $lockCreatedTime = null, $comment = null) {
        $this->logPath = $this->getLogPath('promote');
        $this->comment = $comment;
        parent::__construct($stagingName, array('lockCreatedTime' => $lockCreatedTime));
        $this->versionFile = "{$this->backupDirectory}version";
    }

    public function log($message, $level = 'debug', $leadingHtml = '', $trailingHtml = '...<br />') {
        $this->logger->log($message, $level, $leadingHtml, $trailingHtml);
    }

    /**
     * Write <cps_error_count> and <cps_log_file_name> tags to log.
     */
    public function writeLogStatus() {
        $this->log(sprintf(Config::getMessage(NUMBER_OF_DEPLOY_ERRORS_PCT_S_LBL), Deployment::getCPErrorCountTags($this->errorCount)));
        $this->log(sprintf(Config::getMessage(LOG_FILE_PCT_S_LBL), Deployment::getCPLogFileNameTags($this->logPath)));
        if ($this->errorCount === 0) {
            $this->log(Config::getMessage(DEPLOY_OPERATION_SUCCESSFUL_LBL), 'INFO');
        }
        else {
            $this->log(Config::getMessage(DEPLOY_OPERATION_FAILED_LBL), 'ERROR');
        }
        // TODO: log "x errors were encountered message"
    }

    public function writeLogHeader($deployType, $message = null) {
        $this->logger = new DeployLogger($this->logPath);
        $this->logger->writeLogHeader(
            ($message === null) ? Config::getMessage(CP_DEPLOYMENT_LOG_LBL) : $message,
            $deployType,
            $this->comment,
            Deployment::getAccountInformation($this->getAccount())
        );
    }

    public function startPromote() {
        try {
            $this->writeLogHeader(Config::getMessage(PROMOTE_DEPLOYMENT_LBL));
            if (!FileSystem::isReadableDirectory("{$this->stagingPath}optimized/views/")) {
                $this->log('ERROR: ' . Config::getMessage(OPTIMIZED_STAGING_FILES_APPEAR_EX_MSG), 'ERROR');
                return false;
            }
            if (!$this->verifyDeployLock()) {
                return;
            }
            $this->backupProductionDirectories();
            $this->copyStagingToProduction();
            $this->removeProductionFilesNotInStaging();
            if ($newTimestamp = $this->getNewTimestamp()) {
                $this->removeOldOptimizedAssetsDirectories($newTimestamp);
            }
            FileSystem::writeDeployTimestampToFile();
            $this->lockPageSetMappings();
            $this->writeLogStatus();
            $this->removeDeployLock();
            Deployment::recordVersionChanges("{$this->backupDirectory}optimized/", "{$this->productionPath}optimized/", 'production');
            $this->pruneBackups();
        }
        catch (\Exception $e) {
            $this->log('ERROR: ' . $e->getMessage(), 'ERROR');
            return false;
        }
        return true;
    }

    protected function verifyDeployLock() {
        $accountID = $existingLockAccountID = $this->getAccount()->acct_id;
        if (!$accountID || !$createdTime = $this->lockCreatedTime) {
            $existingLockAccountID = null;
            $createdTime = null;
        }
        $lockData = Deployment::createDeployLock($accountID, Config::getMessage(PROMOTE_DEPLOYMENT_LBL), $existingLockAccountID, $createdTime);
        if ($lockData['lock_obtained'] === true) {
            $this->shouldRemoveDeployLock = true;
            $this->log($lockData['message']);
        }
        else {
            $this->log("{$lockData['message']}<br/>{$lockData['lock_details']}");
            return false;
        }
        return true;
    }

    protected function removeDeployLock() {
        if ($this->shouldRemoveDeployLock === true) {
            list($lockWasRemoved) = Deployment::removeDeployLock($this->getAccount()->acct_id);
            if ($lockWasRemoved) {
                $this->log(Config::getMessage(DEPLOY_LOCK_REMOVED_MSG));
            }
            else {
                $this->log(Config::getMessage(ERROR_ENC_REMOVING_DEPLOY_LOCK_MSG), 'warn');
            }
        }
    }

    protected function removeOldOptimizedAssetsDirectories($exclude) {
        $paths = FileSystem::listDirectory(OPTIMIZED_ASSETS_PATH, true, false, array('function', function($f) use ($exclude) {
            return ($f->isDir() && $f->getFilename() !== $exclude && preg_match('/^[0-9]{10}$/', $f->getFilename()));
        }));

        foreach ($paths as $path) {
            $this->log(sprintf(Config::getMessage(REMOVING_OPTIMIZED_ASSETS_DIRECTORY_LBL), basename($path)));
            FileSystem::removeDirectory($path, true);
        }
    }

    /**
     * Find and remove files that exist in source, but not in target.
     *
     * @param array $sourceAndTargetDirectories An array of source and target directories.
     * @param array $allowableFilesToDelete If specified, only delete target files explicitly contained in this array (if they don't exist in sournce). If empty, all files are fair game.
     */
    protected function removeFilesFromTargetNotInSource(array $sourceAndTargetDirectories, array $allowableFilesToDelete = array()) {
        foreach($sourceAndTargetDirectories as $sourceDir => $targetDir) {
            if (FileSystem::isReadableDirectory($sourceDir) && FileSystem::isReadableDirectory($targetDir)) {
                foreach (Deployment::getFileDiffs($sourceDir, $targetDir) as $files) {
                    list($sourceFile, $targetFile) = $files;
                    if ($sourceFile === null && FileSystem::isReadableFile($targetFile) && (empty($allowableFilesToDelete) || in_array($targetFile, $allowableFilesToDelete))) {
                        foreach(Staging::removeFileAndPruneEmptyDirectories($sourceDir, $targetDir, Text::getSubstringAfter($targetFile, $targetDir)) as $message) {
                            $this->log($message);
                        }
                    }
                }
            }
        }
    }

    protected function copyDirectories($directories, $removeTargetDirPriorToCopy = false) {
        foreach($directories as $source => $target) {
            $this->log(sprintf(Config::getMessage(CPYING_PCT_S_TO_PCT_S_LBL), Staging::shortenPathName($source), Staging::shortenPathName($target)));
            if ($removeTargetDirPriorToCopy && FileSystem::isReadableDirectory($target)) {
                FileSystem::removeDirectory($target, true);
            }
            FileSystem::copyDirectory($source, $target);
        }
    }

    private function removeProductionFilesNotInStaging() {
        $this->removeFilesFromTargetNotInSource($this->getSourceAndTargetDirectories());
    }

    private function lockPageSetMappings() {
        $this->log(Config::getMessage(LOCKING_PAGE_SET_MAPPINGS_LBL));
        $this->CI->model('Pageset')->lockPageSetMappings("{$this->productionPath}source");
    }

    private function verifyBackup() {
        foreach($this->getBackupDirectories() as $source => $target) {
            if ($diffs = Deployment::getFileDiffs($source, $target)) {
                throw new \Exception("Files differ between '$source' and '$target'" . var_export($diffs, true));
            }
        }
    }

    private function getNewTimestamp() {
        if ($this->newTimestamp === null) {
            $this->newTimestamp = FileSystem::getLastDeployTimestampFromDir("{$this->docrootPath}optimized");
        }
        return $this->newTimestamp;
    }

    private function getOldTimestamp() {
        if ($this->oldTimestamp === null) {
            $this->oldTimestamp = FileSystem::getLastDeployTimestampFromDir();
        }
        return $this->oldTimestamp;
    }

    private function getBackupDirectories() {
        $directories = array(
            "{$this->productionPath}source" => "{$this->backupDirectory}source",
            "{$this->productionPath}optimized" => "{$this->backupDirectory}optimized",
        );

        if ($timestamp = $this->getOldTimestamp()) {
            $directories[HTMLROOT . "/euf/generated/optimized/$timestamp"] = "{$this->backupDirectory}optimizedAssets/$timestamp";
        }

        return $directories;
    }

    /**
     * Copy .cfg/scripts/cp/generated/staging_XX/source    -> .cfg/scripts/cp/generated/production/source
     *        .cfg/scripts/cp/generated/staging_XX/optimized -> .cfg/scripts/cp/generated/production/optimized
     */
    private function copyStagingToProduction() {
        $this->copyDirectories($this->getSourceAndTargetDirectories());
    }

    private function getSourceAndTargetDirectories() {
        $directories = array(
            "{$this->stagingPath}source" => "{$this->productionPath}source",
            "{$this->stagingPath}optimized" => "{$this->productionPath}optimized",
        );

        if ($timestamp = $this->getNewTimestamp()) {
            $directories["{$this->docrootPath}optimized/$timestamp"] = HTMLROOT . "/euf/generated/optimized/$timestamp";
        }

        return $directories;
    }

    /**
     * Copy .cfg/scripts/cp/generated/production/source    -> .cfg/scripts/cp/generated/production/backup/source
     *        .cfg/scripts/cp/generated/production/optimized -> .cfg/scripts/cp/generated/production/backup/optimized
     *        vhosts/{interface}/euf/generated/production/optimized/{timestamp} -> .cfg/scripts/cp/generated/production/backup/{timestamp}
     */
    private function backupProductionDirectories() {
        $backupDirectories = $this->getBackupDirectories();
        foreach (array_keys($backupDirectories) as $source) {
            if (!FileSystem::isReadableDirectory($source)) {
                mkdir($source, Staging::modeFromCurrentUmask(), true);
            }
        }
        $this->copyDirectories($backupDirectories, true);
        file_put_contents($this->versionFile, MOD_BUILD_VER);
        $this->verifyBackup();
    }

    /**
     * Clean up cruft that tends to build up in the backupDirectory, such as timestamped directories.
     */
    private function pruneBackups() {
        $optimizedAssetsBackupDir = "{$this->backupDirectory}optimizedAssets";
        if (FileSystem::isReadableDirectory($optimizedAssetsBackupDir)
            && ($timestamps = FileSystem::listDirectory($optimizedAssetsBackupDir, false, false, array('match', "/^\d{10}$/")))
            && count($timestamps) > 1)
        {
            rsort($timestamps);
            $lastTimestamp = $this->getOldTimestamp();
            if (!$lastTimestamp || !in_array($lastTimestamp, $timestamps)) {
                $lastTimestamp = $timestamps[0];
            }
            foreach($timestamps as $timestamp) {
                if ($timestamp !== $lastTimestamp) {
                    Filesystem::removeDirectory("$optimizedAssetsBackupDir/$timestamp", true);
                }
            }
        }
    }
}

/**
 * Class for restoring production from the backup created during promote.
 */
final class Rollback extends Promote {
    private $logPath = null;
    public $lockCreatedTime;
    public $comment;

    public function __construct($stagingName, $lockCreatedTime = null, $comment = null) {
        $this->logPath = $this->getLogPath('rollback');
        parent::__construct($stagingName, $lockCreatedTime, $comment);
    }

    /**
     * Starts rollback process
     * @return boolean Returns true upon success.
     */
    public function startRollback() {
        try {
            $this->verifyBackup();
            $this->writeLogHeader(Config::getMessage(ROLLBACK_DEPLOYMENT_LBL));
            if (!$this->verifyDeployLock()) {
                return;
            }
            Deployment::recordVersionChanges("{$this->productionPath}optimized/", "{$this->backupDirectory}optimized/", 'production');
            $this->restoreProductionDirectories();
            $this->removeFilesIntroducedSincePromote();
            if ($oldTimestamp = $this->getOldTimestamp()) {
                $this->removeOldOptimizedAssetsDirectories($oldTimestamp);
            }
            FileSystem::writeDeployTimestampToFile();
            $this->writeLogStatus();
            $this->removeDeployLock();
        }
        catch (\Exception $e) {
            $this->log('ERROR: ' . $e->getMessage(), 'ERROR');
            return false;
        }
        return true;
    }

    public function verifyBackup() {
        $dateFromLog = function($deployType) {
            if ($logInfo = Deployment::getInfoFromLastLog($deployType, false)) {
                return $logInfo[0];
            }
        };

        if (!$promoteDate = $dateFromLog('promote')) {
            throw new \Exception(Config::getMessage(NO_PROMOTE_TO_RESTORE_FROM_LBL));
        }

        if (!FileSystem::isReadableFile($this->versionFile)) {
            if (FileSystem::isReadableDirectory($this->backupDirectory))
                throw new \Exception(sprintf(Config::getMessage(ABLE_ROLLBACK_T_VIEW_SRC_FILES_MSG), '/dav/cp/generated/production/backup/', '/dav/cp/generated/production/backup/'));
            else
                throw new \Exception(Config::getMessage(YOU_ARE_NOT_ABLE_ROLLBACK_TIME_MSG));
        }

        if (($backupVersion = file_get_contents($this->versionFile)) !== MOD_BUILD_VER) {
            $backupVersion = \RightNow\Internal\Utils\Version::versionNumberToName($backupVersion);
            $currentVersion = \RightNow\Internal\Utils\Version::versionNumberToName(MOD_BUILD_VER);
            if ($backupVersion !== $currentVersion) {
                throw new \Exception(sprintf(Config::getMessage(BACKUP_INV_PROMOTE_PERFORMED_MSG), $backupVersion, $currentVersion));
            }
        }

        if (($rollbackDate = $dateFromLog('rollback')) && $rollbackDate >= $promoteDate) {
            throw new \Exception(Config::getMessage(ROLLBACK_ALREADY_PERFORMED_LBL));
        }

        foreach(array_keys($this->getRestoreDirectories()) as $source) {
            if (!FileSystem::isReadableDirectory($source)) {
                throw new \Exception(sprintf(Config::getMessage(BACKUP_DIRECTORY_MISSING_PCT_S_CMD), $source));
            }
        }
    }

    public function startPromote() {
        throw new \Exception('startPromote() should only be called from Promote class');
    }

    private function getOldTimestamp() {
        if ($this->oldTimestamp === null && FileSystem::isReadableDirectory("{$this->backupDirectory}optimizedAssets")) {
            $this->oldTimestamp = FileSystem::getLastDeployTimestampFromDir("{$this->backupDirectory}optimizedAssets");
        }
        return $this->oldTimestamp;
    }

    private function getRestoreDirectories() {
        $directories = array(
            "{$this->backupDirectory}source" => "{$this->productionPath}source",
            "{$this->backupDirectory}optimized" => "{$this->productionPath}optimized",
        );

        if ($timestamp = $this->getOldTimestamp()) {
            $directories["{$this->backupDirectory}optimizedAssets/$timestamp"] = HTMLROOT . "/euf/generated/optimized/$timestamp";
        }

        return $directories;
    }

    private function restoreProductionDirectories() {
        $this->copyDirectories($this->getRestoreDirectories());
    }

    /**
     * Remove files introduced to production since the promote we're now rolling back from (IE. exists in production but not the backup).
     */
    private function removeFilesIntroducedSincePromote() {
        $this->removeFilesFromTargetNotInSource($this->getRestoreDirectories());
    }
}

/**
 * A class for determining what the selected and disabled options should be ('No action'|'Copy to staging'|'Remove from staging')
 * for files and configurations between development and staging.
 * TODO: move to new admin helper view...
 */
final class StagingControls{
    private $options = null;
    private $optionAttributes = null;
    private $selectedOptionKey = null;
    private $disabledOptionKeys = null;
    private $existsInDev;
    private $existsInStaging;
    private $changesExist;
    private $defaultOption;
    private $overrideOption;

    public function __construct($existsInDev, $existsInStaging, $changesExist, $defaultOption = 0, $overrideOption = null) {
        $this->existsInDev = (bool) $existsInDev;
        $this->existsInStaging = (bool) $existsInStaging;
        $this->changesExist = (bool) $changesExist;
        $this->options = self::getOptions();
        $this->defaultOption = (array_key_exists($defaultOption, $this->options)) ? $defaultOption : 0;
        $this->overrideOption = (array_key_exists($overrideOption, $this->options)) ? $overrideOption : null;
    }

    public static function getOptions() {
        return array(Config::getMessage(NO_ACTION_LBL),
                     Config::getMessage(COPY_TO_STAGING_CMD),
                     Config::getMessage(REMOVE_FROM_STAGING_CMD)
                    );
    }

    public function getSelectedOption() {
        return $this->options[$this->getSelectedOptionKey()];
    }

    public function getSelectedOptionKey() {
        if ($this->selectedOptionKey === null) {
            if ($this->overrideOption !== null) {
                $this->selectedOptionKey = $this->overrideOption;
            }
            else {
                $this->selectedOptionKey = $this->defaultOption;
            }

            if (in_array($this->selectedOptionKey, $this->getDisabledOptionKeys())) {
                $this->selectedOptionKey = 0; // No action
            }
        }
        return $this->selectedOptionKey;
    }

    private function getDisabledOptionKeys() {
        if ($this->disabledOptionKeys === null) {
            $this->disabledOptionKeys = array();
            if (!$this->existsInDev || ($this->existsInStaging && !$this->changesExist)) {
                array_push($this->disabledOptionKeys, 1); // Copy to staging
            }
            if (!$this->existsInStaging) {
                array_push($this->disabledOptionKeys, 2); // Remove from staging
            }
        }
        return $this->disabledOptionKeys;
    }

    public function getOptionAttributes() {
        if ($this->optionAttributes === null) {
            $this->optionAttributes = array();
            foreach($this->options as $key => $option) {
                $this->optionAttributes[$option] = array(
                  'selected' => ($key === $this->getSelectedOptionKey()),
                  'disabled' => (in_array($key, $this->getDisabledOptionKeys()))
                );
            }
        }
        return $this->optionAttributes;
    }

    public function getDropDownMenu($id, $className) {
        $label = Config::getMessage(ACTION_LBL);
        $html = "<label for='$id' class='screenreader'>$label</label><select id='$id' class='$className' onChange='dropDownChangeEvent(this);'>";
        $value = 0;
        foreach($this->getOptionAttributes() as $option => $attributes) {
            $selected = $attributes['selected'] ? 'selected' : '';
            $disabled = $attributes['disabled'] ? 'disabled' : '';
            $html .= "<option value=\"$value\" $selected $disabled>$option</option>";
            $value++;
        }
        return "$html</select>";
    }

    /**
     * Return the drop-down menu displayed from the 'Actions' header column.
     * @param array   $options An array of 'disabled' statuses for each menu item.  EG. array(false, false, false).
     * @param integer $defaultOption The index indicating the option that should be selected by default.
     * @param string  $className The class name for the select element.
     * @return string An html menu
     */
    public static function getDropDownMenuForHeader(array $options, $defaultOption, $className) {
        $label = Config::getMessage(SELECT_ACTION_FOR_ALL_LBL);
        $html = "<label for='selectAll' class='screenreader'>$label</label><select id='selectAll' class='$className' onChange='dropDownChangeEvent(this);'>";
        $value = 0;
        foreach(self::getOptions() as $option) {
            $selected = ($value === $defaultOption) ? 'selected' : '';
            $disabled = ($options[$value]) ? 'disabled' : '';
            $html .= "<option value=\"$value\" $selected $disabled>$option</option>";
            $value++;
        }
        return "$html</select>";
    }
}

/**
 * DeployLocking class for creating and removing locks via memcache.
 */
final class DeployLocking{
    private $key = null;

    /**
     * Attempt to create a lock by inserting into memcache.
     * If a lock already exists, return an array with 'lock_obtained' => false,
     * else return an array with 'lock_obtained' => true, along with other data:
     * @param int $accountID ID of account to tie to lock
     * @param string $deployType Type of deploy
     * @return array
     * @throws \Exception if $accountID is not an integer or deploy lock could not be obtained
     */
    public function lockCreate($accountID, $deployType) {
        try {
            if (!is_int($accountID)) {
                throw new \Exception(Config::getMessage(ACCOUNT_ID_MUST_BE_AN_INTEGER_MSG));
            }
            $preLockData = $this->getLockData();
            if ($preLockData) {
                return array_merge($preLockData, array('lock_obtained' => false));
            }
            Api::memcache_value_set(MEMCACHE_TYPE_DEPLOY_LOCK, $this->getKey(), $this->getValue($accountID, $deployType), 0);
            $postLockData = $this->getLockData();
            if (!array_key_exists('account_id', $postLockData) || !$postLockData['account_id']) {
                throw new \Exception(Config::getMessage(ERROR_DEPLOY_LOCK_WAS_NOT_OBTAINED_LBL));
            }
            return array_merge($postLockData, array('lock_obtained' => true));
        }
        catch (\Exception $e) {
            return array('lock_obtained' => false, 'error' => $e->getMessage());
        }
    }

    /**
     * Remove deploy lock from memcache.
     * @return bool True upon success.
     * @throws \Exception If deploy lock could not be removed
     */
    public function lockRemove() {
        try {
            if ($this->getLockData()) {
                Api::memcache_value_delete(MEMCACHE_TYPE_DEPLOY_LOCK, $this->getKey());
                if ($this->getLockData()) {
                    throw new \Exception(Config::getMessage(ERROR_DEPLOY_LOCK_WAS_NOT_REMOVED_LBL));
                }
            }
            return true;
        }
        catch (\Exception $e) {
            echo "ERROR: ({$e->getMessage()})";
        }
        return false;
    }

    /**
     * Return an empty array if no lock currently exists, otherwise, return the decoded lock contents array.
     * @return array
     * @throws \Exception If value retrieved out of memcache wasn't the expected return value
     */
    public function getLockData() {
        try {
            $fetchResult = Api::memcache_value_fetch(MEMCACHE_TYPE_DEPLOY_LOCK, Api::memcache_value_deferred_get(MEMCACHE_TYPE_DEPLOY_LOCK, array($this->getKey())));
            if (!is_array($fetchResult)) {
                throw new \Exception(sprintf(Config::getMessage(ERROR_EXPECTED_AN_ARRAY_GOT_PCT_S_MSG), $fetchResult));
            }
            return ($fetchResult) ? json_decode($fetchResult[$this->getKey()], true) : array();
        }
        catch (\Exception $e) {
            return array('error' => $e->getMessage());
        }
    }

    /**
     * Append account_name, created_time_human and lock_details to $lockData.
     * @param array $lockData Current details about lock
     * @return array
     */
    public function appendLockDetails(array $lockData) {
        if($account = get_instance()->model('Account')->get($lockData['account_id'])->result){
            $lockData['account_name'] = $account->DisplayName;
        }
        $lockData['created_time_human'] = \RightNow\Utils\Framework::formatDate($lockData['created_time']);
        $lockData['lock_details'] = sprintf(Config::getMessage(LOCK_CREATION_T_PCT_S_BR_S_LOCK_LBL), $lockData['created_time_human'], $lockData['deploy_type'], $lockData['account_id'], $lockData['account_name']);
        return $lockData;
    }

    /**
     * Return '{site name}-{interface name}'
     * @return string
     */
    private function getKey() {
        if ($this->key === null) {
            $this->key = Config::getConfig(DB_NAME, 'COMMON') . '-' . Text::getSubstringBefore(\Rnow::getCfgDir(), '.cfg');
        }
        return $this->key;
    }

    private function getValue($accountID, $deployType) {
        return json_encode(array(
            'account_id' => $accountID,
            'created_time' => time(),
            'deploy_type' => $deployType,
        ));
    }
}

require_once CPCORE . 'Internal/Libraries/Cache/CachedMethods.php';

/**
 * A class for finding differences between environments (development, staging_xx, production)
 * for selected directories. Used by deployment manager UI to build up the necessary code used
 * by YUI data table.
 *
 * Valid '$source' / '$target' combinations:
 *     'development' / 'staging_xx'
 *     'staging_xx' / 'production'
 */
final class EnvironmentFileDifferences extends \RightNow\Internal\Libraries\Cache\CachedMethods {
    private $sourceBasePath = null;
    private $targetBasePath = null;
    private static $menuOptions = array(false, true, true);
    private static $defaultMenuOption = 1;
    private $postFileIDs = null;
    /**
     * Paths to exclude from showing.
     */
    private static $excludePaths = array(
        'views/Partials/[0-9]{9,}\.php',
        'helpers/[0-9]{9,}\.php',
    );

    public function __construct($source = 'development', $target = 'production', $readOnly = true) {
        parent::__construct();
        $this->source = $source;
        $this->target = $target;
        $this->readOnly = $readOnly;
        $this->validateInput();
        $this->postFileIDs = array_key_exists('fileIDs', $_POST) ? json_decode($_POST['fileIDs'], true) : array();
    }

    public function getFileTableView($filesLabel = null, $options = array()) {
        return get_instance()->load->view("Admin/deploy/filesTable", array_merge($options, array(
            'filesLabel' => ($filesLabel === null) ? sprintf(Config::getMessage(FILE_DIFFERENCES_PCT_S_AND_PCT_S_MSG), $this->sourceLabel, $this->targetLabel) : $filesLabel,
            'data' => $this->dataDefinitions,
            'columns' => $this->columnDefinitions)), true);
    }

    public static function getActionLabel($readOnly = true) {
        $label = Config::getMessage(ACTION_LBL);
        if ($readOnly !== true) {
            $label .= ":<br/>" . StagingControls::getDropDownMenuForHeader(self::$menuOptions, self::$defaultMenuOption, 'filesDataTable');
        }
        return $label;
    }

    /**
     * EnvironmentFileDifferences->mode
     * Validate source and target.
     * Returns 'developmentToStaging' or 'stagingToProduction'.
     * or throws an exception if not a valid source/target combination.
     *
     * @return string
     * @throws \Exception If source is development and target is production
     */
    protected function _getMode() {
        $source = $this->source;
        $target = $this->target;
        if ($source === 'development') {
            if ($target === 'production') {
                throw new \Exception('Comparing development to production not currently supported.');
            }
            $this->sourceBasePath = CUSTOMER_FILES;
        }
        else {
            $stagingSource = $this->stagingSource;
            $this->sourceBasePath = $stagingSource->getStagingPath();
            $source = 'staging';
        }

        if ($target === 'production') {
            $this->targetBasePath = OPTIMIZED_FILES . 'production/';
        }
        else {
            $stagingTarget = $this->stagingTarget;
            $this->targetBasePath = $stagingTarget->getStagingPath();
            $target = 'staging';
        }

        return "{$source}To" . ucfirst($target);
    }

    protected function _getStagingSource() {
        return new Staging($this->source);
    }

    protected function _getStagingTarget() {
        return new Staging($this->target);
    }

    protected function _getLabels() {
        return array(
            'development' => Config::getMessage(DEVELOPMENT_LC_LBL),
            'production' => Config::getMessage(PRODUCTION_LC_LBL),
            'staging' => Config::getMessage(STAGING_LC_LBL),
        );
    }

    protected function _getSourceLabel() {
        return $this->getTranslatedLabel($this->source);
    }

    protected function _getTargetLabel() {
        return $this->getTranslatedLabel($this->target);
    }

    /**
     * Return an array of source and target directories to compare, with a unique file identifier string as key.
     * EG. array(
     *     'f_5d8f3d1767145603eefa4cc42a60afff' =>  array(
     *         '/www/rnt/{site}/cgi-bin/{interface}.cfg/scripts/cp/generated/development/source/views/pages/answers/detail.php',
     *         '/www/rnt/{site}/cgi-bin/{interface}.cfg/scripts/cp/generated/staging/staging_01/source/views/pages/answers/detail.php'),
     *      ....
     *      )
     */
    protected function _getDirectories() {
        if ($this->mode === 'developmentToStaging') {
            return $this->stagingTarget->getStagingDirectories();
        }
        else {
            // 'stagingToProduction'
            $directories = array(
                array("{$this->stagingSource->getStagingPath()}source/", OPTIMIZED_FILES . 'production/source/'),
            );
            if (($optimizedAssetsDir = FileSystem::getOptimizedAssetsDir()) && ($stagingTimestamp = FileSystem::getLastDeployTimestampFromDir("{$this->stagingSource->getDocrootPath()}optimized"))) {
                $themes = array("{$this->stagingSource->getDocrootPath()}optimized/$stagingTimestamp/themes/", HTMLROOT . "{$optimizedAssetsDir}themes/");
                $directories[] = $themes;
                // Make sure the optimized theme directories exist
                foreach($themes as $theme) {
                    FileSystem::mkdirOrThrowExceptionOnFailure($theme, true);
                }
            }
            return $directories;
        }
    }

    protected function _getFileDifferences() {
        $differences = array();
        foreach ($this->directories as $directories) {
            $source = $directories[0];
            $target = $directories[1];
            foreach (Deployment::getFileDiffs($directories[0], $directories[1]) as $files) {
                $differences[$this->getFileIdFromPairs($files)] = $files;
            }
        }
        return $differences;
    }

    protected function _getSourceWidgetPath() {
        return ($this->mode === 'developmentToStaging') ? APPPATH . 'widgets' : $this->stagingSource->getStagingPath() . 'source/widgets';
    }

    protected function _getSourceWidgetViewPaths() {
        return FileSystem::listDirectory($this->sourceWidgetPath, true, true, array('equals', 'info.yml'));
    }

    protected function _getTargetWidgetPath() {
        return ($this->mode === 'developmentToStaging') ? $this->stagingTarget->getStagingPath() . 'source/widgets' : OPTIMIZED_FILES . 'production/source/widgets/';
    }

    protected function _getTargetWidgetViewPaths() {
        return FileSystem::listDirectory($this->targetWidgetPath, true, true, array('equals', 'info.yml'));
    }

    protected function _getWidgetViewPaths() {
        return array_merge($this->sourceWidgetViewPaths, $this->targetWidgetViewPaths);

    }

    protected function _getFileDifferencesDetails() {
        $files = array();
        $widgets = array();

        foreach($this->fileDifferences as $fileID => $paths) {
            list($sourcePath, $targetPath) = $paths;
            $existsInSource = $existsInTarget = false;
            $webDavPath = null;
            if ($sourcePath === null) {
                // File exists in target environment, but not in source, determine the source path.
                foreach ($this->directories as $pairs) {
                    list($source, $target) = $pairs;
                    if (Text::beginsWith($targetPath, $target)) {
                        $sourcePath = $source . Text::getSubstringAfter($targetPath, $target);
                        break;
                    }
                }
            }
            else {
                $existsInSource = true;
                if(!$webDavPath = $this->getWebDAVPath($sourcePath)) {
                    continue;
                }
            }

            if ($targetPath === null) {
                // File exists in source environment, but not in target, determine the target path
                foreach ($this->directories as $pairs) {
                    list($source, $target) = $pairs;
                    if (Text::beginsWith($sourcePath, $source)) {
                        $targetPath = $target . Text::getSubstringAfter($sourcePath, $source);
                        break;
                    }
                }
            }
            else {
                $existsInTarget = true;
                if($webDavPath === null && !($webDavPath = $this->getWebDAVPath($targetPath))) {
                    continue;
                }
            }

            $widgetBasePath = FileSystem::isReadableFile($sourcePath) ? $sourcePath : $targetPath;
            $isWidget = false;
            if ($widgetPath = $this->getWidgetBasePath($widgetBasePath)) {
                $nameOrVersion = basename($widgetPath);
                $widgetSourcePath = Text::getSubstringBefore($sourcePath, "/$nameOrVersion/") . "/$nameOrVersion/";
                $widgetExistsInSource = FileSystem::isReadableDirectory($widgetSourcePath);
                $widgetTargetPath = Text::getSubstringBefore($targetPath, "/$nameOrVersion/") . "/$nameOrVersion/";
                $widgetExistsInTarget = FileSystem::isReadableDirectory($widgetTargetPath);

                // Don't stage inactive widgets
                if ($this->mode === 'developmentToStaging' && $widgetExistsInSource && !$widgetExistsInTarget
                    && !Widget\Registry::isWidget(Widgets::getWidgetRelativePath($widgetPath))) {
                        continue;
                }

                // Only treat as a "widget" (i.e. displaying just the widget directory as an all-or-nothing copy) if:
                // - the entire widget is being added or removed.
                // - a widget file that exists in both source and target has changed.
                // - a widget file has been added to source.
                //
                // If a widget file that currently exists in target is removed from source, treat as an ordinary file.
                // This allows the file to simply be removed, not having to un-necessarily copy over the entire widget.
                if ((!$widgetExistsInSource && $widgetExistsInTarget) || ($widgetExistsInSource && !$widgetExistsInTarget) || (!(!$existsInSource && $existsInTarget))) {
                    if (in_array($widgetPath, $widgets)) {
                        continue;
                    }
                    $widgets[] = $widgetPath;
                    $isWidget = true;
                    $webDavPath = $this->getWebDAVPath($widgetPath);

                    $sourcePath = $widgetSourcePath;
                    $targetPath = $widgetTargetPath;
                    $existsInSource = $widgetExistsInSource;
                    $existsInTarget = $widgetExistsInTarget;
                }
            }

            $overrideOption = (array_key_exists($fileID, $this->postFileIDs) && $this->postFileIDs[$fileID] !== '') ? (int) $this->postFileIDs[$fileID] : null;
            //verify the files, if it in ignore list then disable remove from staging option
            $controls = new StagingControls($existsInSource, in_array(Text::getSubstringAfter($sourcePath, 'cp/customer/development/'), Deployer::$requiredFilesInSourceDir) ? false : $existsInTarget, true, self::$defaultMenuOption, $overrideOption);
            $this->setMenuOptions($controls->getOptionAttributes());
            $selectedOption = $controls->getSelectedOptionKey();
            if ($this->readOnly === true && $selectedOption === 0 && $this->mode !== 'stagingToProduction' || self::excludeFile($targetPath)) {
                continue; // No action
            }
            
            /*
            $actionType = null;
            $logMessage = null;
            if (!$this->isValidFileName(basename($webDavPath))) {
                $selectedOption = 0;    // 0 = no action
                $actionType = Config::getMessage(NO_ACTION_LBL);
                $message = Config::getMessage(FILE_INCOR_FMT_REM_DASH_S_TRY_AGAIN_MSG);
                $logMessage = "<br/><font style='color: #FF0000;'>$message</font>";
            } else {
                $actionType = ($this->readOnly === true) ? $controls->getSelectedOption() : $controls->getDropDownMenu($fileID, 'filesDataTable');
            }
            */
            $actionType = ($this->readOnly === true) ? $controls->getSelectedOption() : $controls->getDropDownMenu($fileID, 'filesDataTable');

            $files[] = array(
              'fileID' => $fileID,
              //Commenting out as part of 190711-000148
              //'fileName' => "<a href='/dav/$webDavPath' target='_new' title='$webDavPath'>$webDavPath</a>" . $logMessage,
              'fileName' => "<a href='/dav/$webDavPath' target='_new' title='$webDavPath'>$webDavPath</a>",
              'sourcePath' => $sourcePath,
              'targetPath' => $targetPath,
              'existsInSource' => $existsInSource ? Config::getMessage(YES_LC_LBL) : Config::getMessage(NO_LC_LBL),
              'existsInTarget' => $existsInTarget ? Config::getMessage(YES_LC_LBL) : Config::getMessage(NO_LC_LBL),
              'selectedOption' => $selectedOption,
              'isWidget' => $isWidget,
              'action' => $actionType
            );
        }
        usort($files, function($a, $b) { return strcasecmp($a['fileName'], $b['fileName']); });
        return $files;
    }

    /**
     * Check for valid file names.
     * Using hypen (-) in filename is considered invalid.
     * @param string $fn Name of file to validate
     * @return boolean $result True if valid, false otherwise
     */
    
    /*
     * Commenting out as part of 190711-000148
    private function isValidFileName($fn) {
        return (strpos($fn, '-') === false);
    }
     */

    protected function _getDataDefinitions() {
        $data = array();
        foreach($this->fileDifferencesDetails as $values) {
            array_push($data, json_encode($values));
        }
        return implode(",\n", $data);
    }

    protected function _getColumnDefinitions() {
        return implode(",\n", $this->columnDefinitionsArray);
    }

    protected function _getFileCount() {
        return count($this->fileDifferencesDetails);
    }

    protected function _getColumnDefinitionsArray() {
        $fileNameLabel = Text::escapeStringForJavaScript(Config::getMessage(FLE_NAME_LBL));
        $existsInLabel = Text::escapeStringForJavaScript(Config::getMessage(EXISTS_LESS_THAN_BR_GREATER_THAN_LBL));
        $existsInSourceLabel = $existsInLabel . Text::escapeStringForJavaScript($this->sourceLabel);
        $existsInTargetLabel = $existsInLabel . Text::escapeStringForJavaScript($this->targetLabel);
        $yesLabel = Text::escapeStringForJavaScript(Config::getMessage(YES_LC_LBL));
        $noLabel = Text::escapeStringForJavaScript(Config::getMessage(NO_LC_LBL));
        $columns = array(
            "{key: \"existsInSource\", formatter: function(o) {(o.value) ? \"$yesLabel\" : \"$noLabel\";}, label: \"$existsInSourceLabel\"}",
            "{key: \"existsInTarget\", formatter: function(o) {(o.value) ? \"$yesLabel\" : \"$noLabel\";}, label: \"$existsInTargetLabel\"}",
            "{key: \"fileName\", resizable: false, allowHTML: true, label: \"$fileNameLabel\", sortable: true}",
        );

        if ($this->mode === 'developmentToStaging') {
            $actionLabel = Text::escapeStringForJavaScript(self::getActionLabel(($this->readOnly || $this->fileCount < 2)));
            array_unshift($columns, "{key: \"action\", label: \"$actionLabel\", allowHTML: true}");
        }

        return $columns;
    }

    private function validateInput() {
        assert(is_string($this->mode));
    }

    private function getTranslatedLabel($label) {
        return $this->labels[(Text::beginsWith($label, STAGING_PREFIX) ? Text::getSubstringBefore($label, '_') : $label)];
    }

    private function getWebDAVPath($path) {
        try {
            $handler = new \RightNow\Internal\Libraries\WebDav\PathHandler($path, true);
        }
        catch(\Exception $e) {
            $handler = null;
        }

        if (!$handler || !$handler->isVisiblePath() || !$handler->fileExists()) {
            return null;
        }
        return $handler->getDavPath();
    }

    private function getWidgetBasePath($widgetPath) {
        foreach ($this->widgetViewPaths as $viewPath) {
            $absolutePath = Text::getSubstringBefore($viewPath, '/info.yml');
            if (Text::beginsWith($widgetPath, "$absolutePath/")) {
                return $absolutePath;
            }
        }
    }

    private function setMenuOptions($options) {
        $index = 0;
        foreach($options as $attributes) {
            if ($attributes['disabled'] === false) {
                self::$menuOptions[$index] = false;
            }
            $index++;
        }
    }

    /**
     * Given an array of (<sourceFilePath>, <targetFilePath>), return a string
     * that can be used as a unique ID for the file pairs, and that is safe to
     * be used as variables in a POST.
     * @param array $pairs Array of source/target file paths
     * @param string $prefix Prefix to prepend
     */
    private function getFileIdFromPairs(array $pairs, $prefix = 'f_') {
        return $prefix . md5($pairs[0] . $pairs[1]);
    }

    /**
     * Indicates whether the file with the given path should
     * be excluded from the diff.
     * @param string $path Target file path
     * @return boolean       T if the file should be omitted
     */
    private static function excludeFile($path) {
        foreach (self::$excludePaths as $toExclude) {
            if (preg_match("@$toExclude@", $path)) return true;
        }

        return false;
    }
}
