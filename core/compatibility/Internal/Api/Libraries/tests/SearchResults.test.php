<?

use RightNow\Internal\Api\Libraries\SearchResult,
    RightNow\Internal\Api\Libraries\SearchResults;

RightNow\UnitTest\Helper::loadTestedFile(__FILE__);

require_once CORE_FILES . 'compatibility/Internal/Api/Libraries/SearchResult.php';

class SearchResultsLibraryTest extends CPTestCase {
    public $testingClass = 'RightNow\Internal\Api\Libraries\SearchResults';

    function testToArray () {
        $result = new SearchResult();
        $result->url = 'bananas';
        $result->text = 'hey';
        $result->type = 'magic';
        $result->magic->hat = 'dogs';

        $results = new SearchResults();
        $results->query = 'bananas';
        $results->results []= $result;

        $actual = $results->toArray();

        $this->assertIsA($actual, 'array');
        $this->assertSame('bananas', $actual['query']);
        $this->assertSame('dogs', $actual['results'][0]['magic']->hat);
    }
}
