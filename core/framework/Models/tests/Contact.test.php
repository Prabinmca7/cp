<?php
use RightNow\Api,
    RightNow\Connect\v1_4 as Connect,
    RightNow\Utils\Connect as ConnectUtil,
    RightNow\Utils\Text,
    RightNow\Utils\Config,
    RightNow\UnitTest\Helper as TestHelper,
    RightNow\UnitTest\Fixture;

TestHelper::loadTestedFile(__FILE__);

class ContactModelTest extends CPTestCase {
    public $testingClass = 'RightNow\Models\Contact';
    protected $hookEndpointClass = __CLASS__;
    protected $hookEndpointFilePath = __FILE__;
    protected $model;
    protected $originalConfigValue;

    function __construct()
    {
        parent::__construct();
        $this->model = new RightNow\Models\Contact();
    }

    function setUp()
    {
        $this->originalConfigValue = $this->getDuplicateEmailSetting();
        $this->fixtureInstance = new Fixture();
        parent::setUp();
    }

    function tearDown()
    {
        $this->setDuplicateEmailSetting($this->originalConfigValue);
        $this->fixtureInstance->destroy();
        parent::tearDown();
    }

    function testBlank() {
        $response = $this->model->getBlank();
        $this->assertIsA($response, 'RightNow\Libraries\ResponseObject');
        $this->assertTrue(is_object($response->result));
        $this->assertSame(0, count($response->errors));
        $this->assertSame(0, count($response->warnings));
        $this->assertTrue(is_object($response->result));
        $this->assertTrue(is_object($response->result->CustomFields));
        $this->assertTrue(is_object($response->result->CustomFields->c));
        $this->assertSame($response->result->CustomFields->c->pets_name, 'Fred');
    }

    function testInvalidGet() {
        $response = $this->model->get('sdf');
        $this->assertIsA($response, 'RightNow\Libraries\ResponseObject');
        $this->assertNull($response->result);
        $this->assertSame(1, count($response->errors));

        $response = $this->model->get(null);
        $this->assertIsA($response, 'RightNow\Libraries\ResponseObject');
        $this->assertNull($response->result);
        $this->assertSame(1, count($response->errors));

        $response = $this->model->get("abc123");
        $this->assertIsA($response, 'RightNow\Libraries\ResponseObject');
        $this->assertNull($response->result);
        $this->assertSame(1, count($response->errors));

        $response = $this->model->get(456334);
        $this->assertIsA($response, 'RightNow\Libraries\ResponseObject');
        $this->assertNull($response->result);
        $this->assertSame(1, count($response->errors));
    }

    function testValidGet() {
        $response = $this->model->get(1);
        $this->assertIsA($response, 'RightNow\Libraries\ResponseObject');
        $this->assertTrue(is_object($response->result));
        $this->assertSame(0, count($response->errors));
        $this->assertSame(0, count($response->warnings));
        $contact = $response->result;
        $this->assertIsA($contact, CONNECT_NAMESPACE_PREFIX . '\Contact');
        $this->assertSame(1, $contact->ID);
        $this->assertNull($contact->CustomFields->c->pets_name);

        $response = $this->model->get("1");
        $this->assertIsA($response, 'RightNow\Libraries\ResponseObject');
        $this->assertTrue(is_object($response->result));
        $this->assertSame(0, count($response->errors));
        $this->assertSame(0, count($response->warnings));
        $contact = $response->result;
        $this->assertIsA($contact, CONNECT_NAMESPACE_PREFIX . '\Contact');
        $this->assertSame(1, $contact->ID);
    }

    function testInvalidUpdate() {
        $response = $this->model->update('sdf', array());
        $this->assertIsA($response, 'RightNow\Libraries\ResponseObject');
        $this->assertNull($response->result);
        $this->assertSame(1, count($response->errors));

        $response = $this->model->update(null, array());
        $this->assertIsA($response, 'RightNow\Libraries\ResponseObject');
        $this->assertNull($response->result);
        $this->assertSame(1, count($response->errors));

        $response = $this->model->update("abc123", array());
        $this->assertIsA($response, 'RightNow\Libraries\ResponseObject');
        $this->assertNull($response->result);
        $this->assertSame(1, count($response->errors));

        $response = $this->model->update(456334, array());
        $this->assertIsA($response, 'RightNow\Libraries\ResponseObject');
        $this->assertNull($response->result);
        $this->assertSame(1, count($response->errors));

        $response = $this->model->update(1, array(
             'Contact.Login' => (object) array('value' => 'eturner@rightnow.com.invalid'),
         ));
         $this->assertIsA($response, 'RightNow\Libraries\ResponseObject');
         $this->assertNull($response->result);
         $this->assertIdentical(1, count($response->errors));
    }

    function testValidUpdate() {
        $this->setMockSession();

        // Test for CommunityUser
        $user = $this->fixtureInstance->make('UserActive1');
        $login = 'banana' . microtime(true);
        $response = $this->model->update($user->Contact->ID, array(
            'Contact.Login' => (object) array('value' => $login),
        ));

        $this->assertIsA($response, 'RightNow\Libraries\ResponseObject');
        $this->assertTrue(is_object($response->result));
        $this->assertSame(0, count($response->errors));
        $this->assertSame(0, count($response->warnings));
        $contact = $response->result;
        $this->assertIsA($contact, CONNECT_NAMESPACE_PREFIX . '\Contact');
        $this->assertSame($user->Contact->ID, $contact->ID);
        $this->assertSame($login, $contact->Login);
        $this->fixtureInstance->destroy();

        $login = 'banana' . microtime(true);
        $response = $this->model->update(1, array(
            'Contact.Login' => (object) array('value' => $login)
        ));
        $this->assertIsA($response, 'RightNow\Libraries\ResponseObject');
        $this->assertTrue(is_object($response->result));
        $this->assertSame(0, count($response->errors));
        $this->assertSame(0, count($response->warnings));
        $contact = $response->result;
        $this->assertIsA($contact, CONNECT_NAMESPACE_PREFIX . '\Contact');
        $this->assertSame(1, $contact->ID);
        $this->assertSame($login, $contact->Login);

        $contact = Connect\Contact::fetch(1);
        $contact->Emails[0]->Address = 'banana@banana.com';
        $contact->save();
        $response = $this->model->update("1", array(
            'Contact.Emails.0.Address' => (object) array('value' => 'foo@bar.com'),
            'Contact.Disabled' => (object) array('value' => 'true'),
            'Contact.Address.Country' => (object) array('value' => '1'),
        ));
        $this->assertIsA($response, 'RightNow\Libraries\ResponseObject');
        $this->assertTrue(is_object($response->result));
        $this->assertSame(0, count($response->errors));
        $this->assertSame(0, count($response->warnings));
        $contact = $response->result;
        $this->assertIsA($contact, CONNECT_NAMESPACE_PREFIX . '\Contact');
        $this->assertSame(1, $contact->ID);
        $this->assertTrue($contact->Disabled);
        $this->assertSame('foo@bar.com', $contact->Emails[0]->Address);
        $this->assertSame(1, $contact->Address->Country->ID);

        // OpenLogin update is allowed to enable a contact
        $response = $this->model->update("1", array(
            'Contact.Disabled' => (object) array('value' => false),
        ), true);
        $this->assertIsA($response, 'RightNow\Libraries\ResponseObject');
        $this->assertTrue(is_object($response->result));
        $this->assertSame(0, count($response->errors));
        $this->assertSame(0, count($response->warnings));
        $contact = $response->result;
        $this->assertIsA($contact, CONNECT_NAMESPACE_PREFIX . '\Contact');
        $this->assertFalse($contact->Disabled);

        $dateToSave = date('Y-m-d');
        $response = $this->model->update("1", array(
            'Contact.CustomFields.c.birthday' => (object) array('value' => $dateToSave),
        ));
        $this->assertIsA($response, 'RightNow\Libraries\ResponseObject');
        $this->assertTrue(is_object($response->result));
        $this->assertSame(0, count($response->errors));
        $this->assertSame(0, count($response->warnings));
        $contact = $response->result;
        $this->assertIsA($contact, CONNECT_NAMESPACE_PREFIX . '\Contact');
        $this->assertSame($dateToSave, $contact->CustomFields->c->birthday);

        $dateToSave = time();
        $response = $this->model->update("1", array(
            'Contact.CustomFields.c.birthday' => (object) array('value' => ''),
        ));
        $this->assertIsA($response, 'RightNow\Libraries\ResponseObject');
        $this->assertTrue(is_object($response->result));
        $this->assertSame(0, count($response->errors));
        $this->assertSame(0, count($response->warnings));
        $contact = $response->result;
        $this->assertIsA($contact, CONNECT_NAMESPACE_PREFIX . '\Contact');
        $this->assertNull($contact->CustomFields->c->birthday);

        //Pre-1970 use case
        $contact_cf = '1947-08-30 10:00:00';
        $response = $this->model->update("1", array(
            'Contact.CustomFields.c.datetime1' => (object) array('value' => $contact_cf),
        ));
        $this->assertIsA($response, 'RightNow\Libraries\ResponseObject');
        $this->assertFalse(is_object($response->result));
        $this->assertSame(1, count($response->errors));
        $this->assertSame(0, count($response->warnings));
        $contact = $response->result;
        $this->assertNull($contact);

        //Post-2038 use case
        $contact_cf = '2040-10-30 10:00:00';
        $response = $this->model->update("1", array(
            'Contact.CustomFields.c.datetime1' => (object) array('value' => $contact_cf),
        ));
        $this->assertIsA($response, 'RightNow\Libraries\ResponseObject');
        $this->assertFalse(is_object($response->result));
        $this->assertSame(1, count($response->errors));
        $this->assertSame(0, count($response->warnings));
        $contact = $response->result;
        $this->assertNull($contact);

        // in case the rollback doesn't work??? *sigh*
        $response = $this->model->update(1, array(
            'Contact.Login' => (object) array('value' => 'walkerj')
        ));
        Connect\ConnectAPI::rollback();

        $this->unsetMockSession();
    }

