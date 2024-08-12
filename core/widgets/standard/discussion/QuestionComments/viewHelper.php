<?php

namespace RightNow\Helpers;

use RightNow\Utils\Url,
    RightNow\Utils\Framework,
    RightNow\Utils\Config;

class QuestionCommentsHelper extends \RightNow\Libraries\Widget\Helper {
    public $bestAnswerLabels;
    public $question;
    public $bestAnswerTypes;
    function __construct() {
        // Labels for each combination of best answers.
        $this->bestAnswerLabels = array(
            0 => Config::getMessage(THIS_IS_NOT_A_BEST_ANSWER_LBL),
            1 << SSS_BEST_ANSWER_AUTHOR => Config::getMessage(HAS_BEEN_MARKED_BEST_QUESTIONS_AUTHOR_LBL),
            1 << SSS_BEST_ANSWER_MODERATOR => Config::getMessage(HAS_BEEN_MARKED_BEST_SITE_MODERATOR_LBL),
            1 << SSS_BEST_ANSWER_COMMUNITY => Config::getMessage(HAS_BEEN_MARKED_BEST_COMMUNITY_USERS_LBL),
            (1 << SSS_BEST_ANSWER_AUTHOR) | (1 << SSS_BEST_ANSWER_MODERATOR) => Config::getMessage(MARKED_BEST_BOTH_QS_AUTH_SITE_MODERATOR_LBL),
            (1 << SSS_BEST_ANSWER_AUTHOR) | (1 << SSS_BEST_ANSWER_COMMUNITY) => Config::getMessage(MARKED_BEST_BOTH_QS_AUTHOR_CONT_USERS_LBL),
            (1 << SSS_BEST_ANSWER_MODERATOR) | (1 << SSS_BEST_ANSWER_COMMUNITY) => Config::getMessage(MARKED_BEST_BOTH_S_MODERAT_CONT_USERS_LBL),
            (1 << SSS_BEST_ANSWER_AUTHOR) | (1 << SSS_BEST_ANSWER_MODERATOR) | (1 << SSS_BEST_ANSWER_COMMUNITY) => Config::getMessage(MARKED_SSS_QS_AUTH_S_MODERAT_CONT_USERS_LBL),
        );
    }

    /**
     * Returns a formatted string for the given timestamp.
     * @param string|number $timestamp Timestamp
     * @param bool $semanticAttribute Whether or not this is a timestamp for a semantic element attribute
     * @return string Timestamp
     */
    function formattedTimestamp ($timestamp, $semanticAttribute = false) {
        $time = is_int($timestamp) ? $timestamp : strtotime($timestamp);

        if ($semanticAttribute) {
            return date('Y-m-d', $time);
        }

        return \RightNow\Libraries\Formatter::formatDateTime($time);
    }

    /**
     * Whether or not we should display the comment area, which includes the link for non-logged in users. We will display
     * this if the user isn't logged in AND the question isn't locked, or they have permission
     * @return bool Result
     */
    function shouldDisplayNewCommentArea(){
        if (!Framework::isSocialUser()) {
            return !$this->question->SocialPermissions->isLocked() && $this->question->SocialPermissions->isActive();
        }
        return $this->question->SocialPermissions->canComment();
    }

    /**
     * Determines whether comment reply action should be rendered for the given comment.
     * @param object $forComment Current comment data
     * @return bool Result
     */
    function shouldDisplayCommentReply($forComment) {
        if (!Framework::isLoggedIn()) {
            return $forComment->Parent->ID === null && $this->shouldDisplayNewCommentArea();
        }
        // Only exposing single-level reply. Also, assume that if the user can post a new top level comment, then they can post a reply
        return $this->shouldDisplayNewCommentArea() && $forComment->SocialPermissions->canReply();
    }

