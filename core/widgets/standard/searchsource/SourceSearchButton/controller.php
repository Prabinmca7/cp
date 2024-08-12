<?php
namespace RightNow\Widgets;

use RightNow\Libraries\Search;

class SourceSearchButton extends \RightNow\Libraries\Widget\Base {
    function __construct($attrs) {
        parent::__construct($attrs);
    }

    function getData() {
        $search = Search::getInstance($this->data['attrs']['source_id']);

        $this->data['js'] = array('sources' => $search->getSources());
    }
}
