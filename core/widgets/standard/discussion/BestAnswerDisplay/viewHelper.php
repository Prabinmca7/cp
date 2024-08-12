<?php

namespace RightNow\Helpers;

use RightNow\Utils\Framework,
    RightNow\Utils\Config;

class BestAnswerDisplayHelper extends \RightNow\Libraries\Widget\Helper {
    public $question;
    public $bestAnswerTypes;
     /**
     * Determines whether comment remove as best answer actions should be rendered for the
     * given comment.
     * @param object $forComment Current comment data
     * @return string|false Label type to display
     */
    function shouldDisplayBestAnswerRemoval($forComment) {
        $socialUser = get_instance()->model('CommunityUser')->get()->result;
        if (!$socialUser || !$socialUser->SocialPermissions->isActive() ||
            !$this->question->SocialPermissions->isUnlockedOrUserCanChangeLockStatus()) {
            return false;
        }
        $canSelectAsMod = $this->question->SocialPermissions->canSelectModeratorBestAnswer();
        $canSelectAsAuthor = $this->question->SocialPermissions->canSelectAuthorBestAnswer();
        $bestAuthor = $this->commentIsBestAnswer($forComment, 'author');
        $bestMod = $this->commentIsBestAnswer($forComment, 'moderator');
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
     * Determines whether the comment is a best answer.
     * @param object $comment Current comment data
     * @param String $chosenByType Chosen by 'author' or 'moderator'. Defaults to first match
     * @return bool Result
     */
    function commentIsBestAnswer($comment, $chosenByType = null) {
        return !!get_instance()->model('CommunityQuestion')->getBestAnswerForComment($this->question, $comment,
            $chosenByType ? $this->bestAnswerTypes[$chosenByType] : null)->result;
    }
}

