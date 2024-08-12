<?php
RightNow\UnitTest\Helper::loadTestedFile(__FILE__);

use RightNow\Internal\Utils\CodeAssistant,
    RightNow\Utils\Text,
    RightNow\Utils\FileSystem;

class CodeAssistantUtilTests extends CPTestCase {
    public $testingClass = '\RightNow\Internal\Utils\CodeAssistant';

    public function setUp() {
        parent::setUp();
        $this->oldMask = umask(0);
    }
    public function tearDown() {
        parent::tearDown();
        umask($this->oldMask);
    }

    private function parseOperations() {
        $method = $this->getMethod('getOperationsData');
        $data = $method();
        return $data['operations'];
    }
    private function getOperationData($file, $firstMethod = 'getUnits', $secondMethod = 'executeUnit') {
        return array(
            'file' => $file,
            'methods' => array('getUnits' => $firstMethod, 'executeUnit' => $secondMethod)
        );
    }
    private function getDefaultPath() {
        $defaultPathMethod = $this->getMethod('getDefaultPath');
        return $defaultPathMethod();
    }

    private function cleanupBackupFile($path, $root = OPTIMIZED_FILES) {
        unlink($path);
        $pathSegments = explode('/', $path);
        array_pop($pathSegments);
        while(implode('/', $pathSegments) . '/' !== $root) {
            rmdir(implode('/', $pathSegments));
            array_pop($pathSegments);
        }
    }

    public function testGetFiles() {
        $results = CodeAssistant::getFiles();
        $this->assertTrue(count($results) > 50);
        $this->assertTrue(in_array(CUSTOMER_FILES . 'views/pages/ask.php', $results));

        $results = CodeAssistant::getFiles(CodeAssistant::ASSETS);
        $this->assertTrue(count($results) > 1);
        $this->assertTrue(in_array(HTMLROOT . '/euf/assets/themes/standard/site.css', $results));

        $results = CodeAssistant::getFiles(CodeAssistant::JAVASCRIPT);
        $this->assertTrue(count($results) > 1);
        $this->assertTrue(in_array(CUSTOMER_FILES . 'javascript/autoload.js', $results));

        $results = CodeAssistant::getFiles(CodeAssistant::ALL, CodeAssistant::FILETYPE_JS);
        $this->assertTrue(count($results) > 1);
        $this->assertTrue(in_array(CUSTOMER_FILES . 'javascript/autoload.js', $results));
        foreach($results as $filename) {
            $this->assertTrue(Text::endsWith($filename, '.js'));
        }

        $results = CodeAssistant::getFiles(CodeAssistant::VIEWS, CodeAssistant::FILETYPE_ALL, '@ask.php@');
        $this->assertTrue(count($results) > 1);
        foreach($results as $filename) {
            $this->assertStringContains($filename, 'ask.php');
        }
    }