    /* 190712-000090 Commenting out since test passes on dev servers but causes headers
     * already sent error on Jenkins
    function testUnmodifiedEmailDoesNotSendOnUpdate() {
        $login = 'banana' . microtime(true);
        $email = 'bananaprimary@foo.com';
        $email2 = 'alt1email@foo.com';
        $response = $this->model->create(array(
            'Contact.Login' => (object) array('value' => $login),
            'Contact.Name.First' => (object) array('value' => 'bananaFirst'),
            'Contact.Name.Last' => (object) array('value' => 'bananaLast'),
            'Contact.Emails.0.Address' => (object) array('value' => $email),
            'Contact.Emails.1.Address' => (object) array('value' => $email2),
        ));
        $contact = $response->result;
        $contactID = $contact->ID;
        $formData = array('Contact.Emails.PRIMARY.Address' => (object)array('value' => $email), 'Contact.Emails.ALT1.Address' => (object)array('value' => $email2), 'Contact.Name.First' => (object)array('value' => 'bananaFirst'));

        $response = $this->model->update($contactID, $formData);
        $this->assertTrue(is_object($response));
    }
    */

    function testUpdateErrors() {
        $this->setMockSession();

        $response = $this->model->update(1, array(
            'Contact.Login' => (object) array('value' => '<banan a>')
        ));
        $this->assertIsA($response, 'RightNow\Libraries\ResponseObject');
        $this->assertNull($response->result);
        $this->assertSame(1, count($response->errors));
        $this->assertSame(0, count($response->warnings));

        // Disabled contact can't perform an update.
        $contact = $this->model->get(1)->result;
        $contact->Disabled = true;
        $response = $this->model->update(1, array(
            'Contact.Login' => (object) array('value' => 'updateme'),
            'Contact.Disabled' => (object) array('value' => false),
        ));
        $this->assertIsA($response, 'RightNow\Libraries\ResponseObject');
        $this->assertNull($response->result);
        $this->assertSame(1, count($response->errors));
        $this->assertSame(0, count($response->warnings));

        // OpenLogin can't update the contact if it's not in the
        // process of enabling it.
        $response = $this->model->update(1, array(
            'Contact.Login' => (object) array('value' => 'updateme'),
        ), true);
        $this->assertIsA($response, 'RightNow\Libraries\ResponseObject');
        $this->assertNull($response->result);
        $this->assertSame(1, count($response->errors));
        $this->assertSame(0, count($response->warnings));

        $response = $this->model->update(1, array(
            'Contact.Login' => (object) array('value' => 'updateme'),
            'Contact.Disabled' => (object) array('value' => true),
        ), true);
        $this->assertIsA($response, 'RightNow\Libraries\ResponseObject');
        $this->assertNull($response->result);
        $this->assertSame(1, count($response->errors));
        $this->assertSame(0, count($response->warnings));

        // Strict false check
        $response = $this->model->update(1, array(
            'Contact.Login' => (object) array('value' => 'updateme'),
            'Contact.Disabled' => (object) array('value' => 'false'),
        ), true);
        $this->assertIsA($response, 'RightNow\Libraries\ResponseObject');
        $this->assertNull($response->result);
        $this->assertSame(1, count($response->errors));
        $this->assertSame(0, count($response->warnings));

        $contact->Disabled = false;
        $this->unsetMockSession();
    }

    function testCurrentPassword() {
        $this->setMockSession();

        // change password to the same
        $response = $this->model->update(1, array(
            'Contact.NewPassword' => (object) array('value' => '', 'currentValue' => '', 'requireCurrent' => true),
        ));
        $this->assertIsA($response, 'RightNow\Libraries\ResponseObject');
        $this->assertTrue(is_object($response->result));
        $this->assertSame(0, count($response->errors));
        $this->assertSame(0, count($response->warnings));

        // try to change password with bad current password
        $response = $this->model->update(1, array(
            'Contact.NewPassword' => (object) array('value' => 'bob', 'currentValue' => 'alice', 'requireCurrent' => true),
        ));
        $this->assertIsA($response, 'RightNow\Libraries\ResponseObject');
        $this->assertNull($response->result);
        $this->assertSame(1, count($response->errors));
        $this->assertSame("Your current password doesn't match the one we have for you. Please re-type it.", $response->errors[0]->externalMessage);
        $this->assertSame(0, count($response->warnings));

        // change password
        $response = $this->model->update(1, array(
            'Contact.NewPassword' => (object) array('value' => 'bob', 'currentValue' => '', 'requireCurrent' => true),
        ));
        $this->assertIsA($response, 'RightNow\Libraries\ResponseObject');
        $this->assertTrue(is_object($response->result));
        $this->assertSame(0, count($response->errors));
        $this->assertSame(0, count($response->warnings));

        // try to change password with old password
        $response = $this->model->update(1, array(
            'Contact.NewPassword' => (object) array('value' => '', 'currentValue' => '', 'requireCurrent' => true),
        ));
        $this->assertIsA($response, 'RightNow\Libraries\ResponseObject');
        $this->assertNull($response->result);
        $this->assertSame(1, count($response->errors));
        $this->assertSame("Your current password doesn't match the one we have for you. Please re-type it.", $response->errors[0]->externalMessage);
        $this->assertSame(0, count($response->warnings));

        // change password back to old password
        $response = $this->model->update(1, array(
            'Contact.NewPassword' => (object) array('value' => '', 'currentValue' => 'bob', 'requireCurrent' => true),
        ));
        $this->assertIsA($response, 'RightNow\Libraries\ResponseObject');
        $this->assertTrue(is_object($response->result));
        $this->assertSame(0, count($response->errors));
        $this->assertSame(0, count($response->warnings));
        $this->unsetMockSession();
    }

    function testCreate() {
        $login = 'banana' . microtime(true);
        $response = $this->model->create(array(
            'Contact.Login' => (object) array('value' => $login),
            'Contact.Name.First' => (object) array('value' => 'bananaFirst'),
            'Contact.Name.Last' => (object) array('value' => 'bananaLast'),
        ));
        $this->assertIsA($response, 'RightNow\Libraries\ResponseObject');
        $this->assertIsA($response->result, CONNECT_NAMESPACE_PREFIX . '\Contact');
        $contact = $response->result;
        $this->assertSame($login, $contact->Login);
        $this->assertSame('bananaFirst', $contact->Name->First);
        $this->assertSame('bananaLast', $contact->Name->Last);

        $login = 'banana' . microtime(true);
        $email = 'banana@foo.com.' . microtime(true);
        $response = $this->model->create(array(
            'Contact.Login' => (object) array('value' => $login),
            'Contact.Emails.0.Address' => (object) array('value' => $email),
            'Contact.Name.First' => (object) array('value' => 'bananaFirst'),
            'Contact.Name.Last' => (object) array('value' => 'bananaLast'),
        ));
        $this->assertIsA($response, 'RightNow\Libraries\ResponseObject');
        $this->assertSame(0, count($response->errors));
        $this->assertSame(0, count($response->warnings));
        $contact = $response->result;
        $this->assertIsA($contact, CONNECT_NAMESPACE_PREFIX . '\Contact');
        $this->assertSame($login, $contact->Login);
        $this->assertSame($email, $contact->Emails[0]->Address);
        $this->assertSame('bananaFirst', $contact->Name->First);
        $this->assertSame('bananaLast', $contact->Name->Last);

        $login = 'banana' . microtime(true);
        $email = 'primary' . microtime(true) . '@foo.com';
        $email2 = 'alt1' . microtime(true) . '@foo.com';
        $response = $this->model->create(array(
            'Contact.Login' => (object) array('value' => $login),
            'Contact.Emails.0.Address' => (object) array('value' => $email),
            'Contact.Emails.1.Address' => (object) array('value' => $email2),
        ));
        $this->assertIsA($response, 'RightNow\Libraries\ResponseObject');
        $this->assertSame(0, count($response->errors));
        $this->assertSame(0, count($response->warnings));
        $contact = $response->result;
        $this->assertIsA($contact, CONNECT_NAMESPACE_PREFIX . '\Contact');
        $this->assertSame($login, $contact->Login);
        $this->assertSame($email, $contact->Emails[0]->Address);
        $this->assertSame($email2, $contact->Emails[1]->Address);

        $response = $this->model->create(array(
            'Contact.Login' => (object) array('value' => ''),
            'Contact.Name.First' => (object) array('value' => 'bananaFirst'),
            'Contact.Name.Last' => (object) array('value' => 'bananaLast'),
        ));
        $this->assertIsA($response, 'RightNow\Libraries\ResponseObject');
        $this->assertSame(0, count($response->errors));
        $this->assertSame(0, count($response->warnings));
        $contact = $response->result;
        $this->assertIsA($contact, CONNECT_NAMESPACE_PREFIX . '\Contact');
        $this->assertNull($contact->Login);

        $login = 'apple' . microtime(true);
        $email = 'apple@foo.com.' . microtime(true);
        $contact_cf = '2020-01-29 10:00:00';
        $response = $this->model->create(array(
            'Contact.Login' => (object) array('value' => $login),
            'Contact.Emails.0.Address' => (object) array('value' => $email),
            'Contact.CustomFields.c.datetime1' => (object) array('value' => $contact_cf),
        ));
        $this->assertIsA($response, 'RightNow\Libraries\ResponseObject');
        $this->assertSame(0, count($response->errors));
        $this->assertSame(0, count($response->warnings));
        $contact = $response->result;
        $this->assertIsA($contact, CONNECT_NAMESPACE_PREFIX . '\Contact');
        $this->assertSame($login, $contact->Login);
        $this->assertSame($email, $contact->Emails[0]->Address);
        $this->assertNotNull($contact->CustomFields->c->datetime1);

        //Pre-1970 use case
        $login = 'apple' . microtime(true);
        $email = 'apple@foo.com.' . microtime(true);
        $contact_cf = '1947-08-15 10:00:00';
        $response = $this->model->create(array(
            'Contact.Login' => (object) array('value' => $login),
            'Contact.Emails.0.Address' => (object) array('value' => $email),
            'Contact.CustomFields.c.datetime1' => (object) array('value' => $contact_cf),
        ));
        $this->assertIsA($response, 'RightNow\Libraries\ResponseObject');
        $this->assertSame(1, count($response->errors));
        $this->assertSame(0, count($response->warnings));
        $contact = $response->result;
        $this->assertNull($contact);

        //Post-2038 use case
        $login = 'apple' . microtime(true);
        $email = 'apple@foo.com.' . microtime(true);
        $contact_cf = '2040-10-14 10:00:00';
        $response = $this->model->create(array(
            'Contact.Login' => (object) array('value' => $login),
            'Contact.Emails.0.Address' => (object) array('value' => $email),
            'Contact.CustomFields.c.datetime1' => (object) array('value' => $contact_cf),
        ));
        $this->assertIsA($response, 'RightNow\Libraries\ResponseObject');
        $this->assertSame(1, count($response->errors));
        $this->assertSame(0, count($response->warnings));
        $contact = $response->result;
        $this->assertNull($contact);

        //Handle Custom Field of datetime type
        $login = 'kiwi' . microtime(true);
        $email = 'kiwi@foo.com.' . microtime(true);
        $contact_cf = 1597899398;
        $response = $this->model->create(array(
            'Contact.Login' => (object) array('value' => $login),
            'Contact.Emails.0.Address' => (object) array('value' => $email),
            'Contact.CustomFields.c.datetime1' => (object) array('value' => $contact_cf),
        ));
        $this->assertIsA($response, 'RightNow\Libraries\ResponseObject');
        $this->assertSame(0, count($response->errors));
        $this->assertSame(0, count($response->warnings));
        $contact = $response->result;
        $this->assertNotNull($contact);
        $this->assertNotNull($contact->CustomFields->c->datetime1);

    }

