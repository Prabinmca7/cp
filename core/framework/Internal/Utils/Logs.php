<?php

namespace RightNow\Internal\Utils;

use RightNow\Internal\Libraries\LogFilter,
    RightNow\Internal\Libraries\SimpleLogger,
    RightNow\Utils\Config as ConfigExternal,
    RightNow\Utils\FileSystem as FileSystemExternal,
    RightNow\Api;

final class Logs
{
    /**
     * Return an array of key names and labels (from msgbase) used in deploy logs.
     *
     * @param string $suffix Appended to label.
     * @return array
     */
    public static function getDeployLogDataKeys($suffix=': ') {
        return array(
            'date' => ConfigExternal::getMessage(DATE_LBL) . $suffix,
            'byAccount' => ConfigExternal::getMessage(BY_ACCOUNT_LBL) . $suffix,
            'ipAddress' => ConfigExternal::getMessage(IP_ADDRESS_UC_LBL) . $suffix,
            'userAgent' => ConfigExternal::getMessage(USER_AGENT_LBL) . $suffix,
            'interfaceName' => ConfigExternal::getMessage(INTERFACE_NAME_LBL) . $suffix,
            'deployType' => ConfigExternal::getMessage(DEPLOY_TYPE_LBL) . $suffix,
            'comment' => ConfigExternal::getMessage(COMMENT_LBL) . $suffix,
        );
    }

    /**
     * Parse out deploy log information defined in getDeployLogDataKeys() above and return an array of values.
     *
     * @param string $logFile Name of log (EG. stage1234567890.log).
     * @return array
     */
    public static function getDeployLogInfo($logFile) {
        static $tags, $initialInfo;
        if (!isset($tags)) {
            $tags = array_keys(self::getDeployLogDataKeys());
            $initialInfo = array_fill_keys($tags, null);
        }
        $info = $initialInfo;

        $logPath = self::getLogPath($logFile);
        if (!self::isDeployLog($logFile) || !FileSystemExternal::isReadableFile($logPath)) {
            echo ConfigExternal::getMessage(REQUESTED_LOG_FILE_WAS_NOT_FOUND_MSG);
            return $info;
        }

        try {
            $contents = file_get_contents($logPath);
        }
        catch (Exception $e) {
            echo $e;
            return $info;
        }

        foreach ($tags as $tag) {
            $pattern = "/<rn_log_$tag>(.+?)<\/rn_log_$tag>/s";
            if (preg_match($pattern, $contents, $matches)) {
                $info[$tag] = $matches[1];
            }
        }

        return $info;
    }

    /**
     * Checks if the filename passed in is in the correct format for a deploy log file.
     * @param string $filename The name of the file to check
     * @return array Array of matches, index 0 and 1 are the file name, index 2 is the
     *         deploy type (deploy|stage|promote|rollback), and index 3 is the timestamp.
     */
    public static function isDeployLog($filename){
        if(preg_match('@(?:^|/)((deploy|promote|stage|rollback|upgradeProduction|upgradeStaging)(\d{10}(?:.\d{6})?)\.log)$@', $filename, $matches))
            return $matches;
        return false;
    }

    /**
     * Check if the filename passed in is in the correct format for a debug log file.
     * @param string $filename The name of the file to check
     * @return string The name of the file if it matched, false otherwise
     */
    public static function isDebugLog($filename){
        if(preg_match('@(?:^|/)(cp\d+\.tr)$@', $filename, $matches))
            return $matches[1];
        return false;
    }

    /**
     * Check if the filename passed in is in the correct format for a WebDAV log file.
     * @param string $filename The name of the file to check
     * @return string The name of the file if it matched, false otherwise
     */
    public static function isWebdavLog($filename){
        if(preg_match('@(?:^|/)(webdav([.]\d{6})*\.log)$@', $filename, $matches))
            return $matches[1];
        return false;
    }

