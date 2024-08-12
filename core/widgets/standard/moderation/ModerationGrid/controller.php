<?php
namespace RightNow\Widgets;
use RightNow\Utils\Config,
    RightNow\Utils\Framework,
    RightNow\Utils\Text;

class ModerationGrid extends \RightNow\Widgets\Grid {

    function __construct($attrs)
    {
        parent::__construct($attrs);
        $this->setAjaxHandlers(
            array(
                'moderate_social_object_ajax' => array(
                    'method'        => 'moderateSocialObject',
                    'clickstream'   => 'SSSModDashboard',
                    'login_required' => true,
                )
            )
        );
    }

    function getData()
    {
        unset($this->data['attrs']['add_params_to_url'], $this->data['attrs']['highlight']);
        if (parent::getData() === false || !Framework::isSocialUser()) {
            return false;
        }
        if (empty($this->data['js']['columnID']) && $defaultSortDefinitions = $this->CI->model('Report')->getDefaultSortDefinitions($this->data['attrs']['report_id'])) {
            $this->data['js']['columnID'] = $defaultSortDefinitions[0]['col_id'];
            $this->data['js']['sortDirection'] = $defaultSortDefinitions[0]['sort_direction'];
        }
        if ($this->data['attrs']['prodcat_type'] === 'Category') {
            $prodColumnIndex = $this->data['attrs']['product_column_index'] - 1;
            $catColumnIndex = $this->data['attrs']['category_column_index'] - 1;
            $this->data['tableData']['headers'][$prodColumnIndex] = $this->data['tableData']['headers'][$catColumnIndex];
            $this->data['tableData']['headers'][$prodColumnIndex]['visible'] = true;
            $this->data['js']['headers'][$prodColumnIndex] = $this->data['tableData']['headers'][$prodColumnIndex];
            foreach ($this->data['tableData']['data'] as $index => $row) {
                $this->data['tableData']['data'][$index][$prodColumnIndex] = $row[$catColumnIndex];
            }
        }
        $statusMapping = $this->CI->model($this->data['attrs']['object_type'])->getSocialObjectMetadataMapping($this->data['attrs']['object_type'], 'allowed_actions')->result;
        $statuses = $this->CI->model($this->data['attrs']['object_type'])->getMappedSocialObjectStatuses()->result;           
        $this->data['js']['statuses'] = array('deleted' => isset($statusMapping['delete']) ? $statuses[$statusMapping['delete']] : null);

        if($this->data['attrs']['object_type'] === 'CommunityUser') {
            $this->data['js']['statuses']['suspended'] = $statuses[STATUS_TYPE_SSS_USER_SUSPENDED];
        }
        $this->classList->add('rn_' . $this->data['attrs']['object_type']);
        
        $this->data['js']['f_tok'] = \RightNow\Utils\Framework::createTokenWithExpiration(0);
    }

