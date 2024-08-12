<?php

use RightNow\Utils\FileSystem,
    RightNow\Internal\Utils\Deployment;

RightNow\UnitTest\Helper::loadTestedFile(__FILE__);

require_once(CPCORE . 'Internal/Utils/Deployment.php');

//TODO: create staging feature tests

class StagingTest extends CPTestCase {
    public $testingClass = 'RightNow\Utils\FileSystem';
    function __construct() {
        $this->base_work_dir = sprintf("%s/unitTest/%s", get_cfg_var('upload_tmp_dir'), get_class($this));
        umask(0);
        FileSystem::mkdirOrThrowExceptionOnFailure($this->base_work_dir, true);
        $this->baseDir = "$this->base_work_dir/dir1/";
        $this->baseDir2 = "$this->base_work_dir/dir1Copy/";
        $this->CI = get_instance();
        $this->accountIds = array();
        foreach (range(1, 10) as $accountID) {
            if ($account = $this->CI->model('Account')->get($accountID)) {
                $this->accountIds[] = $accountID;
            }
        }
    }

    function getFiles() {
        return array (
          'config/mapping.php',
          'Controllers/ajaxCustom.php',
          'errors/error_general.php',
          'helpers/sample_helper.php',
          'javascript/autoload.js',
          'libraries/SampleLibrary.php',
          'models/custom/sample_model.php',
          'views/admin/answer_full_preview.php',
          'views/pages/ask.php',
          'views/templates/agent.php',
          'widgets/custom/PreviousAnswersCustom/view.php',
        );
    }

    function getConfigurations() {
        return array ();
    }

    function setUpForFileDiffs() {
        $cwd = getcwd();
        $baseDir = $this->baseDir;
        FileSystem::removeDirectory($baseDir, true);
        FileSystem::removeDirectory($this->baseDir2, true);
        mkdir($baseDir);
        foreach(range(1, 3) as $level) {
            chdir($baseDir);
            file_put_contents("file$level.html", "file$level.html", FILE_APPEND);
            file_put_contents("_file$level.html", "file$level.html", FILE_APPEND);
            file_put_contents("file$level.php", "file$level.php", FILE_APPEND);
            $nextDir = sprintf('dir%d/', $level+1);
            mkdir($nextDir);
            $baseDir .= $nextDir;
        }
        FileSystem::copyDirectory($this->baseDir, $this->baseDir2);
        chdir($cwd);
    }

    function testGetFileDiffs2() {
        $this->setUpForFileDiffs();
        $dir1 = CUSTOMER_FILES . 'widgets/custom';
        $dir2 = OPTIMIZED_FILES . 'production/source/widgets/custom';
        $recursive = false;
        $recursive = true;
        $actual = Deployment::getFileDiffs($dir1, $dir2, $recursive);
    }

    function testFileCompare() {
        $this->setUpForFileDiffs();
        $file1 = $this->baseDir . 'file1.html';
        $file2 = $this->baseDir . '_file1.html';
        $file3 = $this->baseDir . 'file1.php';
        $this->assertTrue(Deployment::fileCompare($file1, $file2, true));
        $this->assertTrue(Deployment::fileCompare($file1, $file2, false));
        $this->assertFalse(Deployment::fileCompare($file1, $file3, true));
        $this->assertFalse(Deployment::fileCompare($file1, $file3, false));
    }

    function testStageConstructor() {
        $files = array();
        $configs = array();
        $stage = new \RightNow\Internal\Libraries\Stage('staging_01', array('lockCreatedTime' => $lockCreatedTime, 'files' => $files, 'configurations' => $configs));
    }

    function testLogPath() {
        $stage = new \RightNow\Internal\Libraries\Stage('staging_01');
        $this->assertSame(1, preg_match('@^' . \RightNow\Api::cfg_path() . "/log/stage(\d){10}[.]log$@", $stage->getLogPath()));
    }

    function testDoesAccountHavePermission() {
        $this->assertIsA(Deployment::doesAccountHavePermission('promote'), boolean);
        $this->assertIsA(Deployment::doesAccountHavePermission('stage'), boolean);
        $this->assertIsA(Deployment::doesAccountHavePermission('edit'), boolean);
        try {
            Deployment::doesAccountHavePermission('deity');
        }
        catch (\Exception $e) {
            $error = $e->getMessage();
        }
        $this->assertEqual("Invalid permission: 'deity'", $error);
    }

