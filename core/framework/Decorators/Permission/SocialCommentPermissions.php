<?php

namespace RightNow\Decorators;

require_once CPCORE . 'Decorators/Permission/PermissionBase.php';

use RightNow\Connect\v1_4 as Connect,
    RightNow\Utils\Framework;

/**
 * Extends the Connect CommunityUser object to provide simple methods to check for various permissions.
 */
class SocialCommentPermissions extends PermissionBase {
    /**
     * Since we're not decorating an actual Connect obj, set this to null to avoid verification
     */
    protected $connectTypes = array( 'CommunityComment', 'RightNow\Libraries\TabularDataObject' );

    protected $socialQuestion;
    protected $commentAuthorID;

    protected $isConnectObject = false;

    public function __construct($connectObj){
        parent::__construct($connectObj);

        if(method_exists($connectObj, 'getMetadata') && $connectObj::getMetadata()->COM_type === 'CommunityComment'){
            $this->isConnectObject = true;
        }

        $this->setQuestionObject();
    }

    /**
     * When this decorator is used on Comment tabular objects, it's put into the cache which
     * causes it to be serialized. When that happens we lose our user and question objects so we
     * need to refresh those when deserialize is called.
     */
    public function __wakeup(){
        $this->setQuestionObject();
    }

    /**
     * Whether the comment is in an active state
     * @return boolean True if the comment is active, false otherwise
     */
    protected function isActive(){
        return parent::isStatusOf(STATUS_TYPE_SSS_COMMENT_ACTIVE);
    }

    /**
     * Whether the comment is in a suspended state
     * @return boolean True if the comment is suspended, false otherwise
     */
    protected function isSuspended(){
        return parent::isStatusOf(STATUS_TYPE_SSS_COMMENT_SUSPENDED);
    }

    /**
     * Whether the comment is in a deleted state
     * @return boolean True if the comment is deleted, false otherwise
     */
    protected function isDeleted(){
        return parent::isStatusOf(STATUS_TYPE_SSS_COMMENT_DELETED);
    }

    /**
     * Whether the comment is in a pending state
     * @return boolean True if the comment is pending, false otherwise
     */
    protected function isPending(){
        return parent::isStatusOf(STATUS_TYPE_SSS_COMMENT_PENDING);
    }

    /**
     * Whether the currently logged in user is the author of this comment.
     * @return boolean True if the currently logged in user matches the CreatedByCommunityUser field. False if no user is logged in or user is not the author.
     */
    protected function isAuthor() {
        return ($this->getSocialUser() && $this->getSocialUser()->ID && isset($this->getSocialUser()->ID) && $this->connectObj->CreatedByCommunityUser && isset($this->connectObj->CreatedByCommunityUser->ID)) ?
                    ($this->getSocialUser()->ID === (int)$this->connectObj->CreatedByCommunityUser->ID) : false;
    }

    /**
     * Determines if the user has the ability to read this comment. Will throw an exception if called on a
     * Comment tabular result
     * @return bool Whether the user has permission to read this comment
     * @throws \Exception If this decorator isn't decorating a CommunityComment Connect object
     */
    protected function canRead(){
        $emptyComment = $this->getCommentShell();

        // Check socialQuestion->isActive instead of socialQuestion->canRead, because when allowing the ability
        // to read comments on suspended questions, we get into serious weirdness when displaying bestAnswers,
        // and knowing when to display the rovingCommentEditor.  We would also need to change socialComment->canUpdateStatus
        // to allow for non-active questions, which gets into a lot of strange edge cases.
        // In summary, leave the socialQuestion->isActive check there.
        return $this->socialQuestion &&
               $this->socialQuestion->SocialPermissions->isActive() &&
               ($this->isActive() || $this->canUpdateStatus($emptyComment) || ($this->isPending() && $this->isAuthor())) &&
               parent::can(PERM_SOCIALQUESTIONCOMMENT_READ, $emptyComment);
    }

    /**
     * Determines if the current user can create a comment
     * @return bool Whether the user has permission to create a comment
     */
    protected function canCreate(){
        return $this->canUserModify() &&
               $this->isQuestionActive() &&
               $this->socialQuestion->SocialPermissions->isUnlockedOrUserCanChangeLockStatus() &&
               parent::can(PERM_SOCIALQUESTIONCOMMENT_CREATE, $this->getEmptyComment());
    }