    /**
     * Returns an array of information used to display webdav logs.
     *
     * @param string $logName Name of log to display
     * @return array
     */
    public static function getWebdavLogData($logName = 'webdav.log') {
        $logData = array(
            'archived' => self::getArchivedWebdavLogs(),
            'table' => array(
                'columns' => array(
                    array('key' => 'fileName', 'label' => ConfigExternal::getMessage(RNW_FILE_NAME_LBL), 'width' => '400'),
                    array('key' => 'account', 'label' => ConfigExternal::getMessage(ACCOUNT_LBL)),
                    array('key' => 'ipAddress', 'label' => ConfigExternal::getMessage(IP_ADDRESS_UC_LBL)),
                    array('key' => 'date', 'label' => ConfigExternal::getMessage(DATE_LBL)),
                    array('key' => 'action', 'label' => ConfigExternal::getMessage(ACTION_LBL)),
                    array('key' => 'interface', 'label' => ConfigExternal::getMessage(INTERFACE_NAME_LBL)),
                ),
                'data' => array(),
                'strings' => array_merge(self::getDataTableStrings(), array(
                    'summary' => ConfigExternal::getMessage(WEBDAV_LOG_FILES_SORTED_BY_DATE_LBL),
                )),
            ),
        );

        if (self::isWebdavLog($logName) && ($logPath = self::getLogPath($logName)) && FileSystemExternal::isReadableFile($logPath)) {
            $log = fopen($logPath, 'r');
            $logs = array();
            $CI = get_instance();
            for ($i = 0; $line = fgets($log); $i++) {
                $logs[$i] = explode(',', $line);
                $logs[$i][0] = htmlspecialchars_decode(str_replace('%20', ' ', $logs[$i][0]));
                $logs[$i][1] = $CI->model('Account')->get($logs[$i][1])->result->DisplayName;
                $logs[$i][4] = self::getWebDavLogDescriptionFor($logs[$i][4]);
            }
            $logData['table']['data'] = $logs;
        }

        return $logData;
    }

    /**
     * Returns an array of information used to display debug logs.
     *
     * @return array
     */
    public static function getDebugLogData() {
        $logData = array(
            'table' => array(
                'data' => array(),
                'columns' => array(
                    array('key' => 'name', 'label' => ConfigExternal::getMessage(RNW_FILE_NAME_LBL), 'sortable' => true, 'allowHTML' => true, 'width' => '10em'),
                    array('key' => 'date', 'label' => ConfigExternal::getMessage(CREATION_TIME_LBL), 'sortable' => true, 'allowHTML' => true, 'width' => '10em'),
                    array('key' => 'delete', 'label' => ConfigExternal::getMessage(DELETE_CMD), 'sortable' => false, 'allowHTML' => true, 'width' => '5em'),
                ),
                'strings' => array_merge(self::getDataTableStrings(), array(
                    'summary' => ConfigExternal::getMessage(DEBUG_LOGS_SORTED_BY_CREATION_TIME_LBL),
                    'error' => ConfigExternal::getMessage(SORRY_REQUEST_PROCESSED_MSG),
                    'loading' => ConfigExternal::getMessage(LOADING_LBL),
                    'remove' => ConfigExternal::getMessage(DELETE_CMD),
                )),
            ),
        );

        $logs = array();
        foreach(FileSystemExternal::listDirectory(self::getLogPath(), true, false, array('match', '@(?:^|/)(cp\d+\.tr)$@')) as $log) {
            if ($name = self::isDebugLog($log)) {
                $epoch = filemtime($log);
                $CI = get_instance();
                $logs[] = array(
                    'name' => $name,
                    'epoch' => $epoch,
                    'date' => \RightNow\Utils\Framework::formatDate($epoch,  $CI->cpwrapper->cpPhpWrapper->getDtfMonthDateDate()),
                );
            }
        }
        $logData['table']['data'] = $logs;

        return $logData;
    }

