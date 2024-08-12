<?php

use RightNow\Utils\FileSystem;

\RightNow\UnitTest\Helper::loadTestedFile(__FILE__);

class DeployTest extends CPTestCase {
    public $testingClass = 'RightNow\Controllers\Admin\Deploy';

    function testGetWidgetVersionDifferences() {
        $invoke = $this->getStaticMethod('_getWidgetVersionDifferences');
        $results = $invoke(array(), array());

        $source = array(
            'custom/input/SameVersionInSourceAndTarget' => '1.0',
            'custom/input/DifferentVersionInSourceAndTarget' => '2.0',
            'custom/input/SourceOnly' => '1.0',
        );

        $target = array(
            'custom/input/SameVersionInSourceAndTarget' => '1.0',
            'custom/input/DifferentVersionInSourceAndTarget' => '1.0',
            'custom/input/TargetOnly' => '1.0',
        );

        $expected = array(
            'source' => array(
                'custom/input/DifferentVersionInSourceAndTarget' => '2.0',
                'custom/input/SourceOnly' => '1.0',
                'custom/input/TargetOnly' => NULL),
            'destination' => array(
                'custom/input/DifferentVersionInSourceAndTarget' => '1.0',
                'custom/input/SourceOnly' => NULL,
                'custom/input/TargetOnly' => '1.0'),
        );

        $this->assertIdentical($expected, $invoke($source, $target));
    }

    function testEscapedHTMLViewsCorrectly() {
        $columnsData = array (
          1 =>
          array (
            'exists' =>
            array (
              0 => true,
              1 => true,
            ),
            'id' =>
            array (
              0 => 1,
              1 => 1,
            ),
            'item' =>
            array (
              0 => '/<iphone>/i',
              1 => '/<iphone>/i',
            ),
            'description' =>
            array (
              0 => '<iPhone>',
              1 => '<iPhone>',
            ),
            'value' =>
            array (
              0 => 'mobile',
              1 => 'mobile',
            ),
            'enabled' =>
            array (
              0 => true,
              1 => true,
            ),
            'locked' =>
            array (
              0 => true,
              1 => true,
            ),
            'selectedOption' =>
            array (
              0 => NULL,
              1 => NULL,
            ),
          )
        );
        $getPageSetData = $this->getMethod('_getPageSetData', array(false));
        $responseArray = $getPageSetData(false, $columnsData);
        $output = explode(",\n", $responseArray);
        $decodedOutput = json_decode($output[0]);
        $this->assertIdentical(\RightNow\Utils\Text::escapeHtml($columnsData[1]['item'][0]), $decodedOutput->item);
        $this->assertIdentical(\RightNow\Utils\Text::escapeHtml($columnsData[1]['description'][0]), $decodedOutput->description);
        $this->assertIdentical(\RightNow\Utils\Text::escapeHtml($columnsData[1]['value'][0]), $decodedOutput->value);
    }

    function testChangesExist() {
        $invoke = $this->getMethod('_changesExist', array(false));

        $_POST['fileIDs'] = '{"f_dfbb2532db48ccaebf514368b7975d82":1,"f_e7d24f59939079a4192a0ae21bcd3a53":2,"f_78eed0047189882da3cbb487132":0}';
        $this->assertIdentical(true, $invoke());

        $_POST['fileIDs'] = '{"f_dfbb2532db48ccaebf514368b7975d82":0,"f_e7d24f59939079a4192a0ae21bcd3a53":0,"f_78eed0047189882da3cbb487132":0}';
        $this->assertIdentical(false, $invoke());

        unset($_POST['fileIDs']);
    }

    function testGetStagingAndProductionFrameworkVersions() {
        $getStagingAndProductionFrameworkVersions = $this->getMethod('_getStagingAndProductionFrameworkVersions', array(false));

        $this->assertIdentical(array('0.0', '0.0'), $getStagingAndProductionFrameworkVersions());

        $stagingVersionFilePath = DOCROOT . '/cp/generated/staging/staging_01/optimized/frameworkVersion';
        $stagingVersionFileContents = file_get_contents($stagingVersionFilePath);
        file_put_contents($stagingVersionFilePath, '3.0');

        $this->assertIdentical(array('3.0', CP_FRAMEWORK_VERSION), $getStagingAndProductionFrameworkVersions());

        file_put_contents($stagingVersionFilePath, $stagingVersionFileContents);
    }

