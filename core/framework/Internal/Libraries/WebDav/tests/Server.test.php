<?php

use RightNow\Internal\Libraries\WebDav\Server;

RightNow\UnitTest\Helper::loadTestedFile(__FILE__);

class ServerTest extends CPTestCase {
    public $testingClass = '\RightNow\Internal\Libraries\WebDav\Server';
    private $account;

    function __construct() {
        $this->account = (object)array(
            'acct_id' => 'test account'
        );
        $this->testServer = new Server($this->account);
    }

    private function getLoggedServer() {
        $logFile = 'testLog' . rand() . time() . '.log';
        $logPath = \RightNow\Api::cfg_path() . "/log/$logFile";
        $loggedServer = new Server($this->account, $logPath);
        return array($logFile, $logPath, $loggedServer);
    }

    function testPROPFIND() {
        $options = array(
            'path' => 'cp/customer/development',
            'depth' => 'infinity'
        );
        $results = array();
        $this->assertTrue($this->testServer->PROPFIND($options, $results));

        $expectedFiles = array(
            'development/',
            'config/',
            'controllers/',
            'errors/',
            'javascript/',
            'libraries/',
            'models/',
            'views/'
        );

        $results = array_map(function($infoObject) { return $infoObject['path']; }, $results['files']);
        foreach($expectedFiles as $file) {
            $this->assertTrue(in_array($file, $results));
        }
    }

    function testGetIndexHtml() {
        $handler = new \RightNow\Internal\Libraries\WebDav\PathHandler('cp/customer/development');
        $method = $this->getMethod('getIndexHtml', array($this->account));

        $output = $method($handler);
        $expectedUrls = array(
            '/dav/cp/customer/development/config',
            '/dav/cp/customer/development/controllers',
            '/dav/cp/customer/development/errors',
            '/dav/cp/customer/development/javascript',
            '/dav/cp/customer/development/libraries',
            '/dav/cp/customer/development/models',
            '/dav/cp/customer/development/views'
        );

        $this->assertTrue(is_string($output));
        $this->assertTrue(strlen($output) > 0);
        $this->assertStringContains($output, "Index of cp/customer/development");
        foreach($expectedUrls as $url) {
            $this->assertStringContains($output, "href='$url'");
        }
    }

    function testGetBreadcrumbHtml() {
        $method = $this->getMethod('getBreadcrumbHtml', array($this->account));

        $this->assertIdentical($method('/'), '/');
        $this->assertIdentical($method('cp/core'), "/<a href='/dav/cp'>cp</a>/<a href='/dav/cp/core'>core</a>/");
    }

    function testIsIgnoredPutRequest() {
        $ignoredPaths = array(
            'cp/core/framework/._magicFile',
            'cp/customer/development/temp2342.htm',
            'cp/core/framework/.DS_Store',
            'cp/customer/development/.VolumeIcon.icns',
            'cp/customer/development/.fseventsd',
            'cp/customer/development/.Trashes'
        );

        $validPaths = array(
            'cp/customer/development/models/custom/newModel.php',
            'cp/customer/development/libraries/testLibrary.php'
        );

        $method = $this->getMethod('isIgnoredPutRequest', array($this->account));
        foreach($ignoredPaths as $path) {
            $this->assertTrue($method($path));
        }
        foreach($validPaths as $path) {
            $this->assertFalse($method($path));
        }
    }

    function testPUT() {
        list($logFile, $logPath, $loggedServer) = $this->getLoggedServer();
        $filePath = 'cp/customer/development/libraries/' . $logFile;
        $options = array(
            'path' => $filePath
        );

        $filePointer = $loggedServer->PUT($options);

        $this->assertNotNull($filePointer);
        $this->assertStringContains(file_get_contents($logPath), $filePath);

        unlink($logPath);
        fclose($filePointer);
        unlink(DOCROOT . '/cp/customer/development/libraries/' . $logFile);
    }

    function testIsIgnoredMkColRequest() {
        $ignoredPaths = array(
            'MM_CASETEST4291',
            'mm_casetest4291',
            'xyiznwsk',
            'cp/customer/development/MM_CASETEST4291',
        );

        $validPaths = array(
            'cp/customer/development/models/custom/newModel.php',
            'cp/customer/development/libraries/testLibrary.php'
        );

        $method = $this->getMethod('isIgnoredMkColRequest', array($this->account));
        foreach($ignoredPaths as $path) {
            $this->assertTrue($method($path));
        }
        foreach($validPaths as $path) {
            $this->assertFalse($method($path));
        }
    }