    function testGetInfoFromLastLog() {
        $path = \RightNow\Api::cfg_path() . '/log';
        $logType = 'testStage';
        $cleanup = function() use ($path, $logType) {
            foreach (glob("$path/{$logType}*.log") as $path) {
                unlink($path);
            }
        };
        $cleanup();
        $now = time();
        $future = $now+1;
        $past = $now-1;
        $contents = "blah blah blah\n<cps_error_count>0</cps_error_count>\nblah blah blah";
        $contentsFailed = "blah blah blah\n<cps_error_count>1</cps_error_count>\nblah blah blah";
        file_put_contents("$path/{$logType}{$now}.log", $contents);
        file_put_contents("$path/{$logType}{$past}.log", $contents);
        file_put_contents("$path/{$logType}{$future}.log", $contentsFailed);
        list($actual) = Deployment::getInfoFromLastLog($logType, false);
        list($actualFormatted) = Deployment::getInfoFromLastLog($logType);
        $cleanup();
        $this->assertEqual($now, $actual);
        $this->assertEqual(strftime('%m/%d/%Y %I:%M %p', $now), $actualFormatted);
        $this->assertNull(Deployment::getInfoFromLastLog('someReallyUnlikelyLogName'));
    }

    function testCreateDeployLock() {
        $l = new \RightNow\Internal\Libraries\DeployLocking();
        $this->assertTrue($l->lockRemove());

        $deployType = 'stage';
        $firstAccountID = $this->accountIds[0];
        $secondAccountID = $this->accountIds[1];

        // Ensure we can obtain a lock when there is not an existing one.
        $lockData = Deployment::createDeployLock($firstAccountID, $deployType);
        $this->assertTrue($lockData['lock_obtained']);
        $this->assertEqual($lockData['account_id'], $firstAccountID);
        $firstLockCreatedTime = $lockData['created_time'];
        $this->assertIsA($firstLockCreatedTime, integer);

        // Ensure a subsequent call retains the first lock
        sleep(1); // so epoch time would differ.
        $lockData = Deployment::createDeployLock($firstAccountID, $deployType);
        $this->assertFalse($lockData['lock_obtained']);
        $this->assertEqual($lockData['account_id'], $firstAccountID);
        $this->assertEqual($lockData['created_time'], $firstLockCreatedTime);

        // Ensure we can keep an existing lock if we are the owner and can verify the creation time.
        // This happens in the Deployer when the lock was previously obtained in the UI.
        sleep(1); // so epoch time would differ.
        $lockData = Deployment::createDeployLock($firstAccountID, $deployType, $firstAccountID, $firstLockCreatedTime);
        $this->assertTrue($lockData['lock_obtained']);
        $this->assertEqual($lockData['account_id'], $firstAccountID);
        $this->assertEqual($lockData['created_time'], $firstLockCreatedTime);

        // @@@ 130725-000115
        $this->assertFalse(Deployment::checkIfDeployLockRemoved($firstAccountID, $firstLockCreatedTime));
        $this->assertFalse(Deployment::checkIfDeployLockRemoved(null, null));
        $this->assertFalse(Deployment::checkIfDeployLockRemoved($firstAccountID, null));
        $this->assertFalse(Deployment::checkIfDeployLockRemoved(null, $firstLockCreatedTime));
        $this->assertTrue(Deployment::checkIfDeployLockRemoved((string)$firstAccountID + 2, $firstLockCreatedTime));
        $this->assertTrue(Deployment::checkIfDeployLockRemoved($firstAccountID, (string)$firstLockCreatedTime + 2));

        // Make sure wait time is working
        $waitStartTime = time();
        $this->assertFalse(Deployment::checkIfDeployLockRemovedWithWait($firstAccountID, $firstLockCreatedTime, 5, 1));
        $this->assertTrue(time() >= $waitStartTime + 5);
        $waitStartTime = time();
        $this->assertFalse(Deployment::checkIfDeployLockRemovedWithWait(null, null, 2, 1));
        $this->assertTrue(time() >= $waitStartTime + 2);
        $this->assertTrue(Deployment::checkIfDeployLockRemovedWithWait((string)$firstAccountID + 2, $firstLockCreatedTime, 5, 1));
        $this->assertTrue(Deployment::checkIfDeployLockRemovedWithWait($firstAccountID, (string)$firstLockCreatedTime + 2, 5, 1));
        // Make sure the test is done at least once when wait time is 0
        $this->assertTrue(Deployment::checkIfDeployLockRemovedWithWait($firstAccountID, (string)$firstLockCreatedTime + 2, 0));

        // Ensure we can over-ride keep an existing lock if we can verify the creation time and the lock owner's account_id.
        if ($secondAccountID !== null) {
            sleep(1); // so epoch time differs.
            $lockData = Deployment::createDeployLock($secondAccountID, $deployType, $firstAccountID, $firstLockCreatedTime);
            $this->assertTrue($lockData['lock_obtained']);
            $this->assertEqual($lockData['account_id'], $secondAccountID);
            $secondLockCreatedTime = $lockData['created_time'];
            $this->assertNotEqual($secondLockCreatedTime, $firstLockCreatedTime);

            // Ensure we are NOT allowed to over-ride an existing lock if we CANNOT verify the creation time and the lock owner's account_id.
            $lockData = Deployment::createDeployLock($firstAccountID, $deployType, 999, $secondLockCreatedTime);
            $this->assertFalse($lockData['lock_obtained']);

            $lockData = Deployment::createDeployLock($secondAccountID, $deployType, $secondAccountID, 1234567);
            $this->assertFalse($lockData['lock_obtained']);
        }
    }

