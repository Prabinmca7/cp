<?

namespace RightNow\Models;

use RightNow\Utils\Text,
    RightNow\Utils\Framework,
    RightNow\Connect\v1_4 as Connect,
    RightNow\Utils\Connect as ConnectUtil,
    RightNow\Connect\Knowledge\v1 as KnowledgeFoundation,
    RightNow\Libraries\SearchMappers\SocialSearchMapper;

require_once CPCORE . 'Libraries/SearchMappers/SocialSearchMapper.php';
require_once CPCORE . 'Models/KFSearch.php';

/**
 * Search model for social searches
 */
class SocialSearch extends KFSearch {
    private $filterMethodMapping = array(
        'author'              => 'authorFilter',
        'category'            => 'categoryFilter',
        'createdTime'         => 'createdTimeFilter',
        'numberOfBestAnswers' => 'numberOfBestAnswersFilter',
        'product'             => 'productFilter',
        'status'              => 'statusFilter',
        'updatedTime'         => 'updatedTimeFilter',
    );

    /**
     * Searches KFAPI.
     * @param array $filters Search filters, each an array having at minimum a 'value' key specifying a
     *                       single value ('value' => 1), multiple values ('value' => '1,2,3'), or null.
     *                       - author: int
     *                       - category: int
     *                       - createdTime: datetime
     *                       - numberOfBestAnswers: int
     *                       - product: int
     *                       - status: int
     *                       - updatedTime: datetime
     *                       - query: int
     *                       - sort: int
     *                       - direction: int
     *                       - page: int
     *                       - offset: int
     *                       - limit: int
     * @return SearchResults instance
     */
    function search(array $filters = array()) {
        if (!isset($filters['status']) || !$filters['status']) {
            // TEMPORARY: as we don't currently send in a 'status' filter and only want active questions by default.
            $filters['status'] = $this->addActiveStatusFilter();
        }

        $unmodifiedFilters = $filters;
        $filters = $this->stripCommunityFilter($filters);
        $searchResults = $this->performContentSearch($filters);
        return $this->getResponseObject(SocialSearchMapper::toSearchResults($searchResults, $unmodifiedFilters), 'is_object', is_string($searchResults) ? $searchResults : null);
    }

    /**
     * Override base function
     * @param string $filterType Filter type
     * @return array An array of filter values for the specified type
     */
    function getFilterValuesForFilterType($filterType) {
        $values = array();

        if ($filterType === 'updatedTime') {
            $values = $this->updatedTimeValues();
        }
        else if ($filterType === 'numberOfBestAnswers') {
            $values = $this->buildFilterArray(array('yes', 'no'));
        }
        else {
            $values = parent::getFilterValuesForFilterType($filterType)->result;
        }

        return $this->getResponseObject($values, 'is_array');
    }

    /**
     * If $filterToStrip is specified, strips either the product or category filter, otherwise
     * retains the filter array
     * @param array $filters Search filters
     * @param string $filterToStrip Type of product/category filter to remove. Value should be
     *        either 'product' or 'category' - for any other value, nothing will be stripped.
     * @return array $filters Modified search filters
     */
    protected function stripCommunityFilter($filters, $filterToStrip = null) {
        if ($filterToStrip && ($filterToStrip === 'product' || $filterToStrip === 'category')) {
            $filters[$filterToStrip]['value'] = '';
        }
        return $filters;
    }

    /**
     * Performs a content search via the KFAPI
     * @param array $filters Search filters
     * @return object Results
     */
    protected function performContentSearch(array $filters) {
        $query = trim(isset($filters['query']['value']) ? $filters['query']['value'] : '');
        if ($query === '' || $query === null) return 'Query is required';

        $search = new KnowledgeFoundation\ContentGroupedSearch();
        $this->addKnowledgeApiSecurityFilter($search);
        $search->InteractionToken = $this->getKnowledgeApiSessionToken();
        $search->SearchTerms = $query;
        $search->SearchConstraints[0] = $this->constraints($filters);
        try {
            $search->Search();
        }
        catch (\Exception $e) {
            // Invalid filters will cause an exception to be thrown
            $search->Error = $e->getMessage();
        }

        return $search->Results;
    }

    /**
     * Returns a 'status' filter array for all active statuses.
     * @return array A 'status' filter for active questions
     */
    protected function addActiveStatusFilter() {
        static $filter;
        if (!isset($filter)) {
            $statuses = array();
            $query = \RightNow\Connect\v1_4\ROQL::query('SELECT ID FROM CommunityQuestionSts WHERE StatusType = ' . STATUS_TYPE_SSS_QUESTION_ACTIVE)->next();
            while($row = $query->next()) {
                $statuses[] = $row['ID'];
            }
            $filter = array(
                'value' => implode(',', $statuses),
                'key' => 'status',
                'type' => 'status',
            );
        }

        return $filter;
    }

