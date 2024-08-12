<?php
use RightNow\Internal\Libraries\WebDav\PathHandler,
    RightNow\Internal\Utils\FileSystem;

RightNow\UnitTest\Helper::loadTestedFile(__FILE__);
class PathHandlerTest extends CPTestCase {
    public $testingClass = '\RightNow\Internal\Libraries\WebDav\PathHandler';

    function testGetArtificialPaths() {
        $paths = array(
            array('path' => '',              'isArtificial' => true),
            array('path' => '/',             'isArtificial' => true),
            array('path' => 'cp',            'isArtificial' => true),
            array('path' => 'cp/core',       'isArtificial' => false),
            array('path' => 'cp/customer',   'isArtificial' => false),
            array('path' => 'cp/generated',  'isArtificial' => false),
            array('path' => 'cp/logs',       'isArtificial' => false)
        );

        foreach($paths as $testCase) {
            $handler = new PathHandler($testCase['path']);
            if($testCase['isArtificial']) {
                $this->assertTrue($handler->isArtificialPath());
            }
            else {
                $this->assertFalse($handler->isArtificialPath());
            }
        }

        //All of the artificial paths should have children
        $getArtificial = $this->getMethod('getArtificialPaths', true);
        foreach($getArtificial() as $path => $children) {
            $this->assertTrue(count($children) > 0);
        }
    }

    function testGetInsertedPaths() {
        $insertedPaths = $this->getMethod('getInsertedPaths', true);

        //Every inserted path must map to a readable location on disk
        foreach($insertedPaths() as $basePath) {
            $this->assertTrue(is_readable($basePath));
        }
    }

    function testGetFileSystemPath() {
        $cp = DOCROOT . '/cp/';
        $frameworkVersion = \RightNow\Utils\Framework::getFrameworkVersion();
        $productionTimestamp = FileSystem::getLastDeployTimestampFromFile() ?: FileSystem::getLastDeployTimestampFromDir();
        $stagingTimestamp = FileSystem::getLastDeployTimestampFromDir(HTMLROOT . "/euf/generated/staging/staging_01/optimized");
        $testPaths = array(
            //input, output
            '' => false,
            '.' => false,
            '/' => false,
            'cp/' => false,
            'cp/core' => $cp . 'core',
            'cp/core/assets' => HTMLROOT . '/euf/core/' . CP_FRAMEWORK_VERSION,
            'cp/customer' => $cp . 'customer',
            'cp/customer/development/widgets' => $cp . 'customer/development/widgets',
            'cp/generated/staging' => $cp . 'generated/staging/staging_01',
            'cp/generated/production' => $cp . 'generated/production',
            'cp/core/framework' => $cp . 'core/framework/' . $frameworkVersion,
            'cp/core/framework/Controllers/Ajax.php' => CPCORE . $frameworkVersion . '/Controllers/Ajax.php',
            'cp/core/widgets/standard/input/TextInput/view.php' => $cp . 'core/widgets/standard/input/TextInput/view.php',
            'cp/customer/error/splash.html' => $cp . "customer/development/errors/splash.html",
            'cp/customer/error' => $cp . "customer/development/errors",
            'cp/generated/staging/assets' => HTMLROOT . '/euf/generated/staging/staging_01/optimized/' . $stagingTimestamp,
            'cp/generated/production/assets/themes/standard/widgetCss/CommunityUserDisplay.css' => HTMLROOT . "/euf/generated/optimized/$productionTimestamp/themes/standard/widgetCss/CommunityUserDisplay.css",
            'cp/customer/assets/themes' => HTMLROOT . '/euf/assets/themes'
        );

        list($object, $testingProperty) = $this->reflect('isTesting');
        foreach($testPaths as $path => $expected) {
            $handler = $object->newInstanceArgs(array($path));
            $testingProperty->setValue($handler, true);
            $this->assertSame($handler->getFileSystemPath(), $expected);
        }
    }

    function testTransformToDavPath() {
        $testPaths = array(
            DOCROOT . '/cp/generated/staging/staging_01/source/views/pages/ask.php' => 'cp/generated/staging/source/views/pages/ask.php'
        );

        $method = $this->getMethod('transformToDavPath', true);
        foreach($testPaths as $absolutePath => $davPath) {
            $this->assertIdentical($method($absolutePath), $davPath);
        }
    }

