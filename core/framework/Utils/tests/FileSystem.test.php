<?php

use RightNow\Utils\FileSystem;

RightNow\UnitTest\Helper::loadTestedFile(__FILE__);

class FileSystemScriptsTest extends CPTestCase {

    function __construct() {
        $this->base_work_dir = sprintf("%s/unitTest/%s", get_cfg_var('upload_tmp_dir'), get_class($this));
        umask(0);
        FileSystem::mkdirOrThrowExceptionOnFailure($this->base_work_dir, true);
        $this->baseDir = "$this->base_work_dir/dir1/";
    }

    function setUp() {
        // Make sure that we don't create files that others can't remove
        umask(0);
        $cwd = getcwd();
        $baseDir = $this->baseDir;
        FileSystem::removeDirectory($baseDir, true);
        if (is_dir($baseDir)) {
            throw new Exception("Couldn't remove $baseDir.");
        }
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
        chdir($cwd);
        parent::setUp();
    }

    function getStaticMethodInvoker($methodName) {
        return RightNow\UnitTest\Helper::getStaticMethodInvoker('\RightNow\Utils\FileSystem', $methodName);
    }

    function testIsWritableDirectory() {
        $this->assertIdentical(true, FileSystem::isWritableDirectory($this->baseDir));
        $this->assertIdentical(true, FileSystem::isWritableDirectory($this->baseDir . 'dir2'));
        $this->assertIdentical(true, FileSystem::isWritableDirectory($this->baseDir . 'dir2/dir3'));
        $this->assertIdentical(true, FileSystem::isWritableDirectory($this->baseDir . 'dir2/dir3/dir4'));
        $this->assertIdentical(false, FileSystem::isWritableDirectory($this->baseDir . 'file1.html'));
        $this->assertIdentical(false, FileSystem::isWritableDirectory($this->baseDir . 'file2.html'));
        $this->assertIdentical(false, FileSystem::isWritableDirectory($this->baseDir . 'dir2/file2.html'));
    }

    function testIsReadableFile() {
        $this->assertIdentical(false, FileSystem::isReadableFile($this->baseDir));
        $this->assertIdentical(false, FileSystem::isReadableFile($this->baseDir . 'dir2'));
        $this->assertIdentical(false, FileSystem::isReadableFile($this->baseDir . 'dir2/dir3'));
        $this->assertIdentical(false, FileSystem::isReadableFile($this->baseDir . 'dir2/dir3/dir4'));
        $this->assertIdentical(true, FileSystem::isReadableFile($this->baseDir . 'file1.html'));
        $this->assertIdentical(false, FileSystem::isReadableFile($this->baseDir . 'file2.html'));
        $this->assertIdentical(true, FileSystem::isReadableFile($this->baseDir . 'dir2/file2.html'));
        $this->assertIdentical(true, FileSystem::isReadableFile($this->baseDir . 'dir2/_file2.html'));
        $this->assertIdentical(true, FileSystem::isReadableFile($this->baseDir . 'dir2/file2.php'));
    }

    function testIsReadableDirectory() {
        $this->assertIdentical(true, FileSystem::isReadableDirectory($this->baseDir));
        $this->assertIdentical(true, FileSystem::isReadableDirectory($this->baseDir . 'dir2'));
        $this->assertIdentical(true, FileSystem::isReadableDirectory($this->baseDir . 'dir2/dir3'));
        $this->assertIdentical(true, FileSystem::isReadableDirectory($this->baseDir . 'dir2/dir3/dir4'));
        $this->assertIdentical(false, FileSystem::isReadableDirectory($this->baseDir . 'file1.html'));
        $this->assertIdentical(false, FileSystem::isReadableDirectory($this->baseDir . 'file2.html'));
        $this->assertIdentical(false, FileSystem::isReadableDirectory($this->baseDir . 'dir2/file2.html'));
    }

