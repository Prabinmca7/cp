<?php
namespace RightNow\Models;

use RightNow\Connect\v1_4 as Connect,
    RightNow\Utils\Connect as ConnectUtil,
    RightNow\Utils\Framework,
    RightNow\Utils\Config,
    RightNow\Utils\Date,
    RightNow\Libraries\ConnectTabular,
    RightNow\ActionCapture;

/**
 * A base class for Community-type models to increase code-reuse
 */
abstract class CommunityObjectBase extends PrimaryObjectBase {

    const USER_NOT_LOGGED_IN_ERROR_CODE = 'ERROR_USER_NOT_LOGGED_IN';
    const USER_HAS_NO_SOCIAL_USER_CODE = 'ERROR_USER_HAS_NO_SOCIAL_USER';
    const USER_HAS_BLANK_SOCIAL_USER_CODE = 'ERROR_USER_HAS_BLANK_SOCIAL_USER';

    protected $commentFields = <<<COMMENT
        c.ID,
        c.LookupName,
        c.CreatedTime,
        c.UpdatedTime,
        c.ParentCreatedByCommunityUser.ID AS 'CreatedByCommunityUser.ID',
        c.ParentCreatedByCommunityUser.DisplayName AS 'CreatedByCommunityUser.DisplayName',
        c.ParentCreatedByCommunityUser.AvatarURL AS 'CreatedByCommunityUser.AvatarURL',
        c.ParentCreatedByCommunityUser.StatusWithType.StatusType AS 'CreatedByCommunityUser.StatusWithType.StatusType.ID',
        c.Body,
        c.BodyContentType.ID AS 'BodyContentType.ID',
        c.BodyContentType.LookupName AS 'BodyContentType.LookupName',
        c.Parent AS 'Parent.ID',
        c.CommunityQuestion AS 'CommunityQuestion.ID',
        c.StatusWithType.Status AS 'StatusWithType.Status.ID',
        c.StatusWithType.StatusType AS 'StatusWithType.StatusType.ID',
        c.Type,
        c.Parent.level1,
        c.Parent.level2,
        c.Parent.level3,
        c.Parent.level4,
        c.ContentRatingSummaries.NegativeVoteCount,
        c.ContentRatingSummaries.PositiveVoteCount,
        c.ContentRatingSummaries.RatingTotal,
        c.ContentRatingSummaries.RatingWeightedCount,
        c.ContentRatingSummaries.RatingWeightedTotal
COMMENT;

    protected $questionFields = <<<QUESTION
        q.ID,
        q.LookupName,
        q.CreatedTime,
        q.UpdatedTime,
        q.ParentCreatedByCommunityUser.ID AS 'CreatedByCommunityUser.ID',
        q.ParentCreatedByCommunityUser.DisplayName AS 'CreatedByCommunityUser.DisplayName',
        q.ParentCreatedByCommunityUser.AvatarURL AS 'CreatedByCommunityUser.AvatarURL',
        q.ParentCreatedByCommunityUser.StatusWithType.StatusType AS 'CreatedByCommunityUser.StatusWithType.StatusType.ID',
        q.Body,
        q.Attributes.ContentLocked as ContentLocked,
        q.BodyContentType.ID AS 'BodyContentType.ID',
        q.BodyContentType.LookupName AS 'BodyContentType.LookupName',
        q.Category as 'Category.ID',
        q.LastActivityTime,
        q.Product as 'Product.ID',
        q.StatusWithType.Status as 'StatusWithType.Status.ID',
        q.StatusWithType.StatusType as 'StatusWithType.StatusType.ID',
        q.Subject,
        q.ContentRatingSummaries.NegativeVoteCount,
        q.ContentRatingSummaries.PositiveVoteCount,
        q.ContentRatingSummaries.RatingTotal,
        q.ContentRatingSummaries.RatingWeightedCount,
        q.ContentRatingSummaries.RatingWeightedTotal
QUESTION;

    protected $bestAnswerFields = <<<BESTANSWER
        q.BestCommunityQuestionAnswers.CommunityComment as BestCommunityQuestionAnswerCommentID,
        q.BestCommunityQuestionAnswers.BestAnswerType as BestCommunityQuestionAnswerType,
        q.BestCommunityQuestionAnswers.CreatedTime as BestCommunityQuestionCreatedTime,
        q.BestCommunityQuestionAnswers.CommunityUser as BestCommunityQuestionCreatedByCommunityUser,
        q.BestCommunityQuestionAnswers.BestCommunityQuestionAnswerList.ParentCommunityComment.Parent as BestCommunityQuestionAnswerParentCommentID

BESTANSWER;

    /**
     * Allows tests to review checked permissions and set the outcome of a permission check.
     */
    private $permissionCheckCallback;

    protected $interfaceID;

    /**
     * Constructor
     * @param string $objectName The name of the Connect Object the Connect instance is for.
     *          If not specified, the Connect object is assumed to be the class name of the inheriting model.
     */
    function __construct($objectName = '') {
        $this->interfaceID = \RightNow\Api::intf_id();
        parent::__construct($objectName);
    }

    /**
     * Checks to see if user is logged in and has a social user.
     * @return RightNow\Libraries\ResponseObject 'result' attribute of object
     *   is social user object if user is logged in or has social user, 'errors'
     *   attribute is populated otherwise.
     */
    public function getSocialUser() {
        if (!Framework::isLoggedIn()) {
            return $this->getResponseObject(null, null, array(
                array(
                    'externalMessage' => Config::getMessage(USER_IS_NOT_LOGGED_IN_LBL),
                    'errorCode' => self::USER_NOT_LOGGED_IN_ERROR_CODE,
                ),
            ));
        }

        $socialUser = $this->CI->model('CommunityUser')->get()->result;
        if(!$socialUser) {
            return $this->getResponseObject(null, null, array(
                array(
                    'externalMessage' => Config::getMessage(USER_DOES_NOT_HAVE_A_DISPLAY_NAME_LBL),
                    'errorCode' => self::USER_HAS_NO_SOCIAL_USER_CODE,
                ),
            ));
        }

        if(trim($socialUser->DisplayName) === '') {
            return $this->getResponseObject(null, null, array(
                array(
                    'externalMessage' => Config::getMessage(USER_DOES_NOT_HAVE_A_DISPLAY_NAME_LBL),
                    'errorCode' => self::USER_HAS_BLANK_SOCIAL_USER_CODE,
                ),
            ));
        }

        return $this->getResponseObject($socialUser);
    }

