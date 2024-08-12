<?php
RightNow\UnitTest\Helper::loadTestedFile(__FILE__);

use RightNow\Connect\v1_4 as Connect,
    RightNow\Utils\Framework,
    RightNow\Utils\Text;

class NotificationTest extends CPTestCase {
    public $testingClass = 'RightNow\Models\Notification';

    function __construct() {
        parent::__construct();
        $this->model = new RightNow\Models\Notification();
        $this->contactIDWithNotifications = 1172; //Thanks Dallas!
        $this->expectedNotifications = array(
            'product' => array(130),
            'category' => array(122),
            'answer' => array(48, 49),
        );
        $this->productFilterID = HM_PRODUCTS;
        $this->productID = 129;
        $this->categoryFilterID = HM_CATEGORIES;
        $this->categoryID = 123;
        $this->answerID = 45;

        $this->productNotification = 'RightNow\Connect\v1_4\ProductNotification';
        $this->productNotificationArray = 'RightNow\Connect\v1_4\ProductNotificationArray';
        $this->productNotificationObject = 'object:RightNow\Connect\v1_4\ProductNotificationArray';

        $this->categoryNotification= 'RightNow\Connect\v1_4\CategoryNotification';
        $this->categoryNotificationArray = 'RightNow\Connect\v1_4\CategoryNotificationArray';
        $this->categoryNotificationObject = 'object:RightNow\Connect\v1_4\CategoryNotificationArray';

        $this->answerNotification = 'RightNow\Connect\v1_4\AnswerNotification';
        $this->answerNotificationArray = 'RightNow\Connect\v1_4\AnswerNotificationArray';
        $this->answerNotificationObject = 'object:RightNow\Connect\v1_4\AnswerNotificationArray';
    }


    function tearDown() {
        $contactID = $this->contactIDWithNotifications;
        foreach($this->getNotificationIDs($contactID) as $key => $IDs) {
            foreach($IDs as $ID) {
                if (!in_array($ID, $this->expectedNotifications[$key])) {
                    $response = $this->model->delete($key, $ID, $contactID);
                    $this->assertIsA($response->result, 'array');
                    $this->assertTrue(count($response->result) > 0);
                }
            }
        }

        // Loop through both expected and actual to determine all items accounted for.
        // Note: not just doing a assertIdentical here as the order will change.
        $actual = $this->getNotificationIDs($this->contactIDWithNotifications);
        $expected = $this->expectedNotifications;
        foreach($expected as $notificationType => $IDs) {
            foreach($IDs as $ID) {
                if (!in_array($ID, $actual[$notificationType])) {
                    $this->fail("$notificationType $ID missing");
                }
            }
        }

        foreach($actual as $notificationType => $IDs) {
            foreach($IDs as $ID) {
                if (!in_array($ID, $expected[$notificationType])) {
                    $this->fail("$notificationType $ID not removed");
                }
            }
        }
        parent::tearDown();
    }

    function getNotificationIDs($contactID) {
        $notifications = array();
        foreach($this->model->get('all', $contactID)->result as $notificationType => $objects) {
            $notifications[$notificationType] = array();
            $connectName = ucfirst($notificationType);
            foreach ($objects as $obj) {
                $notifications[$notificationType][] = $obj->$connectName->ID;
            }
        }
        return $notifications;
    }

    function testGetNotificationsForAnswer() {
        $response = $this->model->getNotificationsForAnswer(1, $this->contactIDWithNotifications);
        $this->assertTrue($this->responseIsValid($response));
        $this->assertSame(0, count($response->result));

        $response = $this->model->getNotificationsForAnswer(48, $this->contactIDWithNotifications);
        $this->assertTrue($this->responseIsValid($response));
        $notifications = $response->result;
        $this->assertSame(3, count($notifications));
        $this->assertTrue(is_array($notifications['answer']));
        $this->assertIsA($notifications['answer'][0], $this->answerNotification);
        $this->assertSame(48, $notifications['answer'][0]->Answer->ID);
    }