    function testRemoveOptimizedStagingFiles() {
        $cpStagingPath = DOCROOT . '/cp/generated/staging/staging_01';
        $eufStagingPath = DOCROOT . '/euf/application/staging/staging_01';

        $removeOptimizedStagingFiles = $this->getMethod('_removeOptimizedStagingFiles', array(false));

        $fileCountCP = count(FileSystem::listDirectory("$cpStagingPath/optimized/"));
        $this->assertTrue($fileCountCP > 1);
        if (FileSystem::isReadableDirectory("$eufStagingPath/optimized")) {
            $fileCountEuf = count(FileSystem::listDirectory("$eufStagingPath/optimized/"));
            $this->assertTrue($fileCountEuf > 1);
        }

        FileSystem::copyDirectory("$cpStagingPath/optimized", "$cpStagingPath/optimized.backupfortests");
        if (FileSystem::isReadableDirectory("$eufStagingPath/optimized"))
            FileSystem::copyDirectory("$eufStagingPath/optimized", "$eufStagingPath/optimized.backupfortests");
        $stagingVersionFilePath = "$cpStagingPath/optimized/frameworkVersion";
        $stagingVersionFileContents = file_get_contents($stagingVersionFilePath);
        file_put_contents($stagingVersionFilePath, '3.0');

        $removeOptimizedStagingFiles();
        $this->assertIdentical(1, count(FileSystem::listDirectory("$cpStagingPath/optimized/")));
        if (FileSystem::isReadableDirectory("$eufStagingPath/optimized"))
            $this->assertIdentical(0, count(FileSystem::listDirectory("$eufStagingPath/optimized/")));

        FileSystem::copyDirectory("$cpStagingPath/optimized.backupfortests", "$cpStagingPath/optimized");
        FileSystem::removeDirectory("$cpStagingPath/optimized.backupfortests", true);
        if (FileSystem::isReadableDirectory("$eufStagingPath/optimized")) {
            FileSystem::copyDirectory("$eufStagingPath/optimized.backupfortests", "$eufStagingPath/optimized");
            FileSystem::removeDirectory("$eufStagingPath/optimized.backupfortests", true);
        }
        file_put_contents($stagingVersionFilePath, $stagingVersionFileContents);
    }

    function testDoStagingAndProductionVersionsMatch() {
        $doStagingAndProductionVersionsMatch = $this->getMethod('_doStagingAndProductionVersionsMatch', array(false));

        $this->assertTrue($doStagingAndProductionVersionsMatch());

        $stagingVersionFilePath = DOCROOT . '/cp/generated/staging/staging_01/optimized/frameworkVersion';
        $stagingVersionFileContents = file_get_contents($stagingVersionFilePath);
        file_put_contents($stagingVersionFilePath, '3.0');

        $this->assertFalse($doStagingAndProductionVersionsMatch());

        file_put_contents($stagingVersionFilePath, $stagingVersionFileContents);
    }

