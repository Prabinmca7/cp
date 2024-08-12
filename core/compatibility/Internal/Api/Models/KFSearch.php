<?php

namespace RightNow\Api\Models;

use RightNow\Connect\Knowledge\v1 as KnowledgeFoundation,
    RightNow\Internal\Api\Libraries\KFSearchMapper,
    RightNow\Internal\Api\Response,
    RightNow\Internal\Utils\Version as Version;

require_once CORE_FILES . 'compatibility/Internal/Api/Libraries/KFSearchMapper.php';
require_once CORE_FILES . 'compatibility/Internal/Api/Models/Base.php';
require_once CORE_FILES . 'compatibility/Internal/Api/Response.php';

/**
 * Search model for Knowledge Foundation searches
 */
class KFSearch extends Base {

    const DEFAULT_LIMIT = 10;
    const DEFAULT_OFFSET = 0;
    const MAX_LIMIT = 100;
    const MAX_OFFSET = 1000;

    private $filterMethodMapping = array(
        'product'  => 'productFilter',
        'category' => 'categoryFilter',
    );

    private $filters = array(
        'query'     => array('value' => null, 'key' => 'kw', 'type' => 'query'),
        'sort'      => array('value' => null, 'key' => 'sort', 'type' => 'sort'),
        'direction' => array('value' => null, 'dir' => 'kw', 'direction' => 'query'),
        'page'      => array('value' => null, 'key' => 'page', 'type' => 'page'),
        'product'   => array('value' => null, 'key' => 'p', 'type' => 'product'),
        'category'  => array('value' => null, 'key' => 'c', 'type' => 'category'),
        'offset'    => array('value' => self::DEFAULT_OFFSET, 'key' => null, 'type' => 'offset'),
        'limit'     => array('value' => self::DEFAULT_LIMIT)
    );

    /**
     * Searches KFAPI.
     * @param array $filters Optional filters
     *                         -limit: int
     *                         -offset: int
     *                         -sort: int
     *                         -direction: int
     *                         -product: int id
     *                         -category: int id
     * @return SearchResults A SearchResults object instance
     */
    function execute (array $filters) {
        foreach($this->filters as $filterName => $filterValue){
            if(array_key_exists($filterName, $filters)) {
                $this->filters[$filterName]['value'] = $filters[$filterName];
            }
        }
        $searchResults = $this->performContentSearch($this->filters);
        return Response::getResponseObject(KFSearchMapper::toSearchResults($searchResults, $this->filters), 'is_object', is_string($searchResults) ? $searchResults : null);
    }

    /**
     * For the given filter type name, returns the
     * values for the filter.
     * @param  string $filterType Filter type
     * @return array             Filter values
     */
    function getFilterValuesForFilterType ($filterType) {
        $sortOption = new KnowledgeFoundation\ContentSortOptions();
        $metaData = $sortOption::getMetadata();

        if ($filterType === 'sort') {
            $result = $metaData->SortField->named_values;
        }
        else if ($filterType === 'direction') {
            $result = $metaData->SortOrder->named_values;
        }

        return $this->getResponseObject($result, 'is_array');
    }

    /**
     * Constructs a SortOptions object.
     * @param int|null $sort Sort field id
     * @param int|null $direction Sort direction
     * @param string $query Query for sort. Used in inherited classes;
     *  here for method consistency as prefered by PHP.
     * @return object|null ContentSortOptions or null if $sort is falsey
     */
    protected function sortFilter ($sort, $direction, $query = '') {
        // Appease code sniffer
        $query;
        if ($sort) {
            $sortOption = new KnowledgeFoundation\ContentSortOptions();
            try {
                if($this->connectVersion == 1.4) {
                    $sortOption->SortOrder = new \RightNow\Connect\v1_4\NamedIDOptList();
                    $sortOption->SortOrder->ID = $direction ?: 1;

                    $sortOption->SortField = new \RightNow\Connect\v1_4\NamedIDOptList();
                    $sortOption->SortField->ID = $sort;
                }
                else {
                    $sortOption->SortOrder = new \RightNow\Connect\v1_3\NamedIDOptList();
                    $sortOption->SortOrder->ID = $direction ?: 1;

                    $sortOption->SortField = new \RightNow\Connect\v1_3\NamedIDOptList();
                    $sortOption->SortField->ID = $sort;
                }
            }
            catch(Exception $err) {
                return Response::generateResponseObject(null, null, $err->getMessage());
            }

            return $sortOption;
        }
        return null;
    }

    /**
     * Returns a normalized limit value
     * @param  int $limit Limit value
     * @return int        Limit, normalized
     */
    protected function limit ($limit) {
        $limit = $limit ?: self::DEFAULT_LIMIT;
        $limit = max(min($limit, self::MAX_LIMIT), 1);
        return $limit;
    }

    /**
     * Returns a normalized offset value
     * @param  int $offset Offset value
     * @return int         Offset, normalized
     */
    protected function offset ($offset) {
        $offset = $offset ?: self::DEFAULT_OFFSET;
        $offset = max(min($offset, self::MAX_OFFSET), self::DEFAULT_OFFSET);
        return $offset;
    }

    /**
     * Performs KF answer search
     * @param array $filters Filter values
     * @return string|array Error message or results
     */
    private function performContentSearch (array $filters) {
        if (!$query = trim($filters['query']['value'])) return 'Query is required';

        $contentSearch = new KnowledgeFoundation\ContentSearch();
        $this->addKnowledgeApiSecurityFilter($contentSearch);
        $contentSearch->Filters = $this->filters($filters);

        $kfFilters = array(
            'limit'  => $this->limit($filters['limit']['value']),
            'offset' => $this->offset($filters['offset']['value']),
        );
        $sortOptions = $this->sortFilter($filters['sort']['value'], $filters['direction']['value']);

        try {
            $result = $contentSearch->searchContent($this->getKnowledgeApiSessionToken(), $query, null,
                $sortOptions, $kfFilters['limit'], $kfFilters['offset']);
        }
        catch (\Exception $e) {
            return $e->getMessage();
        }

        return $result;
    }

    /**
     * Constructs an array of filters.
     * @param array $filters Filter values
     * @return object ContentFilterArray
     */
    private function filters (array $filters) {
        $kfFilters = new KnowledgeFoundation\ContentFilterArray();

        foreach ($filters as $type => $filterArray) {
            $method = $this->filterMethodMapping[$type];
            if ($method && ($filter = $this->$method($filterArray['value']))) {
                $kfFilters []= $filter;
            }
        }

        return $kfFilters;
    }

    /**
     * Constructs a ServiceProductContentFilter
     * @param  int $id Product id
     * @return object|null     ServiceProductContentFilter
     */
    private function productFilter ($id) {
        if ($product = $this->CI->model('Prodcat')->get($id)->result) {
            $filter = new KnowledgeFoundation\ServiceProductContentFilter();
            $filter->ServiceProduct = $product;
            return $filter;
        }
    }

    /**
     * Constructs a ServiceCategoryContentFilter
     * @param  int $id Product id
     * @return object|null     ServiceCategoryContentFilter
     */
    private function categoryFilter ($id) {
        if ($category = $this->CI->model('Prodcat')->get($id)->result) {
            $filter = new KnowledgeFoundation\ServiceCategoryContentFilter();
            $filter->ServiceCategory = $category;
            return $filter;
        }
    }
}