    function testDeployLocking() {
        $deployType = 'stage';
        $l = new \RightNow\Internal\Libraries\DeployLocking();

        $this->assertTrue($l->lockRemove());

        $this->assertEqual(array(), $l->getLockData());

        $createResult = $l->lockCreate($this->accountIds[0], $deployType);
        $this->assertTrue($createResult['lock_obtained']);

        $lockData = $l->getLockData();
        $this->assertEqual($deployType, $lockData['deploy_type']);

        $createResult2 = $l->lockCreate($this->accountIds[0], $deployType);
        $this->assertFalse($createResult2['lock_obtained']);

        $this->assertTrue($l->lockRemove());

        $this->assertEqual(array(), $l->getLockData());
    }

    function testStagingControls() {
        $this->assertEqual("<label for='selectAll' class='screenreader'>Select Action For All</label><select id='selectAll' class='className' onChange='dropDownChangeEvent(this);'><option value=\"0\" selected >No action</option><option value=\"1\"  >Copy to staging</option><option value=\"2\"  >Remove from staging</option></select>", \RightNow\Internal\Libraries\StagingControls::getDropDownMenuForHeader(array(false, false, false), 0, 'className'));
        $this->assertEqual("<label for='selectAll' class='screenreader'>Select Action For All</label><select id='selectAll' class='someTable' onChange='dropDownChangeEvent(this);'><option value=\"0\"  >No action</option><option value=\"1\" selected >Copy to staging</option><option value=\"2\"  disabled>Remove from staging</option></select>", \RightNow\Internal\Libraries\StagingControls::getDropDownMenuForHeader(array(false, false, true), 1, 'someTable'));
    }