    /**
     * Retrieves a list of statuses for a current social object, optionally by status type
     * @param integer|null $statusTypeID The ID of the status type for which to return statuses.  If null or ommitted, returns all statuses
     * @param string|null $socialStatusObjectName Name of the social status object for which statuses is needed. If not passed, dynamically find the social status object name using the currently invoking class name
     * @return array Array of objects; each object has the properties StatusID, StatusLookupName, StatusTypeID
     */
    public function getSocialObjectStatuses($statusTypeID = null, $socialStatusObjectName = null) {
        if ($socialStatusObjectName === null) {
            $socialStatusObjectName = $this->objectName . 'Sts';
        }
        $roql = "SELECT os.ID as 'Status.ID', os.LookupName as 'Status.LookupName', os.StatusType as 'StatusType.ID' FROM $socialStatusObjectName os %s ORDER BY os.ID, os.DisplayOrder";
        $roql = sprintf($roql, $statusTypeID ? sprintf("WHERE os.StatusType = %d", $statusTypeID) : '');
        $query = ConnectTabular::query($roql);
        return $this->getResponseObject($query->getCollection(), 'is_array', $query->error);
    }

    /**
     * Retrieves a list of statuses for a current social object, optionally by status type
     * @param integer|null $statusTypeID The ID of the status type for which to return statuses.  If null or ommitted, returns all statuses.
     * @param string|null $socialStatusObjectName Name of the social status object for which statuses is needed. If not passed, dynamically find the social status object name using the currently invoking class name
     * @return array Array of objects; StatusTypeID as key and values are their statuses as array (StatusID as key)
     */
    public function getMappedSocialObjectStatuses($statusTypeID = null, $socialStatusObjectName = null) {
        $statusesObj = $this->getSocialObjectStatuses($statusTypeID, $socialStatusObjectName)->result;
        $statuses = array();
        if ($statusesObj) {
            foreach ($statusesObj as $statusObj) {
                $statuses[$statusObj->StatusType->ID] = isset($statuses[$statusObj->StatusType->ID]) && $statuses[$statusObj->StatusType->ID] ? $statuses[$statusObj->StatusType->ID] : array();
                $statuses[$statusObj->StatusType->ID][$statusObj->Status->ID] = array("StatusLookupName" => $statusObj->Status->LookupName);
            }
        }
        return $this->getResponseObject($statuses, 'is_array');
    }

    /**
     * Retrieves a list of statuses for a current social object for a given status type
     * @param integer $statusTypeID The ID of the status type for which to return statuses.
     * @param string|null $socialStatusObjectName Name of the social status object for which statuses is needed. If not passed, dynamically find the social status object name using the currently invoking class name
     * @return array Array of objects; list of status ids as values
     */
    public function getStatusesFromStatusType($statusTypeID, $socialStatusObjectName = null) {
        $statuses = array_map(function($x) { return $x->Status->ID; }, $this->getSocialObjectStatuses($statusTypeID, $socialStatusObjectName)->result);
        return $this->getResponseObject($statuses, 'is_array');
    }

    /**
     * Retrieves a status type ID of the current social object for a given status ID
     * @param integer $statusID The ID of the status for which to return status type.
     * @param string|null $socialStatusObjectName Name of the social status object for which status type is needed.
     * @return int|null Status type ID for a requested status
     */
    public function getStatusTypeFromStatus($statusID, $socialStatusObjectName = null) {
        $socialObjectAllStatuses = $this->getSocialObjectStatuses(null, $socialStatusObjectName)->result;
        foreach ($socialObjectAllStatuses as $index => $status) {
            if ((int)$status->ID === (int)$statusID) {
                return $status->StatusType->ID;
            }
        }
    }

    /**
     * Check if user is allowed to perform moderator action
     * @param bool $checkIsUserModerator Also check if currently logged in user has permission to moderate users
     * @return bool True if allowed, error message if user is not allowed to moderate
     */
    public function isModerateActionAllowed($checkIsUserModerator = false) {
        if (!Framework::isSocialUser()) {
            return $this->getResponseObject(null, null, Config::getMessage(YOU_MUST_BE_LOGGED_PERFORM_THIS_ACTION_MSG));
        }
        if (!Framework::isSocialModerator() || ($checkIsUserModerator && !Framework::isSocialUserModerator())) {
            return $this->getResponseObject(null, null, Config::getMessage(DO_NOT_HAVE_PERMISSION_PERFORM_ACTION_MSG));
        }
        return true;
    }

    /**
     * Check if social object ID is valid and not already deleted
     * @param integer $socialObjectID Social object ID to validate
     * @param string $socialObjectName Name of the social object to validate
     * @return bool Returns true if $socialObjectID is valid, readable, able to be modified by a moderator. Otherwise false.
     */
    public function isValidSocialObjectToModerate($socialObjectID, $socialObjectName) {
        $metaData = $this->getSocialObjectMetadataMapping($socialObjectName)->result;

        if (!Framework::isValidID($socialObjectID)) {
            return $this->getResponseObject(false, 'is_bool', $metaData['validation_errors']['object_invalid']);
        }

        // verify we are not trying to moderate our own user
        if ($socialObjectName === 'CommunityUser' &&
            ($currentUser = $this->CI->model('CommunityUser')->get()->result) &&
            $currentUser->ID === (int)$socialObjectID) {
            return $this->getResponseObject(false, 'is_bool', $metaData['validation_errors']['update_own_account']);
        }

        $socialObject = $this->CI->model($socialObjectName)->get($socialObjectID)->result;

        if (!$socialObject || (isset($metaData['allowed_actions']['delete']) && $socialObject->StatusWithType->StatusType->ID === $metaData['allowed_actions']['delete'])) {
            return $this->getResponseObject(false, 'is_bool', $metaData['validation_errors']['object_not_exist']);
        }
        if (!$socialObject->SocialPermissions->canUpdate()) {
            return $this->getResponseObject(false, 'is_bool', $metaData['validation_errors']['object_can_not_update']);
        }

        // if we are dealing with comment, that has a parent, verify the parent is readable
        if ($socialObjectName === 'CommunityComment' &&
            isset($socialObject->Parent->ID) && $socialObject->Parent->ID &&
            !$this->CI->model('CommunityComment')->get($socialObject->Parent->ID)->result) {
            return $this->getResponseObject(false, 'is_bool', $metaData['validation_errors']['object_not_exist']);
        }

        return $this->getResponseObject(true, 'is_bool');
    }

