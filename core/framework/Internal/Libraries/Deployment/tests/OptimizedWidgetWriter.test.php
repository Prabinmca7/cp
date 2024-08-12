<?php
RightNow\UnitTest\Helper::loadTestedFile(__FILE__);

require_once CPCORE . 'Internal/Utils/Deployment/CodeWriter.php';

use RightNow\Utils\Text,
    RightNow\Internal\Libraries\Deployment\OptimizedWidgetWriter as Writer;

class OptimizedWidgetWriterTest extends CPTestCase {
    public $testingClass = 'RightNow\Internal\Libraries\Deployment\OptimizedWidgetWriter';

    function tearDown () {
        $this->eraseTempDir();
    }

    function testConstructorForStandardWidget () {
        $widget = (object) array(
            'type'         => 'standard',
            'absolutePath' => '/inkind'
        );
        $writer = new Writer($widget);

        $this->assertSame('/inkind/optimized/optimizedWidget.js', $writer->jsPath);
        $this->assertSame('/inkind/optimized/optimizedWidget.php', $writer->phpPath);
        $this->assertSame('/inkind/optimized/', $writer->path);
    }

    function testConstructorForCustomWidget () {
        $widget = (object) array(
            'type'         => 'custom',
            'relativePath' => 'song',
            'version'      => '4.5',
        );
        $writer = new Writer($widget, 'window');

        $this->assertSame('window/widgets/song/4.5/optimized/', $writer->path);
        $this->assertSame('window/widgets/song/4.5/optimized/optimizedWidget.js', $writer->jsPath);
        $this->assertSame('window/widgets/song/4.5/optimized/optimizedWidget.php', $writer->phpPath);
    }

    function testWriteJavaScriptWhenLogicIsNotReadable () {
        $widget = (object) array(
            'type'         => 'standard',
            'absolutePath' => '/hannah',
        );
        $writer = new Writer($widget);
        $writer->jsPath = $this->getTestDir() . '/file';
        $this->assertFalse($writer->writeJavaScript('cruel'));
        $this->assertFalse(file_exists($writer->jsPath));
    }

    function testWriteJavaScript () {
        $logicFile = $this->getTestDir() . '/logic';
        $this->writeTempFile('logic', 'keep');
        $widget = (object) array(
            'type'         => 'standard',
            'absolutePath' => '/hannah',
            'logic'        => $logicFile,
        );

        $writer = new Writer($widget);
        $writer->jsPath = $this->getTestDir() . '/file';

        $this->assertTrue($writer->writeJavaScript('cruel'));
        $this->assertIdentical('cruel', file_get_contents($writer->jsPath));
    }

    function testWritePhp () {
        $widget = (object) array(
            'type'         => 'standard',
            'absolutePath' => '/hannah',
        );

        $writer = new Writer($widget);
        $writer->phpPath = $this->getTestDir() . '/file';

        $writer->writePhp('cruel', array());
        $this->assertIdentical('cruel', file_get_contents($writer->phpPath));
    }

    function testInjectPhpRequirements () {
        $widget = (object) array(
            'absolutePath' => '/history',
            'type'         => 'standard'
        );
        $method = $this->getMethod('injectPhpRequirements', array($widget));

        $actual = $method("<?\nnamespace RightNow\Foo\Bar;\nclass Stood{}", array('standard/utils/Blank'));
        $expected =<<<CODE
<?
namespace RightNow\Foo\Bar;
\RightNow\Utils\Widgets::requireOptimizedWidgetController("standard/utils/Blank");
class Stood{}
CODE;
        $this->assertIdentical($expected, $actual);
    }

    function testInjectPhpRequirementsForCustomWidgets () {
        $widget = (object) array(
            'absolutePath' => '/history',
            'type'         => 'custom'
        );
        $method = $this->getMethod('injectPhpRequirements', array($widget));

        $actual = $method("<?\nnamespace RightNow\Foo\Bar;\nclass Stood{}", array('custom/sample/SampleWidget'));
        $expected =<<<CODE
<?
namespace RightNow\Foo\Bar;
\RightNow\Utils\Widgets::requireOptimizedWidgetController("custom/sample/SampleWidget");
class Stood{}
CODE;
        $this->assertIdentical($expected, $actual);
    }
}