    function testCopyFileOrThrowExceptionOnFailure() {
        $runTest = function($that, $expectsException, $source, $target, $shouldOverwrite) {
            try {
                FileSystem::copyFileOrThrowExceptionOnFailure($source, $target, $shouldOverwrite);
                $expectsException ? $that->fail() : $that->pass();
            }
            catch (Exception $e) {
                $expectsException ? $that->pass() : $that->fail();
            }
        };

        $runTest($this, false, $this->baseDir . 'file1.html', $this->baseDir . 'file1.html.copy', false);
        $this->assertIdentical('file1.html', file_get_contents($this->baseDir . 'file1.html.copy'));
        @unlink($this->baseDir . 'file1.html.copy');
        $runTest($this, false, $this->baseDir . 'file1.html', $this->baseDir . 'file1.php', false);
        $this->assertIdentical('file1.php', file_get_contents($this->baseDir . 'file1.php'));
        $runTest($this, false, $this->baseDir . 'file1.html', $this->baseDir . 'file1.php', true);
        $this->assertIdentical('file1.html', file_get_contents($this->baseDir . 'file1.php'));
        file_put_contents($this->baseDir . 'file1.php', 'file1.php');
        $this->assertIdentical('file1.php', file_get_contents($this->baseDir . 'file1.php'));

        $runTest($this, true, $this->baseDir . 'file1.html', $this->baseDir . 'dir2', true);
        $this->assertIdentical(false, file_exists($this->baseDir . 'dir2/file1.php'));

        $runTest($this, true, $this->baseDir . 'dir2', $this->baseDir, true);
        $runTest($this, true, $this->baseDir . 'dir2/dir3/dir4', $this->baseDir . 'dir2', true);
    }

    function testFilePutContentsOrThrowExceptionOnFailure() {
        $runTest = function($that, $expectsException, $path, $contents) {
            try {
                FileSystem::filePutContentsOrThrowExceptionOnFailure($path, $contents);
                $expectsException ? $that->fail() : $that->pass();
            }
            catch (Exception $e) {
                $expectsException ? $that->pass() : $that->fail();
            }
        };

        $runTest($this, false, $this->baseDir . 'file1.html.copy', 'file1.html.copy');
        $this->assertIdentical('file1.html.copy', file_get_contents($this->baseDir . 'file1.html.copy'));
        @unlink($this->baseDir . 'file1.html.copy');

        $runTest($this, true, $this->baseDir . 'dir2', 'dir2 is a directory');
    }

    function testGetCoreAssetFileSystemPath() {
        $this->assertIdentical(HTMLROOT . '/euf/core/', FileSystem::getCoreAssetFileSystemPath());
        $this->assertIdentical(HTMLROOT . '/euf/core/', FileSystem::getCoreAssetFileSystemPath(null));
        $this->assertIdentical(HTMLROOT . '/euf/core/', FileSystem::getCoreAssetFileSystemPath(array()));
        $this->assertIdentical(HTMLROOT . '/euf/core/5', FileSystem::getCoreAssetFileSystemPath(5));
        $this->assertIdentical(HTMLROOT . '/euf/core/blah', FileSystem::getCoreAssetFileSystemPath('blah'));
        $this->assertIdentical(HTMLROOT . '/euf/core/blah/blah2', FileSystem::getCoreAssetFileSystemPath('blah/blah2'));
    }

    function testListDirectory() {
        $addFullPath = false;
        $expected = array ('_file1.html', 'dir2', 'file1.html', 'file1.php');
        $actual = FileSystem::listDirectory($this->baseDir, $addFullPath);
        $this->assertEqual($expected, sort($actual));
    }

    function testListDirectoryFullPath() {
        $addFullPath = true;
        $expected = array ($this->baseDir . '_file1.html', $this->baseDir . 'dir2', $this->baseDir . 'file1.html', $this->baseDir . 'file1.php');
        $actual = FileSystem::listDirectory($this->baseDir, $addFullPath);
        $this->assertEqual($expected, sort($actual));
    }

    function testListDirectoryRecursive() {
        $addFullPath = false;
        $recursive = true;
        $expected =  array (
          '_file1.html',
          'dir2',
          'dir2/file2.html',
          'dir2/file2.php',
          'dir2/dir3',
          'dir2/dir3/file3.php',
          'dir2/dir3/dir4',
          'dir2/dir3/_file3.html',
          'dir2/dir3/file3.html',
          'dir2/_file2.html',
          'file1.html',
          'file1.php',
        );
        $actual = FileSystem::listDirectory($this->baseDir, $addFullPath, $recursive);

        foreach($expected as $e)
            $this->assertTrue(in_array($e, $actual));

        foreach($actual as $a)
            $this->assertTrue(in_array($a, $expected));
    }

    function testListDirectoryFilterEquals() {
        $addFullPath = false;
        $recursive = false;
        $filter = array('equals', 'file1.php');
        $expected = array ('file1.php');
        $actual = FileSystem::listDirectory($this->baseDir, $addFullPath, $recursive, $filter);
        $this->assertEqual($expected, sort($actual));
    }

