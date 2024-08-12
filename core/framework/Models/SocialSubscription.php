<?php

namespace RightNow\Models;

use RightNow\Connect\v1_4 as Connect,
    RightNow\Utils\Framework,
    RightNow\Utils\Config,
    RightNow\ActionCapture;

/**
 * Handles the CRUD operations for both social question and social product/category subscriptions
 */
class SocialSubscription extends CommunityObjectBase {

    const SUBSCRIPTION_LIMIT = 600;

    /**
     * Adds a new social subscription
     * If subscription already exists then returns response object with error message
     *
     * @param int $objectID ID of an object to which the user is subscribing
     * @param string $type Type of the Subscription. One of the values of "Question","Product" or "Category"
     * @return social user object
     * @throws \Exception If Total number of Subscriptions for a given user are >= 600
     */
    public function addSubscription ($objectID, $type) {
        $metadata = $this->getMetadata($type);
        if (!$metadata->result) {
            return $metadata;
        }
        $metadata = $metadata->result;

        $socialUserResponseObject = $this->getSocialUser();
        if($socialUserResponseObject->errors)
            return $socialUserResponseObject;

        // Fetch the social user object
        $socialUserObject = $socialUserResponseObject->result;

        if ($socialUserObject->StatusWithType->StatusType->ID !== STATUS_TYPE_SSS_USER_ACTIVE) {
            return $this->getResponseObject(false, 'is_bool', Config::getMessage(DO_NOT_HAVE_PERMISSION_PERFORM_ACTION_LBL));
        }
        
        $objectResponse = $this->isValidObject($objectID, $type);
        if (!$objectResponse->result) {
            return $objectResponse;
        }
        $object = $objectResponse->result;

        // Get the subscription id
        $subscriptionID = $this->getSubscriptionID($objectID, $socialUserResponseObject->result->ID, $type)->result;

        // If subscription id exists then the user is already subscribed to the object thus throw an error
        if ($subscriptionID !== null) {
            return $this->getResponseObject(null, null, Config::getMessage(USER_IS_ALREADY_SUBSCRIBED_MSG));
        }

        try {

            // Check for max subscription limit of 600
            $subscriptionCount = 0;
            if ($socialUserObject->{$metadata["SubscriptionProperty"]} !== null) {
                $subscriptionCount = count($socialUserObject->{$metadata["SubscriptionProperty"]});
            }

            if($subscriptionCount >= self::SUBSCRIPTION_LIMIT) {
                throw new \Exception(Config::getMessage(THERE_REQUEST_EXCEEDED_SUB_LIMIT_MSG));
            }

            // Create the social subscription object
            $subscriptionConnectObjName = CONNECT_NAMESPACE_PREFIX . "\\" . $metadata["SubscriptionConnectObject"];
            $socialSubscription = new $subscriptionConnectObjName();
            $socialSubscription->{$metadata["SubscribedToProperty"]} = $object;
            $socialSubscription->DeliveryFrequency = 1; // Now
            $socialSubscription->DeliveryMethod = 1; // Email
            $socialSubscription->StartNotificationTime = time();

            // Trigger a notification when new question or new comment is added
            $socialSubscription->NotificationTriggerOptions->NewContent = true;

            // Add the subscription to the social user's subscription list and save it
            $socialUserObject->{$metadata["SubscriptionProperty"]}[] = $socialSubscription;

            // Save the social user object
            $socialUserObject->save();
        }
        catch (\Exception $ex) {
            return $this->getResponseObject(null, null, $ex->getMessage());
        }

        $acsSubject = lcfirst($type);

        ActionCapture::record($acsSubject, 'subscribe', $objectID);
        $this->CI->model('Clickstream')->insertAction($this->CI->session->getSessionData('sessionID'), $this->CI->session->getProfileData('contactID'), CS_APP_EU, '/' . $acsSubject . '_subscribe', $objectID, '', '');

        return $this->getResponseObject($socialUserObject);
    }