    function responseIsValid($response, $expectedReturn = 'array', $errorCount = 0, $warningCount = 0) {
        $status = true;
        $expectedType = object;
        $actualType = gettype($response);
        $expectedClass = 'RightNow\Libraries\ResponseObject';
        $actualClass = get_class($response);

        if (($expectedType !== $actualType) || ($expectedClass !== $actualClass)) {
            print("<strong>Expected response to be of type: '$expectedType', class: '$expectedClass'. Got type:'$actualType' class:'$actualClass'.</strong><br/>");
            $status = false;
        }

        list($expectedReturnType, $expectedReturnClass) = explode(':', $expectedReturn);
        $actualReturnType = gettype($response->result);
        $actualReturnClass = null;
        if (($expectedReturnType !== $actualReturnType) || ($expectedReturnClass !== $actualReturnClass)) {
            print("<strong>Expected return to be of type: '$expectedReturnType' class: '$expectedReturnClass'. Got type: '$actualReturnType' class: '$actualReturnClass'.</strong><br/>");
            $status = false;
        }

        if (count($response->errors) !== $errorCount) {
            printf("<strong>Expected %d error(s), got %d</strong><br/>", $errorCount, count($response->errors));
            foreach($response->errors as $error) {
                print("&nbsp;&nbsp;&nbsp;&nbsp;{$error}<br/>");
            }
            $status = false;
        }
        if (count($response->warnings) !== $warningCount) {
            printf("<strong>Expected %d warning(s), got %d</strong><br/>", $warningCount, count($response->warnings));
            foreach($response->warnings as $warning) {
                print("&nbsp;&nbsp;&nbsp;&nbsp;{$warning}<br/>");
            }
            $status = false;
        }
        return $status;
    }

    function testGet() {
        $contactID = $this->contactIDWithNotifications;

        $response = $this->model->get('whatever', $contactID);
        $this->assertTrue($this->responseIsValid($response, 'array', 1));
        $this->assertIdentical("Invalid filter type: 'whatever'", (string) $response->error);

        if (!Framework::isLoggedIn()) {
            $response = $this->model->get(null);
            $this->assertTrue($this->responseIsValid($response, 'array', 1, 0));
            $this->assertIdentical('Unable to determine contact.', (string) $response->error);
        }

        $response = $this->model->get('answer', $contactID);
        $this->assertTrue($this->responseIsValid($response));
        foreach($response->result['answer'] as $notification){
            $this->assertIsA($notification, $this->answerNotification);
            $this->assertTrue(is_int($notification->Answer->ID));
        }

        $response = $this->model->get('product', $contactID);
        $this->assertTrue($this->responseIsValid($response));

        $response = $this->model->get('category', $contactID);
        $this->assertTrue($this->responseIsValid($response));

        $response = $this->model->get('all', $contactID);
        $this->assertTrue($this->responseIsValid($response));
        $notifications = $response->result;
        $this->assertIsA($notifications['answer'], 'array');
        $this->assertIsA($notifications['product'], 'array');
        $this->assertIsA($notifications['category'], 'array');

        $response = $this->model->get(array('product', 'category'), $contactID);
        $this->assertTrue($this->responseIsValid($response));
        $notifications = $response->result;
        $this->assertIsA($notifications['product'], 'array');
        $this->assertIsA($notifications['category'], 'array');

        // Only notifications for public answers should be returned
        $privateAnswerID = 70;
        $publicAnswerNumber1 = 0;
        $publicAnswerNumber2 = 0;
        $updatedAnswer = Connect\Answer::fetch($privateAnswerID);

        $updatedAnswer ->StatusWithType->StatusType->ID = ANS_PUBLIC;
        $this->assertTrue($this->responseIsValid($this->model->add('answer', $privateAnswerID, $contactID)));
        $response = $this->model->get('answer', $contactID)->result;
        foreach ($response['answer'] as $notification) {
            $this->assertTrue($notification->Answer->StatusWithType->StatusType->ID === ANS_PUBLIC);
            $publicAnswerNumber1 ++;
        }

        $updatedAnswer ->StatusWithType->StatusType->ID = ANS_PRIVATE;
        $this->assertTrue($this->responseIsValid($this->model->add('answer', $privateAnswerID, $contactID)));
        $response = $this->model->get('answer', $contactID)->result;
        foreach ($response['answer'] as $notification) {
            $this->assertTrue($notification->Answer->StatusWithType->StatusType->ID === ANS_PUBLIC);
            $publicAnswerNumber2 ++;
        }
        $this->assertTrue($publicAnswerNumber1 === $publicAnswerNumber2 + 1);
    }

