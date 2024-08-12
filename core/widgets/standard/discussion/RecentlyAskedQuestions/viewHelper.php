<?

namespace RightNow\Helpers;

class RecentlyAskedQuestionsHelper extends \RightNow\Libraries\Widget\Helper {
    /**
     * Returns $link with the question id url
     * parameter attached.
     * @param  object $question Question object
     * @param  string $link     Link
     * @return string           Link with question id param
     */
    function questionLink($question, $link) {
        return \RightNow\Utils\Url::addParameter($link, 'qid', $question->ID);
    }
}