    /**
     * Destroy all flags for a given list of social content.
     * @param array $socialObjectIDs Array of social object IDs for which flags flags to be destroyed
     * @param string $socialObjectName Name of the social object for which flags to be destroyed
     * @return bool True if flags are destroyed successfully, error message on failure
     */
    public function resetSocialContentFlags(array $socialObjectIDs, $socialObjectName) {
        $errors = array();
        $metaData = $this->getSocialObjectMetadataMapping($socialObjectName)->result;
        if (count($socialObjectIDs) === 0) {
            return $this->getResponseObject(null, null, $metaData['validation_errors']['object_ids_empty']);
        }
        //Allowed to delete the flags?
        foreach($socialObjectIDs as $socialObjectID) {
            $socialObject = $this->get($socialObjectID)->result;
            if (!$socialObject->SocialPermissions->canDeleteFlag()) {
                if (!isset($errors[$socialObjectID]) || !$errors[$socialObjectID]) {
                    $errors[$socialObjectID] = array(
                        'externalMessage' => Config::getMessage(USER_DOES_HAVE_PERMISSION_RESET_FLAGS_LBL),
                        'extraDetails' => $socialObjectID
                    );
                }
            }
        }
        $objectIDsToProcess = array_diff($socialObjectIDs, array_keys($errors));
        if ($objectIDsToProcess) {
            try {
                $roql = sprintf("SELECT " . $metaData['connect_object_names']['content_flag_object_name'] . " FROM " . $metaData['connect_object_names']['content_flag_object_name'] . " f WHERE f." . $metaData['connect_object_names']['social_object_name'] . " IN(%s)", implode(",", $objectIDsToProcess));
                $results = Connect\ROQL::queryObject($roql)->next();
                while ($results && $result = $results->next()) {
                    $result->destroy();
                }
            }
            catch (Connect\ConnectAPIErrorBase $e) {
                return $this->getResponseObject(null, null, Config::getMessage(UNABLE_TO_RESET_FLAGS_PLEASE_TRY_AGAIN_MSG));
            }
        }
        if ($errors) {
            return $this->getResponseObject(null, null, array_values($errors));
        }
        return $this->getResponseObject(true, 'is_bool');
    }

