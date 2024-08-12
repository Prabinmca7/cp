<?

namespace RightNow\Helpers;

use RightNow\Utils\Text,
    RightNow\Utils\Url;

class RelatedKnowledgebaseAnswersHelper extends \RightNow\Libraries\Widget\Helper {

    /**
     * Returns the title of the answer properly truncated and escaped
     * @param object $answer Answer object
     * @param int $limit Truncation character limit
     * @return string Answer title
     */
    function getTitle($answer, $limit) {
        $title = Text::escapeHtml($answer->Title, false);
        if ($limit) {
            $title = Text::truncateText($title, $limit, true, 40);
        }
        return $title;
    }

    /**
     * Returns the target style for a given answer
     * @param object $answer Answer object
     * @param string $urlTarget Target type
     * @return string Target attribute for an <a> tag
     */
    function getTarget($answer, $urlTarget) {
        if ($answer->URL) {
            return "target='$urlTarget'";
        }
    }
}
