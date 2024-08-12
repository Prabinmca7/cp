<?php

namespace RightNow\Controllers\Admin;

use RightNow\Utils\FileSystem,
    RightNow\Utils\Framework,
    RightNow\Utils\Config,
    RightNow\Api,
    RightNow\Internal\Utils\Logs as LogsUtil;

require_once CPCORE . 'Internal/Utils/Deployment.php';
require_once CPCORE . 'Internal/Utils/Logs.php';

class Logs extends Base
{
    function __construct()
    {
        parent::__construct(true, '_verifyLoginWithCPEditPermission', array(
            'adminDeployList' => '_verifyLoginBySessionIdAndRequireCPPromote',
            'adminDeployDetail' => '_verifyLoginBySessionIdAndRequireCPPromote',
            ));
    }

    function index()
    {
        $this->_render('logs/index', array(), Config::getMessage(LOGS_LBL));
    }

    /**
     * Displays all of the logs for webDAV transactions
     * @param string $logName Specific log to load
     */
    function webdav($logName='webdav.log')
    {
        $logData = LogsUtil::getWebdavLogData($logName);
        if (count($logData['table']['data']) > 1) {
            $logData['searchControls'] = $this->load->view('Admin/logs/search', array('columns' => $logData['table']['columns']), true);
            $logData['table']['searchForm'] = '#search';
        }

        $this->_render('logs/webdav', $logData, Config::getMessage(WEBDAV_LOGS_LBL), array(
            'js' => 'logs/webdav',
            'css' => 'logs/logs',
        ));
    }

    /**
     * Displays all the files for debugging logs
     */
    function debug()
    {
        $this->_render('logs/debugList', LogsUtil::getDebugLogData(), Config::getMessage(DEBUG_LOGS_LBL), array(
            'js' => 'logs/debugList',
            'css' => 'logs/logs',
        ));
    }

    /**
     * Views a specific debug log
     *
     * @param string $logFile The name of the log file
     * @param bool $usePlainTemplate Denotes if the log should be displayed with any chrome styling around it
     */
    function viewDebugLog($logFile=false, $usePlainTemplate=false)
    {
        $logInfo = array();
        $logFilePrefix = Api::cfg_path() . "/log/";
        if(!$logFile)
        {
            $logs = FileSystem::listDirectory($logFilePrefix, false, false, array('match', '@(?:^|/)(cp\d+\.tr)$@'));
            $latestLogTime = 0;
            foreach($logs as $log)
            {
                if(LogsUtil::isDebugLog($log))
                {
                    if(($currentLogTime = filemtime($logFilePrefix . $log)) > $latestLogTime)
                    {
                        $latestLogTime = $currentLogTime;
                        $logFile = $log;
                    }
                }
            }
        }

        if(LogsUtil::isDebugLog($logFile))
        {
            $path = $logFilePrefix . $logFile;
            if(FileSystem::isReadableFile($path)){
                $logInfo['logContent'] = str_replace("\n", "<br/>", file_get_contents($path));
            }
        }
        $this->_render('logs/debugDetails', $logInfo, $logFile, array(), ($usePlainTemplate) ? 'plainTemplate' : 'template');
    }

    /**
     * Deletes a debug log
     */
    function deleteDebugLog()
    {
        $logFile = $this->input->post('logName');
        if(LogsUtil::isDebugLog($logFile)) {
            $path = Api::cfg_path() . "/log/$logFile";
            if(FileSystem::isReadableFile($path)) {
                $result = unlink($path);
            }
            else {
                $result = true;
            }

            if(method_exists($this, 'renderJSON')) {
                $this->renderJSON(array('result' => $result));
            }
            else {
                $this->_renderJSONAndExit(array('result' => $result));
            }
        }
    }

    /**
     * Delete all debug log files in the logs directory
     */
    function deleteAllDebugLogs()
    {
        $logDirectoryFiles = FileSystem::listDirectory(Api::cfg_path() . "/log/", true, false, array('match', '@(?:^|/)(cp\d+\.tr)$@'));
        foreach($logDirectoryFiles as $logFile)
        {
            if(FileSystem::isReadableFile($logFile))
                @unlink($logFile);
        }
        Framework::setLocationHeader('/ci/admin/logs/debug');
        exit;
    }

    /**
     * Displays all of the site deployment logs
     * @param bool $usePlainTemplate Denotes if the log should be displayed with any chrome styling around it
     */
    function deploy($usePlainTemplate=false)
    {
        $logData = LogsUtil::getDeployLogData();
        $logData['usePlainTemplate'] = $usePlainTemplate;

        if (count($logData['table']['data']) > 1) {
            $logData['searchControls'] = $this->load->view('Admin/logs/search', array('columns' => $logData['table']['columns']), true);
            $logData['table']['searchForm'] = '#search';
        }

        $this->_render('logs/deployList', $logData, Config::getMessage(DEPLOY_LOGS_LBL), array(
            'js' => 'logs/deployList',
            'css' => 'logs/logs',
        ), ($usePlainTemplate) ? "plainTemplate" : "template");
    }

    /**
     * Displays the contents of a specific deployment log
     *
     * @param string $logFile The name of the log file
     * @param bool $usePlainTemplate Denotes if the log should be displayed with any chrome styling around it
     * @param string $logLevel One of 'DEBUG|INFO|WARN|ERROR|FATAL'
     */
    function viewDeployLog($logFile=false, $usePlainTemplate=false, $logLevel = 'INFO') {
        require_once CPCORE . 'Internal/Libraries/Logging.php';
        $path = Api::cfg_path() . "/log/$logFile";
        if (!LogsUtil::isDeployLog($logFile) || !FileSystem::isReadableFile($path)) {
            exit(Config::getMessage(REQUESTED_LOG_FILE_WAS_NOT_FOUND_MSG));
        }
        if (!LogsUtil::logLevelIsValid($logLevel)) {
            $logLevel = 'INFO';
        }
        $plainTemplateParameter = '0';
        $template = 'template';
        // M&C pages pass in '1' here
        if ($usePlainTemplate === '1' || $usePlainTemplate === true) {
            $plainTemplateParameter = '1';
            $template = 'plainTemplate';
        }

        $logData = LogsUtil::getFilteredLogHtml($path, $logLevel);
        $logHtml = ($logLevel === 'DEBUG' || $logData['entireLog'] === $logData['partialLog'])
            ? "<pre>{$logData['entireLog']}</pre>"
            : $this->load->view('Admin/logs/filteredLog', $logData + array(
                'viewEntireLogMsg' => Config::getMessage(VIEW_ENTIRE_LOG_CMD),
                'viewPartialLogMsg' => Config::getMessage(VIEW_PARTIAL_LOG_CMD),
              ), true);

        $this->_render('logs/deployDetails', array(
            'logContent' => $logHtml,
            'backToLogUrl' => "/ci/admin/logs/deploy/$plainTemplateParameter",
            ), $logFile, array(
            'css' => 'logs/logs'
        ), $template);
    }
}