    function notificationExists($notificationType, $ID, $contactID) {
        $notifications = $this->model->get($notificationType, $contactID)->result;
        foreach($notifications[strtolower($notificationType)] as $notification) {
            if ($notification->$notificationType->ID === $ID) {
                return true;
            }
        }
        return false;
    }

    function testAdd() {
        $contactID = $this->contactIDWithNotifications;
        
        // PRODUCT
        $this->assertFalse($this->notificationExists('Product', $this->productID, $contactID));
        $response = $this->model->add('product', $this->productID, $contactID);
        $this->assertTrue($this->responseIsValid($response));
        $this->assertTrue($this->notificationExists('Product', $this->productID, $contactID), "Product notification '{$this->productID}' not added");
        $before = $this->getStartTime('Product', $this->productID, $contactID);

        // Adding an existing notification should renew
        \Rnow::updateConfig("ANS_NOTIF_DURATION", 1, true);
        sleep(1);
        $response = $this->model->add('product', $this->productID, $contactID);
        $after = $this->getStartTime('Product', $this->productID, $contactID);
        $this->assertIsA($after, 'int');
        $this->assertNotEqual($before, $after);

        // EXPIRE TIME
        $expireTime = $this->getExpireTime('Product', $this->productID, $contactID);
        if (($duration = \RightNow\Utils\Config::getConfig(ANS_NOTIF_DURATION)) && $duration > 0)
        {
            $this->assertTrue(\RightNow\Utils\Text::stringContains($expireTime, "($duration day"));
        }
        else
        {
            $this->assertIdentical(\RightNow\Utils\Config::getMessage(NO_EXPIRATION_DATE_LBL), $expireTime);
        }

        \Rnow::updateConfig("ANS_NOTIF_DURATION", 0, true);

        // CATEGORY
        $this->assertFalse($this->notificationExists('Category', $this->categoryID, $contactID));
        $response = $this->model->add('category', $this->categoryID, $contactID);
        $this->assertTrue($this->responseIsValid($response, 'array'));
        $this->assertTrue($this->notificationExists('Category', $this->categoryID, $contactID));

        // ANSWER
        //$this->assertFalse($this->notificationExists('Answer', $this->answerID, $contactID));
        $response = $this->model->add('answer', $this->answerID, $contactID);
        $this->assertTrue($this->responseIsValid($response));
        $this->assertTrue($this->notificationExists('Answer', $this->answerID, $contactID));

        // INVALID $filterType
        $response = $this->model->add(99999, $this->productID, $contactID);
        $this->assertTrue($this->responseIsValid($response, 'array', 1));

        // INVALID $ID
        $response = $this->model->add('product', 9999, $contactID);
        $this->assertTrue($this->responseIsValid($response, 'array', 1));

        // INVALID $contactID
        $response = $this->model->add('product', $this->productID, 999999999999);
        //$this->assertTrue($this->responseIsValid($response, 'array', 0, 1));
    }

    function testDelete() {
        $contactID = $this->contactIDWithNotifications;

        // INVALID notification type
        $response = $this->model->delete('whatever', $this->productID, $contactID);
        $this->assertTrue($this->responseIsValid($response, 'array', 1, 0));

        // INVALID ID
        $response = $this->model->delete('product', 999999, $contactID);
        $this->assertTrue($this->responseIsValid($response, 'array', 0, 1));

        // INVALID $contactID
        $response = $this->model->delete('product', $this->productID, 99999999);
        $this->assertTrue($this->responseIsValid($response, 'array', 1, 0));
        $this->assertIsA($response->result, 'array');
        $this->assertSame(0, count($response->result));

        // Successful deletes being verified from tearDown()
    }

