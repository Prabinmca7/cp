<?php

RightNow\UnitTest\Helper::loadTestedFile(__FILE__);

class KFSearchModelTest extends CPTestCase {
    public $testingClass = 'RightNow\Api\Models\KFSearch';

    function __construct() {
        parent::__construct();
        $this->model = new RightNow\Api\Models\KFSearch;
    }

    function testSearch () {
        $result = $this->model->execute(array('query' => 'phone'));
        $this->assertSame(10, $result->result->size);
    }

    function testSearchWithSort () {
        $result = $this->model->execute(array(
            'query' => 'phone',
            'sort' => 1,
            'direction' => 1,
        ));

        $this->assertSame(10, $result->result->size);
    }

    function testSearchWithProdFilter () {
        $result = $this->model->execute(array(
            'query' => 'phone',
            'product' => 3,
        ));
        $this->assertSame(3, $result->result->size);
        $this->assertSame(3, count($result->result->results));
    }

    function testSearchWithCatFilter () {
        $result = $this->model->execute(array(
            'query' => 'phone',
            'product' => null,
            'category' => 161,
        ));
        $this->assertSame(9, $result->result->size);
        $this->assertSame(9, count($result->result->results));
    }

    function testSearchWithLimit () {
        $result = $this->model->execute(array(
            'query' => 'phone',
            'category' => null,
            'limit' => 3,
        ));

        $this->assertSame(3, $result->result->size);
        $this->assertSame(3, count($result->result->results));
    }

    function testSearchWithOffset () {
        $result = $this->model->execute(array(
            'query' => 'phone',
            'offset' => 11,
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