    public function testGetOperations() {
        $this->assertEqual(count($this->parseOperations()), count($results = CodeAssistant::getOperations()));

        foreach($results as $key => $operation) {
            $this->assertTrue(is_int($key));
        }

        //Use reflection to modify the static file location property and write out a test file
        list($reflectionClass, $getDefaultPath, $operationsFile) = $this->reflect('method:getDefaultPath', 'operationsFile');
        $path = 'testFile' . rand() . time() .'.yml';
        $oldPath = $operationsFile->getValue();
        $operationsFile->setValue($path);

        $fillerData = array(
            'title' => 'rn:msg:TEST_CMD',
            'description' => 'rn:msg:TEST_CMD',
            'instructions' => 'rn:msg:TEST_CMD',
            'category' => 'rn:msg:TEST_CMD',
            'file' => 'WidgetConverter.php'
        );

        $testData = array(
            'operations' => array(
                $fillerData + array('destinationVersion' => '3.0'),
                $fillerData + array('destinationVersion' => '3.1'),
            )
        );

        file_put_contents($getDefaultPath->invoke(null) . $path, yaml_emit($testData));

        //Should select both 3.0 and 3.1 data
        $results = CodeAssistant::getOperations('2.0', '3.1');
        $this->assertIdentical($testData['operations'][0]['destinationVersion'], $results[0]['destinationVersion']);
        $this->assertIdentical($testData['operations'][1]['destinationVersion'], $results[1]['destinationVersion']);
        $this->assertIdentical(2, count($results));

        //Should select only 3.1 data
        $results = CodeAssistant::getOperations('3.0', '3.1');
        $this->assertIdentical($testData['operations'][1]['destinationVersion'], $results[0]['destinationVersion']);
        $this->assertIdentical(1, count($results));

        //no operations current = next
        try {
            CodeAssistant::getOperations('3.1', '3.1');
            $this->fail('Expected exception');

        }
        catch(\Exception $e) {
            $this->assertTrue(Text::stringContains($e->getMessage(), 'no operations available'));
        }

        //no operations current < next
        try {
            CodeAssistant::getOperations('3.1', '3.0');
            $this->fail('Expected exception');
        }
        catch(\Exception $e) {
            $this->assertTrue(Text::stringContains($e->getMessage(), 'no operations available'));
        }

        $testData = array(
            'operations' => array(
                $fillerData + array('destinationVersion' => '3.0', 'deprecatedVersion' => '3.1')
            )
        );

        file_put_contents($getDefaultPath->invoke(null) . $path, yaml_emit($testData));

        //no operations, the only available operation was deprecated in 3.1
        try {
            CodeAssistant::getOperations('2.0', '3.1');
            $this->fail('Expected exception');
        }
        catch(\Exception $e) {
            $this->assertTrue(Text::stringContains($e->getMessage(), 'no operations available'));
        }

        //restore the file
        unlink($getDefaultPath->invoke(null) . $path);
        $operationsFile->setValue($oldPath);
    }

    public function testGetOperationByID() {
        $data = $this->parseOperations();

        foreach($data as $key => $operation) {
            $this->assertTrue(is_array(CodeAssistant::getOperationByID($key)));
        }
    }

    public function testGetOperationClassName() {
        $method = $this->getMethod('getOperationClassName');

        //Valid file and class
        $result = $method($this->getOperationData('WidgetConverter.php'));

        $class = 'RightNow\Internal\Utils\CodeAssistant\WidgetConverter';

        $this->assertIdentical($class, $result);
        $this->assertTrue(class_exists($class));

        //Invalid file
        try {
            $result = $method($this->getOperationData('PretendFile' . time() . '.php'));
            $this->fail('Excepted Exception');
        }
        catch(\Exception $e) {
            $this->assertTrue(Text::stringContains($e->getMessage(), 'could not be found'));
        }

        //Invalid class
        $file = 'testFile' . time() . '.php';
        file_put_contents($this->getDefaultPath() . $file, '<?php namespace RightNow\Internal\Utils\CodeAssistant; class TestClass {} ');

        try {
            $method($this->getOperationData($file), 'executeUnit');
            $this->fail('Expected Exception');
        }
        catch(\Exception $e) {
            $this->assertTrue(Text::stringContains($e->getMessage(), 'does not exist'));
        }

        unlink($this->getDefaultPath() . $file);
    }

    function testCheckMethodExists() {
        $method = $this->getMethod('checkMethodExists');

        try {
            $method("\RightNow\Internal\Utils\CodeAssistant", "nonExistentMethod");
            $this->fail('Expected Exception');
        }
        catch(\Exception $e) {
            $this->assertStringContains($e->getMessage(), 'does not exist');
        }

        $this->assertTrue($method("\RightNow\Internal\Utils\CodeAssistant", "getOperations"));
    }

