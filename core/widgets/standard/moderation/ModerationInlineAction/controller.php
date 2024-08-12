<?php

namespace RightNow\Widgets;

use RightNow\Utils\Config;

class ModerationInlineAction extends \RightNow\Libraries\Widget\Base {

    protected $actions;

    function __construct ($attrs) {
        parent::__construct($attrs);
        $this->setAjaxHandlers(array(
            'submit_moderator_action_ajax' => array(
                'method' => 'saveModeratorAction',
                'clickstream' => 'save_inline_moderator_action',
            )
        ));
    }

    function getData () {
        $this->setActions();
        if (in_array($this->data['attrs']['object_type'], array('CommunityQuestion', 'CommunityComment'))) {
            $this->data['js']['contentActions'] = $this->getSocialObjectModeratorActions($this->data['attrs']['object_type']);
            $this->data['js']['authorID'] = ($author = $this->getSocialUser()) ? $author->ID : null;
        }
        $this->data['js']['userActions'] = $this->getSocialObjectModeratorActions('CommunityUser');
        $this->data['js']['userDeleteStatuses'] = $this->CI->model('CommunityUser')->getMappedSocialObjectStatuses(STATUS_TYPE_SSS_USER_DELETED)->result[STATUS_TYPE_SSS_USER_DELETED];
        // return if there are no permitted actions for the logged-in user
        if ((!isset($this->data['js']['contentActions']) || !$this->data['js']['contentActions']) && !$this->data['js']['userActions']) return false;
        
        $this->data['js']['f_tok'] = \RightNow\Utils\Framework::createTokenWithExpiration(0);
    }

    /**
     * Sets possible inline moderator actions for different social objects.
     */
    protected function setActions () {
        $this->actions = array(
            "CommunityQuestion" => array(
                "StatusActions" => array(
                    STATUS_TYPE_SSS_QUESTION_SUSPENDED => $this->data['attrs']['label_action_suspend_question'],
                    STATUS_TYPE_SSS_QUESTION_ACTIVE => $this->data['attrs']['label_action_approve_restore_question']),
                "OtherActions" => array(
                    "unlock" => $this->data['attrs']['label_action_unlock_question'],
                    "lock" => $this->data['attrs']['label_action_lock_question'])),
            "CommunityComment" => array(
                "StatusActions" => array(
                    STATUS_TYPE_SSS_COMMENT_SUSPENDED => $this->data['attrs']['label_action_suspend_comment'],
                    STATUS_TYPE_SSS_COMMENT_ACTIVE => $this->data['attrs']['label_action_approve_restore_comment'])),
            "CommunityUser" => array(
                "StatusActions" => array(
                    STATUS_TYPE_SSS_USER_SUSPENDED => $this->data['attrs']['label_action_suspend_user'],
                    STATUS_TYPE_SSS_USER_ACTIVE => $this->data['attrs']['label_action_approve_restore_user'],
                    STATUS_TYPE_SSS_USER_ARCHIVE => $this->data['attrs']['label_action_archive_user'],
                    STATUS_TYPE_SSS_USER_DELETED => $this->data['attrs']['label_action_delete_user'])),
        );
    }

    /**
     * Returns the social content object
     * @return Connect\SocialObject CommunityQuestion or CommunityComment object
     */
    protected function getSocialObject () {
        return $this->CI->model($this->data['attrs']['object_type'])->get($this->data['attrs']['object_id'])->result;
    }

    /**
     * Returns 'content author' if widget is invoked for CommunityComment/CommunityQuestion, or 'user' if widget invoked for specific CommunityUser (e.g user profile page)
     * @return Connect\CommunityUser CommunityUser object
     */
    protected function getSocialUser () {
        //checking the context whether the call is for author or specific social user
        if ($this->data['attrs']['object_type'] === 'CommunityUser') {
            return $this->CI->model('CommunityUser')->get($this->data['attrs']['object_id'])->result;
        }
        return ($socialObject = $this->getSocialObject()) ? $this->CI->model('CommunityUser')->get(isset($socialObject->CreatedByCommunityUser->ID) ? $socialObject->CreatedByCommunityUser->ID : 0)->result : null;
    }

