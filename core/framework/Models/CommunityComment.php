<?php
namespace RightNow\Models;

use RightNow\Connect\v1_4 as Connect,
    RightNow\Utils\Connect as ConnectUtil,
    RightNow\Libraries\ConnectTabular,
    RightNow\Utils\Config,
    RightNow\Utils\Framework;

/**
 * Methods for retrieving social comments (collections of comments)
 */
class CommunityComment extends CommunityObjectBase
{
    /**
     * Instantiates the model, optionally accepting a concrete class name
     *
     * @param string $className Name of the class to instantiate.  Defaults to CommunityComment.
     */
    function __construct($className = 'CommunityComment') {
        parent::__construct($className ?: 'CommunityComment');
    }

    /**
     * Returns an empty comment.
     *
     * @return Connect\CommunityComment An instance of the Connect comment object
     */
    public function getBlank()
    {
        return $this->getResponseObject(parent::getBlank());
    }

    /**
    * Returns a CommunityComment middle layer object from the database based on the comment id.
     *
     * @param int|null $commentID The id of the comment to retrieve.
     * @return Connect\CommunityComment An instance of the Connect comment object
     */
    public function get($commentID)
    {
        $comment = parent::get($commentID);
        if(!is_object($comment)){
            return $this->getResponseObject(null, null, $comment);
        }
        if($comment->CommunityQuestion->Interface->ID !== $this->interfaceID){
            return $this->getResponseObject(null, null, Config::getMessage(USER_DOES_HAVE_READ_PERMISSI_COMMENT_LBL));
        }
        \RightNow\Libraries\Decorator::add($comment, array('class' => 'Permission/SocialCommentPermissions', 'property' => 'SocialPermissions'));

        if ($comment->SocialPermissions->isDeleted()) {
            return $this->getResponseObject(null, null, Config::getMessage(CANNOT_FIND_DELETED_ANOTHER_USER_MSG));
        }

        // ensure the user has permission to view this object
        if (!$comment->SocialPermissions->canRead()) {
            return $this->getResponseObject(null, null, Config::getMessage(USER_DOES_HAVE_READ_PERMISSI_COMMENT_LBL));
        }

        return $this->getResponseObject($comment);
    }

    /**
     * Returns a tabular CommunityComment from the process cache.
     * @param int $commentID The id of the comment to retrieve.
     * @param boolean $checkCommentReadPermission Flag to indicate whether read permission to be checked or not.
     * @return array Tabular comment data
     */
    public function getTabular($commentID, $checkCommentReadPermission = true) {
        if ($checkCommentReadPermission && ($tabularComment = $this->getCommentFromCache($commentID))) {
            return $this->getResponseObject($tabularComment, 'is_array');
        }

        // Deal with cache miss
        $roqlSelectFrom = $this->getCommentSelectROQL(sprintf('c.ID = %d', $commentID), 1);
        $query = ConnectTabular::query($roqlSelectFrom);
        
        //read permission check needs to be bypassed in question detail page where suspended comments are still shown
        if (($tabularComment = $query->getFirst(array('class' => 'Permission/SocialCommentPermissions', 'property' => 'SocialPermissions')))
            && (!$checkCommentReadPermission || $tabularComment->SocialPermissions->canRead())) {
            if($checkCommentReadPermission && ($user = $this->CI->Model('CommunityUser')->get()->result)) {
                // Add rating and flagging info
                if($ratingResult = $this->getRatingsForComment($commentID, $user->ID)) {
                    $tabularComment = ConnectTabular::mergeQueryResults($tabularComment, $ratingResult);
                }
                if($flagResult = $this->getFlagsForComment($commentID, $user->ID)) {
                    $tabularComment = ConnectTabular::mergeQueryResults($tabularComment, $flagResult);
                }
                $this->addCommentToCache($tabularComment);
            }
            return $this->getResponseObject($tabularComment);
        }
        return $this->getResponseObject(null, null, $query->error);
    }