    /**
     * Method to receive moderation action AJAX request and perform the requested action
     * @param array $parameters Request data
     */
    function moderateSocialObject($parameters)
    {
        if (!Framework::isSocialUser()) {
            echo json_encode(array("error" => array($this->data['attrs']['label_not_logged_in_error']), 'report_refresh_required' => false));
            return;
        }
        $contextData = $this->getModerationActionContext($parameters);
        $reportRefreshRequired = true;
        if (in_array($parameters['action'], $this->getAllowedActions($contextData['object_type_to_moderate'], $parameters)) && $parameters['object_ids']) {
            $objectIDs = explode(',', $parameters['object_ids']);
            $responseMessage['error'] = array();
            $objectIDsToModerate = array();
            $objectIDsToLog = array();

            if (($result = $this->CI->model($contextData['object_type_to_moderate'])->isModerateActionAllowed()) && (is_object($result) && $result->error)) {
                 $actionAllowedError = $result->errors[0]->externalMessage;
                 $reportRefreshRequired = false;
            }
            else {
                foreach ($objectIDs as $objectID) {
                    if ($contextData['object_type_to_moderate'] === 'CommunityUser' && $objectID < 0) {
                        $responseMessage['error'][] = Config::getMessage(OPERATION_AUTHOR_UNKOWN_NO_S_SYSTEM_MSG);
                        continue;
                    }
                    if (($result = $this->CI->model($contextData['object_type_to_moderate'])->isValidSocialObjectToModerate($objectID, $contextData['object_type_to_moderate'])) && $result->error) {
                        $responseMessage['error'][] = $this->getFormattedErrorMessage($contextData['object_type_to_moderate'], $objectID, $result->errors[0]->externalMessage);
                        continue;
                    }
                    else if (($socialObject = $this->CI->model($contextData['object_type_to_moderate'])->get($objectID)->result) && $socialObject->StatusWithType->StatusType->ID === $contextData['social_object_metadata']['allowed_actions'][$contextData['action_name']]) {
                        //Optimization: Do not proceed further, if requested action is same as current status of social object.
                        continue;
                    }
                    $objectIDsToModerate[] = $objectID;
                    if ($parameters['action'] !== 'reset_flags') {
                        $result = $this->update($objectID, $contextData['object_type_to_moderate'], $contextData['connect_social_object_name'], $parameters);
                        if ($result->errors) {
                            $responseMessage['error'][] = $this->getFormattedErrorMessage($contextData['object_type_to_moderate'], $objectID, $result->errors[0]->externalMessage);
                        }
                        else {
                            $objectIDsToLog[] = $objectID;
                        }
                    }
                }
                if ($parameters['action'] === 'reset_flags' && $objectIDsToModerate) {
                    $result = $this->CI->model($contextData['object_type_to_moderate'])->resetSocialContentFlags($objectIDsToModerate, $contextData['object_type_to_moderate']);
                    if ($result->errors) {
                        if (isset($result->errors[0]->extraDetails) && $result->errors[0]->extraDetails) {
                            foreach($result->errors as $error) {
                                $responseMessage['error'][] = $this->getFormattedErrorMessage($contextData['object_type_to_moderate'], $error->extraDetails, $error->externalMessage);
                            }
                        }
                        else {
                            $responseMessage['error'][] = $result->errors[0]->externalMessage;
                        }
                    }
                    else {
                        $objectIDsToLog = $objectIDsToModerate;
                    }
                }
            }
            //No error, requested action is successful
            if (!$responseMessage['error'] && (!isset($actionAllowedError) || !$actionAllowedError)) {
                $actionType = $this->CI->model($contextData['object_type_to_moderate'])->getStatusTypeFromStatus($parameters['action']) ?: $parameters['action'];
                $responseMessage['success'][] = $contextData['social_object_metadata']['success_messages'][$actionType][count($objectIDs) > 1 || $actionType === "reset_flags" ? 'multiple' : 'single'] ?: $this->data['attrs']['label_action_successful'];
            }
            else {
                //Authentication or authorization error.
                if (isset($actionAllowedError) && $actionAllowedError) {
                    $responseMessage['error'][] = $actionAllowedError;
                }
                else {
                    //Individual object spcific error, e.g. invalid question id, object already deleted etc
                    if ($responseMessage['error']) {
                        array_unshift($responseMessage['error'], $this->data['attrs']['label_action_successful_with_error']);
                    }
                }
            }
        }
        else {
            $reportRefreshRequired = false;
            $responseMessage = array("error" => array($this->data['attrs']['label_requested_action_not_supported_error']));
        }

        //insert clickstream and ACS entry for different actions
        if ($objectIDsToLog) {
            foreach($objectIDsToLog as $objectID) {
                $this->CI->model($contextData['object_type_to_moderate'])->createModerationClickstreamAndAcsLogs($contextData['object_type_to_moderate'], $parameters['action'], $objectID);
            }
        }
        echo json_encode(array_merge($responseMessage, array('report_refresh_required' => $reportRefreshRequired)));
    }

