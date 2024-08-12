<?php
namespace RightNow\Widgets;

class CommunitySearchResults extends \RightNow\Libraries\Widget\Base {
    function __construct($attrs) {
        parent::__construct($attrs);
        $this->setAjaxHandlers(array(
            'community_search_ajax' => 'getCommunityData',
        ));
    }

    function getData() {
        if (!\RightNow\Utils\Config::getConfig(COMMUNITY_ENABLED, 'RNW')) {
            echo $this->reportError(\RightNow\Utils\Config::getMessage(SOCIAL_ENABLED_CFG_SET_ENABLED_MSG));
            return false;
        }
        if (\RightNow\Utils\Config::getConfig(COMMUNITY_BASE_URL, 'RNW') === '') {
            echo $this->reportError(\RightNow\Utils\Config::getMessage(SOCIAL_BASE_URL_CFG_SET_SET_MSG));
            return false;
        }

        $this->data['baseUrl'] = $this->data['attrs']['author_link_base_url'] ?: \RightNow\Utils\Config::getConfig(COMMUNITY_BASE_URL, 'RNW');

        $page = intval(\RightNow\Utils\Url::getParameter('page'));
        $keyword = \RightNow\Utils\Url::getParameter('kw');
        $this->data['js']['searchTerm'] = $keyword ?: '';

        $results = $this->getResults(array('keyword' => $keyword, 'page' => $page));

        $this->data['results'] = $results->searchResults;
        $this->data['js']['fullResultsUrl'] = $this->data['baseUrl']
            . '/search?find=' . \RightNow\Utils\Url::getParameter('kw')
            . (($this->data['attrs']['resource_id']) ? '&amp;hiveHash=' . $this->data['attrs']['resource_id'] : '')
            . \RightNow\Utils\Url::communitySsoToken('&amp;');

        if (!$results->totalCount && $this->data['attrs']['hide_when_no_results']) {
            $this->classList->add('rn_Hidden');
        }

        if ($this->data['attrs']['pagination_enabled']) {
            $this->computePagination($results->totalCount, $page);
        }
    }

    /**
     * Retrieves community data based on the parameters passed in for Ajax requests. Echos out JSON encoded results
     * @param array|null $params Post data
     */
    function getCommunityData($params) {
        $this->renderJSON($this->getResults($params));
    }

    /**
     * Returns community search results.
     * @param array|null $params The parameters used to perform a Social search
     * @return array An array of results or null if no results
     */
    protected function getResults($params) {
        if ($pageNumber = $params['page']) {
            // Note: $start index below begins with 1 as that is what the community api expects
            $start = max((($this->data['attrs']['limit'] * ($pageNumber - 1)) + 1), 1);
        }
        // contains 'searchResults' and 'totalCount' members
        $results = $this->CI->model('Social')->performSearch(
            $params['keyword'],
            $this->data['attrs']['limit'],
            null, // sort
            $this->data['attrs']['resource_id'],
            null, // user to filter
            $start
        )->result;
        if($results->searchResults){
            $results->searchResults = $this->CI->model('Social')->formatSearchResults(
                $results->searchResults,
                $this->data['attrs']['truncate_size'],
                $this->data['attrs']['highlight'],
                $params['keyword'],
                $this->data['attrs']['post_link_base_url']
            )->result;
        }

        return $results;
    }

    /**
     * Sets data for pagination.
     * @param int $totalResults Number of total results
     * @param int $pageNumber Current page number. If not specified, defaults to 1.
     */
    protected function computePagination($totalResults, $pageNumber) {
        if ($totalResults && $totalResults > $this->data['attrs']['limit']) {
            $currentPage = ($pageNumber && $pageNumber > 0) ? $pageNumber : 1;
            $totalPages = ceil($totalResults / $this->data['attrs']['limit']);
            $maximumLinksToDisplay = $this->data['attrs']['maximum_page_links'];
            if ($maximumLinksToDisplay === 0) {
                $startPage = $endPage = $currentPage;
            }
            else if ($totalPages > $maximumLinksToDisplay) {
                //calculate how far the page links should be shifted based on the specified cutoff
                $split = round($maximumLinksToDisplay / 2);
                if ($currentPage <= $split) {
                    //selected page is halfway (or less) to max_pages, so just stop displaying
                    //links at the specified cutoff
                    $startPage = 1;
                    $endPage = $maximumLinksToDisplay;
                }
                else {
                    //selected page is is more than half of max_pages, so shift the window of page links
                    //by difference between the current page and halfway point
                    $offsetFromMiddle = $currentPage - $split;
                    $maxOffset = $offsetFromMiddle + $maximumLinksToDisplay;
                    if ($maxOffset <= $totalPages) {
                        //the shifted window hasn't hit up against the maximum number of pages of the data set
                        $startPage = 1 + $offsetFromMiddle;
                        $endPage = $maxOffset;
                    }
                    else {
                        //the shifted window hit up against the maximum number of pages of the data set,
                        //so stop at the maximum number of pages
                        $startPage = $totalPages - ($maximumLinksToDisplay - 1);
                        $endPage = $totalPages;
                    }
                }
            }
            else {
                $startPage = 1;
                $endPage = $totalPages;
            }
            $this->data['backClass'] = ($currentPage !== 1) ? '' : 'rn_Hidden';
            $this->data['forwardClass'] = ($endPage > 1 && $currentPage !== $totalPages) ? '' : 'rn_Hidden';
            $this->data['totalPages'] = $totalPages;
            $this->data['js']['currentPage'] = $currentPage;
            $this->data['js']['startPage'] = $startPage;
            $this->data['js']['endPage'] = $endPage;
        }
        else {
            $this->data['paginationClass'] = 'rn_Hidden';
        }
    }
}