    /**
     * Returns a list of tabular SocialComments without ratings or flags.
     * @param array $comments List of IDs to request comment data for.
     * @return array List of comments, keyed off the comment IDs for easy look up
     */
    public function getFromList(array $comments) {
        foreach ($comments as $comment) {
            if (!Framework::isValidID($comment)) {
                return $this->getResponseObject(null, null, Config::getMessage(COMMENT_IDS_MUST_BE_INTEGERS_MSG));
            };
        }

        $roqlSelectFrom = $this->getCommentSelectROQL(sprintf('c.ID in (%s)', implode(',', $comments)));
        $query = ConnectTabular::query($roqlSelectFrom);

        if ($results = $query->getCollection()) {
            $fullComments = array();

            foreach ($results as $comment) {
                $fullComments[$comment->ID] = $comment;
            }

            return $this->getResponseObject($fullComments, 'is_array');
        }

        return $this->getResponseObject(null, null, $query->error);
    }

    /**
     * Creates an comment. In order to create a comment, a contact must be logged-in. Form data is expected to look like
     *
     *      -Keys are Field names (e.g. Comment.Subject)
     *      -Values are objects with the following members:
     *          -value: (string) value to save for the field
     *          -required: (boolean) Whether the field is required
     *
     * @param array $formData Form fields to update the comment with. In order to be created successfully, a contact must be logged in
     * @return Connect\CommunityComment|null Created comment object or null if there are error messages and the comment wasn't created
     * @throws \Exception All thrown exceptions should be caught within this function
     */
    public function create(array $formData) {
        $socialUserResponseObject = $this->getSocialUser();
        if ($socialUserResponseObject->errors) {
            return $socialUserResponseObject;
        }
        $socialUser = $socialUserResponseObject->result;

        $comment = $this->getBlank()->result;

        $contact = $this->getContact();
        if($contact->Disabled){
            // Disabled contacts can't create comments
            return $this->getResponseObject(null, null, Config::getMessage(SORRY_THERES_ACCT_PLS_CONT_SUPPORT_MSG));
        }

        // set social user for logged in contact and set author
        $comment->CreatedByCommunityUser = $socialUser;

        $errors = $warnings = array();
        foreach ($formData as $name => $field) {
            if(!\RightNow\Utils\Text::beginsWith($name, 'CommunityComment')){
                continue;
            }
            $fieldName = explode('.', $name);
            // since CommunityComment is an abstract class, we have to replace the first element of the
            // array with the concrete class name specified in the constructor
            $fieldName[0] = $this->objectName;

            try {
                //Get the metadata about the field we're trying to set. In order to do that we have to
                //populate some of the sub-objects on the record. We don't want to touch the existing
                //record at all, so instead we'll just pass in a dummy instance.
                list(, $fieldMetaData) = ConnectUtil::getObjectField($fieldName, $this->getBlank()->result);
            }
            catch (\Exception $e) {
                $warnings []= $e->getMessage();
                continue;
            }
            $field->value = ConnectUtil::castValue($field->value, $fieldMetaData);
            if (\RightNow\Utils\Validation::validate($field, $name, $fieldMetaData, $errors) &&
                ($setFieldError = $this->setFieldValue($comment, $name, $field->value, $fieldMetaData->COM_type))) {
                   $errors[] = $setFieldError;
            }
        }

        if ($errors) {
            return $this->getResponseObject(null, null, $errors);
        }

        \RightNow\Libraries\Decorator::add($comment, array('class' => 'Permission/SocialCommentPermissions', 'property' => 'SocialPermissions'));

        try{
            // check the permission after the object is constructed so the check will have the data it needs
            if(!$comment->SocialPermissions->canCreate()) {
                throw new \Exception('User does not have permission to create a comment');
            }
            $comment = parent::createObject($comment, SRC2_EU_AAQ);
        }
        catch(\Exception $e){
            $comment = $e->getMessage();
        }

        if(!is_object($comment)){
            return $this->getResponseObject(null, null, $comment);
        }

        // touch question to have an accurate LastActivityTime
        $touchedQuestion = $this->CI->model('CommunityQuestion')->touch($comment->CommunityQuestion);
        if ($touchedQuestion->errors) {
            return $touchedQuestion;
        }

        return $this->getResponseObject($comment, 'is_object', null, $warnings);
    }