    /**
     * Method to fetch metadata about social object
     * @param string|null $socialObjectName Name of the social object for which metadata is required
     * @param string|null $typeOfData Type of data required for a given social object
     * @return array Array of metadata for requested social object or for all social object when $socialObjectName is NULL
     */
    public function getSocialObjectMetadataMapping($socialObjectName = null, $typeOfData = null)
    {
        static $metadataMappings = array();

        if (!$metadataMappings) {
            $metadataMappings = array(
                "CommunityQuestion" => array(
                    "status_type_ids" => array(
                        "active" => STATUS_TYPE_SSS_QUESTION_ACTIVE,
                        "suspended" => STATUS_TYPE_SSS_QUESTION_SUSPENDED,
                        "deleted" => STATUS_TYPE_SSS_QUESTION_DELETED,
                        "pending" => STATUS_TYPE_SSS_QUESTION_PENDING
                    ),
                    "validation_errors" => array(
                        "object_ids_empty" => Config::getMessage(QUESTION_IDS_CANNOT_BE_EMPTY_MSG),
                        "object_invalid" => Config::getMessage(INVALID_QUESTION_MSG),
                        "object_not_exist" => Config::getMessage(DOES_EXIST_DOES_PERMISSION_QUESTION_MSG),
                        "object_can_not_update" => Config::getMessage(DOES_HAVE_PERMISSION_EDIT_QUESTION_MSG),
                        "object_already_deleted" => Config::getMessage(QUESTION_ALREADY_DELETED_MSG),
                        "general_error" => Config::getMessage(UNABLE_COMPLETE_REQUEST_TRY_AGAIN_MSG)
                    ),
                    "success_messages" => array(
                        STATUS_TYPE_SSS_QUESTION_SUSPENDED => array(
                                'single' => Config::getMessage(THE_QUESTION_HAS_BEEN_SUSPENDED_MSG),
                                'multiple' => Config::getMessage(THE_QUESTIONS_HAVE_BEEN_SUSPENDED_MSG)
                            ),
                        STATUS_TYPE_SSS_QUESTION_ACTIVE => array(
                                'single' => Config::getMessage(THE_QUESTION_HAS_BEEN_APPROVEDRESTORED_MSG),
                                'multiple' => Config::getMessage(QUESTIONS_HAVE_BEEN_APPROVEDRESTORED_MSG)
                            ),
                        STATUS_TYPE_SSS_QUESTION_DELETED => array(
                                'single' => Config::getMessage(THE_QUESTION_HAS_BEEN_DELETED_MSG),
                                'multiple' => Config::getMessage(THE_QUESTIONS_HAVE_BEEN_DELETED_MSG)
                            ),
                        "reset_flags" => array(
                                'multiple' => Config::getMessage(THE_FLAGS_HAVE_BEEN_RESET_MSG)
                            ),
                        "move" => array(
                                'single' => Config::getMessage(THE_QUESTION_HAS_BEEN_MOVED_MSG),
                                'multiple' => Config::getMessage(THE_QUESTIONS_HAVE_BEEN_MOVED_MSG)
                            ),
                        "lock" => array(
                                'single' => Config::getMessage(THE_QUESTION_HAS_BEEN_LOCKED_MSG)
                            ),
                        "unlock" => array(
                                'single' => Config::getMessage(THE_QUESTION_HAS_BEEN_UNLOCKED_MSG)
                            )
                    ),
                    "connect_object_names" => array(
                        "social_object_name" => "CommunityQuestion",
                        "content_flag_object_name" => "CommunityQuestionFlg"
                    ),
                    "clickstream_acs_info" => array(
                        STATUS_TYPE_SSS_QUESTION_ACTIVE     => array("subject" => "socialquestion", "verb" => "restore"),
                        STATUS_TYPE_SSS_QUESTION_SUSPENDED  => array("subject" => "socialquestion", "verb" => "suspend"),
                        STATUS_TYPE_SSS_QUESTION_DELETED    => array("subject" => "socialquestion", "verb" => "delete"),
                        "move"                              => array("subject" => "socialquestion", "verb" => "move"),
                        "reset_flags"                       => array("subject" => "socialquestion", "verb" => "resetFlags"),
                        "lock"                              => array("subject" => "socialquestion", "verb" => "lock"),
                        "unlock"                            => array("subject" => "socialquestion", "verb" => "unlock")
                    ),
                    "allowed_actions" => array() //initialize it to empty array and populate it below
                ),

                "CommunityComment" => array(
                    "status_type_ids" => array(
                        "active" => STATUS_TYPE_SSS_COMMENT_ACTIVE,
                        "suspended" => STATUS_TYPE_SSS_COMMENT_SUSPENDED,
                        "deleted" => STATUS_TYPE_SSS_COMMENT_DELETED,
                        "pending" => STATUS_TYPE_SSS_COMMENT_PENDING
                    ),
                    "validation_errors" => array(
                        "object_ids_empty" => Config::getMessage(COMMENT_IDS_CANNOT_BE_EMPTY_MSG),
                        "object_invalid" => Config::getMessage(INVALID_COMMENT_MSG),
                        "object_not_exist" => Config::getMessage(T_PARENT_DOES_EXIST_DOES_PERM_COMMENT_MSG),
                        "object_can_not_update" => Config::getMessage(USER_DOES_HAVE_PERMISSION_EDIT_COMMENT_MSG),
                        "object_already_deleted" => Config::getMessage(COMMENT_ALREADY_DELETED_MSG),
                        "general_error" => Config::getMessage(UNABLE_COMPLETE_REQUEST_TRY_AGAIN_MSG)
                    ),
                    "success_messages" => array(
                        STATUS_TYPE_SSS_COMMENT_SUSPENDED => array(
                                'single' => Config::getMessage(THE_COMMENT_HAS_BEEN_SUSPENDED_MSG),
                                'multiple' => Config::getMessage(THE_COMMENTS_HAVE_BEEN_SUSPENDED_MSG)
                            ),
                        STATUS_TYPE_SSS_COMMENT_ACTIVE => array(
                                'single' => Config::getMessage(THE_COMMENT_HAS_BEEN_APPROVEDRESTORED_MSG),
                                'multiple' => Config::getMessage(COMMENTS_HAVE_BEEN_APPROVEDRESTORED_MSG)
                            ),
                        STATUS_TYPE_SSS_COMMENT_DELETED => array(
                                'single' => Config::getMessage(THE_COMMENT_HAS_BEEN_DELETED_MSG),
                                'multiple' => Config::getMessage(THE_COMMENTS_HAVE_BEEN_DELETED_MSG)
                            ),
                        "reset_flags" => array(
                                'multiple' => Config::getMessage(THE_FLAGS_HAVE_BEEN_RESET_MSG)
                            )
                    ),
                    "connect_object_names" => array(
                        "social_object_name" => "CommunityComment",
                        "content_flag_object_name" => "CommunityCommentFlg"
                    ),
                     "clickstream_acs_info" => array(
                        STATUS_TYPE_SSS_COMMENT_ACTIVE      => array("subject" => "socialcomment", "verb" => "restore"),
                        STATUS_TYPE_SSS_COMMENT_SUSPENDED   => array("subject" => "socialcomment", "verb" => "suspend"),
                        STATUS_TYPE_SSS_COMMENT_DELETED     => array("subject" => "socialcomment", "verb" => "delete"),
                    ),
                    "allowed_actions" => array()
                ),

                "CommunityUser" => array(
                    "status_type_ids" => array(
                        "active" => STATUS_TYPE_SSS_USER_ACTIVE,
                        "suspended" => STATUS_TYPE_SSS_USER_SUSPENDED,
                        "archive" => STATUS_TYPE_SSS_USER_ARCHIVE,
                        "deleted" => STATUS_TYPE_SSS_USER_DELETED,
                        "pending" => STATUS_TYPE_SSS_USER_PENDING
                    ),
                    "validation_errors" => array(
                        "object_invalid" => Config::getMessage(INVALID_USER_MSG),
                        "object_not_exist" => Config::getMessage(AUTH_DOES_EXIST_DOES_PERMISSION_AUTHOR_MSG),
                        "object_can_not_update" => Config::getMessage(DOES_HAVE_PERMISSION_UPDATE_SOCIAL_USER_MSG),
                        "object_already_deleted" => Config::getMessage(USER_ALREADY_DELETED_MSG),
                        "update_own_account" => Config::getMessage(USERS_CANNOT_CHANGE_THEIR_OWN_ACCOUNTS_MSG)
                    ),
                    "success_messages" => array(
                        STATUS_TYPE_SSS_USER_SUSPENDED => array(
                                'single' => Config::getMessage(THE_USER_HAS_BEEN_SUSPENDED_MSG),
                                'multiple' => Config::getMessage(THE_USERS_HAVE_BEEN_SUSPENDED_MSG)
                            ),
                        STATUS_TYPE_SSS_USER_ACTIVE => array(
                                'single' => Config::getMessage(THE_USER_HAS_BEEN_APPROVEDRESTORED_MSG),
                                'multiple' => Config::getMessage(THE_USERS_HAVE_BEEN_APPROVEDRESTORED_MSG)
                            ),
                        STATUS_TYPE_SSS_USER_DELETED => array(
                                'single' => Config::getMessage(THE_USER_HAS_BEEN_DELETED_MSG),
                                'multiple' => Config::getMessage(THE_USERS_HAVE_BEEN_DELETED_MSG)
                            ),
                        STATUS_TYPE_SSS_USER_ARCHIVE => array(
                                'single' => Config::getMessage(THE_USER_HAS_BEEN_ARCHIVED_MSG),
                                'multiple' => Config::getMessage(THE_USERS_HAVE_BEEN_ARCHIVED_MSG)
                            )
                    ),
                    "connect_object_names" => array(
                        "social_object_name" => "CommunityUser"
                    ),
                    "clickstream_acs_info" => array(
                        STATUS_TYPE_SSS_USER_ACTIVE         => array("subject" => "socialuser", "verb" => "restore"),
                        STATUS_TYPE_SSS_USER_SUSPENDED      => array("subject" => "socialuser", "verb" => "suspend"),
                        STATUS_TYPE_SSS_USER_ARCHIVE        => array("subject" => "socialuser", "verb" => "archive"),
                        STATUS_TYPE_SSS_USER_DELETED        => array("subject" => "socialuser", "verb" => "delete"),
                    ),
                    "allowed_actions" => array()
                )
            );
            $this->populateAllowedAction($metadataMappings);
        }
        if ($socialObjectName && $metadataMappings[$socialObjectName]) {
            if ($typeOfData && isset($metadataMappings[$socialObjectName][$typeOfData])) {
                return $this->getResponseObject($metadataMappings[$socialObjectName][$typeOfData], 'is_array');
            }
            return $this->getResponseObject($metadataMappings[$socialObjectName], 'is_array');
        }
        return $this->getResponseObject($metadataMappings, 'is_array');
    }

