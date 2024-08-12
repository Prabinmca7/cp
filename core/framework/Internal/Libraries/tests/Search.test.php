<?

use RightNow\Internal\Libraries\Search;

RightNow\UnitTest\Helper::loadTestedFile(__FILE__);

class ConcreteChildOfSearch extends Search {
    function __construct(array $sourceIDs) {
        parent::__construct($sourceIDs);
    }
}

class InternalSearchLibraryTest extends CPTestCase {
    public $testingClass = 'ConcreteChildOfSearch';

    function __construct() {
        parent::__construct();

        $this->defaultFilters = array();

        foreach (Search::$defaultFilters as $type => $key) {
            $this->defaultFilters[$type] = array(
                'value' => null,
                'key'   => $key,
                'type'  => $type,
            );
        }
    }

    function testValidateSourceFiltersForNoSources () {
        $method = $this->getMethod('validateAndCombineFilters', array(array()));
        $result = $method(array());
        $this->assertIdentical($this->defaultFilters, $result);
    }

    function testValidateSourceFiltersForNoFilters () {
        $method = $this->getMethod('validateAndCombineFilters', array(array()));
        $result = $method(array('a' => array('filters' => array())));
        $this->assertIdentical($this->defaultFilters, $result);
    }

    function testValidateSourceFiltersForConflictingParams () {
        list($class, $method) = $this->reflect('method:validateAndCombineFilters');
        $instance = $class->newInstanceArgs(array(array()));
        $result = $method->invoke($instance, array(
            'foo' => array(
                'filters' => array('a' => 'a'),
            ),
            'bar' => array(
                'filters' => array('a' => 'b'),
            ),
        ));
        $warnings = $instance->warnings;
        $this->assertIdentical(array_merge(array('a' => array('value' => null, 'key' => 'b', 'type' => 'a')), $this->defaultFilters), $result);
        $this->assertSame(1, count($warnings));
        $this->assertStringContains($warnings[0], "a and b");
        $this->assertStringContains($warnings[0], "foo, bar");
    }

    function testValidateSourceFiltersForLegitMultiFilters () {
        list($class, $method) = $this->reflect('method:validateAndCombineFilters');
        $instance = $class->newInstanceArgs(array(array()));
        $result = $method->invoke($instance, array(
            'a' => array(
                'filters' => array('a' => 'b'),
            ),
            'b' => array(
                'filters' => array('b' => 'c'),
            ),
        ));
        $warnings = $instance->warnings;
        $this->assertIdentical(array_merge(
            array('a' => array('value' => null, 'key' => 'b', 'type' => 'a'), 'b' => array('value' => null, 'key' => 'c', 'type' => 'b')),
            $this->defaultFilters), $result);
        $this->assertIdentical(array(), $warnings);
    }

    function testValidateSourceFiltersForLegitSingleFilter () {
        list($class, $method) = $this->reflect('method:validateAndCombineFilters');
        $instance = $class->newInstanceArgs(array(array()));
        $result = $method->invoke($instance, array(
            'a' => array(
                'filters' => array('a' => 'b'),
            ),
        ));
        $warnings = $instance->warnings;
        $this->assertIdentical(array_merge(array('a' => array('value' => null, 'key' => 'b', 'type' => 'a')), $this->defaultFilters), $result);
        $this->assertIdentical(array(), $warnings);
    }
}
