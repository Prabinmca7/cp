<?php

RightNow\UnitTest\Helper::loadTestedFile(__FILE__);

class TestSortList extends WidgetTestCase
{
    public $testingWidget = "standard/search/SortList";

    static $headers = array(
        array('heading' => 'Summary', 'width' => '15.77608', 'data_type' => 5, 'col_id' => 1, 'order' => 0, 'col_definition' => 'answers.summary', 'visible' => true, 'url_info' => '/app/answers/detail/a_id/&lt;5&gt;'),
        array('heading' => 'New or Updated', 'width' => '14.5038166', 'data_type' => 5, 'col_id' => 2, 'order' => 1, 'col_definition' => 'if (date_diff(date_trunc(sysdate(), DAYS), date_trunc(answers.created, DAYS)) / 86400 < $new, msg_lookup(5064), if(date_diff(date_trunc(sysdate(), DAYS), date_trunc(answers.updated, DAYS)) / 86400 < $updated, msg_lookup(6861)))', 'visible' => true),
        array('heading' => 'Description', 'width' => '12.9770994', 'data_type' => 6, 'col_id' => 3, 'order' => 2, 'col_definition' => 'answers.solution', 'visible' => true),
        array('heading' => 'Date Updated', 'width' => '27.7353687', 'data_type' => 4, 'col_id' => 4, 'order' => 3, 'col_definition' => 'answers.updated', 'visible' => true)
    );

    function testGetData()
    {
        \RightNow\Utils\Framework::removeCache("securityToken176");

        $this->createWidgetInstance();
        $data = $this->getWidgetData();

        $this->assertIdentical(array('headers', 'col_id', 'sort_direction', 'searchName'), array_keys($data['js']));
        $this->assertIdentical(self::$headers, $data['js']['headers']);
        $this->assertIdentical(-1, $data['js']['col_id']);
        $this->assertIdentical(1, $data['js']['sort_direction']);
        $this->assertIdentical('sort_args', $data['js']['searchName']);
    }

    function testSorting()
    {
        $this->addUrlParameters(array('sort' => '3,2'));

        $this->createWidgetInstance();
        $data = $this->getWidgetData();

        $this->assertIdentical(array('headers', 'col_id', 'sort_direction', 'searchName'), array_keys($data['js']));
        $this->assertIdentical(self::$headers, $data['js']['headers']);
        $this->assertIdentical(3, $data['js']['col_id']);
        $this->assertIdentical(2, $data['js']['sort_direction']);
        $this->assertIdentical('sort_args', $data['js']['searchName']);

        $this->restoreUrlParameters();
    }
}
