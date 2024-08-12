<?php
namespace RightNow\Widgets;

use RightNow\Libraries\Search;

class SourceResultDetails extends \RightNow\Libraries\Widget\Base {
    function __construct($attrs) {
        parent::__construct($attrs);
    }

    function getData() {
        if ($this->sourceError()) return false;

        $search = Search::getInstance($this->data['attrs']['source_id']);
        $results = $search->addFilters(array('limit' => array('value' => $this->data['attrs']['per_page'])))->executeSearch();

        $this->data['results'] = $results;
    }

    /**
     * Checks for a source_id error. Emits an error
     * message if a problem is found.
     * @return boolean True if an error was encountered
     *                      False if all is good
     */
    private function sourceError () {
        if (\RightNow\Utils\Text::stringContains($this->data['attrs']['source_id'], ',')) {
            echo $this->reportError(\RightNow\Utils\Config::getMessage(THIS_WIDGET_ONLY_SUPPORTS_A_SNGL_I_UHK));
            return true;
        }

        return false;
    }
}
