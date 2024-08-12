<?php
use RightNow\Connect\v1_4 as Connect,
    RightNow\Utils\Text,
    RightNow\Utils\Framework,
    RightNow\Utils\Connect as ConnectUtil,
    RightNow\UnitTest\Helper as TestHelper;

TestHelper::loadTestedFile(__FILE__);

class AssetModelTest extends CPTestCase {
    public $testingClass = 'RightNow\Models\Asset';

    function __construct() {
        parent::__construct();
        $this->assetID = 3;
        $this->user1 = (object) array(
            'login' => 'jerry@indigenous.example.com.invalid.070503.invalid',
            'email' => 'jerry@indigenous.example.com.invalid',
        );

        $this->user2 = (object) array(
            'login' => 'th@nway.xom.invalid.060804.060630.060523.invalid.060804.060630.invalid.060804.in',
            'email' => 'th@nway.xom.invalid',
        );
        $this->maxLength = 240;
        $this->model = $this->CI->model('Asset');
    }

    function verifyResponse($response, $expectedReturn = 'object:RightNow\Connect\v1_4\Asset', $errorCount = 0, $warningCount = 0) {
        $expectedType = object;
        $actualType = gettype($response);
        $expectedClass = 'RightNow\Libraries\ResponseObject';
        $actualClass = get_class($response);

        if (($expectedType !== $actualType) || ($expectedClass !== $actualClass)) {
            print("<strong>Expected response to be of type: '$expectedType', class: '$expectedClass'. Got type:'$actualType' class:'$actualClass'.</strong><br/>");
            $this->assertTrue(false);
        }

        list($expectedReturnType, $expectedReturnClass) = explode(':', $expectedReturn);
        $actualReturnType = gettype($response->result);
        $actualReturnClass = get_class($response->result) ?: null;
        if (($expectedReturnType !== $actualReturnType) || ($expectedReturnClass !== $actualReturnClass)) {
            print("<strong>Expected return to be of type: '$expectedReturnType' class: '$expectedReturnClass'. Got type: '$actualReturnType' class: '$actualReturnClass'.</strong><br/>");
            $this->assertTrue(false);
        }

        if (count($response->errors) !== $errorCount) {
            printf("<strong>Expected %d error(s), got %d</strong><br/>", $errorCount, count($response->errors));
            foreach($response->errors as $error) {
                print("&nbsp;&nbsp;&nbsp;&nbsp;{$error}<br/>");
            }
            $this->assertTrue(false);
        }
        if (count($response->warnings) !== $warningCount) {
             printf("<strong>Expected %d warning(s), got %d</strong><br/>", $warningCount, count($response->warnings));
            foreach($response->warnings as $warning) {
                print("&nbsp;&nbsp;&nbsp;&nbsp;{$warning}<br/>");
            }
            $this->assertTrue(false);
        }
    }

    function testBlank() {
        $response = $this->model->getBlank();
        $this->assertIsA($response, 'RightNow\Libraries\ResponseObject');
        $this->assertTrue(is_object($response->result));
        $this->assertSame(0, count($response->errors));
        $this->assertSame(0, count($response->warnings));
        $this->assertTrue(is_object($response->result));
    }

    function testInvalidAssetID() {
        $this->logIn();
        $invalidIDs = array(null, false, '', 0, 'abc', 'null', 'false');
        foreach($invalidIDs as $ID) {
            $response = $this->model->get($ID);
            $this->assertIdentical($response->errors[0]->externalMessage, "Invalid Asset ID: $ID");
            $this->assertNull($response->result);
        }
        $this->logOut();
    }

    function testGet() {
        //Contact and Org associated to an asset, but contact not logged it
        $response = $this->model->get($this->assetID);
        $this->assertIdentical($response->errors[0]->externalMessage, 'Your session has expired. Please login again to continue.');

        //Asset belongs to logged in contact
        $this->logIn($this->user2->login);
        $response = $this->model->get(3);
        $this->verifyResponse($response);
        $this->logOut();

        //Asset does not belong to logged in contact
        $this->logIn($this->user1->login);
        $response = $this->model->get(3);
        $this->assertIdentical($response->errors[0]->externalMessage, 'Access Denied.');
        $this->logOut();

        //No contact, No Org and contact is not logged in
        $response = $this->model->get(7);
        $this->assertIdentical($response->errors[0]->externalMessage, 'Your session has expired. Please login again to continue.');
    }