    function testGetDirectoryContents() {
        //A file shouldn't have directory contents
        $filePath = new PathHandler('cp/core/framework/Controllers/Ajax.php');
        $this->assertTrue(count($filePath->getDirectoryContents()) === 0);

        //Artificial paths should be included in the contents
        $root = new PathHandler('/');
        $rootHandlers = $root->getDirectoryContents();
        $this->assertIdentical(1, count($rootHandlers));
        $this->assertIdentical('cp', $rootHandlers[0]->getDavPath());

        $cp = new PathHandler('cp');
        $expectedPaths = array(
            'cp/core',
            'cp/customer',
            'cp/generated',
            'cp/logs'
        );

        $contents = $cp->getDirectoryContents();
        $this->assertIdentical(count($contents), count($expectedPaths));
        foreach($contents as $index => $handler) {
            $this->assertIdentical($handler->getDavPath(), $expectedPaths[$index]);
        }

        //Both inserted and actual file system folders should be included in the directory contents
        $core = new PathHandler('cp/core');
        $expectedPaths = array(
            'cp/core/assets',
            'cp/core/framework',
            'cp/core/widgets'
        );
        foreach($core->getDirectoryContents() as $index => $handler) {
            $this->assertIdentical($handler->getDavPath(), $expectedPaths[$index]);
            $this->assertTrue($handler->isVisiblePath());
            $this->assertTrue($handler->fileExists());
        }

        //Test actual paths
        $paths = array(
            'cp/customer' => array(
                'assets',
                'development',
                'error'
            ),
            'cp/customer/development' => array(
                'config',
                'controllers',
                'errors',
                'helpers',
                'javascript',
                'libraries',
                'models',
                'views',
                'widgets'
            ),
            'cp/core/framework' => array(
                'CodeIgniter',
                'Controllers',
                'Libraries',
                'Models',
                'Utils',
                'manifest'
            ),
            'cp/customer/error' => array(
                'error500.html',
                'splash.html',
                'error_exception.php',
                'error_general.php',
                'error_php.php'
            ),
            'cp/generated/staging' => array(
                'assets',
                'backup',
                'source'
            ),
            'cp/generated/production' => array(
                'backup',
                'source'
            )
        );

        foreach($paths as $path => $children) {
            $handler = new PathHandler($path);
            $contents = array_map(function($handler) { return $handler->getFileOrFolderName(); }, $handler->getDirectoryContents());
            foreach($children as $child) {
                $this->assertTrue(in_array($child, $contents));
            }
        }
    }

    function testGetDavUrl() {
        $testPaths = array(
            '/',
            'cp',
            'cp/core',
            'cp/core/framework',
            'cp/core/framework/Controllers/Ajax.php'
        );

        foreach($testPaths as $path) {
            $handler = new PathHandler($path);
            if($path !== '/') {
                $this->assertIdentical('/dav/' . $path, $handler->getDavUrl());
            }
            else {
                $this->assertIdentical('/dav/', $handler->getDavUrl());
            }
        }
    }

    function testGetFileOrFolderName() {
        $testPaths = array(
            '/',
            'cp',
            'cp/core',
            'cp/core/framework',
            'cp/core/framework/Controllers/Ajax.php'
        );

        foreach($testPaths as $path) {
            $handler = new PathHandler($path);
            $segments = explode('/', $path);
            if($path !== '/') {
                $this->assertIdentical(end($segments), $handler->getFileOrFolderName());
            }
            else {
                $this->assertIdentical('/', $handler->getFileOrFolderName());
            }
        }
    }

    function testGetDavPath() {
        $testPaths = array(
            '/',
            'cp',
            'cp/core',
            'cp/core/framework',
            'cp/core/framework/Controllers/Ajax.php'
        );

        foreach($testPaths as $path) {
            $handler = new PathHandler($path);
            $this->assertIdentical($path, $handler->getDavPath());
        }
    }

    function testGetDavSegments() {
        $testPaths = array(
            '/',
            'cp',
            'cp/core',
            'cp/core/framework',
            'cp/core/framework/Controllers/Ajax.php'
        );

        foreach($testPaths as $path) {
            $handler = new PathHandler($path);
            if($path === '/') {
                $this->assertIdentical(array(), $handler->getDavSegments());
            }
            else {
                $this->assertIdentical(explode('/', $path), $handler->getDavSegments());
            }
        }
    }