    function testListDirectoryFilterNotEquals() {
        $addFullPath = false;
        $recursive = false;
        $filter = array('not equals', 'file1.php');
        $expected = array ('_file1.html', 'dir2', 'file1.html');
        $actual = FileSystem::listDirectory($this->baseDir, $addFullPath, $recursive, $filter);
        $this->assertEqual($expected, sort($actual));
    }

    function testListDirectoryFilterMatch() {
        $addFullPath = false;
        $recursive = false;
        $filter = array('match', '/^.+\.php|html$/');
        $expected = array ('_file1.html', 'file1.html', 'file1.php');
        $actual = FileSystem::listDirectory($this->baseDir, $addFullPath, $recursive, $filter);
        $this->assertEqual($expected, sort($actual));
    }

    function testListDirectoryFilterNotMatch() {
        $addFullPath = false;
        $recursive = false;
        $filter = array('not match', '/^file1.php$/');
        $expected = array('_file1.html', 'dir2', 'file1.html');
        $actual = FileSystem::listDirectory($this->baseDir, $addFullPath, $recursive, $filter);
        $this->assertEqual($expected, sort($actual));
    }

    function testListDirectoryisDir() {
        $addFullPath = false;
        $recursive = false;
        $filter = array('method', 'isDir');
        $expected = array ('dir2');
        $actual = FileSystem::listDirectory($this->baseDir, $addFullPath, $recursive, $filter);
        $this->assertEqual($expected, sort($actual));
    }

    function testListDirectoryisFile() {
        $addFullPath = false;
        $recursive = false;
        $filter = array('method', 'isFile');
        $expected = array ('_file1.html', 'file1.html','file1.php');
        $actual = FileSystem::listDirectory($this->baseDir, $addFullPath, $recursive, $filter);
        $this->assertEqual($expected, sort($actual));
    }

    function testListDirectoryCustomFunction() {
        $addFullPath = false;
        $recursive = false;
        $filter = array('function', function($f) {return $f->getFileName() === 'file1.php' || $f->getFileName() === 'file1.html';});
        $expected = array ('file1.html','file1.php');
        $actual = FileSystem::listDirectory($this->baseDir, $addFullPath, $recursive, $filter);
        $this->assertEqual($expected, sort($actual));
    }

    function testListDirectoryExtraData() {
        $addFullPath = false;
        $recursive = false;
        $filter = null;
        $extraData = array('getType', 'getSize', function($f) {return $f->isReadable();});
        $expected = array(
          array('_file1.html', 'file', 10,   true),
          array('dir2',        'dir',  4096, true),
          array('file1.html',  'file', 10,   true),
          array('file1.php',   'file', 9,    true)
        );
        $actual = FileSystem::listDirectory($this->baseDir, $addFullPath, $recursive, $filter, $extraData);
        $this->assertEqual($expected, sort($actual));
    }

    function testGetOptimizedAssetsDir() {
        $getTimeStampFunction = $this->getStaticMethodInvoker('getLastDeployTimestampFromFile');
        $timestamp = $getTimeStampFunction();
        if ($timestamp === null) {
            $this->assertEqual(FileSystem::getOptimizedAssetsDir(), null);
        }
        else {
            $this->assertEqual(FileSystem::getOptimizedAssetsDir(), "/euf/generated/optimized/$timestamp/");
        }
    }

    function testGetLastDeployTimestampFromFile() {
        $path = "$this->base_work_dir/timestamp";
        FileSystem::filePutContentsOrThrowExceptionOnFailure($path,   '1234567890');
        $getTimeStampFunction = $this->getStaticMethodInvoker('getLastDeployTimestampFromFile');
        $this->assertEqual($getTimeStampFunction($path), '1234567890');

        // maxlen
        FileSystem::filePutContentsOrThrowExceptionOnFailure($path, '12345678900asfasfsfd');
        $this->assertEqual($getTimeStampFunction($path), '1234567890');

        // null as default
        unlink($path);
        $this->assertEqual($getTimeStampFunction($path), null);
    }

    function testGetLastDeployTimestampFromDir() {
        $basePath = "$this->base_work_dir/someTimestamps";
        if (!FileSystem::isReadableDirectory($basePath)) {
            mkdir($basePath);
        }
        foreach (FileSystem::listDirectory($basePath, true, false, array('match', '/^[0-9]{10}/')) as $timestamp) {
            rmdir($timestamp);
        }
        // null as default
        $this->assertEqual(
            FileSystem::getLastDeployTimestampFromDir($basePath),
            null
        );
        $expected = '1234567901';
        mkdir("$basePath/0000000000");
        mkdir("$basePath/1234567890");
        mkdir("$basePath/1234567900");
        mkdir("$basePath/$expected");
        $this->assertEqual(
            FileSystem::getLastDeployTimestampFromDir($basePath),
            $expected
        );
    }

