<?php
namespace RightNow\Internal\Utils;

use RightNow\Utils\FileSystem as FileSystemExternal,
    RightNow\Internal\Libraries,
    RightNow\Utils\Text as TextExternal;

final class Deployment{
    /**
     * Wrapper for Stage class - Create the specified staging environment.
     * @param string $stagingName Name of staging directory
     * @param bool $removeFirstIfExists Whether to remove directory if it exists
     * @param bool $shouldModifyPageSetConfiguration Whether to modify page sets
     * @return null
     */
    public static function createStagingDirectories($stagingName, $removeFirstIfExists = false, $shouldModifyPageSetConfiguration = true) {
        $stage = new Libraries\Staging($stagingName);
        return $stage->createStagingDirectories($removeFirstIfExists, $shouldModifyPageSetConfiguration);
    }

    /**
     * Determines if rollback of production is allowed
     * @param string $stagingName Name of staging environment
     * @return array Value of array[0] [bool] true if rollback allowed. array[1] [string] if not allowed, the reason why.
     */
    public static function isPromoteRollbackAllowed($stagingName = null) {
        if ($stagingName === null) {
            $stagingName = STAGING_PREFIX . '01';
        }

        $rollback = new Libraries\Rollback($stagingName);
        try {
            $rollback->verifyBackup();
        }
        catch (\Exception $e) {
            return array(false, $e->getMessage());
        }
        return array(true, '');
    }

    /**
     * Returns an array of pairs of files that differ between $sourceDirectory and $targetDirectory.
     * Pair[0] is the absolute path to a file in $sourceDirectory, and pair[1] is the absolute
     * path to a file in $targetDirectory. A value of null in either pair[0] or pair[1] signifies
     * the file is missing from the respective directory.
     *
     * NOTE: this could easily be moved to filesystem.php should it be used outside
     * of the deployment process.
     *
     * @param string $sourceDirectory Absolute directory path
     * @param string $targetDirectory Absolute directory path
     * @param bool $recursive Whether to diff directory recursively
     * @return array List of diffs
     */
    public static function getFileDiffs($sourceDirectory, $targetDirectory, $recursive = true) {
        $sourceDirectory = TextExternal::removeTrailingSlash($sourceDirectory);
        $targetDirectory = TextExternal::removeTrailingSlash($targetDirectory);
        $sourceDirectoryFiles = FileSystemExternal::listDirectory($sourceDirectory, false, $recursive, array('method', 'isFile'));
        $targetDirectoryFiles = FileSystemExternal::listDirectory($targetDirectory, false, $recursive, array('method', 'isFile'));
        $diffs = array();
        foreach ($sourceDirectoryFiles as $path) {
            $sourceFullPath = "$sourceDirectory/$path";
            $targetFullPath = "$targetDirectory/$path";
            if (!in_array($path, $targetDirectoryFiles)) {
                array_push($diffs, array($sourceFullPath, null));
            }
            else if (!self::fileCompare($sourceFullPath, $targetFullPath, false)) {
                array_push($diffs, array($sourceFullPath, $targetFullPath));
            }
        }

        foreach ($targetDirectoryFiles as $path) {
            if (!in_array($path, $sourceDirectoryFiles)) {
                array_push($diffs, array(null, "$targetDirectory/$path"));
            }
        }
        return $diffs;
    }

    /**
     * Compare the files named $sourceFile and $targetFile, returning true if they seem equal, false otherwise.
     * Unless shallow is given and is false, files with identical size and file type are considered to be equal.
     * TODO: consider md5 to see if more efficient.
     *       if mtime same, consider same (and filesize)
     *
     * @param string $sourceFile Absolute path to file 1.
     * @param string $targetFile Absolute path to file 2.
     * @param bool $shallow If false, only checks filetype and filesize
     * @return bool Whether files are equal
     */
    public static function fileCompare($sourceFile, $targetFile, $shallow = true) {
        if (filetype($sourceFile) !== filetype($targetFile) || filesize($sourceFile) !== filesize($targetFile)) {
            return false;
        }
        if ($shallow === true || file_get_contents($sourceFile) === file_get_contents($targetFile)) {
            return true;
        }
        return false;
    }

