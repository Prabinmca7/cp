<?

use RightNow\Libraries\SearchResult;

RightNow\UnitTest\Helper::loadTestedFile(__FILE__);

class SearchResultTest extends CPTestCase {
    public $testingClass = 'RightNow\Libraries\SearchResult';

    function testConstructor () {
        $result = new SearchResult();
        $this->assertNull($result->type);

        $result = new SearchResult('hunger');
        $this->assertSame('hunger', $result->type);

        try {
            $result = new SearchResult(array('river'));
            $this->fail("Exception wasn't thrown");
        }
        catch (\Exception $e) {
            $this->pass();
        }
    }

    function testType () {
        $result = new SearchResult();

        $result->type = 'like';
        $this->assertSame('like', $result->type);
        $this->assertIsA($result->like, 'stdClass');

        try {
            $result->type = array('river');
            $this->fail("Exception wasn't thrown");
        }
        catch (\Exception $e) {
            $this->pass();
        }
    }

    function testToArray () {
        $result = new SearchResult();
        $result->url = 'bananas';
        $result->text = 'hey';
        $result->type = 'magic';
        $result->magic->hat = 'dogs';

        $actual = $result->toArray();

        $this->assertIsA($actual, 'array');
        $this->assertSame('dogs', $actual['magic']->hat);
        $this->assertSame('bananas', $actual['url']);
        $this->assertTrue(array_key_exists('summary', $actual));
        $this->assertNull($actual['summary']);
    }
}
