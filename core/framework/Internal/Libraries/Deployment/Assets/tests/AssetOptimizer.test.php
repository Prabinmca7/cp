<?
RightNow\UnitTest\Helper::loadTestedFile(__FILE__);

require_once CPCORE . 'Internal/Libraries/Deployment/Assets/AssetOrganizer.php';

use RightNow\Utils\Url,
    RightNow\Utils\Text,
    RightNow\Utils\FileSystem,
    RightNow\Internal\Libraries\Deployment\Assets\AssetOrganizer,
    RightNow\Internal\Libraries\Deployment\Assets\AssetOptimizer;

class AssetOptimizerTests extends CPTestCase {
    public $testingClass = 'RightNow\Internal\Libraries\Deployment\Assets\AssetOptimizer';

    function __construct($label = null) {
        parent::__construct($label);
        $this->docRoot = HTMLROOT;
        $this->outputPrefix = $this->docRoot . Url::getCoreAssetPath('js/' . MOD_BUILD_SP . '.' . MOD_BUILD_NUM) . '/min';
    }


    function testPrependFilePaths () {
        $input = array('a', 'b', 'c');
        $output = AssetOptimizer::prependFilePaths($input, '');
        $this->assertIdentical($input, $output);

        $output = AssetOptimizer::prependFilePaths($input, 'bananas');
        $this->assertIdentical(array('bananasa', 'bananasb', 'bananasc'), $output);
    }

    function testMinifyThrowsOnInvalidFilePath () {
        try {
            AssetOptimizer::minify("asdf", array('obfuscate' => true));
            $this->fail("Exception not hit");
        }
        catch (\Exception $e) {
            $message = $e->getMessage();
            $this->assertStringContains($message, "Cannot read: asdf");
            $this->assertStringContains($message, "1 error(s), 0 warning(s)");
        }
    }

    function testIsVendorFile () {
        $method = $this->getStaticMethod('isVendorFile');
        $this->assertFalse($method(''));
        $this->assertFalse($method('asdfsadf'));
        $this->assertFalse($method(Url::getCoreAssetPath()));
        $this->assertFalse($method(Url::getCoreAssetPath('bananas')));
        $this->assertFalse($method(Url::getCoreAssetPath('bananas/ejs')));
        $this->assertTrue($method(Url::getYUICodePath()));
        $this->assertTrue($method(Url::getYUICodePath('bananas/true')));
        $this->assertTrue($method(Url::getCoreAssetPath('ejs/min')));
    }
}