    function testMKCOL() {
        list($logFile, $logPath, $loggedServer) = $this->getLoggedServer();
        $filePath = 'cp/customer/development/libraries/newDirectory';

        $options = array(
            'path' => $filePath
        );

        $status = $loggedServer->MKCOL($options);

        $this->assertIdentical($status, '201 Created');
        $this->assertStringContains(file_get_contents($logPath), $filePath);

        unlink($logPath);
        rmdir(DOCROOT . '/cp/customer/development/libraries/newDirectory');
    }

    function testCOPY() {
        list($logFile, $logPath, $loggedServer) = $this->getLoggedServer();

        //Copy no-overwrite
        $oldFile = 'cp/customer/development/libraries/Sample.php';
        $newFile = 'cp/customer/development/models/custom/NewFile.php';

        $options = array(
            'path' => $oldFile,
            'dest_path' => $newFile,
        );

        $status = $loggedServer->COPY($options);

        $this->assertIdentical($status, '201 Created');
        $logContent = file_get_contents($logPath);
        $this->assertStringContains($logContent, $oldFile);
        $this->assertStringContains($logContent, $newFile);

        //Copy with overwrite (overwrite the file added above)
        $oldFile = 'cp/customer/development/models/custom/Sample.php';

        $options = array(
            'path' => $oldFile,
            'dest_path' => $newFile,
            'overwrite' => 'true'
        );

        $status = $loggedServer->COPY($options);

        $this->assertIdentical($status, '204 No Content');
        $logContent = file_get_contents($logPath);
        $this->assertStringContains($logContent, $oldFile);
        $this->assertStringContains($logContent, $newFile);

        unlink($logPath);
        unlink(DOCROOT . "/$newFile");
    }

    function testMOVE() {
        list($logFile, $logPath, $loggedServer) = $this->getLoggedServer();

        //Move the file, no overwrite.
        $filePath = 'cp/customer/development/libraries/' . $logFile;
        file_put_contents(DOCROOT . "/$filePath", 'test content');

        $destPath = 'cp/customer/development/models/custom/' . $logFile;
        $options = array(
            'path' => $filePath,
            'dest_path' => $destPath
        );

        $status = $loggedServer->MOVE($options);

        $this->assertIdentical($status, '201 Created');
        $logContent = file_get_contents($logPath);
        $this->assertStringContains($logContent, $filePath);
        $this->assertStringContains($logContent, $destPath);
        $this->assertIdentical(file_get_contents(DOCROOT . "/$destPath"), 'test content');

        unlink($logPath);
        unlink(DOCROOT . "/$destPath");
    }

    function testDELETE() {
        list($logFile, $logPath, $loggedServer) = $this->getLoggedServer();

        //Delete a file
        $filePath = 'cp/customer/development/libraries/' . $logFile;
        $fileSystemPath = DOCROOT . "/$filePath";
        file_put_contents($fileSystemPath, 'test content');
        $this->assertTrue(is_readable($fileSystemPath));

        $options = array(
            'path' => $filePath,
        );

        $status = $loggedServer->DELETE($options);

        $this->assertIdentical($status, '204 No Content');
        $logContent = file_get_contents($logPath);
        $this->assertStringContains($logContent, $filePath);
        $this->assertFalse(is_readable($fileSystemPath));

        unlink($logPath);
    }

    function testMimeTypeByExtension() {
        $method = $this->getMethod('mimeTypeByExtension', array($this->account));
        $this->assertEqual('text/html', $method('html'));
        $this->assertEqual('text/html', $method('html'));
        $this->assertEqual('text/plain', $method('php'));
        $this->assertEqual('text/plain', $method('PHP'));
        $this->assertEqual('text/css', $method('scss'));
        $this->assertEqual('application/octet-stream', $method('someUndefinedExtension'));
        $this->assertEqual(null, $method('someUndefinedExtension', null));
    }

