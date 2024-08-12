<?

namespace RightNow\Helpers;

class SocialContentRatingHelper extends \RightNow\Libraries\Widget\Helper {
    /**
     * Chooses the correct label to display, given a count.
     * @param  int $count Number of items
     * @param string $singularLabel The label used when $count is one
     * @param string $pluralLabel The label that used when $count is not one;
     *                            this label is sprintf-d with $count
     * @return string The label
     */
    function chooseCountLabel ($count, $singularLabel, $pluralLabel) {
        return ($count == 1) ?
            $singularLabel :
            sprintf($pluralLabel, $count);
    }
}