    function testIsArtificialPath() {
        $getArtificial = $this->getMethod('getArtificialPaths', array('/'));
        foreach($getArtificial() as $artificialPath => $children) {
            $handler = new PathHandler($artificialPath);
            $this->assertTrue($handler->isArtificialPath());
        }
    }

    function testGetBasePath() {
        $testPaths = array(
            'cp/core' => DOCROOT . '/cp/core',
            'cp/customer' => DOCROOT . '/cp/customer',
            'cp/generated' => DOCROOT . '/cp/generated',
            'cp/core/assets' => HTMLROOT . '/euf/core',
            'cp/logs' => \RightNow\Api::cfg_path() . '/log',
        );

        foreach($testPaths as $path => $basePath) {
            $handler = new PathHandler($path);
            $this->assertIdentical($handler->getBasePath(), $basePath);
        }
    }

    function testInsertWidgetVersion() {
        $testPaths = array(
            DOCROOT . '/cp/core/widgets/standard/input/TextInput/' => DOCROOT . '/cp/core/widgets/standard/input/TextInput/1.1.1',
            DOCROOT . '/cp/core/widgets/standard/input/TextInput/view.php' => DOCROOT . '/cp/core/widgets/standard/input/TextInput/1.1.1/view.php',
            DOCROOT . '/cp/core/widgets/standard/input' => false,
            DOCROOT . '/cp/customer/developments/widgets/standard/sample/SampleWidget/view.php' => false
        );

        $insertWidgetVersion = $this->getMethod('insertWidgetVersion', array('/'));
        foreach($testPaths as $path => $expected) {
            $this->assertIdentical($insertWidgetVersion($path, '1.1.1'), $expected);
        }
    }

    function testInsertFrameworkVersion() {
        $frameworkVersion = \RightNow\Utils\Framework::getFrameworkVersion();
        $testPaths = array(
            DOCROOT . '/cp/core/framework' => DOCROOT . "/cp/core/framework/$frameworkVersion",
            DOCROOT . '/cp/core/framework/Controllers/Ajax.php' => DOCROOT . "/cp/core/framework/$frameworkVersion/Controllers/Ajax.php",
            HTMLROOT . '/euf/core' => HTMLROOT . '/euf/core/' . CP_FRAMEWORK_VERSION,
            HTMLROOT . '/euf/core/debug-js' => HTMLROOT . '/euf/core/' . CP_FRAMEWORK_VERSION . '/debug-js'
        );

        list($object, $insertFrameworkVersion, $testingProperty) = $this->reflect('method:insertFrameworkVersion', 'isTesting');
        $handler = $object->newInstanceArgs(array('/'));
        $testingProperty->setValue($handler, true);
        foreach($testPaths as $path => $expected) {
            $this->assertIdentical($insertFrameworkVersion->invoke($handler, $path), $expected);
        }
    }

    function testIsDirectory() {
        $testPaths = array(
            'cp/core' => true,
            'cp/customer' => true,
            'cp/core/framework/Controllers/Ajax.php' => false
        );

        foreach($testPaths as $path => $expected) {
            $handler = new PathHandler($path);
            $this->assertIdentical($handler->isDirectory(), $expected);
        }
    }

    function testGetSize() {
        $fileName = rand() . time();
        $testString = 'This is a test string.';
        file_put_contents(CPCORE . $fileName, $testString);

        //Written file should have correct length
        $handler = new PathHandler("cp/core/framework/$fileName");
        $this->assertIdentical(intval($handler->getSize()), strlen($testString));
        unlink(CPCORE . $fileName);

        //Artificial file should have constant length
        $handler = new PathHandler('/');
        $this->assertIdentical($handler->getSize(), '4096');
    }

    function testGetCreationTime() {
        $writeTime = time();
        $fileName = rand() . $writeTime;
        $testString = 'This is a test string.';
        file_put_contents(CPCORE . $fileName, $testString);

        //Written file creation time should match
        $handler = new PathHandler("cp/core/framework/$fileName");
        $creationTime = intval($handler->getCreationTime());
        $this->assertTrue($creationTime >= $writeTime && $creationTime <= $writeTime + 10);
        unlink(CPCORE . $fileName);

        //Artificial file should have `time` creation time
        $handler = new PathHandler('/');
        $creationTime = $handler->getCreationTime();
        $currentTime = time();
        $this->assertTrue($creationTime >= $currentTime && $creationTime <= $writeTime + 10);
    }