    /**
     * Return epoch time and log name of last .cfg/[stage|promote|rollback|upgradeDeploy|servicePackDeploy]{timestamp}.log
     * This will only look at logs containing <cps_error_count>0</cps_error_count> (IE. successful events).
     * @param string $logType Either stage|promote|rollback|upgradeDeploy|servicePackDeploy.
     * @param bool $returnFormattedDate If true, return human readable time, otherwise, return epoch time.
     * @return Mixed Array of time of last successful log and log name, otherwise null.
     */
    public static function getInfoFromLastLog($logType = 'stage', $returnFormattedDate = true) {
        static $logs; // cache
        if (!isset($logs)) {
            $logs = array();
            foreach (FileSystemExternal::listDirectory(\RightNow\Api::cfg_path() . '/log', true) as $filePath) {
                if ((preg_match("/^([a-zA-Z]+)(\d{10})(?:.\d{6})?\.log$/", basename($filePath), $matches)) &&
                    (TextExternal::stringContains(file_get_contents($filePath), '<cps_error_count>0</cps_error_count>')) &&
                    (!isset($logs[$matches[1]]) || $matches[2] > $logs[$matches[1]])) {
                    $logs[$matches[1]] = $matches[2];
                }
            }
        }
        if (isset($logs[$logType]) && $epoch = $logs[$logType]) {
            $logName = "$logType$epoch.log";
            return array(($returnFormattedDate ? \RightNow\Utils\Framework::formatDate($epoch) : $epoch), $logName);
        }
        return null;
    }

    /**
     * Return true if account has specified $permission.
     * @param string $permission One of promote|stage|edit.
     * @return bool Whether account has permission
     * @throws \Exception If permission isn't a valid option
     */
    public static function doesAccountHavePermission($permission) {
        static $permissions; // cache
        if (!isset($permissions)) {
            $CI = get_instance();
            $permissions = array(
              'promote' => ($CI->_doesAccountHavePermission(ACCESS_CP_PROMOTE, 'global')) ? true : false,
              'stage' => ($CI->_doesAccountHavePermission(ACCESS_CP_STAGE, 'global')) ? true : false,
              'edit' => ($CI->_doesAccountHavePermission(ACCESS_CP_EDIT, 'global')) ? true : false,
            );
        }
        if (!array_key_exists($permission, $permissions)) {
            throw new \Exception("Invalid permission: '$permission'");
        }
        return $permissions[$permission];
    }

    /**
     * Return an array of "events" (stage, promote, rollback) and their associated labels, privs and disabled status.
     * @param bool $checkStagePermission If true, check if account has staging privs. If not, return an empty array.
     * @return array Menu items to display
     */
    public static function getDeployMenuItems($checkStagePermission = false) {
        if ($checkStagePermission && !get_instance()->_doesAccountHavePermission(ACCESS_CP_STAGE, 'global')) {
            return array();
        }
        list($lastPromoteDate, $promoteLogName) = self::getInfoFromLastLog('promote', true);
        $hasPromotePrivs = self::doesAccountHavePermission('promote');
        $hasStagePrivs = self::doesAccountHavePermission('stage');
        $stageDisabled = (!$hasStagePrivs);
        $promoteDisabled = (!$hasPromotePrivs);
        $rollbackAllowed = false;
        $reasonRollbackNotAllowed = '';
        if ($hasPromotePrivs) {
            list($rollbackAllowed, $reasonRollbackNotAllowed) = self::isPromoteRollbackAllowed();
        }
        $rollbackDisabled = (!$hasPromotePrivs || !$lastPromoteDate || !$rollbackAllowed);
        $labels = array(
          'stage' => \RightNow\Utils\Config::getMessage(COPY_FILES_CONFIGURATIONS_CMD),
          'promote' => \RightNow\Utils\Config::getMessage(PROMOTE_STAGING_PRODUCTION_MSG),
          'rollback' => \RightNow\Utils\Config::getMessage(ROLLBACK_THE_MOST_RECENT_PROMOTE_MSG),
        );
        $getDescription = function($event, $disabled) use ($labels, $reasonRollbackNotAllowed) {
            $disabledLabel = '';
            if ($disabled === true) {
                if ($event === 'rollback' && $reasonRollbackNotAllowed) {
                    $disabledLabel = " ($reasonRollbackNotAllowed)";
                }
                else {
                    $disabledLabel = ' (' . \RightNow\Utils\Config::getMessage(DISABLED_LC_LBL) . ')';
                }
            }
            return $labels[$event] . $disabledLabel;
        };

        list($lastStageDate, $lastStageLogName) = self::getInfoFromLastLog('stage', true);
        list($lastRollbackDate, $lastRollbackLogName) = self::getInfoFromLastLog('rollback', true);
        return array(
            '/ci/admin/deploy/selectFiles' => array(
                'id' => 'stage',
                'label' => \RightNow\Utils\Config::getMessage(STAGE_LBL),
                'description' => $getDescription('stage', $stageDisabled),
                'lastRunDate' => $lastStageDate,
                'logName' => $lastStageLogName,
                'disabled' => $stageDisabled,
            ),
            '/ci/admin/deploy/promote' => array(
                'id' => 'promote',
                'label' => \RightNow\Utils\Config::getMessage(PROMOTE_LBL),
                'description' => $getDescription('promote', $promoteDisabled),
                'lastRunDate' => $lastPromoteDate,
                'logName' => $promoteLogName,
                'disabled' => $promoteDisabled,
            ),
            '/ci/admin/deploy/rollback' => array(
                'id' => 'rollback',
                'label' => \RightNow\Utils\Config::getMessage(ROLLBACK_LBL),
                'description' => $getDescription('rollback', $rollbackDisabled),
                'lastRunDate' => $lastRollbackDate,
                'logName' => $lastRollbackLogName,
                'disabled' => $rollbackDisabled,
            ),
        );
    }