    /**
     * Updates the specified comment with the given form data. Form data is expected to look like
     *
     *      -Keys are Field names (e.g. Comment.Subject)
     *      -Values are objects with the following members:
     *          -value: (string) value to save for the field
     *          -required: (boolean) Whether the field is required
     *
     * @param int $commentID ID of the comment to update
     * @param array $formData Form fields to update the comment with
     * @return Connect\Comment|null Updated comment object or error messages if the comment wasn't updated
     * @throws \Exception If the user does not have appropriate permissions
     */
    public function update($commentID, array $formData) {
        $socialUserResponseObject = $this->getSocialUser();
        if ($socialUserResponseObject->errors) {
            return $socialUserResponseObject;
        }
        $socialUser = $socialUserResponseObject->result;

        $comment = $this->get($commentID);
        if (!$comment->result) {
            // Error: return the ResponseObject
            return $comment;
        }
        $comment = $comment->result;

        $errors = $warnings = array();

        // the user must either be
        // - authorized to edit their own comment AND logged in as the comment's author
        // - authorized to edit other people's comments
        if (!$comment->SocialPermissions->canUpdate()) {
            return $this->getResponseObject(null, null, Config::getMessage(USER_DOES_HAVE_PERMISSION_EDIT_COMMENT_LBL));
        }

        foreach ($formData as $name => $field) {
            if(is_string($field->value)) {
                // trim any whitespace
                $field->value = trim($field->value);
            }
            $fieldName = explode('.', $name);

            // permission required to update Status field, depending on the value
            // note that setting Status via LookupName only works on create, so we don't need to consider it here
            if (\RightNow\Utils\Text::endsWith($name, 'Status.ID') || \RightNow\Utils\Text::endsWith($name, 'Status')) {
                // get a list of the status IDs that mean Deleted
                $deletedStatuses = $this->getStatusesFromStatusType(STATUS_TYPE_SSS_COMMENT_DELETED)->result;

                // deletions need the delete permission, everything else needs the status change permission
                if (in_array($field->value, $deletedStatuses)) {
                    if (!$comment->SocialPermissions->canDelete()) {
                        throw new \Exception('User does not have permission to delete this comment');
                    }
                }
                else if(!$comment->SocialPermissions->canUpdateStatus()) {
                    throw new \Exception('User does not have permission to set this comment status ' . $field->value);
                }
            }

            try {
                //Get the metadata about the field we're trying to set. In order to do that we have to
                //populate some of the sub-objects on the record. We don't want to touch the existing
                //record at all, so instead we'll just pass in a dummy instance.
                list(, $fieldMetaData) = ConnectUtil::getObjectField($fieldName, $this->getBlank()->result);
            }
            catch (Connect\ConnectAPIErrorBase $e) {
                $warnings []= $e->getMessage();
                continue;
            }
            $field->value = ConnectUtil::castValue($field->value, $fieldMetaData);
            if (\RightNow\Utils\Validation::validate($field, $name, $fieldMetaData, $errors) &&
                ($setFieldError = $this->setFieldValue($comment, $name, $field->value, $fieldMetaData->COM_type))) {
                   $errors[] = $setFieldError;
            }
        }
        if ($errors) {
            return $this->getResponseObject(null, null, $errors);
        }

        try{
            // remove the comment from cache
            $this->removeCommentFromCache($comment->ID);

            $comment = parent::updateObject($comment, SRC2_EU_MYSTUFF_Q);
        }
        catch(\Exception $e){
            $comment = $e->getMessage();
        }
        if(!is_object($comment)){
            return $this->getResponseObject(null, null, $comment);
        }
        return $this->getResponseObject($comment, 'is_object', null, $warnings);
    }

    /**
     * Retrieves a list of flags for the given comment
     * @param Connect\CommunityComment|int $comment Comment instance or ID to flag
     * @param Connect\CommunityUser $socialUser User for which to retrieve the flag.  Defaults to current user.
     * @return Connect\CommunityCommentFlg|null content flag object for the given user
     */
    public function getUserFlag($comment, $socialUser = null) {
        if (!$socialUser || !($socialUser instanceof Connect\CommunityUser)) {
            $socialUserResponseObject = $this->getSocialUser();
            if ($socialUserResponseObject->errors) {
                return $socialUserResponseObject;
            }
            $socialUser = $socialUserResponseObject->result;
        }

        if (isset($commment) && is_object($commment)) {
            $comment = $comment->ID;
        }
        try {
            $roql = sprintf("SELECT CommunityCommentFlg FROM CommunityCommentFlg f WHERE f.CommunityComment.ID = %d AND f.CreatedByCommunityUser = %d", $comment, $socialUser->ID);

            // perform the query and gather the results
            $results = Connect\ROQL::queryObject($roql)->next();

            if ($results && ($result = $results->next())) {
                $flag = $result;
            }
        }
        catch (Connect\ConnectAPIErrorBase $e) {
            return $this->getResponseObject(null, null, $e->getMessage());
        }
        
        return $this->getResponseObject(isset($flag) ? $flag : null, isset($flag) ? ($flag ? 'is_object' : 'is_null') : 'is_null');

    }

