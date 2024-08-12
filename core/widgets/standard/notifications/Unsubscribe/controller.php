<?php

namespace RightNow\Widgets;
use RightNow\Utils\Url;

class Unsubscribe extends \RightNow\Libraries\Widget\Base {
    function __construct($attrs) {
        parent::__construct($attrs);
    }

    function getData() {
        $email = Url::getParameter('email');
        $contactID = Url::getParameter('cid');
        $answerID = Url::getParameter('a_id');
        $unsubscribe = Url::getParameter('unsub');
        if(!$email || !$contactID) {
            return $this->displayError();
        }

        $this->data['email'] = \RightNow\Api::decode_and_decrypt($email);
        $this->data['js']['f_tok'] = \RightNow\Utils\Framework::createTokenWithExpiration(0);
        $contactID = $this->data['js']['contactID'] = intval(\RightNow\Api::decode_and_decrypt($contactID));

        $this->data['js']['answerID'] = $answerID;
        if($answerID) {
            $notifications = $this->CI->model('Notification')->getNotificationsForAnswer($answerID, $contactID)->result;
            $answerObj = "\\" . CONNECT_NAMESPACE_PREFIX ."\Answer";
            $answer = $answerObj::fetch($answerID);
            $this->data['answerUrl'] = '/app/' . \RightNow\Utils\Config::getConfig(CP_ANSWERS_DETAIL_URL) . "/a_id/$answerID";
            if(!count($notifications) || !$answer) {
                $this->data['resultMessage'] = sprintf(\RightNow\Utils\Config::getMessage(EMAIL_ADDRESS_PCT_S_UNSUBSCRIBED_LBL), '<b>' . $this->data['email'] . '</b>');
                return;
            }
            $this->data['resultMessage'] = sprintf(\RightNow\Utils\Config::getMessage(EMAIL_ADDR_PCT_S_SCC_UNSUB_MSG), '<b>' . $this->data['email'] . '</b>');
            $this->data['answerUrl'] = '/app/' . \RightNow\Utils\Config::getConfig(CP_ANSWERS_DETAIL_URL) . "/a_id/$answerID";
            $answerNotifications = isset($notifications['answer']) ? $notifications['answer'] : array();
            $productNotifications = isset($notifications['product']) ? $notifications['product'] : array();
            $categoryNotifications = isset($notifications['category']) ? $notifications['category'] : array();
            $notificationType = null;
            if (count($answerNotifications) === 1 && !$productNotifications && !$categoryNotifications) {
                $notificationType = 'answer';
                $ID = $answerNotifications[0]->Answer->ID;
            }
            else if (count($productNotifications) === 1 && !$answerNotifications && !$categoryNotifications) {
                $notificationType = 'product';
                $ID = $productNotifications[0]->Product->ID;
            }
            else if (count($categoryNotifications) === 1 && !$answerNotifications && !$productNotifications) {
                $notificationType = 'category';
                $ID = $categoryNotifications[0]->Category->ID;
            }

            if ($notificationType) {
                //There's a single notification, immediately unsubscribe from it
                $this->CI->model('Notification')->delete($notificationType, $ID, $contactID);
                $this->data['instructMessage'] = \RightNow\Utils\Config::getMessage(WSH_CONTINUE_RECEIVING_UPD_NOTIF_MSG);
            }
            else {
                //There are multiple notifs, make the user decide
                $this->data['resultMessage'] = sprintf(\RightNow\Utils\Config::getMessage(EMAIL_ADDR_PCT_S_RECEIVING_MULT_MSG), '<b>' . $this->data['email'] . '</b>');
                $this->data['instructMessage'] = \RightNow\Utils\Config::getMessage(PLEASE_CHOOSE_NOTIF_UNSUBSCRIBE_MSG);
            }
            $notificationDetails = array();
            foreach($notifications as $type => $details) {
                if($type === 'answer') {
                    $notificationDetails[] = array('type' => $type,
                                                   'label' => sprintf("%s - %s", \RightNow\Utils\Config::getMessage(ANSWER_LBL), $answer->Summary),
                                                   'id' => intval($answerID));
                }
                else {
                    foreach($details as $productCategoryNotification) {
                        list($idChain, $labelChain) = $this->getProductCategoryChain($productCategoryNotification, $type);
                        $currentID = ($type === 'product') ? $productCategoryNotification->Product->ID : $productCategoryNotification->Category->ID;
                        $notificationDetails[] = array('type' => $type,
                                                       'idChain' => $idChain,
                                                       'label' => $labelChain,
                                                       'id' => $currentID,
                                                       'typeDefine' => ($type === 'product' ? HM_PRODUCTS : HM_CATEGORIES));
                    }
                }
            }
            $this->data['js']['notifications'] = $notificationDetails;
        }
        //unsubscribing from all notifs
        else if($unsubscribe) {
            $this->CI->model('Notification')->deleteAll('product', $contactID);
            $this->CI->model('Notification')->deleteAll('category', $contactID);
            $this->CI->model('Notification')->deleteAll('answer', $contactID);
            return $this->displayError(sprintf(\RightNow\Utils\Config::getMessage(EMAIL_ADDR_PCT_S_UNSUB_NOTIF_MSG), '<b>' . $this->data['email'] . '</b>'));
        }
        else {
            return $this->displayError();
        }
    }

    /**
     * Prints the provided error message, otherwise a default error message is used
     * @param string $message The error message (Optional)
     * @return bool Returns false
     */
    protected function displayError($message = null) {
        echo $message ?: \RightNow\Utils\Config::getMessage(OBTAIN_NOTIFICATION_INFORMATION_MSG);
        return false;
    }

    /**
     * Take a product/category notification and build up its label and ID chain.
     * @param object $notification The Connect notification object
     * @param string $type The type of notification, either product or category
     * @return array Array of product/category ID chain and label
     */
    protected function getProductCategoryChain($notification, $type) {
        $idChain = $labelChain = '';
        $labelChain = ($type === 'product' ? \RightNow\Utils\Config::getMessage(PRODUCT_LBL) : \RightNow\Utils\Config::getMessage(CATEGORY_LBL)) . ' - ';
        $notificationObject = ucfirst($type);
        $hierarchyType = "{$notificationObject}Hierarchy";
        foreach($notification->$notificationObject->$hierarchyType as $parent) {
            $idChain .= $parent->ID . ",";
            $labelChain .= $parent->LookupName . ' / ';
        }
        $idChain .= $notification->$notificationObject->ID;
        $labelChain .= $notification->$notificationObject->LookupName;
        return array($idChain, $labelChain);
    }
}
