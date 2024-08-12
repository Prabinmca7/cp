<?php
namespace RightNow\Widgets;

use RightNow\Libraries\Search;

class SourceResultListing extends \RightNow\Libraries\Widget\Base {
    function __construct ($attrs) {
        parent::__construct($attrs);

        $this->setAjaxHandlers(array('search_results_ajax' => 'getAjaxResults'));
    }

    function getData () {
        if ($this->sourceError()) return false;

        $this->data['results'] = $this->executeSearch(array('limit' => array('value' => $this->data['attrs']['per_page'])));
        $historyData = $this->gatherHistoryData($this->data['results']);
        $this->data['historyData'] = (count($historyData) > 0) ? json_encode($historyData) : null;

        if ($this->data['attrs']['hide_when_no_results'] && !$this->data['results']->total) {
            $this->classList->add('rn_Hidden');
        }
        
        $this->data['js']['filters']['f_tok'] = \RightNow\Utils\Framework::createTokenWithExpiration(0, false, false);
    }

    /**
     * AJAX handler that renders search results.
     * @param  array $params POST parameters
     */
    function getAjaxResults (array $params) {
        $filters = @json_decode($params['filters'], true) ?: array();
        $filters['limit'] = array('value' => $params['limit']);
        $results = $this->executeSearch($filters);
        $results->html = $this->render('Results', array('results' => $results->results, 'query' => $results->filters['query']['value']));

        if (isset($params['includeTopicWords']) &&
            $params['includeTopicWords'] == true && // May be 1 or true
            $filters['query']['value'] &&
            $topicWords = $this->CI->model('Report')->getTopicWords($filters['query']['value'])->result) {
            $results->topicWords = $topicWords;
        }

        $this->renderJSON($results->toArray());
    }

    /**
     * Performs the search, applying the given filters. Sets the `filters`
     * and `sources` properties on the `js` data array.
     * @param array $filters Search filters
     * @return object \RightNow\Libraries\SearchResults instance
     */
    protected function executeSearch(array $filters) {
        $search = Search::getInstance($this->data['attrs']['source_id']);
        $results = $search->addFilters($filters)->executeSearch();
        $this->data['js'] = array(
            'filters' => $search->getFilters(),
            'sources' => $search->getSources(),
        );

        return $results;
    }

    /**
     * Gathers data needed for history management. Returns a subset of values from
     * search results for this purpose.
     * @param array $results Search results
     * @return array Results array with specific key/value pairs.
     */
    private function gatherHistoryData($results) {
        $resultArray = $results->toArray();
        $keysNeeded = array('filters', 'offset', 'query', 'results', 'size', 'total');
        $historyData = array();
        foreach($keysNeeded as $key) {
            $historyData[$key] = $resultArray[$key];
        }
        return $historyData;
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
