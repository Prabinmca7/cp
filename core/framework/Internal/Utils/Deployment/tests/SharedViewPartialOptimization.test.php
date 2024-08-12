<?
RightNow\UnitTest\Helper::loadTestedFile(__FILE__);

require_once CPCORE . 'Internal/Utils/Deployment/CodeWriter.php';

use RightNow\Utils\Text,
    RightNow\Utils\FileSystem,
    RightNow\Internal\Utils\Deployment\SharedViewPartialOptimization;

class SharedViewPartialOptimizationTest extends CPTestCase {
    public $testingClass = 'RightNow\Internal\Utils\Deployment\SharedViewPartialOptimization';

    function testBuildSharedViewPartials() {
        $result = SharedViewPartialOptimization::buildSharedViewPartials();

        $partials = array_merge(glob(CPCORE . 'Views/Partials/*.html.php'), glob(CPCORE . 'Views/Partials/**/*.html.php'));
        $this->assertTrue(count($partials) > 0);

        foreach ($partials as $file) {
            $name = str_replace('/', '_', Text::getSubstringBefore(Text::getSubstringAfter($file, CPCORE . 'Views/Partials/'), '.html.php'));
            $this->assertStringContains($result, "static function {$name}_view");
        }
        $this->assertStringContains($result, "class SharedViewPartials");
        $this->assertStringDoesNotContain($result, "extends");
    }

    // TK - will need some sample custom partials (that are deployed?)
    function testBuildCustomSharedViewPartials() {
        $result = SharedViewPartialOptimization::buildCustomSharedViewPartials();

        $partials = array_merge(glob(APPPATH . 'views/Partials/*.html.php'), glob(APPPATH . 'views/Partials/**/*.html.php'));
        $this->assertTrue(count($partials) > 0);

        foreach ($partials as $file) {
            $name = str_replace('/', '_', Text::getSubstringBefore(Text::getSubstringAfter($file, APPPATH . 'views/Partials/'), '.html.php'));
            $this->assertStringContains($result, "static function $name");
        }
        $this->assertStringContains($result, "namespace Custom\Libraries\Widgets");
        $this->assertStringContains($result, "class CustomSharedViewPartials");
        $this->assertStringContains($result, "extends \RightNow\Libraries\Widgets\SharedViewPartials");
    }

    function testGetSharedViewPartialContent () {
        $method = $this->getStaticMethod('getSharedViewPartialContent');

        // Existing sample
        $result = $method(APPPATH . 'views/Partials', true);
        $this->assertIdentical(array('sample' => 'sample custom shared view partial'), $result);

        $this->writeTempDir();

        // Non existing dir.
        $result = $method($this->testDir);
        $this->assertIdentical(array(), $result);

        // Top-level file, override allowed.
        FileSystem::filePutContentsOrThrowExceptionOnFailure("{$this->testDir}/test.html.php", 'BANANAS');
        $result = $method($this->testDir);
        $this->assertIdentical(array('test' => 'BANANAS'), $result);

        // Top-level file, override not allowed.
        $result = $method($this->testDir, true);
        $this->assertIdentical(array('test' => false), $result);

        unlink("{$this->testDir}/test.html.php");

        FileSystem::mkdirOrThrowExceptionOnFailure("{$this->testDir}/Forms", true);
        FileSystem::filePutContentsOrThrowExceptionOnFailure("{$this->testDir}/Forms/RequiredLabel.html.php", 'Solely');

        // Deeper file, override allowed.
        $result = $method($this->testDir);
        $this->assertIdentical(array('Forms/RequiredLabel' => 'Solely'), $result);

        // Deeper file, override not allowed.
        $result = $method($this->testDir, true);
        $this->assertIdentical(array(), $result);

        $this->eraseTempDir();
    }
}