    /**
     * Wrapper for DeployLocking::lockCreate()
     * Returns an associative array having a boolean 'lock_obtained' key.
     * If there is an existing lock in place, an over-ride is granted if the specified '$existingLockAccountID' and '$existingLockCreatedTime' match that lock.
     *
     * Example: array('lock_obtained' => true, 'account_id' => 12345, 'created_time' => 1283279796, 'deploy_type' => 'stage', 'message' => 'Deploy lock obtained.',
     *                'account_name' => 'Bob Dylan', 'created_time_human' => '2010-08-27 11:17 PM') // added if lock was not obtained
     *
     * @param int $accountID Value of accounts.acct_id of staff performing the deploy operation.
     * @param string $deployType Either stage|promote|rollback
     * @param int|null $existingLockAccountID Specify the account_id of an existing lock in order to either over-ride, or verify the lock belongs to that account.
     * @param string|null $existingLockCreatedTime Specify the created_time of an existing lock in order to either over-ride, or verify the lock belongs to that account.
     * @return array Info about deploy lock
     * @throws \Exception If correct parameters are not provided
     */
    public static function createDeployLock($accountID, $deployType, $existingLockAccountID = null, $existingLockCreatedTime = null) {
        if (($existingLockAccountID && !$existingLockCreatedTime) || ($existingLockCreatedTime && !$existingLockAccountID)) {
            throw new \Exception('Both existingLockAccountID and existingLockCreatedTime must be specified. Cannot specify one or the other.');
        }

        $lockObject = new Libraries\DeployLocking();

        $createLock = function() use ($accountID, $deployType, $lockObject) {
            $lockData = $lockObject->lockCreate($accountID, $deployType);
            if (isset($lockData['error']) && $lockData['error']) {
                $lockData['message'] = \RightNow\Utils\Config::getMessage(ERROR_ENC_OBTAINING_DEPLOY_LOCK_MSG) . " ({$lockData['error']})";
            }
            else {
                $lockData['message'] = ($lockData['lock_obtained'] === true) ? \RightNow\Utils\Config::getMessage(DEPLOY_LOCK_OBTAINED_MSG) : \RightNow\Utils\Config::getMessage(DEPLOY_LOCK_PROCESS_EXISTS_MSG);
            }
            return $lockData;
        };

        $lockData = $createLock();

        if (isset($lockData['error']) && $lockData['error']) {
            return $lockData;
        }

        if ($lockData['lock_obtained'] === false && $lockData['account_id'] && $lockData['account_id'] === $existingLockAccountID && $lockData['created_time'] && $lockData['created_time'] === $existingLockCreatedTime) {
            // An existing lock was in place, but as function caller knew the account_id and created_time, let them have it.
            if ($accountID === $existingLockAccountID) {
                $lockData['lock_obtained'] = true;
            }
            else {
                $lockObject->lockRemove();
                $lockData = $createLock();
            }

            if ($lockData['lock_obtained'] === true) {
                $lockData['message'] = \RightNow\Utils\Config::getMessage(DEPLOY_LOCK_VERIFIED_MSG);
            }
        }

        // Lock was not obtained, provide account name, human readable creation time, and lock details.
        if ($lockData['lock_obtained'] === false) {
            $lockData = $lockObject->appendLockDetails($lockData);
        }

        return $lockData;
    }