    /**
     * Fetches the subscription id of the user subscribed to the object
     *
     * @param int $objectID ID of an object to which the user is subscribed
     * @param int $userID The ID of CommunityUser
     * @param string $type Type of an Object. One of the values of "Question","Product" or "Category"
     * @return int|null the subscription id of the user subscribed to the object else return null
     */
    public function getSubscriptionID ($objectID, $userID, $type) {
        $metadata = $this->getMetadata($type);
        if (!$metadata->result) {
            return $metadata;
        }
        $metadata = $metadata->result;
        // If object id is not valid return error
        if (!Framework::isValidID($objectID)) {
            return $this->getResponseObject(null, null, $metadata["ErrorMsg"]["INVALID_ID"]);
        }

        // If the social user id is null return error
        if (!Framework::isValidID($userID)) {
            return $this->getResponseObject(null, null, Config::getMessage(INVALID_USER_ID_MSG));
        }

        // Fetches the subscription id
        try {
            $roql = sprintf("select " . $metadata["SubscriptionProperty"] . ".ID from CommunityUser where CommunityUser.ID = %d and " . $metadata['SubscriptionProperty'] . "." . $metadata['SubscribedToProperty'] . ".ID = %d and " . $metadata['SubscriptionProperty'] . ".Interface.ID = %d", $userID, $objectID, $this->interfaceID);
            $results = Connect\ROQL::query($roql)->next()->next();
        }
        catch (Connect\ConnectAPIErrorBase $e) {
            return $this->getResponseObject(null, null, $e->getMessage());
        }

        // Returns subscription id
        if ($results !== null) {
            return $this->getResponseObject(intval($results['ID']), 'is_int');
        }

        return $this->getResponseObject(null, 'is_null');
    }

    /**
     * Deletes one or all social subscription(s)
     *
     * @param int $objectID ID of an object to which the user is subscribed. Use -1 to unsubscribe to all subscriptions
     * @param string $type Type of an Object. One of the values of "Question", "Product" or "Category"
     * @return bool True if deletion was successful else returns null with the error message
     */
    public function deleteSubscription ($objectID, $type) {
        $metadata = $this->getMetadata($type);
        if (!$metadata->result) {
            return $metadata;
        }
        $metadata = $metadata->result;

        $socialUserResponseObject = $this->getSocialUser();
        if($socialUserResponseObject->errors)
            return $socialUserResponseObject;

        $socialUserObject = $socialUserResponseObject->result;

        $acsObject = $objectID;

        // If object ID is -1 then delete request is for all the subscriptions
        if($objectID === -1) {
            $socialUserObject->{$metadata["SubscriptionProperty"]} = null;
            $acsObject = 'all';
        }
        else {
            $objectResponse = $this->isValidObject($objectID, $type);
            if (!$objectResponse->result) {
                return $objectResponse;
            }
            // Get the subscription id
            $subscriptionID = $this->getSubscriptionID($objectID, $socialUserResponseObject->result->ID, $type)->result;
            // If subscription id exists then the user is already subscribed to the object thus throw an error
            if ($subscriptionID === null) {
                return $this->getResponseObject(null, null, Config::getMessage(USER_IS_NOT_SUBSCRIBED_MSG));
            }

            $subscriptionCount = count($socialUserObject->{$metadata["SubscriptionProperty"]});
            // Delete the subscription for the users subscription list
            for ($index = 0; $index < $subscriptionCount; $index++) {
                if ($socialUserObject->{$metadata["SubscriptionProperty"]}[$index]->ID === $subscriptionID) {
                    $socialUserObject->{$metadata["SubscriptionProperty"]}->offsetUnset($index);
                    break;
                }
            }
        }

        try {
            // Save the social user object
            $socialUserObject->save();
        }
        catch (\Exception $ex) {
            return $this->getResponseObject(null, null, $ex->getMessage());
        }

        $acsSubject = lcfirst($type);

        ActionCapture::record($acsSubject, 'unsubscribe', $acsObject);
        $this->CI->model('Clickstream')->insertAction($this->CI->session->getSessionData('sessionID'), $this->CI->session->getProfileData('contactID'), CS_APP_EU, '/' . $acsSubject . '_unsubscribe', $acsObject, '', '');

        return $this->getResponseObject(true, "is_bool");
    }

