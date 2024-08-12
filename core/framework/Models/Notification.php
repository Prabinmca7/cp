<?php

namespace RightNow\Models;

use RightNow\Api,
    RightNow\Connect\v1_4 as Connect,
    RightNow\Utils\Connect as ConnectUtil,
    RightNow\Utils\Text,
    RightNow\Utils\Framework,
    RightNow\ActionCapture;

/**
 * Handles the CRUD operations for both answer and product/category notifications
 */
class Notification extends Base
{
    private $interfaceID;
    public function __construct()
    {
        parent::__construct();
        $this->interfaceID = Api::intf_id();
    }

    /**
     * Return an array of specified product, category and/or answer notifications. Only returns the notifications that
     * are associated with this interface and formats the answer summary.
     *
     * @param string|array $notificationType A string indicating the type of notification (e.g. 'answer', or 'all'),
     * or an array of notifiction types e.g. ('product', 'category').
     * @param int $contactID Contact ID of the user. If unspecified, uses the currently logged in user
     * @return array An array of notification objects with the notification type(s) as key.
     */
    public function get($notificationType, $contactID = null) {
        $response = $this->getNotificationsByType($notificationType, $contactID);
        if($response->warnings || $response->errors) {
            return $response;
        }

        $notifications = array();
        foreach($response->result as $filterType => $notificationsByType) {
            foreach($notificationsByType as $notification) {
                if($notification->Interface->ID === $this->interfaceID) {
                    if($filterType === 'answer') {
                        // Only return notifications for public answers
                        if ($notification->Answer->StatusWithType->StatusType->ID !== ANS_PUBLIC) {
                            continue;
                        }
                        // Perform some formatting on answers summary
                        $notification->Answer->Summary = Api::print_text2str($notification->Answer->Summary, OPT_VAR_EXPAND | OPT_ESCAPE_SCRIPT | OPT_ESCAPE_HTML);
                    }
                    $notifications[$filterType][] = $notification;
                }
            }
        }

        $response->result = $notifications;
        return $response;
    }

    /**
     * Adds a new product, category or answer notification.
     * If notification already exists, renew it.
     *
     * @param string $notificationType Either product, category or answer.
     * @param int $ID ID of the product, category or answer.
     * @param int|null $contactID The contact id to perform the action upon, or null if user is logged in.
     * @return array An array of notification objects.
     */
    public function add($notificationType, $ID, $contactID = null)
    {
        list($response, $data) = $this->prepareUpdate('add', $notificationType, $ID, $contactID);
        if (!$response->errors && !$response->warnings) {
            try {
                $notificationType = $data['notification_type'];
                $notifications = $data['notifications'];
                $contact = $data['contact'];
                if ($data['offset'] !== null) {
                    $response->action = $action = 'renew';
                    $this->renewNotification($notificationType, $ID, $contact, $notifications, $data['offset']);
                }
                else {
                    $response->action = $action = 'create';
                    if ($notificationType === 'Product') {
                        $notification = new Connect\ProductNotification;
                        $notification->Product = Connect\ServiceProduct::fetch($ID);
                        $notification->StartTime = time();
                        $notification->ExpireTime = $this->getExpiration();
                        $notification->Interface = $this->interfaceID;
                    }
                    else if ($notificationType === 'Category') {
                        $notification = new Connect\CategoryNotification;
                        $notification->Category = Connect\ServiceCategory::fetch($ID);
                        $notification->StartTime = time();
                        $notification->ExpireTime = $this->getExpiration();
                        $notification->Interface = $this->interfaceID;
                    }
                    else {
                        $notification = new Connect\AnswerNotification;
                        $notification->Answer = Connect\Answer::fetch($ID);
                        $notification->Interface = $this->interfaceID;
                    }

                    $notifications[] = $notification;
                    ConnectUtil::save($contact, SRC2_EU_CONTACT_EDIT);
                }

                $response->result = $this->getReturn($notificationType, $data['contact_id']);
            }
            catch (Connect\ConnectAPIErrorBase $e) {
                $response->error = $e->getMessage();
            }
            ActionCapture::record($notificationType === 'Answer' ? 'answerNotification' : 'prodCatNotification', $action, $ID);
        }

        return $response;
    }