    function testCreateWithOrgAssociation(){

        //Set up some test data
        $password = Api::pw_rev_encrypt('ravipassword');
        Api::test_sql_exec_direct("update orgs set login='ravi', password_encrypt='$password' where name='Ravisonix'");
        Api::test_sql_exec_direct("update orgs set login='domo' where name='Pepperdomo'");

        $response = $this->model->create(array(
            'Contact.Login' => (object) array('value' => 'banana' . microtime(true)),
            'Contact.Organization.Login' => (object) (array('value' => 'ravi')),
        ));
        $this->assertIdentical(1, count($response->errors));

        //@@@ QA 130325-000067 A required organization login should throw an error
        $response = $this->model->create(array(
            'Contact.Login' => (object) array('value' => 'banana' . microtime(true)),
            'Contact.Organization.Login' => (object) array('value' => '', 'required' => true),
        ));
        $this->assertIdentical(1, count($response->errors));

        $response = $this->model->create(array(
            'Contact.Login' => (object) array('value' => 'banana' . microtime(true)),
            'Contact.Organization.Login' => (object) (array('value' => 'ravi')),
            'Contact.Organization.NewPassword' => (object) array('value' => ''),
        ));
        $this->assertIdentical(1, count($response->errors));

        $response = $this->model->create(array(
            'Contact.Login' => (object) array('value' => 'banana' . microtime(true)),
            'Contact.Organization.Login' => (object) (array('value' => 'ravi')),
            'Contact.Organization.NewPassword' => (object) array('value' => 'ravipassword'),
        ));
        $this->assertIdentical(0, count($response->errors));
        $this->assertIsA($response->result, CONNECT_NAMESPACE_PREFIX . '\Contact');
        $this->assertIdentical(43, $response->result->Organization->ID);

        $response = $this->model->create(array(
            'Contact.Login' => (object) array('value' => 'banana' . microtime(true)),
            'Contact.Organization.Login' => (object) (array('value' => 'domo')),
            'Contact.Organization.NewPassword' => (object) array('value' => ''),
        ));
        $this->assertIdentical(0, count($response->errors));
        $this->assertIsA($response->result, CONNECT_NAMESPACE_PREFIX . '\Contact');
        $this->assertIdentical(46, $response->result->Organization->ID);

        $response = $this->model->create(array(
            'Contact.Login' => (object) array('value' => 'banana' . microtime(true)),
            'Contact.Organization.Login' => (object) (array('value' => 'domo')),
        ));
        $this->assertIdentical(0, count($response->errors));
        $this->assertIsA($response->result, CONNECT_NAMESPACE_PREFIX . '\Contact');
        $this->assertIdentical(46, $response->result->Organization->ID);

        $response = $this->model->create(array(
            'Contact.Login' => (object) array('value' => 'banana' . microtime(true)),
            'Contact.Organization.Login' => (object) (array('value' => '')),
            'Contact.Organization.NewPassword' => (object) (array('value' => 'asdf')),
        ));
        $this->assertIdentical(0, count($response->errors));
        $this->assertIsA($response->result, CONNECT_NAMESPACE_PREFIX . '\Contact');
        $this->assertNull($response->result->Organization);

        $response = $this->model->create(array(
            'Contact.Login' => (object) array('value' => 'banana' . microtime(true)),
            'Contact.Organization.NewPassword' => (object) (array('value' => 'asdf')),
        ));
        $this->assertIdentical(0, count($response->errors));
        $this->assertIsA($response->result, CONNECT_NAMESPACE_PREFIX . '\Contact');
        $this->assertNull($response->result->Organization);

        Api::test_sql_exec_direct("update orgs set login=null, password_encrypt=null where name='Ravisonix'");
        Api::test_sql_exec_direct("update orgs set login=null where name='Pepperdomo'");
    }

    function testCreateWithBlankFields() {
        $login = 'banana' . microtime(true);
        $password = '';
        $response = $this->model->create(array(
            'Contact.Login' => (object) array('value' => $login),
            'Contact.Name.First' => (object) array('value' => 'bananaFirst'),
            'Contact.Name.Last' => (object) array('value' => 'bananaLast'),
            'Contact.NewPassword' => (object) array('value' => $password),
            'Contact.CustomFields.c.text1' => (object) array('value' => ''),
            'Contact.CustomFields.c.textarea1' => (object) array('value' => ''),
            'Contact.CustomFields.c.pets_name' => (object) array('value' => ''),
            'Contact.CustomFields.c.website' => (object) array('value' => 'www.example.com'),
        ));
        $this->assertIsA($response, 'RightNow\Libraries\ResponseObject');
        $this->assertIsA($response->result, CONNECT_NAMESPACE_PREFIX . '\Contact');
        $contact = $response->result;
        $this->assertSame($login, $contact->Login);
        $this->assertSame('bananaFirst', $contact->Name->First);
        $this->assertSame('bananaLast', $contact->Name->Last);
        $this->assertEqual($password, $contact->NewPassword);
        $this->assertNull($contact->CustomFields->c->text1);
        $this->assertNull($contact->CustomFields->c->textarea1);
        $this->assertNull($contact->CustomFields->c->pets_name);
        $this->assertSame('www.example.com', $contact->CustomFields->c->website);

        $response = $this->model->create(array(
            'Contact.CustomFields.c.pets_name' => (object) array('value' => null),
        ));
        $this->assertIsA($response, 'RightNow\Libraries\ResponseObject');
        $this->assertIsA($response->result, CONNECT_NAMESPACE_PREFIX . '\Contact');
        $contact = $response->result;
        $this->assertNull($contact->CustomFields->c->pets_name);

        $response = $this->model->create(array(
        ));
        $this->assertIsA($response, 'RightNow\Libraries\ResponseObject');
        $this->assertIsA($response->result, CONNECT_NAMESPACE_PREFIX . '\Contact');
        $contact = $response->result;
        $this->assertSame('Fred', $contact->CustomFields->c->pets_name);
    }

    function testCreateErrors() {
         $response = $this->model->create(array(
              'Contact.Login' => (object) array('value' => 'eturner@rightnow.com.invalid'),
          ));
          $this->assertIsA($response, 'RightNow\Libraries\ResponseObject');
          $this->assertNull($response->result);
          $this->assertIdentical(1, count($response->errors));

          $response = $this->model->create(array(
               'Contact.Emails.0.Address' => (object) array('value' => 'eturner@rightnow.com.invalid'),
           ));
           $this->assertIsA($response, 'RightNow\Libraries\ResponseObject');
           $this->assertNull($response->result);
           $this->assertIdentical(1, count($response->errors));

           // create a Contact with an invalid field.
          $response = $this->model->create(array(
             'Contact.Emails.0.Address' => (object) array('value' => 'raj.chandran' . microtime(true) . '@oracle.com.invalid'),
             'Contact.Pwn' => 'foo'
         ));
           $this->assertIsA($response, 'RightNow\Libraries\ResponseObject');
           $this->assertNull($response->result);
           $this->assertIdentical(1, count($response->errors));
    }

    function testGetChannelFields(){
        $response = $this->model->getChannelFields(array());
        $this->assertTrue(is_array($response->errors));
        $this->assertSame(1, count($response->errors));
        $response = $this->model->getChannelFields(null);
        $this->assertTrue(is_array($response->errors));
        $this->assertSame(1, count($response->errors));
        $response = $this->model->getChannelFields(true);
        $this->assertTrue(is_array($response->errors));
        $this->assertSame(1, count($response->errors));

        $response = $this->model->getChannelFields("not an existing login");
        $this->assertTrue(is_array($response->result));
        $this->assertIdentical(0, count($response->result));

        $response = $this->model->getChannelFields(9934);
        $this->assertTrue(is_array($response->result));
        $this->assertIdentical(0, count($response->result));

        //We don't have any contacts with channel accounts by default, so manually add one
        $contact = $this->model->getBlank()->result;
        $contact->Login = "testContactWithChannelUsernames";

        $contact->ChannelUsernames = new Connect\ChannelUsernameArray();
        $channelUserName = $contact->ChannelUsernames[] = new Connect\ChannelUsername();
        $channelUserName->Username = "fakeUsername";
        $channelUserName->ChannelType = CHAN_FACEBOOK;
        $contact->save();

        $response = $this->model->getChannelFields("testContactWithChannelUsernames");
        $this->assertIsA($response, 'RightNow\Libraries\ResponseObject');
        $this->assertTrue(is_array($response->result));
        $this->assertIdentical('fakeUsername', $response->result[CHAN_FACEBOOK]['Username']);

        Connect\ConnectApi::rollback();
    }

    function testGetChannelTypes() {
        $channels = array('Twitter' => CHAN_TWITTER, 'YouTube' => CHAN_YOUTUBE, 'Facebook' => CHAN_FACEBOOK);

        $response = $this->model->getChannelTypes();
        $this->assertIsA($response, 'RightNow\Libraries\ResponseObject');
        $this->assertTrue(is_array($response->result));
        $this->assertSame(0, count($response->errors));
        $this->assertSame(0, count($response->warnings));
        $channelFields = $response->result;
        foreach ($channels as $channelName => $channelID) {
            $channelField = $channelFields[$channelID];
            $this->assertIdentical($channelName, $channelField['LookupName']);
        }
    }

