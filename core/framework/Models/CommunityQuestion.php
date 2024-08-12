<?php
namespace RightNow\Models;

use RightNow\Connect\v1_4 as Connect,
    RightNow\Utils\Connect as ConnectUtil,
    RightNow\Libraries\ConnectTabular,
    RightNow\Utils\Config,
    RightNow\Utils\Framework,
    RightNow\Libraries\Decorator,
    RightNow\Utils\Validation,
    RightNow\Utils\Text,
    RightNow\ActionCapture,
    RightNow\Libraries\Hooks,
    RightNow\Api,
    RightNow\Libraries\AbuseDetection,
    RightNow\Utils\Date as DateUtil;

/**
 * Methods for retrieving social questions
 */
class CommunityQuestion extends CommunityObjectBase
{
    /**
     * Returns an empty question structure. Used to be able to access question
     * fields without having an exsiting question ID.
     *
     * @return Connect\CommunityQuestion An instance of the Connect question object
     */
    public function getBlank () {
        return $this->getResponseObject(parent::getBlank());
    }

    /**
     * Returns a Question middle layer object from the database based on the question id.
     *
     * @param int|null $questionID The id of the question to retrieve.
     * @return Connect\CommunityQuestion An instance of the Connect question object
     */
    public function get($questionID) {
        $question = parent::get($questionID);
        if(!is_object($question)) {
            return $this->getResponseObject(null, null, $question);
        }
        if($question->Interface->ID !== $this->interfaceID) {
            return $this->getResponseObject(null, null, Config::getMessage(USER_DOES_HAVE_READ_PERMISSI_QUESTION_LBL));
        }
        Decorator::add($question, array('class' => 'Permission/SocialQuestionPermissions', 'property' => 'SocialPermissions'));

        if ($question->SocialPermissions->isDeleted()) {
            return $this->getResponseObject(null, null, Config::getMessage(CANNOT_FIND_MAY_DELETED_ANOTHER_USER_MSG));
        }
        // ensure the user has permission to view this object
        if (!$question->SocialPermissions->canRead()) {
            return $this->getResponseObject(null, null, Config::getMessage(USER_DOES_HAVE_READ_PERMISSI_QUESTION_LBL));
        }
        return $this->getResponseObject($question);
    }

    /**
     * Determines if a Question with the given ID exists.
     *
     * @param int|null $questionID The id of the question to check.
     * @return boolean Whether the Question exists
     */
    public function exists($questionID) {
        if(!Framework::isValidID($questionID)){
            return false;
        }
        return Connect\CommunityQuestion::first("ID = $questionID") !== null;
    }

    /**
     * Returns a tabular CommunityQuestion from the process cache.
     * @param int $questionID The id of the question to retrieve.
     * @return array Tabular question data
     */
    public function getTabular($questionID) {
        if ($tabularQuestion = $this->getQuestionFromCache($questionID)) {
            // because this is a short-lived cache, we don't check permissions on a cache hit
            return $this->getResponseObject($tabularQuestion, 'is_array');
        }

        // Deal with cache miss - note that read permissions are implicitly checked by ROQL
        $roqlSelectFrom = $this->getQuestionSelectROQL(sprintf("q.ID = %d AND q.Interface.ID = %d", $questionID, $this->interfaceID), 1);

        $tabularQuery = ConnectTabular::query($roqlSelectFrom);
        if($tabularQuestion = $tabularQuery->getFirst()) {
            if($user = $this->CI->model('CommunityUser')->get()->result) {
                // Get rating info
                if($ratingResult = $this->getRatingsForQuestion($questionID, $user->ID)) {
                    $tabularQuestion = ConnectTabular::mergeQueryResults($tabularQuestion, $ratingResult);
                }

                // Get flagging info
                if($flagResult = $this->getFlagsForQuestion($questionID, $user->ID)) {
                    $tabularQuestion = ConnectTabular::mergeQueryResults($tabularQuestion, $flagResult);
                }
            }
            $this->addQuestionToCache($tabularQuestion);
        }
        else {
            return $this->getResponseObject(null, null, $tabularQuery->error);
        }
        return $this->getResponseObject($tabularQuestion);
    }

    /**
     * Creates a question. In order to create a question, a contact must be logged-in. It also subscribes the logged in
     * user if 'CommunityUser.Subscribe' form parameter is passed. Form data is expected to look like
     *
     *      -Keys are Field names (e.g. Question.Subject)
     *      -Values are objects with the following members:
     *          -value: (string) value to save for the field
     *          -required: (boolean) Whether the field is required
     *
     * @param array $formData Form fields to update the question with. In order to be created successfully, a contact must be logged in
     * @param boolean $smartAssist Parameter that controls smart assistant suggestions
     * @return Connect\CommunityQuestion|array|null Created question object, array of SmartAssistant data, or null if there are error messages and the question wasn't created
     */
    public function create (array $formData, $smartAssist = false) {
        $socialUserResponseObject = $this->getSocialUser();
        if($socialUserResponseObject->errors)
            return $socialUserResponseObject;
        $socialUser = $socialUserResponseObject->result;
        $contact = $socialUser->Contact;
        $question = $this->getBlank()->result;

        if($contact->Disabled){
            // Disabled contacts can't create questions
            return $this->getResponseObject(null, null, Config::getMessage(SORRY_THERES_ACCT_PLS_CONT_SUPPORT_MSG));
        }

        // pull social user for logged in contact and set author
        $question->CreatedByCommunityUser = $this->CI->model('CommunityUser')->getForContact($contact->ID)->result;
        $errors = $warnings = array();

        // We need unsanitized data from the form since customer can write html text as content of the socialquestion body
        // and add some formatting using the ckeditor toolbars e.g. <p><strong>Bold Text</strong>$ltem&gt Em tag to italicize
        // We will be purifying this data using some tool like html purifier
        if($rawFields = $this->CI->model('Field')->getRawFormFields('Communityquestion.Body')) {
            $formData['Communityquestion.Body']->value  = $rawFields->value;
        }

        foreach ($formData as $name => $field) {
            if(!Text::beginsWith($name, 'CommunityQuestion')
                && !Text::beginsWith($name, 'Communityquestion')){
                continue;
            }
            $fieldName = explode('.', $name);
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
            if(Validation::validate($field, $name, $fieldMetaData, $errors)) {
                //Convert date in string format to numeric value if the field type is "DateTime".
                $field->value = DateUtil::convertDateTimeStringToNumericValue($field->value, $fieldMetaData);

                if($setFieldError = $this->setFieldValue($question, $name, $field->value, $fieldMetaData->COM_type)) {
                    $errors[] = $setFieldError;
                }
            }
        }

        if ($errors) {
            return $this->getResponseObject(null, null, $errors);
        }

        Decorator::add($question, array('class' => 'Permission/SocialQuestionPermissions', 'property' => 'SocialPermissions'));

        try{
            // check the permission after the object is constructed so the check will have the data it needs
            if (!$question->SocialPermissions->canCreate()) {
                return $this->getResponseObject(null, null, Config::getMessage(DO_PERMISSION_CREATE_SOCIAL_QUESTION_LBL));
            }
            if ($smartAssist === true) {
                // OKCS API Hook
                $hookData = array('formData' => $formData, 'token' => $this->getKnowledgeApiSessionToken(), 'canEscalate' => true, 'apiInvoked' => false);

                // if OKCS API is invoked and has no error, return the data to SA Dialog
                if ((!is_string($hookError = Hooks::callHook('pre_retrieve_smart_assistant_answers', $hookData))) && $hookData['apiInvoked']) {
                    if (is_array($hookData['suggestions']))
                        return $this->getResponseObject($hookData, 'is_array');
                }
                else {
                    Api::phpoutlog($hookError);
                }

                //run KFAPI if the OKCS API wasn't invoked at all
                Connect\ConnectAPI::setSource(SRC2_EU_SMART_ASST);
                $smartAssistantResults = $this->getExtendedSmartAssistantResults($question->Subject, $question->Body);
                Connect\ConnectAPI::releaseSource(SRC2_EU_SMART_ASST);
                //Return a response to the SA dialog if either there are results to display or we tried to run rules, but couldn't find anything
                if(is_array($smartAssistantResults) && isset($smartAssistantResults['suggestions']) && is_array($smartAssistantResults['suggestions']) && (count($smartAssistantResults['suggestions']) > 0 || !empty($smartAssistantResults['rulesMatched']))) {
                    unset($smartAssistantResults['rulesMatched']);
                    $smartAssistantResults['hasSA'] = true;
                    //bonus hit is needed on Community ask page with captcha verified when smart assistant popup appears. 
                    if(Framework::doesTokenRequireChallenge($this->CI->input->post('f_tok'))){
                        AbuseDetection::giveBonusHit();
                    }
                    return $this->getResponseObject($smartAssistantResults, 'is_array');
                }
            }
            $question = parent::createObject($question, SRC2_EU_AAQ);
        }
        catch(\Exception $e){
            $question = $e->getMessage();
        }

        if(!is_object($question)){
            return $this->getResponseObject(null, null, $question);
        }

        // Subscribe the logged in user to the created question
        if($formData['CommunityUser.Subscribe']) {
            $subscribe = $this->CI->model('SocialSubscription')->addSubscription($question->ID, 'Question');
            if(!$subscribe->result) {
                $warnings[] = $subscribe->error->externalMessage;
            }
        }

        return $this->getResponseObject($question, 'is_object', null, $warnings);
    }

