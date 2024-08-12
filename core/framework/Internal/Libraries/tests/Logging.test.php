<?php
RightNow\UnitTest\Helper::loadTestedFile(__FILE__);
class SimpleLoggerTest extends CPTestCase
{
    function __construct() {
        $this->base_work_dir = sprintf("%s/unitTest/%s", get_cfg_var('upload_tmp_dir'), get_class($this));
        umask(0);
        \RightNow\Utils\FileSystem::mkdirOrThrowExceptionOnFailure($this->base_work_dir, true);
    }

    function testLogger() {
        $logPath = "$this->base_work_dir/log.log";
        if (is_file($logPath)) {
            unlink($logPath);
        }
        $log = new \RightNow\Internal\Libraries\SimpleLogger($logPath);
        $log->debug("DEBUG"); 
        $log->info("INFO"); 
        $log->warn("WARN"); 
        $log->error("ERROR"); 
        $log->fatal("FATAL"); 
        //printf("<pre>%s</pre>", file_get_contents($logPath));
        
        //PLACEHOLDER for further tests...
    }

    function testFilter() {
        $logPath = "$this->base_work_dir/log.log";
        $filter = new \RightNow\Internal\Libraries\LogFilter($logPath);
        $contentsArray = $filter->filter('ERROR');
        $contentsString = join("\n", $contentsArray);
        //PLACEHOLDER for further tests...
    }
}