    /**
     * Determines whether comment mark/unmark as best answer actions should be rendered for the
     * given comment.
     * @param object $forComment Current comment data
     * @param object $socialUser Current social user
     * @param bool $isMarking Whether caller is marking or unmarking as best answer
     * @param array $bestAnswerTypes Array containing values of best answer type selection. Values are in line with attribute best_answer_types. Values include
     * - author
     * - moderator
     * - none
     * @return string|bool Best Answer user type or false if the user does not have permission
     */
    function shouldDisplayBestAnswerActions($forComment, $socialUser, $isMarking, $bestAnswerTypes = array()) {
        if (!$forComment->SocialPermissions->isActive() ||
            !$socialUser || !$socialUser->SocialPermissions->isActive() ||
            !$this->question->SocialPermissions->isUnlockedOrUserCanChangeLockStatus() ||
            !$this->question->SocialPermissions->isActive()) {
            return false;
        }
        $canSelectAsMod = false;
        $canSelectAsAuthor = false;
        $bestAuthor = false;
        $bestMod = false;

        if (in_array('moderator', $bestAnswerTypes)) {
            $canSelectAsMod = $this->question->SocialPermissions->canSelectModeratorBestAnswer();
            $bestMod = $this->commentIsBestAnswer($forComment, 'moderator');
        }
        if (in_array('author', $bestAnswerTypes)) {
            $canSelectAsAuthor = $this->question->SocialPermissions->canSelectAuthorBestAnswer();
            $bestAuthor = $this->commentIsBestAnswer($forComment, 'author');
        }

        if($isMarking) {
            $bestAuthor = !$bestAuthor;
            $bestMod = !$bestMod;
        }
        if($canSelectAsMod && $canSelectAsAuthor) {
            if($bestAuthor && $bestMod) return 'Both';
            if($bestAuthor) return 'Author';
            if($bestMod) return 'Moderator';
        }
        if ($bestAuthor && $canSelectAsAuthor) {
            return 'Author';
        }
        if ($bestMod && $canSelectAsMod) {
            return 'Moderator';
        }
        return false;
    }

    /**
     * Returns true if comment is in one of the status: Active, Pending or Suspended
     * @param  object $comment Comment
     * @return bool True If comment should be displayed, false otherwise
     */
    function shouldDisplayCommentContent($comment) {
        return $comment->SocialPermissions->isActive() ||
            ($comment->SocialPermissions->isPending() && ($comment->SocialPermissions->canUpdateStatus() || $comment->SocialPermissions->isAuthor())) ||
            ($comment->SocialPermissions->isSuspended() && $comment->SocialPermissions->canUpdateStatus());
    }

    /**
     * Returns true, unless comment is pending and user is not the author or a moderator.
     * @param  object $comment Comment
     * @return bool True If comment should be displayed, false otherwise
     */
    function shouldDisplayComment($comment) {
        if ($comment->SocialPermissions->isPending() && !$comment->SocialPermissions->canUpdateStatus() && !$comment->SocialPermissions->isAuthor()) {
            return false;
        }

        return true;
    }

    /**
     * Determines whether the comment is a best answer.
     * @param object $comment Current comment data
     * @param String $chosenByType Chosen by 'author', 'moderator', 'community'. Defaults to first match
     * @return bool Result
     */
    function commentIsBestAnswer($comment, $chosenByType = null) {
        $bestAnswerType = (isset($this->bestAnswerTypes) && isset($this->bestAnswerTypes[$chosenByType])) ? $this->bestAnswerTypes[$chosenByType] : null;
        return !!get_instance()->model('CommunityQuestion')->getBestAnswerForComment($this->question, $comment, $bestAnswerType)->result;
    }

    /**
     * Returns a label describing the best answer
     * type of the comment. Memoizes the results.
     * @param  object $comment Comment designated as best answer
     * @return string          Label
     */
    function getBestAnswerLabel($comment) {
        static $cache = array();

        if (!array_key_exists($comment->ID, $cache)) {
            $cache[$comment->ID] = $this->bestAnswerLabels[$this->getLabelIndexForBestAnswerComment($comment) ?: 0];
        }

        return $cache[$comment->ID];
    }

