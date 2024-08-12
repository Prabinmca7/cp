<?
namespace RightNow\Helpers;

use RightNow\Utils\Text;

class RelatedAnswersHelper extends \RightNow\Libraries\Widget\Helper {

    /**
     * Formats the answer title appropriately (escapes, truncates, and highlights)
     * @param string $title Answer title
     * @param int $truncateAt Character to truncate at
     * @param bool $highlight Whether to highlight kw or not
     * @return string Formatted answer title
     */
    function formatTitle($title, $truncateAt, $highlight = false) {
        $title = Text::escapeHTML($title);

        if ($truncateAt > 0) {
            $title = Text::truncateText($title, $truncateAt);
        }

        if ($highlight) {
            $title = Text::emphasizeText($title);
        }

        return $title;
    }
}