    function testEnvironmentFileDifferences() {
        // Test valid source/target combinations
        $modes = array(
            'developmentToStaging' => array('development', 'staging_01'),
            'stagingToProduction' => array('staging_01', 'production'),
        );

        foreach($modes as $mode => $pairs) {
            list($source, $target) = $pairs;
            $fileDiffObject = new \RightNow\Internal\Libraries\EnvironmentFileDifferences($source, $target);
            $this->assertEqual($mode, $fileDiffObject->mode);
        }

        // Test invalid source/target combination
        $invalidModes = array(
            array('development', 'production'),
            array('production', 'development'),
            array('development', 'staging_00'),
        );

        foreach($invalidModes as $pairs) {
            list($source, $target) = $pairs;
            try {
                $badness = new \RightNow\Internal\Libraries\EnvironmentFileDifferences($source, $target);
                $this->assertNull("Where's the earth-shattering kaboom?");
            }
            catch (\Exception $e) {
                $this->assertEqual(null, $badness);
            }
        }

        $reflectionProperty = new \ReflectionProperty('\RightNow\Internal\Libraries\EnvironmentFileDifferences', 'menuOptions');
        $reflectionProperty->setAccessible(true);

        $reflectionProperty->setValue(array(false, false, false));
        $this->assertEqual("Action:<br/><label for='selectAll' class='screenreader'>Select Action For All</label><select id='selectAll' class='filesDataTable' onChange='dropDownChangeEvent(this);'><option value=\"0\"  >No action</option><option value=\"1\" selected >Copy to staging</option><option value=\"2\"  >Remove from staging</option></select>", \RightNow\Internal\Libraries\EnvironmentFileDifferences::getActionLabel(false));

        $reflectionProperty->setValue(array(false, false, true));
        $this->assertEqual("Action:<br/><label for='selectAll' class='screenreader'>Select Action For All</label><select id='selectAll' class='filesDataTable' onChange='dropDownChangeEvent(this);'><option value=\"0\"  >No action</option><option value=\"1\" selected >Copy to staging</option><option value=\"2\"  disabled>Remove from staging</option></select>", \RightNow\Internal\Libraries\EnvironmentFileDifferences::getActionLabel(false));

        $this->assertEqual('Action', \RightNow\Internal\Libraries\EnvironmentFileDifferences::getActionLabel(true));

        $fileDiffObject = new \RightNow\Internal\Libraries\EnvironmentFileDifferences('development', 'staging_01');
        $samplePath = CUSTOMER_FILES . 'widgets/custom/sample/SampleWidget/1.0/info.yml';
        $paths = $fileDiffObject->sourceWidgetViewPaths;
        $this->assertTrue(in_array($samplePath, $paths));
        $paths = $fileDiffObject->targetWidgetViewPaths;
        $samplePath = DOCROOT . '/cp/generated/staging/staging_01/source/widgets/custom/sample/SampleWidget/1.0/info.yml';
        $this->assertTrue(in_array($samplePath, $paths));
    }

    function testRemoveFileAndPruneEmptyDirectories() {
        $baseDirectory = "{$this->base_work_dir}/testRemoveFileAndPruneEmptyDirectories/";
        $sourceBaseDirectory = "{$baseDirectory}development/source/";
        $targetBaseDirectory = "{$baseDirectory}staging/source/";
        $targetPath = 'views/pages/answers/list.php';

        $setup = function() use ($baseDirectory, $sourceBaseDirectory, $targetBaseDirectory, $targetPath) {
            if (FileSystem::isReadableDirectory($baseDirectory)) {
                FileSystem::removeDirectory($baseDirectory, true);
            }
            foreach (array($sourceBaseDirectory, $targetBaseDirectory) as $path) {
                $filePath = "{$path}$targetPath";
                mkdir(dirname($filePath), 0777, true);
                touch($filePath);
            }
        };

        $sourceFilePath = "{$sourceBaseDirectory}$targetPath";
        $targetFilePath = "{$targetBaseDirectory}$targetPath";

        // Remove last file from directory. Should not prune as directory exists in source
        $setup();
        $messages = \RightNow\Internal\Libraries\Staging::removeFileAndPruneEmptyDirectories($sourceBaseDirectory, $targetBaseDirectory, $targetPath);
        $this->assertEqual(1, count($messages));
        $this->assertFalse(FileSystem::isReadableFile($targetFilePath));
        $this->assertTrue(FileSystem::isReadableDirectory(dirname($targetFilePath)));

        // Remove last file from directory. Should prune back to $targetBaseDirectory as source directories do not exist.
        $setup();
        FileSystem::removeDirectory($sourceBaseDirectory, false);
        $messages = \RightNow\Internal\Libraries\Staging::removeFileAndPruneEmptyDirectories($sourceBaseDirectory, $targetBaseDirectory, $targetPath);
        $this->assertEqual(4, count($messages));
        $this->assertFalse(FileSystem::isReadableFile($targetFilePath));
        $this->assertFalse(FileSystem::isReadableDirectory(dirname($targetFilePath)));
        $this->assertTrue(FileSystem::isReadableDirectory($targetBaseDirectory));

        // Attempt to remove non-empty directory. Exception should be thrown
        $setup();
        try {
            $boom = \RightNow\Internal\Libraries\Staging::removeFileAndPruneEmptyDirectories($sourceBaseDirectory, $targetBaseDirectory, dirname($targetPath));
            $this->assertFalse(isset($boom), 'exception not thrown');
        }
        catch (\Exception $e) {
            // expected
        }

        // Invalid source directory. Exception should be thrown
        $setup();
        try {
            $boom = \RightNow\Internal\Libraries\Staging::removeFileAndPruneEmptyDirectories('/some/invalid/directory', $targetBaseDirectory, $targetPath);
            $this->assertFalse(isset($boom), 'exception not thrown');
        }
        catch (\Exception $e) {
            // expected
        }

        // Invalid target directory. Exception should be thrown
        $setup();
        try {
            $boom = \RightNow\Internal\Libraries\Staging::removeFileAndPruneEmptyDirectories($sourceBaseDirectory, '/some/invalid/directory', $targetPath);
            $this->assertFalse(isset($boom), 'exception not thrown');
        }
        catch (\Exception $e) {
            // expected
        }

        // Invalid target. Exception should be thrown
        $setup();
        try {
            $boom = \RightNow\Internal\Libraries\Staging::removeFileAndPruneEmptyDirectories($sourceBaseDirectory, $targetBaseDirectory, 'some/invalid/file.foo');
            $this->assertFalse(isset($boom), 'exception not thrown');
        }
        catch (\Exception $e) {
            // expected
        }
    }