    function testWriteDeployTimestampToFile() {
        FileSystem::writeDeployTimestampToFile('production', time());
        $getTimeStampFunction = $this->getStaticMethodInvoker('getLastDeployTimestampFromFile');
        $this->assertNotEqual($getTimeStampFunction(), FileSystem::getLastDeployTimestampFromDir());

        FileSystem::writeDeployTimestampToFile();
        $this->assertEqual($getTimeStampFunction(), FileSystem::getLastDeployTimestampFromDir());

        $stagingDir = OPTIMIZED_FILES . 'staging/staging_01/';
        if (FileSystem::isReadableDirectory($stagingDir)) {
            FileSystem::writeDeployTimestampToFile('staging_01');
            $this->assertEqual($getTimeStampFunction("{$stagingDir}deployTimestamp"), FileSystem::getLastDeployTimestampFromDir(HTMLROOT . '/euf/generated/staging/staging_01/optimized'));
        }

        try {
            FileSystem::writeDeployTimestampToFile($someInvalidMode);
            $this->assertFalse(true, 'exception did not occur');
        }
        catch (Exception $e) {
            // expected
        }

        try {
            FileSystem::writeDeployTimestampToFile('production', 93939393939);
            $this->assertFalse(true, 'exception did not occur');
        }
        catch (Exception $e) {
            // expected
        }
    }

    function testGetSortedListOfDirectoryEntries() {
        $write = array(
            'z.php',
            '.z.php',
            'Z1.md',
            '.DS_Store',
            'z10.md',
            'banana.js',
            '._banana.js',
        );
        $dir = $this->baseDir . '/testGetSortedListOfDirectoryEntries';
        FileSystem::mkdirOrThrowExceptionOnFailure($dir, true);

        foreach ($write as $file) {
            file_put_contents("{$dir}/{$file}", 'content');
        }

        $expected = array(
            '._banana.js',
            '.DS_Store',
            '.z.php',
            'banana.js',
            'z.php',
            'Z1.md',
            'z10.md',
        );
        $contents = FileSystem::getSortedListOfDirectoryEntries($dir);
        $this->assertIdentical($expected, $contents);

        $expected = array (
            '.DS_Store',
            '._banana.js',
            '.z.php',
            'Z1.md',
            'banana.js',
            'z.php',
            'z10.md',
        );
        $contents = FileSystem::getSortedListOfDirectoryEntries($dir, 'strcmp');
        $this->assertIdentical($expected, $contents, sprintf("Expected: '%s' got: '%s'", var_export($expected, true), var_export($contents, true)));

        $expected = array(
            'banana.js',
        );
        $contents = FileSystem::getSortedListOfDirectoryEntries($dir, false, array('equals', 'banana.js'));
        $this->assertIdentical($expected, $contents);

        $write = array(
        	'directory',
        	'.git',
        	'.directory',
        );
        foreach ($write as $subDir) {
            FileSystem::mkdirOrThrowExceptionOnFailure("{$dir}/{$subDir}", true);
        }
        $expected = array(
            '.directory',
            '.git',
            'directory',
        );
        $contents = FileSystem::getSortedListOfDirectoryEntries($dir, false, array('method', 'isDir'));
        $this->assertIdentical($expected, $contents);

        FileSystem::removeDirectory($dir, true);
    }