     // @@@ QA 131023-000047
     function testGetInitialResponseArray() {
         // Check rollback message set right along with some initial & default settings
         $getInitialResponseArray = $this->getMethod('_getInitialResponseArray', array(false));
         $initialResponseArray = $getInitialResponseArray('rollback');
         $this->assertTrue(is_array($initialResponseArray));
         $this->assertIdentical($initialResponseArray['status'], 'error');
         $this->assertIdentical($initialResponseArray['statusMessage'], \RightNow\Utils\Config::getMessage(AN_ERROR_OCCURRED_DURING_ROLLBACK_LBL));
         $this->assertIdentical($initialResponseArray['errorLabel'], \RightNow\Utils\Config::getMessage(ERR_LBL));
         $this->assertNull($initialResponseArray['statusLabel']);
         $this->assertIdentical($initialResponseArray['logContents'], '');
         $this->assertNull($initialResponseArray['html']);
         $this->assertTrue(is_array($initialResponseArray['errors']));
         $this->assertTrue(is_array($initialResponseArray['links']));

         // Check if promote message set right
         $initialResponseArray = $getInitialResponseArray('promote');
         $this->assertTrue(is_array($initialResponseArray));
         $this->assertIdentical($initialResponseArray['errorLabel'], \RightNow\Utils\Config::getMessage(ERR_LBL));
         $this->assertIdentical($initialResponseArray['statusMessage'], \RightNow\Utils\Config::getMessage(AN_ERROR_OCCURRED_DURING_PROMOTE_LBL));

         // Check if staging message set right along with passed in error and status labels
         $initialResponseArray = $getInitialResponseArray('stage', 'Status Label', 'Error Label');
         $this->assertTrue(is_array($initialResponseArray));
         $this->assertIdentical($initialResponseArray['statusMessage'], \RightNow\Utils\Config::getMessage(AN_ERROR_OCCURRED_DURING_STAGING_LBL));
         $this->assertIdentical($initialResponseArray['errorLabel'], 'Error Label');
         $this->assertIdentical($initialResponseArray['statusLabel'], 'Status Label');
     }

    // @@@ QA 131105-000076
    function testCheckLogFileForSuccess() {

        $path = \RightNow\Api::cfg_path() . '/log/promote9876543210.log';
        $getInitialResponseArray = $this->getMethod('_getInitialResponseArray', array(false));
        $response = $getInitialResponseArray('promote');

        $contents =
"[INFO]  11/06/2013 01:13 PM
----------------------------------------------
CP Deployment Log
Date: <rn_log_date>11/06/2013 01:13 PM</rn_log_date>
By Account: <rn_log_byAccount>Administrator LastName - (2)</rn_log_byAccount>
IP Address: <rn_log_ipAddress>10.1.2.32</rn_log_ipAddress>
User Agent: <rn_log_userAgent>Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/535.7 (KHTML, like Gecko) Chrome/16.0.912.63 Safari/535.7</rn_log_userAgent>
Interface Name: <rn_log_interfaceName>rrio1311</rn_log_interfaceName>
Deploy Type: <rn_log_deployType>Promote Deployment</rn_log_deployType>
----------------------------------------------


[DEBUG] 11/06/2013 01:13 PM Deploy lock verified.
[DEBUG] 11/06/2013 01:13 PM Copying production/source to production/backup/source
[DEBUG] 11/06/2013 01:13 PM Copying production/optimized to production/backup/optimized
[DEBUG] 11/06/2013 01:13 PM Copying /euf/generated/optimized/1383765399 to production/backup/optimizedAssets/1383765399
[DEBUG] 11/06/2013 01:14 PM Copying staging/staging_01/source to production/source
[DEBUG] 11/06/2013 01:14 PM Copying staging/staging_01/optimized to production/optimized
[DEBUG] 11/06/2013 01:14 PM Copying /euf/generated/staging/staging_01/optimized/1383768550 to /euf/generated/optimized/1383768550
[DEBUG] 11/06/2013 01:14 PM Removing old optimized assets directory '1383765399'
[DEBUG] 11/06/2013 01:14 PM Locking Page Set Mappings
[DEBUG] 11/06/2013 01:14 PM Number of deploy errors: <cps_error_count>0</cps_error_count>
[DEBUG] 11/06/2013 01:14 PM Log File: <cps_log_file_name>/home/httpd/cgi-bin/rrio1311.cfg/log/promote1383768815.log</cps_log_file_name>
[INFO]  11/06/2013 01:14 PM Deploy Operation Successful!
[DEBUG] 11/06/2013 01:14 PM Deploy lock removed.
        ";
        file_put_contents($path, $contents);
        $this->assertTrue(\RightNow\Utils\FileSystem::isReadableFile($path));

        list(
            $class,
            $method
        ) = $this->reflect('method:checkLogFileForSuccess');

        $controller = $class->newInstanceArgs(array(false));
        $methodArgs = array($path, &$response);
        $rc = $method->invokeArgs($controller, $methodArgs);
        $this->assertTrue($rc);
        // Make sure the log contents contain some of the contents
        $this->assertTrue(\RightNow\Utils\Text::stringContains($response['logContents'], '11/06/2013 01:13 PM Copying production/source to production/backup/source'));
        $this->assertTrue(\RightNow\Utils\Text::stringContains($response['logContents'], '01:14 PM Deploy lock removed.'));
        unlink($path);

        // Test wait for failure
        $contents =
"[INFO]  11/06/2013 01:13 PM
----------------------------------------------
CP Deployment Log
Date: <rn_log_date>11/06/2013 01:13 PM</rn_log_date>
By Account: <rn_log_byAccount>Administrator LastName - (2)</rn_log_byAccount>
IP Address: <rn_log_ipAddress>10.1.2.32</rn_log_ipAddress>
User Agent: <rn_log_userAgent>Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/535.7 (KHTML, like Gecko) Chrome/16.0.912.63 Safari/535.7</rn_log_userAgent>
Interface Name: <rn_log_interfaceName>rrio1311</rn_log_interfaceName>
Deploy Type: <rn_log_deployType>Promote Deployment</rn_log_deployType>
----------------------------------------------


[DEBUG] 11/06/2013 01:13 PM Deploy lock verified.
[DEBUG] 11/06/2013 01:13 PM Copying production/source to production/backup/source
[DEBUG] 11/06/2013 01:13 PM Copying production/optimized to production/backup/optimized
[DEBUG] 11/06/2013 01:13 PM Copying /euf/generated/optimized/1383765399 to production/backup/optimizedAssets/1383765399
[DEBUG] 11/06/2013 01:14 PM Copying staging/staging_01/source to production/source
[DEBUG] 11/06/2013 01:14 PM Copying staging/staging_01/optimized to production/optimized
[DEBUG] 11/06/2013 01:14 PM Copying /euf/generated/staging/staging_01/optimized/1383768550 to /euf/generated/optimized/1383768550
[DEBUG] 11/06/2013 01:14 PM Removing old optimized assets directory '1383765399'
[DEBUG] 11/06/2013 01:14 PM Locking Page Set Mappings
[DEBUG] 11/06/2013 01:14 PM Number of deploy errors: <cps_error_count>0</cps_error_count>
[DEBUG] 11/06/2013 01:14 PM Log File: <cps_log_file_name>/home/httpd/cgi-bin/rrio1311.cfg/log/promote1383768815.log</cps_log_file_name>
        ";
        file_put_contents($path, $contents);
        $this->assertTrue(\RightNow\Utils\FileSystem::isReadableFile($path));
        $startTime = time();
        $rc = $method->invoke($controller, $path, $response);
        // @@@ QA 140109-000060
        $this->assertTrue((time() - $startTime) >= 20);
        $this->assertFalse($rc);


        unlink($path);
    }

