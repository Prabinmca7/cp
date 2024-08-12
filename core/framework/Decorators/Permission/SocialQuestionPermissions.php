<?php

namespace RightNow\Decorators;

use RightNow\Connect\v1_4 as Connect,
    RightNow\Libraries\TabularDataObject;

require_once CPCORE . 'Decorators/Permission/PermissionBase.php';

/**
 * Extends the Connect CommunityQuestion object to provide simple methods to check for various permissions.
 */
class SocialQuestionPermissions extends PermissionBase {
    protected $connectTypes = array( 'CommunityQuestion', 'RightNow\Libraries\TabularDataObject' );

    /**
     * Whether the question is locked
     * @return boolean True if the question is locked, false otherwise
     */
    protected function isLocked(){
        return $this->connectObj->Attributes->ContentLocked === true;
    }

    /**
     * Whether the question is in an active state
     * @return boolean True if the question is active, false otherwise
     */
    protected function isActive(){
        return parent::isStatusOf(STATUS_TYPE_SSS_QUESTION_ACTIVE);
    }

    /**
     * Whether the question is in a suspended state
     * @return boolean True if the question is suspended, false otherwise
     */
    protected function isSuspended(){
        return parent::isStatusOf(STATUS_TYPE_SSS_QUESTION_SUSPENDED);
    }

    /**
     * Whether the question is in a deleted state
     * @return boolean True if the question is deleted, false otherwise
     */
    protected function isDeleted(){
        return parent::isStatusOf(STATUS_TYPE_SSS_QUESTION_DELETED);
    }

    /**
     * Whether the question is in a pending state
     * @return boolean True if the question is pending, false otherwise
     */
    protected function isPending(){
        return parent::isStatusOf(STATUS_TYPE_SSS_QUESTION_PENDING);
    }

    /**
     * Whether the currently logged in user is the author of this question.
     * @return boolean True if the currently logged in user matches the CreatedByCommunityUser field. False if no user is logged in or user is not the author.
     */
    protected function isAuthor() {
        return ($this->getSocialUser() && isset($this->getSocialUser()->ID) && $this->getSocialUser()->ID && $this->connectObj->CreatedByCommunityUser && isset($this->connectObj->CreatedByCommunityUser->ID)) ?
               ($this->getSocialUser()->ID === $this->connectObj->CreatedByCommunityUser->ID) : false;       
    }

    /**
     * Whether the currently logged in user can read this question
     * @return boolean True if the user can read, false if the user doesn't have permission
     */
    protected function canRead(){
        return ($this->isActive() || $this->canUpdateStatus() || ($this->isPending() && $this->isAuthor())) &&
               parent::can(PERM_SOCIALQUESTION_READ, $this->getQuestionShell());
    }

    /**
     * Checks whether the current user has permission to update the status of the question to deleted
     * @return bool True if user can update status, false if user is not active or does not have UPDATE and UPDATE_STATUS_DELETE permissions or the question is in a deleted state
     */
    protected function canCreate() {
        return $this->canUserModify() &&
               parent::can(PERM_SOCIALQUESTION_CREATE, $this->getQuestionShell());
    }

    /**
     * Whether the currently logged in user can edit this question
     * @return boolean True if the user can edit, false if no user is logged in, the question is locked, or the user doesn't have permission
     */
    protected function canUpdate() {
        return $this->areUserAndQuestionActiveOrUserCanChangeStatus() &&
               $this->isUnlockedOrUserCanChangeLockStatus() &&
               !$this->isDeleted() &&
               parent::can(PERM_SOCIALQUESTION_UPDATE, $this->getQuestionShell());
    }

    /**
     * Checks whether the current user has permission to update the status of the question to any status except deleted
     * @return bool True if user can update status, false if user is not active or does not have UPDATE and UPDATE_STATUS permissions or the question is in a deleted state
     */
    protected function canUpdateStatus() {
        return $this->canUserModify() &&
               !$this->isDeleted() &&
               parent::can(PERM_SOCIALQUESTION_UPDATE, $this->getQuestionShell()) &&
               parent::can(PERM_SOCIALQUESTION_UPDATE_STATUS, $this->getQuestionShell());
    }