    /**
     * Returns permitted moderator action list for the given social object
     * @param String $socialObjectType Social object type
     * @return Array|Null Array of all status types of the given social object
     */
    protected function getSocialObjectModeratorActions ($socialObjectType) {
        $actionList = array();
        switch ($socialObjectType) {
            case 'CommunityQuestion':
                if ($socialObject = $this->getSocialObject()) {
                    $canUpdateQuestionStatus = $socialObject->SocialPermissions->canUpdateStatus();
                    $canUpdateQuestionLock = $socialObject->SocialPermissions->canUpdateLock();
                    $socialObjectStatusTypeID = $socialObject->StatusWithType->StatusType->ID;
                }
                break;
            case 'CommunityComment':
                if ($socialObject = $this->getSocialObject()) {
                    $canUpdateCommentStatus = $socialObject->SocialPermissions->canUpdateStatus();
                    $socialObjectStatusTypeID = $socialObject->StatusWithType->StatusType->ID;
                }
                break;
            case 'CommunityUser':
                if (($socialUser = $this->getSocialUser()) && !$socialUser->SocialPermissions->isDeleted()) {
                    $canUpdateUserStatus = $socialUser->SocialPermissions->canUpdateStatus();
                    $canUpdateUserStatusDelete = $socialUser->SocialPermissions->canDelete();
                    $socialObjectStatusTypeID = $socialUser->StatusWithType->StatusType->ID;
                }
                break;
            default:
                return;
        }
        $socialObjectAllStatuses = $this->CI->model($socialObjectType)->getMappedSocialObjectStatuses()->result;
        if (!isset($socialObjectStatusTypeID) || !$socialObjectStatusTypeID || !$socialObjectAllStatuses) {
            return;
        }
        //Check permissions to show permitted actions only in the action menu
        if ((isset($canUpdateQuestionStatus) && $canUpdateQuestionStatus) || (isset($canUpdateCommentStatus) && $canUpdateCommentStatus) || $canUpdateUserStatus) {
            foreach ($this->actions[$socialObjectType]['StatusActions'] as $key => $value) {
                if ($key === $socialObjectStatusTypeID || $key === STATUS_TYPE_SSS_USER_DELETED) {
                    continue;
                }
                if ($this->data['attrs']['object_type'] !== 'CommunityUser' && in_array($key, array(STATUS_TYPE_SSS_USER_ARCHIVE, STATUS_TYPE_SSS_USER_DELETED))) continue;
                $actionList[$value] = key($socialObjectAllStatuses[$key]);
            }
        }

        if ($this->data['attrs']['object_type'] === 'CommunityUser' && $canUpdateUserStatusDelete) {
            $actionList[$this->actions[$socialObjectType]['StatusActions'][STATUS_TYPE_SSS_USER_DELETED]] = key($socialObjectAllStatuses[STATUS_TYPE_SSS_USER_DELETED]);
        }

        if (isset($socialObject) && $socialObject && $socialObjectType === 'CommunityQuestion' && $canUpdateQuestionLock) {
            $isSocialQuestionLocked = $socialObject->Attributes->ContentLocked;
            foreach ($this->actions[$socialObjectType]['OtherActions'] as $key => $value) {
                if (($key === "unlock" && !$isSocialQuestionLocked) || ($key === "lock" && $isSocialQuestionLocked)) {
                    continue;
                }
                $actionList[$value] = $key;
            }
        }
        return $actionList;
    }

