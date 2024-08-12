<?php
namespace RightNow\Widgets;

use RightNow\Libraries\Search;

class SourceFilter extends \RightNow\Libraries\Widget\Base {
    function __construct($attrs) {
        parent::__construct($attrs);
    }

    // The view expects `this->data['options']` to be an array
    // data structure consisting of objects
    // with the following properties:
    // - ID : filter ID value (string/int)
    //
    // This method can be overridden to retrieve any other custom filter
    // values with this same data structure.
    function getData() {
        $search = Search::getInstance($this->data['attrs']['source_id']);

        $result = $search->getFilterValuesForFilterType($this->data['attrs']['filter_type']);

        if ($result) {
            $this->data['options'] = $result;
            $this->data['js']['filter'] = $search->getFilter($this->data['attrs']['filter_type']);
        }
        else if ($errors = $search->getErrors()) {
            foreach ($errors as $error)
                echo $this->reportError($error);
            return false;
        }
    }
}