    function testMkcolPermission() {
        $hasPermission = array(
            array('cp', 'customer', 'assets'),
            array('cp', 'customer', 'development', 'widgets', 'custom'),
            array('cp', 'customer', 'development', 'models', 'custom'),
            array('cp', 'customer', 'development', 'views', 'pages'),
            array('cp', 'customer', 'development', 'views', 'Partials'),
            array('cp', 'customer', 'development', 'views', 'templates'),
            array('cp', 'customer', 'development', 'libraries', 'tests', 'whatever'),
            array('cp', 'customer', 'development', 'libraries'),
            array('cp', 'customer', 'development', 'controllers'),
            array('cp', 'customer', 'development', 'helpers'),
        );

        $noPermission = array(
            array(),
            array('/'),
            array('cp'),
            array('cp', 'core', 'assets', 'default'),
            array('cp', 'customer'),
            array('cp', 'customer', 'development', 'models'),
            array('cp', 'customer', 'development', 'views'),
            array('cp', 'customer', 'development', 'somethingElse'),
            array('cp', 'customer', 'development'),
            array('cp', 'customer', 'development', 'widgets'),
            array('cp', 'customer', 'development', 'models', 'NotCustom'),
            array('cp', 'customer', 'development', 'views', 'blah'),
            array('cp', 'customer', 'development', 'widgets', 'NotCustom'),
        );

        $mkcolPermission = $this->getMethod('mkcolPermission', array($this->account));
        foreach($hasPermission as $segments) {
            $this->assertTrue($mkcolPermission($segments));
        }
        foreach($noPermission as $segments) {
            $this->assertFalse($mkcolPermission($segments));
        }
    }