    /**
     * Flags a comment as inappropriate, miscategorized, copyrighted, or other reasons
     * @param Connect\CommunityComment|int $comment Comment instance or ID to flag
     * @param int|null $flagType Optional.  Indicates the reason for flagging, defaults to 1 which is "Inappropriate"
     * @return Connect\CommunityCommentFlg The newly created content flag
     */
    public function flagComment($comment, $flagType = 1) {
        $socialUserResponseObject = $this->getSocialUser();
        if ($socialUserResponseObject->errors) {
            return $socialUserResponseObject;
        }
        $socialUser = $socialUserResponseObject->result;

        // check for an invalid comment:
        // -null
        // -neither an object nor valid ID
        // -is an object other than a social comment
        if ($comment === null ||
            (!is_object($comment) && !Framework::isValidID($comment)) ||
            (is_object($comment) && !($comment instanceof Connect\CommunityComment))) {
            return $this->getResponseObject(null, null, Config::getMessage(INVALID_COMMENT_LBL));
        }
        $commentID = is_object($comment) ? $comment->ID : intval($comment);

        // we need the comment object so we can check its permissions
        if (!$comment = $this->get($commentID)->result) {
            return $this->getResponseObject(null, null, Config::getMessage(INVALID_COMMENT_LBL));
        }

        // explicitly check to see if the parent is deleted (at which point we would no longer show this comment in CP)
        if ($comment->Parent && $comment->Parent->ID && $comment->Parent->StatusWithType && $comment->Parent->StatusWithType->StatusType && $comment->Parent->StatusWithType->StatusType->ID === STATUS_TYPE_SSS_COMMENT_DELETED){
            return $this->getResponseObject(null, null, Config::getMessage(INVALID_COMMENT_LBL));
        }

        try {
            // construct - but don't yet save - the flag object
            $flag = new Connect\CommunityCommentFlg();
            $flag->CommunityComment = $commentID;
            $flag->CreatedByCommunityUser = $socialUser->ID;
            $flag->Type = intval($flagType);

            // ensure the user is permitted to flag the comment
            if (!$comment->SocialPermissions->canFlag()) {
                return $this->getResponseObject(null, null, Config::getMessage(USER_DOES_HAVE_PERMISSION_FLAG_COMMENT_LBL));
            }

            if ($existingFlag = $this->getUserFlag($commentID)->result) {
                // ensure the user is permitted to remove the existing flag
                if (!$comment->SocialPermissions->canDeleteFlag($existingFlag)) {
                    return $this->getResponseObject(null, null, Config::getMessage(DOES_PERMISSION_REMOVE_COMMENT_FLAG_LBL));
                }

                $existingFlag->destroy();
            }
            // remove the comment from cache
            $this->removeCommentFromCache($commentID);

            // finally it is OK to save the flag
            $flag->save();
        }
        catch (Connect\ConnectAPIErrorBase $e) {
            return $this->getResponseObject(null, null, $e->getMessage());
        }
        return $this->getResponseObject($flag);
    }