    function testFormatSuccessResponse() {
        list(
            $class,
            $method
        ) = $this->reflect('method:formatSuccessResponse');
        $controller = $class->newInstanceArgs(array(false));
        $getInitialResponseArray = $this->getMethod('_getInitialResponseArray', array(false));

        $response = $getInitialResponseArray('stage');
        $methodArgs = array('stage', &$response);
        $method->invokeArgs($controller, $methodArgs);
        $this->assertIdentical($response['statusMessage'],  \RightNow\Utils\Config::getMessage(STAGING_COMPLETED_SUCCESSFULLY_LBL));
        $this->assertIdentical($response['links']['/ci/admin/deploy/promote'], \RightNow\Utils\Config::getMessage(PROMOTE_TO_PRODUCTION_LBL));
        $this->assertTrue(count($response['links']) === 2);

        $response = $getInitialResponseArray('promote');
        $methodArgs = array('promote', &$response);
        $method->invokeArgs($controller, $methodArgs);
        $this->assertIdentical($response['statusMessage'],  \RightNow\Utils\Config::getMessage(PROMOTE_COMPLETED_SUCCESSFULLY_LBL));
        $this->assertIdentical($response['links']['/ci/admin/overview/set_cookie/production'], \RightNow\Utils\Config::getMessage(VIEW_SITE_IN_PRODUCTION_MODE_CMD));
        $this->assertIdentical($response['links']['/ci/admin/deploy/rollback'], \RightNow\Utils\Config::getMessage(ROLLBACK_LBL));
        $this->assertTrue(count($response['links']) === 2);

        $response = $getInitialResponseArray('rollback');
        $methodArgs = array('rollback', &$response);
        $method->invokeArgs($controller, $methodArgs);
        $this->assertIdentical($response['statusMessage'],  \RightNow\Utils\Config::getMessage(ROLLBACK_COMPLETED_SUCCESSFULLY_LBL));
        $this->assertIdentical($response['links']['/ci/admin/overview/set_cookie/production'], \RightNow\Utils\Config::getMessage(VIEW_SITE_IN_PRODUCTION_MODE_CMD));
        $this->assertTrue(count($response['links']) === 1);
    }

