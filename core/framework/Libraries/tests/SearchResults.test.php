<?

use RightNow\Libraries\SearchResult,
    RightNow\Libraries\SearchResults;

RightNow\UnitTest\Helper::loadTestedFile(__FILE__);

require_once CPCORE . 'Libraries/SearchResult.php';

class SearchResultsTest extends CPTestCase {
    public $testingClass = 'RightNow\Libraries\SearchResults';

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