    //@@@ QA 121210-000181 RightNow A&C: End User Registers a Non-Serialized Product (Asset)
    function testCreate() {
        $this->logIn($this->user1->login);

        $assetName = str_pad('OnlyServiceVisible', $this->maxLength, 'OnlyServiceVisible');
        $response = $this->model->create(20, array(
            'Asset.Name' => (object) array('value' => 'OnlyServiceVisible'),
            'Asset.Product' => (object) array('value' =>20),
            'Asset.StatusWithType.Status' => (object) array('value' =>26),
        ));

        $this->assertIsA($response, 'RightNow\Libraries\ResponseObject');
        $this->assertSame(0, count($response->errors));
        $this->assertIsA($response->result, CONNECT_NAMESPACE_PREFIX . '\Asset');
        $this->assertIdentical('jerry@indigenous.example.com.invalid', $response->result->Contact->Emails[0]->Address);
        $this->assertNotNull($response->result->Product->ID);
        $this->assertIdentical('OnlyServiceVisible', $response->result->Name);
        $this->assertIdentical(26, $response->result->StatusWithType->Status->ID);

        $this->logout();
    }


    //@@@ QA 140410-000129 Assets - CP - CP_LOGIN_MAX_TIME set to 1, displays different warning messages in different CP pages
    function testCreateAssetWithTimeOut() {
        $response = $this->model->create(20, array(
            'Asset.Name' => (object) array('value' => 'OnlyServiceVisible'),
            'Asset.Product' => (object) array('value' =>20),
            'Asset.StatusWithType.Status' => (object) array('value' =>26),
        ));

        $this->assertIdentical($response->errors[0]->externalMessage, 'Your product cannot be registered. Please log in and try again.');
    }

    //@@@ QA 140410-000129 Assets - CP - CP_LOGIN_MAX_TIME set to 1, displays different warning messages in different CP pages
    function testUpdateAssetWithTimeOut() {
        $response = $this->model->update(8, array(
            'Asset.Name' => (object) array('value' => 'MF4770 Canon Laser Jet Printer Registered'),
            'Asset.StatusWithType.Status' => (object) array('value' => 27),
        ));

        $this->assertIdentical($response->errors[0]->externalMessage, 'Your registered product cannot be updated. Please log in and try again.');

        $response = $this->model->update(8, array(
            'Asset.Name' => (object) array('value' => 'MF4770 Canon Laser Jet Printer Registered'),
            'Asset.StatusWithType.Status' => (object) array('value' => 27),
        ), "1234");

        $this->assertIdentical($response->errors[0]->externalMessage, 'Your product cannot be registered. Please log in and try again.');
    }

    //@@@ QA 131107-000003 E2E CP: Asset: CP- User can create an asset for a serialized product without entering the serial number from CP page
    function testRegisterNonSerializedInvalidProduct() {
        $this->logIn($this->user1->login);

        $assetName = str_pad('1yr 400 Unlimited', $this->maxLength, '1yr 400 Unlimited');
        $response = $this->model->create(1, array(
            'Asset.Name' => (object) array('value' => '1yr 400 Unlimited'),
            'Asset.Product' => (object) array('value' =>1),
            'Asset.StatusWithType.Status' => (object) array('value' =>26),
            'Asset.SerialNumber' => (object) array('value' => '1yr400Unlimited'),
        ));

        $this->assertIsA($response, 'RightNow\Libraries\ResponseObject');
        $this->assertSame(1, count($response->errors));
        $this->assertIdentical($response->errors[0]->externalMessage, "Invalid ID: No such Sales Product");

        $this->logout();
    }