    /**
     * Deletes a product, category or answer notification
     *
     * @param string $notificationType One or product, category or answer.
     * @param int $ID ID of the notification
     * @param int|null $contactID The contact id to perform the action upon, or null if user is logged in.
     * @return array An array of notification objects.
     */
    public function delete($notificationType, $ID, $contactID = null)
    {
        list($response, $data) = $this->prepareUpdate('delete', $notificationType, $ID, $contactID);
        if (!$response->errors && !$response->warnings) {
            try {
                $data['notifications']->offsetUnset($data['offset']);
                ConnectUtil::save($data['contact'], SRC2_EU_CONTACT_EDIT);
                $notificationType = $data['notification_type'];
                $response->result = $this->getReturn($notificationType, $data['contact_id']);
            }
            catch (Connect\ConnectAPIErrorBase $e) {
                $response->error = $e->getMessage();
            }
            ActionCapture::record($notificationType === 'Answer' ? 'answerNotification' : 'prodCatNotification', 'delete', $ID);
        }

        return $response;
    }

    /**
     * Deletes all notifications of specified type for a contact.
     *
     * @param string $notificationType One of product, category or answer.
     * @param int|null $contactID The contact id to perform the action upon, or null if user is logged in.
     * @return array An array of product/category notification objects.
     */
    public function deleteAll($notificationType, $contactID = null)
    {
        list($response, $data) = $this->prepareUpdate('delete', $notificationType, 'ALL', $contactID);
        if (!$response->errors && !$response->warnings) {
            try {
                $notificationType = $data['notification_type'];
                $contact = $data['contact'];
                $shouldSave = false;
                if ($notificationType === 'Product' && $contact->ServiceSettings->ProductNotifications) {
                    $shouldSave = true;
                    $contact->ServiceSettings->ProductNotifications = null;
                }
                else if ($notificationType === 'Category' && $contact->ServiceSettings->CategoryNotifications) {
                    $shouldSave = true;
                    $contact->ServiceSettings->CategoryNotifications = null;
                }
                else if ($notificationType === 'Answer' && $contact->ServiceSettings->AnswerNotifications) {
                    $shouldSave = true;
                    $contact->ServiceSettings->AnswerNotifications = null;
                }
                if ($shouldSave) {
                    ConnectUtil::save($contact, SRC2_EU_CONTACT_EDIT);
                }
                $response->result = $this->getReturn($notificationType, $data['contact_id']);
            }
            catch (Connect\ConnectAPIErrorBase $e) {
                $response->error = $e->getMessage();
            }
            if(isset($ID)){
                ActionCapture::record($notificationType === 'Answer' ? 'answerNotification' : 'prodCatNotification', 'deleteAll', $ID);
            }else{
                ActionCapture::record($notificationType === 'Answer' ? 'answerNotification' : 'prodCatNotification', 'deleteAll');
            }
        }
        return $response;
    }

    /**
     * Renews a product, category or answer notification by updating the start_time column.
     *
     * @param string $notificationType One or product, category or answer.
     * @param int $ID ID of the notification
     * @param int|null $contactID The contact id to perform the action upon, or null if user is logged in.
     * @return array An array of notification objects.
     */
    public function renew($notificationType, $ID, $contactID = null)
    {
        list($response, $data) = $this->prepareUpdate('renew', $notificationType, $ID, $contactID);
        if (!$response->errors && !$response->warnings) {
            try {
                $notificationType = $data['notification_type'];
                $this->renewNotification($notificationType, $ID, $data['contact'], $data['notifications'], $data['offset']);
            }
            catch (Connect\ConnectAPIErrorBase $e) {
                $response->error = $e->getMessage();
            }
            ActionCapture::record($notificationType === 'Answer' ? 'answerNotification' : 'prodCatNotification', 'renew', $ID);
        }

        $response->result = $this->getReturn($notificationType, $data['contact_id']);
        return $response;
    }