    function testLookupContactByEmail(){
        $response = $this->model->lookupContactByEmail('');
        $this->assertIsA($response, 'RightNow\Libraries\ResponseObject');
        $this->assertFalse($response->result);
        $this->assertSame(0, count($response->errors));
        $this->assertSame(0, count($response->warnings));

        $response = $this->model->lookupContactByEmail('banana');
        $this->assertIsA($response, 'RightNow\Libraries\ResponseObject');
        $this->assertFalse($response->result);
        $this->assertSame(0, count($response->errors));
        $this->assertSame(0, count($response->warnings));

        $response = $this->model->lookupContactByEmail('eturner@rightnow.com.invalid');
        $this->assertIsA($response, 'RightNow\Libraries\ResponseObject');
        $this->assertTrue(is_int($response->result));
        $this->assertSame(0, count($response->errors));
        $this->assertSame(0, count($response->warnings));

        $response = $this->model->lookupContactByEmail('eturner@rightnow.com.invalid', 'John', 'Doe');
        $this->assertIsA($response, 'RightNow\Libraries\ResponseObject');
        $this->assertTrue(is_int($response->result));
        $this->assertSame(0, count($response->errors));
        $this->assertSame(0, count($response->warnings));

        $response = $this->model->lookupContactByEmail('test@example.com', 'John', 'Doe');
        $this->assertIsA($response, 'RightNow\Libraries\ResponseObject');
        $this->assertFalse($response->result);
        $this->assertSame(0, count($response->errors));
        $this->assertSame(0, count($response->warnings));
    }

    function testGetIDFromLogin(){
        $response = $this->model->getIDFromLogin('eturner@rightnow.com.invalid');
        $this->assertIsA($response, 'RightNow\Libraries\ResponseObject');
        $this->assertTrue(is_int($response->result));
        $this->assertSame(0, count($response->errors));
        $this->assertSame(0, count($response->warnings));

        $response = $this->model->getIDFromLogin('test@example.com.invalid.nope');
        $this->assertIsA($response, 'RightNow\Libraries\ResponseObject');
        $this->assertNull($response->result);
        $this->assertSame(0, count($response->errors));
        $this->assertSame(0, count($response->warnings));

        $response = $this->model->getIDFromLogin("hackermcgee@'Select * from _accounts'");
        $this->assertIsA($response, 'RightNow\Libraries\ResponseObject');
        $this->assertNull($response->result);
        $this->assertSame(0, count($response->errors));
        $this->assertSame(0, count($response->warnings));
    }

    function testLookupContactAndOrgIdByEmail(){
        $response = $this->model->lookupContactAndOrgIdByEmail('someContact@thatDoesNotExist.com');
        $this->assertIsA($response, 'RightNow\Libraries\ResponseObject');
        $this->assertFalse($response->result);
        $this->assertSame(0, count($response->errors));
        $this->assertSame(0, count($response->warnings));

        $response = $this->model->lookupContactAndOrgIdByEmail('walkerj@brindell.example.invalid');
        $this->assertIsA($response, 'RightNow\Libraries\ResponseObject');
        $this->assertFalse($response->result);
        $this->assertSame(0, count($response->errors));
        $this->assertSame(0, count($response->warnings));

        $response = $this->model->lookupContactAndOrgIdByEmail('ejergan@latisan.example.invalid', 'John', 'Doe');
        $this->assertIsA($response, 'RightNow\Libraries\ResponseObject');
        $this->assertTrue(is_array($response->result));
        $this->assertSame(0, count($response->errors));
        $this->assertSame(0, count($response->warnings));
        $this->assertTrue(is_int($response->result[0]));
        $this->assertTrue(is_int($response->result[1]));

        $response = $this->model->lookupContactAndOrgIdByEmail('eturner@rightnow.com.invalid');
        $this->assertIsA($response, 'RightNow\Libraries\ResponseObject');
        $this->assertTrue(is_array($response->result));
        $this->assertSame(0, count($response->errors));
        $this->assertSame(0, count($response->warnings));
        $this->assertTrue(is_int($response->result[0]));
        $this->assertNull($response->result[1]);

        $response = $this->model->lookupContactAndOrgIdByEmail('test@example.com', 'John', 'Doe');
        $this->assertIsA($response, 'RightNow\Libraries\ResponseObject');
        $this->assertFalse($response->result);
        $this->assertSame(0, count($response->errors));
        $this->assertSame(0, count($response->warnings));
    }

    function testLookupContactByOpenLoginAccount(){
        $response = $this->model->lookupContactByOpenLoginAccount('facebook', 12);
        $this->assertIsA($response, 'RightNow\Libraries\ResponseObject');
        $this->assertFalse($response->result);
        $this->assertSame(0, count($response->errors));
        $this->assertSame(0, count($response->warnings));

        $contact = $this->model->lookupContactByOpenLoginAccount('twitter', 'GlzIGF3ZXNvbWUgYXNsa2Qga2xqYXMgbGR')->result;
        $this->assertIsA($contact, 'RightNow\Connect\v1_4\Contact');
        $this->assertIdentical(1434, $contact->ID);
        $this->assertIdentical(0, count($contact->OpenIDAccounts));
        $this->assertIdentical(1, count($contact->ChannelUsernames));
        $this->assertIdentical(11, $contact->ChannelUsernames[0]->ChannelType->ID);
        $this->assertIdentical('Twitter', $contact->ChannelUsernames[0]->ChannelType->LookupName);
        $this->assertIdentical('GlzIGF3ZXNvbWUgYXNsa2Qga2xqYXMgbGR', $contact->ChannelUsernames[0]->UserNumber);
        $this->assertIdentical('chuckb', $contact->ChannelUsernames[0]->Username);

        $contact = $this->model->lookupContactByOpenLoginAccount('openid', 'test_address_1@test.invalid')->result;
        $this->assertIsA($contact, 'RightNow\Connect\v1_4\Contact');
        $this->assertIdentical(1, $contact->OpenIDAccounts[0]->ID);
        $this->assertIdentical(100, $contact->ID);
    }

    function testGetOpenLoginChannel(){
        $response = $this->model->getOpenLoginChannel('facebook')->result;
        $this->assertSame($response, CHAN_FACEBOOK);

        $response = $this->model->getOpenLoginChannel('FaCeBoOk')->result;
        $this->assertSame($response, CHAN_FACEBOOK);

        $response = $this->model->getOpenLoginChannel('twitter')->result;
        $this->assertSame($response, CHAN_TWITTER);

        $response = $this->model->getOpenLoginChannel('TwItTer')->result;
        $this->assertSame($response, CHAN_TWITTER);

        $response = $this->model->getOpenLoginChannel('foo')->result;
        $this->assertSame($response, 0);
    }

    function testSendLoginEmail(){
        $this->setMockSession();

        $this->CI->session->expect('setSessionData', array(array('previouslySeenEmail' => null)));
        $response = $this->model->sendLoginEmail(null);
        $this->assertIsA($response, 'RightNow\Libraries\ResponseObject');
        $this->assertTrue(is_array($response->result));
        $this->assertTrue(is_string($response->result['message']));

        $this->CI->session->expect('setSessionData', array(array('previouslySeenEmail' => 1)));
        $response = $this->model->sendLoginEmail(1);
        $this->assertIsA($response, 'RightNow\Libraries\ResponseObject');
        $this->assertTrue(is_array($response->result));
        $this->assertTrue(is_string($response->result['message']));

        $this->CI->session->expect('setSessionData', array(array('previouslySeenEmail' => true)));
        $response = $this->model->sendLoginEmail(true);
        $this->assertIsA($response, 'RightNow\Libraries\ResponseObject');
        $this->assertTrue(is_array($response->result));
        $this->assertTrue(is_string($response->result['message']));

        $this->CI->session->expect('setSessionData', array(array('previouslySeenEmail' => array())));
        $response = $this->model->sendLoginEmail(array());
        $this->assertIsA($response, 'RightNow\Libraries\ResponseObject');
        $this->assertTrue(is_array($response->result));
        $this->assertTrue(is_string($response->result['message']));

        $this->CI->session->expect('setSessionData', array(array('previouslySeenEmail' => '')));
        $response = $this->model->sendLoginEmail('');
        $this->assertIsA($response, 'RightNow\Libraries\ResponseObject');
        $this->assertTrue(is_array($response->result));
        $this->assertTrue(is_string($response->result['message']));

        $email = 'test@example.com' . time();
        $this->CI->session->expect('setSessionData', array(array('previouslySeenEmail' => $email)));
        $response = $this->model->sendLoginEmail($email);
        $this->assertIsA($response, 'RightNow\Libraries\ResponseObject');
        $this->assertTrue(is_array($response->result));
        $this->assertTrue(is_string($response->result['message']));

        $email = 'perpetualslacontactnoorg@invalid.com';
        $this->CI->session->expect('setSessionData', array(array('previouslySeenEmail' => $email)));
        $response = $this->model->sendLoginEmail($email);
        $this->assertIsA($response, 'RightNow\Libraries\ResponseObject');
        $this->assertTrue(is_array($response->result));
        $this->assertTrue(is_string($response->result['message']));

        $email = 'perpetualSLACONTactnoorg@invalid.com';
        $this->CI->session->expect('setSessionData', array(array('previouslySeenEmail' => $email)));
        $response = $this->model->sendLoginEmail($email);
        $this->assertIsA($response, 'RightNow\Libraries\ResponseObject');
        $this->assertTrue(is_array($response->result));
        $this->assertTrue(is_string($response->result['message']));

        $email = 'perpetualslacontactnoorg@invalid.com';
        $this->CI->session->expect('setSessionData', array(array('previouslySeenEmail' => $email)));
        $response = $this->model->sendLoginEmail($email);
        $this->assertIsA($response, 'RightNow\Libraries\ResponseObject');
        $this->assertTrue(is_array($response->result));
        $this->assertTrue(is_string($response->result['message']));

        Connect\ConnectApi::rollback();
        $this->unsetMockSession();
    }

    function testContactAlreadyExists(){
        $response = $this->model->contactAlreadyExists('login', 'foo@example.com');
        $this->assertIsA($response, 'RightNow\Libraries\ResponseObject');
        $this->assertFalse($response->result);
        $this->assertSame(0, count($response->errors));
        $this->assertSame(0, count($response->warnings));

        $response = $this->model->contactAlreadyExists('email', 'foo@example.com');
        $this->assertIsA($response, 'RightNow\Libraries\ResponseObject');
        $this->assertFalse($response->result);
        $this->assertSame(0, count($response->errors));
        $this->assertSame(0, count($response->warnings));

        $response = $this->model->contactAlreadyExists('login', 'eturner@rightnow.com.invalid');
        $this->assertIsA($response, 'RightNow\Libraries\ResponseObject');
        $this->assertTrue(is_array($response->result));
        $this->assertTrue(is_string($response->result['message']));
        $this->assertSame(0, count($response->errors));
        $this->assertSame(0, count($response->warnings));

        $response = $this->model->contactAlreadyExists('email', 'eturner@rightnow.com.invalid');
        $this->assertIsA($response, 'RightNow\Libraries\ResponseObject');
        $this->assertTrue(is_array($response->result));
        $this->assertTrue(is_string($response->result['message']));
        $this->assertSame(0, count($response->errors));
        $this->assertSame(0, count($response->warnings));
    }

