<?
RightNow\UnitTest\Helper::loadTestedFile(__FILE__);

use RightNow\Utils\Url,
    RightNow\Internal\Libraries\Deployment\Assets\AssetOrganizer;

class AssetOrganizerTests extends CPTestCase {
    public $testingClass = 'RightNow\Internal\Libraries\Deployment\Assets\AssetOrganizer';

    function __construct($label = null) {
        parent::__construct($label);
        $this->docRoot = HTMLROOT;
        $this->outputPrefix = $this->docRoot . Url::getCoreAssetPath('js/' . MOD_BUILD_SP . '.' . MOD_BUILD_NUM) . '/min';
    }

    function verifyAssetValidity ($asset) {
        $class = new \ReflectionClass($asset);
        $nonOptimizedPath = $class->getProperty('nonOptimizedPath');
        $nonOptimizedPath->setAccessible(true);
        $inputPath = $nonOptimizedPath->getValue($asset);

        $this->assertTrue(method_exists($asset, 'minify'));
        $this->assertIsA($asset->optimizedPath, 'string');
        $this->assertTrue(is_string($inputPath) || is_array($inputPath));
        if (!\RightNow\Utils\Text::endsWith($asset->optimizedPath, '/RightNow.Compatibility.MarketingFeedback.js'))
            $this->assertBeginsWith($asset->optimizedPath, $this->outputPrefix);
        else
            $this->assertBeginsWith($asset->optimizedPath, $this->docRoot . Url::getCoreAssetPath('static'));
    }

    function verifyAssets ($assets) {
        $count = 0;
        foreach ($assets as $asset) {
            $count++;
            $this->verifyAssetValidity($asset);
        }
        return $count;
    }

    function testGetAllFrameworkJS () {
        $optimizer = new AssetOrganizer($this->docRoot);
        $result = $optimizer->getAllFrameworkJS();
        $count = $this->verifyAssets($result);
        // framework + modules + chat + MAv2 + MACompatibility + module glob
        $this->assertSame(5 + count(glob($this->docRoot . Url::getCoreAssetPath('debug-js/modules/**/*.js'))), $count);
    }

    function testGetFramework () {
        $method = $this->getMethod('getFramework', array($this->docRoot));
        $result = $method();
        $count = $this->verifyAssets($result);
        // standard + mobile
        $this->assertSame(2, $count);
        $this->assertEndsWith($result[0]->optimizedPath, 'RightNow.js');
        $this->assertEndsWith($result[1]->optimizedPath, 'RightNow.Mobile.js');
    }

    function testGetWidgetHelperModules () {
        $method = $this->getMethod('getWidgetHelperModules', array($this->docRoot));
        $result = $method();
        $count = $this->verifyAssets($result);
        $this->assertSame($count, count(glob($this->docRoot . Url::getCoreAssetPath('debug-js/modules/**/*.js'))));
    }

    function testGetChat () {
        $method = $this->getMethod('getChat', array($this->docRoot));
        $result = $method();
        $this->verifyAssetValidity($result);
        $this->assertEndsWith($result->optimizedPath, 'RightNow.Chat.js');
    }

    function testGetMAv2() {
        $method = $this->getMethod('getMAv2', array($this->docRoot));
        $result = $method();
        $this->verifyAssetValidity($result);
        $this->assertEndsWith($result->optimizedPath, 'RightNow.MarketingFeedback.js');
    }

    function testGetMACompatibility() {
        $method = $this->getMethod('getMACompatibility', array($this->docRoot));
        $result = $method();
        $this->verifyAssetValidity($result);
        $this->assertEndsWith($result->optimizedPath, 'RightNow.Compatibility.MarketingFeedback.js');
    }

    function testGetCustomAndDeprecated() {
        $method = $this->getMethod('getCustomAndDeprecated', array($this->docRoot));
        $this->assertNull($method('', ''));
        $result = $method(CUSTOMER_FILES, '/bullets/in/the/air');
        $this->assertSame('/bullets/in/the/air/custom/autoload.js', $result->optimizedPath);
    }
}
