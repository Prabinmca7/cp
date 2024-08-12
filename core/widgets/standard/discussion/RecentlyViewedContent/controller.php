<?php
namespace RightNow\Widgets;

use \RightNow\Utils\Url,
    \RightNow\Utils\Text;

class RecentlyViewedContent extends \RightNow\Libraries\Widget\Base {
    function __construct($attrs) {
        parent::__construct($attrs);
    }

    protected $urlParameters = array(
        'questions' => array(
            'param' => 'qid',
            'model' => 'CommunityQuestion'
        ),
        'answers' => array(
            'param' => 'a_id',
            'model' => 'Answer'
        ),
    );

    function getData() {
        if (!($this->CI->session->canSetSessionCookies() &&
            $sessionUrlParams = $this->CI->session->getSessionData('urlParameters'))) {
            //If cookies are disabled, or there's no data, there's nothing to display.
            return false;
        }

        $sessionUrlParams = $this->getArrayOfUniqueEntries(array_reverse($sessionUrlParams));

        if($urlParamToExclude = $this->findUrlParamsInPage()) {
            unset($sessionUrlParams[array_search($urlParamToExclude, $sessionUrlParams)]);
        }

        $sessionUrlParams = $this->removeUnusedUrlParams($sessionUrlParams);
        $sessionUrlParams = ($this->data['attrs']['content_count'] === 0) ? $sessionUrlParams : array_slice($sessionUrlParams, 0, $this->data['attrs']['content_count']);

        if(count($sessionUrlParams) === 0) {
            return false;
        }

        $this->data['previousContent'] = $this->getContentFromIDs($sessionUrlParams);

        if(count($this->data['previousContent']) === 0) {
            return false;
        }
    }

    /**
     * Get any url parameters on the current page associated with the types of content being compiled
     * @return array|null Parameter value that was found, or null when no associated params were found
     */
    protected function findUrlParamsInPage() {
        foreach($this->data['attrs']['content_type'] as $contentType) {
            $paramToCheck = $this->urlParameters[$contentType]['param'];
            if($urlParam = \RightNow\Utils\Url::getParameter($paramToCheck)) {
                return array($paramToCheck => $urlParam);
            }
        }
    }

    /**
     * Calls into the appropriate models to get thier Summary or Subject attribute for use
     *     in link text
     * @param array $ids An array of recently viewed items, each entry an array with an
     *     'a_id' or 'qid' attribute, depending on if the item is an Answer or
     *     CommunityQuestion
     * @return array An array of arrays with three keys: 'id', 'url', 'text'
     */
    protected function getContentFromIDs(array $ids) {
        if(count($ids) === 0) {
            return array();
        }

        $answerIDs = $questionIDs = array();
        foreach($ids as $id) {
            reset($id);
            $key = key($id);
            if($key === 'a_id') {
                $answerIDs []= $id[$key];
            }
            else if($key === 'qid') {
                $questionIDs []= $id[$key];
            }
        }

        $answerSummaries = $questionSubjects = array();
        if(count($answerIDs) > 0) {
            $answerSummaries = $this->CI->model('Answer')->getAnswerSummary($answerIDs)->result;
        }
        if(count($questionIDs) > 0) {
            $questionSubjects = $this->CI->model('CommunityQuestion')->getQuestionSubject($questionIDs)->result;
        }
        $answerSummariesCount = isset($answerSummaries) ? count($answerSummaries) : 0;
        $questionSubjectsCount = isset($questionSubjects) ? count($questionSubjects) : 0;
        if($answerSummariesCount === 0 && $questionSubjectsCount === 0){
            return array();
        }

        $combinedSortedContent = array();
        foreach($ids as $id) {
            $contentItem = array();
            if (isset($id['a_id']) && isset($answerSummaries[$id['a_id']])) {
                $contentItem['type'] = 'AnswerContent';
                $contentItem['url'] = Url::defaultAnswerUrl($id['a_id']);
                $contentItem['text'] = $this->truncateText($answerSummaries[$id['a_id']]['Summary']);
            }
            else if(isset($id['qid']) && isset($questionSubjects[$id['qid']])) {
                $contentItem['type'] = 'CommunityQuestion';
                $contentItem['url'] = Url::defaultQuestionUrl($id['qid']);
                $contentItem['text'] = $this->truncateText($questionSubjects[$id['qid']]['Subject']);
            }
            if(!empty($contentItem)) {
                $combinedSortedContent []= $contentItem;
            }
        }

        return $combinedSortedContent;
    }

    /**
     * Search sessionUrlParams for any keys not lsited as a content_type to use, and remove them.
     * @param array $sessionUrlParams List of Url Parameters to check against
     * @return array Array of entries consisting of content_types
     */
    protected function removeUnusedUrlParams(array $sessionUrlParams) {
        $paramsToUse = array();

        foreach($this->data['attrs']['content_type'] as $contentType) {
            $paramsToUse[] = $this->urlParameters[$contentType]['param'];
        }

        foreach($sessionUrlParams as $param) {
            if(!in_array(key($param), $paramsToUse)) {
                unset($sessionUrlParams[array_search($param, $sessionUrlParams)]);
            }
        }

        return array_values($sessionUrlParams);
    }

    /**
     * Return a single array of unique entries for the sessionUrlParams array
     * @param array $sessionUrlParams List of Url Parameters to check against
     * @return array Array of unique entries in the $sessionUrlParams array
     */
    private function getArrayOfUniqueEntries(array $sessionUrlParams) {
        $paramList = array();

        foreach($sessionUrlParams as $param) {
            if(!in_array($param, $paramList)) {
                $paramList[] = $param;
            }
        }

        return $paramList;
    }

    /**
     * Truncates and escapes text, presumably for a link
     * @param string $text Text to truncated
     * @return string|null Truncated text
    */
    private function truncateText($text) {
        $text = Text::escapeHtml($text, false);
        if ($this->data['attrs']['truncate_size']) {
            $text = Text::truncateText($text, $this->data['attrs']['truncate_size'], true, 40);
        }
        return $text;
    }
}