    /**
     * Retrieves the comment count based on the product/category
     * @param string $filterType Filtertype can be product or category.
     * @param array $prodCatList List of products/categories for which comment count should be determined.
     * @param int $count The number of products/categories to be fetched.
     * @param array $sortOrder The order for sorting the list.
     * @return array|null If valid arguments are passed, list of array containing comment count is returned. Otherwise null is returned.
     */
    public function getCommentCountByProductCategory($filterType, array $prodCatList, $count, $sortOrder = null) {
        $result = array();
        if(!empty($prodCatList)) {
            try {
                $sortOrder = $sortOrder ? ' Count(CommunityComments.ID)' : null;
                $roql = sprintf("SELECT CommunityQuestion.{$filterType}, Count(CommunityComments.ID) FROM CommunityQuestion WHERE CommunityQuestion.{$filterType} IN (" . implode(',', $prodCatList) . ") AND Interface.ID = curInterface() AND CommunityComments.StatusWithType.StatusType = %d AND CommunityQuestion.StatusWithType.StatusType = %d GROUP BY {$filterType}" . ($sortOrder ? (" ORDER BY" . $sortOrder . " DESC") : "") . " LIMIT " . $count, STATUS_TYPE_SSS_COMMENT_ACTIVE, STATUS_TYPE_SSS_QUESTION_ACTIVE);
                $firstLevelObjects = Connect\ROQL::query($roql)->next();
                while($row = $firstLevelObjects->next()){
                    $result[$row[$filterType]] = $row['Count(CommunityComments.ID)'];
                }
            }
            catch(Connect\ConnectAPIErrorBase $e) {
                return $this->getResponseObject(null, null, $e->getMessage());
            }
        }
        return $this->getResponseObject($result, 'is_array');
    }

    /**
     * Retrieves a list of ratings for the given comment
     * @param Connect\CommunityComment|int $comment Comment instance or ID to flag
     * @param Connect\CommunityUser $socialUser User for which to retrieve the rating.  Defaults to current user.
     * @return Connect\CommunityCommentRtg|null content rating object for the given user
     */
    public function getUserRating($comment, $socialUser = null) {
        if (!$socialUser || !($socialUser instanceof Connect\CommunityUser)) {
            $socialUserResponseObject = $this->getSocialUser();
            if ($socialUserResponseObject->errors) {
                return $socialUserResponseObject;
            }
            $socialUser = $socialUserResponseObject->result;
        }

        if (!$comment || !($comment instanceof Connect\CommunityComment)) {
            return $this->getResponseObject(null, null, Config::getMessage(INVALID_QUESTION_LBL));
        }

        try {
            $roql = sprintf("SELECT CommunityCommentRtg FROM CommunityCommentRtg r WHERE r.CommunityComment = %d AND r.CreatedByCommunityUser = %d", $comment->ID, $socialUser->ID);

            // perform the query and gather the results
            $results = Connect\ROQL::queryObject($roql)->next();

            if ($results && ($result = $results->next())) {
                $rating = $result;
            }
        }
        catch (Connect\ConnectAPIErrorBase $e) {
            return $this->getResponseObject(null, null, $e->getMessage());
        }

        return isset($rating) ? $this->getResponseObject($rating) : null;
    }

    /**
     * Resets the rating on a comment
     * @param Connect\CommunityComment|int $comment Comment instance or ID to rate
     * @return array|null $ratingValue Indicates the rated value, range is from 1 to 100, inclusive
     */
    public function resetCommentRating ($comment) {
        if ($abuseMessage = $this->isAbuse()) {
            return $this->getResponseObject(false, 'is_bool', $abuseMessage);
        }        

        $socialUserResponseObject = $this->getSocialUser();
        if ($socialUserResponseObject->errors) {
            return $socialUserResponseObject;
        }

        // check for an invalid comment:
        // -null
        // -neither an object nor valid ID
        // -is an object other than a social comment

        $isObject = is_object($comment);

        if ($comment === null ||
            (!$isObject && !Framework::isValidID($comment)) ||
            ($isObject && !($comment instanceof Connect\CommunityComment) && !Framework::isValidID($comment->ID))) {
            return $this->getResponseObject(null, null, Config::getMessage(INVALID_COMMENT_LBL));
        }

        // we need the decorated comment object so we can check its permissions
        $comment = $isObject ? $this->get($comment->ID)->result : $this->get(intval($comment))->result;

        if (!$comment) {
            return $this->getResponseObject(null, null, Config::getMessage(USER_DOES_HAVE_READ_PERMISSI_COMMENT_LBL));
        }

        if ($existingRating = $this->getUserRating($comment)->result) {
            // ensure the user is permitted to remove the existing rating
            if (!$comment->SocialPermissions->canDeleteRating($existingRating)) {
                return $this->getResponseObject(null, null, Config::getMessage(DOES_HAVE_PERMISSION_REMOVE_RATING_LBL));
            }

            $returnData = $existingRating->RatingValue;

            if ($existingRating->destroy()) {
                $this->removeCommentFromCache($comment->ID);
                return $this->getResponseObject($returnData);
            }
        }

        return $this->getResponseObject(false, 'is_bool', Config::getMessage(SORRY_BUT_ACTION_CANNOT_PCT_R_TRY_AGAIN_MSG));
    }

