<?php
namespace RightNow\Widgets;
use RightNow\Libraries\Search;

class RecentSearches extends \RightNow\Libraries\Widget\Base {
    function __construct($attrs) {
        parent::__construct($attrs);
    }

    function getData() {
        if (!$this->helper('Okcs')->checkOkcsEnabledFlag($this->getPath(), $this) || !($this->CI->session->canSetSessionCookies())) {
            return false;
        }
        $search = Search::getInstance($this->data['attrs']['source_id']);
        $this->data['js'] = array('filter' => $search->getFilters(), 'sources' => $search->getSources());
        
        $recentSearches = $this->CI->model('Okcs')->getUpdatedRecentSearches($this->data['attrs']['no_of_suggestions']);
        $this->data['js']['recentSearches'] = $recentSearches;
    }
}