    function testValidateOperation() {
        $getValidOperation = function() {
            return array(
                'title' => 'rn:msg:TEST_OPERATION_CMD',
                'description' => 'rn:msg:TEST_DESCRIPTION_CMD',
                'instructions' => 'rn:msg:TEST_INSTRUCTIONS_CMD',
                'success' => 'rn:msg:TEST_SUCCESS_CMD',
                'failure' => 'rn:msg:TEST_FAILURE_CMD',
                'destinationVersion' => '3.0',
                'category' => 'conversion',
                'file' => 'WidgetConverter.php'
            );
        };

        //Test a valid operation
        $method = $this->getMethod('validateOperation');
        $this->assertTrue(is_array($result = $method(1, $getValidOperation())));
        $this->assertIdentical(1, $result['id']);

        //Deprecated version specified and greater than destination version
        $data = $getValidOperation();
        $data['deprecatedVersion'] = '3.1';
        $this->assertTrue(is_array($method(1, $data)));

        //Deprecated version specified but less than or equal to the destination version
        try {
            $data['deprecatedVersion'] = '3.0';
            $method(1, $data);
            $this->fail('Expected Exception');
        }
        catch(\Exception $e) {
            $this->assertTrue(Text::stringContains($e->getMessage(), 'is less than or equal to the destination version'));
        }

        //Remove a required key
        $data = $getValidOperation();
        unset($data['title']);

        try {
            $method(1, $data);
            $this->fail('Expected Exception');
        }
        catch(\Exception $e) {
            $this->assertTrue(Text::stringContains($e->getMessage(), 'is required'));
        }

        //Remove internationalization from a string key
        $data = $getValidOperation();
        $data['title'] = 'Test Operation';

        try {
            $method(1, $data);
            $this->fail('Expected Exception');
        }
        catch(\Exception $e) {
            $this->assertTrue(Text::stringContains($e->getMessage(), 'must be internationalized'));
        }

        //Bad internationalization from a string key
        $data = $getValidOperation();
        $data['title'] = 'rn:invalid:Test Operation';

        try {
            $method(1, $data);
            $this->fail('Expected Exception');
        }
        catch(\Exception $e) {
            $this->assertTrue(Text::stringContains($e->getMessage(), 'has an invalid `rn` type'));
        }

        //Invalid operation type
        $data = $getValidOperation();
        $data['type'] = 'Conadfsversion';

        try {
            $method(1, $data);
            $this->fail('Expected Exception');
        }
        catch(\Exception $e) {
            $this->assertTrue(Text::stringContains($e->getMessage(), 'is not supported'));
        }

        //Invalid file
        $data = $getValidOperation();
        $data['file'] = 'testFile' . time() . rand() . '.php';

        try {
            $method(1, $data);
            $this->fail('Expected Exception');
        }
        catch(\Exception $e) {
            $this->assertTrue(Text::stringContains($e->getMessage(), 'Cannot read the following file'));
        }
    }

    function testGetUnits() {
        $operation = $this->getOperationData('Yui3Suggestions.php');

        //Add a JS file and ensure that it's added to the getUnits call
        $file = 'javascript/testjsfile' . time() . '.js';
        file_put_contents(CUSTOMER_FILES . $file, 'YAHOO.util.dom.getStyle() \n');

        $result = CodeAssistant::getUnits($operation);
        $this->assertTrue(in_array($file, $result));

        unlink(CUSTOMER_FILES . $file);

        //Operation which returns no units
        $file = 'testOperation' . time() . '.php';
        $class = Text::getSubstringBefore($file, '.php');
        $operation = $this->getOperationData($file);

        file_put_contents($this->getDefaultPath() . $file, "<?php namespace RightNow\Internal\Utils\CodeAssistant; class $class { public static function getUnits() { return array(); } public static function executeUnit() {}} ");

        try {
            CodeAssistant::getUnits($operation);
            $this->fail('Expected Exception');
        }
        catch(\Exception $e) {
            $this->assertTrue(Text::stringContains($e->getMessage(), 'no items'));
        }

        unlink($this->getDefaultPath() . $file);
    }

