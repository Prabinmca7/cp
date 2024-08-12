<?php
namespace RightNow\Widgets;

use RightNow\Libraries\Search;

class SourceSort extends \RightNow\Libraries\Widget\Base {
    function __construct($attrs) {
        parent::__construct($attrs);
    }

    function getData() {
        if ($this->sourceError()) return false;

        $search = Search::getInstance($this->data['attrs']['source_id']);

        $column = $search->getFilterValuesForFilterType('sort');
        $direction = $search->getFilterValuesForFilterType('direction');

        if ($column && $direction) {
            $this->data['options_column'] = $column;
            $this->data['js']['filter_column'] = $search->getFilter('sort');
            $this->data['options_direction'] = $direction;
            $this->data['js']['filter_direction'] = $search->getFilter('direction');
        }
        else if ($errors = $search->getErrors()) {
            foreach ($errors as $error)
                echo $this->reportError($error);
            return false;
        }
        else {
            return false;
        }
    }

    private function sourceError () {
        if (\RightNow\Utils\Text::stringContains($this->data['attrs']['source_id'], ',')) {
            echo $this->reportError(\RightNow\Utils\Config::getMessage(THIS_WIDGET_ONLY_SUPPORTS_A_SNGL_I_UHK));
            return true;
        }

        return false;
    }
}