    /**
     * Checks whether the current user has permission to update the status of the question to deleted
     * @return bool True if user can update status, false if user is not active or does not have UPDATE and UPDATE_STATUS_DELETE permissions or the question is in a deleted state
     */
    protected function canDelete() {
        return $this->canUpdate() &&
               parent::can(PERM_SOCIALQUESTION_UPDATE_STATUS_DELETE, $this->getQuestionShell());
    }

    /**
     * Checks whether the current user has permission to change the question's interface
     * @return bool True if user can update the interface, false otherwise
     */
    protected function canUpdateInterface() {
        return $this->canUpdate() &&
               parent::can(PERM_SOCIALQUESTION_UPDATE_INTERFACE, $this->getQuestionShell());
    }

     /**
     * Checks whether the given user has permission to select a best answer
     * @param int $userType Type of user to check (SSS_BEST_ANSWER_AUTHOR or SSS_BEST_ANSWER_MODERATOR)
     * @return bool|null Whether the given user can select best answer as the given type
     */
    protected function canSelectBestAnswerAs($userType) {
        if($userType === SSS_BEST_ANSWER_AUTHOR)
            return $this->canSelectAuthorBestAnswer();
        if($userType === SSS_BEST_ANSWER_MODERATOR)
            return $this->canSelectModeratorBestAnswer();
    }

     /**
     * Checks whether the logged in user is able to select a best answer as the question author
     * @return bool Whether the logged in user can select best answer as the question author
     */
    protected function canSelectAuthorBestAnswer() {
        return $this->canUpdate() &&
               $this->isAuthor() &&
               parent::can(PERM_SOCIALQUESTION_UPDATE_AUTHORBESTANSWER, $this->getQuestionShell());
    }

     /**
     * Checks whether the logged in user is able to select a best answer as a moderator
     * @return bool Whether the logged in user can select best answer as a moderator
     */
    protected function canSelectModeratorBestAnswer() {
        return $this->canUpdate() &&
               parent::can(PERM_SOCIALQUESTION_UPDATE_MODERATORBESTANSWER, $this->getQuestionShell());
    }

    /**
     * Whether the currently logged in user can rate this question
     * @return boolean True if the user can rate, false if no user is logged in, the question is locked, the current user is the author, or the user doesn't have permission to rate
     */
    protected function canRate(){
        $rate = parent::getSocialObjectShell('CommunityQuestionRtg', $this->connectObj, $this->getSocialUser() ?: null);
        // add a filler rating value
        $rate->RatingValue = 100;
        return $this->areUserAndQuestionActiveOrUserCanChangeStatus() &&
               $this->isUnlockedOrUserCanChangeLockStatus() &&
               !$this->isAuthor() &&
               parent::can(PERM_SOCIALQUESTIONCONTENTRATING_CREATE, $rate);
    }

    /**
     * Whether the currently logged in user can delete the rating on this question
     * @param Connect\CommunityQuestionRtg $rating The rating we need to know whether the current user can delete
     * @return boolean True if the user can delete the rating, false if no user is logged in, the question is locked, the current user is the author, or the user doesn't have permission to delete the rating
     */
    protected function canDeleteRating($rating){
        return $this->areUserAndQuestionActiveOrUserCanChangeStatus() &&
               $this->isUnlockedOrUserCanChangeLockStatus() &&
               !$this->isAuthor() &&
               parent::can(PERM_SOCIALQUESTIONCONTENTRATING_DELETE, $rating);
    }

    /**
     * Whether the currently logged in user can delete the flag on this question
     * @param Connect\CommunityQuestionFlg $flag The flag we need to know whether the current user can delete
     * @return boolean True if the user can delete the flag, false if no user is logged in, the question is locked, the current user is the author, or the user doesn't have permission to delete the flag
     */
    protected function canDeleteFlag($flag = null){
        $flag = $flag ?: parent::getSocialObjectShell('CommunityQuestionFlg', $this->connectObj, $this->getSocialUser() ?: null);
        // ensure we have a valid flag object
        if(!$flag ||
            !method_exists($flag, 'getMetadata') ||
            !$flag::getMetadata()->COM_type === 'CommunityQuestionFlg') {
                return false;
        }
        // add a filler flag type if it's not set
        $flag->Type->ID = isset($flag->Type->ID) ? $flag->Type->ID : FLAG_INAPPROPRIATE;
        return $this->areUserAndQuestionActiveOrUserCanChangeStatus() &&
               $this->isUnlockedOrUserCanChangeLockStatus() &&
               !$this->isAuthor() &&
               parent::can(PERM_SOCIALQUESTIONCONTENTFLAG_DELETE, $flag);
    }