    function testShortenPathName() {
        $path = 'foo/bar/whatever.php';
        $this->assertEqual($path, \RightNow\Internal\Libraries\Staging::shortenPathName(OPTIMIZED_FILES . $path));
        $this->assertEqual("/$path", \RightNow\Internal\Libraries\Staging::shortenPathName(HTMLROOT . "/$path"));
        $this->assertEqual($path, \RightNow\Internal\Libraries\Staging::shortenPathName($path));
    }

    function testModeFromCurrentUmask() {
        $old = umask();
        umask(0002);
        $shouldBe0775 = \RightNow\Internal\Libraries\Staging::modeFromCurrentUmask();

        umask(0);
        $shouldBe0777 = \RightNow\Internal\Libraries\Staging::modeFromCurrentUmask();

        umask($old);

        $this->assertEqual(0775, $shouldBe0775); // 509
        $this->assertEqual(0777, $shouldBe0777); // 511

    }

    function testPruneBackups() {
        $pruneBackups = $this->getMethodInvoker('pruneBackups');
        $restore = false;
        $backupDir = DOCROOT . '/cp/generated/production/backup/optimizedAssets';
        $getDirs = function($path = null) use ($backupDir) {
            $path = $path ?: $backupDir;
            $dirs = FileSystem::isReadableDirectory($path) ? FileSystem::listDirectory($path, false, false, array('method', 'isdir')) : array();
            sort($dirs);
            return $dirs;
        };

        if ($contents = $getDirs()) {
            $restore = true;
            $restoreDir = "{$this->base_work_dir}/optimizedAssets";
            FileSystem::mkdirOrThrowExceptionOnFailure($restoreDir);
            foreach($contents as $dirName) {
                FileSystem::copyDirectory("$backupDir/$dirName", "$restoreDir/$dirName");
                FileSystem::removeDirectory("$backupDir/$dirName", true);
            }
        }
        $this->assertIdentical(array(), $getDirs());

        foreach(array('1359411381', '1359324981', '1359238581', '1359152181', '000000', 'notATimestamp') as $timestamp) {
            FileSystem::mkdirOrThrowExceptionOnFailure("$backupDir/$timestamp", true);
        }
        $pruneBackups();
        // Non-timestamps should not be touched, and, the most recent timestamp should persist.
        $this->assertIdentical(array('000000', '1359411381', 'notATimestamp'), $getDirs());

        if ($restore && ($restoreContents = $getDirs($restoreDir))) {
            FileSystem::removeDirectory($backupDir, true);
            foreach($restoreContents as $dirName) {
                FileSystem::copyDirectory("$restoreDir/$dirName", "$backupDir/$dirName");
                FileSystem::removeDirectory($restoreDir, true);
            }
            $this->assertIdentical($contents, $getDirs(), 'Backup directory not restored');
        }
    }