    function testDeleteAll() {
        // INVALID $contactID
        $response = $this->model->deleteAll('product', 99999999);
        $this->assertTrue($this->responseIsValid($response, 'array', 1, 0));
        $this->assertIsA($response->result, 'array');
        $this->assertSame(0, count($response->result));

        // Answers
        $contactID = $this->contactIDWithNotifications;
        $before = $this->getNotificationIDs($contactID);
        $response = $this->model->deleteAll('answer', $contactID);
        $this->assertTrue($this->responseIsValid($response));
        $after = $this->getNotificationIDs($contactID);
        foreach($before['answer'] as $ID) {
            $this->model->add('answer', $ID, $contactID);
        }
        $this->assertIdentical(0, count($after['answer']));

        // Products
        $response = $this->model->deleteAll('product', $contactID);
        $this->assertTrue($this->responseIsValid($response));
        $after = $this->getNotificationIDs($contactID);
        foreach($before['product'] as $ID) {
            $this->model->add('product', $ID, $contactID);
        }
        $this->assertIdentical(0, count($after['product']));

        // Everything
        $this->model->deleteAll('product', $contactID);
        $this->model->deleteAll('category', $contactID);
        $this->model->deleteAll('answer', $contactID);
        $after = $this->getNotificationIDs($contactID);
        foreach($before as $type => $Ids) {
            foreach($Ids as $ID) {
                $this->model->add($type, $ID, $contactID);
            }
        }
        $this->assertIdentical(0, count($after));
    }


    function getStartTime($notificationType, $ID, $contactID) {
        foreach($this->model->get($notificationType, $contactID)->result[strtolower($notificationType)] as $notification) {
            if ($notification->$notificationType->ID === $ID) {
                return $notification->StartTime;
            }
        }
    }

    function getExpireTime($notificationType, $ID, $contactID) {
        foreach($this->model->get($notificationType, $contactID)->result[strtolower($notificationType)] as $notification) {
            if ($notification->$notificationType->ID === $ID) {
                return $notification->ExpireTime;
            }
        }
    }

    function testRenew() {
        $contactID = $this->contactIDWithNotifications;
        $productID = $this->productID;

        // INVALID notification type
        $response = $this->model->renew('whatever', $productID, $contactID);
        $this->assertTrue($this->responseIsValid($response, 'array', 1));

        // INVALID $ID
        $response = $this->model->renew('product', 99999999, $contactID);
        $this->assertTrue($this->responseIsValid($response, 'array', 0, 1));

        // INVALID $contactID
        $response = $this->model->renew('product', $productID, 999999);
        $this->assertTrue($this->responseIsValid($response, 'array', 1, 0));

        // PRODUCT
        $response = $this->model->add('product', $this->productID, $contactID);
        $before = $this->getStartTime('Product', $this->productID, $contactID);
        $this->assertIsA($before, 'int');

        sleep(1);
        $response = $this->model->renew('product', $this->productID, $contactID);
        $this->assertTrue($this->responseIsValid($response, 'array'));
        $after = $this->getStartTime('Product', $this->productID, $contactID);
        $this->assertIsA($after, 'int');
        $this->assertNotEqual($before, $after);


        // ANSWER
        $answerID = $this->answerID;
        $response = $this->model->add('answer', $answerID, $contactID);
        $before = $this->getStartTime('Answer', $answerID, $contactID);
        $this->assertIsA($before, 'int');

        sleep(1);
        $response = $this->model->renew('answer', $answerID, $contactID);
        $this->assertTrue($this->responseIsValid($response));
        $after = $this->getStartTime('Answer', $answerID, $contactID);
        $this->assertIsA($after, 'int');

        // EXPIRE TIME
        \Rnow::updateConfig("ANS_NOTIF_DURATION", 1, true);
        $response = $this->model->renew('answer', $answerID, $contactID);
        $expireTime = $this->getExpireTime('Answer', $answerID, $contactID);

        if (($duration = \RightNow\Utils\Config::getConfig(ANS_NOTIF_DURATION)) && $duration > 0)
        {
            $this->assertTrue(\RightNow\Utils\Text::stringContains($expireTime, "($duration day"));
        }
        else
        {
            $this->assertIdentical(\RightNow\Utils\Config::getMessage(NO_EXPIRATION_DATE_LBL), $expireTime);
        }

        \Rnow::updateConfig("ANS_NOTIF_DURATION", 0, true);
    }