    /**
     * Rates a comment on a scale from 1-100
     * @param Connect\CommunityComment|int $comment Comment instance or ID to rate
     * @param int $ratingValue Indicates the rated value, range is from 1 to 100, inclusive
     * @param int $ratingWeight Indicates the relative weight of the rating, range is from 1 to 100, inclusive. Defaults to 100.
     * @return Connect\CommunityCommentRtg The newly created content rating
     */
    public function rateComment ($comment, $ratingValue, $ratingWeight = 100) {
        $socialUserResponseObject = $this->getSocialUser();
        if ($socialUserResponseObject->errors) {
            return $socialUserResponseObject;
        }
        $socialUser = $socialUserResponseObject->result;

        // check for an invalid comment:
        // -null
        // -neither an object nor valid ID
        // -is an object other than a social comment
        if ($comment === null ||
            (!is_object($comment) && !Framework::isValidID($comment)) ||
            (is_object($comment) && !($comment instanceof Connect\CommunityComment))) {
            return $this->getResponseObject(null, null, Config::getMessage(INVALID_COMMENT_LBL));
        }
        $comment = is_object($comment) ? $comment : $this->get(intval($comment))->result;

        // we need the decorated comment object so we can check its permissions
        if (!$comment = $this->get($comment->ID)->result) {
            return $this->getResponseObject(null, null, Config::getMessage(INVALID_COMMENT_LBL));
        }

        // explicitly check to see if the parent is deleted (at which point we would no longer show this comment in CP)
        if ($comment->Parent && $comment->Parent->ID && $comment->Parent->StatusWithType && $comment->Parent->StatusWithType->StatusType && $comment->Parent->StatusWithType->StatusType->ID === STATUS_TYPE_SSS_COMMENT_DELETED) {
            return $this->getResponseObject(null, null, Config::getMessage(INVALID_COMMENT_LBL));
        }

        // Enforce user can't vote on own comment
        if (isset($socialUser->ID, $comment->CreatedByCommunityUser->ID) && $socialUser->ID === $comment->CreatedByCommunityUser->ID) {
            return $this->getResponseObject(null, null, Config::getMessage(CANNOT_VOTE_ON_OWN_COMMENT_LBL));
        }

        // Check if user has rated
        if ($this->getUserRating($comment, $socialUser) && isset($this->getUserRating($comment, $socialUser)->result) && $this->getUserRating($comment, $socialUser)->result) {
            return $this->getResponseObject(null, null, Config::getMessage(USER_HAS_RATED_ON_THE_CONTENT_LBL));
        }

        try {
            // construct - but don't yet save - the rating object
            $rating = new Connect\CommunityCommentRtg();
            $rating->CommunityComment = $comment->ID;
            $rating->CreatedByCommunityUser = $socialUser->ID;
            $rating->RatingValue = intval($ratingValue);
            $rating->RatingWeight = intval($ratingWeight);

            // ensure the user is permitted to rate the comment
            if (!$comment->SocialPermissions->canRate()) {
                return $this->getResponseObject(null, null, Config::getMessage(USER_DOES_HAVE_PERMISSION_RATE_COMMENT_LBL));
            }

            // remove the comment from cache
            $this->removeCommentFromCache($comment->ID);

            // finally it is OK to save the new rating
            $rating->save();
        }
        catch (Connect\ConnectAPIErrorBase $e) {
            return $this->getResponseObject(null, null, $e->getMessage());
        }
        return $this->getResponseObject($rating);
    }

    /**
     * Fetches all the flag type IDs and their lookup names
     * @return Array Array of Flag IDs and Lookup Names
     */
    public function getFlagTypes () {
        return parent::getFlattenFlagTypes(Connect\CommunityCommentFlg::getMetadata());
    }

