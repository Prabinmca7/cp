<?
RightNow\UnitTest\Helper::loadTestedFile(__FILE__);

require_once CPCORE . 'Internal/Utils/Deployment/CodeWriter.php';

use RightNow\Utils\Text,
    RightNow\Utils\FileSystem,
    RightNow\Internal\Libraries\Widget\Registry,
    RightNow\Internal\Utils\Deployment\WidgetHelperOptimization;

class WidgetHelperOptimizationTest extends CPTestCase {
    public $testingClass = 'RightNow\Internal\Utils\Deployment\WidgetHelperOptimization';

    function testBuildStandardSharedHelpers () {
        $result = WidgetHelperOptimization::buildStandardSharedHelpers();

        $helpers = array_merge(glob(CPCORE . 'Helpers/*.php'), glob(CPCORE . 'Helpers/**/*.php'));
        $helpers = array_filter($helpers, function ($path) {
            return !Text::endsWith($path, ".test.php");
        });
        $this->assertTrue(count($helpers) > 0);

        preg_match_all('/class [a-zA-Z]/', $result, $matches);
        $this->assertSame(count($helpers), count($matches[0]));

        preg_match_all('/namespace [a-zA-Z]/', $result, $matches);
        $this->assertSame(count($helpers), count($matches[0]));

        $this->assertStringDoesNotContain($result, "<?");
    }

    function testBuildCustomSharedHelpers () {
        $result = WidgetHelperOptimization::buildCustomSharedHelpers();

        $helpers = array_merge(glob(APPPATH . 'helpers/*.php'), glob(APPPATH . 'helpers/**/*.php'));
        $this->assertTrue(count($helpers) > 0);

        $this->assertTrue(strlen($result) > 0);

        // Old legacy sample_helper.php isn't a valid new helper, so it isn't included.
        preg_match_all('/class [a-zA-Z]/', $result, $matches);
        $this->assertTrue(count($helpers) > count($matches));

        preg_match_all('/namespace [a-zA-Z]/', $result, $matches);
        $this->assertTrue(count($helpers) > count($matches));

        $this->assertStringDoesNotContain($result, "<?");
    }

    function testValidateWidgetHelperContents () {
        $pathInfo = Registry::getWidgetPathInfo('standard/utils/Blank');
        $helperClass = 'RightNow\Helpers\BlankHelper';

        $result = WidgetHelperOptimization::validateWidgetHelperContents($pathInfo, '');
        $this->assertSame($helperClass, $result);

        $result = WidgetHelperOptimization::validateWidgetHelperContents($pathInfo, $helperClass);
        $this->assertSame($helperClass, $result);

        $result = WidgetHelperOptimization::validateWidgetHelperContents($pathInfo, "class {$helperClass}");
        $this->assertSame($helperClass, $result);

        $result = WidgetHelperOptimization::validateWidgetHelperContents($pathInfo, "namespace RightNow\Helpers; class BlankHelper");
        $this->assertTrue($result);
    }
}