    /**
     * Returns an array of information used to display deploy logs.
     *
     * @return array
     */
    public static function getDeployLogData() {
        $logData = array(
            'table' => array(
                'columns' => array(
                    array('key' => 'fileName', 'label' => ConfigExternal::getMessage(RNW_FILE_NAME_LBL), 'allowHTML' => true),
                    array('key' => 'creationTime', 'label' => ConfigExternal::getMessage(CREATION_TIME_LBL)),
                    array('key' => 'deployType', 'label' => ConfigExternal::getMessage(DEPLOY_TYPE_LBL)),
                    array('key' => 'account', 'label' => ConfigExternal::getMessage(ACCOUNT_LBL)),
                    array('key' => 'comment', 'label' => ConfigExternal::getMessage(COMMENT_LBL), 'allowHTML' => true),
                ),
                'data' => array(),
                'strings' => array_merge(self::getDataTableStrings(), array(
                    'summary' => ConfigExternal::getMessage(SITE_DEPLOYMENT_LOGS_SORTED_DATE_LBL),
                )),
            ),
        );

        $logs = array();
        foreach (self::getDeployLogs() as $key => $values) {
            $logs[$key] = array_merge($values, self::getDeployLogInfo(basename($values['name'])));
            $logs[$key]['deployType'] = \RightNow\Utils\Text::removeSuffixIfExists($logs[$key]['deployType'], ' deployment');
            $logs[$key]['comment'] = htmlspecialchars(isset($logs[$key]['comment']) ? $logs[$key]['comment'] : '');
        }

        $logData['table']['data'] = $logs;

        return $logData;
    }

    /**
     * A wrapper for LogFilter class
     * @param string $logPathOrContents Path to log file or contents of log file
     * @param string $logLevel Level of log to return
     * @return array
     */
    public static function filterLog($logPathOrContents, $logLevel = 'INFO') {
        $filter = new LogFilter($logPathOrContents, $logLevel);
        return $filter->filter($logLevel);
    }

    /**
     * Used by Libraries/Deploy.php
     * @param string $logPath Path to log file
     * @param string $logLevel Level of log to display
     * @return array Log details
     */
    public static function getFilteredLogHtml($logPath, $logLevel = 'INFO') {
        assert(self::logLevelIsValid($logLevel));
        $contents = file_get_contents($logPath);

        // add a class tag to the header
        $divTag = '<div class="rn_LogHeader">';
        $delimeterPosition = strpos($contents, SimpleLogger::LOGGING_HEADER_DELIMITER);
        $lengthOfLoggingDelimiter = strlen(SimpleLogger::LOGGING_HEADER_DELIMITER);
        $contents = substr($contents, 0, $delimeterPosition) . $divTag . substr($contents, $delimeterPosition);
        $delimeterPosition = strpos($contents, SimpleLogger::LOGGING_HEADER_DELIMITER, $lengthOfLoggingDelimiter + strlen($divTag) + $delimeterPosition) + $lengthOfLoggingDelimiter;
        $contents = substr($contents, 0, $delimeterPosition) . "</div>" . substr($contents, $delimeterPosition);

        $escapeComments = function($logData) {
            return preg_replace_callback("#<rn_log_comment>(.*?)</rn_log_comment>#",
                function($matches) {
                    return '<rn_log_comment>' . str_replace('&lt;br/&gt;', '<br/>', htmlspecialchars($matches[1])) . '</rn_log_comment>';
                },
                $logData);
        };
        $entireLog = $escapeComments(implode("<br/>", self::filterLog(explode("\n", $contents), 'DEBUG')));
        $partialLog = $escapeComments(implode("<br/>", self::filterLog(explode("\n", $contents), $logLevel)));

        return array(
          'entireLog' => $entireLog,
          'partialLog' => $partialLog,
          'viewLogLabel' => ConfigExternal::getMessage(VIEW_LOG_CMD),
        );
    }

    /**
     * Used by Libraries/Logging.php
     */
    public static function getLogLevels() {
        return array(
          'DEBUG' => 0,
          'INFO'  => 1,
          'WARN'  => 2,
          'ERROR' => 3,
          'FATAL' => 4,
        );
    }

    /**
     * Used by Libraries/Logging.php
     * @param string $logLevel Level of log to validate
     */
    public static function logLevelIsValid($logLevel) {
        return (in_array($logLevel, array_keys(self::getLogLevels())));
    }