    function testValidateCurrentPassword() {
        $this->assertTrue($this->model->validateCurrentPassword(9, ''));
        $this->assertFalse($this->model->validateCurrentPassword(9, 'asdf'));

        $this->assertTrue($this->model->validateCurrentPassword(10, ''));
        $this->assertFalse($this->model->validateCurrentPassword(10, 'asdf'));

        // update an existing contact's password, then re-test
        $contact = Connect\Contact::fetch(9);
        $contact->NewPassword = 'some new password';
        $contact->save();

        $this->assertTrue($this->model->validateCurrentPassword(9, 'some new password'));
        $this->assertFalse($this->model->validateCurrentPassword(9, ''));
        $this->assertFalse($this->model->validateCurrentPassword(9, 'some_new_password'));

        $contact->NewPassword = ''; // rollback
        $contact->save();
    }

    function testValidateCurrentPasswordDisallowsOpenLoginContactWithoutSetPassword() {
        $login = 'banana' . microtime(true);
        $contact = $this->model->create(array(
            'Contact.Login'      => (object) array('value' => $login),
            'Contact.Name.First' => (object) array('value' => 'bananaFirst'),
            'Contact.Name.Last'  => (object) array('value' => 'bananaLast'),
        ))->result;

        $this->assertFalse($this->model->validateCurrentPassword($contact->ID, 'currentpassword'));

        $this->logIn($login, array('openLoginUsed' => array('provider' => 'twitter')));
        $this->assertFalse($this->model->validateCurrentPassword($contact->ID, ''));
        $this->assertFalse($this->model->validateCurrentPassword($contact->ID, null));
        $this->assertFalse($this->model->validateCurrentPassword($contact->ID, 'currentpassword'));
        $this->logOut();

        $this->destroyObject($contact);
    }

    function testValidateCurrentPasswordAllowsOpenLoginContactWithSetPasswordAndMatch() {
        $login = 'banana' . microtime(true);
        $password = 'password';
        $contact = $this->model->create(array(
            'Contact.Login'       => (object) array('value' => $login),
            'Contact.Name.First'  => (object) array('value' => 'bananaFirst'),
            'Contact.Name.Last'   => (object) array('value' => 'bananaLast'),
            'Contact.NewPassword' => (object) array('value' => $password),
        ))->result;

        $this->assertFalse($this->model->validateCurrentPassword($contact->ID, 'currentpassword'));

        $this->logIn($login, array('openLoginUsed' => array('provider' => 'twitter')));
        $this->assertFalse($this->model->validateCurrentPassword($contact->ID, 'currentpassword'));
        $this->assertTrue($this->model->validateCurrentPassword($contact->ID, 'password'));
        $this->logOut();

        $this->destroyObject($contact);
    }

    function testLookupContact(){
        $method = $this->getMethod('lookupContact');

        $response = $method('');
        $this->assertFalse($response);

        $response = $method('banana');
        $this->assertFalse($response);

        $response = $method('eturner@rightnow.com.invalid');
        $this->assertTrue(is_array($response));
        $this->assertTrue(is_int($response['c_id']));
        $this->assertNull($response['org_id']);

        $response = $method('someContact@thatDoesNotExist.com');
        $this->assertFalse($response);
        $response = $method('foo@example.com');
        $this->assertFalse($response);
        $response = $method(null);
        $this->assertFalse($response);
        $response = $method(false);
        $this->assertFalse($response);
    }

    function testValidateUniqueFields(){
        $method = $this->getMethod('validateUniqueFields');
    }

    function testCheckUniqueFields(){
        $method = $this->getMethod('checkUniqueFields');

        $contact = new Connect\Contact();
        $contact->Login = "eturner@rightnow.com.invalid";
        $response = $method($contact);
        $this->assertTrue(is_array($response));
        $this->assertTrue(is_string($response['login']));

        $contact = new Connect\Contact();
        $this->addEmailToContact($contact, 'eturner@rightnow.com.invalid');
        $response = $method($contact);
        $this->assertTrue(is_array($response));
        $this->assertTrue(is_string($response['email']));

        $contact = new Connect\Contact();
        $this->addEmailToContact($contact, 'whatever@foo.bar' . microtime(true));
        $this->addEmailToContact($contact, 'eturner@rightnow.com.invalid', 1);
        $response = $method($contact);
        $this->assertTrue(is_array($response));
        $this->assertTrue(is_string($response['email']));

        $contact = new Connect\Contact();
        $this->addEmailToContact($contact, 'whatever@foo.bar' . microtime(true));
        $this->addEmailToContact($contact, 'whatever@foo.bar.baz' . microtime(true), 1);
        $this->addEmailToContact($contact, 'eturner@rightnow.com.invalid', 2);
        $response = $method($contact);
        $this->assertTrue(is_array($response));
        $this->assertTrue(is_string($response['email']));

        $contact = Connect\Contact::fetch(1268);
        $response = $method($contact);
        $this->assertFalse($response);

        $contact = new Connect\Contact();
        $response = $method($contact);
        $this->assertFalse($response);

        $contact = new Connect\Contact();
        $response = $method($contact);
        $contact->Login = "foo@example.com";
        $this->assertFalse($response);

        //Ensure duplicate emails on the same contact throw errors
        $contact = new Connect\Contact();
        $this->addEmailToContact($contact, 'fake@example.com');
        $this->addEmailToContact($contact, 'fake@example.com', 1);
        $this->addEmailToContact($contact, 'fake@example.com', 2);
        $response = $method($contact);
        $this->assertTrue(is_array($response));
        $this->assertTrue(is_string($response['duplicates_within_email_fields']));

        $contact = new Connect\Contact();
        $this->addEmailToContact($contact, 'fake1@example.com');
        $this->addEmailToContact($contact, 'fake@example.com', 1);
        $this->addEmailToContact($contact, 'fake@example.com', 2);
        $response = $method($contact);
        $this->assertTrue(is_array($response));
        $this->assertTrue(is_string($response['duplicates_within_email_fields']));

        $contact = new Connect\Contact();
        $this->addEmailToContact($contact, 'fake@example.com');
        $this->addEmailToContact($contact, 'fake1@example.com', 1);
        $this->addEmailToContact($contact, 'fake@example.com', 2);
        $response = $method($contact);
        $this->assertTrue(is_array($response));
        $this->assertTrue(is_string($response['duplicates_within_email_fields']));

        $contact = new Connect\Contact();
        $this->addEmailToContact($contact, 'fake@example.com');
        $this->addEmailToContact($contact, 'fake@example.com', 1);
        $this->addEmailToContact($contact, 'fake1@example.com', 2);
        $response = $method($contact);
        $this->assertTrue(is_array($response));
        $this->assertTrue(is_string($response['duplicates_within_email_fields']));
    }

    function testValidateStateAndCountry() {
        $method = $this->getMethod('validateStateAndCountry');
        $contact = new Connect\Contact();
        $statePath = explode('.', 'Contact.Address.StateOrProvince');
        $countryPath = explode('.', 'Contact.Address.Country');

        //Only a state is valid
        ConnectUtil::setFieldValue($contact, $statePath, 1);
        ConnectUtil::setFieldValue($contact, $countryPath, null);
        $this->assertNull($method($contact));

        //Only a country is valid
        ConnectUtil::setFieldValue($contact, $statePath, null);
        ConnectUtil::setFieldValue($contact, $countryPath, 1);
        $this->assertNull($method($contact));

        //A valid state and country combination
        ConnectUtil::setFieldValue($contact, $statePath, 1);
        ConnectUtil::setFieldValue($contact, $countryPath, 1);
        $this->assertNull($method($contact));

        //An invalid state and country
        ConnectUtil::setFieldValue($contact, $statePath, 62);
        ConnectUtil::setFieldValue($contact, $countryPath, 1);
        $this->assertIsA($method($contact), 'string');
    }

    function addEmailToContact(Connect\Contact $contact, $emailAddress, $index = 0) {
        $email = new Connect\Email();
        $email->Address = $emailAddress;
        $email->AddressType->ID = $index;
        $contact->Emails[] = $email;
    }

