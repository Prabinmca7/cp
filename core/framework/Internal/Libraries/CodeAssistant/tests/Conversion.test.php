<?php

use RightNow\Utils\FileSystem;

require_once(CPCORE . 'Internal/Utils/CodeAssistant.php');
RightNow\UnitTest\Helper::loadTestedFile(__FILE__);

class ConversionTest extends CPTestCase {
    public $testingClass = '\RightNow\Internal\Libraries\CodeAssistant\Conversion';

    function testCreateTempFile() {
        
    }

    function testGenerateDiff() {
        
    }

    function testCreateDirectory() {
        list($reflectionClass, $directoryCache) = $this->reflect('directoryCache');

        $instance = $reflectionClass->newInstance();
        $instance->setAbsolutePath(CUSTOMER_FILES);

        $this->assertTrue($instance->createDirectory('testDirectory'));
        $cache = $directoryCache->getValue($instance);
        $this->assertTrue(isset($cache[CUSTOMER_FILES . 'testDirectory']));
        $this->assertTrue(count($instance->getInstructions()) === 1);

        $this->assertTrue($instance->createDirectory('testDirectory/nextTest'));
        $cache = $directoryCache->getValue($instance);
        $this->assertTrue(isset($cache[CUSTOMER_FILES . 'testDirectory/nextTest']));
        $this->assertTrue(count($instance->getInstructions()) === 2);

        //Directory already exists in cache, don't override
        $this->assertFalse($instance->createDirectory('testDirectory'));

        //Directory already exists on FS, don't override
        $this->assertFalse($instance->createDirectory('widgets'));
    }

    function testDeleteFile() {
        list($reflectionClass, $removedCache) = $this->reflect('removedFiles');

        $instance = $reflectionClass->newInstance();
        $instance->setAbsolutePath(CUSTOMER_FILES);

        //Can't delete a directory
        $this->assertFalse($instance->deleteFile('widgets'));

        //Delete a file which should exist, the instruction should be added and the operation cached
        $this->assertTrue($instance->deleteFile('widgetVersions'));
        $cache = $removedCache->getValue($instance);
        $this->assertTrue(isset($cache[CUSTOMER_FILES . 'widgetVersions']));
        $this->assertTrue(count($instance->getInstructions()) === 1); 

        //Can't delete a non-writable file
        try {
            $instance->setAbsolutePath(CORE_FILES);
            $instance->deleteFile('cpHistory');
            $this->fail('expected exception');
        }
        catch(\Exception $e) {
            $this->assertTrue(\RightNow\Utils\Text::stringContains($e->getMessage(), 'is not a writable path'));
        }

    }

    function testCreateFile() {
        list($reflectionClass, $writableCache) = $this->reflect('writableFileCache');

        $instance = $reflectionClass->newInstance();
        $instance->setAbsolutePath(CUSTOMER_FILES);

        //Can't override an existing file
        $this->assertFalse($instance->createFile('widgetVersions', 'test content'));

        //Can't override an existing directory
        $this->assertFalse($instance->createFile('widgets/custom', 'test content'));

        //Create a file and make sure it is cached correctly and the instruction is added
        $this->assertTrue($instance->createFile('testFile', 'test content'));
        $cache = $writableCache->getValue($instance);
        $this->assertTrue(isset($cache[CUSTOMER_FILES . 'testFile']));
        $this->assertIdentical('test content', $cache[CUSTOMER_FILES . 'testFile']); 
        $this->assertTrue(count($instance->getInstructions()) === 1);

        //Attempt to recreate the file
        $this->assertFalse($instance->createFile('testFile', 'test content'));
    }


