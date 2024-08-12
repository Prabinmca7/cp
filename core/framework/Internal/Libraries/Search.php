<?

namespace RightNow\Internal\Libraries;

use RightNow\Utils\Config,
    RightNow\Libraries\Cache\ReadThroughCache,
    RightNow\Internal\Utils\SearchSourceConfiguration;

/**
 * Handles retrieval of filters and search source mappings.
 */
class Search {
    /**
     * Default `filter type -> param name` mapping
     * for a search source.
     * @var array
     */
    public static $defaultFilters = array(
        'query'     => 'kw',
        'sort'      => 'sort',
        'direction' => 'dir',
        'page'      => 'page',
        'product'   => 'p',
        'category'  => 'c',
    );

    /**
     * The array structure of sources for the search.
     * @var null by default but populated on demand
     */
    private $sources = null;

    /**
     * The array structure of filters for the search.
     * @var null by default but populated on demand
     */
    private $filters = null;

    /**
     * Caller specified source ids in the constructor.
     * @var array
     */
    protected $sourceIDs = array();

    /**
     * Array of errors encountered during processing.
     * @var array
     */
    protected $errors = array();

    /**
     * Array of warnings encountered during processing.
     * @var array
     */
    protected $warnings = array();

    /**
     * Additional filter values to override / add to
     * the search's filter values.
     * @var array
     */
    protected $additionalFilters = array();

    /**
     * Internal cache of search source instances.
     * @var array
     */
    protected static $instancePerSourceGroup = array();

    /**
     * Constructor.
     * @param array $sourceIDs Source ids for the search
     */
    protected function __construct (array $sourceIDs) {
        $this->sourceIDs = $sourceIDs;
    }

    /**
     * Getter for private properties. Acts as a lazy loader,
     * retrieving the values using restricted getter methods.
     * @param  string $name Property name
     * @return mixed       value
     */
    function __get ($name) {
        if (property_exists(self::class, $name) && is_null($this->$name)) {
            $this->$name = call_user_func(array(__CLASS__, 'get' . ucfirst($name)));
        }        

        return $this->$name;
    }

    /**
     * Given the desired sources to search on,
     * produces their mapping info from the
     * search sources configuration file.
     * Called by the __get method to retrieve and cache a value.
     * @return null|array       source configuration or null if no
     *                                 registered sources were provided
     */
    protected function getSources () {
        if (!$sources = self::selectSources($this->getSearchMapping(), $this->sourceIDs)) {
            $this->errors []= Config::getMessage(NO_SEARCH_SOURCES_WERE_PROVIDED_MSG);
        }

        return $sources;
    }

    /**
     * Adds one or more filter items to the filters
     * property.
     * @param array $filters Filters to add
     */
    protected function addFilters (array $filters) {
        $this->filters = array_merge($this->__get('filters') ?: array(), $filters);
        if (array_key_exists('limit', $filters) || array_key_exists('page', $filters)) {
            $this->filters = self::normalizePagingFilters($this->filters);
        }
        return $this->filters;
    }

    /**
     * Retrieves the search filters and their values for the search.
     * Called by the __get method to retrieve and cache a value.
     * @return array group of filters
     * [
     *     'filter type': [
     *         'type': 'filter type',
     *         'key': 'url param key' or null,
     *         'value': value
     *     ],
     *     …
     * ]
     */
    protected function getFilters () {
        if (!$sources = self::__get('sources')) return;

        $merged = $this->validateAndCombineFilters($sources);
        $filters = self::populateFromParams($merged);
        $filters = array_merge($filters, $this->additionalFilters);
        return self::normalizePagingFilters($filters);
    }

    /**
     * Normalizes the offset and page filters.
     * @param  array $filters Existing search filters
     * @return array $filters with potentially modified page and offset filters
     */
    private static function normalizePagingFilters (array $filters) {
        $filters['page']['value'] = isset($filters['page']['value']) ? (int)$filters['page']['value'] : 1;
        if ($filters['page']['value'] <= 0) {
            $filters['page']['value'] = 1;
        }

        $filters['offset'] = self::computeOffsetFromPageAndLimit($filters);

        return $filters;
    }