    function testGetModifiedTime() {
        $writeTime = time();
        $fileName = rand() . $writeTime;
        $testString = 'This is a test string.';
        file_put_contents(CPCORE . $fileName, $testString);

        //Written file modified time should match
        $handler = new PathHandler("cp/core/framework/$fileName");
        $modifiedTime = intval($handler->getModifiedTime(false));
        $timeDifference = abs($modifiedTime - $writeTime);
        $this->assertTrue(($timeDifference === 1) || ($timeDifference === 0), sprintf("Times differ too much (modified: %d, write: %d, difference: %d)", $modifiedTime, $writeTime, $timeDifference));
        unlink(CPCORE . $fileName);

        //Artificial file should have `time` modified time
        $handler = new PathHandler('/');
        $this->assertIdentical($handler->getModifiedTime(false), time());
    }

    function testFileExists() {
        //Written file should exist
        $fileName = rand() . time();
        $testString = 'This is a test string.';
        file_put_contents(CPCORE . $fileName, $testString);
        $handler = new PathHandler("cp/core/framework/$fileName");
        $this->assertTrue($handler->fileExists());
        unlink(CPCORE . $fileName);

        //Written folder should exist
        $fileName = rand() . time();
        mkdir(CPCORE . $fileName);
        $handler = new PathHandler("cp/core/framework/$fileName");
        $this->assertTrue($handler->fileExists());
        rmdir(CPCORE . $fileName);

        //Artificial file should always exist
        $handler = new PathHandler('/');
        $this->assertTrue($handler->fileExists());

        //Fake path should not exist
        $handler = new PathHandler("cp/core/framework/" . time() . rand());
        $this->assertFalse($handler->fileExists());
    }

    function testIsVisiblePath() {
        $hiddenPaths = array(
            //Directories
            'cp/core/compatibility',
            'cp/core/compatibility/',
            'cp/core/compatibility/Internal',
            'cp/core/framework/Controllers/Admin',
            'cp/core/framework/Internal',
            'cp/core/framework/Views/Admin',
            'cp/core/framework/Hooks',
            'cp/core/framework/Hooks/Clickstream.php',
            'cp/core/widgets/standard/search/AdvancedSearchDialog/optimized',
            'cp/core/util',
            'cp/generated/production/optimized',
            'cp/generated/production/assets/templates',
            'cp/generated/production/assets/pages',
            'cp/generated/staging/optimized',
            'cp/core/assets/admin',
            'cp/core/assets/css',
            'cp/core/assets/js',
            'cp/core/assets/js/',

            //Specific Files
            'cp/core/compatibility/ActionCapture.php',
            'cp/core/framework/Controllers/AnswerPreview.php',
            'cp/core/framework/Controllers/InlineImage.php',
            'cp/core/compatibility/Controllers/InlineImg.php',
            'cp/core/framework/Controllers/Dqa.php',
            'cp/core/framework/Models/Pageset.php',
            'cp/core/framework/init.php',
            'cp/core/framework/optimized_includes.php',
            'cp/core/compatibility/optimized_includes.php',
            'cp/customer/development/widgetVersions',
            'cp/customer/development/frameworkVersion',
            'cp/customer/development/versionAuditLog',
            'cp/core/cpHistory',
            'cp/generated/production/source/config/pageSetMapping.php',
            'cp/generated/staging/source/config/pageSetMapping.php',
            'cp/generated/production/deployTimestamp',
            'cp/generated/staging/deployTimestamp',

            //General Files
            'cp/logs/dqa',
            'cp/core/.cvsignore',
            'cp/core/.#foo.php.1.2.5'
        );

        $visiblePaths = array(
            //Directories
            'cp/core/framework/Views',
            'cp/core/framework/Views/Partials'
        );

        foreach($hiddenPaths as $path) {
            $handler = new PathHandler($path);
            $this->assertFalse($handler->isVisiblePath());
        }

        foreach($visiblePaths as $path) {
            $handler = new PathHandler($path);
            $this->assertTrue($handler->isVisiblePath());
        }
    }

    function testIsHiddenLogFile() {
        $method = $this->getMethod('isHiddenLogFile', array('cp/logs/deploy1234567890.log'));
        $this->assertFalse($method());

        $method = $this->getMethod('isHiddenLogFile', array('cp/logs/error_log.xml'));
        $this->assertTrue($method());
    }
}