    function testGetContactID() {
        $method = $this->getMethod('getContactID');
        $this->assertIdentical(1, $method(1));
        $this->assertIdentical(1, $method('1'));
        if (Framework::isLoggedIn()) {
            $this->assertIsA($method(), 'integer');
        }
        else {
            $this->assertNull($method());
        }
    }

    function testGetNotificationsByType() {
        $getNotificationsByType = $this->getMethod('getNotificationsByType');
        $contactID = $this->contactIDWithNotifications;

        $response = $getNotificationsByType('all', $contactID);
        $this->assertTrue($this->responseIsValid($response));
        $notifications = $response->result;
        $this->assertIsA($notifications['answer'], $this->answerNotificationArray);
        $this->assertIsA($notifications['product'], $this->productNotificationArray);
        $this->assertIsA($notifications['category'], $this->categoryNotificationArray);

        $response = $getNotificationsByType(array('product', 'category'), $contactID);
        $this->assertTrue($this->responseIsValid($response));
        $notifications = $response->result;
        $this->assertIsA($notifications['product'], $this->productNotificationArray);
        $this->assertIsA($notifications['category'], $this->categoryNotificationArray);
    }

    function testGetNotificationType() {
        $getNotificationType = $this->getMethod('getNotificationType');
        $this->assertIdentical('Product', $getNotificationType('prod'));
        $this->assertIdentical('Product', $getNotificationType('product'));
        $this->assertIdentical('Product', $getNotificationType(HM_PRODUCTS));
        $this->assertIdentical(HM_PRODUCTS, $getNotificationType('prod', 'id'));
        $this->assertIdentical('Category', $getNotificationType('cat'));
        $this->assertIdentical('Category', $getNotificationType('category'));
        $this->assertIdentical('Category', $getNotificationType(HM_CATEGORIES));
        $this->assertIdentical(HM_CATEGORIES, $getNotificationType('cat', 'id'));

        $this->assertIdentical('Answer', $getNotificationType('answer', 'id'));
        $this->assertIdentical('Answer', $getNotificationType('answer', 'name'));
    }

    function testGetReturn(){
        $getReturn = $this->getMethod('getReturn');

        $contactID = $this->contactIDWithNotifications;

        $response = $getReturn('ProductAndCategory', $contactID);
        $this->assertIsA($response['product'], 'array');
        $this->assertIsA($response['category'], 'array');

        $response = $getReturn(null, $contactID);
        $this->assertIsA($response['product'], 'array');
        $this->assertIsA($response['category'], 'array');

        $response = $getReturn('answer', $contactID);
        $this->assertIsA($response['product'], 'array');
        $this->assertIsA($response['category'], 'array');

        $response = $getReturn('Answer', $contactID);
        foreach($response['answer'] as $notification){
            $this->assertIsA($notification, $this->answerNotification);
            $this->assertTrue(is_int($notification->Answer->ID));
        }
    }

    function testGetExpiration(){
        $getExpiration = $this->getMethod('getExpiration');

        $this->assertTrue(is_string($getExpiration()));

        \Rnow::updateConfig("ANS_NOTIF_DURATION", 1, true);
        $this->assertTrue(Text::stringContains($getExpiration(), "1 day"));
        $this->assertTrue(Text::stringContains($getExpiration(time() - (24 * 60 * 60)), "0 days"));
        $this->assertTrue(Text::stringContains($getExpiration(time() - (24 * 60 * 60 * 2)), "-1 days"));

        \Rnow::updateConfig("ANS_NOTIF_DURATION", 7, true);
        $this->assertTrue(Text::stringContains($getExpiration(), "7 day"));
        $this->assertTrue(Text::stringContains($getExpiration(time() - (24 * 60 * 60)), "6 days"));
        $this->assertTrue(Text::stringContains($getExpiration(time() - (24 * 60 * 60 * 7)), "0 days"));

        \Rnow::updateConfig("ANS_NOTIF_DURATION", 0, true);
    }

