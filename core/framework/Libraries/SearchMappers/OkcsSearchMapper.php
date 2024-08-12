<?

namespace RightNow\Libraries\SearchMappers;

use RightNow\Libraries\SearchResults,
    RightNow\Libraries\SearchResult,
    RightNow\Utils\Text,
    RightNow\Utils\Config,
    RightNow\Api;

/**
* Maps OKCS search results into SearchResults.
*/
class OkcsSearchMapper extends BaseMapper {
    public static $type = 'OkcsSearch';
    public static $defaultSize = 10;
    
    /**
    * This Method maps OKCS search results into SearchResults
    * @param array $apiResult Array of search results
    * @param array $filters Array of filters
    * @return array Mapped search results
    */
    static function toSearchResults ($apiResult, array $filters = array()) {
        $query = null;
        $resultSet = new SearchResults();
        if(is_array($apiResult) && $apiResult['category']) {
            $resultSet->category = $apiResult['category'];
            return $resultSet;
        }
        $resultSet->searchResults = $apiResult->result['searchResults'];
        $resultSet->searchState = $apiResult->result['searchState'];
        $resultSet->query = $query;
        $resultSet->filters = $filters;
        $results = isset($resultSet->searchResults['results']->results[0]->resultItems) ? $resultSet->searchResults['results']->results[0]->resultItems : null;
        $resultSet->total = (is_array($results) && count($results) > 0) ? count($results) : 0;
        if ($resultSet->searchResults['pageMore'] === 0) {
            $resultSet->size = self::$defaultSize;
        }
        else if($filters['searchType']['value'] === 'newTab' && $filters['page']['value'] > 1){
            $resultSet->size = self::$defaultSize;
            if($resultSet->searchResults['pageMore'] > 0)
                $resultSet->total = $resultSet->size + 1;
        }
        else {
            $resultSet->size = 1;
        }

        if (is_array($results) && count($results) > 0) {
            foreach ($results as $summaryContent) {
                $result = new SearchResult();
                $result->type = self::$type;
                $result->text = self::getAnswerTitle($summaryContent, $filters['truncate']['value']);
                $result->url = self::getAnswerUrl($summaryContent, $resultSet->searchState, $result->text, $filters['sessionKey']);
                $result->OkcsSearch->id = $summaryContent->docId;
                $result->summary = self::getAnswerExcerpts($summaryContent);
                $summaryContent->isHighlightingEnabled = !empty($summaryContent->highlightedLink);
                $summaryContent->title = $result->text ?: \RightNow\Utils\Config::getMessage(NO_TTLE_LBL);
                $summaryContent->href = $result->url;
                $resultSet->results []= $result;
            }
            unset($filters['sessionKey']);
        }
        return $resultSet;
    }
    
    /**
    * This Method returns title of the answer
    * @param object $answer Search result object
    * @param int $length Number of characters for truncation
    * @return string Answer title
    */
    static function getAnswerTitle ($answer, $length) {
        if ($answer->title) {
            $title = Text::escapeHtml($answer->title->snippets[0]->text);
        }
        if(trim($title) === '')
            $title = Config::getMessage(NO_TTLE_LBL);
        return is_null($length) ? $title : Text::truncateText($title, $length);
    }
    
    /**
    * This Method returns answer excerpts
    * @param object $summaryContent Search result object
    * @return string Excerpts of the answer
    */
    static function getAnswerExcerpts ($summaryContent) {
        $excerpt = '';
        if(isset($summaryContent->textElements) && is_array($summaryContent->textElements) && count($summaryContent->textElements) > 0) {
            foreach ($summaryContent->textElements as $excerptSnippet) {
                foreach ($excerptSnippet->snippets as $snippet) {
                    $excerpt .= $snippet->text;
                    if ($summaryContent->type === 'template' && strpos($snippet->text, 'IM:URL_ANSWER') !== false ) {
                        $snippet->text = '';
                    }
                }
            }
        }
        return $excerpt;
    }
    