    function testDeployStatus() {
        $path = \RightNow\Api::cfg_path() . '/log/stage9876543210.log';
        $contents =
"[INFO]  11/06/2013 01:13 PM
----------------------------------------------
CP Deployment Log
Date: <rn_log_date>11/06/2013 01:13 PM</rn_log_date>
By Account: <rn_log_byAccount>Administrator LastName - (2)</rn_log_byAccount>
IP Address: <rn_log_ipAddress>10.1.2.32</rn_log_ipAddress>
User Agent: <rn_log_userAgent>Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/535.7 (KHTML, like Gecko) Chrome/16.0.912.63 Safari/535.7</rn_log_userAgent>
Interface Name: <rn_log_interfaceName>rrio1311</rn_log_interfaceName>
Deploy Type: <rn_log_deployType>Promote Deployment</rn_log_deployType>
----------------------------------------------


[DEBUG] 11/06/2013 01:13 PM Deploy lock verified.
[DEBUG] 11/06/2013 01:13 PM Copying production/source to production/backup/source
[DEBUG] 11/06/2013 01:13 PM Copying production/optimized to production/backup/optimized
[DEBUG] 11/06/2013 01:13 PM Copying /euf/generated/optimized/1383765399 to production/backup/optimizedAssets/1383765399
[DEBUG] 11/06/2013 01:14 PM Copying staging/staging_01/source to production/source
[DEBUG] 11/06/2013 01:14 PM Copying staging/staging_01/optimized to production/optimized
[DEBUG] 11/06/2013 01:14 PM Copying /euf/generated/staging/staging_01/optimized/1383768550 to /euf/generated/optimized/1383768550
[DEBUG] 11/06/2013 01:14 PM Removing old optimized assets directory '1383765399'
[DEBUG] 11/06/2013 01:14 PM Locking Page Set Mappings
[DEBUG] 11/06/2013 01:14 PM Number of deploy errors: <cps_error_count>0</cps_error_count>
[DEBUG] 11/06/2013 01:14 PM Log File: <cps_log_file_name>/home/httpd/cgi-bin/rrio1311.cfg/log/stage9876543210.log</cps_log_file_name>
[INFO]  11/06/2013 01:14 PM Deploy Operation Successful!
[DEBUG] 11/06/2013 01:14 PM Deploy lock removed.";
        
        file_put_contents($path, $contents);
        $this->assertTrue(\RightNow\Utils\FileSystem::isReadableFile($path));

        //Request to a valid successful log file
        $postData['logPath'] = $path;
        $url = '/ci/admin/deploy/stageStatus';
        $postString = http_build_query($postData);
        $response = json_decode($this->makeRequest(
            $url,
            array('admin' => true, 'flags' => "--post-data='$postString'")
        ), true);
        $this->assertTrue($response['status'] === 'success');
        
        //Request to an invalid log file
        $postData['logPath'] = '/etc/hosts';
        $postString = http_build_query($postData);
        $response = json_decode($this->makeRequest(
            $url,
            array('admin' => true, 'flags' => "--post-data='$postString'")
        ), true);
        $this->assertTrue($response['status'] === 'error');
    }

}
