<?

use RightNow\Internal\Libraries\Widget\Registry,
    RightNow\Internal\Libraries\Widget\Locator;

RightNow\UnitTest\Helper::loadTestedFile(__FILE__);

class LocatorTest extends CPTestCase {
    public $testingClass = 'RightNow\Internal\Libraries\Widget\Locator';

    function testGetWidgetsWhenWidgetDoesNotExist () {
        $locator = new Locator('bananas', '<rn:widget path="search/PrintPageLink"/>');
        $result = $locator->getWidgets();
        $this->assertIdentical(array(), $result);
    }

    function testGetWidgetsDoesNotMatchOtherRnTags () {
        $locator = new Locator();
        $locator->addContentToProcess('bananas', '<rn:widgts path="search/PrintPageLink"/>');
        $locator->addContentToProcess('bananas', '<rn:condition path="search/PrintPageLink">');
        $result = $locator->getWidgets();
        $this->assertIdentical(array(), $result);
    }

    function testGetWidgetsIncludesPluralWidgetsInDeclaration () {
        $locator = new Locator('bananas', '<rn:widgets path="utils/PrintPageLink"/>');
        $result = $locator->getWidgets();
        $this->assertSame(1, count($result));
        $this->assertTrue(array_key_exists('standard/utils/PrintPageLink', $result));
        $result = $result['standard/utils/PrintPageLink'];
        $this->assertIsA($result['view'], 'string');
        $this->assertIsA($result['meta'], 'array');
        $this->assertIdentical(array('bananas'), $result['referencedBy']);
    }

    function testGetWidgetsIncludesSubWidgets () {
        $locator = new Locator('greek', '<rn:widget path="search/AdvancedSearchDialog" />');
        $result = $locator->getWidgets();
        $this->assertTrue(count($result) > 4);
        $keys = array_keys($result);
        $this->assertSame('standard/search/AdvancedSearchDialog', $keys[0]);
    }

    function testGetWidgetsExcludesSubWidgets () {
        $locator = new Locator('world', '<rn:widget path="search/CombinedSearchResults" />', false);
        $result = $locator->getWidgets();
        $this->assertSame(1, count($result));
        $keys = array_keys($result);
        $this->assertSame('standard/search/CombinedSearchResults', $keys[0]);

        $locator = new Locator('world', '<rn:widget path="search/CombinedSearchResults" />', true);
        $result = $locator->getWidgets();
        $this->assertSame(2, count($result));
        $keys = array_keys($result);
        $this->assertSame('standard/reports/Multiline', $keys[0]);
        $this->assertSame('standard/search/CombinedSearchResults', $keys[1]);
    }

    function testGetWidgetsExcludesParentWidgetsButIncludesPartials () {
        $locator = new Locator('world', '<rn:widget path="custom/viewpartialtest/ExtendedCustomAnswerFeedback" />');
        $locator->includeParents = false;
        $result = $locator->getWidgets();

        // Widget's parent is excluded but its view partial is included.
        $this->assertIdentical(array(
            'custom/viewpartialtest/ExtendedCustomAnswerFeedback',
            'standard/utils/Blank',
        ), array_keys($result));

        $this->assertIdentical(array('world'), $result['custom/viewpartialtest/ExtendedCustomAnswerFeedback']['referencedBy']);
        $this->assertSame(1, count($result['standard/utils/Blank']['referencedBy']));
        $this->assertStringContains($result['standard/utils/Blank']['referencedBy'][0], '.html.php');
    }

    function testGetWidgetsIncludesExtendedViews () {
        $locator = new Locator('world', '<rn:widget path="search/CombinedSearchResults" />');
        $result = $locator->getWidgets();
        $this->assertSame(2, count($result));
        $keys = array_keys($result);
        // Pulls in parent view.
        $this->assertIdentical(array('standard/reports/Multiline', 'standard/search/CombinedSearchResults'), $keys);
        $widget = Registry::getWidgetPathInfo('standard/search/CombinedSearchResults');
        $this->assertIdentical(file_get_contents($widget->view), $result['standard/search/CombinedSearchResults']['view']);
    }

    function testGetNonReferencedParentWidgets () {
        $locator = new Locator('world', '<rn:widget path="search/CombinedSearchResults" />');
        $result = $locator->getWidgets();
        $this->assertSame(2, count($result));
        $keys = array_keys($result);
        $this->assertIdentical(array('standard/reports/Multiline', 'standard/search/CombinedSearchResults'), $keys);
        $result = $locator::removeNonReferencedParentWidgets($result);
        $keys = array_keys($result);
        $this->assertSame(1, count($result));
        $this->assertIdentical(array('standard/search/CombinedSearchResults'), $keys);

        //Multiline is referenced by CombinedSearchResults widget and from the page also
        $locator = new Locator('world', '<rn:widget path="search/CombinedSearchResults" /> <rn:widget path="reports/Multiline" />');
        $result = $locator->getWidgets();
        $this->assertSame(2, count($result));
        $keys = array_keys($result);
        $result = $locator::removeNonReferencedParentWidgets($result);
        $keys = array_keys($result);
        $this->assertSame(2, count($result));
        $this->assertIdentical(array('standard/reports/Multiline','standard/search/CombinedSearchResults'), $keys);

        //Multiline is referenced by CombinedSearchResults and MobileMultiline widgets
        $locator = new Locator('world', '<rn:widget path="search/CombinedSearchResults" /> <rn:widget path="reports/MobileMultiline" />');
        $result = $locator->getWidgets();
        $this->assertSame(3, count($result));
        $keys = array_keys($result);
        $result = $locator::removeNonReferencedParentWidgets($result);
        $keys = array_keys($result);
        $this->assertSame(2, count($result));
        $this->assertIdentical(array('standard/search/CombinedSearchResults', 'standard/reports/MobileMultiline'), $keys);
    }

    function testGetWidgetsIncludesPartials () {
        $locator = new Locator('trying', '<rn:widgets path="viewpartialtest/WidgetsInViewPartials"/>');
        $result = $locator->getWidgets();
        $this->assertSame(5, count($result));
        $this->assertIdentical(array(
            'custom/viewpartialtest/WidgetsInViewPartials',
            'standard/utils/PrintPageLink',
            'standard/input/SelectionInput',
            'custom/sample/SampleWidget',
            'standard/output/FieldDisplay',
            ), array_keys($result));
    }

    function testErrors () {
        $locator = new Locator('day', '<rn:widget path="nope/none"/>');
        $result = $locator->getWidgets();
        $this->assertIdentical(array(), $result);
        $this->assertIdentical(array('nope/none'), $locator->errors);
    }

    function testInvalidFramework () {
        $sampleWidgetInfoFilePath = CUSTOMER_FILES . 'widgets/custom/sample/SampleWidget/1.0/info.yml';
        $sampleWidgetInfo = file_get_contents($sampleWidgetInfoFilePath);
        $newInfo = preg_replace('/framework: \[.*?\]/', 'framework: ["3.0"]', $sampleWidgetInfo);

        file_put_contents($sampleWidgetInfoFilePath, $newInfo);
        \RightNow\Internal\Utils\Widgets::killCacheVariables();
        Registry::initialize(true);

        $locator = new Locator('invalidFramework', '<rn:widgets path="sample/SampleWidget"/>');
        $result = $locator->getWidgets();
        $this->assertIdentical(array(), $result);
        $this->assertIdentical(array('sample/SampleWidget'), $locator->errors);

        file_put_contents($sampleWidgetInfoFilePath, $sampleWidgetInfo);
    }
}
