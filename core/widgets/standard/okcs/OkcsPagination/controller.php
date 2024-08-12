<?php
namespace RightNow\Widgets;

use RightNow\Libraries\Search,
    RightNow\Utils\Url,
    RightNow\Utils\Okcs;

class OkcsPagination extends \RightNow\Widgets\SourcePagination {
    function __construct($attrs) {
        parent::__construct($attrs);
    }

    function getData () {
        if (!$this->helper('Okcs')->checkOkcsEnabledFlag($this->getPath(), $this)) {
            return false;
        }
        $results = new \stdClass();
        if($this->data['attrs']['source_id'] === 'OKCSBrowse') {
            if($this->data['attrs']['data_source'] === 'authoring_recommendations') {
                $search = Search::getInstance($this->data['attrs']['source_id']);
                $pageMore = $this->CI->model('Okcs')->getSortNotifications();
                $results->total = $pageMore;
                $this->data['js'] = $this->populateJsData($results, $search->getFilters());
            }
            else {
                $search = Search::getInstance($this->data['attrs']['source_id']);
                $pageMore = $this->CI->model('Okcs')->getIMArticles();
                $results->total = $pageMore;
                $this->data['js'] = $this->populateJsData($results, $search->getFilters());
            }
        }
        else {
            parent::getData();
        }
    }

    /**
    * Method to to populate pagination data
    * @param object $results Search results
    * @param array $filters List of filters
    * @return array Pagination Details
    */
    protected function populateJsData ($results, array $filters) {
        if($this->data['attrs']['source_id'] === 'OKCSSearch') {
            $pageSize = $results->size;
            $currentPage = intval($results->searchResults['page']);
            $currentPage = Url::getParameter('searchType') === 'newTab' ? $currentPage - 1 : $currentPage;
            $total = isset($results->searchResults['results']->results[0]->totalResults) ? intval($results->searchResults['results']->results[0]->totalResults) : 0;
        }
        else if ($this->data['attrs']['source_id'] === 'OKCSBrowse') {
            $pageSize = Url::getParameter('pageSize') !== null ? Url::getParameter('pageSize') : 0;
            $pageNumber = Url::getParameter('browsePage') !== null ? Url::getParameter('browsePage') : 0;
            if(Url::getParameter('browsePage') !== null) {
                $pageNumber = intval(Url::getParameter('browsePage'));
            }
            else {
                $pageNumber = 1;
            }
            $currentPage = intval($pageNumber);
            $total = $pageSize + $results->total;
        }
        return array(
            'size'          => $pageSize,
            'total'         => $total,
            'offset'        => isset($results->offset) ? $results->offset : null,
            'pageMore'      => intval(isset($results->searchResults) && isset($results->searchResults['pageMore']) ? $results->searchResults['pageMore'] : null),
            'currentPage'   => $currentPage,
            'numberOfPages' => 0,
            'sources'       => isset($results->sources) ? $results->sources : null,
            'filter'        => isset($filters['page']) ? $filters['page'] : null,
            'limit'         => isset($filters['limit']) && isset($filters['limit']['value']) ? $filters['limit']['value'] : null,
            'okcsAction'    => $this->data['attrs']['source_id'] === 'OKCSBrowse' ? 'browse' : ''
        );
    }
}