    function testModifyFile() {
        list($reflectionClass, $writableCache) = $this->reflect('writableFileCache');
     
        $instance = $reflectionClass->newInstance();
        $instance->setAbsolutePath(CUSTOMER_FILES);

        //A non-existent file cannot be modified
        $this->assertFalse($instance->modifyFile('nonExistentFile', 'test content'));
        
        //Modify the file
        $this->assertTrue($instance->modifyFile('widgetVersions', 'test content'));
        $cache = $writableCache->getValue($instance);
        $this->assertTrue(isset($cache[CUSTOMER_FILES . 'widgetVersions']));
        $this->assertIdentical('test content', $cache[CUSTOMER_FILES . 'widgetVersions']);
        $this->assertTrue(count($instance->getInstructions()) === 1);
    }

    function testMoveFile() {
        list($reflectionClass, $writableCache, $removedCache) = $this->reflect('writableFileCache', 'removedFiles');
     
        $instance = $reflectionClass->newInstance();
        $instance->setAbsolutePath(CUSTOMER_FILES);

        //non-existent destination
        $this->assertFalse($instance->moveFile('widgetVersions', 'fakePath/yup'));

        //non-existent source
        $this->assertFalse($instance->moveFile('banana', 'newTestFile'));

        //non-writable destination
        try {
            $this->assertFalse($instance->moveFile('widgetVersions', CPCORE . 'cpHistory'));
            $this->fail('Excepted exception');
        }
        catch(\Exception $e) {
            $this->assertTrue(\RightNow\Utils\Text::stringContains($e->getMessage(), 'not a writable path'));
        }

        //non-writable source
        try {
            $this->assertFalse($instance->moveFile(CPCORE . 'cpHistory', 'widgetVersions'));
            $this->fail('Excepted exception');
        }
        catch(\Exception $e) {
            $this->assertTrue(\RightNow\Utils\Text::stringContains($e->getMessage(), 'not a writable path'));
        }

        //Move a file
        $this->assertTrue($instance->moveFile('widgetVersions', 'newWidgetVersions'));
        $cache = $writableCache->getValue($instance);
        $deletedCache = $removedCache->getValue($instance);
        $this->assertTrue(isset($cache[CUSTOMER_FILES . 'newWidgetVersions']));
        $this->assertTrue(isset($deletedCache[CUSTOMER_FILES . 'widgetVersions']));        
        $this->assertTrue(count($instance->getInstructions()) === 1);
    }

    function testMoveDirectory() {
        umask(0);
        list($reflectionClass, $directoryCache) = $this->reflect('directoryCache');
     
        $instance = $reflectionClass->newInstance();
        $instance->setAbsolutePath(CUSTOMER_FILES);

        $basePath = CUSTOMER_FILES;
        $testDir = 'testMoveDirectory';
        $paths = array(
            'source' => "widgets/$testDir",
            'absSource' => "{$basePath}widgets/$testDir",
            'target' => "widgets/{$testDir}2",
            'absTarget' => "{$basePath}widgets/{$testDir}2",
        );

        $createPopulatedDirectory = function($path) {
            FileSystem::mkdirOrThrowExceptionOnFailure($path);
            chmod($path, 0777);
            $filePath = "$path/test.txt";
            FileSystem::filePutContentsOrThrowExceptionOnFailure($filePath, 'some content');
            chmod($filePath, 0666);
        };

        $cleanup = function() use ($paths) {
            FileSystem::removeDirectory($paths['absSource'], true);
            FileSystem::removeDirectory($paths['absTarget'], true);
        };


        $source = $paths['source'];
        $target = $paths['target'];

        // non-existent source
        $cleanup();
        $this->assertFalse($instance->moveDirectory($source, $target));

        // target directory exists
        $createPopulatedDirectory($paths['absTarget']);
        $this->assertFalse($instance->moveDirectory($source, $target));

        // Move a directory
        $cleanup();
        $createPopulatedDirectory($paths['absSource']);
        $this->assertTrue($instance->moveDirectory($source, $target));
        $cache = $directoryCache->getValue($instance);
        $this->assertFalse($cache[$paths['absSource']]);
        $this->assertTrue($cache[$paths['absTarget']]);

        $cleanup();
    }
}