    /**
     * Returns an array of all notification tied to a particular answer. This includes
     * direct answer notifications on this answer as well as product/category notifications that
     * are tied to this answer.
     *
     * @param int $answerID The Answer ID from which to retrieve notifications
     * @param int $contactID The ID of the contact from which to retrieve notifications
     * @return array Answer and product/category notification for the user
     */
    public function getNotificationsForAnswer($answerID, $contactID = null)
    {
        $results = array();
        $contactID = intval($contactID);
        $answerID = intval($answerID);
        $response = $this->get('all', $contactID);
        if ($response->errors || $response->warnings) {
            return $response;
        }
        $notifications = $response->result;
        $notificationAnswerCount = isset($notifications['answer']) ? count($notifications['answer']) : 0;
        if(Framework::isValidID($answerID) && $notificationAnswerCount){
            foreach($notifications['answer'] as $notification){
                if($notification->Answer->ID === $answerID){
                    $results['answer'][] = $notification;
                    break;
                }
            }
        }

        $itemsAssociatedWithAnswer = $this->getItemsAssociatedWithAnswer($answerID);

        //We need to iterate over all the product/category notifications and see if the ID of the prod/cat
        //is tied to the answer
        $notificationProductCount = isset($notifications['product']) ? count($notifications['product']) : 0;
        if($notificationProductCount){
            foreach($notifications['product'] as $notification){
                if(isset($itemsAssociatedWithAnswer[$notification->Product->ID]) && $itemsAssociatedWithAnswer[$notification->Product->ID]){
                    if(!isset($results['product']) || !is_array($results['product'])){
                        $results['product'] = array();
                    }
                    $results['product'][] = $notification;
                }
            }
        }
        $notificationCategoryCount = isset($notifications['category']) ? count($notifications['category']) : 0;
        if($notificationCategoryCount){
            foreach($notifications['category'] as $notification){
                if(isset($itemsAssociatedWithAnswer[$notification->Category->ID]) && $itemsAssociatedWithAnswer[$notification->Category->ID]){
                    if(!isset($results['category']) || !is_array($results['category'])){
                        $results['category'] = array();
                    }
                    $results['category'][] = $notification;
                }
            }
        }
        $response->result = $results;
        return $response;
    }

    /**
     * Return an array of specified product, category and/or answer notifications. These notifications are not
     * filtered by interface and do not return formatted answer summaries. The get() function wraps this one
     * to provide that functionality.
     *
     * @param string|array $notificationType A string indicating the type of notification (e.g. 'answer', or 'all'),
     * or an array of notifiction types (e.g. ('product', 'category')).
     * @param int $contactID Contact ID of the user. If unspecified, uses the currently logged in user
     * @return array An array of notification objects with the notification type(s) as key.
     */
    protected function getNotificationsByType($notificationType, $contactID = null) {
        $response = $this->getResponseObject(array(), 'is_array');

        if (!$contactObject = $this->getContactObject($contactID)) {
            $response->error = 'Unable to determine contact.';
            return $response;
        }

        $filters = array();
        if (!is_array($notificationType)) {
            $notificationType = array($notificationType);
        }
        foreach ($notificationType as $type) {
            if ($type === 'all') {
                $filters = array('Product', 'Category', 'Answer');
                break;
            }
            if (!$connectName = $this->getNotificationType($type, 'name')) {
                $response->error = "Invalid filter type: '$type'";
                return $response;
            }
            $filters[] = $connectName;
        }

        $notifications = array();
        foreach ($filters as $filter) {
            $notificationsName = "{$filter}Notifications";
            if ($objects = $contactObject->ServiceSettings->$notificationsName) {
                $notifications[strtolower($filter)] = $objects;

                // ExpireTime is not an actual field in the notification object. Create as a "meta" tag in response.
                foreach($objects as $notificationObject) {
                    $notificationObject->ExpireTime = $this->getExpiration($notificationObject->StartTime);
                }
            }
        }
        $response->result = $notifications;
        return $response;
    }

    /**
     * Renew either an answer or product category notification.
     * @param string $notificationType The type of notification, either Answer, Product, or Category
     * @param int $ID The ID of the answer, product, or category to update
     * @param Connect\Contact $contact The Connect contact object
     * @param object $notifications The current list of notifications for the contact
     * @param int $offset The index of the notification that we're updating
     * @return void
     */
    protected function renewNotification($notificationType, $ID, Connect\Contact $contact, $notifications, $offset) {
        if ($notificationType === 'Answer') {
            //StartTime is read-only for connect answers but if we "update" the field it correctly
            //updates the start time.
            $notifications[$offset]->Answer = Connect\Answer::fetch($ID);
        }
        else {
            $notifications[$offset]->StartTime = time();
        }
        ConnectUtil::save($contact, SRC2_EU_CONTACT_EDIT);
    }