    /**
     * Produces a single array containing the `type -> param name` URL parameters
     * to use to extract filter values for the search source(s). If there is
     * more than one source for the search, the types of parameters are checked
     * to determine that there aren't conflicts (e.g. query can't have a param named "kw"
     * for one source but then also a param named "query" for another source when those
     * sources are used in tandem).
     * @param  array $sourcesForTheSearch Sources for the search
     *                                    ['source id' => ['filters' => [], 'model' => ''], …]
     * @return array                      filter structure
     */
    private function validateAndCombineFilters (array $sourcesForTheSearch) {
        $unifiedFilters = $this->filtersFromSources($sourcesForTheSearch);
        $unifiedFilters = self::fillInFiltersWithDefaults($unifiedFilters);

        return $unifiedFilters;
    }

    /**
     * Given the sources, each with their own list of filters, a single
     * list of filters is built-up, with the combined filters from all
     * of the sources.
     * @param  array $sourcesForTheSearch Source structure
     * @return array                      Filter structure
     */
    private function filtersFromSources (array $sourcesForTheSearch) {
        $unifiedFilters = $warnings = array();

        foreach ($sourcesForTheSearch as $sourceName => $sourceInfo) {
            foreach ($sourceInfo['filters'] as $type => $paramName) {
                if (array_key_exists($type, $unifiedFilters) && $unifiedFilters[$type]['key'] !== $paramName) {
                    // Last filter of the same type wins, but warn of the collision.
                    $this->warnings []= sprintf(
                        Config::getMessage(THE_COMBINATION_OF_SRCH_SOURCES_PCT_MSG),
                        implode(', ', array_keys($sourcesForTheSearch)), $unifiedFilters[$type]['key'], $paramName, $type, $paramName, $type);
                }

                $unifiedFilters[$type] = self::makeFilterArray($type, $paramName);
            }
        }

        return $unifiedFilters;
    }

    /**
     * Gets the search mapping.
     * @return array mapping structure
     */
    private function getSearchMapping () {
        $mapping = SearchSourceConfiguration::getSearchMapping();
        $this->errors += SearchSourceConfiguration::getMappingErrors();

        return $mapping;
    }

    /**
     * Produces an offset filter structure by computing via
     * the limit and page filters.
     * @param  array $filters Filter structure
     * @return array          Single filter structure
     */
    private static function computeOffsetFromPageAndLimit (array $filters) {
        $perPage = isset($filters['limit']['value']) ? $filters['limit']['value'] : 10;
        $page = $filters['page']['value'];

        return self::makeFilterArray('offset', null, ($page - 1) * $perPage);
    }

    /**
     * Produces a single filter structure.
     * @param string $type Filter type
     * @param string $key String url param key
     * @param string|int|array $value Value
     * @return array Single filter structure
     */
    private static function makeFilterArray ($type, $key, $value = null) {
        return array(
            'value' => $value,
            'key'   => $key,
            'type'  => $type,
        );
    }

    /**
     * Given a group of filters, adds the default set to them.
     * @param array $filters Filter structure
     * @return array Filters with defaults added
     */
    private static function fillInFiltersWithDefaults (array $filters) {
        foreach (self::$defaultFilters as $type => $key) {
            if (!array_key_exists($type, $filters)) {
                $filters[$type] = self::makeFilterArray($type, $key);
            }
        }

        return $filters;
    }

    /**
     * Returns an array populated with the url parameter values of
     * the given mapping
     * @param array $mapping Mapping structure eg.
     *      [ type => [ 'type': type, 'value': null, 'key': param name ] ]
     * @return array Populated mappings eg.
     *      [ type  => [ 'type': type, 'value' param value, 'key': param name ] ]
     */
    private static function populateFromParams (array $mapping) {
        return array_map(function ($filterArray) {
            $filterArray['value'] = \RightNow\Utils\Url::getParameter($filterArray['key']);
            return $filterArray;
        }, $mapping);
    }

    /**
     * Returns the list of sources to search, given the registered ones
     * as the ones for the current search transaction.
     * @param  array $registeredSearchSources Registered core + custom source entries
     * @param  array $sources                 Sources for this search
     * @return array                          Selected subset of $registeredSearchSources
     */
    private static function selectSources (array $registeredSearchSources, array $sources) {
        $selected = array();

        foreach ($sources as $sourceID) {
            if (array_key_exists($sourceID, $registeredSearchSources)) {
                $selected[$sourceID] = $registeredSearchSources[$sourceID];
            }
        }

        return $selected;
    }
}
