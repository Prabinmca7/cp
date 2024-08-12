<?

use RightNow\Libraries\Search,
    RightNow\Internal\Libraries\Search as InternalSearch;

RightNow\UnitTest\Helper::loadTestedFile(__FILE__);

class SearchLibraryTest extends CPTestCase {
    public $testingClass = 'RightNow\Libraries\Search';

    function setUp() {
        parent::setUp();
        Search::clearCache();
    }

    function testGetInstanceWithNoSourceIDs () {
        $result = Search::getInstance('');

        $this->assertIdentical(array(), $result->getFilters());
        $errors = $result->getErrors();
        $this->assertSame(1, count($errors));
        $this->assertStringContains($errors[0], 'No search sources');
    }

    function testGetInstanceWithNonExistentSourceIDs () {
        $result = Search::getInstance('bananas');

        $this->assertIdentical(array(), $result->getFilters());
        $errors = $result->getErrors();
        $this->assertSame(1, count($errors));
        $this->assertStringContains($errors[0], 'No search sources');
    }

    function testGetInstanceWithExistingSourceIDs () {
        $result = Search::getInstance('KFSearch');
        $this->assertIdentical(array_merge(array_keys(InternalSearch::$defaultFilters), array('offset')), array_keys($result->getFilters()));
        $this->assertIdentical(array(), $result->getErrors());
    }

    function testAddFilters () {
        $search = Search::getInstance('catharsis');
        $input = array(
            'bananas' => array('value' => 'now'),
        );
        $this->assertIsA($search->addFilters($input), $this->testingClass);

        $actual = $search->getFilters();
        $this->assertIdentical($input, $actual);
    }

    function testAddFiltersOverridesExistingFilters () {
        $search = Search::getInstance('catharsis');
        $input = array(
            'bananas' => array('value' => 'now'),
        );
        $this->assertIsA($search->addFilters($input), $this->testingClass);

        $input = array(
            'bananas' => array('value' => 'newvalue'),
        );
        $this->assertIsA($search->addFilters($input), $this->testingClass);

        $actual = $search->getFilters();
        $this->assertIdentical($input, $actual);
    }

    function testAddFiltersRecalculatesOffsetWhenLimitIsAdded () {
        $search = Search::getInstance('catharsis');
        $search->addFilters(array(
            'limit'  => array('value' => 20),
            'page'   => array('value' => 4),
            'offset' => array('value' => 1),
        ));
        $filters = $search->getFilters();
        $this->assertIdentical(60, $filters['offset']['value']);

        $search->addFilters(array(
            'limit'  => array('value' => 55),
        ));
        $filters = $search->getFilters();
        $this->assertIdentical(165, $filters['offset']['value']);
    }

    function testAddFiltersRecalculatesOffsetWhenPageIsAdded () {
        $search = Search::getInstance('catharsis');
        $search->addFilters(array(
            'limit'  => array('value' => 20),
            'page'   => array('value' => 4),
            'offset' => array('value' => 1),
        ));
        $filters = $search->getFilters();
        $this->assertIdentical(60, $filters['offset']['value']);

        $search->addFilters(array(
            'page'  => array('value' => 5),
        ));
        $filters = $search->getFilters();
        $this->assertIdentical(80, $filters['offset']['value']);
    }

    function testPageIsSetToOneIfLessThanOne () {
        $search = Search::getInstance('catharsis');
        $search->addFilters(array('page' => array('value' => '-1')));
        $filter = $search->getFilter('page');
        $this->assertIdentical(1, $filter['value']);
        $search->addFilters(array('page' => array('value' => 0)));
        $filter = $search->getFilter('page');
        $this->assertIdentical(1, $filter['value']);
    }

    function testGetFilter () {
        $search = Search::getInstance('catharsis');
        $input = array(
            'bananas' => array('value' => 'now'),
        );
        $search->addFilters($input);
        $this->assertIdentical($input['bananas'], $search->getFilter('bananas'));
    }

    function testGetSources () {
        $search = Search::getInstance('drive');
        $this->assertIdentical(array(), $search->getSources());

        $search = Search::getInstance('KFSearch');
        $sources = $search->getSources();
        $this->assertSame(1, count($sources));
        $this->assertIdentical(array('KFSearch'), array_keys($sources));
        $this->assertIsA($sources['KFSearch']['endpoint'], 'string');
        $this->assertIsA($sources['KFSearch']['model'], 'string');
        $this->assertIsA($sources['KFSearch']['filters'], 'array');
    }

    function testExecuteSearch () {
        $search = Search::getInstance('KFSearch')->addFilters(array('query' => array('value' => 'phone')));

        $result = $search->executeSearch();

        $this->assertIsA($result, 'RightNow\Libraries\SearchResults');
        $this->assertIdentical($search->getFilters(), $result->filters);
        $this->assertTrue(count($result->results) > 0);
    }

    function testGetFilterValuesForFilterType () {
        $search = Search::getInstance('KFSearch');

        $result = $search->getFilterValuesForFilterType('sort');
        $this->assertTrue(count($result) > 0);

        $result = $search->getFilterValuesForFilterType('direction');
        $this->assertTrue(count($result) > 0);

        $result = $search->getFilterValuesForFilterType('bananas');
        $this->assertIdentical(array(), $result);
    }

    function testCallOnModelsForSources () {
        $search = Search::getInstance('KFSearch');
        $class = new \ReflectionClass($this->testingClass);
        $method = $class->getMethod('callOnModelsForSources');
        $method->setAccessible(true);

        $this->assertIdentical(array("The 'blahblah' model could not be found"), $method->invokeArgs($search, array(array('KFSearch' => array('model' => 'blahblah')), function() {})));
    }
}
