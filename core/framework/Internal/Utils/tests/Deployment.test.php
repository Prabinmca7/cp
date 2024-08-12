<?php
RightNow\UnitTest\Helper::loadTestedFile(__FILE__);
use RightNow\Internal\Utils\Deployment;

class DeploymentTest extends CPTestCase {
    function testGetAccountInformation(){
        $account = (object)array('fname' => "First",
                                 'lname' => "Last",
                                 'acct_id' => 12);
        $this->assertEqual(Deployment::getAccountInformation($account), "First Last - (12)\n");
    }

    function testGetCPLogFileNameTags(){
        $this->assertEqual(Deployment::getCPLogFileNameTags("test log file name"), "<cps_log_file_name>test log file name</cps_log_file_name>");
    }

    function testGetCPErrorCountTags(){
        $this->assertEqual(Deployment::getCPErrorCountTags("test error count"), "<cps_error_count>test error count</cps_error_count>");
    }
}