    function testGetContactObject(){
        $getContactObject = $this->getMethod('getContactObject');

        $response = $getContactObject(1268);
        $this->assertIsA($response, 'RightNow\Connect\v1_4\Contact');
        $this->assertIdentical(1268, $response->ID);

        $response = $getContactObject("1268");
        $this->assertIsA($response, 'RightNow\Connect\v1_4\Contact');
        $this->assertIdentical(1268, $response->ID);
    }

    function testPrepareUpdate(){
        $prepareUpdate = $this->getMethod('prepareUpdate');

        try{
            $prepareUpdate('test', 'category', 1, 1);
            $this->fail('Only "add", "renew" or "delete" are allowed.');
        }
        catch(\Exception $e){
            $this->pass();
        }

        $this->setIsAbuse();
        $expected = \RightNow\Utils\Config::getMessage(REQUEST_PERFORMED_SITE_ABUSIVE_MSG);
        list($response,) = $prepareUpdate('add', 'answer', 1, 1268);
        $this->assertEqual($expected, $response->error);
        $this->clearIsAbuse();

        $response = $prepareUpdate('add', 'answer', 1, 1268);
        $this->assertIsA($response[0], 'RightNow\Libraries\ResponseObject');
        $this->assertTrue(is_array($response[1]));
        $this->assertIdentical(1268, $response[1]['contact_id']);
        $this->assertIsA($response[1]['contact'], 'RightNow\Connect\v1_4\Contact');
        $this->assertIdentical(1268, $response[1]['contact']->ID);
        $this->assertNull($response[1]['offset']);
        $this->assertTrue(is_object($response[1]['notifications']));
        $this->assertIdentical('Answer', $response[1]['notification_type']);

        $response = $prepareUpdate('renew', 'answer', 1, 1268);
        $this->assertIdentical(1, count($response[0]->warnings));
        $this->assertIdentical('Answer', $response[1]['notification_type']);
        $this->assertNull($response[1]['offset']);

        $response = $prepareUpdate('delete', 'answer', 1, 1268);
        $this->assertIdentical(1, count($response[0]->warnings));
        $this->assertIdentical('Answer', $response[1]['notification_type']);
        $this->assertNull($response[1]['offset']);

        $response = $prepareUpdate('delete', 'answer', 'ALL', 1268);
        $this->assertIdentical(0, count($response[0]->warnings));
        $this->assertIdentical('Answer', $response[1]['notification_type']);
        $this->assertNull($response[1]['offset']);
        $this->assertTrue(is_object($response[1]['notifications']));
    }

    function testGetOffsetFromID(){
        $getOffsetFromID = $this->getMethod('getOffsetFromID');

        $this->assertNull($getOffsetFromID(null, null, array()));

        $notifications = array((object)array(
            'Product' => (object)array(
                'ID' => 12
            ),
            'Interface' => (object)array(
                'ID' => 1
            )
        ));
        $this->assertIdentical(0, $getOffsetFromID('Product', 12, $notifications));

        $notifications[] = (object)array(
            'Product' => (object)array(
                'ID' => 25
            ),
            'Interface' => (object)array(
                'ID' => 1
            )
        );
        $this->assertIdentical(1, $getOffsetFromID('Product', 25, $notifications));

        $notifications[1]->Interface->ID = 2;
        $this->assertNull($getOffsetFromID('Product', 25, $notifications));
    }

    function testGetItemsAssociatedWithAnswer(){
        $getItemsAssociatedWithAnswer = $this->getMethod('getItemsAssociatedWithAnswer');

        try{
            $getItemsAssociatedWithAnswer(null);
            $this->fail('Exception should be thrown for non valid answer ID');
        }
        catch(\Exception $e){
            $this->pass();
        }
        $response = $getItemsAssociatedWithAnswer(1);
        $this->assertIdentical(array(7 => 13, 68 => 14, 70 => 14), $response);

        $response = $getItemsAssociatedWithAnswer(52);
        $this->assertTrue(is_array($response));
        foreach($response as $type){
            $this->assertTrue($type === HM_PRODUCTS || $type === HM_CATEGORIES);
        }
    }
}
