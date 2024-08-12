<?

namespace Custom\Models;

use RightNow\Libraries\Cache,
    Custom\Libraries\DDGMapper;

require_once APPPATH . 'libraries/DDGMapper.php';

class DDGSearch extends \RightNow\Models\SearchSourceBase {
    const CACHE_TIME = 1800; // Amount of time to cache results from the third-party API (in seconds)
    const API_URL = 'http://api.duckduckgo.com/?q=%s&format=json';

    /**
     * Searches Duck Duck Go's API for the given
     * query filter.
     * @param array $filters Search filters
     * @return \RightNow\Libraries\SearchResults Search results
     */
    function search (array $filters = array()) {
        $searchResults = $this->performContentSearch($filters['query']);

        return $this->getResponseObject(DDGMapper::toSearchResults($searchResults, $filters), 'is_object', is_string($searchResults) ? $searchResults : null);
    }

    /**
     * Hits Duck Duck Go's API. Caches the transaction.
     * @param array $query Query filter
     * @return array Associative array of results
     */
    private function performContentSearch ($query) {
        $query = trim($query['value']);

        if ($query === '' || $query === null) return 'Query is required';

        $cache = new Cache\PersistentReadThroughCache(self::CACHE_TIME, function($kw, $url) {
            return @file_get_contents(sprintf($url, $kw));
        });

        return @json_decode($cache->get($query, self::API_URL), true) ?: array();
    }

    /**
     * Override default function
     * @param string $filterType Filter type
     */
    function getFilterValuesForFilterType ($filterType) {}
}