    //@@@ QA 121210-000180 RightNow A&C: End User Registers a Serialized Product (Asset)
    function testUpdate() {
        $this->logIn($this->user1->login);
        $return = $this->model->update('asdf', array());
        $this->assertIsA($return, 'RightNow\Libraries\ResponseObject');
        $this->assertSame(1, count($return->errors));

        //Trying to update an asset that does not belong to the logged in user
        $return = $this->model->update(1, array());
        $this->assertIsA($return, 'RightNow\Libraries\ResponseObject');
        $this->assertSame(1, count($return->errors));
        $this->assertIdentical($return->errors[0]->externalMessage, "Access Denied.");

        $return = $this->model->update(1, array(), "123455");
        $this->assertIsA($return, 'RightNow\Libraries\ResponseObject');
        $this->assertSame(1, count($return->errors));
        $this->assertIdentical($return->errors[0]->externalMessage, "Access Denied.");

        //Trying to update an asset that belongs to the logged in user
        $return = $this->model->update(8, array(
            'Asset.Name' => (object) array('value' => 'MF4770 Canon Laser Jet Printer Registered'),
            'Asset.StatusWithType.Status' => (object) array('value' => 27),
        ));

        $this->assertIsA($return, 'RightNow\Libraries\ResponseObject');
        $this->assertSame(0, count($return->errors));

        $this->assertIsA($return->result, CONNECT_NAMESPACE_PREFIX . '\Asset');
        $asset = $return->result;
        $this->assertIdentical(27, $asset->StatusWithType->Status->ID);

        $this->logout();
    }

    //@@@ QA 130721-000006 RightNow A&C: Uptake the Asset StatusWithType API from MAPI
    function testSetFieldValue(){
        $setFieldValue = $this->getMethod('setFieldValue');
        $mockAsset = $this->model->getBlank()->result;

        $setFieldValue($mockAsset, 'Asset.StatusWithType.Status', 26, null);
        $this->assertIdentical(26,$mockAsset->StatusWithType->Status->ID);

        $setFieldValue($mockAsset, 'Asset.Product', 1, null);
        $this->assertIdentical(1,$mockAsset->Product->ID);
    }

    //@@@ QA 121210-000180 RightNow A&C: End User Registers a Serialized Product (Asset)
    function testValidateSerialNumber() {
        $this->logIn($this->user1->login);
        $response = $this->model->validateSerialNumber('GS20104022338', 5);
        $this->assertFalse($response->result);
        $this->logout();
    }

    //@@@ QA 131105-000004 RightNow A&C: Remove the filter on the Asset Status so all Status are visible in CP.
    function testGetAssetStatuses() {
        $this->logIn($this->user1->login);
        $response = $this->model->getAssetStatuses();
        $this->assertTrue(is_array($response->result));
        $this->logout();
    }

    //@@@ QA 120820-000111 RightNow A&C: End User Manually Associates Existing Registered Product (Asset) to Incident
    function testGetAssets() {
        $this->logIn($this->user1->login);
        $response = $this->model->getAssets();
        $this->assertTrue(is_array($response->result));
        $this->logout();

        //@@@ QA 140124-000085 Assets - CP - Unregistered asset displayed under registered products dorpdown in AAQ page
        $this->logIn($this->user2->login);
        $assets = $this->model->getAssets()->result;
        $assetIDs = array();
        foreach ($assets as $asset) {
            $assetIDs[] = $asset->ID;
        }

        $this->assertSame(0, count(array_diff($assetIDs, array(13, 14, 2, 3))));
        $this->logout();
    }