    /**
     * Updates moderator action on the comment
     * @param int $commentID ID of the comment to update
     * @param array $data Action data to update the comment with
     * @return Connect\CommunityComment CommunityComment object on success else error
     */
    public function updateModeratorAction($commentID, array $data) {
        $socialObject = $this->update($commentID, $data);
        $objectMetadata = $this->getSocialObjectMetadataMapping('CommunityComment')->result;
        if ($socialObject->result) {
            $response = $this->resetFlagsIfRequiredForThisAction($data['CommunityComment.StatusWithType.Status.ID']->value, $commentID, 'CommunityComment');
            if (isset($response->result) && $response->result) {
                //since reset flag is successful for this comment, so commit both restore and reset flag operations
                Connect\ConnectAPI::commit();
            }
            else if ($response === false) {
                //reset flag operation has failed
                $rollback = true;
            }
        }
        if (isset($rollback) && $rollback) {
            Connect\ConnectAPI::rollback();
            return $this->getResponseObject(null, 'is_null', sprintf($objectMetadata['validation_errors']['general_error'], $commentID));
        }
        return $socialObject;
    }

    /**
     * Returns the zero-based index of a comment. If the specified comment id
     * corresponds to a child (i.e. non-top-level) comment, then the index of
     * its top-level parent is returned. If the specified comment id is invalid,
     * the comment specified by the id doesn't exist, or is prohibited from displaying,
     * -1 is returned.
     * @param  int $commentID Comment ID
     * @return int Zero-based index of the specified comment or -1
     */
    public function getIndexOfTopLevelComment($commentID) {
        if (!Framework::isValidID($commentID)
            || !($comment = $this->getTabular($commentID, false)->result))
            return -1;

        $question = $this->CI->model('CommunityQuestion')->get($comment->CommunityQuestion->ID)->result;

        $sql = $this->buildSelectROQL('count() AS count', 'CommunityComment c', array(
            "c.CommunityQuestion = {$comment->CommunityQuestion->ID}",
            "c.Parent IS NULL",
            $this->getCommentStatusTypeFilters($question),
            "c.ID <= {$commentID}",
        ));

        $query = ConnectTabular::query($sql);
        $result = $query->getFirst();

        return ((int) $result->count) - 1;
    }

    /**
     * Utility method to set the value on the Comment object. Handles more complex types such as comment entries
     * and file attachments.
     * @param Connect\RNObject $comment Current comment object that is being created/updated
     * @param string $fieldName Name of the field we're setting
     * @param mixed $fieldValue Value of the field.
     * @param string $fieldType Common object model field type
     * @return null|string Returns null upon success or an error message from Connect::setFieldValue upon error.
     */
    protected function setFieldValue(Connect\RNObject $comment, $fieldName, $fieldValue, $fieldType = null){
        if($fieldType === 'Comment'){
            $this->createCommentEntry($comment, $fieldValue);
        }
        else if($fieldType === 'FileAttachmentCommunity') {
            $this->createAttachmentEntry($comment, $fieldValue);
        }
        else{
            if (strtolower($fieldName) === 'communitycomment.body') {
                // All comments submitted from CP will be html.
                $contentType = new Connect\NamedIDOptList();
                $contentType->LookupName = 'text/html';
                parent::setFieldValue($comment, 'CommunityComment.BodyContentType', $contentType);
            }
            return parent::setFieldValue($comment, $fieldName, $fieldValue);
        }
    }

    /**
     * Utility function to create a thread entry object with the specified value. Additionally sets
     * values for the entry type and channel of the thread.
     * @param Connect\Comment $comment Current comment object that is being created/updated
     * @param string $value Comment value
     */
    protected function createCommentEntry(Connect\Comment $comment, $value){
        if($value !== null && $value !== false && $value !== ''){
            $comment = $comment->Comments->fetch(CONNECT_DISCUSSION_ENDUSER);
            $comment->Comments = new Connect\CommentArray();
            $comment = $comment->Comments[] = new Connect\Comment();
            $comment->Body = $value;
            $comment->CreatedByCommunityUser = $this->CI->model('CommunityUser')->get()->result;
        }
    }
    
    /**
     * Get total number of comments
     * @return int count
     */
    public function getTotalCommentCount() {
        $result = Connect\ROQL::query('SELECT count() AS count FROM CommunityComment')->next()->next();
        return (int) $result['count'];
    }
}