    function testGetInstructions() {
        $operation = $this->getOperationData('Yui3Suggestions.php');
        $operation['type'] = 'suggestion';

        //Verify that the instruction generation method is called
        $file = 'javascript/testjsfile' . time() . '.js';
        file_put_contents(CUSTOMER_FILES . $file, 'YAHOO.util.dom.getStyle() \n');

        $instructions = CodeAssistant::getInstructions($operation, array($file));
        $this->assertTrue(isset($instructions[$file]));

        unlink(CUSTOMER_FILES . $file);
    }

    function testPathHasPermission() {
        $method = $this->getMethod('pathHasPermission');

        //Test writable paths
        $this->assertTrue($method(CUSTOMER_FILES . 'widgetVersions', 'writable'));
        $this->assertTrue($method(CUSTOMER_FILES . 'widgetVersions/', 'writable'));
        $this->assertTrue($method(CUSTOMER_FILES . 'widgets/custom', 'writable'));
        $this->assertTrue($method(HTMLROOT . '/euf/assets', 'writable'));
        $this->assertTrue($method(HTMLROOT . '/euf/assets/images/icons/widxdark.gif', 'writable'));
        $this->assertFalse($method(CPCORE . 'widgets/standard', 'writable'));
        $this->assertFalse($method(CPCORE, 'writable'));
        $this->assertFalse($method(CPCORE . 'cpHistory', 'writable'));

        //Test readable paths
        $this->assertTrue($method(CUSTOMER_FILES . 'widgetVersions', 'readable'));
        $this->assertTrue($method(CUSTOMER_FILES . 'widgetVersions/', 'readable'));
        $this->assertTrue($method(CUSTOMER_FILES . 'widgets/custom', 'readable'));
        $this->assertTrue($method(CPCORE . 'widgets/standard', 'readable'));
        $this->assertTrue($method(CPCORE, 'readable'));
        $this->assertTrue($method(CPCORE . 'cpHistory', 'readable'));
        $this->assertFalse($method('/custom/scripts', 'readable'));
    }

    function testGetPathKey() {
        $method = $this->getMethod('getPathKey');

        //Get writable path keys
        $this->assertIdentical('scripts', $method(CUSTOMER_FILES, 'writable'));
        $this->assertIdentical('scripts', $method(CUSTOMER_FILES . 'widgetVersions/', 'writable'));
        $this->assertIdentical('scripts', $method(CUSTOMER_FILES . 'widgets/custom', 'writable'));
        $this->assertIdentical('assets', $method(HTMLROOT . '/euf/assets/images', 'writable'));

        //Get readable path keys
        $this->assertIdentical('cp', $method(CUSTOMER_FILES, 'readable'));
        $this->assertIdentical('cp', $method(CUSTOMER_FILES . 'widgetVersions/', 'readable'));
        $this->assertIdentical('cp', $method(CUSTOMER_FILES . 'widgets/custom', 'readable'));
        $this->assertIdentical('cp', $method(CPCORE, 'readable'));
        $this->assertIdentical('cp', $method(CPCORE . 'cpHistory', 'readable'));
        $this->assertIdentical('cp', $method(CPCORE . 'widgets/stnadard', 'readable'));
        $this->assertIdentical('assets', $method(HTMLROOT . '/euf/assets/images', 'readable'));
        $this->assertIdentical('assets', $method(HTMLROOT . '/euf/core/images', 'readable'));
    }

    function testGetAllFrameworkVersions() {
        $results = CodeAssistant::getAllFrameworkVersions();

        $this->assertTrue(count($results) > 2);
        $this->assertTrue(in_array('2.0', $results));
        $this->assertTrue(in_array('3.0', $results));
        $this->assertTrue(in_array('3.1', $results));

        $this->assertTrue(array_search('2.0', $results) < array_search('3.0', $results));
        $this->assertTrue(array_search('3.0', $results) < array_search('3.1', $results));
        $this->assertTrue(array_search('2.0', $results) < array_search('3.1', $results));
    }

