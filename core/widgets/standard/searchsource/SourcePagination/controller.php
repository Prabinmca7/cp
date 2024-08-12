<?php
namespace RightNow\Widgets;

use RightNow\Libraries\Search;

class SourcePagination extends \RightNow\Libraries\Widget\Base {
    function __construct ($attrs) {
        parent::__construct($attrs);
    }

    function getData () {
        if ($this->sourceError()) return false;

        $search = Search::getInstance($this->data['attrs']['source_id']);
        $results = $search->addFilters(array('limit' => array('value' => isset($this->data['attrs']['per_page']) ? $this->data['attrs']['per_page'] : 10)))->executeSearch();

        if ($errors = $search->getErrors()) {
            foreach ($errors as $error)
                echo $this->reportError($error);
            return false;
        }

        $this->data['js'] = $this->populateJsData($results, $search->getFilters());
    }

    /**
     * Utility function to format search result data for use in the widget's JavaScript
     * @param \RightNow\Libraries\SearchResults $results Search results
     * @param array $filters Filters used to execute the search
     * @return array Array of data formatted to be accessed easily in widget's JavaScript
     */
    protected function populateJsData ($results, array $filters) {
        return array(
            'size'          => $results->size,
            'total'         => $results->total,
            'offset'        => $results->offset,
            'currentPage'   => $filters['page']['value'],
            'numberOfPages' => $results->size ? (int)ceil($results->total / $filters['limit']['value']) : 0,
            'filter'        => $filters['page'],
            'limit'         => $filters['limit']['value'],
        );
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