    /**
     * Return either a product, category or answer name, or ID as specified by $format.
     * Note: Answers do not have an ID, so just the name is returned regardless of format.
     *
     * @param string $notificationType One of prod*, cat*, ans* or a product or category id
     * @param string $format Return one of 'Product', 'Category' or 'Answer' if 'name', else return prod/cat ID (or 'Answer').
     * @return string|int|null One of 'Product', 'Category', 'Answer' '{product_id}', '{category_id}' or null.
     */
    protected function getNotificationType($notificationType, $format = 'name') {
        if(intval($notificationType) === HM_PRODUCTS || Text::beginsWith(strtolower($notificationType), 'prod'))
            return ($format === 'name') ? 'Product' : HM_PRODUCTS;
        if(intval($notificationType) === HM_CATEGORIES || Text::beginsWith(strtolower($notificationType), 'cat'))
            return ($format === 'name') ? 'Category' : HM_CATEGORIES;
        if(Text::beginsWith(strtolower($notificationType), 'ans'))
            return 'Answer';
    }

    /**
     * Return array of notifications from $this->get(). Both 'product' and 'category' are returned when $notificationType is 'Product' or 'Category'.
     *
     * @param string $notificationType Type of notification
     * @param int $contactID Contact ID of the user.
     * @return array List of notifications
     */
    protected function getReturn($notificationType, $contactID) {
        if ($notificationType !== 'Answer') {
            $notificationType = array('product', 'category');
        }
        return $this->get($notificationType, $contactID)->result;
    }

    /**
     * Return formatted expiration date if ANS_NOTIF_DURATION set (e.g. "Expires 12/09/2011 (3 days)", else return "No Expiration Date")
     * @param int $startTime Number of seconds since epoch when the subscription was created
     * @return string Expiration time in a label
     */
    protected function getExpiration($startTime = null) {
        $expiration = '';

        if($startTime === null)
            $startTime = time();

        // Only bother checking expiration of ANS_NOTIF_DURATION is set and non-zero.
        if (($duration = \RightNow\Utils\Config::getConfig(ANS_NOTIF_DURATION)) && $duration > 0)
        {
            $date = sprintf(\RightNow\Utils\Config::getMessage(EXPIRES_PCT_S_LBL), Api::date_str(DATEFMT_SHORT, strtotime("+$duration day", $startTime)));

            // Time trickery: Round the startTime and now to the beginning of the day for calculating how many days the subscription will last.
            // 86,400 seconds in a day.
            $nowDay = floor(time() / 86400);
            $startDay = floor($startTime / 86400);
            $daysLeft = intval($duration - ($nowDay - $startDay));
            $days = ($daysLeft === 1) ? sprintf(\RightNow\Utils\Config::getMessage(PCT_D_DAY_LBL), $daysLeft) : sprintf(\RightNow\Utils\Config::getMessage(PCT_D_DAYS_LBL), $daysLeft);

            $expiration = "$date ($days)";
        }
        else
        {
            $expiration = \RightNow\Utils\Config::getMessage(NO_EXPIRATION_DATE_LBL);
        }

        return $expiration;
    }

    /**
     * Retrieve cached contact model object.
     * @param int $contactID ID of the contact
     * @return Connect\Contact|null Contact object or null if no contact was found.
     */
    protected function getContactObject($contactID) {
        static $contacts = array();
        if (($contactID = (intval($contactID) ?: $this->getContactID($contactID))) && !array_key_exists($contactID, $contacts)) {
            $contacts[$contactID] = $this->CI->model('Contact')->get($contactID)->result;
        }
        return isset($contacts[$contactID]) ? $contacts[$contactID] : null;
    }