    function testSendResetPasswordEmail() {
        $getPassword = function($login) {
            $si = Api::sql_prepare("SELECT password_hash FROM contacts WHERE login='$login'");
            Api::sql_bind_col($si, 1, BIND_BIN, 61);
            $row = Api::sql_fetch($si);
            Api::sql_free($si);
            return $row[0];
        };

        $response = $this->model->sendResetPasswordEmail(1);
        $this->assertIsA($response, 'RightNow\Libraries\ResponseObject');
        $this->assertIsA($response->result, 'array');
        $this->assertIsA($response->result['message'], 'string');
        $this->assertSame(1, count($response->errors));

        $response = $this->model->sendResetPasswordEmail('basdfasdfsdfas');
        $this->assertIsA($response, 'RightNow\Libraries\ResponseObject');
        $this->assertIsA($response->result, 'array');
        $this->assertIsA($response->result['message'], 'string');
        $this->assertSame(1, count($response->errors));

        $contactLogin = 'YGsmjrpmuu@sol.invalid';
        $response = $this->model->sendResetPasswordEmail($contactLogin);
        $this->assertIsA($response, 'RightNow\Libraries\ResponseObject');
        $this->assertIsA($response->result, 'array');
        $this->assertIsA($response->result['message'], 'string');
        $this->assertSame(0, count($response->errors));
        // password reset will _not_ immediately invalidate the password by setting it to '*'
        $this->assertNotIdentical('*', $getPassword($contactLogin));
        $this->assertNotNull(Api::sql_get_dttm("SELECT password_email_exp FROM contacts WHERE login='$contactLogin'"));

        // Abuse
        $this->setIsAbuse();
        $expected = \RightNow\Utils\Config::getMessage(REQUEST_PERFORMED_SITE_ABUSIVE_MSG);
        $response = $this->model->sendResetPasswordEmail($contactLogin);
        $this->assertEqual($expected, $response->errors[0]->externalMessage);
        $this->clearIsAbuse();

        // Invalid email is made valid
        $contactLogin = 'JBthiujh@iekv.invalid';
        $contact = Connect\ROQL::queryObject("SELECT Contact FROM Contact where Login='$contactLogin'")->next()->next();
        $contact->Emails[0]->Invalid = true;
        $contact->save();
        $response = $this->model->sendResetPasswordEmail($contactLogin);
        $this->assertIsA($response, 'RightNow\Libraries\ResponseObject');
        $this->assertIsA($response->result, 'array');
        $this->assertIsA($response->result['message'], 'string');
        $this->assertSame(0, count($response->errors));
        $this->assertFalse($contact->Emails[0]->Invalid);
        // password reset will _not_ immediately invalidate the password by setting it to '*'
        $this->assertNotIdentical('*', $getPassword($contactLogin));
        $this->assertNotNull(Api::sql_get_dttm("SELECT password_email_exp FROM contacts WHERE login='$contactLogin'"));

        // Stop resetting password within Expired time span
        $contactLogin = 'platinumorg';
        $contact = Connect\ROQL::queryObject("SELECT Contact FROM Contact where Login='$contactLogin'")->next()->next();
        $contact->ResetPassword();
        $firstReset = Api::sql_get_dttm("SELECT password_email_exp FROM contacts WHERE login='$contactLogin'");
        $response = $this->model->sendResetPasswordEmail($contactLogin);
        $this->assertSame($firstReset, Api::sql_get_dttm("SELECT password_email_exp FROM contacts WHERE login='$contactLogin'"));

        // Disabled contact isn't enabled and password isn't reset
        $contactLogin = 'platinumorg';
        $contact = Connect\ROQL::queryObject("SELECT Contact FROM Contact where Login='$contactLogin'")->next()->next();
        $contact->Disabled = true;
        $contact->save();
        $response = $this->model->sendResetPasswordEmail($contactLogin);
        $this->assertIsA($response, 'RightNow\Libraries\ResponseObject');
        $this->assertIsA($response->result, 'array');
        $this->assertIsA($response->result['message'], 'string');
        $this->assertSame(0, count($response->errors));
        $this->assertTrue($contact->Disabled);
        $this->assertTrue('*' !== $getPassword($contactLogin));
    }

    function testEnforcePasswordInterval() {
        if (!class_exists('\RightNow\Models\MockContact')) {
            Mock::generate('\RightNow\Models\Contact', '\RightNow\Models\MockContact');
        }
        $model = new \ReflectionClass('RightNow\Models\MockContact');
        $instance = $model->newInstance();
        $method = $model->getMethod('enforcePasswordInterval');
        $method->setAccessible(true);
        $expect = $model->getMethod('expect');
        $expect->invoke($instance, 'get', array(12));
        $setReturn = $model->getMethod('returns');
        // TK
        // No expiration

        // Expiration but hasn't expired yet and not in warn period

        // Expiration and within warn period

        // Expired
    }

    function testProcessOrganizationCredentials(){
        $method = $this->getMethod('processOrganizationCredentials');

        $formData = array();
        $this->assertNull($method($formData));

        $formData = array('Contact.Login' => (object)array('value' => 'test'));
        $this->assertNull($method($formData));

        $formData = array('Contact.Organization.NewPassword' => (object)array('value' => 'test'));
        $this->assertNull($method($formData));

        $formData = array('Contact.Organization.Login' => (object)array('value' => 'test'), 'Contact.Organization.NewPassword' => (object)array('value' => 'test'));
        $this->assertFalse($method($formData));

        $formData = array('Contact.Organization.Login' => (object)array('value' => 'test'), 'Contact.Organization.NewPassword' => (object)array('value' => 'test'));
        $this->assertFalse($method($formData));

        $password = Api::pw_rev_encrypt('ravipassword');
        Api::test_sql_exec_direct("update orgs set login='ravi', password_encrypt='$password' where name='Ravisonix'");
        Api::test_sql_exec_direct("update orgs set login='domo' where name='Pepperdomo'");

        $formData = array('Contact.Organization.Login' => (object)array('value' => 'ravi'), 'Contact.Organization.NewPassword' => (object)array('value' => 'ravipassword'));
        $this->assertIsA($method($formData), 'int');

        $formData = array('Contact.Organization.Login' => (object)array('value' => 'domo'));
        $this->assertIsA($method($formData), 'int');

        $formData = array('Contact.Organization.Login' => (object)array('value' => 'domo'), 'Contact.Organization.NewPassword' => (object)array('value' => ''));
        $this->assertIsA($method($formData), 'int');


        Api::test_sql_exec_direct("update orgs set login=null, password_encrypt=null where name='Ravisonix'");
        Api::test_sql_exec_direct("update orgs set login=null where name='Pepperdomo'");
    }

    function testDoLogin() {
        $sessionID = $this->CI->session->getSessionData('sessionID');

        $this->setMockSession();
        $cpLoginStart = $_COOKIE['cp_login_start'];
        unset($_COOKIE['cp_login_start']);

        // Unacceptable session state
        // NOTE - This call to doLogin appears to cause sporadic Internal Server Errors when
        // attempting to run this test file on its own. If you are running into this on your site
        // comment out the following line. Please uncomment before committing any changes.
        $expectedMessage = Config::getMessage(PLEASE_ENABLE_COOKIES_BROWSER_LOG_MSG);
        $result = $this->model->doLogin('foo', 'bar', 'session', 'bananas', 'no')->result;
        $this->assertIdentical('bananas', $result['w_id']);
        $this->assertIdentical(0, $result['success']);
        $this->assertIdentical($expectedMessage, $result['message']);
        $this->assertFalse($result['showLink']);
        $this->assertFalse(array_key_exists('url', $result));

        // Too long of password
        $expectedMessage = sprintf(Config::getMessage(PASSWD_ENTERED_EXCEEDS_MAX_CHARS_MSG), 20);
        $this->CI->session->setReturnValue('canSetSessionCookies', true);
        $this->CI->session->returns('getSessionData', true, array('cookiesEnabled'));
        $result = $this->model->doLogin('slatest', str_repeat('a', 21), $sessionID, 'bananas', 'no')->result;
        $this->assertIdentical('bananas', $result['w_id']);
        $this->assertIdentical(0, $result['success']);
        $this->assertIdentical($expectedMessage, $result['message']);
        $this->assertFalse(array_key_exists('showLink', $result));
        $this->assertFalse(array_key_exists('url', $result));


        // Bad login
        $expectedMessage = Config::getMessage(USERNAME_PASSWD_ENTERED_INCOR_ACCT_MSG);
        // The username or password you entered is incorrect or your account has been disabled.
        $result = $this->model->doLogin('slatest', 'a', $sessionID, 'bananas', 'no')->result;
        $this->assertIdentical('bananas', $result['w_id']);
        $this->assertIdentical('no', $result['url']);
        $this->assertIdentical(0, $result['success']);
        $this->assertIdentical($expectedMessage, $result['message']);
        $this->assertFalse(array_key_exists('showLink', $result));
        $this->assertFalse($result['addSession']);
        $this->assertIdentical('', $result['sessionParm']);

        // Display the 'site limit' variation error message when CP_MAX_LOGINS set
        $expectedMessage = Config::getMessage(USRNAME_PASSWD_ENTERED_INCOR_ACCT_MSG);
        // The username or password you entered is incorrect, your account has been disabled, or a site limit has been reached.
        \Rnow::updateConfig('CP_MAX_LOGINS', 1, true);
        $result = $this->model->doLogin('slatest', 'a', $sessionID, 'pineapples', 'no')->result;
        $this->assertIdentical(0, $result['success']);
        $this->assertIdentical($expectedMessage, $result['message']);
        \Rnow::updateConfig('CP_MAX_LOGINS', 0, true);

        // Display the 'site limit' variation error message when CP_MAX_LOGINS_PER_CONTACT set
        \Rnow::updateConfig('CP_MAX_LOGINS_PER_CONTACT', 1, true);
        $result = $this->model->doLogin('slatest', 'a', $sessionID, 'mangos', 'no')->result;
        $this->assertIdentical(0, $result['success']);
        $this->assertIdentical($expectedMessage, $result['message']);
        \Rnow::updateConfig('CP_MAX_LOGINS_PER_CONTACT', 0, true);

        // Success
        $expectedMessage = Config::getMessage(REDIRECTING_ELLIPSIS_MSG);
        $this->CI->session->setReturnValue('createMapping', (object) array('bananas' => true));
        $result = $this->model->doLogin('slatest', '', $sessionID, 'bananas', '')->result;
        $this->assertIdentical('bananas', $result['w_id']);
        $this->assertIdentical('/app/home', $result['url']);
        $this->assertIdentical(1, $result['success']);
        $this->assertIdentical($expectedMessage, $result['message']);
        $this->assertFalse(array_key_exists('showLink', $result));
        $this->assertFalse($result['addSession']);
        $this->assertIdentical('', $result['sessionParm']);

        $this->unsetMockSession();
        if ($cpLoginStart)
            $_COOKIE['cp_login_start'] = $cpLoginStart;
    }