    /**
     * Constructs a simple array of objects having keys 'ID' and 'LookupName' which point to the same value.
     * @param array $values An array of values
     * @return array An array of objects
     */
    private function buildFilterArray(array $values) {
        $filterArray = array();
        foreach($values as $value) {
            $filterArray[] = (object) array(
                'ID' => $value,
                'LookupName' => $value,
            );
        }

        return $filterArray;
    }

    /**
     * Returns an array of time interval values used by the `updatedTime` filter, or $interval if valid.
     * @param string|null $interval Specify to validate if $interval is a valid interval. If not specified a filter array of intervals is returned.
     * @return array|string||null The $values array is returned if $interval is null. Otherwise either $interval or null returned.
     */
    private function updatedTimeValues($interval = null) {
        $intervals = array('day', 'week', 'month', 'year');
        if ($interval) {
            return in_array($interval, $intervals) ? $interval : null;
        }

        static $values;
        $values = $values ?: $this->buildFilterArray($intervals);

        return $values;
    }

    /**
     * Constructs ContentSearchConstraints
     * @param array $filters Filter values
     * @return object ContentSearchConstraints
     */
    private function constraints(array $filters) {
        $constraints = new KnowledgeFoundation\ContentSearchConstraints();
        $constraints->Limit = $this->limit($filters['limit']['value']);
        $constraints->SortOptions = $this->sortFilter($filters['sort']['value'], $filters['direction']['value'], $filters['query']['value']);
        $constraints->Start = $this->offset($filters['offset']['value']);
        $constraints->Filters[0] = $this->filter($filters);

        return $constraints;
    }

    /**
     * Constructs a DomainContentFilter
     * @param array $filters Filter values
     * @return object DomainContentFilter
     */
    private function filter(array $filters) {
        $filter = new KnowledgeFoundation\DomainContentFilter();
        $filter->Domain = new Connect\NamedIDOptList();
        $filter->Domain->ID = 10003; // TODO: This maps to KF_API_CONTENT_SEARCH_DOMAIN_QNS_AND_COMMENTS in kf_enums.h but is not currently exposed to PHP
        $filter->Filter = $this->combineFilterStrings($filters);

        return $filter;
    }

    /**
     * Constructs a filter "WHERE" clause by AND-ing all the relevant filters together.
     * @param array $filters Filter values
     * @example 'Status = 29 AND Product = 1 AND NumberOfBestAnswers IN (1,2,3,4,5) AND ...'
     * @return string
     */
    private function combineFilterStrings(array $filters) {
        $strings = array();
        foreach ($filters as $type => $filterArray) {
            if ((isset($this->filterMethodMapping[$type]) && $method = $this->filterMethodMapping[$type]) && ($filter = $this->$method($filterArray['value']))) {
                $strings[] = $filter;
            }
        }

        return $strings ? implode(' AND ', $strings) : '';
    }

    /**
     * Constructs a filter string [e.g. 'Product in (1,2,3)'] used by the KFAPI
     * @param mixed $value The value sent in from the search filters
     * @param string $filterName The name of the filter
     * @param array $operations The allowed operations for the filter.
     * @return string|null The filter string expected by the KFAPI, or null
     */
    private function filterString($value, $filterName, $operations = array('=', 'IN')) {
        $items = array();
        $value = isset($value) ? $value : '';
        foreach(explode(',', $value) as $item) {
            if ($item !== '') {
                $items[] = trim($item);
            }
        }

        if (!$count = count($items)) {
            return;
        }

        if ($count === 1 && in_array('=', $operations)) {
            return "$filterName = {$items[0]}";
        }

        if ($count == 2 && in_array('BETWEEN', $operations) && !in_array('IN', $operations)) {
            // 'BETWEEN' currently only supported if 'IN' is not also specified as being valid as we don't really have
            // a way to indicate the $value sent in is meant to be treated as an 'IN' or 'BETWEEN' operation.
            return "$filterName BETWEEN '{$items[0]}' AND '{$items[1]}'";
        }

        if (in_array('IN', $operations)) {
            return "$filterName IN (" . implode(',', $items) . ')';
        }
    }

