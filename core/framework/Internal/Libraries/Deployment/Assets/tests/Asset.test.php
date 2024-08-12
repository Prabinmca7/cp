<?

RightNow\UnitTest\Helper::loadTestedFile(__FILE__);

use RightNow\Utils\Url,
    RightNow\Internal\Libraries\Deployment\Assets\Asset;

class AssetAssetTests extends CPTestCase {
    public $testingClass = 'RightNow\Internal\Libraries\Deployment\Assets\Asset';

    function testConstructor () {
        $asset = new Asset('foo', 'bar');
        $this->assertTrue(property_exists($asset, 'nonOptimizedPath'));
        $this->assertSame('bar', $asset->optimizedPath);
    }

    function testMinify () {
        $asset = new Asset(HTMLROOT . Url::getCoreAssetPath('debug-js/RightNow.js'), 'foo/bar');
        $obfuscated = $asset->minify();
        $this->assertIsA($obfuscated, 'string');
        $this->assertTrue(strlen($obfuscated) > 1);

        $asset = new Asset(HTMLROOT . Url::getCoreAssetPath('debug-js/RightNow.js'), 'foo/bar');
        $min = $asset->minify(false);
        $this->assertIsA($min, 'string');
        $this->assertTrue(strlen($min) > 1);
        // currently the minification and obfuscation are disabled, so this test fails (they are equal to each other)
        $this->assertTrue(strlen($obfuscated) < strlen($min));
    }
}