    /**
     * Validates the object to which the user is subscribing
     *
     * @param int $objectID ID of an object to which the user is subscribed
     * @param string $type Type of an Object. One of the values of "Question","Product" or "Category"
     * @return null|Connect/CommunityQuestion|Connect/ServiceCategory|Connect/ServiceProduct Connect Object
     */
    public function isValidObject ($objectID, $type) {
        $metadata = $this->getMetadata($type);
        if (!$metadata->result) {
            return $metadata;
        }
        $metadata = $metadata->result;

        if (!Framework::isValidID($objectID)) {
            return $this->getResponseObject(false, 'is_bool', $metadata["ErrorMsg"]["INVALID_ID"]);
        }
        $object = $this->CI->model($metadata["Model"])->get($objectID)->result;
        if (!$object) {
            return $this->getResponseObject(null, null, $metadata["ErrorMsg"]["NOT_EXIST"]);
        }
        if ($type === "Question") {
            // If the question is not active return error, since users can only subscribe or unsubscribe to active questions
            if ($object->StatusWithType->StatusType->ID !== STATUS_TYPE_SSS_QUESTION_ACTIVE) {
                return $this->getResponseObject(false, 'is_bool', $metadata["ErrorMsg"]["NOT_ACTIVE"]);
            }

            // If user does not have read permission on the question then user cannot subscribe
            if (!$object->SocialPermissions->canRead()) {
                return $this->getResponseObject(false, 'is_bool', Config::getMessage(DO_NOT_HAVE_PERMISSION_PERFORM_ACTION_LBL));
            }
        }
        else {
            // If user does not have read permission on product or category then user cannot subscribe
            $permissionedList = $this->CI->model("Prodcat")->getPermissionedListSocialQuestionRead($type === "Product")->result;
            if (!$permissionedList) {
                return $this->getResponseObject(false, 'is_bool', Config::getMessage(DO_NOT_HAVE_PERMISSION_PERFORM_ACTION_LBL));
            }

            if(is_array($permissionedList)) {
                $permissionedListIDs = array_map(function ($arr) {return $arr['ID'];}, $permissionedList);
                if(!in_array($objectID, $permissionedListIDs)) {
                    return $this->getResponseObject(false, 'is_bool', Config::getMessage(DO_NOT_HAVE_PERMISSION_PERFORM_ACTION_LBL));
                }
            }

            if (!$this->CI->model($metadata["Model"])->isEnduserVisible($object)) {
                return $this->getResponseObject(null, null, $metadata["ErrorMsg"]["NOT_VISIBLE"]);
            }
        }
        return $this->getResponseObject($object);
    }

    /**
     * Defines the the metadata needed for Social subscriptions
     *
     * @param string $type Type of an Object. One of the values of "Question","Product" or "Category"
     * @return array Array of Metadata of subscription objects
     */
    protected function getMetadata ($type) {
        static $metadata = null;
        if (!$metadata) {
            $metadata = array(
                "Question" => array("ErrorMsg" => array("INVALID_ID" => Config::getMessage(INVALID_QUESTION_ID_LBL),
                                                        "NOT_EXIST" => Config::getMessage(QUESTION_DOES_NOT_EXIST_LBL),
                                                        "NOT_ACTIVE" => Config::getMessage(QUESTION_IS_NOT_ACTIVE_LBL),
                                    ),
                                    "Model" => "CommunityQuestion",
                                    "SubscriptionProperty" => "CommunityQuestionSubscriptions",
                                    "SubscribedToProperty" => "CommunityQuestion",
                                    "SubscriptionConnectObject" => "CommunityQuestionSubscription"
                                ),
                "Product" => array("ErrorMsg" => array("INVALID_ID" => Config::getMessage(INVALID_PRODUCT_ID_MSG),
                                                       "NOT_EXIST" => Config::getMessage(PRODUCT_DOES_NOT_EXIST_LBL),
                                                       "NOT_VISIBLE" => Config::getMessage(PRODUCT_IS_NOT_VISIBLE_TO_END_USER_LBL)
                                                 ),
                                    "Model" => "Prodcat",
                                    "SubscriptionProperty" => "CommunityProductSubscriptions",
                                    "SubscribedToProperty" => "Product",
                                    "SubscriptionConnectObject" => "CommunityProductSubscription",
                                ),
                "Category" => array("ErrorMsg" => array("INVALID_ID" => Config::getMessage(INVALID_CATEGORY_ID_MSG),
                                                        "NOT_EXIST" => Config::getMessage(CATEGORY_DOES_NOT_EXIST_LBL),
                                                        "NOT_VISIBLE" => Config::getMessage(CATEGORY_IS_NOT_VISIBLE_TO_END_USER_LBL)
                                                    ),
                                "Model" => "Prodcat",
                                "SubscriptionProperty" => "CommunityCategorySubscriptions",
                                "SubscribedToProperty" => "Category",
                                "SubscriptionConnectObject" => "CommunityCategorySubscription"
                            )
            );
        }
        if (!$type || !$metadata[$type]) {
            return $this->getResponseObject(null, null, sprintf(Config::getMessage(VALUE_OF_TYPE_SHOULD_BE_ONE_OF_S_S_OR_S_LBL), "Question", "Product", "Category"));
        }
        return $this->getResponseObject($metadata[$type], "is_array");
    }

}