    /**
     * Given an array of best answer types and whether
     * the comment they're representing is a best answer
     * for that type, this returns the label for that
     * combination of best answer types.
     * @param  array $types Keys are the best answer type ids
     *                      and values are booleans representing
     *                      whether a comment is a best answer of
     *                      that type
     * @return string        Label for the best answer combination
     */
    function getLabelForBestAnswerTypes($types) {
        $index = null;
        foreach ($types as $typeID => $isSelected) {
            if ($isSelected) {
                $index = $this->combineAsBitMask($index, $typeID);
            }
        }

        return $this->bestAnswerLabels[$index];
    }

    /**
     * Converts the given id to a power of two and adds it
     * to the bitmask. If bitmask is null, the converted value
     * of idValue is returned.
     * @param  null|int $bitmask Bitmask value to add to
     * @param  int $idValue Best answer type id
     * @return int          bitmask value
     */
    protected function combineAsBitMask($bitmask, $idValue) {
        $converted = 1 << $idValue;

        if (is_null($bitmask)) {
            $bitmask = $converted;
        }
        else {
            $bitmask |= $converted;
        }

        return $bitmask;
    }

    /**
     * Returns the index into the best answer array for the label
     * to use for the given comment marked as best answer.
     * @param  object $comment Comment
     * @return int|null          bitmask index or null if the
     *                                   comment isn't a best answer
     */
    protected function getLabelIndexForBestAnswerComment($comment) {
        $index = null;
        foreach ($this->bestAnswerTypes as $typeLabel => $typeID) {
            if ($this->commentIsBestAnswer($comment, $typeLabel)) {
                $index = $this->combineAsBitMask($index, $typeID);
            }
        }

        return $index;
    }

    /**
     * Checks if the given page is the current page or not.
     * @param integer $pageNumber Arbitrary page number
     * @param integer $currentPage Current/clicked page number
     * @return bool True if the page numbers match
     */
    function isCurrentPage($pageNumber, $currentPage) {
        return $pageNumber === $currentPage;
    }

    /**
     * Inserts the given page numbers into the given format string.
     * @param string $labelPage Label to display
     * @param integer $pageNumber Page number
     * @param integer $endPage Last page number in the pagination
     * @return string Sprintf-d string
     */
    function paginationLinkTitle($labelPage, $pageNumber, $endPage) {
        return sprintf($labelPage, $pageNumber, $endPage);
    }

    /**
     * Produces a url for a pagination link's 'href' value.
     * @param  integer $pageNumber Page the url is for
     * @return string Pagination url
     */
    function paginationLinkUrl($pageNumber) {
        static $path;

        if (is_null($path)) {
            $CI = get_instance();
            $path = $CI->isAjaxRequest() ? $CI->getPageFromReferrer() : $CI->page;
            $path = Url::addParameter('/app/' . $path . Url::sessionParameter(), 'qid', $this->question->ID);
        }

        return Url::addParameter($path, 'page', $pageNumber);
    }

    /**
     * Determines if a hellip should be displayed.
     * @param integer $pageNumber Page number to check
     * @param integer $currentPage Current/clicked page number
     * @param integer $endPage Last page number in the pagination
     * @return bool True if the hellip should be displayed
     */
    function shouldShowHellip($pageNumber, $currentPage, $endPage) {
        return abs($pageNumber - $currentPage) === (($currentPage === 1 || $currentPage === $endPage) ? 3 : 2);
    }

    /**
     * Determines if the given page number should be displayed.
     * The pagination pattern followed here is:
     *     1 ... 4 5 6 ... 12.
     * if, for example, 5 is the current/clicked page out of a total of 12 pages.
     * @param integer $pageNumber Page number to check
     * @param integer $currentPage Current/clicked page number
     * @param integer $endPage Last page number in the pagination
     * @return bool True if the page number should be displayed.
     */
    function shouldShowPageNumber($pageNumber, $currentPage, $endPage) {
        // Always display the first and last pages.
        // Display the next (or previous) two pages when you're on the first or last page.
        // Unless you're on other pages, in which case we want to display page numbers adjacent to the current page only.
        return $pageNumber === 1 || ($pageNumber === $endPage) || (abs($pageNumber - $currentPage) <= (($currentPage === 1 || $currentPage === $endPage) ? 2 : 1));
    }
}