    /**
     * Creates clickstream and ACS logs for the given moderation action.
     * @param string $objectName Name of the social object
     * @param string $action Moderator action performed
     * @param integer|null $objectID SocialObject ID over which action is performed
     */
    public function createModerationClickstreamAndAcsLogs($objectName, $action, $objectID = '') {
        $moderationClickstreamAcsInfo = $this->getSocialObjectMetadataMapping($objectName, 'clickstream_acs_info')->result;
        $action = $this->CI->model($objectName)->getStatusTypeFromStatus($action) ?: $action;
        if ($moderationClickstreamAcsInfo[$action]['subject'] && $moderationClickstreamAcsInfo[$action]['verb']) {
            $this->CI->model('Clickstream')->insertAction($this->CI->session->getSessionData('sessionID'), $this->CI->session->getProfileData('contactID'), CS_APP_EU, '/' . $moderationClickstreamAcsInfo[$action]['subject'] . '_' . $moderationClickstreamAcsInfo[$action]['verb'], $objectID, '', '');
            ActionCapture::record($moderationClickstreamAcsInfo[$action]['subject'], $moderationClickstreamAcsInfo[$action]['verb'], $objectID);
        }
    }

    /**
     * Retrieves status type counts of the given social object
     * @param string $socialObjectName Name of the social object for which count is needed.
     * @param string $interval Group by interval, either day or hour
     * @param integer $units Number of intervals. Pass negative number to get the counts for past date.
     * @return array Array of array; each array has the key StatusTypeID and Total as their value.
     */
    public function getSocialObjectCountsByStatusType($socialObjectName, $interval = null, $units = null) {
        $dateFilterRQL = '';

        if ($interval && $units) {
            if (!in_array($interval, array('day', 'hour'))) {
                return $this->getResponseObject(null, null, sprintf(Config::getMessage(INTERVAL_MUST_BE_EITHER_S_OR_S_LBL), 'day', 'hour'));
            }
            $units = (int) $units;
            // Find the start date
            $startDate = Date::add(Date::getCurrentDateTime(), $units, $interval, 1);
            $dateFilterRQL = " AND SO.UpdatedTime >= '" . $startDate . "' " . (($socialObjectName !== 'CommunityUser') ? " AND CreatedByCommunityUser IS NOT NULL " : "");
        }
        $interfaceFilterRQL = '';
        if ($socialObjectName !== 'CommunityUser') {
            $interfaceFilterRQL = ($socialObjectName === 'CommunityQuestion') ? " AND SO.Interface.ID = {$this->interfaceID}" : " AND SO.ParentCommunityQuestion.Interface.ID = {$this->interfaceID}";
        }
        $deletedParentFilterRQL = '';
        if($socialObjectName === 'CommunityComment') {
            $parentSocialQuestionMetaData = $this->getSocialObjectMetadataMapping('CommunityQuestion')->result;
            $deletedParentFilterRQL = sprintf(" AND SO.ParentCommunityQuestion.StatusWithType.StatusType.ID != %d ", $parentSocialQuestionMetaData['status_type_ids']['deleted']);
        }
        $metaData = $this->getSocialObjectMetadataMapping($socialObjectName)->result;
        $rql = "SELECT SO.StatusWithType.StatusType.ID, count(ID) as Total FROM {$metaData['connect_object_names']['social_object_name']} SO
                WHERE SO.StatusWithType.StatusType !=%d {$dateFilterRQL} {$interfaceFilterRQL} {$deletedParentFilterRQL} GROUP BY SO.StatusWithType.StatusType ORDER BY SO.StatusWithType.StatusType.LookupName";
        $rql = sprintf($rql, $metaData['status_type_ids']['deleted']);
        try {
            $query = Connect\ROQL::query($rql)->next();
        }
        catch(Connect\ConnectAPIErrorBase $e){
            return $this->getResponseObject(null, null, $e->getMessage());
        }
        $rows = array();
        while($row = $query->next()) {
            $rows[$row['ID']] = $row['Total'];
        }
        return $this->getResponseObject($rows, 'is_array', property_exists($query, 'error') ? $query->error : null);
    }

