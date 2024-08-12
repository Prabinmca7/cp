<?

RightNow\UnitTest\Helper::loadTestedFile(__FILE__);

use RightNow\Internal\Libraries\Deployment\Assets\AssetGroup;

class AssetGroupTests extends CPTestCase {
    public $testingClass = 'RightNow\Internal\Libraries\Deployment\Assets\AssetGroup';

    function testConstructorNoArgs () {
        $group = new AssetGroup();
        $count = 0;

        foreach ($group as $asset) {
            $count++;
        }
        $this->assertSame(0, $count);
    }

    function testConstructorAssetArgs () {
        $assets = array(new MockAssetyAsset('future'), new MockAssetyAsset('sick'));
        $group = new AssetGroup($assets[0], $assets[1]);
        $count = 0;

        foreach ($group as $key => $asset) {
            $count++;
            $this->assertSame($assets[$key], $asset);
        }
        $this->assertSame(count($assets), $count);
    }

    function testConstructorArrayArgs () {
        $assets1 = array(new MockAssetyAsset('future'), new MockAssetyAsset('sick'));
        $assets2 = array(new MockAssetyAsset('neon'));
        $group = new AssetGroup($assets1, $assets2);
        $count = 0;

        foreach ($group as $key => $asset) {
            $count++;
            if ($assets1[$key]) {
                $this->assertSame($assets1[$key], $asset);
            }
            else {
                $this->assertSame($assets2[count($assets1) - $key], $asset);
            }
        }
        $this->assertSame(count($assets1) + count($assets2), $count);
    }

    function testConstructorAssetGroupArgs () {
        $assets1 = new AssetGroup(new MockAssetyAsset('future'), new MockAssetyAsset('sick'));
        $assets2 = new AssetGroup(new MockAssetyAsset('neon'));
        $group = new AssetGroup($assets1, $assets2);
        $count = 0;

        foreach ($group as $asset) {
            $count++;
            $this->assertTrue(method_exists($asset, 'minify'));
        }
        $this->assertSame(3, $count);
    }

    function testConstructorMixedArgs() {
        $group = new AssetGroup(new MockAssetyAsset('future'),
            array(new MockAssetyAsset('blues'), new MockAssetyAsset('arcade')), new AssetGroup(new MockAssetyAsset('empathy')));
        $count = 0;

        foreach ($group as $asset) {
            $count++;
            $this->assertTrue(method_exists($asset, 'minify'));
        }

        $this->assertSame(4, $count);
    }

    function testAdd () {
        $assets = array(new MockAssetyAsset('future'), new MockAssetyAsset('sick'));
        $group = new AssetGroup();
        $group->add($assets[0]);
        $group->add($assets[1]);
        $count = 0;

        foreach ($group as $index => $asset) {
            $count++;
            $this->assertSame($assets[$index], $asset);
        }
        $this->assertSame(count($assets), $count);
    }

    function testAddGroupOfAssets () {
        $assets = array(new MockAssetyAsset('future'), new MockAssetyAsset('sick'));
        $group = new AssetGroup();
        $group->addGroupOfAssets($assets);
        $count = 0;

        foreach ($group as $index => $asset) {
            $count++;
            $this->assertSame($assets[$index], $asset);
        }
        $this->assertSame(count($assets), $count);
    }
}

class MockAssetyAsset {
    function __construct($path) {
        $this->path = $path;
    }

    function minify() {}
}