    /**
    * This Method returns link url of the answer
    * @param object $result Search result object
    * @param object $searchState Search state object
    * @param string $title Title of the answer
    * @param string $searchSession OKCS search session
    * @return string Answer url
    */
    static function getAnswerUrl($result, $searchState, $title, $searchSession) {
        if (isset($result->title) && $result->title && isset($result->title->url)) {
            $linkUrl = $result->title->url;
        }
        else if (isset($result->link) && $result->link) {
            $linkUrl = $result->link;
        }
        else if (isset($result->clickThroughLink) && $result->clickThroughLink) {
            if(Text::stringContains($result->clickThroughLink, 'turl='))
                $linkUrl = urldecode(Text::getSubstringAfter($result->clickThroughLink, 'turl='));
        }
        $highlightUrl = isset($result->highlightedLink) && (is_array($result->highlightedLink) || $result->highlightedLink instanceof Countable) && count($result->highlightedLink) === 1 ? $result->highlightedLink : '';
        $highlightContentFlag = false;
        $urlData = '';
        $answerStatus = '';
        $clickThroughTransactionID = $searchState['transactionID'] + 1;
        $clickThroughUrl = "transactionID/{$clickThroughTransactionID}/priorTransactionID/{$searchState['priorTransactionID']}/searchSession/{$searchSession}/clickThrough/{$linkUrl}";
        $result->clickThroughUrl = Api::encode_base64_urlsafe(Api::ver_ske_encrypt_fast_urlsafe($clickThroughUrl));

        if (Text::stringContains($linkUrl, 'IM:') !== false) {
            $articleData = explode(':', $linkUrl);
            $answerLocale = $articleData[3];
            $answerID = $articleData[6];
            if(Text::stringContains($linkUrl, ':#') !== false) {
                $answerStatus = $articleData[4];
                $answerID = strtoupper($answerStatus) === 'PUBLISHED' ? $answerID : $answerID . "_d";
                $attachment = Text::getSubstringAfter($linkUrl, ':#');
                if(Text::stringContains($highlightUrl, '#xml='))
                    $attachment .= '#xml=' . str_replace('%23', '', Text::getSubstringAfter($highlightUrl, '#xml='));
                $attachmentUrl = $answerID . "/file/" . Api::encode_base64_urlsafe(Api::ver_ske_encrypt_fast_urlsafe($attachment));
                return $attachmentUrl;
            }
            if($highlightUrl !== '') {
                $urlData .= 'searchSession/' . $searchSession . '/txn/' . $searchState['transactionID'] . '/highlightedLink/';
                $highlightContentFlag = true;
            }
            else {
                $answerStatus = $articleData[4];
            }
        }
        else {
            $externalUrl = urlencode($linkUrl);
            $linkUrl = !empty($highlightUrl) ? urlencode($result->highlightedLink) : urlencode($linkUrl);
            if(isset($result->fileType) && $result->fileType === 'HTML' && !empty($highlightUrl)) {
                $urlData = "type/{$result->fileType}/searchSession/{$searchSession}/txn/{$searchState['transactionID']}/externalUrl/{$externalUrl}/highlightedLink/";
                $highlightContentFlag = true;
            }
            else {
                $urlData = 'url/' . $linkUrl;
            }
        }

        if (!is_null($highlightUrl) && $highlightContentFlag) {
            $query = parse_url($highlightUrl, PHP_URL_QUERY);
            parse_str($query, $params);
            $highlightInfo = $params['highlight_info'];
            $trackedURL = $params['turl'];
            $urlData .= "priorTransactionId={$searchState['priorTransactionID']}&answerId={$params['answer_id']}&highlightInfo={$highlightInfo}&trackedURL={$trackedURL}";
        }
        $urlData = (!empty($answerStatus)) ? "title/{$title}/a_status/{$answerStatus}/{$urlData}" : "title/{$title}/{$urlData}";
        if(!is_null($answerID))
            $href = "/a_id/{$answerID}";
        if(!is_null($answerLocale))
            $href .= "/loc/{$answerLocale}";
        if(isset($result->fileType) && (($result->fileType === 'HTML' && !empty($highlightUrl)) || $result->fileType === 'PDF'))
            return $urlData;
        return $href . '/answer_data/' . Api::encode_base64_urlsafe(Api::ver_ske_encrypt_fast_urlsafe($urlData));
    }
}