    function testListDirectoryRecursively() {
        $baseDir = CUSTOMER_FILES . 'widgets/custom/sample/SampleWidget/1.0';
        $expected = array(
            $baseDir . '/Partial.html.php',
            $baseDir . '/base.css',
            $baseDir . '/controller.php',
            $baseDir . '/info.yml',
            $baseDir . '/logic.js',
            $baseDir . '/tests/base.test',
            $baseDir . '/themesPackageSource/mobile/widgetCss/SampleWidget.css',
            $baseDir . '/themesPackageSource/standard/widgetCss/SampleWidget.css',
            $baseDir . '/view.php',
        );

        $actual = FileSystem::listDirectoryRecursively($baseDir);
        sort($actual);
        $this->assertSame(count($expected), count($actual));
        for ($i = 0; $i < count($actual); $i++) {
            $this->assertIdentical($expected[$i], $actual[$i]);
        }

        $expected = array(
            $baseDir . '/Partial.html.php',
            $baseDir . '/base.css',
            $baseDir . '/controller.php',
            $baseDir . '/info.yml',
            $baseDir . '/logic.js',
            $baseDir . '/view.php',
        );
        $actual = FileSystem::listDirectoryRecursively($baseDir, 1);
        sort($actual);
        $this->assertSame(count($expected), count($actual));
        for ($i = 0; $i < count($actual); $i++) {
            $this->assertIdentical($expected[$i], $actual[$i]);
        }

        $expected = array(
            $baseDir . '/Partial.html.php',
            $baseDir . '/base.css',
            $baseDir . '/controller.php',
            $baseDir . '/info.yml',
            $baseDir . '/logic.js',
            $baseDir . '/tests/base.test',
            $baseDir . '/view.php',
        );
        $actual = FileSystem::listDirectoryRecursively($baseDir, 2);
        sort($actual);
        $this->assertSame(count($expected), count($actual));
        for ($i = 0; $i < count($actual); $i++) {
            $this->assertIdentical($expected[$i], $actual[$i]);
        }

        $actual = FileSystem::listDirectoryRecursively($baseDir, 3);
        sort($actual);
        $this->assertSame(count($expected), count($actual));
        for ($i = 0; $i < count($actual); $i++) {
            $this->assertIdentical($expected[$i], $actual[$i]);
        }

        $expected = array(
            $baseDir . '/Partial.html.php',
            $baseDir . '/base.css',
            $baseDir . '/controller.php',
            $baseDir . '/info.yml',
            $baseDir . '/logic.js',
            $baseDir . '/tests/base.test',
            $baseDir . '/themesPackageSource/mobile/widgetCss/SampleWidget.css',
            $baseDir . '/themesPackageSource/standard/widgetCss/SampleWidget.css',
            $baseDir . '/view.php',
        );
        $actual = FileSystem::listDirectoryRecursively($baseDir, 4);
        sort($actual);
        $this->assertSame(count($expected), count($actual));
        for ($i = 0; $i < count($actual); $i++) {
            $this->assertIdentical($expected[$i], $actual[$i]);
        }
    }

    function testGetDirectoryTree(){
        $baseDir = CUSTOMER_FILES . 'widgets/custom/sample/SampleWidget/1.0';

        $results = FileSystem::getDirectoryTree($baseDir);
        $this->assertIdentical(15, count($results));
        $this->assertFalse($results['themesPackageSource']);
        $this->assertFalse($results['themesPackageSource/standard']);
        $this->assertFalse($results['themesPackageSource/standard/widgetCss']);
        $this->assertFalse($results['themesPackageSource/mobile']);
        $this->assertFalse($results['themesPackageSource/mobile/widgetCss']);

        $this->assertIdentical(filemtime("$baseDir/base.css"), $results['base.css']);
        $this->assertIdentical(filemtime("$baseDir/info.yml"), $results['info.yml']);
        $this->assertIdentical(filemtime("$baseDir/controller.php"), $results['controller.php']);
        $this->assertIdentical(filemtime("$baseDir/logic.js"), $results['logic.js']);
        $this->assertIdentical(filemtime("$baseDir/view.php"), $results['view.php']);
        $this->assertIdentical(filemtime("$baseDir/themesPackageSource/standard/widgetCss/SampleWidget.css"), $results['themesPackageSource/standard/widgetCss/SampleWidget.css']);
        $this->assertIdentical(filemtime("$baseDir/themesPackageSource/mobile/widgetCss/SampleWidget.css"), $results['themesPackageSource/mobile/widgetCss/SampleWidget.css']);

        $results = FileSystem::getDirectoryTree($baseDir, array('php', 'js'));
        $this->assertIdentical(10, count($results));
        $this->assertFalse($results['themesPackageSource']);
        $this->assertFalse($results['themesPackageSource/standard']);
        $this->assertFalse($results['themesPackageSource/standard/widgetCss']);
        $this->assertFalse($results['themesPackageSource/mobile']);
        $this->assertFalse($results['themesPackageSource/mobile/widgetCss']);
        $this->assertIdentical(filemtime("$baseDir/controller.php"), $results['controller.php']);
        $this->assertIdentical(filemtime("$baseDir/logic.js"), $results['logic.js']);
        $this->assertIdentical(filemtime("$baseDir/view.php"), $results['view.php']);
    }
}