    /**
     * Constructs an Author filter
     * @param mixed $value Author ID or comma-separated string of IDs.
     * @return string|null The filter string expected by the KFAPI, or null
     */
    private function authorFilter($value) {
        return $this->filterString($value, 'Author');
    }

    /**
     * Constructs a Category filter
     * @param mixed $value Category ID or comma-separated string of IDs.
     * @return string|null The filter string expected by the KFAPI, or null
     */
    private function categoryFilter($value) {
        return $this->filterString($value, 'Category');
    }

    /**
     * Constructs a NumberOfBestAnswers filter
     * @param int|string $value If 'yes', then a value of '1,2,3' is passed in, if 'no' then '0' is used.
     *                          Otherwise $value is epected to be an integer or a comma-separated string of integers.
     * @return string|null The filter string expected by the KFAPI, or null
     */
    private function numberOfBestAnswersFilter($value) {
        if ($value === 'yes') {
            $value = '1,2,3';
        }
        else if ($value === 'no') {
            $value = 0;
        }

        // NOTE: The KFAPI also supports the 'BETWEEN' operation for this filter but as we do not currently have a
        // way to specify if the $value's are to be treated as an 'IN' or 'BETWEEN' we're going to omit 'BETWEEN'.
        return $this->filterString($value, 'NumberOfBestAnswers', array('=', 'IN'));
    }

    /**
     * Constructs a Product filter
     * @param mixed $value Product ID or comma-separated string of IDs.
     * @return string|null The filter string expected by the KFAPI, or null
     */
    private function productFilter($value) {
        return $this->filterString($value, 'Product');
    }

    /**
     * Constructs a Status filter
     * @param mixed $value Status ID or comma-separated string of IDs.
     * @return string|null The filter string expected by the KFAPI, or null
     */
    private function statusFilter($value) {
        return $this->filterString($value, 'Status');
    }

    /**
     * Constructs an UpdatedTime filter
     * @param string $value One of [day,week,month,year] or a comma-separated string of '{fromDate},{toDate}'.
     * @return string|null The filter string expected by the KFAPI, or null
     */
    private function updatedTimeFilter($value) {
        return $this->filterString($this->timeFilter($value), 'UpdatedTime', array('BETWEEN'));
    }

    /**
     * Constructs a CreatedTime filter
     * @param string $value One of [day,week,month,year] or a comma-separated string of '{fromDate},{toDate}'.
     * @return string|null The filter string expected by the KFAPI, or null
     */
    private function createdTimeFilter($value) {
        return $this->filterString($this->timeFilter($value), 'CreatedTime', array('BETWEEN'));
    }

    /**
     * Constructs a 'from' and 'to' date string used by CreatedTime and UpdatedTime filters
     * @param string $value One of [day,week,month,year] or a comma-separated string of '{fromDate},{toDate}'.
     * @return mixed A "{fromDate},{toDate}" string if $value is a recognized interval, else $value.
     */
    private function timeFilter($value) {
        if ($value && !Text::stringContains($value, ',') && ($interval = $this->updatedTimeValues(strtolower("$value")))) {
            // One of 'day', 'week', 'month' or 'year'
            $dateFormat = 'Y-m-d\TH:i:s\Z';
            // Use UTC time
            $toDateObject = new \DateTime('@' . time());
            $toDate = $toDateObject->format($dateFormat);
            $fromDate = $toDateObject->sub(new \DateInterval('P1' . strtoupper(substr($interval, 0, 1))))->format($dateFormat);
            $value = "$fromDate,$toDate";
        }
        return $value;
    }

    /**
     * Returns a ContentSortOptions object or null. When no query is provided
     * and there is no sort value, return a ContentSortOptions object which
     * specifies sort by updated time, ascending. If sort value is provided,
     * return a ContentSortOptions object which specifies sort by the provided
     * value. Otherwise return null.
     * @param int|null $sort Sort field id
     * @param int|null $direction Sort direction
     * @param string $query Query for sort.
     * @return object|null ContentSortOptions or null if $sort is falsey
     */
    protected function sortFilter ($sort, $direction, $query = '') {
        if($sort || ($query === '*' || $query === '')) {
            $sortOption = new KnowledgeFoundation\ContentSortOptions();
            $sortOption->SortOrder = new Connect\NamedIDOptList();
            $sortOption->SortField = new Connect\NamedIDOptList();

            if($sort) {
                $sortOption->SortOrder->ID = $direction ?: 1;
                $sortOption->SortField->ID = $sort;
            }
            else {
                $sortOption->SortOrder->ID = 1;
                $sortOption->SortField->ID = 1;
            }
            return $sortOption;
        }
    }
}
