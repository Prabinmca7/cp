<?

namespace RightNow\Internal\Api\Libraries;

use RightNow\Internal\Api\Libraries\SearchResult,
    RightNow\Internal\Api\Libraries\SearchResults;

// @codingStandardsIgnoreStart
interface BaseMapperInterface {
// @codingStandardsIgnoreEnd
    /**
     * Maps results from disparate sources into search results
     * conforming to the RightNow\Libraries\SearchResult
     * interface.
     * @param  mixed $searchResults Raw data structures from disparate
     *                              sources containing result data
     * @param  array  $filters       Search filters used to trigger the search
     * @return object \RightNow\Libraries\SearchResults SearchResults instance
     */
    static function toSearchResults($searchResults, array $filters = array());
}

/**
 * Base search mapper. All search mappers should extend this class.
 */
class BaseMapper implements BaseMapperInterface {
    public static $type = 'GenericSearchResult';

    /**
     * Should be implemented by children.
     * @param  mixed $searchResult Raw data structures from disparate
     *      sources containing result data
     * @param array $filters Search filters used to trigger the search
     * @throws \Exception An exception is thrown to make it clear that the child should implement this function
     */
    static function toSearchResults ($searchResult, array $filters = array()) {
        throw new \Exception(sprintf(\RightNow\Utils\Config::getMessage(TOSEARCHRESULT_FUNCTION_ID_CHILD_CLAS_S_LBL), get_class()));
    }

    /**
     * Returns an empty result set.
     * @param array $filters Search filters used to trigger the search
     * @return object \RightNow\Libraries\SearchResults Empty SearchResults instance
     */
    static function noResults (array $filters = array()) {
        return SearchResults::emptyResults($filters);
    }
}