    //@@@ QA 130721-000006 RightNow A&C: Uptake the Asset StatusWithType API from MAPI
    function testIsContactAllowedToReadAsset() {
        $isContactAllowedToReadAsset = $this->getMethod('isContactAllowedToReadAsset');

        $this->logIn($this->user1->login);
        $response = $this->model->get(222);    //Invalid Asset ID
        $this->assertIdentical($response->errors[0]->externalMessage, 'Invalid ID: No such Asset with ID = 222');
        $this->logOut();

        //Contact not logged in
        $response = $this->model->get(3);
        $this->assertIdentical($response->errors[0]->externalMessage, 'Your session has expired. Please login again to continue.');

        //Asset belongs to logged in contact
        $this->logIn($this->user2->login);
        $response = $this->model->get(3);
        $this->verifyResponse($response);
        $this->logOut();

        //Asset does not belong to logged in contact
        $this->logIn($this->user1->login);
        $response = $this->model->get(3);
        $this->assertIdentical($response->errors[0]->externalMessage, 'Access Denied.');
        $this->logOut();

        //Contact not logged in
        $response = $this->model->get(8);
        $this->assertIdentical($response->errors[0]->externalMessage, 'Your session has expired. Please login again to continue.');

        //Asset does not belong to logged in contact
        $this->logIn($this->user2->login);
        $response = $this->model->get(8);
        $this->assertIdentical($response->errors[0]->externalMessage, 'Access Denied.');
        $this->logOut();

        //Asset belongs to logged in contact
        $this->logIn($this->user1->login);
        $response = $this->model->get(8);
        $this->verifyResponse($response);
        $this->logOut();

        //Contact not logged in and No Contact and Org associated with Asset
        $response = $this->model->get(7);
        $this->assertIdentical($response->errors[0]->externalMessage, 'Your session has expired. Please login again to continue.');

        //No Contact and Org associated with Asset
        $this->logIn($this->user2->login);
        $response = $this->model->get(7);
        $this->verifyResponse($response);
        $this->logOut();

        //No Contact and Org associated with Asset
        $this->logIn($this->user1->login);
        $response = $this->model->get(7);
        $this->verifyResponse($response);
        $this->logOut();
    }

    //@@@ QA 130821-000080 RightNow A&C: Read only view of any Asset that belong to end user's organization or its subsidiaries in CP and ability to submit a question.
    function testIsContactAllowedToUpdateAsset() {
        $this->logIn($this->user1->login);
        $response = $this->model->isContactAllowedToUpdateAsset(222);    //Invalid Asset ID
        $this->assertFalse($response);
        $this->logOut();

        //Contact not logged in
        $response = $this->model->isContactAllowedToUpdateAsset(3);
        $this->assertFalse($response);

        //Asset belongs to logged in contact
        $this->logIn($this->user2->login);
        $response = $this->model->isContactAllowedToUpdateAsset(3);
        $this->assertTrue($response);
        $this->logOut();

        //Asset does not belong to logged in contact
        $this->logIn($this->user1->login);
        $response = $this->model->isContactAllowedToUpdateAsset(3);
        $this->assertFalse($response);
        $this->logOut();

        //Contact not logged in
        $response = $this->model->isContactAllowedToUpdateAsset(8);
        $this->assertFalse($response);

        //Asset does not belong to logged in contact
        $this->logIn($this->user2->login);
        $response = $this->model->isContactAllowedToUpdateAsset(8);
        $this->assertFalse($response);
        $this->logOut();

        //Asset belongs to logged in contact
        $this->logIn($this->user1->login);
        $response = $this->model->isContactAllowedToUpdateAsset(8);
        $this->assertTrue($response);
        $this->logOut();

        //Contact not logged in and No Contact and Org associated with Asset
        $response = $this->model->isContactAllowedToUpdateAsset(7);
        $this->assertFalse($response);

        //No Contact and Org associated with Asset
        $this->logIn($this->user2->login);
        $response = $this->model->isContactAllowedToUpdateAsset(7);
        $this->assertFalse($response);
        $this->logOut();

        //No Contact and Org associated with Asset
        $this->logIn($this->user1->login);
        $response = $this->model->isContactAllowedToUpdateAsset(7);
        $this->assertFalse($response);
        $this->logOut();
    }

    //@@@ QA 140124-000083 Assets - CP - cannot register the 2nd asset from CP pages when 2 assets have same serial number
    function testAllowRegistrationOfProductsWithSameSerialNumber() {
        $this->logIn($this->user1->login);

        $response = $this->model->validateSerialNumber('1234', 14);
        $this->assertSame(11, $response->result);

        $return = $this->model->update(11, array(
            'Asset.Name' => (object) array('value' => 'Casio G-Shock 1'),
            'Asset.StatusWithType.Status' => (object) array('value' => 26),
        ),'1234');

        $response = $this->model->validateSerialNumber('1234', 14);
        $this->assertSame(12, $response->result);

        //Reset the data
        $asset = $this->model->get(11)->result;
        $asset->StatusWithType->Status->ID = 28;
        $asset->Contact = null;
        $asset->Organization = null;
        ConnectUtil::save($asset, SRC2_EU_ASSET);

        $this->logout();
    }
}