    function testpmdPermission() {
        $pmdPermission = $this->getMethod('pmdPermission', array($this->account));

        $this->assertFalse($pmdPermission('PUT', array('cp', 'core','assets', 'foo')));
        $this->assertTrue($pmdPermission('COPY', array('cp', 'customer', 'assets', 'foo')));
        $this->assertTrue($pmdPermission('PUT', array('cp', 'customer', 'assets', 'foo')));
        $this->assertTrue($pmdPermission('DELETE',  array('cp', 'customer', 'assets', 'foo')));
        $this->assertFalse($pmdPermission('COPY', array('cp', 'core', 'assets', 'default', 'foo')));
        $this->assertFalse($pmdPermission('COPY', array('cp', 'customer', '', 'default', 'foo')));
        $this->assertTrue($pmdPermission('PUT', array('cp', 'customer', 'error', 'splash.html')));
        $this->assertFalse($pmdPermission('PUT', array('cp', 'customer', 'error', 'offLimits.html')));

        $this->assertTrue($pmdPermission('PUT', array('cp', 'customer', 'development', 'allowMixedFrameworkSpPatching')));
        $this->assertTrue($pmdPermission('DELETE', array('cp', 'customer', 'development', 'allowMixedFrameworkSpPatching')));

        $this->assertTrue($pmdPermission('PUT', array('cp', 'customer', 'development', 'config', 'hooks.php')));
        $this->assertTrue($pmdPermission('COPY', array('cp', 'customer', 'development', 'config', 'hooks.php')));
        $this->assertTrue($pmdPermission('COPY', array('cp', 'customer', 'development', 'config', 'extensions.yml')));
        $this->assertTrue($pmdPermission('COPY', array('cp', 'customer', 'development', 'config', 'search_sources.yml')));
        $this->assertTrue($pmdPermission('COPY', array('cp', 'customer', 'development', 'config', 'HandsOff.php')));
        $this->assertFalse($pmdPermission('DELETE', array('cp', 'customer', 'development', 'config', 'mapping.php')));
        $this->assertFalse($pmdPermission('DELETE', array('cp', 'customer', 'development', 'config')));

        $this->assertTrue($pmdPermission('COPY', array('cp', 'customer', 'development', 'errors', 'error_general.php')));
        $this->assertFalse($pmdPermission('COPY', array('cp', 'customer', 'development', 'errors', 'goAway.php')));
        $this->assertFalse($pmdPermission('DELETE', array('cp', 'customer', 'development', 'errors')));

        $this->assertTrue($pmdPermission('COPY', array('cp', 'customer', 'development', 'models', 'custom', 'CustomModel.php')));
        $this->assertFalse($pmdPermission('COPY', array('cp', 'customer', 'development', 'models', 'NOTcustom', 'Model.php')));
        $this->assertFalse($pmdPermission('COPY', array('cp', 'customer', 'development', 'models', 'custom')));
        $this->assertFalse($pmdPermission('DELETE', array('cp', 'customer', 'development', 'models', 'custom')));
        $this->assertTrue($pmdPermission('COPY', array('cp', 'customer', 'development', 'widgets', 'custom', 'CustomWidget')));
        $this->assertFalse($pmdPermission('COPY', array('cp', 'customer', 'development', 'widgets', 'NOTcustom', 'Widget')));
        $this->assertFalse($pmdPermission('COPY', array('cp', 'customer', 'development', 'widgets', 'custom')));
        $this->assertFalse($pmdPermission('DELETE', array('cp', 'customer', 'development', 'widgets', 'custom')));
        $this->assertTrue($pmdPermission('COPY', array('cp', 'customer', 'development', 'models', 'custom', 'CustomModel.php')));

        $this->assertTrue($pmdPermission('COPY', array('cp', 'customer', 'development', 'libraries', 'myLibrary.php')));
        $this->assertFalse($pmdPermission('COPY', array('cp', 'customer', 'development', 'libraries')));
        $this->assertTrue($pmdPermission('COPY', array('cp', 'customer', 'development', 'helpers', 'myHelper.php')));
        $this->assertFalse($pmdPermission('COPY', array('cp', 'customer', 'development', 'helpers')));
        $this->assertTrue($pmdPermission('COPY', array('cp', 'customer', 'development', 'controllers', 'myController.php')));
        $this->assertFalse($pmdPermission('COPY', array('cp', 'customer', 'development', 'controllers')));

        $this->assertTrue($pmdPermission('PUT', array('cp', 'customer', 'development', 'javascript', 'autoload.js')));
        $this->assertFalse($pmdPermission('DELETE', array('cp', 'customer', 'development', 'javascript', 'autoload.js')));
        $this->assertTrue($pmdPermission('PUT', array('cp', 'customer', 'development', 'javascript', 'blah.js')));
        $this->assertFalse($pmdPermission('DELETE', array('cp', 'customer', 'development', 'javascript')));

        $this->assertFalse($pmdPermission('PUT', array('cp', 'customer', 'development', 'views')));
        $this->assertTrue($pmdPermission('PUT', array('cp', 'customer', 'development', 'views', 'pages', 'aPath')));
        $this->assertFalse($pmdPermission('PUT', array('cp', 'customer', 'development', 'views', 'pages')));
        $this->assertFalse($pmdPermission('PUT', array('cp', 'customer', 'development', 'views', 'Partials')));
        $this->assertFalse($pmdPermission('DELETE', array('cp', 'customer', 'development', 'views', 'Partials')));
        $this->assertFalse($pmdPermission('COPY', array('cp', 'customer', 'development', 'views', 'Partials')));

        $this->assertTrue($pmdPermission('PUT', array('cp', 'customer', 'development', 'views', 'templates', 'aTemplate')));
        $this->assertFalse($pmdPermission('PUT', array('cp', 'customer', 'development', 'views', 'templates')));

        $this->assertTrue($pmdPermission('PUT', array('cp', 'customer', 'development', 'views', 'admin', 'answer_full_preview.php')));
        $this->assertFalse($pmdPermission('PUT', array('cp', 'customer', 'development', 'views', 'admin', 'NOPE.php')));
        $this->assertFalse($pmdPermission('COPY', array('cp', 'customer', 'development', 'views', 'admin', 'answer_full_preview.php')));

        $this->assertTrue($pmdPermission('PUT', array('cp', 'customer', 'development', 'views', 'admin', 'answer.php')));
        $this->assertFalse($pmdPermission('COPY', array('cp', 'customer', 'development', 'views', 'admin', 'answer.php')));
        $this->assertTrue($pmdPermission('PUT', array('cp', 'customer', 'development', 'views', 'admin', 'okcs_answer_full_preview.php')));
        $this->assertFalse($pmdPermission('COPY', array('cp', 'customer', 'development', 'views', 'admin', 'okcs_answer_full_preview.php')));
        $this->assertFalse($pmdPermission('DELETE', array('cp', 'customer', 'development', 'views', 'admin')));

        // code assistant backups - Can only delete files under the temp_backups folder, cannot move or create files
        $this->assertTrue($pmdPermission('DELETE', array('cp', 'generated', 'temp_backups', 'ca07-12-2013 14:32:16')));
        $this->assertFalse($pmdPermission('PUT', array('cp', 'generated', 'temp_backups', 'ca07-12-2013 14:32:16')));
        $this->assertFalse($pmdPermission('COPY', array('cp', 'generated', 'temp_backups', 'ca07-12-2013 14:32:16')));

        $this->assertFalse($pmdPermission('DELETE', array('cp', 'generated', 'temp_backups')));

        // logs
        $this->assertFalse($pmdPermission('COPY', array('cp', 'logs', 'staging123456789.log')));
        $this->assertTrue($pmdPermission('DELETE', array('cp', 'logs', 'cp123456.tr')));

        $this->assertFalse($pmdPermission('COPY', array('cp', 'customer', 'error')));
        $this->assertFalse($pmdPermission('PUT', array('cp', 'customer', 'error')));
        $this->assertFalse($pmdPermission('DELETE', array('cp', 'customer', 'error')));

        $this->assertFalse($pmdPermission('COPY', array('cp', 'customer', 'error', 'splash.html')));
        $this->assertTrue($pmdPermission('PUT', array('cp', 'customer', 'error', 'splash.html')));
        $this->assertFalse($pmdPermission('DELETE', array('cp', 'customer', 'error', 'splash.html')));

        $this->assertFalse($pmdPermission('COPY', array('cp', 'customer', 'error', 'bunk.html')));
        $this->assertFalse($pmdPermission('PUT', array('cp', 'customer', 'error', 'bunk.html')));
        $this->assertFalse($pmdPermission('DELETE', array('cp', 'customer', 'error', 'bunk.html')));

        $this->assertTrue($pmdPermission('PUT', array('cp', 'customer', 'error', 'splash.html')));
        $this->assertFalse($pmdPermission('PUT', array('cp', 'customer', 'error', 'splash.html-da96890c-319d-4f59-b0cb-b9f8f8538784')));

        $this->assertFalse($pmdPermission('DELETE', array('cp', 'customer', 'error', 'splash.html')));
        $this->assertFalse($pmdPermission('DELETE', array('cp', 'customer', 'error', 'splash.html-da96890c-319d-4f59-b0cb-b9f8f8538784')));

        $lastAgent = $_SERVER['HTTP_USER_AGENT'];
        $_SERVER['HTTP_USER_AGENT'] = "Cyberduck";
        $this->assertTrue($pmdPermission('PUT', array('cp', 'customer', 'error', 'splash.html-da96890c-319d-4f59-b0cb-b9f8f8538784')));
        $this->assertTrue($pmdPermission('DELETE', array('cp', 'customer', 'error', 'splash.html-da96890c-319d-4f59-b0cb-b9f8f8538784')));
        $_SERVER['HTTP_USER_AGENT'] = $lastAgent;
    }

    function testIsCyberduck() {
        $method = $this->getMethod('isCyberduck',  array($this->account));

        $this->assertFalse($method());

        $lastAgent = $_SERVER['HTTP_USER_AGENT'];
        $_SERVER['HTTP_USER_AGENT'] = "Cyberduck";
        $this->assertTrue($method());
        $_SERVER['HTTP_USER_AGENT'] = $lastAgent;
    }

    function testIsCyberduckGuidFile() {
        $method = $this->getMethod('isCyberduckGuidFile',  array($this->account));

        $this->assertTrue($method('mapping.php-da96890c-319d-4f59-b0cb-b9f8f8538784', array('mapping.php')));
        $this->assertTrue($method('hooks.php-71a27491-c588-43d8-b1fa-f2f8950c17a2', array('hooks.php')));

        $this->assertFalse($method('hooksaphp-71a27491-c588-43d8-b1fa-f2f8950c17a2', array('hooks.php')));
        $this->assertFalse($method('hooks2.php-71a27491-c588-43d8-b1fa-f2f8950c17a2', array('hooks.php')));
    }
}