    function testDoLogout(){
        $sessionID = $this->CI->session->getSessionData('sessionID');

        $this->setMockSession();
        // The session parameter is null when run from a browser, but '' when run from command line.
        // doLogout() does a strict check for empty string, so results vary between methods.
        // sessionParameter() should probably be changed to return '' in both situations.
        $sessionParameter = \RightNow\Utils\Url::sessionParameter();

        $input = $expected = '/app/home';
        if ($sessionParameter === null) {
            $expected = "$input/session/";
        }
        $response = $this->model->doLogout($input);
        $this->assertIsA($response, 'RightNow\Libraries\ResponseObject');
        $this->assertTrue(is_array($response->result));
        $this->assertIdentical($expected, $response->result['url']);
        $this->assertIdentical(1, $response->result['success']);

        $input = '/app/home/sno/5';
        $sessionParam = 'session/bananaSession';
        $expected = ($sessionParameter === null) ? "/app/home/$sessionParam" : '/app/home';
        $this->CI->session->returns('getSessionData', $sessionParam);
        $response = $this->model->doLogout($input);
        $this->assertIdentical($expected, $response->result['url']);
        $this->assertIdentical(1, $response->result['success']);

        $this->CI->session->returns('getSessionData', true, array('cookiesEnabled'));
        $this->CI->session->returns('canSetSessionCookies', true);
        $this->model->doLogin('slatest', '', $sessionID, '', '');
        $response = $this->model->doLogout('/app/home');
        $this->assertIdentical('/app/home', $response->result['url']);
        $this->assertIdentical(1, $response->result['success']);

        // TK - Seg fault city when invalid strings are passed to #contact_logout...?
        // \Rnow::updateConfig('COMMUNITY_ENABLED', 1, true);
        // \Rnow::updateConfig('COMMUNITY_BASE_URL', 'www.socialsite.com', true);
        // $this->CI->session->returns('isLoggedIn', true);
        // $this->CI->session->returns('getSessionData', $sessionID);
        // $this->CI->session->returns('getProfileData', 'authToken');
        // $response = $this->model->doLogout('/app/answers/list', '/app/home');
        // $this->assertIdentical('/app/answers/list', $response->result['url']);
        // // echo "<br><pre>"; print_r($response->result); echo "</pre>";
        // $this->assertIdentical(1, $response->result['success']);
        // $this->assertTrue(Text::beginsWith($response->result['socialLogout'], 'www.socialsite.com/scripts/signout?redirectUrl=http'));
        // $this->assertTrue(Text::stringContains($response->result['socialLogout'], urlencode('/app/home')));
        // // $this->model->doLogin('slatest', '', $sessionID, '', '');
        // $response = $this->model->doLogout('/app/answers/list', 'http://www.someothersite.com/logout');
        // $this->assertIdentical('/app/answers/list', $response->result['url']);
        // $this->assertIdentical(1, $response->result['success']);
        // $this->assertTrue(Text::beginsWith($response->result['socialLogout'], 'www.socialsite.com/scripts/signout?redirectUrl=http'));
        // $this->assertTrue(Text::stringContains($response->result['socialLogout'], urlencode('http://www.someothersite.com/logout')));

        // \Rnow::updateConfig('COMMUNITY_ENABLED', 0, true);
        // \Rnow::updateConfig('COMMUNITY_BASE_URL', '', true);

        $this->unsetMockSession();
    }

    function testGetProfileSid(){
        $sessionID = $this->CI->session->getSessionData('sessionID');

        $response = $this->model->getProfileSid(null, null, $sessionID);
        $this->assertIsA($response, 'RightNow\Libraries\ResponseObject');
        $this->assertNull($response->result);
        $this->assertTrue(is_array($response->errors));
        $this->assertIdentical(1, count($response->errors));

        $response = $this->model->getProfileSid(false, null, $sessionID);
        $this->assertNull($response->result);
        $this->assertTrue(is_array($response->errors));
        $this->assertIdentical(1, count($response->errors));

        $response = $this->model->getProfileSid('slatest', null, $sessionID);
        $this->assertIsA($response, 'RightNow\Libraries\ResponseObject');
        $this->assertIsA($response->result, 'RightNow\Libraries\ProfileData');
        $this->assertIdentical('slatest', $response->result->login);

        $this->model->doLogout('/app/home');

        // Abuse
        $this->setIsAbuse();
        $expected = \RightNow\Utils\Config::getMessage(REQUEST_PERFORMED_SITE_ABUSIVE_MSG);
        $response = $this->model->getProfileSid('slatest', null, $sessionID);
        $this->assertEqual($expected, $response->errors[0]->externalMessage);
        $this->model->doLogout('/app/home');
        $this->clearIsAbuse();

        //@@@ QA 130305-000139 Trim contact usernames prior to login
        $response = $this->model->getProfileSid('    slatest', null, $sessionID);
        $this->assertIsA($response, 'RightNow\Libraries\ResponseObject');
        $this->assertIsA($response->result, 'RightNow\Libraries\ProfileData');
        $this->assertIdentical('slatest', $response->result->login);

        $this->model->doLogout('/app/home');

        $response = $this->model->getProfileSid('slatest    ', null, $sessionID);
        $this->assertIsA($response, 'RightNow\Libraries\ResponseObject');
        $this->assertIsA($response->result, 'RightNow\Libraries\ProfileData');
        $this->assertIdentical('slatest', $response->result->login);

        $this->model->doLogout('/app/home');

        $response = $this->model->getProfileSid('    slatest    ', null, $sessionID);
        $this->assertIsA($response, 'RightNow\Libraries\ResponseObject');
        $this->assertIsA($response->result, 'RightNow\Libraries\ProfileData');
        $this->assertIdentical('slatest', $response->result->login);

        $this->model->doLogout('/app/home');

        $response = $this->model->getProfileSid('slatest', 'abc', $sessionID);
        $this->assertIsA($response, 'RightNow\Libraries\ResponseObject');
        $this->assertNull($response->result);
        $this->assertTrue(is_array($response->errors));
        $this->assertIdentical(1, count($response->errors));
    }

    function testDuplicateEmailSet() {
        $this->setDuplicateEmailSetting(true);
        $result = $this->makeRequest("/ci/unitTest/wgetRecipient/testModel/Contact/duplicateEmailSet");
        $this->assertSame('', $result);
    }

    function duplicateEmailSet() {
        $email = "james.watson@rightnow.com.invalid.test";
        $response = $this->model->create(array(
            'Contact.Emails.0.Address' => (object) array('value' => $email),
        ));
        $firstContact = $response->result;
        $this->assertIsA($firstContact, CONNECT_NAMESPACE_PREFIX . '\Contact');

        // creates
        $response = $this->model->create(array(
            'Contact.Emails.0.Address' => (object) array('value' => $email),
        ));
        $this->assertNull($response->result);
        $this->assertSame(1, count($response->errors));
        $this->assertTrue(Text::stringContains($response->errors[0] . '', sprintf(\RightNow\Utils\Config::getMessage(EXING_ACCT_EMAIL_ADDR_PCT_S_PLS_MSG), $email)));

        $response = $this->model->create(array(
            'Contact.Emails.0.Address' => (object) array('value' => "james.watson@rightnow.com.invalid.test2"),
            'Contact.Emails.1.Address' => (object) array('value' => $email),
        ));
        $this->assertNull($response->result);
        $this->assertSame(1, count($response->errors));
        $this->assertTrue(Text::stringContains($response->errors[0] . '', sprintf(\RightNow\Utils\Config::getMessage(EXING_ACCT_EMAIL_ADDR_PCT_S_PLS_MSG), $email)));

        // updates
        $response = $this->model->update(1, array(
            'Contact.Name.First' => (object) array('value' => 'first'),
            'Contact.Name.Last' => (object) array('value' => 'last'),
            'Contact.Login' => (object) array('value' => 'eturner@rightnow.com.invalid'),
            'Contact.Emails.0.Address' => (object) array('value' => "something@different.com.invalid"),
            'Contact.Emails.1.Address' => (object) array('value' => $email),
        ));
        $this->assertNull($response->result);
        $this->assertSame(1, count($response->errors));
        $this->assertTrue(Text::stringContains($response->errors[0] . '', \RightNow\Utils\Config::getMessage(EXISTING_ACCT_USERNAME_PLS_ENTER_MSG)));

        // create with proper password flag set
        $response = $this->model->create(array(
            'Contact.Emails.0.Address' => (object) array('value' => $email),
            'Contact.Name.First' => (object) array('value' => 'first'),
            'Contact.Name.Last' => (object) array('value' => 'last'),
            'Contact.Login' => (object) array('value' => microtime(true)),
            'Contact.NewPassword' => (object) array('value' => 'password'),
            'ResetPasswordProcess' => (object) array('value' => true),
        ));
        $secondContact = $response->result;
        $this->assertIsA($secondContact, CONNECT_NAMESPACE_PREFIX . '\Contact');
        $this->assertSame($firstContact->Emails[0]->Address, $secondContact->Emails[0]->Address);
        $this->destroyObject($secondContact);
        $this->destroyObject($firstContact);
    }

    function testDuplicateEmailNotSet() {
        $this->setDuplicateEmailSetting(false);
        $result = $this->makeRequest("/ci/unitTest/wgetRecipient/testModel/Contact/duplicateEmailNotSet");
        $this->assertSame('', $result);
    }

    function duplicateEmailNotSet() {
        $email = "james.watson@rightnow.com.invalid.test";
        $response = $this->model->create(array(
            'Contact.Emails.0.Address' => (object) array('value' => $email),
        ));
        $firstContact = $response->result;
        $this->assertIsA($firstContact, CONNECT_NAMESPACE_PREFIX . '\Contact');

        // creates
        $response = $this->model->create(array(
            'Contact.Emails.0.Address' => (object) array('value' => $email),
        ));
        $this->assertNull($response->result);
        $this->assertSame(1, count($response->errors));
        $this->assertTrue(Text::stringContains($response->errors[0]->externalMessage, sprintf(\RightNow\Utils\Config::getMessage(EXING_ACCT_EMAIL_ADDR_PCT_S_PLS_MSG), $email)));

        $response = $this->model->create(array(
            'Contact.Emails.0.Address' => (object) array('value' => "james.watson@rightnow.com.invalid.test2"),
            'Contact.Emails.1.Address' => (object) array('value' => $email),
        ));
        $this->assertNull($response->result);
        $this->assertSame(1, count($response->errors));
        $this->assertTrue(Text::stringContains($response->errors[0]->externalMessage, sprintf(\RightNow\Utils\Config::getMessage(EXING_ACCT_EMAIL_ADDR_PCT_S_PLS_MSG), $email)));

        // updates
        $response = $this->model->update(1, array(
            'Contact.Name.First' => (object) array('value' => 'first'),
            'Contact.Name.Last' => (object) array('value' => 'last'),
            'Contact.Emails.0.Address' => (object) array('value' => "something@different.com.invalid"),
            'Contact.Emails.1.Address' => (object) array('value' => $email),
        ));
        $this->assertNull($response->result);
        $this->assertSame(1, count($response->errors));
        $this->assertTrue(Text::stringContains($response->errors[0]->externalMessage, sprintf(\RightNow\Utils\Config::getMessage(EXING_ACCT_EMAIL_ADDR_PCT_S_PLS_MSG), $email)));

        // create with password flag set
        $response = $this->model->create(array(
            'Contact.Emails.0.Address' => (object) array('value' => $email),
            'Contact.Name.First' => (object) array('value' => 'first'),
            'Contact.Name.Last' => (object) array('value' => 'last'),
            'Contact.Login' => (object) array('value' => microtime(true)),
            'Contact.NewPassword' => (object) array('value' => 'password'),
            'ResetPasswordProcess' => (object) array('value' => true),
        ));
        $this->assertNull($response->result);
        $this->assertSame(1, count($response->errors));
        $this->assertTrue(Text::stringContains($response->errors[0]->externalMessage, sprintf(\RightNow\Utils\Config::getMessage(EXING_ACCT_EMAIL_ADDR_PCT_S_PLS_MSG), $email)));

        $this->destroyObject($firstContact);
    }