    /**
     * Fetch the social object counts grouped by day or month for a given interval
     * @param string $socialObjectName Name of the social object for which count is needed
     * @param string $interval Group by interval either day or month
     * @param integer $units Number of intervals. Pass negative to get the counts from past data.
     * @return array Each element has date as KEY and count as their VALUE.
     */
    public function getRecentSocialObjectCountsByDateTime($socialObjectName, $interval = 'day', $units = -7) {
        if (!in_array($interval, array('day', 'month'))) {
            return $this->getResponseObject(null, null, sprintf(Config::getMessage(INTERVAL_MUST_BE_EITHER_S_OR_S_LBL), 'day', 'month'));
        }

        //including today or current month
        $units = (int) $units + 1;
        $metaData = $this->getSocialObjectMetadataMapping($socialObjectName)->result;

        //Populate the date ranges and which is used to fill values to zero for all dates that do not have any records
        $date = array();
        $date['endDate'] = Date::trunc(Date::getCurrentDateTime(), $interval);
        $date['startDate'] = Date::add($date['endDate'], $units, $interval, 1);
        $dateRanges = Framework::createDateRangeArray(substr($date['startDate'], 0, 10), substr($date['endDate'], 0, 10), ('+1 '. $interval));

        $interfaceFilterRQL = '';
        if ($socialObjectName !== 'CommunityUser') {
            $interfaceFilterRQL = ($socialObjectName === 'CommunityQuestion') ? " AND SO.Interface.ID = {$this->interfaceID}" : " AND SO.ParentCommunityQuestion.Interface.ID = {$this->interfaceID}";
        }
        $rql = "SELECT count(SO.ID) AS count, date_trunc(SO.CreatedTime, '$interval') AS CreatedTime FROM {$metaData['connect_object_names']['social_object_name']} SO
                WHERE SO.CreatedTime > '" . $date['startDate'] . "' AND SO.StatusWithType.StatusType !=%d {$interfaceFilterRQL} "
                . "GROUP BY date_diff('" . $date['startDate'] . "', date_trunc(SO.CreatedTime, '$interval')) ORDER BY SO.CreatedTime";

        $rql = sprintf($rql, $metaData['status_type_ids']['deleted']);
        try {
            $query = Connect\ROQL::query($rql)->next();
        }
        catch(Connect\ConnectAPIErrorBase $e){
            return $this->getResponseObject(null, null, $e->getMessage());
        }

        $rows = array();
        while($row = $query->next()) {
            $rows[substr($row['CreatedTime'], 0, 10)] = $row['count'];
        }

        return $this->getResponseObject(array_merge($dateRanges, $rows), 'is_array', isset($query->error) ? $query->error : null);

    }

    /**
     * Fetches all the flag type IDs and their LookupNames
     * @param Connect\_metadata $flagObjectMetaData Metadata about social question or comment flag types.
     * @return Array Array of Flag IDs and Lookup Names
     */
    protected function getFlattenFlagTypes (Connect\_metadata $flagObjectMetaData) {
        return array_map(function ($namedValue) {
            return array(
                'ID'         => $namedValue->ID,
                'LookupName' => $namedValue->LookupName,
            );
        }, $flagObjectMetaData->Type->named_values);
    }

    /**
     * Returns ROQL for performing SELECT query to retrieving questions.
     * In a method to enforce scope.
     * @param string $where The WHERE portion of the query (excluding the 'WHERE')
     * @param string $limit The LIMIT portion of the query (excluding the 'LIMIT')
     * @param bool $includeBestAnswers Include best answers or not
     * @return string ROQL SELECT query for retrieving questions
     */
    protected function getQuestionSelectROQL($where, $limit = '', $includeBestAnswers = true) {
        $query = ($includeBestAnswers === true) ? $this->questionFields . ',' . PHP_EOL . $this->bestAnswerFields : $this->questionFields;
        return $this->buildSelectROQL($query, 'CommunityQuestion q', $where, $limit);
    }

    /**
     * Returns ROQL for performing SELECT query to retrieving comments.
     * In a method to enforce scope.
     * @param string $where The WHERE portion of the query (excluding the 'WHERE')
     * @param string $limit The LIMIT portion of the query (excluding the 'LIMIT')
     * @param Connect\CommunityQuestion|null $question Parent Question of the comment
     * @return string ROQL SELECT query for retrieving comments
     */
    protected function getCommentSelectROQL($where, $limit = '', Connect\CommunityQuestion $question = null) {
        $filters = array($this->getCommentStatusTypeFilters($question));
        if ($where) {
            $filters []= $where;
        }
        return $this->buildSelectROQL($this->commentFields, 'CommunityComment c', $filters, $limit);
    }

    /**
     * Builds a ROQL SELECT statement.
     * @param  string $what  The columns to select
     * @param  string $from  The object to select from
     * @param  string|array $where The where filter; if
     *                             an array is supplied,
     *                             its elements are joined
     *                             with ' AND '
     * @param  string $limit The limit
     * @return string        Constructed query
     */
    protected function buildSelectROQL($what, $from, $where, $limit = '') {
        if (is_array($where)) {
            $where = implode(" AND ", array_filter($where));
        }

        $query = "SELECT $what FROM $from WHERE $where ";

        if ($limit) {
            $query .= "LIMIT $limit";
        }

        return $query;
    }

    /**
     * Returns a StatusType filter suitable for a ROQL WHERE clause.
     * @param Connect\CommunityQuestion|null $question Parent Question of the comment
     * @return string Where condition
     */
    protected function getCommentStatusTypeFilters(Connect\CommunityQuestion $question = null) {
        if($question){
            $comment = $this->CI->model('CommunityComment')->getBlank()->result;
            $comment->CommunityQuestion = $question;
        }
        $socialUser = $this->CI->model('CommunityUser')->get()->result;
        $accessFilters = array(
            STATUS_TYPE_SSS_COMMENT_DELETED,
                isset($comment) && !($socialUser && $comment && ConnectUtil::hasPermission($comment, PERM_SOCIALQUESTIONCOMMENT_UPDATE_STATUS)) ? STATUS_TYPE_SSS_COMMENT_PENDING : null,
            );    
        $showPendingForAuthorWhere = $socialUser ? " OR (c.StatusWithType.StatusType = " . STATUS_TYPE_SSS_COMMENT_PENDING . " AND c.CreatedByCommunityUser.ID = " . $socialUser->ID . ")" : "";

        return "(c.StatusWithType.StatusType NOT IN (" . implode(',', array_filter($accessFilters)) . ")" . $showPendingForAuthorWhere. ")";
    }

    /**
     * Returns ROQL for performing SELECT query to retrieve comments
     * ordered by parent levels.
     * @param string $where The WHERE portion of the query (excluding the 'WHERE')
     * @param string $limit The LIMIT portion of the query (excluding the 'LIMIT')
     * @param Connect\CommunityQuestion|null $question Parent Question of the comment
     * @param string $sortOrder Order by using sort type of either ASC or DESC
     * @return string ROQL SELECT query for retrieving ordered comments
     */
    protected function getOrderedCommentSelectROQL($where, $limit = '', Connect\CommunityQuestion $question = null, $sortOrder = 'ASC') {
        return $this->getCommentSelectROQL($where, $limit, $question)
            . <<<ROQL

ORDER BY
    c.Parent.level1,
    c.Parent.level2,
    c.Parent.level3,
    c.Parent.level4,
    c.Parent,
    c.ID $sortOrder
ROQL;
    }

