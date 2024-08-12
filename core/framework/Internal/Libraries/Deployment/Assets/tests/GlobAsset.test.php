<?

RightNow\UnitTest\Helper::loadTestedFile(__FILE__);

use RightNow\Utils\Url,
    RightNow\Utils\Text,
    RightNow\Utils\FileSystem,
    RightNow\Internal\Libraries\Deployment\Assets\GlobAsset;

class GlobAssetTests extends CPTestCase {
    public $testingClass = 'RightNow\Internal\Libraries\Deployment\Assets\GlobAsset';

    function testConstructorBadGlobPath () {
        $group = new GlobAsset('big/wheel/moon/cat.css', 'big/wheel/star/cat.css');
        $count = 0;

        foreach ($group as $asset) {
            $count++;
        }
        $this->assertSame(0, $count);
    }

    function testConstructor () {
        $corePath = HTMLROOT . Url::getCoreAssetPath('debug-js');
        $globPath = $corePath . '/**/*.js';
        $output = '/eagles/sore/debug-js';
        $group = new GlobAsset($globPath, $output);
        $rawGlob = glob($globPath);
        foreach ($group as $index => $asset) {
            $this->assertSame($output . Text::getSubstringAfter($rawGlob[$index], $corePath), $asset->optimizedPath);
        }
    }
}