    function testContactCreateHooks() {
        $create = function($login) {
            $login .= microtime(true);
            $response = get_instance()->model('Contact')->create(array(
                'Contact.Login' => (object) array('value' => $login),
                'Contact.Name.First' => (object) array('value' => 'someGuyFirst'),
                'Contact.Name.Last' => (object) array('value' => 'someGuyLast'),
            ));
            return array($login, $response);
        };

        $expectedObject = CONNECT_NAMESPACE_PREFIX . '\Contact';

        $hookName = 'pre_contact_create';
        $this->setHook($hookName, array($hookName));
        list($login) = $create($hookName);
        $this->assertIsA(self::$hookData['data'], $expectedObject);
        $this->assertEqual($login, self::$hookData['data']->Login);

        // If pre_contact_create returns a string, the contact should not get created
        $this->setHook($hookName, array($hookName), 'hookError');
        list($login, $response) = $create('contactShouldNotGetCreated');
        $this->assertIsA($response, 'RightNow\Libraries\ResponseObject');
        $this->assertNull($response->result);
        $this->assertIdentical(1, count($response->errors));
        $this->assertIdentical(self::$hookErrorMsg, $response->errors[0]->externalMessage);
        $this->assertIdentical(0, count($response->warnings));
        $this->assertFalse(\RightNow\Api::sql_get_int("SELECT c_id FROM contacts WHERE login='$login'"));

        $hookName = 'post_contact_create';
        $this->setHook($hookName, array($hookName));
        list($login) = $create($hookName);
        $this->assertIsA(self::$hookData['data'], $expectedObject);
        $this->assertEqual($login, self::$hookData['data']->Login);
    }

    function testContactUpdateHooks() {
        $update = function($login, $addTimestamp = true) {
            $login .= $addTimestamp ? microtime(true) : '';
            $response = get_instance()->model('Contact')->update(1, array('Contact.Login' => (object) array('value' => $login)));
            return array($login, $response);
        };

        $getLogin = function() {
            return \RightNow\Api::sql_get_str("SELECT login FROM contacts WHERE c_id=1", 20);
        };

        $originalLogin = $getLogin();
        $expectedObject = CONNECT_NAMESPACE_PREFIX . '\Contact';
        $this->setMockSession();

        $hookName = 'pre_contact_update';
        $this->setHook($hookName, array($hookName));
        list($login) = $update($hookName);
        $this->assertIsA(self::$hookData['data'], $expectedObject);
        $this->assertEqual($login, self::$hookData['data']->Login);

        // If pre_contact_update returns a string, the contact should not get updated
        $this->setHook($hookName, array($hookName), 'hookError');
        list($login, $response) = $update('contactShouldNotGetUpdated');
        $this->assertIsA($response, 'RightNow\Libraries\ResponseObject');
        $this->assertNull($response->result);
        $this->assertIdentical(1, count($response->errors));
        $this->assertIdentical(self::$hookErrorMsg, $response->errors[0]->externalMessage);
        $this->assertIdentical(0, count($response->warnings));
        $this->assertNotEqual($login, $getLogin());

        $hookName = 'post_contact_update';
        $this->setHook($hookName, array($hookName));
        list($login) = $update($hookName);
        $this->assertIsA(self::$hookData['data'], $expectedObject);
        $this->assertEqual($login, self::$hookData['data']->Login);

        $update($originalLogin, false);
        $this->unsetMockSession();
    }

    function testLoginHooks() {
        $CI = $this->CI;
        $model = $this->model;
        $sessionID = $this->CI->session->getSessionData('sessionID');
        $test = function() use ($CI, $model, $sessionID) {
            $CI->session->setReturnValue('canSetSessionCookies', true);
            $CI->session->returns('getSessionData', true, array('cookiesEnabled'));
            $CI->session->setReturnValue('createMapping', (object) array('widgetID' => true));
            $response = $model->doLogin('slatest', '', $sessionID, 'widgetID', '');
            return $response;
        };

        $this->setMockSession();

        $hookName = 'pre_login';
        $this->setHook($hookName, array($hookName));
        $test();
        $this->assertEqual(array('source' => 'LOCAL'), self::$hookData['data']);

        // If pre_login returns a string, the login should not happen
        $this->setHook($hookName, array($hookName), 'hookError');
        $response = $test();
        $this->assertIsA($response, 'RightNow\Libraries\ResponseObject');
        $this->assertIdentical(self::$hookErrorMsg, $response->result['message']);

        $hookName = 'post_login';
        $this->setHook($hookName, array($hookName));
        $test();
        $this->assertEqual(array('source' => 'LOCAL'), self::$hookData['data']);

        $this->unsetMockSession();
    }

    function testLogoutHooks() {
        $makeRequest = function($hookName) {
            return json_decode(TestHelper::makeRequest("/ci/unitTest/wgetRecipient/invokeTestMethod/"
                . urlencode(__FILE__) . "/" . __CLASS__ . "/callLogout/$hookName"));
        };

        $expectedMessage = 'Exiting logout before the redirect happens.';

        $hookName = 'pre_logout';
        $output = $makeRequest($hookName);
        $this->assertEqual($expectedMessage, $output->message);
        $this->assertIdentical(array(), $output->hookData);
        $this->setHook($hookName, array());

        $hookName = 'post_logout';
        $output = $makeRequest($hookName);
        $this->assertEqual($expectedMessage, $output->message);
        $this->assertIdentical(0, $output->hookData->returnValue);
    }

    function testVerifyPasswordChange() {
        $method = $this->getMethod('verifyPasswordChange');
        $contact = $this->model->getBlank()->result;

        $this->assertNull($method(null, $contact));
        $this->assertNull($method((object) array('value' => 'knee'), $contact));

        $result = $method((object) array('currentValue' => str_repeat('a', 21)), $contact);
        $this->assertStringContains($result, 'Current Password');
        $this->assertStringContains($result, 'by 1 character');

        $contact = $this->model->get(1)->result;
        $result = $method((object) array('currentValue' => str_repeat('a', 20)), $contact);
        $this->assertStringContains($result, "doesn't match");

        $this->logIn($contact->Login, array('openLoginUsed' => array('provider' => 'twitter')));
        $result = $method((object) array('currentValue' => str_repeat('a', 20)), $contact);
        $this->assertStringContains($result, "doesn't match");
        $this->logout();

        $contact = $this->model->get(1293)->result;
        $result = $method((object) array('currentValue' => str_repeat('a', 20)), $contact);
        $this->assertStringContains($result, "doesn't match");

        $this->logIn($contact->Login, array('openLoginUsed' => array('provider' => 'twitter')));
        $result = $method((object) array('currentValue' => str_repeat('a', 20)), $contact);
        $this->assertStringContains($result, "current password is not currently set");
        $this->logout();

        $this->logIn($contact->Login, array('openLoginUsed' => array('provider' => 'twitter')));
        $result = $method((object) array('value' => str_repeat('a', 20)), $contact);
        $this->assertStringContains($result, "doesn't match");
        $this->logout();
    }

    function testReplaceErrorMessages() {
        $method = $this->getMethod('replaceErrorMessages');

        // Presently, only email related messages should be updated
        $unchangedMessage = 'Heat not a furnace for your foe so hot that it do singe yourself.';
        $expectedMessage = sprintf(Config::getMessage(PCT_S_IS_INVALID_MSG), Config::getMessage(EMAIL_ADDR_LBL));

        $messages = $method(array(
            "Pattern does not match: value '080-1805-20xx.@docomo.ne.jp' does not match pattern ''; Contact.Emails[0].Address",
            "Pattern does not match: value '*dafsdklj???A3[[[' does not match pattern ''; Contact.Emails[1].Address",
            $unchangedMessage
        ));

        $this->assertSame($messages[0], $expectedMessage);
        $this->assertSame($messages[1], $expectedMessage);
        $this->assertSame($messages[2], $unchangedMessage);
    }

    function testGetForSocialUser() {
        // should get an error for invalid input
        $response = $this->model->getForSocialUser('guava');
        $this->assertSame(1, count($response->errors));

        $response = $this->model->getForSocialUser(9999999);
        $this->assertSame(1, count($response->errors));

        // Create contact and social user
        $contact = $this->assertResponseObject($this->model->create(array(
            'Contact.Login' => (object) array('value' => 'Login' . __FUNCTION . rand()),
            'Contact.Name.First' => (object) array('value' => 'First' . __FUNCTION),
            'Contact.Name.Last' => (object) array('value' => 'Last' . __FUNCTION),
        )))->result;

        $this->logIn();
        $socialUser = new Connect\CommunityUser();
        $socialUser->DisplayName = $contact->Login;
        $socialUser->Contact = $contact;
        $socialUser->save();
        Connect\ConnectAPI::commit();
        $this->assertFalse(empty($socialUser->ID), "CommunityUser should have been assigned an ID");

        // Retrieve contact from social user
        $response = $this->model->getForSocialUser($socialUser->ID);
        $this->assertResponseObject($response);
        $this->assertConnectObject($response->result, 'Contact');
        $this->assertSame($contact->ID, $response->result->ID, "Fetched wrong Contact");

        // clean up - delete the Contact and the CommunityUser (Note: this does not also delete the common_user row)
        $this->destroyObject($socialUser);
        $this->destroyObject($contact);
    }

    function callLogout() {
        $hookName = Text::getSubstringAfter(get_instance()->uri->uri_string(), 'callLogout/');
        $this->setHook($hookName, array(), 'logoutHookEndpoint', false);
        $this->logIn();
        $this->model->doLogout('/app/home');
    }

    function logoutHookEndpoint($data) {
        exit(json_encode(array('message' => 'Exiting logout before the redirect happens.',
            'hookData' => $data
        )));
    }

    private function getDuplicateEmailSetting() {
        return Api::site_config_int_get(CFG_OPT_DUPLICATE_EMAIL) === 1;
    }

    private function setDuplicateEmailSetting($setValue) {
        Api::test_sql_exec_direct(sprintf(
            "UPDATE site_config_int SET value = %s%d
            WHERE sci_id = %d",
            $setValue ? "value | " : "value & ~",
            CFG_OPT_DUPLICATE_EMAIL,
            SCI_OPTS
        ));
        Connect\ConnectAPI::commit();
    }
}
