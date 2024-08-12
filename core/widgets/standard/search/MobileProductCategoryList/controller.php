<?php
namespace RightNow\Widgets;

class MobileProductCategoryList extends \RightNow\Widgets\ProductCategoryList {
    function __construct($attrs) {
        parent::__construct($attrs);
    }

    function getData() {
        if(parent::getData() === false)
            return false;

        // Convert results for two-column display
        $results = array();
        foreach($this->data['results'] as $resultGroup) {
            foreach($resultGroup as $key => $result) {
                $results[$result['id']] = $result;
            }
        }

        $this->data['results'] = $results;
    }
}
