<?

namespace RightNow\Libraries;

use RightNow\Utils\Config,
    RightNow\Models\SearchSourceBase,
    RightNow\Libraries\SearchResults,
    RightNow\Internal\Utils\SearchSourceConfiguration;

/**
 * Search source library. To be used with the search source widgets and models.
 */
class Search extends \RightNow\Internal\Libraries\Search {
    /**
     * Cache of model results across all instances.
     * @var array
     */
    private static $cachedResults = array();

    /**
     * Gets or creates a Search instance.
     * @param string|array $sourceIDs Source ids for the search
     *                                Either array or comma-separated
     *                                string
     * @return Search Search instance
     */
    static function getInstance ($sourceIDs) {
        $sourceIDs = SearchSourceConfiguration::normalizeSourceIDs($sourceIDs);
        $cacheKey = self::generateCacheKey($sourceIDs);

        if (!array_key_exists($cacheKey, self::$instancePerSourceGroup)) {
            self::$instancePerSourceGroup[$cacheKey] = new Search($sourceIDs);
        }

        return self::$instancePerSourceGroup[$cacheKey];
    }

    /**
     * Initializes the cache arrays used by Search
     */
    static function clearCache () {
        self::$cachedResults = array();
        self::$instancePerSourceGroup = array();
    }

    /**
     * Creates a cache key for the given array.
     * @param  array $sourceIDs Sources for which to generate the cache
     * @return string            Cache key for the sources
     */
    static function generateCacheKey ($sourceIDs) {
        return implode(',', $sourceIDs);
    }

    /**
     * Adds one or more filters to the search.
     * @param array $filter One or more filter arrays:
     *                                  [
     *                                      'type name': [
     *                                          'type': 'type name',
     *                                          'key':  'url param key' or null,
     *                                          'value': value to use,
     *                                      ],
     *                                      …
     *                                  ]
     * @return Search Search instance so that additional method / property
     *                       calls can be chained
     */
    function addFilters (array $filter) {
        parent::addFilters($filter);

        return $this;
    }

    /**
     * Gets the filter specified by type.
     * @param  string $type Filter type
     * @return array|null       The specified filter or null
     *                              if it doesn't exist
     */
    function getFilter ($type) {
        $filters = $this->getFilters();
        return $filters[$type];
    }

    /**
     * Gets all filters.
     * @return array Filters array
     */
    function getFilters () {
        return $this->filters ?: array();
    }

    /**
     * Gets the search source configuration for the search.
     * @return array Search source configuration:
     *                      [
     *                          'source id': [
     *                              'filters':
     *                                  [filters],
     *                              'endpoint': 'endpoint',
     *                              'model': 'model'
     *                          ],
     *                          …
     *                      ]
     */
    function getSources () {
        return $this->sources ?: array();
    }

    /**
     * Gets all errors.
     * @return array List of errors
     */
    function getErrors () {
        return $this->errors;
    }

    /**
     * Gets all warnings.
     * @return array List of warnings
     */
    function getWarnings () {
        return $this->warnings;
    }

    /**
     * Searches.
     * @return SearchResults SearchResults object
     */
    function executeSearch () {
        return $this->searchSources();
    }

    /**
     * Calls through to the #getFilterValuesForFilterType method in the
     * models represented by the given source ids.
     * Because filters can only represent a single source at a time, only one
     * model should respond for the specified $filterType.
     * @param  string $filterType A filter's type
     * @return array Potential values for a complex-type filter
     */
    function getFilterValuesForFilterType ($filterType) {
        // Map of source id -> response object from the source's model response.
        $filters = array();

        if (!$this->getErrors()) {
            $this->callOnModelsForSources($this->getSources(), function ($model, $sourceID, $sourceInfo) use ($filterType, &$filters) {
                $response = $model->getFilterValuesForFilterType($filterType);
                if ($response->result) {
                    $filters[$sourceID] = $response->result;
                }
                else {
                    return $response->errors;
                }
            });

            if (count($filters) === 1) {
                $filters = array_values($filters);
                $filters = $filters[0];
            }
        }

        return $filters;
    }

    /**
     * Calls the #search method on each model for each specified source
     * and combines their results.
     * @return \RightNow\Libraries\SearchResults
     */
    private function searchSources () {
        $allResults = null;
        $filters = $this->getFilters();

        if (!$this->getErrors()) {
            $cache = self::$cachedResults;

            $this->callOnModelsForSources($this->getSources(), function ($model, $sourceID) use (&$allResults, $filters, &$cache) {
                if (!array_key_exists($sourceID, $cache)) {
                    $cache[$sourceID] = $model->search($filters);
                }
                $response = $cache[$sourceID];

                if (!$results = $response->result) return $response->errors;

                if ($results instanceof SearchResults) {
                    if (is_null($allResults)) {
                        $allResults = $results;
                    }
                    else {
                        $allResults->addResults($results);
                    }
                }
                else {
                    return Config::getMessage(THE_RES_RET_FROM_THE_MODEL_WAS_NOT_MSG);
                }
            });

            self::$cachedResults = $cache;
        }

        return $allResults ?: SearchResults::emptyResults($filters);
    }

    /**
     * Retrieves the model for each search source, passing it to the callback.
     * @param  array  $sources  Search sources
     * @param  \Closure $callback Callback that will interact with the model;
     *                            if this callback returns anything, it's considered
     *                            to be a ResponseObject with errors
     * @return object|null           ResponseObject if there's a model error
     */
    private function callOnModelsForSources (array $sources, \Closure $callback) {
        foreach ($sources as $sourceID => $source) {
            try {
                $model = \RightNow\Models\Base::loadModel($source['model']);
            }
            catch (\Exception $e) {
                return $this->addError(sprintf(Config::getMessage(THE_PCT_S_MODEL_COULD_NOT_BE_FOUND_MSG), $source['model']));
            }

            if (!$model instanceof SearchSourceBase) {
                return $this->addError(sprintf(Config::getMessage(THE_PCT_S_MODEL_DESIGNATED_BY_THE_MSG), $source['model'], $sourceID));
            }

            if ($errorMessage = $callback($model, $sourceID, $source)) {
                return $this->addError($errorMessage);
            }
        }
    }

    /**
     * Adds an error.
     * @param string|array $message Array of messages or single message
     * @return array Array of error messages
     */
    private function addError ($message) {
        if (is_string($message)) {
            $this->errors []= $message;
        }
        else if (is_array($message)) {
            $this->errors = array_merge($this->errors, $message);
        }

        return $this->errors;
    }
}