    function testGetWebDAVBackupPath() {
        $method = $this->getMethod('getWebDAVBackupPath');
        $directory = 'ca' . strftime("%m-%d-%Y %H.%M.%S") . '/';
        $backupDirectory = CodeAssistant::getBackupPath() . $directory;
        $pathKey = 'scripts/';
        $file = 'widgets/custom/input/TestInput/base.css';
        $absolutePath = $backupDirectory . $pathKey . $file;

        FileSystem::mkdirOrThrowExceptionOnFailure(dirname($absolutePath), true);
        file_put_contents($absolutePath, 'test data');

        $result = $method($absolutePath, $backupDirectory);
        $this->assertTrue($result['isDAV']);
        $this->assertIdentical('cp/generated/temp_backups/' . $directory . $pathKey . $file, $result['davPath']);
        $this->assertIdentical('generated/temp_backups/.../' . $file, $result['visiblePath']);

        FileSystem::removeDirectory($backupDirectory, true);
    }

    function testGetWebDAVPathObject() {
        $method = $this->getMethod('getWebDAVPathObject');

        //A visible DAV path
        $pathObject = array(
            'key' => 'cp',
            'hiddenPath' => 'customer/development/',
            'visiblePath' => 'widgets/custom/sample/SampleWidget/1.0/base.css'
        );
        $result = $method($pathObject);
        $this->assertTrue($result['isDAV']);
        $this->assertIdentical('cp/customer/development/widgets/custom/sample/SampleWidget/1.0/base.css', $result['davPath']);
        $this->assertIdentical('widgets/custom/sample/SampleWidget/1.0/base.css', $result['visiblePath']);

        //A non-visible readable path
        $pathObject = array(
            'key' => 'euf',
            'hiddenPath' => 'applications/development/source',
            'visiblePath' => 'widgets/custom/sample/SampleWidget/base.css'
        );
        $result = $method($pathObject);
        $this->assertFalse($result['isDAV']);
        $this->assertNull($result['hiddenPath']);
        $this->assertIdentical($pathObject['visiblePath'], $result['visiblePath']);
    }

    function testGetWebDAVPath() {
        $method = $this->getMethod('getWebDAVPath');

        //Retrieve a valid DAV path
        $path = $method(CUSTOMER_FILES . 'widgets/custom');
        $this->assertIdentical('cp/customer/development/widgets/custom', $path);

        //Fail on an invalid DAV path
        $path = $method(DOCROOT . 'euf');
        $this->assertFalse($path);
    }

    function testGetAbsolutePathAndCheckPermissions() {
        $method = $this->getMethod('getAbsolutePathAndCheckPermissions');

        //v3 customer file readable and writable
        $pathObject = array(
            'key' => 'cp',
            'hiddenPath' => 'customer/development/',
            'visiblePath' => 'controllers/AjaxCustom.php'
        );

        $this->assertIdentical(CUSTOMER_FILES . 'controllers/AjaxCustom.php', $method($pathObject, true));

        //v3 asset readable and writable
        $pathObject = array(
            'key' => 'assets',
            'hiddenPath' => 'assets/images/icons/',
            'visiblePath' => 'widxdark.gif'
        );

        $this->assertIdentical(HTMLROOT . '/euf/assets/images/icons/widxdark.gif', $method($pathObject, true));

        //v3 core file readable, not writable
        $pathObject = array(
            'key' => 'cp',
            'hiddenPath' => 'core/framework/',
            'visiblePath' => 'Controllers/Ajax.php'
        );

        $this->assertIdentical(CPCORE . 'Controllers/Ajax.php', $method($pathObject));

        try {
            $this->assertIdentical(CPCORE . 'Controllers/Ajax.php', $method($pathObject, true));
            $this->fail();
        }
        catch(\Exception $e) {
            $this->assertStringContains($e->getMessage(), 'not writable');
        }

        //v2 core file readable, not writable
        $pathObject = array(
            'key' => 'euf',
            'hiddenPath' => 'application/rightnow/',
            'visiblePath' => 'controllers/ajaxRequest.php'
        );

        $this->assertIdentical(DOCROOT . '/euf/application/rightnow/controllers/ajaxRequest.php', $method($pathObject));

        try {
            $this->assertIdentical(DOCROOT . '/euf/application/rightnow/controllers/ajaxRequest.php', $method($pathObject, true));
            $this->fail();
        }
        catch(\Exception $e) {
            $this->assertStringContains($e->getMessage(), 'not writable');
        }

        //non-accessible file
        $pathObject = array(
            'key' => 'test',
            'hiddenPath' => 'test/test',
            'visiblePath' => 'controllers/ajaxRequest.php'
        );

        try {
            $this->assertIdentical(DOCROOT .'/euf/test/test/controllers/ajaxRequest.php', $method($pathObject));
            $this->fail();
        }
        catch(\Exception $e) {
            $this->assertStringContains($e->getMessage(), 'not accessible');
        }
    }