    /**
     * Determines if the current user can edit this comment
     * @return bool Whether the user has permission to edit this comment
     */
    protected function canUpdate(){
        $emptyComment = $this->getEmptyComment();

        return $this->canUserModify() &&
               ($this->isActive() || $this->isPending() ||
                   ($this->isSuspended() && parent::can(PERM_SOCIALQUESTIONCOMMENT_UPDATE_STATUS, $emptyComment))) &&
               !$this->isDeleted() &&
               $this->isQuestionActive() &&
               $this->socialQuestion->SocialPermissions->isUnlockedOrUserCanChangeLockStatus() &&
               parent::can(PERM_SOCIALQUESTIONCOMMENT_UPDATE, $emptyComment);
    }

    /**
     * Checks whether the current user has permission to update the status of the comment
     * @return bool True if user can update status, false if user is not active or does not have UPDATE and UPDATE_STATUS permissions or the comment is in a deleted state
     */
    protected function canUpdateStatus() {
        return $this->canUpdate() &&
               parent::can(PERM_SOCIALQUESTIONCOMMENT_UPDATE_STATUS, $this->getEmptyComment());
    }

    /**
     * Determines if the current user can soft-delete this comment
     * @return bool Whether the user has permission to soft-delete this comment
     */
    protected function canDelete() {
        return $this->canUpdate() &&
               parent::can(PERM_SOCIALQUESTIONCOMMENT_UPDATE_STATUS_DELETE, $this->getEmptyComment());
    }

    /**
     * Determines if the user has the ability to flag this comment
     * @return bool True if the user can flag this comment
     */
    protected function canFlag(){
        //Check if there's even an active, logged in user to save some cycles doing all the calculations below
        if(!$this->canUserModify()){
            return false;
        }

        $commentFlagObject = $this->createEmptyObjectWithAttachedComment('CommunityCommentFlg', $this->connectObj);
        // add a filler flag type
        $commentFlagObject->Type->ID = FLAG_INAPPROPRIATE;
        return $this->isActive() &&
               $this->isQuestionActive() &&
               !$this->isAuthor() &&
               $this->socialQuestion->SocialPermissions->isUnlockedOrUserCanChangeLockStatus() &&
               $this->cachePerQuestion(PERM_SOCIALQUESTIONCOMMENTCONTENTFLAG_CREATE, $commentFlagObject);
    }

    /**
     * Determines if the user has the ability to delete the given flag
     * @param Connect\CommunityCommentFlg $flag The flag we need to know whether the current user can delete
     * @return bool True if the user can rate this comment
     */
    protected function canDeleteFlag($flag = null){
        $flag = $flag ?: $this->createEmptyObjectWithAttachedComment('CommunityCommentFlg', $this->connectObj);
        // Check if there's even an active, logged in user to save some cycles doing all the calculations below
        // also ensure we have a valid flag object
        if(!$this->canUserModify() ||
            !$flag ||
            !method_exists($flag, 'getMetadata') ||
            !$flag::getMetadata()->COM_type === 'CommunityCommentFlg') {
                return false;
        }
        // add a filler flag type if it's not set
        $flag->Type->ID = $flag->Type->ID ?: FLAG_INAPPROPRIATE;

        return $this->isActive() &&
               $this->isQuestionActive() &&
               !$this->isAuthor() &&
               $this->socialQuestion->SocialPermissions->isUnlockedOrUserCanChangeLockStatus() &&
               parent::can(PERM_SOCIALQUESTIONCOMMENTCONTENTFLAG_DELETE, $flag);
    }

    /**
     * Determines if the user has the ability to rate this comment
     * @return bool True if the user can rate this comment
     */
    protected function canRate(){
        //Check if there's even a logged in user to save some cycles doing all the calculations below
        if(!$this->canUserModify()){
            return false;
        }
        $commentRateObject = $this->createEmptyObjectWithAttachedComment('CommunityCommentRtg', $this->connectObj);
        // add a filler rating value
        $commentRateObject->RatingValue = 100;

        return $this->isActive() &&
               $this->isQuestionActive() &&
               !$this->isAuthor() &&
               $this->socialQuestion->SocialPermissions->isUnlockedOrUserCanChangeLockStatus() &&
               $this->cachePerQuestion(PERM_SOCIALQUESTIONCOMMENTCONTENTRATING_CREATE, $commentRateObject);
    }

    /**
     * Determines if the user has the ability to delete the given rating
     * @param Connect\CommunityCommentRtg $rating The rating we need to know whether the current user can delete
     * @return bool True if the user can rate this comment
     */
    protected function canDeleteRating($rating){
        // Check if there's even a logged in user to save some cycles doing all the calculations below
        // also ensure we have a valid rating object
        if(!$this->canUserModify() ||
            !$rating ||
            !method_exists($rating, 'getMetadata') ||
            !$rating::getMetadata()->COM_type === 'CommunityCommentRtg') {
                return false;
        }

        return $this->isActive() &&
               $this->isQuestionActive() &&
               !$this->isAuthor() &&
               $this->socialQuestion->SocialPermissions->isUnlockedOrUserCanChangeLockStatus() &&
               parent::can(PERM_SOCIALQUESTIONCOMMENTCONTENTRATING_DELETE, $rating);
    }

