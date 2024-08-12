<?

namespace Custom\Libraries;

use RightNow\Utils\Config,
    RightNow\Libraries\SearchResult,
    RightNow\Libraries\SearchResults;

/**
 * Maps Duck Duck Go API results into SearchResults.
 */
class DDGMapper extends \RightNow\Libraries\SearchMappers\BaseMapper {
    public static $type = 'DDGSearch';

    /**
     * Maps the raw search results from the Duck Duck Go API
     * into search results conforming to the RightNow\Libraries\SearchResult
     * interface - https://api.duckduckgo.com/api.
     * @param  array $apiResult Associative array API response
     * @param  array $filters   Search filters that triggered the search
     * @return object \RightNow\Libraries\SearchResults SearchResults instance
     */
    static function toSearchResults ($apiResult, array $filters = array()) {
        if (!$apiResult) return self::noResults($filters);

        $results = new SearchResults();
        $results->query = $filters['query']['value'];
        $results->total = is_array($apiResult['RelatedTopics']) ? count($apiResult['RelatedTopics']) : 0;
        $results->filters = $filters;

        if ($results->total > 0) {
            $count = 0;
            // API results include 'Results' and 'RelatedTopics'. Include both.
            $set = array_merge($apiResult['Results'], $apiResult['RelatedTopics']);

            foreach ($set as $topic) {
                // Don't include more results than the caller-limit specified.
                if ($count++ >= $filters['limit']['value']) break;

                if (!$topic['FirstURL']) {
                    // There's some weird results that don't have a URL...
                    $results->total--;
                    continue;
                }
                $result = new SearchResult();
                $result->type = self::$type;
                $result->url = $topic['FirstURL'];
                $result->text = $topic['Text'];

                // Add a special icon property.
                $result->DDGSearch->icon = array(
                    'url' => $topic['Icon']['URL'],
                    'height' => $topic['Icon']['Height'],
                    'width' => $topic['Icon']['Width'],
                );

                $results->results []= $result;
                $results->size = $count;
            }
        }

        return $results;
    }
}
