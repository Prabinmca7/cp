<?

RightNow\UnitTest\Helper::loadTestedFile(__FILE__);

class KFSearchTest extends CPTestCase {
    public $testingClass = 'RightNow\Models\KFSearch';

    function __construct() {
        parent::__construct();
        $this->model = new RightNow\Models\KFSearch;
    }

    function testSearch () {
        $result = $this->model->search(array('query' => array('value' => 'phone')));
        $this->assertSame(10, $result->result->size);
    }

    function testSearchWithSort () {
        $result = $this->model->search(array(
            'query' => array('value' => 'phone'),
            'sort' => array(
                'value' => 1,//'CreatedTime',
            ),
            'direction' => array('value' => 1),
        ));

        $this->assertSame(10, $result->result->size);
    }

    function testSearchWithProdFilter () {
        $result = $this->model->search(array(
            'query' => array('value' => 'phone'),
            'product' => array('value' => 3),
        ));
        $this->assertSame(3, $result->result->size);
        $this->assertSame(3, count($result->result->results));
    }

    function testSearchWithCatFilter () {
        $result = $this->model->search(array(
            'query' => array('value' => 'phone'),
            'category' => array('value' => 161),
        ));
        $this->assertSame(9, $result->result->size);
        $this->assertSame(9, count($result->result->results));
    }

    function testSearchWithLimit () {
        $result = $this->model->search(array(
            'query' => array('value' => 'phone'),
            'limit' => array('value' => 3),
        ));

        $this->assertSame(3, $result->result->size);
        $this->assertSame(3, count($result->result->results));
    }

    function testSearchWithOffset () {
        $result = $this->model->search(array(
            'query' => array('value' => 'phone'),
            'offset' => array('value' => 11),
        ));
        $this->assertTrue($result->result->size > 0);
        $this->assertTrue($result->result->size < 10);
    }

    function testSortFilter() {
        $method = $this->getMethod('sortFilter');

        $result = $method(null, null);
        $this->assertNull($result);

        $result = $method(null, 2);
        $this->assertNull($result);

        $result = $method(2, 2);
        $this->assertSame($result->SortField->ID, 2);
        $this->assertSame($result->SortOrder->ID, 2);

        $result = $method(2, null);
        $this->assertSame($result->SortField->ID, 2);
        $this->assertSame($result->SortOrder->ID, 1);
    }
}
