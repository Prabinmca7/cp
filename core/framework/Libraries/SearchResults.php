<?

namespace RightNow\Libraries;

/**
 * Represents a result set of search results for a search.
 * Container of \RightNow\Libraries\SearchResult instances.
 */
#[\AllowDynamicProperties]
class SearchResults {
    /**
     * The search query used to execute the search
     * @var string
     */
    public $query = '';

    /**
     * Number of results contained in the paging window.
     * @var integer
     */
    public $size = 0;

    /**
     * Total number of results.
     * @var integer
     */
    public $total = 0;

    /**
     * The offset into the paging window.
     * @var integer
     */
    public $offset = 0;

    /**
     * The search filters used to execute the search.
     * @var array
     */
    public $filters = array();

    /**
     * List of \RightNow\Libraries\SearchResult instances.
     * @var array
     */
    public $results = array();

    public $html;
    public $searchResults;
    public $searchState;

    /**
     * Returns an array representation of the object.
     * @return array Ready for JSON encoding
     */
    function toArray () {
        $array = (array) $this;
        $array['results'] = array_map(function ($searchResult) {
            return $searchResult->toArray();
        }, $this->results);

        return $array;
    }

    /**
     * Merges other SearchResults instances to this result set and updates
     * the `size` and `total` properties.
     *
     * Additionally, any number of SearchResults instances can be passed in as extra parameters
     * and will be added.
     *
     * @throws \Exception If anything other than a SearchResults instance is supplied
     * @return SearchResults This instance of SearchResults
     */
    function addResults () {
        $args = func_get_args();
        foreach ($args as $otherResults) {
            if (!($otherResults instanceof SearchResults)) {
                throw new \Exception("Can only add other SearchResults instances");
            }

            $this->size += $otherResults->size;
            $this->total += $otherResults->total;
            $this->results = array_merge($this->results, $otherResults->results);
        }

        return $this;
    }

    /**
     * Result set representing no results.
     * @param array $filters Search filters that triggered the search
     * @return SearchResults Empty SearchResults instance
     */
    static function emptyResults (array $filters = array()) {
        $empty = new SearchResults();
        $empty->filters = $filters;

        return $empty;
    }
}