    /**
     * Perform validation and data gathering in preparation for an add, delete or renew on a product or category notification.
     *
     * @param string $mode One of 'add', 'renew' or 'delete'.
     * @param string $type One of product, category, or answer.
     * @param int $ID The id of the product, category or answer
     * @param int|null $contactID The contact id to perform the action upon, or null if user is logged in.
     * @return array An array containing a response object as well as a data array.
     * @throws \Exception If the mode provided isn't on the available options
     */
    private function prepareUpdate($mode, $type, $ID, $contactID)
    {
        if (!in_array($mode, array('add', 'renew', 'delete'))) {
            throw new \Exception("Invalid mode: '$mode'");
        }

        if ($abuseMessage = $this->isAbuse()) {
            return array($this->getResponseObject(false, 'is_bool', $abuseMessage), array());
        }

        $contactID = $this->getContactID($contactID);

        $response = $this->getNotificationsByType($type, $contactID);
        if($response->warnings || $response->errors) {
            return array($response, array());
        }

        $notificationType = $this->getNotificationType($type, 'name');
        $notifications = isset($response->result[strtolower($notificationType)]) ? $response->result[strtolower($notificationType)] : null;

        if ($ID !== 'ALL') {
            if (!Framework::isValidID($ID)) {
                $response->error = 'Invalid ID';
            }
            if ((($offset = $this->getOffsetFromID($notificationType, intval($ID), $notifications)) === null) && $mode !== 'add') {
                $response->warning = "Notification not found: '$ID'";
            }
        }

        $contact = $this->getContactObject($contactID);

        if ($mode === 'add' && !$response->errors && !is_object($notifications)) {
            // contact has no existing notifications of specified type
            if ($notificationType === 'Product') {
                $notifications = $contact->ServiceSettings->ProductNotifications = new Connect\ProductNotificationArray();
            }
            else if ($notificationType === 'Category') {
                $notifications = $contact->ServiceSettings->CategoryNotifications = new Connect\CategoryNotificationArray();
            }
            else{
                $notifications = $contact->ServiceSettings->AnswerNotifications = new Connect\AnswerNotificationArray();
            }
        }

        return array($response, array(
            'contact_id' => $contactID,
            'contact' => $contact,
            'offset' => isset($offset) ? $offset : $this->getOffsetFromID($notificationType, intval($ID), $notifications),
            'notifications' => $notifications,
            'notification_type' => $notificationType,
        ));
    }


    /**
     * Return contact ID, either from $contactID, or from session profile.
     *
     * @param int|null $contactID ID of the contact. If not provided uses the currently logged in user
     * @return int|null ID of contact or null if no contact was found.
     */
    private function getContactID($contactID = null)
    {
        if (Framework::isValidID($contactID))
            return intval($contactID);
        if (Framework::isLoggedIn())
            return $this->CI->session->getProfileData('contactID');
    }

    /**
     * Return offset of $ID from $notifications.
     *
     * @param string $connectName Either Product, Category, or Answer
     * @param int $ID ID of notification to find
     * @param array|null $notifications List of current notifications
     * @return int|null Offset or null if not found
     */
    private function getOffsetFromID($connectName, $ID, $notifications) {
        $notificationCount = (isset($notifications) && $notifications) ? count($notifications) : 0;
        for ($offset = 0; $offset < $notificationCount; $offset++) {
            if ($ID === $notifications[$offset]->$connectName->ID && $notifications[$offset]->Interface->ID === $this->interfaceID) {
                return $offset;
            }
        }
        return null;
    }

    /**
     * Return product/category hierarchy associated with an answer
     *
     * @param int $answerID ID of answer
     * @return array List of products and categories
     */
    private function getItemsAssociatedWithAnswer($answerID) {
        $itemsAssociatedWithAnswer = array();

        // Use Connect PHP, in case we are dealing with a privileged answer. The KBF API will not return
        // it unless the contact is logged in, which is not required to unsubscribe from a notification.
        $answer = Connect\Answer::fetch($answerID);
        if (!$answer) {
            return $itemsAssociatedWithAnswer;
        }

        foreach (array(HM_PRODUCTS, HM_CATEGORIES) as $type) {
            list ($property, $subProperty) = ($type === HM_CATEGORIES) ?
                array("Categories", "CategoryHierarchy") :
                array("Products", "ProductHierarchy");

            foreach ($answer->{$property} as $hierMenu) {
                $itemsAssociatedWithAnswer[$hierMenu->ID] = $type;
                foreach ($hierMenu->{$subProperty} as $parentHierMenu) {
                    $itemsAssociatedWithAnswer[$parentHierMenu->ID] = $type;
                }
            }
        }
        return $itemsAssociatedWithAnswer;
    }
}