    function testBackupFile() {
        $method = $this->getMethod('backupFile');

        $backupDirectory = time() . rand() . '/';
        $keyPath = CUSTOMER_FILES;
        $filePath = 'widgets/custom/sample/SampleWidget/1.0/logic.js';

        //Create backup of readable/writable file
        $backupFile = OPTIMIZED_FILES . $backupDirectory . 'scripts/' . $filePath;
        $this->assertIdentical($backupFile, $method($keyPath . $filePath, OPTIMIZED_FILES . $backupDirectory));
        $this->assertTrue(FileSystem::isReadableFile($backupFile));

        //Cleanup the files
        $this->cleanupBackupFile($backupFile);
    }

    function testProcessInstruction() {
        $backupDirectory = time() . rand() . '/';
        $method = $this->getMethod('processInstruction');
        $tempPathMethod = $this->getMethod('getTemporaryPath');

        //Create a directory
        $visiblePath = time() . rand();
        $instruction = array(
            'type' => 'createDirectory',
            'source' => array(
                'key' => 'cp',
                'hiddenPath' => 'customer/development/widgets/custom/',
                'visiblePath' => $visiblePath
            )
        );
        $this->assertFalse($method($instruction, OPTIMIZED_FILES . $backupDirectory));
        $widgetDirectory = CUSTOMER_FILES . 'widgets/custom/' . $visiblePath;
        $this->assertTrue(FileSystem::isReadableDirectory($widgetDirectory));
        rmdir($widgetDirectory);

        //Create a file
        file_put_contents($tempPathMethod() . $visiblePath, 'test content');
        $instruction = array(
            'type' => 'createFile',
            'tempSource' => $visiblePath,
            'source' => array(
                'key' => 'cp',
                'hiddenPath' => 'customer/development/javascript/',
                'visiblePath' => $visiblePath . '.js'
            )
        );
        $filePath = CUSTOMER_FILES . "javascript/$visiblePath.js";
        $this->assertFalse($method($instruction, OPTIMIZED_FILES . $backupDirectory));
        $this->assertTrue(FileSystem::isReadableFile($filePath));
        $this->assertIdentical('test content', file_get_contents($filePath));
        unlink($filePath);
        unlink($tempPathMethod() . $visiblePath);

        //Delete a file, backup created
        $deletedFile = CUSTOMER_FILES . "javascript/$visiblePath.js";
        file_put_contents($deletedFile, 'test content');
        $instruction = array(
            'type' => 'deleteFile',
            'source' => array(
                'key' => 'cp',
                'hiddenPath' => 'customer/development/javascript/',
                'visiblePath' => $visiblePath . '.js'
            )
        );
        $backupPath = $method($instruction, OPTIMIZED_FILES . $backupDirectory);
        $this->assertIdentical($backupPath, OPTIMIZED_FILES . "{$backupDirectory}scripts/javascript/$visiblePath.js");
        $this->assertFalse(FileSystem::isReadableFile($deletedFile));
        $this->assertTrue(FileSystem::isReadableFile($backupPath));
        $this->assertIdentical('test content', file_get_contents($backupPath));
        $this->cleanupBackupFile($backupPath);

        //Modify a file, backup created
        $modifiedFile = CUSTOMER_FILES . "javascript/$visiblePath.js";
        file_put_contents($tempPathMethod() . $visiblePath, 'modified content');
        file_put_contents($modifiedFile, 'original content');
        $instruction = array(
            'type' => 'modifyFile',
            'tempSource' => $visiblePath,
            'source' => array(
                'key' => 'cp',
                'hiddenPath' => 'customer/development/javascript/',
                'visiblePath' => $visiblePath . '.js'
            )
        );
        $backupPath = $method($instruction, OPTIMIZED_FILES . $backupDirectory);
        $this->assertIdentical($backupPath, OPTIMIZED_FILES . "{$backupDirectory}scripts/javascript/$visiblePath.js");
        $this->assertTrue(FileSystem::isReadableFile($modifiedFile));
        $this->assertTrue(FileSystem::isReadableFile($backupPath));
        $this->assertIdentical('modified content', file_get_contents($modifiedFile));
        $this->assertIdentical('original content', file_get_contents($backupPath));
        $this->cleanupBackupFile($backupPath);
        unlink($tempPathMethod() . $visiblePath);
        unlink($modifiedFile);

        //Move a file, backup created
        $originalFile = CUSTOMER_FILES . "javascript/$visiblePath.js";
        file_put_contents($tempPathMethod() . $visiblePath, 'test content');
        file_put_contents($originalFile, 'test content');
        $instruction = array(
            'type' => 'moveFile',
            'tempSource' => $visiblePath,
            'source' => array(
                'key' => 'cp',
                'hiddenPath' => 'customer/development/javascript/',
                'visiblePath' => $visiblePath . '.js'
            ),
            'destination' => array(
                'key' => 'cp',
                'hiddenPath' => 'customer/development/javascript/',
                'visiblePath' => $visiblePath . '-destination.js'
            )
        );
        $backupPath = $method($instruction, OPTIMIZED_FILES . $backupDirectory);
        $this->assertIdentical($backupPath, OPTIMIZED_FILES . "{$backupDirectory}scripts/javascript/$visiblePath.js");
        $this->assertFalse(FileSystem::isReadableFile($originalFile));
        $this->assertTrue(FileSystem::isReadableFile($backupPath));
        $destinationFile = CUSTOMER_FILES . "javascript/{$visiblePath}-destination.js";
        $this->assertTrue(FileSystem::isReadableFile($destinationFile));
        $this->assertIdentical('test content', file_get_contents($backupPath));
        $this->assertIdentical('test content', file_get_contents($destinationFile));
        $this->cleanupBackupFile($backupPath);
        unlink($tempPathMethod() . $visiblePath);
        unlink($destinationFile);

        // Move a directory and verify backup created
        $directoryName = 'custom/testMoveDirectory';
        $directoryPath = CUSTOMER_FILES . "widgets/$directoryName";
        FileSystem::mkdirOrThrowExceptionOnFailure($directoryPath);
        chmod($directoryPath, 0777);
        $filePath = "$directoryPath/test.txt";
        FileSystem::filePutContentsOrThrowExceptionOnFailure($filePath, 'some content');
        chmod($filePath, 0666);

        $instruction = array (
            'type' => 'moveDirectory',
            'source' => array (
                'key' => 'cp',
                'hiddenPath' => 'customer/development/widgets/',
                'visiblePath' => $directoryName,
            ),
            'destination' => array(
                'key' => 'cp',
                'hiddenPath' => 'customer/development/widgets/',
                'visiblePath' => "{$directoryName}2",
            ),
        );
        $backupPath = $method($instruction, OPTIMIZED_FILES . $backupDirectory);
        $this->assertTrue(FileSystem::isReadableDirectory($backupPath));
        $this->assertFalse(FileSystem::isReadableDirectory($directoryPath));
        $this->assertTrue(FileSystem::isReadableDirectory("{$directoryPath}2"));
        $this->assertTrue(FileSystem::isReadableFile("{$directoryPath}2/test.txt"));
        FileSystem::removeDirectory("{$directoryPath}2", true);
    }