    /**
     * Create a formatted error message
     * @param string $objectType Name of the social object
     * @param int $objectID Social Object ID
     * @param string $message Original error message
     * @return string formatted error message with Object ID and Title if exist.
     */
    protected function getFormattedErrorMessage($objectType, $objectID, $message) {
        $fieldToShow = array(
            'CommunityQuestion' => array('field' => 'Subject', 'object_label' => Config::getMessage(QUESTION_ID_COLON_LBL)), 
            'CommunityUser' => array('field' => 'DisplayName', 'object_label' => Config::getMessage(USER_ID_COLON_LBL)),
            'CommunityComment' => array('field' => 'Body', 'object_label' => Config::getMessage(COMMENT_ID_COLON_LBL))
        );
        $objectTitle = ($socialObject = $this->CI->model($objectType)->get($objectID)->result) ? $socialObject->$fieldToShow[$objectType]['field'] : '';
        if($objectTitle) {
            $objectTitle = \RightNow\Libraries\Formatter::formatTextEntry($objectTitle, $socialObject->BodyContentType->LookupName, false);
        }
        return ($objectTitle ? '<i>' . Text::truncateText($objectTitle, 75) . '</i> - ' : '') . $message . ' <span>(' . $fieldToShow[$objectType]['object_label'] .' '. $objectID . ')</span>';
    }

    /**
     * Get possible allowed moderation actions for a given social object name
     * @param string $objectTypeToModerate Name of the social object to moderate
     * @return array Array of allowed actions
     */
    protected function getAllowedActions ($objectTypeToModerate) {
        $statuses = $this->CI->model($objectTypeToModerate)->getMappedSocialObjectStatuses()->result;
        $allowedActionsStatuses = array();
        if ($statuses) {
            $allowedActions = $this->CI->model($objectTypeToModerate)->getSocialObjectMetadataMapping($objectTypeToModerate, 'allowed_actions')->result;
            foreach ($allowedActions as $action => $value) {
                $allowedActionsStatuses[] = isset($statuses[$allowedActions[$action]]) ? key($statuses[$allowedActions[$action]]) : $value;
            }
        }
        return $allowedActionsStatuses;
    }

    /*
     * Lookup a user's default avatar data
     * @param String $displayName String containing Social User's display name
     * @return array Array that contains user's default avatar information
     */
    function getDefaultAvatar ($displayName) {
        $displayName = strip_tags($displayName);
        return array('text' => strtoupper(Text::getMultibyteSubstring($displayName, 0, 1)), 'color' => strlen($displayName) % 5);
    }

    /**
     * Get requested moderation action and related information
     * @param array $parameters Request data
     * @return array Array of data to identify current moderation action and related metadata
     */
    private function getModerationActionContext($parameters) {
        $actionContextData = array(
            'object_type_to_moderate' => $this->data['attrs']['object_type'],
            'action_name' => $parameters['action_name']
        );
        //Update allowed action and other required dynamic variable if requested action is "suspend_user" or "restore_user" but not from CommunityUser dashboard.
        if ($this->data['attrs']['object_type'] !== 'CommunityUser' && in_array($parameters['action_name'], array('restore_user', 'suspend_user'))) {
            $actionContextData['object_type_to_moderate'] = 'CommunityUser';
            $actionContextData['action_name'] = ($parameters['action_name'] === 'suspend_user') ? 'suspend' : 'restore';
        }
        $actionContextData['social_object_metadata'] = $this->CI->model($actionContextData['object_type_to_moderate'])->getSocialObjectMetadataMapping($actionContextData['object_type_to_moderate'])->result;
        $actionContextData['connect_social_object_name'] = $actionContextData['social_object_metadata']['connect_object_names']['social_object_name'];
        return $actionContextData;
    }


    /**
     * Updates the given social object with the moderator action
     * @param int $objectID ID of the social object
     * @param string $objectTypeToModerate Social object name
     * @param string $connectSocialObjectName Connect object name for social object
     * @param array $parameters Request data
     * @return RightNow\Libraries\ResponseObject Updated social object OR error message if the social object wasn't updated
     */
    private function update ($objectID, $objectTypeToModerate, $connectSocialObjectName, array $parameters) {
        if ($this->data['attrs']['object_type'] === 'CommunityQuestion' && $parameters['action'] === 'move') {
            $fieldToUpdate = '.' . $parameters['prodcat_type'];
            $value = (int) $parameters['prodcat_id'];
        }
        else {
            $fieldToUpdate = '.StatusWithType.Status.ID';
            $value = $parameters['action'];
        }
        return $this->CI->model($objectTypeToModerate)->updateModeratorAction($objectID, array($connectSocialObjectName . $fieldToUpdate => (object) array('value' => $value)));
    }
}