    /**
     * Returns rating data in tabular format for an array of SocialQuestions
     * @param array $questionIDs An array of CommunityQuestion IDs
     * @param int $userID User ID for which to return rating data
     * @return object ROQLResult Object
     */
    protected function getRatingsForQuestions(array $questionIDs, $userID) {
        return $this->getQuestionRatingQuery(implode(',', $questionIDs), $userID)->getCollection();
    }

    /**
     * Returns rating data in tabular format for a single CommunityQuestion
     * @param int $questionID CommunityQuestion ID
     * @param  int $userID User id
     * @return object Tabular data object
     */
    protected function getRatingsForQuestion($questionID, $userID) {
        return $this->getQuestionRatingQuery($questionID, $userID)->getFirst();
    }

    /**
     * Returns flagging data in tabular format for an array of SocialQuestions
     * @param array $questionIDs An array of CommunityQuestion IDs
     * @param int $userID User ID for which to return flagging data
     * @return array Representing result rows
     */
    protected function getFlagsForQuestions(array $questionIDs, $userID) {
        return $this->getQuestionFlagsQuery(implode(',', $questionIDs), $userID)->getCollection();
    }

    /**
     * Returns flagging data in tabular format for a single CommunityQuestion.
     * @param int $questionID CommunityQuestion ID
     * @param int $userID User ID for which to return flagging data
     * @return object Result row
     */
    protected function getFlagsForQuestion($questionID, $userID) {
        return $this->getQuestionFlagsQuery($questionID, $userID)->getFirst();
    }

    /**
     * Returns rating data in tabular format for an array of SocialComments.
     * @param array $questionCommentIDs An array of CommunityComment IDs
     * @param int $userID User ID for which to return rating data
     * @return array Representing result rows
     */
    protected function getRatingsForComments(array $questionCommentIDs, $userID) {
        return $this->getCommentRatingQuery(implode(',', $questionCommentIDs), $userID)->getCollection();
    }

    /**
     * Returns rating data in tabular format for an array of SocialComments.
     * @param int $questionCommentID CommunityComment ID
     * @param int $userID User ID for which to return rating data
     * @return object Result row
     */
    protected function getRatingsForComment($questionCommentID, $userID) {
        return $this->getCommentRatingQuery($questionCommentID, $userID)->getFirst();
    }

    /**
     * Returns flagging data in tabular format for an array of SocialComments
     * @param array $questionCommentIDs An array of CommunityComment IDs
     * @param int $userID User ID for which to return flagging data
     * @return array Representing result rows
     */
    protected function getFlagsForComments(array $questionCommentIDs, $userID) {
        return $this->getCommentFlagsQuery(implode(',', $questionCommentIDs), $userID)->getCollection();
    }

    /**
     * Returns flagging data in tabular format for a single CommunityComment.
     * @param int $questionCommentID CommunityComment ID
     * @param int $userID User ID for which to return flagging data
     * @return array Representing result rows
     */
    protected function getFlagsForComment($questionCommentID, $userID) {
        return $this->getCommentFlagsQuery($questionCommentID, $userID)->getFirst();
    }

    /**
     * Adds a tabular data question to the in-process cache
     * @param Array $question Row of tabular data that represents a question
     * @return Boolean Whether question was added to the cache successfully or not
     */
    protected function addQuestionToCache($question) {
        if (!$question->ID) return false;

        try {
            Framework::setCache("CommunityQuestion_{$question->ID}", $question);
        }
        catch (\Exception $e) {
            return false;
        }

        return true;
    }

    /**
     * Removes a tabular data question from the in-process cache
     * @param int $questionID ID of question to remove from cache
     */
    protected function removeQuestionFromCache($questionID) {
        Framework::removeCache("CommunityQuestion_$questionID");
    }

    /**
     * Retrieves a tabular data question from the in-process cache
     * @param int $questionID ID of question to retrieve
     * @return array|null The question data if present, null otherwise
     */
    protected function getQuestionFromCache($questionID) {
        return Framework::checkCache("CommunityQuestion_$questionID");
    }

    /**
     * Adds a tabular data comment to the in-process cache
     * @param Array $comment Row of tabular data that represents a comment
     * @return Boolean Whether comment was added to the cache successfully or not
     */
    protected function addCommentToCache($comment) {
        if (!$comment->ID) return false;

        try {
            Framework::setCache("CommunityComment_{$comment->ID}", $comment);
        }
        catch (\Exception $e) {
            return false;
        }

        return true;
    }

    /**
     * Removes a tabular data comment from the in-process cache
     * @param int $commentID ID of comment to remove from cache
     */
    protected function removeCommentFromCache($commentID) {
        Framework::removeCache("CommunityComment_$commentID");
    }

    /**
     * Retrieves a tabular data comment from the in-process cache
     * @param int $commentID ID of comment to retrieve
     * @return array|null The comment data if present, null otherwise
     */
    protected function getCommentFromCache($commentID) {
        return Framework::checkCache("CommunityComment_$commentID");
    }

    /**
     * Checks permissions for either the object's author or an alternate.  For example, an author may edit their own question but
     * so can anyone with the appropriate permission.
     *
     * @param Connect\RNObject $rnObject Object on which to check permissions; should have a CreatedByCommunityUser field
     * @param integer $authorPermissionID Permission which applies only to the author
     * @param integer $alternatePermissionID Permission which applies to anyone, not just the author
     * @return boolean True if the logged-in user is the author and has $authorPermissionID, or if the logged-in user has $alternatePermissionID
     */
    protected function checkAuthorAndObjectPermission(Connect\RNObject $rnObject, $authorPermissionID, $alternatePermissionID) {
        $socialUser = $this->CI->model('CommunityUser')->get()->result;
        return ($socialUser && $rnObject->CreatedByCommunityUser && $rnObject->CreatedByCommunityUser->ID === $socialUser->ID && ConnectUtil::hasPermission($rnObject, $authorPermissionID))
            || ($socialUser && ConnectUtil::hasPermission($rnObject, $alternatePermissionID));
    }