    /**
     * Whether this comment can be replied to by the current user. Checks the status of the parent question as well.
     * @return bool True if the user can reply, false otherwise
     */
    protected function canReply(){
        return $this->canUserModify() &&
               $this->socialQuestion &&
               $this->isActive() &&
               $this->socialQuestion->SocialPermissions->canComment() &&
               !$this->connectObj->Parent->ID;
    }

    /**
     * Returns whether the current question is active
     * @return bool True if question is active
     */
    protected function isQuestionActive(){
        return $this->socialQuestion &&
               $this->socialQuestion->SocialPermissions->isActive();
    }

    /**
     * Since comments are tabular, we have to do a whole bunch of faking to be able to call hasPermission correctly. This
     * method handles all of that faking and returns an object than can actually be used to call hasPermission().
     * @param  string $shellToCreate Class name of Connect object to create and append CommunityComment to
     * @param  Connect\CommunityComment|object|null $comment The comment object to use, if null a shell is created
     *   Don't use a shell for flagging and rating permissions as it will cause inaccurate results. If the comment object
     *   is not of type CommunityComment, it's expected to be from a TabularQuery and have a valid ID to use.
     * @return object Instance of $shellToCreate with CommunityComment field populated
     */
    protected function createEmptyObjectWithAttachedComment($shellToCreate, $comment = null){
        $commentObject = $comment ?: $this->getCommentShell();
        if (!($commentObject instanceof Connect\CommunityComment)) {
            $commentObject = Connect\CommunityComment::fetch($commentObject->ID);
        }
        return parent::getSocialObjectShell($shellToCreate, null, $this->getSocialUser() ?: null, $commentObject);
    }

    /**
     * Returns an empty CommunityComment shell.
     * @return Object An empty CommunityComment object.
     */
    private function getCommentShell() {
        // if comment author is NULL (not really a normal data condition), then actually fetch the comment and return it
        if ($this->commentAuthorID === null) {
            try {
                return Connect\CommunityComment::fetch($this->connectObj->ID);
            }
            catch (\Exception $e) {
                // failure is probably due to testing, so just fall-through
            }
        }
        return parent::getSocialObjectShell('CommunityComment', $this->socialQuestion, $this->commentAuthorID ?: null);
    }

    /**
     * Returns a CommunityComment object suitable for using with hasPermission() and hasAbility().
     * @return Object An empty CommunityComment object.  If the decorated object is a TabularDataObject, the returned
     *   object will have the top-level properties copied from the tabular data.
     */
    private function getEmptyComment() {
        if (!($this->connectObj instanceof Connect\RNObject))
            return $this->getCommentShell();

        return $this->connectObj;
    }

    /**
     * Sets properties for the currently logged in CommunityUser instance as well as the parent CommunityQuestion of this comment
     */
    private function setQuestionObject(){
        $questionID = isset($this->connectObj->CommunityQuestion->ID) ? (int)$this->connectObj->CommunityQuestion->ID : 0;
        if($questionID && $socialQuestion = get_instance()->model('CommunityQuestion')->get($questionID)->result){
            $this->socialQuestion = $socialQuestion;
        }

        if($commentAuthorID = isset($this->connectObj->CreatedByCommunityUser) ? (int)$this->connectObj->CreatedByCommunityUser->ID : null){
            $this->commentAuthorID = $commentAuthorID;
        }
    }

    /**
     * Checks if the object can perform the provided permission, but caches the permission check result per question.
     * Intended to minimize DB traffic since all comments for a question share the same (API) permissions, but
     * the API must access the DB in order to check the comment's permission.
     * @param  int|string $permission The permission to check
     * @param  Connect\RNObject $rnObject The object to check, intended to be a rating or flagging object
     * @return boolean Whether the provided permission is allowed
     */
    private function cachePerQuestion($permission, $rnObject){
        // set up the cache key - we need the permission (either integer or string) and the question ID
        $questionID = (int)$this->connectObj->CommunityQuestion->ID;
        $cacheKey = "DECORATORS_SOCIALCOMMENTPERMISSION_{$questionID}_{$permission}";

        $result = Framework::checkCache($cacheKey);
        if ($result === null) {
            $result = $this->can($permission, $rnObject);
            Framework::setCache($cacheKey, $result);
        }
        return $result;
    }
}
