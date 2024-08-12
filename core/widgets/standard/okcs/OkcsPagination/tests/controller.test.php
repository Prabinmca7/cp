<?php

use RightNow\Utils\Text as Text;
use RightNow\Connect\v1_4 as Connect;
require_once CORE_WIDGET_FILES . 'standard/searchsource/SourcePagination/controller.php';
RightNow\UnitTest\Helper::loadTestedFile(__FILE__);


class OkcsPaginationTest extends WidgetTestCase {
    public $testingWidget = "standard/okcs/OkcsPagination";

    function testPopulateJsDataOKCSBrowse () {
        \Rnow::updateConfig('OKCS_ENABLED', 1, true);
        $this->createWidgetInstance(array('source_id' => 'OKCSBrowse'));
        $populateJsData = $this->getWidgetMethod('populateJsData');
        $this->addUrlParameters(array('pageSize' => '4'));

        $results = (object)array(
            'total'  => 4,
            'offset' => 10,
        );
        $results->size = 12;
        $filters = array(
            'limit'       => array('value' => 10),
            'page'        => array('value' => 2),
        );
        $expected = array(
            'size'          => '4',
            'total'         => 8,
            'offset'        => 10,
            'pageMore'      => 0,
            'currentPage'   => 1,
            'numberOfPages' => 0,
            'sources'       => null,
            'filter'        => array('value' => 2),
            'limit'         => 10,
            'okcsAction'    => 'browse'
        );
        $this->assertIdentical($populateJsData($results, $filters), $expected);
        \Rnow::updateConfig('OKCS_ENABLED', 0, true);
    }
    
    function testPopulateJsDataOKCSSearch () {
        \Rnow::updateConfig('OKCS_ENABLED', 1, true);
        $this->createWidgetInstance(array('source_id' => 'OKCSSearch'));
        $populateJsData = $this->getWidgetMethod('populateJsData');

        $results = (object)array(
            'total'  => 4,
            'offset' => 10,
        );
        $results->size = 12;
        $filters = array(
            'limit'       => array('value' => 10),
            'page'        => array('value' => 2),
        );
        $expected = array(
            'size'          => 12,
            'total'         => 0,
            'offset'        => 10,
            'pageMore'      => 0,
            'currentPage'   => 0,
            'numberOfPages' => 0,
            'sources'       => null,
            'filter'        => array('value' => 2),
            'limit'         => 10,
            'okcsAction'    => ''
        );
        $this->assertIdentical($populateJsData($results, $filters), $expected);
        \Rnow::updateConfig('OKCS_ENABLED', 0, true);
    }
}
