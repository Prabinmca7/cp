<?

namespace RightNow\Libraries\SearchMappers;

use RightNow\Utils\Url,
    RightNow\Utils\Config,
    RightNow\Libraries\SearchResult,
    RightNow\Libraries\SearchResults,
    RightNow\Connect\Knowledge\v1\SearchResponse;

/**
 * Maps Social search results into SearchResults.
 */
class SocialSearchMapper extends BaseMapper {
    public static $type = 'SocialSearch';

    /**
     * Maps Social search results into search results
     * conforming to the RightNow\Libraries\SearchResult
     * interface.
     * @param  array $apiResults Array containing Social objects
     * @param  array  $filters    Search filters used to execute the search
     * @return object \RightNow\Libraries\SearchResults SearchResults instance
     */
    static function toSearchResults ($apiResults, array $filters = array()) {
        if (!is_object($apiResults) || !property_exists($apiResults, 'GroupedSummaries')) {
            return self::noResults($filters);
        }

        $resultSet = new SearchResults();
        $resultSet->query = $filters['query']['value'];
        $resultSet->filters = $filters;
        $resultSet->offset = $filters['offset']['value'];
        $resultSet->total = $apiResults->TotalResults;

        if ($resultSet->total && ($contents = $apiResults->GroupedSummaries[0]->SummaryContents)) {
            $resultSet->size = count($contents);
            foreach ($contents as $socialContent) {
                $resultSet->results[] = self::searchResult($socialContent);
            }
        }

        return $resultSet;
    }

    /**
     * Constructs a SearchResult object
     * @param object $socialContent A social content object
     * @return object A SearchResult object
     */
    private static function searchResult($socialContent) {
        $result = new SearchResult(self::$type);
        $result->url = Url::defaultQuestionUrl($socialContent->ID);
        $result->text = $socialContent->Title;
        $result->summary = $socialContent->Excerpt;
        $result->created = $socialContent->CreatedTime;
        $result->updated = $socialContent->UpdatedTime;
        $result->SocialSearch->id = $socialContent->ID;
        $result->SocialSearch->author = $socialContent->CreatedBySocialUser;
        $result->SocialSearch->bestAnswerCount = $socialContent->NumberOfBestAnswers;
        $result->SocialSearch->commentCount = $socialContent->NumberOfComments;

        return $result;
    }
}