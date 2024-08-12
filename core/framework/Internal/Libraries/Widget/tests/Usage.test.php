<?

RightNow\UnitTest\Helper::loadTestedFile(__FILE__);

use RightNow\Utils\Text,
    RightNow\Internal\Libraries\Widget\Usage;

class UsageTest extends CPTestCase {
    public $testingClass = 'RightNow\Internal\Libraries\Widget\Usage';

    function testConstructor () {
        $usage = new Usage('blah', MockCacheForUsageTest);

        $this->assertSame(1, count(MockCacheForUsageTest::$constructorCalledWith));
        $this->assertIsA(MockCacheForUsageTest::$constructorCalledWith[0], 'int');
    }

    function testGetReferences () {
        // Invalid widget.
        $usage = new Usage('nope', MockCacheForUsageTest);
        $result = $usage->getReferences();
        $this->assertFalse($result);

        // Result cached.
        MockCacheForUsageTest::$getShouldReturn = array(false, 22);
        $usage = new Usage('nope', MockCacheForUsageTest);
        $result = $usage->getReferences();
        $this->assertIdentical(array('Usage_WidgetReferences_nope'), MockCacheForUsageTest::$getCalledWith);
        $this->assertNull(MockCacheForUsageTest::$setCalledWith);
        $this->assertFalse($result);
        $this->assertSame(22, $usage->lastCheckTime);
        MockCacheForUsageTest::$getShouldReturn = null;

        // Valid widget, but not found in any views.
        $usage = new Usage('standard/utils/ProgressBar', MockCacheForUsageTest);
        $result = $usage->getReferences();
        // Rendering test files may get picked up
        if ($result === false) {
            $this->assertFalse($result);
            $this->assertFalse(is_dir(CUSTOMER_FILES . 'views/pages/unitTest/rendering/widgets/standard/utils/ProgressBar'));
        }
        else {
            $expected = array (
                'pages/unitTest/rendering/widgets/standard/utils/ProgressBar/tests/attrs.php' => 'view',
                'pages/unitTest/rendering/widgets/standard/utils/ProgressBar/tests/base.php' => 'view',
                'pages/unitTest/rendering/widgets/standard/utils/ProgressBar/tests/descriptions.php' => 'view',
                'pages/unitTest/rendering/widgets/standard/utils/ProgressBar/tests/productRegistration.php' => 'view',
            );
            $this->assertIdentical($expected, $result);
        }
        $this->assertTrue($usage->lastCheckTime > 100000);

        // Valid widget found in lots serveral views including other widget view partials.
        $usage = new Usage('standard/input/RichTextInput', MockCacheForUsageTest);
        $result = $usage->getReferences();

        foreach ($result as $path => $type) {
            if (Text::endsWith($path, '.html.php')) {
                $this->assertSame(Usage::STANDARD_WIDGET, $type);
            }
            else if (Text::beginsWith($path, 'pages')) {
                $this->assertSame(Usage::VIEW, $type);
            }
            else {
                $this->assertSame(Usage::STANDARD_WIDGET, $type);
            }
        }

        $types = array_values($result);
        $this->assertTrue(in_array(Usage::STANDARD_WIDGET, $types));
        $this->assertTrue(in_array(Usage::VIEW, $types));
    }

    function testGetReferencesReturnsOnlyActualWidgetTagViews () {
        $usage = new Usage('custom/viewpartialtest/CustomAnswerFeedback', MockCacheForUsageTest);
        $result = $usage->getReferences();

        if ($result) {
            // Rendering unit tests
            foreach (array_keys($result) as $viewPath) {

                $this->assertStringContains($viewPath, 'pages/unitTest/rendering/widgets/custom/viewpartialtest');
            }
        }
        else {
            $this->assertFalse(is_dir(CUSTOMER_FILES . 'views/pages/unitTest/rendering/widgets/custom/viewpartialtest'));
        }
    }

    function testGetReferenceInFileBaseCases () {
        $usage = new Usage('collapse', MockCacheForUsageTest);

        // Invalid type.
        try {
            $results = $usage->getReferenceInFile('pages/home.php', 'nope');
            $this->fail();
        }
        catch (\Exception $e) {
            $this->assertStringContains($e->getMessage(), 'nope');
        }

        // Invalid widget.
        try {
            $results = $usage->getReferenceInFile('pages/home.php', Usage::STANDARD_WIDGET);
            $this->fail();
        }
        catch (\Exception $e) {
            $this->assertStringContains($e->getMessage(), 'pages/home.php');
        }

        // 'collapse' wasn't found on the home page.
        $results = $usage->getReferenceInFile('pages/home.php', Usage::VIEW);
        $this->assertIdentical('', $results);
        $this->assertTrue($usage->lastCheckTime > 100000);
    }

    function testGetReferenceInFile () {
        $usage = new Usage('standard/navigation/NavigationTab', MockCacheForUsageTest);
        $match = Usage::$startWidgetPathMatch . 'navigation/NavigationTab' . Usage::$endWidgetPathMatch;

        $results = $usage->getReferenceInFile('templates/standard.php', Usage::VIEW);

        $this->assertTrue(strlen($results) > 0);
        $this->assertTrue($usage->lastCheckTime > 100000);
        $this->assertStringContains($results, $match);
        $this->assertStringDoesNotContain($results, Usage::$snippetBreak);

        // Snippet includes two lines above and below the match.
        $lines = explode("\n", $results);
        $this->assertStringDoesNotContain($lines[0], $match);
        $this->assertStringDoesNotContain($lines[1], $match);
        $this->assertStringContains($lines[2], $match);
        $this->assertStringDoesNotContain($lines[count($lines) - 1], $match);
        $this->assertStringDoesNotContain($lines[count($lines) - 2], $match);

        // Result is cached.
        $this->assertSame(MockCacheForUsageTest::$setCalledWith[1][0], $results);
        $this->assertSame($usage->lastCheckTime, MockCacheForUsageTest::$setCalledWith[1][1]);
        MockCacheForUsageTest::$getShouldReturn = MockCacheForUsageTest::$setCalledWith[1];
        $usage = new Usage('standard/navigation/NavigationTab', MockCacheForUsageTest);
        $results2 = $usage->getReferenceInFile('templates/standard.php', Usage::VIEW);
        $this->assertIdentical($results, $results2);
        $this->assertNull(MockCacheForUsageTest::$setCalledWith);
        MockCacheForUsageTest::$getShouldReturn = null;
    }

    function testGetReferenceInFileWorksWithPartials () {
        $usage = new Usage('feedback/SocialContentRating', MockCacheForUsageTest);
        Usage::$startWidgetPathMatch = "(:";
        Usage::$endWidgetPathMatch = ":)";
        $match = "(:feedback/SocialContentRating:)";

        $results = $usage->getReferenceInFile('standard/discussion/QuestionComments/ActionsToolbar.html.php', Usage::STANDARD_WIDGET);

        $this->assertStringContains($results, $match);
    }
}

class MockCacheForUsageTest {
    public static $getShouldReturn = null;
    public static $getCalledWith = null;
    public static $setCalledWith = null;
    public static $constructorCalledWith = null;

    function __construct() {
        self::$constructorCalledWith = func_get_args();
        self::$getCalledWith = null;
        self::$setCalledWith = null;
    }

    function get () {
        self::$getCalledWith = func_get_args();
        return self::$getShouldReturn;
    }

    function set () {
        self::$setCalledWith = func_get_args();
    }
}
