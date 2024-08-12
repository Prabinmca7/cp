<?php
namespace RightNow\Widgets;

use RightNow\Libraries\Search;

class SummaryResultListing extends SourceResultListing {
    function __construct ($attrs) {
        parent::__construct($attrs);
    }

    function getData () {
        $this->data['results'] = $this->executeSearch(array('limit' => array('value' => $this->data['attrs']['per_page'])));
        if (isset($this->data['attrs']['hide_when_no_results']) && $this->data['attrs']['hide_when_no_results'] && !$this->data['results']->total) {
            $this->classList->add('rn_Hidden');
        }
        $this->data['js']['filters']['f_tok'] = \RightNow\Utils\Framework::createTokenWithExpiration(0, false, false);
    }

    /**
     * Performs the search, applying the given filters. Sets the `filters`
     * and `sources` properties on the `js` data array.
     * @param array $filters Search filters
     * @return object \RightNow\Libraries\SearchResults instance
     */
    function executeSearch(array $filters) {
        $this->data['attrs']['source_id'] = $this->data['attrs']['history_source_id'] = (($this->data['attrs']['results_type'] === 'Answers') ? 'KFSearch' : 'SocialSearch');
        $search = Search::getInstance($this->data['attrs']['source_id']);
        $results = $search->addFilters($filters)->executeSearch();
        $this->data['js'] = array(
            'filters' => $search->getFilters(),
            'sources' => $search->getSources(),
        );

        return $results;
    }
}