    /**
     * Updates the specified question with the given form data. Form data is expected to look like
     *
     *      -Keys are Field names (e.g. Question.Subject)
     *      -Values are objects with the following members:
     *          -value: (string) value to save for the field
     *          -required: (boolean) Whether the field is required
     *
     * @param int $questionID ID of the question to update
     * @param array $formData Form fields to update the question with
     * @return Connect\CommunityQuestion|null Updated question object or error messages if the question wasn't updated
     */
    public function update($questionID, array $formData) {
        $socialUserResponseObject = $this->getSocialUser();
        if($socialUserResponseObject->errors)
            return $socialUserResponseObject;
        $socialUser = $socialUserResponseObject->result;

        $question = $this->get($questionID);
        if (!$question->result) {
            // Error: return the ResponseObject
            return $question;
        }
        $question = $question->result;

        // set ContentLastUpdatedByCommunityUser null so that the core will populate it correctly
        // if the user has also specified a value then it will overwrite this
        $question->ContentLastUpdatedByCommunityUser = null;

        $errors = $warnings = array();

        // the user must either be
        // - authorized to edit their own question AND logged in as the question's author
        // - authorized to edit other people's questions
        if (!$question->SocialPermissions->canUpdate()) {
            return $this->getResponseObject(null, null, Config::getMessage(DOES_HAVE_PERMISSION_EDIT_QUESTION_LBL));
        }

        foreach ($formData as $name => $field) {
            if(is_string($field->value)) {
                // trim any whitespace
                $field->value = trim($field->value);
            }
            $fieldName = explode('.', $name);

            // permission required to update Status field, depending on the value
            // note that setting Status via LookupName only works on create, so we don't need to consider it here
            if (Text::endsWith($name, 'Status.ID') || Text::endsWith($name, 'Status')) {
                // get a list of the status IDs that mean Deleted
                $deletedStatuses = $this->getStatusesFromStatusType(STATUS_TYPE_SSS_QUESTION_DELETED)->result;

                // deletions need the delete permission, everything else needs the status change permission
                if (in_array($field->value, $deletedStatuses)) {
                    if (!$question->SocialPermissions->canDelete()) {
                        return $this->getResponseObject(null, null, Config::getMessage(DOES_HAVE_PERMISSION_DELETE_QUESTION_LBL));
                    }
                }
                else if(!$question->SocialPermissions->canUpdateStatus()) {
                    return $this->getResponseObject(null, null, Config::getMessage(DOES_PERMISSI_CHANGE_STATUS_QUESTION_LBL));
                }
            }

            // permission required to update site interface
            if (Text::endsWith($name, 'Interface') && !$question->SocialPermissions->canUpdateInterface()) {
                return $this->getResponseObject(null, null, Config::getMessage(DOES_PERMISSION_CHANGE_QUESTION_LBL));
            }

            // permission required to lock/unlock a question
            if (Text::endsWith($name, 'ContentLocked') && !$question->SocialPermissions->canUpdateLock()) {
                return $this->getResponseObject(null, null, Config::getMessage(DOES_PERMISSION_LOCK_UNLOCK_QUESTION_LBL));
            }

            if (Text::endsWith($name, 'LastActivityTime')) {
                $lastActivityTime = intval($field->value);
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
            if (Validation::validate($field, $name, $fieldMetaData, $errors)) {
                //Convert date in string format to numeric value if the field type is "DateTime".
                $field->value = DateUtil::convertDateTimeStringToNumericValue($field->value, $fieldMetaData);

                if($setFieldError = $this->setFieldValue($question, $name, $field->value, $fieldMetaData->COM_type)) {
                    $errors[] = $setFieldError;
                }
            }
        }
        if ($errors) {
            return $this->getResponseObject(null, null, $errors);
        }

        try{
            // remove the question from cache before updating
            $this->removeQuestionFromCache($question->ID);
            $question = parent::updateObject($question, SRC2_EU_MYSTUFF_Q);
        }
        catch(\Exception $e){
            $question = $e->getMessage();
        }
        if(!is_object($question)){
            return $this->getResponseObject(null, null, $question);
        }

        // touch question to have an accurate LastActivityTime
        $touchedQuestion = $this->touch($question, isset($lastActivityTime) ? $lastActivityTime : null);
        if ($touchedQuestion->errors) {
            return $touchedQuestion;
        }

        return $this->getResponseObject($question, 'is_object', null, $warnings);
    }

    /**
     * Gets the total number of top-level comments for
     * the question.
     * @param  object $question Question object instance
     * @return int Number of comments
     */
    public function getTopLevelCommentCount($question) {
        return $this->getCommentCount($question, false);
    }

    /**
     * Retrieves the comments for a given question in tabular form (e.g. not objects).  Includes
     * rating data for the current user and rating summary data. Data is returned in the order it
     * should be displayed.
     * @param Connect\CommunityQuestion $question Question object to query for comments
     * @param int  $pageSize Number of top-level Comments per page, default=0=unlimited
     * @param int  $pageNumber Page number to retrieve
     * @param string $sortOrder Sorting order for SocialComments.
     * @return ResponseObject Array of SocialComments
     */
    public function getComments($question, $pageSize = 0, $pageNumber = 1, $sortOrder = 'ASC') {
        if (!$question || !($question instanceof Connect\CommunityQuestion)) {
            return $this->getResponseObject(null, null, Config::getMessage(INVALID_QUESTION_LBL));
        }

        // we need to do two queries for the comments - one for top level comments and another
        // for their descendants.  The bulk of each query is the same, so we'll reuse the fragment
        $roqlSelectFrom = $this->getOrderedCommentSelectROQL("c.CommunityQuestion = %d AND c.Parent IS NULL", '', $question, $sortOrder);
        $topLevelRoql = sprintf($roqlSelectFrom, $question->ID);
        // pagination only applies to top level comments, and only if the page size is a positive integer
        // need to decrement the page number since they are 1-based and SQL needs 0-based
        if ($pageSize > 0) {
            $topLevelRoql .= sprintf(" LIMIT %d,%d", $pageSize * ($pageNumber - 1), $pageSize);
        }
        $query = ConnectTabular::query($topLevelRoql, false);
        $topLevelComments = $query->getCollection(array('class' => 'Permission/SocialCommentPermissions', 'property' => 'SocialPermissions'));
        $topLevelCommentIDs = array_map(function ($comment) {
            return $comment->ID;
        }, $topLevelComments);

        if (!empty($topLevelCommentIDs)) {
            // now that we have the top level IDs, use them to fetch the descendants
            // don't worry about SQL injection since we got the IDs from the DB
            $validationFunction = 'is_array';
            $comments = $this->attachChildComments($question, $topLevelComments, $topLevelCommentIDs);
        }
        else {
            // we didn't have any comments, so we should be returning a null response
            $validationFunction = 'is_null';
        }
        return $this->getResponseObject(isset($comments) ? $comments : null, $validationFunction, $query->error);
    }

    /**
     * Retrieve the Question's BestAnswers in a list including decorated comments.
     * @param  Connect\CommunityQuestion $question Question instance for best answers
     * @return Array|null List of best answers or null if the Question is invalid
     */
    public function getBestAnswers(Connect\CommunityQuestion $question) {
        $result = array();
        if($question->BestCommunityQuestionAnswers) {
            foreach($question->BestCommunityQuestionAnswers as $bestAnswer) {
                if($bestAnswer->CommunityComment->StatusWithType->StatusType->ID !== STATUS_TYPE_SSS_COMMENT_DELETED &&
                    (!isset($bestAnswer->CommunityComment->Parent->StatusWithType->StatusType->ID) || !($bestAnswer->CommunityComment->Parent->StatusWithType->StatusType->ID) || $bestAnswer->CommunityComment->Parent->StatusWithType->StatusType->ID !== STATUS_TYPE_SSS_COMMENT_DELETED)) {      
                    Decorator::add($bestAnswer->CommunityComment, array('class' => 'Permission/SocialCommentPermissions', 'property' => 'SocialPermissions'));
                    $result[] = $bestAnswer;
                }
            }
        }

        return $this->getResponseObject($result, 'is_array');
    }

    /**
     * Retrieves the Question's BestAnswer instance for the given comment.
     * @param Connect\CommunityQuestion $question Question instance to look for BestAnswer
     * @param Connect\Comment $comment Comment instance
     * @param Number $type Type of answer (SSS_BEST_ANSWER_AUTHOR, SSS_BEST_ANSWER_MODERATOR, SSS_BEST_ANSWER_COMMUNITY)
     *                     if not specified, the first BestAnswer matching the comment is returned
     * @return Connect\BestSocialAnswer|null BestAnswer for the comment or null if not found
     */
    public function getBestAnswerForComment(Connect\CommunityQuestion $question, $comment, $type = null) {
        if($comment->StatusWithType->StatusType->ID === STATUS_TYPE_SSS_COMMENT_DELETED) {
            return $this->getResponseObject(null, null, null, Config::getMessage(GIVEN_T_DEL_THEREFORE_NO_BEST_ANSWER_LBL));
        }

        $foundAnswer = null;
        if ($question->BestCommunityQuestionAnswers) {
            foreach ($question->BestCommunityQuestionAnswers as $bestAnswer) {
                if ($bestAnswer->CommunityComment->ID === (int) $comment->ID && (!$type || $type === $bestAnswer->BestAnswerType->ID)) {
                    $foundAnswer = $bestAnswer;
                    break;
                }
            }
        }

        return $this->getResponseObject($foundAnswer, null, null,
            ($foundAnswer) ? null : Config::getMessage(DIDNT_FIND_BEST_ANSWER_GIVEN_COMMENT_LBL));
    }

    /**
     * Marks the given comment on that comment's question as a best answer whose
     * type depends upon the role of the currently logged in user.
     * @param int $commentID Comment id
     * @param int $markAsType Best answer user define (SSS_BEST_ANSWER_AUTHOR or SSS_BEST_ANSWER_MODERATOR)
     * @return Connect\BestSocialAnswerArray The question's best answers
     */
    public function markCommentAsBestAnswer($commentID, $markAsType = null) {
        $socialUserResponseObject = $this->getSocialUser();
        if($socialUserResponseObject->errors)
            return $socialUserResponseObject;
        $socialUser = $socialUserResponseObject->result;

        if (!$comment = $this->CI->model('CommunityComment')->get($commentID)->result) {
            return $this->getResponseObject(null, null, Config::getMessage(CANNOT_FIND_DELETED_ANOTHER_USER_MSG));
        }
        if (!$question = $comment->CommunityQuestion) {
            return $this->getResponseObject(null, null, Config::getMessage(CANNOT_FIND_MAY_DELETED_ANOTHER_USER_MSG));
        }
        if (!$markAsType) {
            $markAsType = $this->getBestAnswerTypeForUser($question, $socialUser);
        }

        // ensure the user is permitted to set the best answer.  If it is their question they can
        // mark it as such, or if they have permission to mark others' questions as best answer
        if (!$question->SocialPermissions->canSelectBestAnswerAs($markAsType)) {
            return $this->getResponseObject(null, null, Config::getMessage(DOES_HAVE_PERMISSION_SET_BEST_ANSWER_LBL));
        }

        try {
            // remove the question and comment from cache
            $this->removeQuestionFromCache($question->ID);
            $this->removeCommentFromCache($comment->ID);

            if ($question->BestCommunityQuestionAnswers) {
                foreach ($question->BestCommunityQuestionAnswers as $answerIndex => $prevBestAnswer) {
                    if ($prevBestAnswer->BestAnswerType->ID === $markAsType) {
                        $question->BestCommunityQuestionAnswers->offsetUnset(intval($answerIndex));
                        $question->save();
                    }
                }
            }

            $bestAnswer = new Connect\BestCommunityQuestionAnswer();
            $bestAnswer->BestAnswerType->ID = $markAsType;
            $bestAnswer->CommunityComment = $comment;
            $bestAnswer->CommunityUser = $socialUser;
            $question->BestCommunityQuestionAnswers[] = $bestAnswer;
            $question->save();
        }
        catch (Connect\ConnectAPIErrorBase $e) {
            return $this->getResponseObject(null, null, $e->getMessage());
        }

        // touch question to have an accurate LastActivityTime
        $touchedQuestion = $this->touch($question);
        if ($touchedQuestion->errors) {
            return $touchedQuestion;
        }

        return $this->getResponseObject($question->BestCommunityQuestionAnswers);
    }

     /**
     * Unmarks the given comment on that comment's question as a best answer whose
     * type depends upon the role of the currently logged in user.
     * @param int $commentID Comment id
     * @param int $markAsType Best answer user define (SSS_BEST_ANSWER_AUTHOR or SSS_BEST_ANSWER_MODERATOR)
     * @return Connect\BestSocialAnswerArray|null The question's best answers
     */
    public function unmarkCommentAsBestAnswer($commentID, $markAsType = null) {
        $socialUserResponseObject = $this->getSocialUser();
        if($socialUserResponseObject->errors)
            return $socialUserResponseObject;
        $socialUser = $socialUserResponseObject->result;
        if (!$comment = $this->CI->model('CommunityComment')->get($commentID)->result) {
            return $this->getResponseObject(null, null, Config::getMessage(CANNOT_FIND_DELETED_ANOTHER_USER_MSG));
        }
        if (!$question = $comment->CommunityQuestion) {
            return $this->getResponseObject(null, null, Config::getMessage(CANNOT_FIND_MAY_DELETED_ANOTHER_USER_MSG));
        }
        if (!$markAsType) {
            $markAsType = $this->getBestAnswerTypeForUser($question, $socialUser);
        }

        // ensure the user is permitted to set the best answer.  If it is their question they can
        // mark it as such, or if they have permission to mark others' questions as best answer
        if (!$question->SocialPermissions->canSelectBestAnswerAs($markAsType)) {
            return $this->getResponseObject(null, null, Config::getMessage(DOES_HAVE_PERMISSION_SET_BEST_ANSWER_LBL));
        }

        try {
            // remove the question and comment from cache
            $this->removeQuestionFromCache($question->ID);
            $this->removeCommentFromCache($comment->ID);
            if ($question->BestCommunityQuestionAnswers) {
                foreach ($question->BestCommunityQuestionAnswers as $answerIndex => $prevBestAnswer) {
                    if ($prevBestAnswer->BestAnswerType->ID === $markAsType) {
                        $question->BestCommunityQuestionAnswers->offsetUnset(intval($answerIndex));
                        $question->save();
                        return $question->BestCommunityQuestionAnswers ? $this->getResponseObject($question->BestCommunityQuestionAnswers) : $this->getResponseObject(null, null);
                    }
                }
            }
        }
        catch (Connect\ConnectAPIErrorBase $e) {
            return $this->getResponseObject(null, null, $e->getMessage());
        }
    }

    /**
     * Retrieves a list of flags for the given question
     * @param Connect\CommunityQuestion|int $question Question instance or ID to flag
     * @param Connect\CommunityUser $user User for which to retrieve the flag.  Defaults to current user.
     * @return Connect\CommunityQuestionFlg|null content flag object for the given user
     */
    public function getUserFlag($question, $user = null) {
        if (is_object($question)) {
            $question = $question->ID;
        }
        if (!$user && Framework::isLoggedIn()){
            // default to the current user if one is not provided
            $user = $this->CI->model('CommunityUser')->get()->result;
        }
        if (!$user){
            return $this->getResponseObject(null, null, Config::getMessage(USER_NOT_PROVIDED_AND_NO_LOGGED_USER_LBL));
        }
        if (!($user instanceof Connect\CommunityUser)){
            return $this->getResponseObject(null, null, Config::getMessage(INVALID_USER_OBJECT_LBL));
        }
        try {
            $roql = sprintf("SELECT CommunityQuestionFlg FROM CommunityQuestionFlg f WHERE f.CommunityQuestion = %d AND f.CreatedByCommunityUser = %d", $question, $user->ID);

            // perform the query and gather the results
            $results = Connect\ROQL::queryObject($roql)->next();

            if($results && ($result = $results->next())) {
                $flag = $result;
            }
        }
        catch (Connect\ConnectAPIErrorBase $e) {
            return $this->getResponseObject(null, null, $e->getMessage());
        }
        return $this->getResponseObject(isset($flag) ? $flag : null, isset($flag) ? ($flag ? 'is_object' : 'is_null') : 'is_null');
    }

    /**
     * Flags a question as inappropriate, miscatgorized, copyrighted, or other reasons
     * @param Connect\CommunityQuestion|int $question Question instance or ID to flag
     * @param int|null $flagType Optional.  Indicates the reason for flagging, defaults to 1 which is "Inappropriate"
     * @return Connect\CommunityQuestionFlg The newly created content flag
     */
    public function flagQuestion($question, $flagType = 1) {
        $socialUserResponseObject = $this->getSocialUser();
        if($socialUserResponseObject->errors)
            return $socialUserResponseObject;

        // check for an invalid question:
        // -null
        // -neither an object nor valid ID
        // -is an object other than a social question
        if ($question === null ||
            (!is_object($question) && !Framework::isValidID($question)) ||
            (is_object($question) && !($question instanceof Connect\CommunityQuestion))) {
            return $this->getResponseObject(null, null, Config::getMessage(INVALID_QUESTION_LBL));
        }

        // fetch the decorated question
        $question = is_object($question) ? $question : $this->get($question)->result;
        if (!$question) {
            return $this->getResponseObject(null, null, Config::getMessage(INVALID_QUESTION_ID_UC_LBL));
        }

        try {
            // ensure the user is permitted to flag the question
            if (!$question->SocialPermissions->canFlag()) {
                return $this->getResponseObject(null, null, Config::getMessage(DOES_HAVE_PERMISSION_FLAG_QUESTION_LBL));
            }

            if ($existingFlag = $this->getUserFlag($question->ID)->result) {
                // ensure the user is permitted to remove the existing flag
                if (!$question->SocialPermissions->canDeleteFlag($existingFlag)) {
                    return $this->getResponseObject(null, null, Config::getMessage(DOES_HAVE_PERMISSION_REMOVE_FLAG_LBL));
                }

                $existingFlag->destroy();
            }

            $flag = new Connect\CommunityQuestionFlg();
            $flag->CommunityQuestion = $question->ID;
            $flag->CreatedByCommunityUser = $socialUserResponseObject->result->ID;
            $flag->Type = intval($flagType);

            // remove the question from cache
            $this->removeQuestionFromCache($question->ID);

            // finally it is OK to save the flag
            $flag->save();
        }
        catch (Connect\ConnectAPIErrorBase $e) {
            return $this->getResponseObject(null, null, $e->getMessage());
        }
        return $this->getResponseObject($flag);
    }

    /**
     * Retrieves a list of ratings for the given question
     * @param Connect\CommunityQuestion|int $question Question instance to rate
     * @param Connect\CommunityUser $user User for which to retrieve the rating.  Defaults to current user.
     * @return Connect\CommunityQuestionRtg|null content rating object for the given user
     */
    public function getUserRating($question, $user = null) {
        $rating = [];
        if (!$user && Framework::isLoggedIn()){
            // default to the current user if one is not provided
            $user = $this->CI->model('CommunityUser')->get()->result;
        }
        if (!$user){
            return $this->getResponseObject(null, null, Config::getMessage(USER_NOT_PROVIDED_AND_NO_LOGGED_USER_LBL));
        }
        if (!($user instanceof Connect\CommunityUser)){
            return $this->getResponseObject(null, null, Config::getMessage(INVALID_USER_OBJECT_LBL));
        }
        // check for an invalid question:
        // -null
        // -neither an object nor valid ID
        // -is an object other than a social question
        if ($question === null ||
            (!is_object($question) && !Framework::isValidID($question)) ||
            (is_object($question) && !($question instanceof Connect\CommunityQuestion))) {
            return $this->getResponseObject(null, null, Config::getMessage(INVALID_QUESTION_LBL));
        }
        $questionID = is_object($question) ? $question->ID : intval($question);

        try {
            $roql = sprintf("SELECT CommunityQuestionRtg FROM CommunityQuestionRtg r WHERE r.CommunityQuestion = %d AND r.CreatedByCommunityUser = %d", $questionID, $user->ID);

            // perform the query and gather the results
            $results = Connect\ROQL::queryObject($roql)->next();

            if($results && ($result = $results->next())) {
                $rating = $result;
            }
        }
        catch (Connect\ConnectAPIErrorBase $e) {
            return $this->getResponseObject(null, null, $e->getMessage());
        }

        return $this->getResponseObject($rating);
    }

    /**
     * Resets the rating on a question
     * @param Connect\CommunityQuestion|int $question Question instance or ID to rate
     * @return array|null $ratingValue Indicates the rated value, range is from 1 to 100, inclusive
     */
    public function resetQuestionRating ($question) {
        if ($abuseMessage = $this->isAbuse()) {
            return $this->getResponseObject(false, 'is_bool', $abuseMessage);
        }

        $socialUserResponseObject = $this->getSocialUser();
        if ($socialUserResponseObject->errors)
            return $socialUserResponseObject;

        // check for an invalid question:
        // -null
        // -neither an object nor valid ID
        // -is an object other than a social question
        $isObject = is_object($question);

        if ($question === null ||
            (!$isObject && !Framework::isValidID($question)) ||
            ($isObject && !($question instanceof Connect\CommunityQuestion) && !Framework::isValidID($question->ID))) {
            return $this->getResponseObject(null, null, Config::getMessage(INVALID_QUESTION_LBL));
        }
        // fetch the decorated question
        $question = $isObject ? $this->get($question->ID)->result : $this->get($question)->result;

        if (!$question) {
            return $this->getResponseObject(null, null, Config::getMessage(USER_DOES_HAVE_READ_PERMISSI_QUESTION_LBL));
        }

        if ($existingRating = $this->getUserRating($question->ID)->result) {
            // ensure the user is permitted to remove the existing rating
            if (!$question->SocialPermissions->canDeleteRating($existingRating)) {
                return $this->getResponseObject(null, null, Config::getMessage(DOES_HAVE_PERMISSION_REMOVE_RATING_LBL));
            }

            $returnData = $existingRating->RatingValue;

            if ($existingRating->destroy()) {
                $this->removeQuestionFromCache($question->ID);
                return $this->getResponseObject($returnData);
            }
        }
        return $this->getResponseObject(false, 'is_bool', Config::getMessage(SORRY_BUT_ACTION_CANNOT_PCT_R_TRY_AGAIN_MSG));
    }

    /**
     * Rates a question on a scale from 1-100
     * @param Connect\CommunityQuestion|int $question Question instance or ID to rate
     * @param int $ratingValue Indicates the rated value, range is from 1 to 100, inclusive
     * @param int $ratingWeight Indicates the relative weight of the rating, range is from 1 to 100, inclusive. Defaults to 100.
     * @return Connect\CommunityQuestionRtg The newly created content rating
     */
    public function rateQuestion($question, $ratingValue, $ratingWeight = 100) {        
        if ($abuseMessage = $this->isAbuse()) {
            return $this->getResponseObject(false, 'is_bool', $abuseMessage);
        }

        $socialUserResponseObject = $this->getSocialUser();
        if($socialUserResponseObject->errors)
            return $socialUserResponseObject;

        // check for an invalid question:
        // -null
        // -neither an object nor valid ID
        // -is an object other than a social question
        if ($question === null ||
            (!is_object($question) && !Framework::isValidID($question)) ||
            (is_object($question) && !($question instanceof Connect\CommunityQuestion))) {
            return $this->getResponseObject(null, null, Config::getMessage(INVALID_QUESTION_LBL));
        }
        // fetch the decorated question
        $question = is_object($question) ? $question : $this->get($question)->result;
        if (!$question) {
            return $this->getResponseObject(null, null, Config::getMessage(INVALID_QUESTION_ID_UC_LBL));
        }

        // Check if user has voted
        if ($this->getUserRating($question->ID)->result) {
            return $this->getResponseObject(null, null, Config::getMessage(USER_HAS_RATED_ON_THE_CONTENT_LBL));
        }

        try {
            // ensure the user is permitted to rate the question
            if (!$question->SocialPermissions->canRate()) {
                return $this->getResponseObject(null, null, Config::getMessage(DOES_HAVE_PERMISSION_RATE_QUESTION_LBL));
            }

            $rating = new Connect\CommunityQuestionRtg();
            $rating->CommunityQuestion = $question->ID;
            $rating->CreatedByCommunityUser = $socialUserResponseObject->result->ID;
            $rating->RatingValue = intval($ratingValue);
            $rating->RatingWeight = intval($ratingWeight);

            // remove the question from cache
            $this->removeQuestionFromCache($question->ID);

            // finally it is OK to save the new rating
            $rating->save();
        }
        catch (Connect\ConnectAPIErrorBase $e) {
            return $this->getResponseObject(null, null, $e->getMessage());
        }
        return $this->getResponseObject($rating);
    }

    /**
     * Retrieves a list of recent questions
     *
     * $filters
     *      ['maxQuestions'] int - Maximum number of questions - defaults to 10
     *      ['answerType'] array - An array containing the type(s) of user(s) who selected the question's best
     *          answer(s). Valid values are 'author', 'moderator', and 'community'; array may also be empty,
     *          which returns no best answers (because there are no best answers without authors). Defaults to an
     *          array containing both 'author' and 'moderator'
     *      ['product'] int - Product ID
     *      ['category'] int - Category ID
     *      ['includeChildren'] boolean - If a product or category filter is set, should questions
     *          assigned to children products or categories be included in the results.
     *      ['questionsFilter'] string - 'with', 'without', 'both' - defaults to 'with'. Specifies
     *          if result should be filtered on having a 'best answer' or not. When 'answerType'
     *          filter is empty, this filter is overriden to 'both'
     *
     * @param array $filters Options hash to filter down the list of questions. See available options above.
     * @return array|null list of Question objects
     */
    public function getRecentlyAskedQuestions(array $filters = array()) {
        // Set filter defaults
        $filters['maxQuestions'] = $filters['maxQuestions'] ?: 10;
        $filters['questionsFilter'] = $filters['questionsFilter'] ?: 'with';
        $filters['answerType'] = isset($filters['answerType']) ? $filters['answerType'] : array('author', 'moderator');
        if(count($filters['answerType']) === 0)
            $filters['questionsFilter'] = 'both';

        $query = ConnectTabular::query($this->buildRecentlyAskedRoql($filters), false);
        $rawQuestions = $query->getCollection();

        if($filters['questionsFilter'] === 'with' || $filters['questionsFilter'] === 'both') {
            $rawQuestions = $this->trimDeletedAnswers($rawQuestions, 'BestCommunityQuestionAnswerCommentID');
            $rawQuestions = $this->trimDeletedAnswers($rawQuestions, 'BestCommunityQuestionAnswerParentCommentID');
        }

        $questions = $questionIDs = array();
        foreach ($rawQuestions as $result) {
            if (!in_array($result->ID, $questionIDs)) {
                if (count($questionIDs) === intval($filters['maxQuestions'])) {
                    break;
                }
                $questionIDs[] = $result->ID;
            }

            $questions[] = $result;
        }

        return $this->getResponseObject($this->addFlagsAndRatingsToRecentlyAskedQuestions($questions, $questionIDs), 'is_array', null, null);
    }

    /**
     * Fetches all the flag type IDs and their lookup names
     * @return Array Array of Flag IDs and Lookup Names
     */
    public function getFlagTypes () {
        return parent::getFlattenFlagTypes(Connect\CommunityQuestionFlg::getMetadata());
    }

    /**
     * Updates moderator action on the question
     * @param int $questionID ID of the question to update
     * @param array $data Action data to update the question with
     * @return Connect\CommunityQuestion CommunityQuestion object on success else error
     */
    public function updateModeratorAction($questionID, array $data) {
        //check permission for move on product/category
        $checkMovePermission = function ($question, $type) use ($data) {
            $permissionByProducts = Config::getConfig(SSS_PERMISSION_BY_PRODUCTS);
            if ($permissionByProducts && $type === 'Category') return true;
            if (!$permissionByProducts && $type === 'Product') return true;
            return $question->SocialPermissions->canMove($data['CommunityQuestion.'.$type]->value, $type);
        };
        if (isset($data['CommunityQuestion.Product']) && $data['CommunityQuestion.Product'] && $data['CommunityQuestion.Product']->value && (!($question = $this->get($questionID)->result) || !$checkMovePermission($question, "Product"))) {
            return $this->getResponseObject(null, null, Config::getMessage(PERMISSI_CREATEUPDATE_QUEST_DSTNT_PRDCT_LBL));
        }
        if (isset($data['CommunityQuestion.Category']) && $data['CommunityQuestion.Category'] && $data['CommunityQuestion.Category']->value && (!($question = $this->get($questionID)->result) || !$checkMovePermission($question, "Category"))) {
            return $this->getResponseObject(null, null, Config::getMessage(PERMISSI_CREATEUPDATE_QUEST_DSTNT_CTGRY_LBL));
        }

        $socialObject = $this->update($questionID, $data);
        $objectMetadata = $this->getSocialObjectMetadataMapping('CommunityQuestion')->result;
        if($socialObject->result) {
            $response = isset($data['CommunityQuestion.StatusWithType.Status.ID']) && isset($data['CommunityQuestion.StatusWithType.Status.ID']->value) ? $this->resetFlagsIfRequiredForThisAction($data['CommunityQuestion.StatusWithType.Status.ID']->value, $questionID, 'CommunityQuestion') : $this->resetFlagsIfRequiredForThisAction(null, $questionID, 'CommunityQuestion');
            if ($response) {
                //since reset flag is successful for this question, so commit both restore and reset flag operations
                Connect\ConnectAPI::commit();
            }
            else if ($response === false) {
                //reset flag operation has failed
                Connect\ConnectAPI::rollback();
                return $this->getResponseObject(null, 'is_null', sprintf($objectMetadata['validation_errors']['general_error'], $questionID));
            }
        }
        return $socialObject;
    }

    /**
     * Sends an email regarding a social discussion to the specified email.
     * @param string $sendTo Email address to send to
     * @param int $questionID Question ID
     * @return bool Whether the email was successfully sent
     */
    public function emailToFriend($sendTo, $questionID) {
        $socialUserResponseObject = $this->getSocialUser();
        if($socialUserResponseObject->errors)
            return $socialUserResponseObject;
        $socialUser = $socialUserResponseObject->result;

        if (!Framework::isValidID($questionID)) {
            return $this->getResponseObject(false, 'is_bool', Config::getMessage(INVALID_QUESTION_ID_LBL));
        }

        if (!$question = $this->get($questionID)->result) {
            if ($questionErrors = $this->get($questionID)->errors) {
                return $this->getResponseObject(false, 'is_bool', $questionErrors[0]->externalMessage);
            }
            return $this->getResponseObject(false, 'is_bool', Config::getMessage(CANNOT_FIND_MAY_DELETED_ANOTHER_USER_MSG));
        }

        if (!$question->SocialPermissions->isActive()) {
            return $this->getResponseObject(false, 'is_bool', Config::getMessage(QUESTION_IS_NOT_ACTIVE_LBL));
        }

        $sendTo = trim($sendTo);
        if (!Text::isValidEmailAddress($sendTo)) {
            return $this->getResponseObject(false, 'is_bool', Config::getMessage(RECIPIENT_EMAIL_ADDRESS_INCORRECT_LBL));
        }

        $sendOptions = new Connect\SendOptions();
        $sendOptions->Subject = $question->Subject;
        $sendOptions->To->EmailAddresses[] = $sendTo;
        $sendOptions->From->EmailAddresses[] = $this->CI->session->getProfileData('email');
        $sendOptions->DisplayName = $socialUser->DisplayName;
        $question->send($sendOptions);

        ActionCapture::record('socialquestion', 'email', $questionID);

        if ($sendOptions->Sent !== 0) {
            return $this->getResponseObject(true, 'is_bool');
        }
        return $this->getResponseObject(false, 'is_bool', Config::getMessage(SORRY_WERENT_ABLE_SEND_EMAIL_PLS_MSG));
    }

    /**
     * Updates questions LastActivityTime field to the current time
     * @param CommunityQuestion|int $question Question object or ID
     * @param int $time Timestamp to use for LastActivityTime
     * @return CommunityQuestion|null If update operation is successful, the question object is returned. Otherwise null is returned.
     */
    public function touch($question, $time = null) {
        $socialUserResponseObject = $this->getSocialUser();
        if($socialUserResponseObject->errors)
            return $socialUserResponseObject;

        if (Framework::isValidID($question)) {
            $question = $this->get($question)->result;
        }

        $time = $time ?: time();

        if ($question && $question instanceof Connect\CommunityQuestion) {
            try {
                $question->LastActivityTime = $time;
                $question->save();
                return $this->getResponseObject($question);
            }
            catch (Connect\ConnectAPIErrorBase $e) {
                return $this->getResponseObject(null, null, $e->getMessage());
            }
        }
    }

    /**
     * Returns an array containing active CommunityQuestion's ID and Subject
     * @param int|array $socialQuestion A single int of a CommunityQuestion id or an array
     *     containing multiple CommunityQuestion IDs
     * @return array|null List containing answer's answerID and Subject
     */
    public function getQuestionSubject($socialQuestion) {
        if (Framework::isValidID($socialQuestion)) {
            $whereClause = "= $socialQuestion";
        }
        else if (is_array($socialQuestion) && count($socialQuestion)) {
            $socialQuestionList = implode(', ', $socialQuestion);
            if (!preg_match('/\s*\d+(\s*,\s*\d+)*\s*/', $socialQuestionList))
                return null;
            $whereClause = "IN ($socialQuestionList)";
        }
        else {
            return $this->getResponseObject(null, null, 'Not a valid type for the $socialQuestion parameter.');
        }

        try {
            $query = "SELECT A.ID, A.Subject FROM CommunityQuestion A WHERE A.ID $whereClause" . sprintf(" AND A.StatusWithType.StatusType.ID = %d", STATUS_TYPE_SSS_QUESTION_ACTIVE);
            $queryResult = Connect\ROQL::query($query)->next();
        }
        catch (Connect\ConnectAPIErrorBase $e) {
            return $this->getResponseObject(null, null, $e->getMessage());
        }

        $results = array();
        while ($row = $queryResult->next()) {
            $results[$row['ID']] = $row;
        }
        return $this->getResponseObject($results, 'is_array');
    }

    /**
     * Retrieves the question count based on the product/category
     * @param string $filterType Filtertype can be product or category.
     * @param array $prodCatList List of products/categories for which comment count should be determined.
     * @param array $showMetadata Determines which metadata to show for each product/category. Valid values are 'question_count', 'comment_count', and 'last_activity'.
     * @param int $count The number of products/categories to be fetched.
     * @param array $sortOrder The order for sorting the list.
     * @return array|null If valid arguments are passed, list of array containing comment count is returned. Otherwise null is returned.
     */
    public function getQuestionCountByProductCategory($filterType, array $prodCatList, array $showMetadata, $count, $sortOrder = null) {
        $result = $questionCount = $lastActivity = array();
        if(!empty($prodCatList)) {
            try {
                $selectQuery = !in_array('question_count', $showMetadata) ? '' : ', Count(*)';
                $selectQuery = !in_array('last_activity', $showMetadata) ? $selectQuery : $selectQuery . ', MAX(LastActivityTime)';

                switch($sortOrder) {
                    case "question_count":
                        $sortOrder = ' Count(*)';
                        break;
                    case "last_activity":
                        $sortOrder = ' MAX(LastActivityTime)';
                        break;
                    default:
                        $sortOrder = null;
                }

                $roql = sprintf("SELECT {$filterType}" . $selectQuery . " FROM CommunityQuestion WHERE {$filterType} IN (" . implode(',', $prodCatList) . ") AND Interface.ID = curInterface() AND StatusWithType.StatusType = %d GROUP BY {$filterType} " . ($sortOrder ? ("ORDER BY" . $sortOrder . " DESC ") : "") . ($count ? ("LIMIT " . $count) : ""), STATUS_TYPE_SSS_QUESTION_ACTIVE);
                $firstLevelObjects = Connect\ROQL::query($roql)->next();
                while($row = $firstLevelObjects->next()){
                    $questionCount[$row[$filterType]] = $row['Count(*)'] ?: null;
                    $lastActivity[$row[$filterType]] = $row['MAX(LastActivityTime)'] ?: null;
                }
                $result = array('questionCount' => $questionCount, 'lastActivity' => $lastActivity);
            }
            catch(Connect\ConnectAPIErrorBase $e) {
                return $this->getResponseObject(null, null, $e->getMessage());
            }
        }
        return $this->getResponseObject($result, 'is_array');
    }

    /**
     * Retrieves the comment count and lastActivity based on the question ID
     * @param string $filterType Filtertype can be product or category.
     * @param string $prodCatValue ID of the product or category.
     * @param array $questionList List of question IDs for which comment count should be determined.
     * @param array $showMetadata Determines which metadata to show for each product/category. Valid values are 'comment_count', and 'last_activity'.
     * @param int $count The number of questions to be fetched.
     * @param array $sortOrder The order for sorting the list. Valid values are 'comment_count', 'question_id' and 'last_activity'.
     * @return array|null If valid arguments are passed, list of array containing last activity and comment count is returned. Otherwise null is returned.
     */
    public function getCommentCountByQuestion($filterType, $prodCatValue, array $questionList, array $showMetadata, $count, $sortOrder = null) {
        $result = array();
        try {
            $selectQuery = !in_array('comment_count', $showMetadata) ? '' : ', Count(CommunityComments.ID)';
            $selectQuery = !in_array('last_activity', $showMetadata) ? $selectQuery : $selectQuery . ', LastActivityTime';

            switch($sortOrder) {
                case "comment_count":
                    $sortColumn = ' Count(CommunityComments.ID)';
                    break;
                case "last_activity":
                    $sortColumn = ' LastActivityTime';
                    break;
                default:
                    $sortColumn = null;
            }

            $sortColumn = !empty($questionList) ? null : $sortColumn;

            $roql = sprintf("SELECT CommunityQuestion.ID, CommunityQuestion.Subject" . $selectQuery . " FROM CommunityQuestion WHERE {$filterType} = " . $prodCatValue . (!empty($questionList) ? " AND ID IN (" . implode(',', $questionList) . ")" : null) . " AND (CommunityComments.StatusWithType.StatusType IS NULL OR CommunityComments.StatusWithType.StatusType = %d) AND Interface.ID = curInterface() AND CommunityQuestion.StatusWithType.StatusType = %d  GROUP BY CommunityQuestion.ID" . ($sortColumn ? (" ORDER BY" . $sortColumn . " DESC") : "") . " LIMIT " . $count, STATUS_TYPE_SSS_COMMENT_ACTIVE, STATUS_TYPE_SSS_QUESTION_ACTIVE);
            $firstLevelObjects = Connect\ROQL::query($roql)->next();
            while($row = $firstLevelObjects->next()){
                $result[$row['ID']] = array('id' => $row['ID'], 'subject' => $row['Subject'], 'last_activity' => $row['LastActivityTime'], 'comment_count' => $row['Count(CommunityComments.ID)']);
            }
        }
        catch(Connect\ConnectAPIErrorBase $e) {
            return $this->getResponseObject(null, null, $e->getMessage());
        }
        return $this->getResponseObject($result, 'is_array');
    }

    /**
     * Get previous, next, first and last social discussion
     * @param Int $questionID ID of the Current Question
     * @param Int $prodcatID ID of the Product
     * @param string $type Determines previous, next, first and last social discussion in the mentioned type. Type can be product or category.
     * @param array $args List of arguements to get any/all of previous, next, first and last social discussion. List includes
     * - prevQuestion Previous social discussion to $question
     * - nextQuestion Next social discussion to $question
     * - oldestNewestQuestion First and last social discussion of same product/category as $question
     * @return array|null If valid arguments are passed, list of array containing social discussion ids are returned. Otherwise null is returned.
     */
    public function getPrevNextQuestionID ($questionID, $prodcatID, $type = 'product', $args = array()) {
        $returnData = array();

        if (!$questionID || !Framework::isValidID($questionID)) {
            return $this->getResponseObject(null, null, Config::getMessage(INVALID_QUESTION_LBL));
        }

        if (count($args) === 0) {
            // nothing to do
            return $this->getResponseObject(null, null);
        }

        if (!$prodcatID || !Framework::isValidID($prodcatID)) {
            // question is not associated with any product or category
            return $this->getResponseObject(null, null);
        }

        $roql = "FROM CommunityQuestion q WHERE q.Interface = curInterface()";

        if ($type === 'product' && $prodcatID) {
            $roql .= sprintf(" AND q.Product = %d", $prodcatID);
        }

        if ($type === 'category' && $prodcatID) {
            $roql .= sprintf(" AND q.Category = %d", $prodcatID);
        }

        // if user is not logged-in or is non moderator, then show active questions only
        $isLoggedIn = Framework::isLoggedIn();
        if(!$isLoggedIn || ($isLoggedIn && !Framework::isSocialModerator())) {
            $roql .= sprintf(" AND q.StatusWithType.StatusType.ID = %d", STATUS_TYPE_SSS_QUESTION_ACTIVE);
        }
        else {
            // moderator should see all questions execept deleted ones.
            $roql .= sprintf(" AND q.StatusWithType.StatusType.ID != %d", STATUS_TYPE_SSS_QUESTION_DELETED);
        }

        if ($args['oldestNewestQuestion'] === true) {
            // get the oldest and newest question
            $oldestNewestRoql = "SELECT min(q.ID) as oldest, max(q.ID) as newest " . $roql;

            $results = Connect\ROQL::query($oldestNewestRoql)->next();
            if($results && ($result = $results->next())) {
                $returnData['oldestQuestion'] = $result['oldest'];
                $returnData['newestQuestion'] = $result['newest'];
            }
        }

        // Using != due to type mismatch
        if (isset($args['prevQuestion']) && $args['prevQuestion'] === true && isset($returnData['oldestQuestion']) && $returnData['oldestQuestion'] != $questionID) {
            // get previous question
            $prevRoql = "SELECT max(q.ID) as prev " . $roql . sprintf(" AND q.ID < %d", $questionID);
            $results = Connect\ROQL::query($prevRoql)->next();
            if($results && ($result = $results->next())) {
                $returnData['prevQuestion'] = $result['prev'];
            }
        }

        // Using != due to type mismatch
        if (isset($args['nextQuestion']) && $args['nextQuestion'] === true && isset($returnData['newestQuestion']) && $returnData['newestQuestion'] != $questionID) {
            // get next question
            $nextRoql = "SELECT min(q.ID) as next " . $roql . sprintf(" AND q.ID > %d", $questionID);
            $results = Connect\ROQL::query($nextRoql)->next();
            if($results && ($result = $results->next())) {
                $returnData['nextQuestion'] = $result['next'];
            }
        }

        return $this->getResponseObject($returnData);
    }

    /**
     * Gets a comment count for the given question.
     * @param  object  $question Question object instance
     * @param  boolean $all      Whether to count all comments (true)
     *                           or just top-level comments (false)
     * @return int            Number of comments
     */
    protected function getCommentCount($question, $all = true) {
        if (!$question || !($question instanceof Connect\CommunityQuestion) || !$question->ID) return 0;

        $where = array($this->getCommentStatusTypeFilters($question), "c.CommunityQuestion = {$question->ID}");
        if (!$all) {
            $where []= "c.Parent IS NULL";
        }

        $select = $this->buildSelectROQL('count() AS count', 'CommunityComment c', $where);

        $query = ConnectTabular::query($select);
        $result = $query->getFirst();

        return (int) $result->count;
    }

    /**
     * Creates the appropriate ROQL query clause for a product or category filter
     * @param String $type Type to use with the filter
     * @param Int $id ID of object to filter on
     * @param Boolean $includeChildren Whether to include children products or categories
     * @param String $alias Alias name used in the query
     * @return Null|String Returns a string to be used in a ROQL query or null if
     *      there's a problem with the params.
     */
    protected function addProdCatFilterROQL($type, $id, $includeChildren, $alias) {
        $prodcat = $this->CI->model('Prodcat')->get($id)->result;
        if (!$prodcat || !$this->CI->model('Prodcat')->isEnduserVisible($prodcat)) return;

        $toInclude = array($prodcat->ID);

        if ($includeChildren) {
            $childrenQuery = Connect\ROQL::queryObject("SELECT Service{$type} FROM Service{$type} pc WHERE (
                pc.Parent.ID = {$prodcat->ID} OR
                pc.Parent.level1.ID = {$prodcat->ID} OR
                pc.Parent.level2.ID = {$prodcat->ID} OR
                pc.Parent.level3.ID = {$prodcat->ID} OR
                pc.Parent.level4.ID = {$prodcat->ID}) AND
                pc.EndUserVisibleInterfaces.ID = curInterface()")->next();

            while ($child = $childrenQuery->next()){
                $toInclude[] = $child->ID;
            }
        }

        return sprintf(" AND {$alias}.{$type}.ID in (%s)", implode(',', $toInclude));
    }

    /**
     * Utility method to set the value on the Question object. Handles more complex types such as comment entries
     * and file attachments.
     * @param Connect\RNObject $question Current question object that is being created/updated
     * @param string $fieldName Name of the field we're setting
     * @param mixed $fieldValue Value of the field.
     * @param string $fieldType Common object model field type
     * @return null|string Returns null upon success or an error message from Connect::setFieldValue upon error.
     */
    protected function setFieldValue(Connect\RNObject $question, $fieldName, $fieldValue, $fieldType = null){
        if($fieldType === 'FileAttachmentCommunity'){
            $this->createAttachmentEntry($question, $fieldValue);
        }
        else{
            if (strtolower($fieldName) === 'communityquestion.body') {
                // All Questions submitted from CP will be html.
                $contentType = new Connect\NamedIDOptList();
                $contentType->LookupName = 'text/html';
                parent::setFieldValue($question, 'CommunityQuestion.BodyContentType', $contentType);
            }
            return parent::setFieldValue($question, $fieldName, $fieldValue);
        }
    }

    /**
     * Determines whether a comment should be included within a comment list
     * that's returned to callers.
     * @param  object $comment Comment object instance
     * @return bool Whether the comment should be included
     */
    protected function shouldIncludeComment($comment) {
        if (!$comment) return false;

        if ($comment->SocialPermissions->isSuspended()) {
            return $comment->SocialPermissions->canUpdateStatus();
        }
        if ($comment->SocialPermissions->isPending()) {
            return $comment->SocialPermissions->isAuthor() || $comment->SocialPermissions->canUpdateStatus();
        }
        return true;
    }

    /**
     * Given top-level comments, retrieves and attaches sub-level comments.
     * @param  Connect\RNObject $question Current question
     * @param  array  $topLevelComments   Array of Comment objects
     * @param  array  $topLevelCommentIDs Array consisting of comment ids
     * @return array                      Array of Comment objects
     */
    protected function attachChildComments (Connect\RNObject $question, array $topLevelComments, array $topLevelCommentIDs) {
        $flattened = implode(',', $topLevelCommentIDs);

        // This SELECT is split in two for performance reasons (avoids ROQL left-joining the comment table to itself)
        // Fetch Results 1/2
        $roqlSelectFrom = $this->getOrderedCommentSelectROQL(
            sprintf("c.CommunityQuestion = %d", $question->ID) . sprintf(' AND c.Parent IN (%s) ', $flattened),
            '',
            $question);
        $query = ConnectTabular::query($roqlSelectFrom, false);
        $results = $query->getCollection(array('class' => 'Permission/SocialCommentPermissions', 'property' => 'SocialPermissions'));

        // Fetch Results 2/2
        $roqlSelectFrom = $this->getOrderedCommentSelectROQL(
            sprintf("c.CommunityQuestion = %d", $question->ID) . sprintf(' AND c.Parent.level1 IN (%s) ', $flattened),
            '',
            $question);
        $query = ConnectTabular::query($roqlSelectFrom, false);
        $results = array_merge($results, $query->getCollection(array('class' => 'Permission/SocialCommentPermissions', 'property' => 'SocialPermissions')));
        $sortedComments = $this->sortComments($results);

        // keep these in a hash table so we can easily reconstruct the final array
        $nestedComments = $nestedCommentIDs = array();
        foreach ($sortedComments as $result) {
            // comments nested several levels deep need to be grouped under their oldest anscestor
            $topLevelRepliedTo = $result->Parent->ID;
            if (array_search($topLevelRepliedTo, $topLevelCommentIDs, true) === false) {
                $topLevelRepliedTo = $result->Parent->level1;
            }

            if (!isset($nestedComments[$topLevelRepliedTo]) || !$nestedComments[$topLevelRepliedTo]) {
                $nestedComments[$topLevelRepliedTo] = array();
            }

            $nestedComments[$topLevelRepliedTo][] = $result;
            $nestedCommentIDs[] = $result->ID;
        }

        // finally, construct the array of comments to return in correct display order
        $comments = array();
        foreach ($topLevelComments as $topLevelComment) {
            $comments []= $topLevelComment;

            if (array_key_exists($topLevelComment->ID, $nestedComments)) {
                $hierarchy = array();
                $children = $nestedComments[$topLevelComment->ID];

                foreach ($children as $nestedComment) {
                    if ($this->shouldIncludeComment($nestedComment)) {
                        $hierarchy []= $nestedComment;
                    }
                }

                if ($hierarchy) {
                    $comments = array_merge($comments, $hierarchy);
                }
            }
        }
        return $this->addCommentFlagsAndRatings($comments, $topLevelCommentIDs, $nestedCommentIDs);
    }

    /**
     * Sorts the comments into display order.
     * @param  array $comments List of comments
     * @return array           Sorted comments
     */
    protected function sortComments(array $comments) {
        $sortedComments = array();
        foreach ($comments as $comment) {
            $sortedComments[$this->getCommentSortKey($comment)] = $comment;
        }
        ksort($sortedComments, SORT_STRING);

        return $sortedComments;
    }

    /**
     * Sets the Products / Categories field on the Question.
     * @param Connect\RNObject $question   Question being created / modified
     * @param String           $fieldName  Field name; either 'Question.Products' or 'Question.Categories'
     * @param Array|Number           $fieldValue Product / Category ID or list of IDs
     */
    protected function setProductCategoryValue(Connect\RNObject $question, $fieldName, $fieldValue) {
        static $dataTypes;
        $dataTypes || ($dataTypes = array(
            'Products' => array(
                'item'       => CONNECT_NAMESPACE_PREFIX . '\ServiceProduct',
                'collection' => CONNECT_NAMESPACE_PREFIX . '\ServiceProductArray',
            ),
            'Categories' => array(
                'item'       => CONNECT_NAMESPACE_PREFIX . '\ServiceCategory',
                'collection' => CONNECT_NAMESPACE_PREFIX . '\ServiceCategoryArray',
            ),
        ));

        $fieldName = Text::getSubstringAfter($fieldName, 'Question.');

        $dataType = $dataTypes[$fieldName];
        $item = $dataType['item'];
        $collection = $dataType['collection'];
        $collection = new $collection();

        $values = is_array($fieldValue) ? $fieldValue : array($fieldValue);

        try {
            foreach ($values as $index => $id) {
                if ($id > 0) {
                    $collection[$index] = $item::fetch($id);
                }
            }
        }
        catch (Connect\ConnectAPIErrorBase $e) {
            $collection = $e->getMessage();
        }

        $question->{$fieldName} = $collection;
    }

    /**
     * Given a CommunityUser, returns type of best answer to assign.
     * @param Connect\CommunityQuestion $question Current Question
     * @param Connect\CommunityUser $user Current CommunityUser
     * @return Number Best answer define
     */
    protected function getBestAnswerTypeForUser(Connect\CommunityQuestion $question, Connect\CommunityUser $user) {
        return ($question->CreatedByCommunityUser->ID === $user->ID)
            ? SSS_BEST_ANSWER_AUTHOR
            : SSS_BEST_ANSWER_MODERATOR;
    }

    /**
     * Generate the sort key for a comment.  This will be a sort of concatenation of the comment's
     * ancestor's IDs along with the comment's own ID - it mirrors the structure of the lvl[1-6]_id
     * fields in the sss_comments table (which differs from the Level[1-4] fields returned by ROQL)
     *
     * The key is a long string with each integer at a set position so that they can be accurately
     * sorted; the integers become zero-padded strings in order to preserve the positions.
     *
     * Examples using a pad side of 2:
     *   For a comment with ID 6 and ancestors 1,2,3,4,5 the key will be
     *   010203040506
     *
     *   For a comment with ID 6 and ancestor 1 the key will be
     *   010600000000
     *
     *   For a comment with ID 6 and no ancestors the key will be
     *   060000000000
     *
     * @param object $comment Tabular data row containing a comment
     * @param integer $padSize The padded size for each key; should be enough to contain the largest integer which will be seen
     * @return String Array key suitable for sorting the comments into display order
     */
    protected function getCommentSortKey($comment, $padSize = 8) {
        $key = '';
        // @codingStandardsIgnoreStart - CodeSniffer does not like level1, level2, level3, and level4 'variables' (Zend.NamingConventions.ValidVariableName.ContainsNumbers)
        foreach (array($comment->Parent->level1, $comment->Parent->level2, $comment->Parent->level3, $comment->Parent->level4, null) as $value) {
        // @codingStandardsIgnoreEnd - CodeSniffer does not like level1, level2, level3, and level4 'variables' (Zend.NamingConventions.ValidVariableName.ContainsNumbers)
            // use each value until we encounter a null, then use the parent for the the next value and break
            if ($value){
                $key .= str_pad($value, $padSize, '0', STR_PAD_LEFT);
            }
            else if ($comment->Parent->ID) {
                $key .= str_pad($comment->Parent->ID, $padSize, '0', STR_PAD_LEFT);
                break;
            }
            else {
                break;
            }
        }

        // now append the actual comment ID and pad the whole thing to a consistent length
        $key .= str_pad($comment->ID, $padSize, '0', STR_PAD_LEFT);
        return str_pad($key, $padSize * 6, '0');
    }

    /**
     * Add flags and ratings to $comments for the logged-in social user.
     * @param array $comments A list of comment objects
     * @param array $topLevelCommentIDs A list of top level comment IDs
     * @param array $nestedCommentIDs A list of child comment IDs
     * @return array An array of comments with flags and comments merged in.
     */
    private function addCommentFlagsAndRatings(array $comments, array $topLevelCommentIDs, array $nestedCommentIDs) {
        if ($user = $this->CI->model('CommunityUser')->get()->result) {
            $ratings = $flags = array();
            // not done yet - we have the right array to return but we need to indicate if the current user
            // has flagged or rated each comment.
            $results = $this->getRatingsForComments(array_merge($topLevelCommentIDs, $nestedCommentIDs), $user->ID);

            // keep these in a hash table so we can look them up since the current user has probably not rated all comments
            foreach ($results as $result) {
                $ratings[$result->CommunityComment] = $result;
            }

            // do the same thing for flags
            $results = $this->getFlagsForComments(array_merge($topLevelCommentIDs, $nestedCommentIDs), $user->ID);
            foreach ($results as $result) {
                $flags[$result->CommunityComment] = $result;
            }

            // go through the comments and merge in the ratings and flags
            foreach ($comments as $index => $comment) {
                if (isset($ratings[$comment->ID]) && $ratings[$comment->ID]) {
                    $comments[$index] = ConnectTabular::mergeQueryResults($comments[$index], $ratings[$comment->ID]);
                }
                if (isset($flags[$comment->ID]) && $flags[$comment->ID]) {
                    $comments[$index] = ConnectTabular::mergeQueryResults($comments[$index], $flags[$comment->ID]);
                }

                $this->addCommentToCache($comments[$index]);
            }
        }

        return $comments;
    }

    /**
     * Takes an array of tabular question data, and trims any BestAnswers that
     * have a deleted comment or a deleted parent comment
     * @param array $rawQuestions Array of tabular data containing SocialQuestions with BestAnswers
     * @param String $commentField Type of field to check against.
     *                             Either 'BestSocialQuestionAnswerParentCommentID' or 'BestSocialQuestionAnswerCommentID'
     * @return array Trimmed tabular data array
     */
    private function trimDeletedAnswers(array $rawQuestions, $commentField) {
        $bestAnswerCommentIDs = array();

        foreach($rawQuestions as $key => $question) {
            if($question->{$commentField} &&
                !in_array($question->{$commentField}, $bestAnswerCommentIDs)) {
                $bestAnswerCommentIDs[] = $question->{$commentField};
            }
        }

        if(empty($bestAnswerCommentIDs)) {
            return $rawQuestions;
        }

        $roql = sprintf('SELECT c.ID FROM CommunityComment c WHERE c.ID IN (%s) AND ', implode(',', $bestAnswerCommentIDs)) . $this->getCommentStatusTypeFilters();
        $query = ConnectTabular::query($roql, false);
        $rawComments = $query->getCollection();

        $flattenedIDArray = array();
        foreach($rawComments as $comment) {
            $flattenedIDArray[] = $comment->ID;
        }

        foreach($rawQuestions as $key => $question) {
            if($question->{$commentField} && !in_array($question->{$commentField}, $flattenedIDArray)) {
                unset($rawQuestions[$key]);
            }
        }

        return array_values($rawQuestions);
    }

    /**
     * Builds a ROQL SELECT query to return a list of RecentlyAskedQuestions
     *
     * $filters
     *      ['maxQuestions'] int - Maximum number of questions
     *      ['answerType'] array - An array containing the type(s) of user(s) who selected the question's best
     *          answer(s). Valid values are 'author', 'moderator', and 'community'; array may also be empty,
     *          which returns no best answers (because there are no best answers without authors).
     *      ['product'] int - Product ID
     *      ['category'] int - Category ID
     *      ['includeChildren'] boolean - If a product or category filter is set, should questions
     *          assigned to children products or categories be included in the results.
     *      ['questionsFilter'] string - 'with', 'without', 'both'. Specifies
     *          if result should be filtered on having a 'best answer' or not. When 'answerType'
     *          filter is empty, this filter is overriden to 'both'
     *
     * @param array $filters Options hash to filter down the list of questions. See available options above.
     * @return string ROQL SELECT query for retrieving recently asked Questions
     */
    private function buildRecentlyAskedRoql(array $filters = array()) {
        $roql = array('q.Interface.ID = ' . $this->interfaceID . ' AND');

        // Filter on Questions with or without Best Answers
        if ($filters['questionsFilter'] === 'without') {
            $roql []= "q.BestCommunityQuestionAnswers.CommunityComment IS NULL AND";
        }
        else if ($filters['questionsFilter'] === 'with') {
            $roql []= "q.BestCommunityQuestionAnswers.CommunityComment IS NOT NULL AND";
        }

        // Filter on Active Questions only
        $roql []= "q.StatusWithType.StatusType = " . STATUS_TYPE_SSS_QUESTION_ACTIVE;

        // Filter on Products/Categories
        if (isset($filters['product']) && $filters['product']) {
            $roql []= $this->addProdCatFilterROQL('Product', $filters['product'], $filters['includeChildren'], 'q');
        }
        if (isset($filters['category']) && $filters['category']) {
            $roql []= $this->addProdCatFilterROQL('Category', $filters['category'], $filters['includeChildren'], 'q');
        }

        // Filter on specific Best Answer types
        $answerTypes = array();
        if (count($filters['answerType']) > 0 && $filters['questionsFilter'] !== 'without') {
            $answerTypeNameConstants = array(
                'author'    => SSS_BEST_ANSWER_AUTHOR,
                'moderator' => SSS_BEST_ANSWER_MODERATOR,
                'community' => SSS_BEST_ANSWER_COMMUNITY
            );
            if(is_array($filters['answerType'])) {
                foreach($filters['answerType'] as $answerType) {
                    if(array_key_exists($answerType, $answerTypeNameConstants)) {
                        $answerTypes []= $answerTypeNameConstants[$answerType];
                    }
                }
            }
            $roql []= ($filters['questionsFilter'] === 'both') ?
                sprintf("AND (q.BestCommunityQuestionAnswers.BestAnswerType in (%s) OR q.BestCommunityQuestionAnswers.BestAnswerType IS NULL)", implode(',', $answerTypes)) :
                sprintf("AND q.BestCommunityQuestionAnswers.BestAnswerType in (%s)", implode(',', $answerTypes));
        }

        // Order by most recently updated
        $roql []= "ORDER BY q.UpdatedTime DESC, q.ID DESC";

        // Second order by Best Answer Type
        $limit = $filters['maxQuestions'];
        if ($filters['answerType'] !== 'none' && $filters['questionsFilter'] !== 'without') {
            $roql []= ", q.BestCommunityQuestionAnswers.BestAnswerType ASC";
            $limit *= count($answerTypes);
        }

        return $this->getQuestionSelectROQL(implode("\n ", $roql), $limit, count($filters['answerType']) !== 0);
    }

    /**
     * Adds flags and ratings to an array of RecentlyAkedQuestion tabular data
     * @param array $questions Array of tabular data containing SocialQuestions
     * @param array $questionIDs Array of questionIDs to check flagging and ratings on
     * @return array Array containging updates SocialQuestions
     */
    private function addFlagsAndRatingsToRecentlyAskedQuestions(array $questions = array(), array $questionIDs = array()) {
        if (($user = $this->CI->model('CommunityUser')->get()->result) && !empty($questionIDs)) {
            $ratings = $flags = array();

            foreach ($this->getRatingsForQuestions($questionIDs, $user->ID) as $result) {
                $ratings[$result->CommunityQuestion] = $result;
            }

            foreach ($this->getFlagsForQuestions($questionIDs, $user->ID) as $result) {
                $flags[$result->CommunityQuestion] = $result;
            }

            foreach ($questions as $index => $question) {
                if (isset($ratings[$question->ID]) && $ratings[$question->ID]) {
                    $questions[$index] = ConnectTabular::mergeQueryResults($question, $ratings[$question->ID]);
                }
                if (isset($flags[$question->ID]) && $flags[$question->ID]) {
                    $questions[$index] = ConnectTabular::mergeQueryResults($question, $flags[$question->ID]);
                }

                $this->addQuestionToCache($questions[$index]);
            }
        }

        return $questions;
    }

    /**
     * Fetches the metadata of the SocialQuestions
     * @param array $questionIds Array of question IDs
     * @return array Array containing the list of question metadata
     */
    public function getQuestionsByIDs(array $questionIds = array()) {
        $questions = array();
        if(!empty($questionIds)) {
            try {
                $roql = 'q.ID IN (' . implode(",", $questionIds) . ') ORDER BY q.CreatedTime DESC, q.ID DESC';
                $query = ConnectTabular::query($this->getQuestionSelectROQL($roql), false);
                $questions = $query->getCollection();
            }
            catch (Connect\ConnectAPIErrorBase $e) {
                return $this->getResponseObject(null, null, $e->getMessage());
            }
        }
        return $this->getResponseObject($this->addFlagsAndRatingsToRecentlyAskedQuestions($questions, $questionIds), 'is_array', null, null);
    }
    /**
     * Get total number of questions
     * @return int count
     */
    public function getTotalQuestionCount() {
        $result = Connect\ROQL::query('SELECT count() AS count FROM CommunityQuestion')->next()->next();
        return (int) $result['count'];
    }
}