    /**
     * Removes flags of given social object if it is required to be done for given status update action.
     * @param integer $action Status update action
     * @param integer $socialObjectID ID of the social content
     * @param string $socialObjectType Name of social content object type
     * @return boolean|null False if reset flag failed, true if reset flag is successful, null if reset flag is not required
     */
    protected function resetFlagsIfRequiredForThisAction($action, $socialObjectID, $socialObjectType) {
        $objectMetadata = $this->getSocialObjectMetadataMapping($socialObjectType)->result;
        $statuses = $this->getMappedSocialObjectStatuses()->result;
        if (empty($statuses)) {
            return false;
        }
        if ((int) $action === key($statuses[$objectMetadata['allowed_actions']['restore']])) {
            $roql = sprintf("SELECT " . $objectMetadata['connect_object_names']['content_flag_object_name'] . " FROM " . $objectMetadata['connect_object_names']['content_flag_object_name'] . " f WHERE f." . $objectMetadata['connect_object_names']['social_object_name'] . "=%d", $socialObjectID);
            $results = Connect\ROQL::queryObject($roql)->next();
            //Call resetSocialContentFlags() only when flags exist
            $responseObject = $results && $results->next() ? $this->resetSocialContentFlags(array($socialObjectID), $socialObjectType) : true;
            return ($responseObject === true || $responseObject->result) ? true : false;
        }
    }

    /**
     * Populate all allowed moderation actions (also based on permission in case of user moderation actions) for all social objects
     * @param array &$metadataMappings Array of metadata of all social objects
     * @return null
     */
    protected function populateAllowedAction(array &$metadataMappings) {
        if (!Framework::isSocialUser()) {
            return;
        }
        // populate the allowed action and map to the right status_type id or string
        // 1. If 'action' has status_type key and allowed_objects keys are integer or valid social object name, then map the value of status_type to an action.
        // 2. If array values of allowed_objects is not a valid social object name (e.g move, reset_flags), then directly map the value to an action
        $allowedActionObjectsMap = array(
            "restore" => array(
                "status_type" => "active",
                "allowed_objects" => array("CommunityQuestion", "CommunityComment", "CommunityUser")),
            "suspend" => array(
                "status_type" => "suspended",
                "allowed_objects" => array("CommunityQuestion", "CommunityComment", "CommunityUser")),
            "archive" => array(
                "status_type" => "archive",
                "allowed_objects" => array("CommunityUser")),
            "delete" => array(
                "status_type" => "deleted",
                "allowed_objects" => array("CommunityQuestion", "CommunityComment", "CommunityUser")),
            "suspend_user" => array(
                "status_type" => "suspended",
                "allowed_objects" => array("CommunityQuestion" => "CommunityUser", "CommunityComment" => "CommunityUser")),
            "restore_user" => array(
                "status_type" => "active",
                "allowed_objects" => array("CommunityQuestion" => "CommunityUser", "CommunityComment" => "CommunityUser")),
            "reset_flags" => array(
                "allowed_objects" => array("CommunityQuestion" => "reset_flags", "CommunityComment" => "reset_flags")),
            "move" => array(
                "allowed_objects" => array("CommunityQuestion" => "move"))
        );
        $mockSocialUser = $this->CI->model('CommunityUser')->getBlank()->result;
        foreach($allowedActionObjectsMap as $action => $objectNames) {
            foreach($objectNames['allowed_objects'] as $key => $value) {
                if (isset($objectNames['status_type'])) {
                    if ($value === 'CommunityUser'
                        && (
                        (in_array($objectNames['status_type'], array('active', 'suspended', 'archive')) && !$mockSocialUser->SocialPermissions->canUpdateStatus())
                        || ($objectNames['status_type'] === 'deleted' && !$mockSocialUser->SocialPermissions->canDelete())
                        )
                    ) {
                        continue;
                    }
                    $objectToStore = !is_int($key) && (isset($metadataMappings[$key])) ? $key : $value;
                    $metadataMappings[$objectToStore]['allowed_actions'][$action] = $metadataMappings[$value]['status_type_ids'][$objectNames['status_type']];
                }
                else {
                    $metadataMappings[$key]['allowed_actions'][$action] = $value;
                }
            }
        }
    }

    /**
     * Returns the ROQL query for CommunityQuestion ratings.
     * Results are _not_ cached.
     * @param  string $questions Question id(s) in the where clause
     * @param  number $userID    User id in the where clause
     * @return ConnectTabular            Query
     */
    protected function getQuestionRatingQuery($questions, $userID) {
        return ConnectTabular::query(sprintf("SELECT r.RatingValue, r.RatingWeight, r.CommunityQuestion FROM CommunityQuestionRtg r WHERE r.CommunityQuestion IN (%s) AND r.CreatedByCommunityUser = %d",
            $questions, $userID), false);
    }

    /**
     * Returns the ROQL query for CommunityQuestion flaggings.
     * Results are _not_ cached.
     * @param  string $questions Question id(s) in the where clause
     * @param  number $userID    User id in the where clause
     * @return ConnectTabular            Query
     */
    protected function getQuestionFlagsQuery($questions, $userID) {
        return ConnectTabular::query(sprintf("SELECT f.Type AS FlagType, f.CommunityQuestion FROM CommunityQuestionFlg f WHERE f.CommunityQuestion IN (%s) AND f.CreatedByCommunityUser = %d",
            $questions, $userID), false);
    }

    /**
     * Returns the ROQL query for CommunityQuestion flaggings.
     * Results are _not_ cached.
     * @param  string $comments CommunityComment id(s) in the where clause
     * @param  number $userID    User id in the where clause
     * @return ConnectTabular            Query
     */
    protected function getCommentFlagsQuery($comments, $userID) {
        return ConnectTabular::query(sprintf("SELECT f.Type AS FlagType, f.CommunityComment FROM CommunityCommentFlg f WHERE f.CommunityComment IN (%s) AND f.CreatedByCommunityUser = %d",
            $comments, $userID), false);
    }

    /**
     * Returns the ROQL query for CommunityQuestion ratings.
     * Results are _not_ cached.
     * @param  string $comments CommunityComment id(s) in the where clause
     * @param  number $userID    User id in the where clause
     * @return ConnectTabular            Query
     */
    protected function getCommentRatingQuery($comments, $userID) {
        return ConnectTabular::query(sprintf("SELECT r.RatingValue, r.RatingWeight, r.CommunityComment FROM CommunityCommentRtg r WHERE r.CommunityComment IN (%s) AND r.CreatedByCommunityUser = %d",
            $comments, $userID), false);
    }
}