    /**
     * Whether the currently logged in user can flag this question
     * @return boolean True if the user can flag, false if no user is logged in, the question is locked, the current user is the author, or the user doesn't have permission to flag
     */
    protected function canFlag(){
        $flag = parent::getSocialObjectShell('CommunityQuestionFlg', $this->connectObj, $this->getSocialUser() ?: null);
        // add a filler flag type
        $flag->Type->ID = FLAG_INAPPROPRIATE;
        return $this->areUserAndQuestionActiveOrUserCanChangeStatus() &&
               $this->isUnlockedOrUserCanChangeLockStatus() &&
               !$this->isAuthor() &&
               parent::can(PERM_SOCIALQUESTIONCONTENTFLAG_CREATE, $flag);
    }

    /**
     * Whether new comments can be created that are associated with this CommunityQuestion
     * @return bool True if the user can create a new comment.
     */
    protected function canComment(){
        $comment = parent::getSocialObjectShell('CommunityComment', $this->connectObj, $this->getSocialUser() ?: null);
        return $this->canUserModify() &&
               $this->isActive() &&
               $this->isUnlockedOrUserCanChangeLockStatus() &&
               parent::can(PERM_SOCIALQUESTIONCOMMENT_CREATE, $comment);
    }

    /**
     * Returns whether the current question is locked and if the current user can edit that lock status
     * @return boolean True if the question is unlocked or the user can change lock status
     */
    protected function isUnlockedOrUserCanChangeLockStatus(){
        return !$this->isLocked() || parent::can(PERM_SOCIALQUESTION_UPDATE_LOCKED, $this->getQuestionShell());
    }

    /**
     * Checks whether the current user can update question lock
     * @return boolean True if user can update question lock, false if user is not active or does not have UPDATE_LOCK permission or question is in deleted state
     */
    protected function canUpdateLock() {
        return $this->canUserModify() &&
               !$this->isDeleted() && parent::can(PERM_SOCIALQUESTION_UPDATE_LOCKED, $this->getQuestionShell());
    }

    /**
     * Checks whether or not the current user and question are active
     * @return bool True if both question and user are active
     */
    protected function areUserAndQuestionActiveOrUserCanChangeStatus() {
        return $this->canUserModify() &&
               ($this->isActive() || $this->canUpdateStatus());
    }

    /**
     * Checks whether the current user has permission to move the question
     * @param int $ID ID of the product/category to which question is getting moved to
     * @param string $type Type on which move operation is performed i.e 'Product' or 'Category'
     * @return bool True if user can move question, false if user is not active or does not have CREATE_QUESTION or UPDATE_QUESTION permissions on the destination product/category or UPDATE_QUESTION permission on the current product/category or the question is in a deleted state
     */
    protected function canMove($ID, $type) {
        $question = $this->getQuestionShell();
        // return false if user can't modify the existing question
        if (!$this->canUserModify() || $this->isDeleted() || !parent::can(PERM_SOCIALQUESTION_UPDATE, $question)) {
            return false;
        }
        // now change the product/category and verify the move is allowed
        if ($type === "Product") {
            $question->Product = (int) $ID;
        }
        else {
            $question->Category = (int) $ID;
        }
        return parent::can(PERM_SOCIALQUESTION_CREATE, $question) &&
               parent::can(PERM_SOCIALQUESTION_UPDATE, $question);
    }

    /**
     * Returns a CommunityQuestion object suitable for using with hasPermission() and hasAbility().
     * @return Object An empty CommunityQuestion object.  If the decorated object is a TabularDataObject, the returned
     *   object will have the top-level properties copied from the tabular data.
     */
    private function getQuestionShell() {
        if (!$this->connectObj instanceof TabularDataObject)
            return $this->connectObj;

        return parent::getSocialObjectShell('CommunityQuestion');
    }
}
