<?

namespace RightNow\Helpers;

class RecentlyAnsweredQuestionsHelper extends \RightNow\Libraries\Widget\Helper {
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

    /**
     * Returns link with the question id plus comment id url
     * @param object $question Question object
     * @param object $comment Comment Object
     * @return string Link with question id plus comment id
     */
    function commentLink($question, $comment) {
        return \RightNow\Utils\Url::defaultQuestionUrl($question->ID, $comment->ID);
    }

    /**
     * Determines whether the question has an
     * author-chosen best answer.
     * @param  object $question Question object
     * @return bool           True if the question has a author-
     *                             chosen best answer
     */
    function questionHasAuthorChosenBestAnswer($question) {
        return isset($question->bestAnswers[SSS_BEST_ANSWER_AUTHOR]) && ((isset($question->bestAnswers[SSS_BEST_ANSWER_MODERATOR]) ? $question->bestAnswers[SSS_BEST_ANSWER_MODERATOR] : 0) !== $question->bestAnswers[SSS_BEST_ANSWER_AUTHOR]);
    }

    /**
     * Determines if the question has a moderator-chosen best
     * answer (and that it's not the same as any author-chosen
     * best answer).
     * @param  object $question Question object
     * @return bool           True if the question has a moderator-
     *                             chosen best answer and that
     *                             it's not the same as any author-
     *                             chosen best answer
     */
    function questionHasModeratorChosenBestAnswer($question) {
        return (isset($question->bestAnswers[SSS_BEST_ANSWER_MODERATOR]) && $question->bestAnswers[SSS_BEST_ANSWER_MODERATOR]) && !is_null($question->bestAnswers[SSS_BEST_ANSWER_MODERATOR]);
    }

    /**
     * Returns the moderator-chosen best answer for
     * the question.
     * @param  object $question Question object
     * @param  array $comments Question comments
     * @return object Best answer comment
     */
    function moderatorChosenBestAnswer ($question, array $comments) {
        return $comments[$question->bestAnswers[SSS_BEST_ANSWER_MODERATOR]];
    }

    /**
     * Returns the author-chosen best answer for
     * the question.
     * @param  object $question Question object
     * @param  array $comments Question comments
     * @return object Best answer comment
     */
    function authorChosenBestAnswer ($question, $comments) {
        return $comments[$question->bestAnswers[SSS_BEST_ANSWER_AUTHOR]];
    }
}