    function testProcessInstructions() {
        $method = $this->getMethod('processInstructions');
        $tempPathMethod = $this->getMethod('getTemporaryPath');
        $createdFile = time() . rand();
        $tempFile = $tempPathMethod() . $createdFile;

        //Error in the unit data - Mark the unit as failed
        $operation = array(
            'file' => 'Yui3Suggestions.php'
        );
        $units = array(
            'testInput' => array(
                'errors' => array(
                    'Test Error Message'
                )
            )
        );
        $result = $method($operation, $units);
        $this->assertTrue(count($result['failedUnits']) === 1);
        $this->assertTrue(count($result['successfulUnits']) === 0);
        $this->assertIdentical('Test Error Message', $result['failedUnits']['testInput']['errors'][0]);

        //Process a pair of valid instructions. A directory should be created and a child file inserted.
        file_put_contents($tempFile, 'test content');
        $units = array(
            'testInput' => array(
                'instructions' => array(
                    array(
                        'type' => 'createDirectory',
                        'source' => array(
                            'key' => 'cp',
                            'hiddenPath' => 'customer/development/widgets/custom/',
                            'visiblePath' => $createdFile
                        )
                    ),
                    array(
                        'type' => 'createFile',
                        'tempSource' => $createdFile,
                        'source' => array(
                            'key' => 'cp',
                            'hiddenPath' => 'customer/development/widgets/custom/',
                            'visiblePath' => "$createdFile/$createdFile"
                        )
                    )
                )
            )
        );

        $result = $method($operation, $units);

        //Unit completed successfully
        $this->assertTrue(count($result['successfulUnits']) === 1);
        $this->assertTrue(count($result['failedUnits']) === 0);
        $unitResults = $result['successfulUnits']['testInput'];

        //Two instructions completed
        $this->assertTrue(count($unitResults['instructions']) === 2);
        $this->assertIdentical('createDirectory', $unitResults['instructions'][0]['type']);
        $this->assertIdentical('createFile', $unitResults['instructions'][1]['type']);

        //No backups created
        $this->assertFalse($unitResults['instructions'][0]['backup']);
        $this->assertFalse($unitResults['instructions'][1]['backup']);

        //Cleanup files
        unlink($tempFile);
        unlink(CUSTOMER_FILES . "widgets/custom/$createdFile/$createdFile");
        rmdir(CUSTOMER_FILES . "widgets/custom/$createdFile");

        //Process a pair of instructions. The first is valid, the second is invalid
        file_put_contents($tempFile, 'modified content');
        file_put_contents(CUSTOMER_FILES . "javascript/$createdFile", 'original content');
        $units = array(
            'testInput' => array(
                'instructions' => array(
                    array(
                        'type' => 'modifyFile',
                        'tempSource' => $createdFile,
                        'source' => array(
                            'key' => 'cp',
                            'hiddenPath' => 'customer/development/javascript/',
                            'visiblePath' => $createdFile
                        )
                    ),
                    array(
                        'type' => 'createFile',
                        'tempSource' => $createdFile,
                        'source' => array(
                            'key' => 'cp',
                            'hiddenPath' => 'customer/development/widgets/custom/',
                            'visiblePath' => "$createdFile/TEST_FAIL/$createdFile"
                        )
                    )
                )
            )
        );

        $result = $method($operation, $units);
        $errors = $result['failedUnits']['testInput']['errors'];
        $this->assertTrue(count($errors) === 2);
        $this->assertStringContains($errors[0], 'backup will need to be manually reverted');
        $this->assertStringContains($errors[1], 'Unable to create file');

        unlink($tempFile);
        unlink(CUSTOMER_FILES . "javascript/$createdFile");
    }
}
