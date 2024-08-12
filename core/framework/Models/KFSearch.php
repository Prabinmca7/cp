<?

namespace RightNow\Models;

use RightNow\Utils\Text,
    RightNow\Utils\Framework,
    RightNow\Connect\v1_4 as Connect,
    RightNow\Connect\Knowledge\v1 as KnowledgeFoundation,
    RightNow\Libraries\SearchMappers\KFSearchMapper;

require_once CPCORE . 'Libraries/SearchMappers/KFSearchMapper.php';

/**
 * Search model for Knowledge Foundation searches
 */
class KFSearch extends SearchSourceBase {
    private $filterMethodMapping = array(
        'product'  => 'productFilter',
        'category' => 'categoryFilter',
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
    function search (array $filters = array()) {
        $searchResults = $this->performContentSearch($filters);

        return $this->getResponseObject(KFSearchMapper::toSearchResults($searchResults, $filters), 'is_object', is_string($searchResults) ? $searchResults : null);
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

            $sortOption->SortOrder = new Connect\NamedIDOptList();
            $sortOption->SortOrder->ID = $direction ?: 1;

            $sortOption->SortField = new Connect\NamedIDOptList();
            $sortOption->SortField->ID = $sort;

            return $sortOption;
        }
    }

    /**
     * Returns a normalized limit value
     * @param  int $limit Limit value
     * @return int        Limit, normalized
     */
    protected function limit ($limit) {
        $limit = $limit ?: 10;
        $limit = max(min($limit, 100), 1);
        return $limit;
    }

    /**
     * Returns a normalized offset value
     * @param  int $offset Offset value
     * @return int         Offset, normalized
     */
    protected function offset ($offset) {
        $offset = $offset ?: 0;
        $offset = max($offset, 0);
        return $offset;
    }

    /**
     * Searches.
     * @param array $filters Filter values
     * @return string|array Error message or results
     */
    private function performContentSearch (array $filters) {
        $query = trim(isset($filters['query']['value']) ? $filters['query']['value'] : '');
        if ($query === '' || $query === null) return 'Query is required';
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
            $method = isset($this->filterMethodMapping[$type]) ? $this->filterMethodMapping[$type] : null;
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
