<?php

RightNow\UnitTest\Helper::loadTestedFile(__FILE__);

use \RightNow\Utils\Text;

class LogsControllerTest extends CPTestCase
{
    public $testingClass = 'RightNow\Controllers\Admin\Logs';

    function testViewDebugLog()
    {
        $logFile1 = \RightNow\Api::cfg_path() . '/log/cp1234.tr';
        file_put_contents($logFile1, "this is logFile1");
        // sleep in order to guarantee modified time order
        sleep(1);
        $logFile2 = \RightNow\Api::cfg_path() . '/log/cp5678.tr';
        file_put_contents($logFile2, "this is logFile2");

        $results = $this->makeRequest('/ci/admin/logs/viewDebugLog', array('admin' => true));
        $this->assertStringDoesNotContain($results, 'this is logFile1');
        $this->assertStringContains($results, 'this is logFile2');

        $results = $this->makeRequest('/ci/admin/logs/viewDebugLog/cp1234.tr', array('admin' => true));
        $this->assertStringContains($results, 'this is logFile1');
        $this->assertStringDoesNotContain($results, 'this is logFile2');

        $results = $this->makeRequest('/ci/admin/logs/viewDebugLog/cp5678.tr', array('admin' => true));
        $this->assertStringDoesNotContain($results, 'this is logFile1');
        $this->assertStringContains($results, 'this is logFile2');

        @unlink($logFile1);
        @unlink($logFile2);
    }

    function testDeleteDebugLog()
    {
        $logFile = \RightNow\Api::cfg_path() . '/log/cp1234.tr';
        file_put_contents($logFile, "this is logFile");
        $this->assertIdentical(true, file_exists($logFile));

        $result = $this->makeRequest('/ci/admin/logs/deleteDebugLog', array('admin' => true, 'flags' => " --post-data='logName=cp1234.tr'"));

        $this->assertIdentical('{"result":true}', $result);
        $this->assertIdentical(false, file_exists($logFile));
    }
}