    /**
     * Returns an array of messages used by DataTable
     *
     * @return array
     */
    public static function getDataTableStrings() {
        return array(
            'ascending' => ConfigExternal::getMessage(ASCENDING_LBL),
            'descending' => ConfigExternal::getMessage(DESCENDING_LBL),
            'sortBy' => ConfigExternal::getMessage(SORT_BY_COLUMN_LBL),
            'reverseSortBy' => ConfigExternal::getMessage(REVERSE_SORT_BY_COLUMN_LBL),
            'showAll' => ConfigExternal::getMessage(SHOW_ALL_CMD),
            'empty' => ConfigExternal::getMessage(NO_RECORDS_FOUND_MSG),
            'first' => ConfigExternal::getMessage(FIRST_LBL),
            'prev' => ConfigExternal::getMessage(PREV_LBL),
            'next' => ConfigExternal::getMessage(NEXT_LBL),
            'last' => ConfigExternal::getMessage(LAST_LBL),
            'go' => ConfigExternal::getMessage(GO_CMD),
            'page' => ConfigExternal::getMessage(PAGE_LBL),
            'rows' => ConfigExternal::getMessage(ROWS_UC_LBL),
            'total' => ConfigExternal::getMessage(TOTAL_LBL),
        );
    }

    /**
     * Gets the string description for the WebDAV transaction type
     *
     * @param int $ID The ID of the transaction
     * @return string The transaction name
     */
    public static function getWebDavLogDescriptionFor($ID = null) {
        switch(intval($ID)) {
            case 0:
                return ConfigExternal::getMessage(CREATED_LBL);
            case 1:
                return ConfigExternal::getMessage(COPIED_LBL);
            case 2:
                return ConfigExternal::getMessage(MOVED_LBL);
            case 3:
                return ConfigExternal::getMessage(DELETED_LBL);
            case 4:
                return ConfigExternal::getMessage(CREATED_FOLDER_LBL);
            case 5:
                return ConfigExternal::getMessage(EDITED_LBL);
        }
    }

    /**
     * Retrieve all archived webdav logs
     *
     * @return array An array with the log file name and the formatted timestamp of when it was created
     */
    public static function getArchivedWebdavLogs() {
        $logs = array();
        foreach (FileSystemExternal::listDirectory(self::getLogPath(), false, false, array('method', 'isFile')) as $file) {
            if (self::isWebdavLog($file) && $file !== 'webdav.log') {
                $logData = array('name' => $file);
                $archiveTime = substr(str_replace('.log', '', $file), strlen('webdav.'));
                $archiveTime = mktime(0, 0, 0, substr($archiveTime, 2, 2), substr($archiveTime, 4, 2), substr($archiveTime, 0, 2));
                $logData['time'] = \RightNow\Utils\Framework::formatDate($archiveTime, 'default', null);
                $logs[] = $logData;
            }
        }
        return $logs;
    }

    /**
     * Returns an array containing arrays keyed with
     *  - name => "just the name"
     *  - time => an integer representing the timestamp encoded into the file name.
     * @return array
     */
    public static function getDeployLogs() {
        require_once CPCORE . 'Internal/Libraries/Staging.php';
        $logs = array();
        foreach(FileSystemExternal::listDirectory(self::getLogPath(), true) as $log) {
            if ($matches = self::isDeployLog($log, true)) {
                $logs[] = array(
                    'name' => $matches[1],
                    'epoch' => $matches[3],
                    'time' => \RightNow\Utils\Framework::formatDate($matches[3])
                );
            }
        }
        usort($logs, function($a, $b) {
            if ($a['epoch'] === $b['epoch']) {
                return 0;
            }
            return ($a['epoch'] > $b['epoch']) ? -1 : 1;
        });
        return $logs;
    }

    /**
     * Returns the path to the interface's log directory with optional $logName appended.
     *
     * @param string $logName Relative name of log file
     * @return string Full path to log file
     */
    public static function getLogPath($logName = '') {
        return Api::cfg_path() . "/log/$logName";
    }
}