    /**
     * @assert the config is not empty (i.e. the site has been previously deployed)
     */
    function testWriteFrameworkVersionToConfig() {
        //TODO: We can't update this config from within the product. Commenting out for now until we have an alternative solution
        /*
        umask(0);
        file_put_contents(OPTIMIZED_FILES . 'production/optimized/frameworkVersion', ' 27.2 ');
        $method = $this->getMethod('writeFrameworkVersionToConfig');
        $originalVersion = $method();
        $this->assertIsA($originalVersion, 'string');
        $this->assertTrue(\RightNow\Utils\Text::stringContains($originalVersion, '.'));

        $output = \RightNow\UnitTest\Helper::makeRequest("/ci/unitTest/wgetRecipient/invokeTestMethod/"
            . urlencode(__FILE__) . "/" . __CLASS__ . "/getFrameworkVersionFromConfig");
        $this->assertSame('', $output);
        file_put_contents(OPTIMIZED_FILES . 'production/optimized/frameworkVersion', $originalVersion);
        $this->assertSame($originalVersion, $method());
        */
    }
    function getFrameworkVersionFromConfig() {
        //TODO: We can't update this config from within the product. Commenting out for now until we have an alternative solution
        //$this->assertSame('27.2', \RightNow\Utils\Config::getConfig(CP_DEPLOYED_FRAMEWORK_VERSION));
    }

    /**
     * @assert the config is not empty (i.e. the site has been previously deployed)
     */
    function testWriteWidgetVersionsToConfig() {
        //TODO: We can't update this config from within the product. Commenting out for now until we have an alternative solution
        /*
        umask(0);
        file_put_contents(OPTIMIZED_FILES . 'production/optimized/widgetVersions', serialize(array('banana' => '12.6')));
        $method = $this->getMethod('writeWidgetVersionsToConfig');
        $original = $method();
        $this->assertIsA($original, 'string');

        $output = \RightNow\UnitTest\Helper::makeRequest("/ci/unitTest/wgetRecipient/invokeTestMethod/"
            . urlencode(__FILE__) . "/" . __CLASS__ . "/getWidgetVersionsFromConfig");
        $this->assertSame('', $output);
        file_put_contents(OPTIMIZED_FILES . 'production/optimized/widgetVersions', serialize(json_decode($original, true)));
        $this->assertSame($original, $method());
        */
    }

    function testExcludeFile() {
        $fileDiffObject = new \RightNow\Internal\Libraries\EnvironmentFileDifferences('development', 'staging_01');
        $class = new \ReflectionClass($fileDiffObject);
        $method = $class->getMethod('excludeFile');
        $method->setAccessible(true);

        $this->assertFalse($method->invoke($fileDiffObject, '/mary/views/Partials/2.php'));
        $this->assertFalse($method->invoke($fileDiffObject, '/mary/views/Partials/23232920.php'));
        $this->assertTrue($method->invoke($fileDiffObject, '/mary/views/Partials/23232232397.php'));
        $this->assertFalse($method->invoke($fileDiffObject, '/mary/helpers/2.php'));
        $this->assertFalse($method->invoke($fileDiffObject, '/mary/helpers/23232920.php'));
        $this->assertTrue($method->invoke($fileDiffObject, '/mary/helpers/23232232397.php'));
    }

    function getWidgetVersionsFromConfig() {
        //TODO: We can't update this config from within the product. Commenting out for now until we have an alternative solution
        //$this->assertSame(array('banana' => '12.6'), json_decode(\RightNow\Utils\Config::getConfig(CP_DEPLOYED_WIDGET_VERSIONS), true));
    }

    function getMethodInvoker($methodName) {
        return \RightNow\UnitTest\Helper::getMethodInvoker('RightNow\Internal\Libraries\Promote', $methodName, array('staging_01'));
    }

}