    /**
     * Saves the moderator action on SocialContent and CommunityUser object and display JSON encoded result with SocialObjectType, SocialObjectID, ActionID, StatusWithTypeID, ContentLocked on success OR error message on failure
     * @param Array $params Post parameters having new actionID and SocialObject type
     */
    public function saveModeratorAction (array $params) {
        $this->setActions();
        $response = array('updatedObject' => array(), 'updatedUserActions' => array(), 'updatedContentActions' => array(), 'error' => null);
        $objectID = $this->data['attrs']['object_id'];

        if ($params['objectType'] === 'CommunityUser') {
            if ($this->data['attrs']['object_type'] !== $params['objectType'] && ($result = $this->CI->model($this->data['attrs']['object_type'])->isValidSocialObjectToModerate($objectID, $this->data['attrs']['object_type'])) && (is_object($result) && $result->error)) {
                $response['error'] = $result->errors[0]->externalMessage;
                $this->echoResponse($response);
                return;
            }

            $user = $this->getSocialUser();
            $objectID = $user ? $user->ID : null;
            if (!$objectID) {
                $response['error'] = $this->data['attrs']['label_user_not_found_error'];
                $this->echoResponse($response);
                return;
            }
        }
        if (($result = $this->CI->model($params['objectType'])->isModerateActionAllowed()) && (is_object($result) && $result->error)) {
            $response['error'] = $result->errors[0]->externalMessage;
            $this->echoResponse($response);
            return;
        }
        if (($result = $this->CI->model($params['objectType'])->isValidSocialObjectToModerate($objectID, $params['objectType'])) && (is_object($result) && $result->error)) {
            $response['error'] = $result->errors[0]->externalMessage;
            $this->echoResponse($response);
            return;
        }
        //Check whether the posted action is allowed or not.
        if (array_key_exists($params['actionID'], $this->actions['CommunityQuestion']['OtherActions'])) {
            $isValidAction = true;
            $fieldToUpdate = 'CommunityQuestion.Attributes.ContentLocked';
            $value = ($params['actionID'] === 'lock') ? 1 : 0;
        }
        else {
            $socialObjectAllStatuses = $this->CI->model($params['objectType'])->getMappedSocialObjectStatuses()->result;
            foreach ($this->actions[$params['objectType']]['StatusActions'] as $key => $value) {
                if ($this->data['attrs']['object_type'] !== 'CommunityUser' && in_array($key, array(STATUS_TYPE_SSS_USER_ARCHIVE, STATUS_TYPE_SSS_USER_DELETED))) continue;
                if (key($socialObjectAllStatuses[$key]) === (int) $params['actionID']) {
                    $isValidAction = true;
                    $connectObjectName = $this->CI->model($params['objectType'])->getSocialObjectMetadataMapping($params['objectType'], 'connect_object_names')->result['social_object_name'];
                    $fieldToUpdate = $connectObjectName . '.StatusWithType.Status.ID';
                    $value = $params['actionID'];
                    break;
                }
            }
        }
        if ($isValidAction) {
            $query = $this->CI->model($params['objectType'])->updateModeratorAction($objectID, array($fieldToUpdate => (object) array('value' => $value)));
            if ($query->result) {
                $actionType = $this->CI->model($params['objectType'])->getStatusTypeFromStatus($params['actionID']) ?: $params['actionID'];
                $response['updatedObject'] = array(
                    'objectType' => $params['objectType'],
                    'ID' => $query->result->ID,
                    'statusID' => $query->result->StatusWithType->Status->ID,
                    'statusWithTypeID' => $query->result->StatusWithType->StatusType->ID,
                    'successMessage' => $this->CI->model($params['objectType'])->getSocialObjectMetadataMapping($params['objectType'], 'success_messages')->result[$actionType]['single'] ?: $this->data['attrs']['label_on_success_banner']
                    );
                if (in_array($this->data['attrs']['object_type'], array('CommunityQuestion', 'CommunityComment'))) {
                    $response['updatedContentActions'] = $this->getSocialObjectModeratorActions($this->data['attrs']['object_type']);
                }
                if ($this->data['attrs']['object_type'] === 'CommunityQuestion') {
                    $response['updatedObject']['isContentLocked'] = $query->result->Attributes->ContentLocked;
                }
                $response['updatedUserActions'] = $this->getSocialObjectModeratorActions('CommunityUser');
                //Create clickstream and acs logs for successful moderator action
                $this->CI->model($params['objectType'])->createModerationClickstreamAndAcsLogs($params['objectType'], $params['actionID'], $objectID);
            }
            else {
                $response['error'] = $query->errors[0]->externalMessage;
            }
        }
        else {
            $response['error'] = $this->data['attrs']['label_invalid_moderator_action_error'];
        }
        $this->echoResponse($response);
    }

    /**
     * Echo out response and set flash data for page flips
     * @param Array $response Response to echo
     */
    private function echoResponse($response) {
        if($response['updatedObject']['successMessage'] && $this->data['attrs']['refresh_page_on_moderator_action']) {
            $this->CI->session->setFlashData('info', $response['updatedObject']['successMessage']);
        }
        echo json_encode($response);
    }
}
