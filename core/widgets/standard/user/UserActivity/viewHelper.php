<?

namespace RightNow\Helpers;

use RightNow\Utils\Url,
    RightNow\Utils\Text,
    RightNow\Libraries\Formatter;

class UserActivityHelper extends \RightNow\Libraries\Widget\Helper {
    /**
     * Returns the label for the specified activity from the
     * given widget attributes.
     * @param  string $activity Activity name
     * @param array $attrs Widget attributes
     * @return string           Label
     */
    function labelForActivity ($activity, array $attrs) {
        static $labels;

        if (is_null($labels)) {
            $labels = array(
                'question'                  => 'label_question',
                'comment'                   => 'label_comment',
                'bestAnswerGivenByUser'     => 'label_best_answer_by_user',
                'bestAnswerGivenToUser'     => 'label_best_answer_to_user',
                'commentRatingGivenByUser'  => 'label_comment_ratings_by_user',
                'commentRatingGivenToUser'  => 'label_comment_ratings_to_user',
                'questionRatingGivenByUser' => 'label_question_ratings_by_user',
                'questionRatingGivenToUser' => 'label_question_ratings_to_user',
            );
        }

        return $attrs[$labels[$activity]];
    }

    /**
     * Formats the timestamp.
     * @param  string $text String containing date + time
     * @return string       Formatted date string
     */
    function formatTimestamp ($text) {
        return $text ? \RightNow\Libraries\Formatter::formatDateTime(strtotime($text)) : '';
    }

    /**
     * Formats the question / comment entry.
     * @param  string $text  Question / comment text
     * @param  int $limit Number of characters to begin truncating
     * @return string        Formatted entry
     */
    function formatPostContent ($text, $limit) {
        return Text::truncateText(Formatter::formatMarkdownEntry($text), $limit);
    }
}