    /**
     * Wrapper for DeployLocking::lockRemove(). Returns an array whose first element is true if lock was successfully removed (or did not exist in the first place), else false.
     * @param int $accountID Value of accounts.acct_id of staff who currently owns the lock.
     * @return array Array in the format array[0] [bool] true upon success, array[1] [array] lockData
     */
    public static function removeDeployLock($accountID) {
        $returnStatus = false; // error
        $lockObject = new \RightNow\Internal\Libraries\DeployLocking();
        if (!$lockData = $lockObject->getLockData()) {
            // no lock present, report success.
            $returnStatus = true;
        }
        else if ($lockData['account_id'] === $accountID) {
            $returnStatus = $lockObject->lockRemove();
        }

        if ($lockData && $returnStatus === false) {
            $lockData = $lockObject->appendLockDetails($lockData);
        }

        return array($returnStatus, $lockData);
    }

    /**
     * Check to see if the deploy lock has been removed. Returns true if the lock no longer exists, false if it does. Pass in an account ID and lock
     * created time to see if a specific lock has been removed. Pass null in account ID and lock created time if you want to see if all deploy locks
     * have been removed.
     * @param int|null $accountID Specify the account_id of an existing lock in order verify the lock belongs to that account. If null, this function will not check account ID for a match
     * @param string|null $lockCreatedTime Specify the created_time of an existing lock in order verify the lock matches. If null, this function will not check the lock create time for a match
     * @return bool True if lock removed, false if lock still exists
     */
    public static function checkIfDeployLockRemoved($accountID = null, $lockCreatedTime = null) {
        $lockObject = new \RightNow\Internal\Libraries\DeployLocking();

        if (!$lockData = $lockObject->getLockData()) {
            // no lock present, report success.
            return true;
        }
        if (($accountID === null || $lockData['account_id'] === $accountID) && ($lockCreatedTime === null || $lockData['created_time'] === $lockCreatedTime)) {
            return false;
        }

        return true;
    }

    /**
     * Check to see if the deploy lock has been removed while waiting a specified amount of time.
     * @param int|null $accountID Specify the account_id of an existing lock in order verify the lock belongs to that account. If null, this function will not check account ID for a match
     * @param string|null $lockCreatedTime Specify the created_time of an existing lock in order verify the lock matches. If null, this function will not check the lock create time for a match
     * @param int $waitLength Specify the number of seconds to wait for the deploy lock to be removed
     * @param int $sleepInterval Specify the number of seconds to sleep between checking the memcached lock again
     * @return bool True if lock removed, false for lock exists after wait limit
     */
    public static function checkIfDeployLockRemovedWithWait($accountID = null, $lockCreatedTime = null, $waitLength = 180, $sleepInterval = 5) {
        $waitTime = time() + $waitLength;
        do {
            if (self::checkIfDeployLockRemoved($accountID, $lockCreatedTime)) {
                return true;
            }
            sleep($sleepInterval);
        } while ($waitTime > time());

        return false;
    }

    public static function getCPErrorCountTags($errorCount) {
        return "<cps_error_count>$errorCount</cps_error_count>";
    }

    public static function getCPLogFileNameTags($logFileName) {
        return "<cps_log_file_name>$logFileName</cps_log_file_name>";
    }

    public static function getAccountInformation($account) {
        if ($account) {
            // @codingStandardsIgnoreStart
            $name = (\RightNow\Utils\Config::getConfig(intl_nameorder, 'COMMON')) ? "{$account->lname} {$account->fname}" : "{$account->fname} {$account->lname}";
            // @codingStandardsIgnoreEnd
            return "$name - ({$account->acct_id})\n";
        }
        return '';
    }

    /**
     * Record widget and framework version changes during deploy operations.
     * @param string|null $fromPath The path to the version files indicating the previous versions.
     * @param string|null $toPath The path to the version files indicating the new versions.
     * @param string $mode One of 'development', 'staging' or 'production'
     * @param array $targets An array of targets where version changes are to be recorded.
     */
    public static function recordVersionChanges($fromPath, $toPath, $mode, array $targets = array('acs', 'db')) {
        require_once CPCORE . 'Internal/Utils/VersionTracking.php';
        if ($fromPath && $toPath && !VersionTracking::versionsPopulatedForMode($mode)) {
            // There are no cp_object version entries for the specified $mode, so force intial population.
            $fromPath = null;
        }
        VersionTracking::recordVersionChanges($fromPath, $toPath, $mode, $targets);
    }
}
