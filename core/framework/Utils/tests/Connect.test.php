<?php

use RightNow\Connect\v1_4 as ConnectPHP,
    RightNow\Connect\Knowledge\v1 as KnowledgeFoundation,
    RightNow\Utils\Connect,
    RightNow\Utils\Text,
    RightNow\Api;

RightNow\UnitTest\Helper::loadTestedFile(__FILE__);

class ConnectTest extends CPTestCase {
    public $testingClass = 'RightNow\Utils\Connect';

    function __construct() {
        parent::__construct();
        $this->CI->model('Contact');
        $this->CI->model('Incident');
        $this->interfaceID = \RightNow\Api::intf_id();
    }

    function testIrrelevantHasPermission(){
        // an RNObject has a hasPermission method, but has no meaning
        $object = new ConnectPHP\RNObject;
        $this->assertFalse(Connect::hasPermission($object, PERM_SOCIALQUESTION_READ));

        // an Incident object has a hasPermission method, but nothing sensible to pass it
        $incident = new ConnectPHP\Incident;
        $this->assertFalse(Connect::hasPermission($incident, PERM_SOCIALQUESTION_READ));
    }

    function testHasPermission(){
        $object = new ConnectPHP\CommunityQuestion;
        $this->assertTrue(Connect::hasPermission($object, PERM_SOCIALQUESTION_READ));

        list($fixtureInstance, $question) = $this->getFixtures(array('QuestionActiveUserActive'));

        // non-author cannot update question
        $this->logIn('useractive1');
        $this->assertTrue(Connect::hasPermission($question, PERM_SOCIALQUESTION_READ));
        $this->assertFalse(Connect::hasPermission($question, PERM_SOCIALQUESTION_UPDATE));
        $this->logOut();

        // author can update their own question
        $this->logIn($question->CreatedByCommunityUser->Contact->Login);
        $this->assertTrue(Connect::hasPermission($question, PERM_SOCIALQUESTION_READ));
        $this->assertTrue(Connect::hasPermission($question, PERM_SOCIALQUESTION_UPDATE));
        $this->logOut();

        $fixtureInstance->destroy();
    }

    // 140808-000079 - CP: modify ConnectUtil::hasPermission() to support lookup names as well as IDs
    function testHasPermissionAcceptsMultipleTypes(){
        $object = new ConnectPHP\CommunityQuestion;

        // integers work
        $this->assertTrue(Connect::hasPermission($object, PERM_SOCIALQUESTION_READ));

        // strings work
        $this->assertTrue(Connect::hasPermission($object, 'CommunityQuestion.READ'));

        // named IDs work
        $namedID = new ConnectPHP\NamedID();
        $namedID->ID = PERM_SOCIALQUESTION_READ;
        $this->assertTrue(Connect::hasPermission($object, $namedID));
    }

    function testGetSupportObjects() {
        $allObjects = Connect::getSupportedObjects();
        $this->assertIsA($allObjects, 'array');
        $this->assertIdentical(9, count($allObjects));
        $this->assertIsA($allObjects['Answer'], 'string');
        $this->assertIdentical('read', $allObjects['Answer']);

        $socialQuestion = Connect::getSupportedObjects('Communityquestion');
        $this->assertIsA($socialQuestion, 'string');
        $this->assertIdentical('read,write', $socialQuestion);
    }

    function testRetrieveMetaData() {
        $objectName = 'Contact';
        $contactMetaData = Connect::retrieveMetaData($objectName);

        $this->assertNotNull($contactMetaData);
        $this->assertIsA($contactMetaData, CONNECT_NAMESPACE_PREFIX . '\_metadata');
        $this->assertIdentical($contactMetaData->type_name, CONNECT_NAMESPACE_PREFIX . '\Contact');
        $this->assertIdentical($contactMetaData->COM_type, 'Contact');

        $this->assertNull(Connect::retrieveMetaData(''));
        $this->assertNull(Connect::retrieveMetaData(null));
    }

    function testMapOldFieldName() {
        $this->assertIdentical('Contact.Name.First', Connect::mapOldFieldName('contacts.first_name'));
        $this->assertIdentical('Contact.Name.Last', Connect::mapOldFieldName('contacts.last_name'));
        $this->assertIdentical('Contact.NewPassword', Connect::mapOldFieldName('contacts.password_new'));
        $this->assertIdentical('Contact.asdf', Connect::mapOldFieldName('contacts.asdf'));

        $this->assertIdentical('Answer.ID', Connect::mapOldFieldName('answers.a_id'));
        $this->assertIdentical('Answer.Language', Connect::mapOldFieldName('answers.lang_id'));
        $this->assertIdentical('Answer.AnswerType', Connect::mapOldFieldName('answers.type'));
        $this->assertIdentical('Answer.GuidedAssistance.ID', Connect::mapOldFieldName('answers.guideID'));

        $this->assertIdentical('Incident.PrimaryContact.ID', Connect::mapOldFieldName('incidents.c_id'));
        $this->assertIdentical('Incident.ReferenceNumber', Connect::mapOldFieldName('incidents.ref_no'));
        $this->assertIdentical('Incident.CreatedTime', Connect::mapOldFieldName('incidents.created'));
        $this->assertIdentical('Incident.StatusWithType.Status.ID', Connect::mapOldFieldName('incidents.status_id'));
        $this->assertIdentical('Incident.SLAInstance', Connect::mapOldFieldName('incidents.sla'));

        $this->assertIdentical('Contact.ChannelUsernames.TWITTER.Username', Connect::mapOldFieldName('contacts.channel$11'));
        $this->assertIdentical('Contact.ChannelUsernames.FACEBOOK.Username', Connect::mapOldFieldName('contacts.channel$14'));
        $this->assertIdentical('Contact.ChannelUsernames.YOUTUBE.Username', Connect::mapOldFieldName('contacts.channel$12'));

        $this->assertIdentical('contacts2.asdf', Connect::mapOldFieldName('contacts2.asdf'));
        $this->assertIdentical('ans.a_id', Connect::mapOldFieldName('ans.a_id'));
        $this->assertIdentical('foo.bar', Connect::mapOldFieldName('foo.bar'));
    }

    function testMapNewFieldName() {
        $this->assertIdentical('contacts.first_name', Connect::mapNewFieldName('Contact.Name.First'));
        $this->assertIdentical('contacts.last_name', Connect::mapNewFieldName('Contact.Name.Last'));
        $this->assertIdentical('contacts.password_new', Connect::mapNewFieldName('Contact.NewPassword'));
        $this->assertNull(Connect::mapNewFieldName('Contact.asdf'));

        $this->assertIdentical('answers.a_id', Connect::mapNewFieldName('Answer.ID'));
        $this->assertIdentical('answers.lang_id', Connect::mapNewFieldName('Answer.Language'));
        $this->assertIdentical('answers.type', Connect::mapNewFieldName('Answer.AnswerType'));
        $this->assertIdentical('answers.guideID', Connect::mapNewFieldName('Answer.GuidedAssistance.ID'));

        $this->assertIdentical('incidents.c_id', Connect::mapNewFieldName('Incident.PrimaryContact.ID'));
        $this->assertIdentical('incidents.ref_no', Connect::mapNewFieldName('Incident.ReferenceNumber'));
        $this->assertIdentical('incidents.created', Connect::mapNewFieldName('Incident.CreatedTime'));
        $this->assertIdentical('incidents.status_id', Connect::mapNewFieldName('Incident.StatusWithType.Status.ID'));

        $this->assertIdentical('incidents.c$text', Connect::mapNewFieldName('Incident.CustomFields.c.text'));
        $this->assertIdentical('incidents.c$priority', Connect::mapNewFieldName('Incident.CustomFields.c.priority'));
        $this->assertIdentical('contacts.c$pets_name', Connect::mapNewFieldName('Contact.CustomFields.c.pets_name'));
        $this->assertIdentical('contacts.c$url', Connect::mapNewFieldName('Contact.CustomFields.c.url'));

        $this->assertIdentical('contacts.channel$11', Connect::mapNewFieldName('Contact.ChannelUsernames.TWITTER.Username'));
        $this->assertIdentical('contacts.channel$14', Connect::mapNewFieldName('Contact.ChannelUsernames.FACEBOOK.Username'));
        $this->assertIdentical('contacts.channel$12', Connect::mapNewFieldName('Contact.ChannelUsernames.YOUTUBE.Username'));

        $this->assertIdentical('incidents.sla', Connect::mapNewFieldName('Incident.SLAInstance'));

        $this->assertNull(Connect::mapNewFieldName('foo.bar'));
    }

    function testParseFieldName() {
        $this->assertIsA(Connect::parseFieldName('Banana.foo'), 'string');
        $this->assertIsA(Connect::parseFieldName(''), 'string');
        $this->assertIsA(Connect::parseFieldName(null), 'string');
        $this->assertIsA(Connect::parseFieldName(0), 'string');
        $this->assertIsA(Connect::parseFieldName(false), 'string');
        $this->assertIsA(Connect::parseFieldName(array()), 'string');

        // parseFieldName is only concerned about object and field. Sub-fields are neither looked at nor validated in this method.
        // Incorrect sub-field name errors are reported when attempting to retrieve the field's value.
        $this->assertIdentical(array('Contact', 'Name', 'first'), Connect::parseFieldName('contact.name.first', true));
        $this->assertIdentical(array('Contact', 'Name', 'First'), Connect::parseFieldName('Contact.Name.First', true));
        $this->assertIdentical(array('Contact', 'Name', 'First'), Connect::parseFieldName('Contact.Name.First', false));

        $this->assertIdentical(array('Incident', 'FileAttachments', '0', 'id'), Connect::parseFieldName('incident.fileAttachments.0.id', true));
        $this->assertIdentical(array('Incident', 'Subject'), Connect::parseFieldName('Incident.Subject', true));
        $this->assertIdentical(array('Incident', 'Subject'), Connect::parseFieldName('Incident.Subject', false));

        $this->assertIdentical(array('Communityquestion', 'Name', 'First'), Connect::parseFieldName('CommunityQuestion.Name.First', true));
        $this->assertIdentical(array('Communityquestion', 'Name', 'First'), Connect::parseFieldName('CommunityQuestion.Name.First', false));

        $this->assertIsA(Connect::parseFieldName('Answer.Summary', true), 'string');
        $this->assertIdentical(array('Answer', 'Summary'), Connect::parseFieldName('Answer.Summary'));

        $this->assertIdentical("'Incident2' is an invalid value for the 'name' attribute. [Answer, Asset, Communitycomment, Communityquestion, Communityuser, Contact, Incident, Servicecategory, Serviceproduct] are the supported values",
            Connect::parseFieldName('Incident2.Product'));
        $this->assertIdentical("'Incident2' is an invalid value for the 'name' attribute. [Asset, Communitycomment, Communityquestion, Communityuser, Contact, Incident] are the supported values",
            Connect::parseFieldName('Incident2.Product', true));
        $this->assertIdentical(array('Answer', 'Product'),
            Connect::parseFieldName('Answer.Product'));
        $this->assertIdentical("'Answer' is an invalid value for the 'name' attribute. [Asset, Communitycomment, Communityquestion, Communityuser, Contact, Incident] are the supported values",
            Connect::parseFieldName('Answer.Product', true));
    }

    function testGetObjectInstance() {
        $this->assertNull(Connect::getObjectInstance(null));
        $this->assertTrue(Connect::getObjectInstance('Contact') instanceof ConnectPHP\Contact);
        $this->assertIdentical(null, Connect::getObjectInstance('Answer'));

        $this->addUrlParameters(array('a_id' => 52));
        $this->assertTrue(Connect::getObjectInstance('Answer') instanceof KnowledgeFoundation\AnswerContent);
        $this->restoreUrlParameters();

        $this->assertTrue(Connect::getObjectInstance('INCIDENT') instanceof ConnectPHP\Incident);

        $this->assertNull(Connect::getObjectInstance('bananas'));
        $this->assertNull(Connect::getObjectInstance('getClassSuffix'));
    }

    function testGettingAContact() {
        // Blank contact
        $result = Connect::getObjectInstance('contact');
        $this->assertIsA($result, 'RightNow\Connect\v1_4\Contact');
        $this->assertNull($result->ID);

        // Blank object only
        $this->logIn();
        $result = Connect::getObjectInstance('CONTACT', true);
        $this->assertIsA($result, 'RightNow\Connect\v1_4\Contact');
        $this->assertNull($result->ID);
        $this->logOut();

        // Logged-in contact
        $this->logIn();
        $result = Connect::getObjectInstance('Contact');
        $this->assertIsA($result, 'RightNow\Connect\v1_4\Contact');
        $this->assertIsA($result->ID, 'int');
        $this->logOut();

        // Can't access by specifying an id in the URL
        $this->addUrlParameters(array('c_id' => 1));
        $result = Connect::getObjectInstance('Contact');
        $this->assertIsA($result, 'RightNow\Connect\v1_4\Contact');
        $this->assertNull($result->ID);
        $this->restoreUrlParameters();

        // Can't access by specfying an id via direct parameter
        $result = Connect::getObjectInstance('Contact', 1);
        $this->assertIsA($result, 'RightNow\Connect\v1_4\Contact');
        $this->assertNull($result->ID);
        $this->restoreUrlParameters();
    }

    function testGettingAIncident() {
        // Blank incident
        $result = Connect::getObjectInstance('incident');
        $this->assertIsA($result, 'RightNow\Connect\v1_4\Incident');
        $this->assertNull($result->ID);

        // Blank object only with valid i_id
        $this->addUrlParameters(array('i_id', 149));
        $result = Connect::getObjectInstance('INCIdent');
        $this->assertIsA($result, 'RightNow\Connect\v1_4\Incident');
        $this->assertNull($result->ID);
        $this->restoreUrlParameters();

        // Valid i_id but not logged-in contact -> blank incident
        $this->addUrlParameters(array('i_id' => 149));
        $result = Connect::getObjectInstance('Incident');
        $this->assertIsA($result, 'RightNow\Connect\v1_4\Incident');
        $this->assertNull($result->ID);
        $this->restoreUrlParameters();

        $this->logIn('walkerj');
        $this->addUrlParameters(array('i_id' => '70'));

        // Valid i_id (70) and logged-in contact (walkerj) for the incident -> populated incident
        $result = Connect::getObjectInstance('incident');
        $this->assertIsA($result, 'RightNow\Connect\v1_4\Incident');
        $this->assertSame(70, $result->ID);

        $this->restoreUrlParameters();

        // Blank incident supplied for asking for an incident that the user doesn't own.
        $result = Connect::getObjectInstance('incident', 1);
        $this->assertIsA($result, 'RightNow\Connect\v1_4\Incident');
        $this->assertNull($result->ID);

        // Directly accessing incident via method parameter
        $result = Connect::getObjectInstance('incident', 70);
        $this->assertIsA($result, 'RightNow\Connect\v1_4\Incident');
        $this->assertSame(70, $result->ID);

        $result = Connect::getObjectInstance('incident', '70');
        $this->assertIsA($result, 'RightNow\Connect\v1_4\Incident');
        $this->assertSame(70, $result->ID);

        $this->logOut();
    }

    function testGettingAnAnswer() {
        // No such thing as a blank answer (read-only)
        $this->assertNull(Connect::getObjectInstance('answer'));
        $this->assertNull(Connect::getObjectInstance('answer', true));

        // Invalid a_id in the URL
        $this->addUrlParameters(array('a_id' => 122324211212));
        $this->assertNull(Connect::getObjectInstance('answer'));
        $this->restoreUrlParameters();

        // Legit a_id in the URL
        $this->addUrlParameters(array('a_id' => 52));
        $result = Connect::getObjectInstance('answer');
        $this->assertIsA($result, 'RightNow\Connect\Knowledge\v1\AnswerContent');
        $this->assertSame(52, $result->ID);
        $this->restoreUrlParameters();

        // Invalid direct method param
        $this->assertNull(Connect::getObjectInstance('answer', 24234234));

        // Legit direct method param
        $result = Connect::getObjectInstance('answer', '52');
        $this->assertIsA($result, 'RightNow\Connect\Knowledge\v1\AnswerContent');
        $this->assertSame(52, $result->ID);
        // Side-effect of extracting comma-separated ids
        $result = Connect::getObjectInstance('answer', '1,52');
        $this->assertIsA($result, 'RightNow\Connect\Knowledge\v1\AnswerContent');
        $this->assertSame(52, $result->ID);
    }

    function testGettingAQuestion() {

        // TK - Need:
        // 1) Connect\Question mock object
        // 2) Populated Questions in the test db.
        //
        // $result = $method();
        // $this->assertIsA($result, 'RightNow\Connect\v1_4\Question');
        // $this->assertNull($result->ID);

    }

    function testGettingAProduct() {
        // No such thing as a blank ServiceProduct (read-only)
        $this->assertNull(Connect::getObjectInstance('ServiceProduct'));
        $this->assertNull(Connect::getObjectInstance('serviceproduct', true));

        // Invalid p in the URL
        $this->addUrlParameters(array('p' => 122324211212));
        $this->assertNull(Connect::getObjectInstance('ServiceProduct'));
        $this->restoreUrlParameters();

        // Legit p in the URL
        $this->addUrlParameters(array('p' => 1));
        $result = Connect::getObjectInstance('ServiceProduct');
        $this->assertIsA($result, 'RightNow\Connect\v1_4\ServiceProduct');
        $this->assertSame(1, $result->ID);
        $this->restoreUrlParameters();

        // Legacy comma-separated chain is still supported
        $this->addUrlParameters(array('p' => '1,4'));
        $result = Connect::getObjectInstance('ServiceProduct');
        $this->assertIsA($result, 'RightNow\Connect\v1_4\ServiceProduct');
        $this->assertSame(4, $result->ID);
        $this->restoreUrlParameters();

        // Invalid direct method param
        $this->assertNull(Connect::getObjectInstance('ServiceProduct', 24234234));

        // Legit direct method param
        $result = Connect::getObjectInstance('ServiceProduct', '2');
        $this->assertIsA($result, 'RightNow\Connect\v1_4\ServiceProduct');
        $this->assertSame(2, $result->ID);
        $result = Connect::getObjectInstance('ServiceProduct', '1,2');
        $this->assertIsA($result, 'RightNow\Connect\v1_4\ServiceProduct');
        $this->assertSame(2, $result->ID);
    }

    function testGettingACategory() {
        // No such thing as a blank ServiceCategory (read-only)
        $this->assertNull(Connect::getObjectInstance('ServiceCategory'));
        $this->assertNull(Connect::getObjectInstance('ServiceCategory', true));

        // Invalid c in the URL
        $this->addUrlParameters(array('c' => 122324211212));
        $this->assertNull(Connect::getObjectInstance('ServiceCategory'));
        $this->restoreUrlParameters();

        // Legit c in the URL
        $this->addUrlParameters(array('c' => 70));
        $result = Connect::getObjectInstance('ServiceCategory');
        $this->assertIsA($result, 'RightNow\Connect\v1_4\ServiceCategory');
        $this->assertSame(70, $result->ID);
        $this->restoreUrlParameters();

        // Legacy comma-separated chain is still supported
        $this->addUrlParameters(array('c' => '71,77'));
        $result = Connect::getObjectInstance('ServiceCategory');
        $this->assertIsA($result, 'RightNow\Connect\v1_4\ServiceCategory');
        $this->assertSame(77, $result->ID);
        $this->restoreUrlParameters();

        // Invalid direct method param
        $this->assertNull(Connect::getObjectInstance('ServiceCategory', 24234234));

        // Legit direct method param
        $result = Connect::getObjectInstance('ServiceCategory', '79');
        $this->assertIsA($result, 'RightNow\Connect\v1_4\ServiceCategory');
        $this->assertSame(79, $result->ID);
        $result = Connect::getObjectInstance('ServiceCategory', '71,79');
        $this->assertIsA($result, 'RightNow\Connect\v1_4\ServiceCategory');
        $this->assertSame(79, $result->ID);
    }

    function testGettingASocialUser() {
        // Social users can be retrieved via URL param.
        list($fixtureInstance, $userActive1) = $this->getFixtures(array(
            'UserActive1',
        ));
        $this->addUrlParameters(array('user' => $userActive1->ID));
        $result = Connect::getObjectInstance('CommunityUser');
        $this->assertIsA($result, 'RightNow\Connect\v1_4\CommunityUser');
        $this->assertSame($userActive1->ID, $result->ID);
        $this->restoreUrlParameters();
        $fixtureInstance->destroy();

        // Social users cannot be retrieved via profile cookie.
        $this->logIn();
        // Blank CommunityUser.
        $result = Connect::getObjectInstance('CommunityUser');
        $this->assertIsA($result, 'RightNow\Connect\v1_4\CommunityUser');
        $this->assertNull($result->ID);
        $this->logOut();
    }

    function testGetObjectField() {
        $this->logOut('', '');
        $result = Connect::getObjectField(array('Contact', 'Login'));
        $this->assertIsA($result, 'array');
        $this->assertSame(2, count($result));
        $this->assertNull($return[0]);
        $meta = $result[1];
        $this->assertIsA($meta, 'RightNow\Connect\v1_4\_metadata');
        $this->assertSame('Login', $meta->name);

        $result = Connect::getObjectField(array('Contact', 'Emails'));
        $this->assertIsA($result[0], 'RightNow\Connect\v1_4\EmailArray');
        $this->assertIdentical(0, count($result[0]));
        $this->assertIsA($result[1], 'RightNow\Connect\v1_4\_metadata');
        $this->assertSame('Email', $result[1]->COM_type);

        $result = Connect::getObjectField(array('Contact', 'Emails', 'PRIMARY', 'Address'));
        $this->assertNull($result[0]);
        $this->assertIsA($result[1], 'RightNow\Connect\v1_4\_metadata');
        $this->assertSame('String', $result[1]->COM_type);
        $this->assertSame(CONNECT_NAMESPACE_PREFIX . '\Email', $result[1]->container_class);

        $contact = $this->CI->model('Contact')->get(1)->result;
        $result = Connect::getObjectField(array('Contact', 'Emails', 'PRIMARY', 'Address'), $contact);
        $this->assertIsA($result[0], 'string');
        $this->assertIdentical('Address', $result[1]->name);

        $result = Connect::getObjectField(array('Contact', 'Emails', '0', 'Address'));
        $this->assertNull($result[0]);
        $this->assertIsA($result[1], 'RightNow\Connect\v1_4\_metadata');
        $this->assertSame('String', $result[1]->COM_type);
        $this->assertSame(CONNECT_NAMESPACE_PREFIX . '\Email', $result[1]->container_class);

        $result = Connect::getObjectField(array('contact', 'Name'), $this->CI->model('Contact')->getBlank()->result);
        $name = $result[0];
        $this->assertIsA($name, 'RightNow\Connect\v1_4\PersonName');
        $this->assertNull($name->First);
        $this->assertNull($name->Last);
        $this->assertIsA($result[1], 'RightNow\Connect\v1_4\_metadata');
        $this->assertSame('PersonName', $result[1]->COM_type);

        $result = Connect::getObjectField(array('Contact', 'CustomFields', 'c', 'pets_name'));
        $this->assertFalse(is_object($result[0]));
        $this->assertIsA($result[1], 'RightNow\Connect\v1_4\_metadata');
        $this->assertSame('String', $result[1]->COM_type);
        $this->assertSame('pets_name', $result[1]->name);

        $result = Connect::getObjectField(array('Contact', 'CustomFields', 'c', 'pet_type'));
        $this->assertSame('dog', $result[1]->named_values[0]->LookupName);
    }

    function testPopulateFieldValues() {
        $contact = $this->CI->model('Contact')->get(1)->result;
        Connect::populateFieldValues(array(
            'LookupName',
            'Address.City',
            'Address.Country.LookupName',
            'Organization.LookupName',
            'SomeFieldThatDoesNotExist',
            'Another.Field.That.Does.Not.Exist',
        ), $contact);
        $object = json_decode(json_encode($contact));
        $this->assertIdentical($contact->Name->First . ' ' . $contact->Name->Last, $object->LookupName);
        $this->assertIdentical($contact->Address->City, $object->Address->City);
        $this->assertIdentical($contact->Address->Country->LookupName, $object->Address->Country->LookupName);
        $this->assertIdentical($contact->Organization->LookupName, $object->Organization->LookupName);

        $country = $this->CI->model('Country')->get(1)->result;
        Connect::populateFieldValues(array(
            'ID',
            'LookupName',
            'Abbreviation',
            'Name',
            'Provinces.*.[ID, DisplayOrder, Name]',
            'Provinces.*.Names.*.LabelText',
            ), $country);
        $object = json_decode(json_encode($country));
        $this->assertEqual($object->LookupName, $country->LookupName);
        $this->assertTrue(count( (array) $object->Provinces) > 0);
        $offset = 0;
        foreach($object->Provinces as $province) {
            $this->assertEqual($province->ID, $country->Provinces[$offset]->ID);
            $this->assertEqual($province->DisplayOrder, $country->Provinces[$offset]->DisplayOrder);
            $this->assertEqual($province->Name, $country->Provinces[$offset]->Name);
            foreach($province->Names as $name) {
                $this->assertEqual($name->LabelText, $country->Provinces[$offset]->Names[0]->LabelText);
            }
            $offset++;
        }
    }

    function testGetObjectFieldLoggedIn() {
        $login = 'slatest';
        $this->logIn();

        $this->assertTrue(strlen($this->CI->session->getSessionData('sessionID')) > 1, "Could not login as $login");
        $result = Connect::getObjectField(array('Contact', 'Login'));
        $this->assertSame($login, $result[0]);

        $result = Connect::getObjectField(array('Contact', 'Emails'));
        $this->assertIsA($result[0], 'RightNow\Connect\v1_4\EmailArray');
        $this->assertIsA($result[0][0], 'RightNow\Connect\v1_4\Email');
        $this->assertIsA($result[1], 'RightNow\Connect\v1_4\_metadata');
        $this->assertSame('Email', $result[1]->COM_type);

        $result = Connect::getObjectField(array('Contact', 'Name'));
        $name = $result[0];
        $this->assertIsA($name, 'RightNow\Connect\v1_4\PersonName');
        $this->assertIsA($name->First, 'string');
        $this->assertIsA($name->Last, 'string');
        $this->assertIsA($result[1], 'RightNow\Connect\v1_4\_metadata');
        $this->assertSame('PersonName', $result[1]->COM_type);

        $result = Connect::getObjectField(array('Contact', 'CustomFields', 'c', 'pets_name'));
        $this->assertFalse(is_object($result[0]));
        $this->assertIsA($result[1], 'RightNow\Connect\v1_4\_metadata');
        $this->assertSame('String', $result[1]->COM_type);
        $this->assertSame('pets_name', $result[1]->name);

        $result = Connect::getObjectField(array('Contact', 'CustomFields', 'c', 'pet_type'));
        $this->assertSame('dog', $result[1]->named_values[0]->LookupName);

        $this->logOut();
    }

    function testGetObjectFieldObjectReference() {
        $contact = $this->CI->model('Contact')->get(212)->result;
        $result = Connect::getObjectField(array('Contact', 'Login'), $contact);
        $login = $result[0];
        $this->assertIdentical($contact->Login, $login);
    }

    function testGetObjectFieldExceptions() {
        try {
            Connect::getObjectField(array('Contact', 'Banana'));
            $this->fail();
        }
        catch (\Exception $e) {
            $this->pass();
        }
        try {
            Connect::getObjectField(array('Contact', 'Banana', 'Fooo'));
            $this->fail();
        }
        catch (\Exception $e) {
            $this->pass();
        }
        try {
            Connect::getObjectField(array('Contact', 'CustomFields', 'pet_type'));
            $this->fail();
        }
        catch (\Exception $e) {
            $this->pass();
        }
        try {
            $contact = new ConnectPHP\Contact();
            Connect::getObjectField(array('Incident', 'Subject'), $contact);
            $this->fail();
        }
        catch (\Exception $e) {
            $this->pass();
        }
    }

    function testSetFieldValueAppliesToSubclasses() {
        $methodUnderTest = $this->getMethod('guardObjectType');
        // TODO: find an example of a Connect subclass with which to test this
        // if we have not thrown an exception, the test passes
    }

    function testSetFieldValue() {
        $contact = $this->CI->model("Contact")->getBlank()->result;

        Connect::setFieldValue($contact, array('contact', 'Login'), 'banana');
        $this->assertSame('banana', $contact->Login);
        Connect::setFieldValue($contact, array('Contact', 'Name', 'First'), 'Cuffy');
        $this->assertSame('Cuffy', $contact->Name->First);

        $contact = $this->CI->model("Contact")->getBlank()->result;

        Connect::setFieldValue($contact, array('Contact', 'CustomFields', 'c', 'pets_name'), 'china');
        $this->assertSame('china', $contact->CustomFields->c->pets_name);
        Connect::setFieldValue($contact, array('Contact', 'CustomFields', 'c', 'pet_type'), 3);
        $this->assertSame(3, $contact->CustomFields->c->pet_type->ID);
        Connect::setFieldValue($contact, array('Contact', 'Emails', '0', 'Address'), 'foo@bar.com');
        Connect::setFieldValue($contact, array('Contact', 'Emails', '1', 'Address'), 'foo@banana.com');
        Connect::setFieldValue($contact, array('Contact', 'Emails', '2', 'Address'), 'foo@nonono.com');
        $this->assertSame('foo@bar.com', $contact->Emails[0]->Address);
        $this->assertSame('foo@banana.com', $contact->Emails[1]->Address);
        $this->assertSame('foo@nonono.com', $contact->Emails[2]->Address);
        Connect::setFieldValue($contact, array('Contact', 'Banana'), 'green');
        $this->assertSame('green', $contact->Banana);

        $contact = $this->CI->model("Contact")->getBlank()->result;
        Connect::setFieldValue($contact, array('Contact', 'Emails', 'PRIMARY', 'Address'), 'foo@bar.com');
        Connect::setFieldValue($contact, array('Contact', 'Emails', 'ALT1', 'Address'), 'foo@banana.com');
        Connect::setFieldValue($contact, array('Contact', 'Emails', 'ALT2', 'Address'), 'foo@nonono.com');
        $this->assertSame('foo@bar.com', $contact->Emails[0]->Address);
        $this->assertSame('foo@banana.com', $contact->Emails[1]->Address);
        $this->assertSame('foo@nonono.com', $contact->Emails[2]->Address);

        $contact = $this->CI->model("Contact")->getBlank()->result;
        Connect::setFieldValue($contact, array('Contact', 'Emails', 'PRIMARY', 'AddressType', 'ID'), 0);
        $this->assertSame(0, $contact->Emails[0]->AddressType->ID);

        $contact = $this->CI->model("Contact")->get(1)->result;
        Connect::setFieldValue($contact, array('Contact', 'Emails', 'PRIMARY', 'AddressType', 'ID'), 0);
        $this->assertSame(0, $contact->Emails[0]->AddressType->ID);

        $contact = $this->CI->model("Contact")->getBlank()->result;
        list($office, $mobile, $fax, $alt1) = array('111.222.3333', '222.333.4444', '333.444.5555', '444.555.6666');
        Connect::setFieldValue($contact, array('Contact', 'Phones', 'OFFICE', 'Number'), null);
        $this->assertSame(0, count($contact->Phones));
        Connect::setFieldValue($contact, array('Contact', 'Phones', 'OFFICE', 'Number'), $office);
        Connect::setFieldValue($contact, array('Contact', 'Phones', 'MOBILE', 'Number'), $mobile);
        Connect::setFieldValue($contact, array('Contact', 'Phones', 'FAX', 'Number'), $fax);
        Connect::setFieldValue($contact, array('Contact', 'Phones', 'ALT1', 'Number'), $alt1);
        $this->assertSame($office, $contact->Phones[0]->Number);
        $this->assertSame($mobile, $contact->Phones[1]->Number);
        $this->assertSame($fax, $contact->Phones[2]->Number);
        $this->assertSame($alt1, $contact->Phones[3]->Number);

        Connect::setFieldValue($contact, array('Contact', 'Phones', 'OFFICE', 'Number'), null);
        $this->assertSame(3, count($contact->Phones));
        $this->assertSame($mobile, $contact->Phones[0]->Number);
        $this->assertSame($fax, $contact->Phones[1]->Number);
        $this->assertSame($alt1, $contact->Phones[2]->Number);

        $contact = $this->CI->model("Contact")->getBlank()->result;
        Connect::setFieldValue($contact, array('Contact', 'Address', 'Country'), 1);
        Connect::setFieldValue($contact, array('Contact', 'Address', 'StateOrProvince'), 1);
        $this->assertIsA($contact->Address->Country, CONNECT_NAMESPACE_PREFIX . '\Country');
        $this->assertIdentical(1, $contact->Address->Country->ID);
        $this->assertIdentical(1, $contact->Address->StateOrProvince->ID);

        $contact = $this->CI->model("Contact")->getBlank()->result;
        Connect::setFieldValue($contact, array('Contact', 'Organization'), 1);
        $this->assertIsA($contact->Organization, CONNECT_NAMESPACE_PREFIX . '\Organization');
        $this->assertIdentical(1, $contact->Organization->ID);

        $contact = $this->CI->model("Contact")->getBlank()->result;
        Connect::setFieldValue($contact, array('Contact', 'Organization'), 56);
        $this->assertIsA($contact->Organization, CONNECT_NAMESPACE_PREFIX . '\Organization');
        $this->assertIdentical(56, $contact->Organization->ID);

        $contact = $this->CI->model("Contact")->getBlank()->result;
        Connect::setFieldValue($contact, array('Contact', 'Emails', 'PRIMARY', 'Address'), '');
        Connect::setFieldValue($contact, array('Contact', 'Emails', 'ALT1', 'Address'), null);
        $this->assertTrue(Connect::isArray($contact->Emails));
        $this->assertIdentical(0, count($contact->Emails));

        $contact = $this->CI->model("Contact")->getBlank()->result;
        Connect::setFieldValue($contact, array('Contact', 'ChannelUsernames', 'TWITTER', 'Username'), 'bobby');
        Connect::setFieldValue($contact, array('Contact', 'ChannelUsernames', 'TWITTER', 'UserNumber'), 42);
        $this->assertTrue(Connect::isArray($contact->ChannelUsernames));
        $this->assertIdentical(1, count($contact->ChannelUsernames));
        $this->assertIdentical('Twitter', $contact->ChannelUsernames[0]->ChannelType->LookupName);
        
        $question = $this->CI->model("CommunityQuestion")->getBlank()->result;
        $body = '<p>Test</p>';
        Connect::setFieldValue($question, array('CommunityQuestion','Body'), $body);
        $this->assertEqual($question->Body, $body);
        $body = '<IMG SRC=j&#X41vascript:alert("test2")>';
        Connect::setFieldValue($question, array('CommunityQuestion','Body'), $body);
        $this->assertNotEqual($question->Body, $body);
    }

    function testSetFieldValueException() {
        $incident = new ConnectPHP\Incident();
        try {
            Connect::setFieldValue($incident, array('Contact', 'Login'), 'foo');
            $this->fail();
        }
        catch (\Exception $e) {
            $this->pass();
        }
    }

    function testGetFormattedObjectFieldValue() {
        ob_start();
        $invalidField = Connect::getFormattedObjectFieldValue(array('Contact', 'bananas'), false);
        $invalidTable = Connect::getFormattedObjectFieldValue(array('Batman', 'bananas'), false);
        ob_end_clean();
        $this->assertNull($invalidField);
        $this->assertNull($invalidTable);
        // not enduser visible
        $this->assertNull(Connect::getFormattedObjectFieldValue(array('Contact', 'CustomFields', 'c', 'winner'), false));
        // enduser visible
        $this->assertNull(Connect::getFormattedObjectFieldValue(array('Contact', 'CustomFields', 'c', 'mktg_optin'), false));

        ob_start();
        Api::test_sql_exec_direct("UPDATE prod_vis SET enduser = 0 WHERE id = 129"); //P2
        \RightNow\Connect\v1_4\ConnectAPI::commit();
        \RightNow\Utils\Framework::setCache("ProdcatModel{$this->interfaceID}129Visible", false);
        \RightNow\Utils\Framework::removeCache("ProdcatModel1Product120-FormattedChain");
        $erroredResult = \RightNow\Utils\Connect::getFormattedObjectFieldValue(array ('ServiceProduct', 'Name'), false, '129');

        Api::test_sql_exec_direct("UPDATE prod_vis SET enduser = 1 WHERE id = 129"); //P2
        \RightNow\Connect\v1_4\ConnectAPI::commit();
        \RightNow\Utils\Framework::setCache("ProdcatModel{$this->interfaceID}129Visible", true);
        \RightNow\Utils\Framework::removeCache("ProdcatModel1Product120-FormattedChain");
        $validResult = \RightNow\Utils\Connect::getFormattedObjectFieldValue(array ('ServiceProduct', 'Name'), false, '129');
        ob_end_clean();

        $this->assertNull($erroredResult);
        $this->assertSame($validResult, 'p2');
    }

    function testIsFileAttachmentType() {
        $this->assertFalse(Connect::isFileAttachmentType(null));
        $this->assertFalse(Connect::isFileAttachmentType(""));
        $this->assertFalse(Connect::isFileAttachmentType(1));
        $this->assertFalse(Connect::isFileAttachmentType(false));
        $this->assertFalse(Connect::isFileAttachmentType(array()));
        $this->assertFalse(Connect::isFileAttachmentType((object)array()));
        $this->assertFalse(Connect::isFileAttachmentType(new ConnectPHP\ServiceProduct()));

        $this->assertTrue(Connect::isFileAttachmentType(new ConnectPHP\FileAttachmentAnswerArray()));
        $this->assertTrue(Connect::isFileAttachmentType(new ConnectPHP\FileAttachmentIncidentArray()));
        $this->assertTrue(Connect::isFileAttachmentType(new ConnectPHP\FileAttachmentCommon()));
        $this->assertTrue(Connect::isFileAttachmentType(new ConnectPHP\FileAttachmentSharedArray()));
    }

    function testGetProductCategoryType() {
        $this->assertIdentical(false, Connect::getProductCategoryType(null));
        $this->assertIdentical(false, Connect::getProductCategoryType(""));
        $this->assertIdentical(false, Connect::getProductCategoryType(1));
        $this->assertIdentical(false, Connect::getProductCategoryType(false));
        $this->assertIdentical(false, Connect::getProductCategoryType(array()));
        $this->assertIdentical(false, Connect::getProductCategoryType((object)array()));
        $this->assertIdentical(false, Connect::getProductCategoryType(new ConnectPHP\FileAttachmentCommon()));

        $this->assertIdentical('product', Connect::getProductCategoryType(new ConnectPHP\ServiceProduct()));
        $this->assertIdentical('product', Connect::getProductCategoryType(new ConnectPHP\ServiceProductArray()));

        $this->assertIdentical('category', Connect::getProductCategoryType(new ConnectPHP\ServiceCategory()));
        $this->assertIdentical('category', Connect::getProductCategoryType(new ConnectPHP\ServiceCategoryArray()));
    }

    function testIsNamedIDType() {
        $this->assertFalse(Connect::isNamedIDType(null));
        $this->assertFalse(Connect::isNamedIDType('namedID'));
        $this->assertFalse(Connect::isNamedIDType(false));
        $this->assertFalse(Connect::isNamedIDType(10));

        $this->assertFalse(Connect::isNamedIDType(array()));
        $this->assertFalse(Connect::isNamedIDType((object)array()));

        $this->assertFalse(Connect::isNamedIDType((object)array('COM_type' => 'foo')));
        $this->assertFalse(Connect::isNamedIDType((object)array('COM_type' => 'string')));
        $this->assertFalse(Connect::isNamedIDType((object)array('COM_type' => 'Email')));

        $this->assertTrue(Connect::isNamedIDType((object)array('COM_type' => 'NamedIDOptList')));
        $this->assertTrue(Connect::isNamedIDType((object)array('COM_type' => 'NamedIDLabel')));
        $this->assertTrue(Connect::isNamedIDType(new ConnectPHP\NamedIDOptList()));
        $this->assertTrue(Connect::isNamedIDType(new ConnectPHP\NamedIDLabel()));

        $this->assertIdentical(false, Connect::isNamedIDType(new ConnectPHP\ServiceProduct()));
    }

    function testIsProductNotification() {
        $this->assertIdentical(false, Connect::isProductNotificationType(null));
        $this->assertIdentical(false, Connect::isProductNotificationType(""));
        $this->assertIdentical(false, Connect::isProductNotificationType(1));
        $this->assertIdentical(false, Connect::isProductNotificationType(false));
        $this->assertIdentical(false, Connect::isProductNotificationType(array()));
        $this->assertIdentical(false, Connect::isProductNotificationType((object)array()));
        $this->assertIdentical(false, Connect::isProductNotificationType(new ConnectPHP\CategoryNotification()));
        $this->assertIdentical(true, Connect::isProductNotificationType(new ConnectPHP\ProductNotification()));
    }

    function testIsCountryType() {
        $this->assertIdentical(false, Connect::isCountryType(null));
        $this->assertIdentical(false, Connect::isCountryType(""));
        $this->assertIdentical(false, Connect::isCountryType(1));
        $this->assertIdentical(false, Connect::isCountryType(false));
        $this->assertIdentical(false, Connect::isCountryType(array()));
        $this->assertIdentical(false, Connect::isCountryType((object)array()));
        $this->assertIdentical(false, Connect::isCountryType(new ConnectPHP\ServiceProduct()));
        $this->assertIdentical(false, Connect::isCountryType((object)array('COM_type' => 'Country')));

        $this->assertIdentical(true, Connect::isCountryType(new ConnectPHP\Country()));
    }

    function testIsIncidentThreadType() {
        $this->assertIdentical(false, Connect::isIncidentThreadType(null));
        $this->assertIdentical(false, Connect::isIncidentThreadType(""));
        $this->assertIdentical(false, Connect::isIncidentThreadType(1));
        $this->assertIdentical(false, Connect::isIncidentThreadType(false));
        $this->assertIdentical(false, Connect::isIncidentThreadType(array()));
        $this->assertIdentical(false, Connect::isIncidentThreadType((object)array()));
        $this->assertIdentical(false, Connect::isIncidentThreadType(new ConnectPHP\ServiceProduct()));

        $this->assertIdentical(true, Connect::isIncidentThreadType(new ConnectPHP\ThreadArray()));
        $this->assertIdentical(true, Connect::isIncidentThreadType(new ConnectPHP\Thread()));
    }

    function testIsQuestionCommentType(){
        $this->assertIdentical(false, Connect::isQuestionCommentType(null));
        $this->assertIdentical(false, Connect::isQuestionCommentType(""));
        $this->assertIdentical(false, Connect::isQuestionCommentType(1));
        $this->assertIdentical(false, Connect::isQuestionCommentType(false));
        $this->assertIdentical(false, Connect::isQuestionCommentType(array()));
        $this->assertIdentical(false, Connect::isQuestionCommentType((object)array()));
        $this->assertIdentical(false, Connect::isQuestionCommentType(new ConnectPHP\ServiceProduct()));
        $this->assertIdentical(false, Connect::isQuestionCommentType(new ConnectPHP\ThreadArray()));
        $this->assertIdentical(false, Connect::isQuestionCommentType(new ConnectPHP\Thread()));
    }

    function testIsSlaInstanceType(){
        $this->assertIdentical(false, Connect::isSlaInstanceType(null));
        $this->assertIdentical(false, Connect::isSlaInstanceType(""));
        $this->assertIdentical(false, Connect::isSlaInstanceType(1));
        $this->assertIdentical(false, Connect::isSlaInstanceType(false));
        $this->assertIdentical(false, Connect::isSlaInstanceType(array()));
        $this->assertIdentical(false, Connect::isSlaInstanceType((object)array()));
        $this->assertIdentical(false, Connect::isSlaInstanceType(new ConnectPHP\ServiceProduct()));

        $this->assertIdentical(true, Connect::isSlaInstanceType(new ConnectPHP\SLAInstance()));
        $this->assertIdentical(true, Connect::isSlaInstanceType(new ConnectPHP\AssignedSLAInstance()));
    }

    function testIsArray() {
        $this->assertIdentical(false, Connect::isArray(null));
        $this->assertIdentical(false, Connect::isArray(""));
        $this->assertIdentical(false, Connect::isArray(1));
        $this->assertIdentical(false, Connect::isArray(false));
        $this->assertIdentical(false, Connect::isArray(array()));
        $this->assertIdentical(false, Connect::isArray((object)array()));
        $this->assertIdentical(false, Connect::isArray(new ConnectPHP\ServiceProduct()));

        $this->assertIdentical(true, Connect::isArray(new ConnectPHP\ServiceProductArray()));
        $this->assertIdentical(true, Connect::isArray(new ConnectPHP\ThreadArray()));
        $this->assertIdentical(true, Connect::isArray(new ConnectPHP\SLAInstanceArray()));
        $this->assertIdentical(true, Connect::isArray(new ConnectPHP\ConnectArray()));
    }

    function testIsCustomField() {
        $this->assertIdentical(false, Connect::isCustomField(null));
        $this->assertIdentical(false, Connect::isCustomField(""));
        $this->assertIdentical(false, Connect::isCustomField(1));
        $this->assertIdentical(false, Connect::isCustomField(false));
        $this->assertIdentical(false, Connect::isCustomField(array()));
        $this->assertIdentical(false, Connect::isCustomField(array('container_class' => 'RightNow\\Connect\\v1_4\\ContactCustomFieldsc')));
        $this->assertIdentical(false, Connect::isCustomField((object)array()));
        $this->assertIdentical(false, Connect::isCustomField(new ConnectPHP\ServiceProduct()));

        $customFields = new ConnectPHP\ContactCustomFieldsc();
        $customFields = $customFields::getMetadata();
        $this->assertIdentical(true, Connect::isCustomField($customFields->age));
        $standardFields = new ConnectPHP\Contact();
        $standardFields = $standardFields::getMetadata();
        $this->assertIdentical(false, Connect::isCustomField($standardFields->Login));
    }

    function testIsCustomAttribute() {
        $this->assertIdentical(false, Connect::isCustomAttribute(null));
        $this->assertIdentical(false, Connect::isCustomAttribute(""));
        $this->assertIdentical(false, Connect::isCustomAttribute(1));
        $this->assertIdentical(false, Connect::isCustomAttribute(false));
        $this->assertIdentical(false, Connect::isCustomAttribute(array()));
        $this->assertIdentical(false, Connect::isCustomField(array('container_class' => 'RightNow\\Connect\\v1_4\\ContactCustomFieldsCO')));
        $this->assertIdentical(false, Connect::isCustomAttribute((object)array()));
        $this->assertIdentical(false, Connect::isCustomAttribute(new ConnectPHP\ServiceProduct()));

        // custom field
        $customFields = new ConnectPHP\IncidentCustomFieldsc();
        $customFields = $customFields::getMetadata();
        $this->assertIdentical(false, Connect::isCustomAttribute($customFields->age));

        // custom attribute
        $customAttributes = new ConnectPHP\IncidentCustomFieldsCO();
        $customAttributes = $customAttributes::getMetadata();
        $this->assertIdentical(true, Connect::isCustomAttribute($customAttributes->FieldBool));

        // Custom menu attributes can be associated with primary objects such as 'Countries',
        // in which case we have to track down the relationships to determine if a custom attribute.
        //
        // So this test basically confirms that #isCustomAttribute has the ability to lie, returning
        // `true` for a standard Country field's meta data. Or really any field's meta data, if there's
        // any kind of a relationship to a custom attribute.
        $meta = (object) array(
           'type_name' => 'RightNow\\Connect\\v1_4\\Country',
           'COM_type' => 'Country',
           'is_primary' => true,
           'label' => 'Country',
           'description' => 'Represents a country object defined in the RightNow CX system.',
           'relationships' => array(
                (object) array(
                   'relationName' => 'Incident$CustomFields$CO$ic_menu2_attr',
                   'navigability' => 1,
                   'relationKind' => 1,
                   'localObjectType' => 'Country',
                   'remoteObjectType' => 'Incident',
                ),
            ),
        );
        $this->assertIdentical(true, Connect::isCustomAttribute($meta));

        // relationships empty
        $meta = (object) array(
           'type_name' => 'RightNow\\Connect\\v1_4\\Country',
           'COM_type' => 'Country',
           'is_primary' => true,
           'label' => 'Country',
           'description' => 'Represents a country object defined in the RightNow CX system.',
           'relationships' => array(),
        );
        $this->assertIdentical(false, Connect::isCustomAttribute($meta));

        // relationships missing
        $meta = (object) array(
           'type_name' => 'RightNow\\Connect\\v1_4\\Country',
           'COM_type' => 'Country',
           'is_primary' => true,
           'label' => 'Country',
           'description' => 'Represents a country object defined in the RightNow CX system.',
        );
        $this->assertIdentical(false, Connect::isCustomAttribute($meta));
    }

    function testIsChannelField() {
        $this->assertIdentical(false, Connect::isChannelField(array()));

        //Legitimate cases
        $this->assertTrue(Connect::isChannelField(array('Contact', 'ChannelUsernames', 'TWITTER', 'Username')));
        $this->assertTrue(Connect::isChannelField(array('Contact', 'ChannelUsernames', 'FACEBOOK', 'Username')));

        //Failure cases
        $this->assertFalse(Connect::isChannelField(array('Incident', 'channelUsernames', 'TWITTER', 'Username')));
        $this->assertFalse(Connect::isChannelField(array('Incident', 'ID')));
        $this->assertFalse(Connect::isChannelField(array('Answer', 'channelUsernames', 'TWITTER', 'Username')));
        $this->assertFalse(Connect::isChannelField(array('Answer', 'Summary')));
    }

    function testIsChannelFieldEnduserVisible() {
        //By default all of these values should be EUV
        foreach(array(CHAN_TWITTER, CHAN_YOUTUBE, CHAN_FACEBOOK, 'TWITTER', 'FACEBOOK', 'YOUTUBE') as $channelID) {
            $this->assertTrue(Connect::isChannelFieldEnduserVisible(array('Contact', 'ChannelUsernames', $channelID, 'Username')));
        }

        //None of these should be EUV
        $this->assertFalse(Connect::isChannelFieldEnduserVisible(array('Incident', 'ChannelUsernames', 'TWITTER', 'Username')));
        $this->assertFalse(Connect::isChannelFieldEnduserVisible(array('Answer', 'ChannelUsernames', 'TWITTER', 'Username')));
        $this->assertFalse(Connect::isChannelFieldEnduserVisible(array('Contact', 'ChannelUsernames', 'MYSPACE', 'Username')));
        $this->assertFalse(Connect::isChannelFieldEnduserVisible(array('Contact', 'ChannelUsernames', 'DIGG', 'Username')));
        $this->assertFalse(Connect::isChannelFieldEnduserVisible(array('Contact', 'ChannelUsernames', '123', 'Username')));
        $this->assertFalse(Connect::isChannelFieldEnduserVisible(array('Contact', 'ChannelUsernames', '147', 'Username')));
        $this->assertFalse(Connect::isChannelFieldEnduserVisible(array('Incident', 'ID')));
        $this->assertFalse(Connect::isChannelFieldEnduserVisible(array('Answer', 'Summary')));
    }

    function testGetNamedValues() {
        $return = Connect::getNamedValues('Contact', 'Address.Country');
        $this->assertIsA($return, 'array');
        $this->assertSame(2, count($return));
        $return = Connect::getNamedValues('Account', 'Country');
        $this->assertIsA($return, 'array');
        $this->assertSame(2, count($return));
        $return = Connect::getNamedValues('Contact', 'Address.StateOrProvince');
        $this->assertIsA($return, 'array');
        $this->assertSame(62, count($return));
        $return = Connect::getNamedValues('Contact', 'CustomFields.c.pet_type');
        $this->assertIsA($return, 'array');
        $this->assertSame(4, count($return));
        try {
            $return = Connect::getNamedValues('Contact', 'Login');
            $this->fail();
        }
        catch (ConnectPHP\ConnectAPIErrorBase $e) {
            $this->pass();
        }
        try {
            $return = Connect::getNamedValues('Contact', 'banana');
            $this->fail();
        }
        catch (ConnectPHP\ConnectAPIErrorBase $e) {
            $this->pass();
        }
    }

    function testCastValue() {
        // boolean
        $this->assertTrue(Connect::castValue('true', (object)array('COM_type' => 'boolean')));
        $this->assertTrue(Connect::castValue('TRUE', (object)array('COM_type' => 'boolean')));
        $this->assertFalse(Connect::castValue('false', (object)array('COM_type' => 'BOOLEAN')));
        $this->assertFalse(Connect::castValue('FALSE', (object)array('COM_type' => 'BOOLEAN')));
        $this->assertTrue(Connect::castValue(true, (object)array('COM_type' => 'Boolean')));
        $this->assertFalse(Connect::castValue(false, (object)array('COM_type' => 'Boolean')));
        $this->assertFalse(Connect::castValue(0, (object)array('COM_type' => 'Boolean')));
        $this->assertTrue(Connect::castValue(10, (object)array('COM_type' => 'Boolean')));
        $this->assertNull(Connect::castValue('', (object)array('COM_type' => 'Boolean')));
        $this->assertNull(Connect::castValue(null, (object)array('COM_type' => 'Boolean')));
        $this->assertFalse(Connect::castValue('0', (object)array('COM_type' => 'Boolean')));
        $this->assertTrue(Connect::castValue('1', (object)array('COM_type' => 'Boolean')));
        // int
        $this->assertNull(Connect::castValue(null, (object)array('COM_type' => 'integer')));
        $this->assertNull(Connect::castValue('', (object)array('COM_type' => 'integer')));
        $this->assertIdentical(0, Connect::castValue(0, (object)array('COM_type' => 'integer')));
        $this->assertIdentical(-50, Connect::castValue(-50, (object)array('COM_type' => 'integer')));
        $this->assertIdentical(50, Connect::castValue(50, (object)array('COM_type' => 'integer')));
        $this->assertIdentical(1, Connect::castValue('1.1', (object)array('COM_type' => 'Integer')));
        $this->assertIdentical(27, Connect::castValue('27.1', (object)array('COM_type' => 'integer')));
        $this->assertIdentical(0, Connect::castValue('abc', (object)array('COM_type' => 'integer')));
        $this->assertIdentical(0, Connect::castValue(array(), (object)array('COM_type' => 'integer')));
        //ID Type fields
        $this->assertIdentical(1, Connect::castValue('1', (object)array('COM_type' => 'namedidlabel', 'is_list' => false)));
        $this->assertIdentical(50, Connect::castValue('50', (object)array('COM_type' => 'namedidlabel', 'is_list' => false)));
        $this->assertIdentical(1, Connect::castValue(1, (object)array('COM_type' => 'namedidoptlist', 'is_list' => false)));
        $this->assertIdentical(50, Connect::castValue(50, (object)array('COM_type' => 'namedidoptlist', 'is_list' => false)));
        $this->assertIdentical(10, Connect::castValue(10.5, (object)array('COM_type' => 'country', 'is_list' => false)));
        $this->assertIdentical(3, Connect::castValue(3.1415, (object)array('COM_type' => 'country', 'is_list' => false)));
        $this->assertIdentical(5, Connect::castValue(5.0, (object)array('COM_type' => 'stateorprovince', 'is_list' => false)));
        $this->assertIdentical(10, Connect::castValue('10.5', (object)array('COM_type' => 'stateorprovince', 'is_list' => false)));
        $this->assertIdentical(3, Connect::castValue('3.1415', (object)array('COM_type' => 'servicecategory', 'is_list' => false)));
        $this->assertIdentical(1, Connect::castValue(array(1), (object)array('COM_type' => 'servicecategory', 'is_list' => false)));
        $this->assertIdentical(1, Connect::castValue(array(1, 2), (object)array('COM_type' => 'servicecategory', 'is_list' => false)));
        $this->assertIdentical(array(3), Connect::castValue(array(3), (object)array('COM_type' => 'servicecategory', 'is_list' => true)));
        $this->assertIdentical(array(3, 4), Connect::castValue(array(3, 4), (object)array('COM_type' => 'servicecategory', 'is_list' => true)));
        $this->assertNull(Connect::castValue('', (object)array('COM_type' => 'servicecategory')));
        $this->assertNull(Connect::castValue(0, (object)array('COM_type' => 'serviceproduct')));
        $this->assertNull(Connect::castValue('0', (object)array('COM_type' => 'serviceproduct')));
        $this->assertNull(Connect::castValue(null, (object)array('COM_type' => 'serviceproduct')));
        $this->assertNull(Connect::castValue(array(), (object)array('COM_type' => 'serviceproduct')));
        $this->assertIdentical('abc', Connect::castValue('abc', (object)array('COM_type' => 'assignedslainstance')));
        $this->assertIdentical(-5, Connect::castValue(-5, (object)array('COM_type' => 'assignedslainstance')));
        $this->assertIdentical('-5', Connect::castValue('-5', (object)array('COM_type' => 'assignedslainstance')));
        $this->assertIdentical(1, Connect::castValue(array('abc'), (object)array('COM_type' => 'assignedslainstance', 'is_list' => false)));
        $this->assertIdentical(1, Connect::castValue(array(1, 2, 3), (object)array('COM_type' => 'assignedslainstance', 'is_list' => false)));
        // date
        $this->assertIdentical(null, Connect::castValue(0, (object)array('COM_type' => 'date')));
        $this->assertIdentical(02312343212, Connect::castValue(02312343212, (object)array('COM_type' => 'datetime')));
        $this->assertIdentical('2011-11-01', Connect::castValue('01 November 2011', (object)array('COM_type' => 'date')));
        // @@@ 210318-000115 datetime
        $this->assertIdentical(null, Connect::castValue(0, (object)array('COM_type' => 'datetime')));
        $this->assertIdentical('2003-01-02 04:05:00', Connect::castValue('02 Jan 2003 4:05 AM', (object)array('COM_type' => 'datetime')));
        // string
        $this->assertIdentical('banana', Connect::castValue(' banana  ', (object)array('COM_type' => 'string')));
        $this->assertIdentical('banana', Connect::castValue("banana\n", (object)array('COM_type' => 'Thread')));
        $this->assertIdentical(null, Connect::castValue("", (object)array('COM_type' => 'string')));
        $this->assertIdentical(null, Connect::castValue("     ", (object)array('COM_type' => 'string')));
        $this->assertSame(null, Connect::castValue(false, (object)array('COM_type' => 'string')));
        $this->assertSame(null, Connect::castValue(true, (object)array('COM_type' => 'string')));
        $this->assertIdentical("0", Connect::castValue(0, (object)array('COM_type' => 'string')));
        // passwords
        $this->assertSame('', Connect::castValue('', (object)array('COM_type' => 'string', 'name' => 'NewPassword')));
        $this->assertSame(' ', Connect::castValue(' ', (object)array('COM_type' => 'string', 'name' => 'NewPassword')));
        $this->assertSame('   a   ', Connect::castValue('   a   ', (object)array('COM_type' => 'string', 'name' => 'NewPassword')));
        $this->assertSame(null, Connect::castValue(null, (object)array('COM_type' => 'string', 'name' => 'NewPassword')));
        $this->assertSame(false, Connect::castValue(false, (object)array('COM_type' => 'string', 'name' => 'NewPassword')));
        $this->assertSame(0, Connect::castValue(0, (object)array('COM_type' => 'string', 'name' => 'NewPassword')));
        $this->assertSame(null, Connect::castValue('', (object)array('COM_type' => 'string', 'name' => 'Password')));
        $this->assertSame(null, Connect::castValue('', (object)array('COM_type' => 'string', 'name' => 'newPassword')));
        $this->assertSame(null, Connect::castValue('', (object)array('COM_type' => 'string', 'name' => 'Newpassword')));
        // default
        $this->assertIdentical('banana', Connect::castValue(' banana ', (object)array('COM_type' => '')));
        $this->assertIdentical('banana', Connect::castValue(' banana ', (object)array('COM_type' => 0)));
        $this->assertIdentical('0', Connect::castValue(0, (object)array('COM_type' => 'banana')));
        
        $this->assertSame(null, Connect::castValue('banana', (object)array('COM_type' => 'fileattachmentcommunity')));
        $this->assertTrue(is_array(Connect::castValue(array('file' => 'abc'), (object)array('COM_type' => 'fileattachmentcommunity'))));
        $this->assertTrue(is_array(Connect::castValue(new \stdClass(), (object)array('COM_type' => 'fileattachmentcommunity'))));
    }

    function testCheckAndStripMask() {
        //Valid input - return stripped field
        $this->assertIdentical('123', Connect::checkAndStripMask('Contact.CustomFields.c.text1', '(123)', (object) array('inputMask' => 'F(M#M#M#F)')));

        //Invalid input - return unmodified field
        $this->assertIdentical('(123', Connect::checkAndStripMask('Contact.CustomFields.c.text1', '(123', (object) array('inputMask' => 'F(M#M#M#F)')));

        //No mask - return unmodified field
        $this->assertIdentical('(123)', Connect::checkAndStripMask('Contact.CustomFields.c.text1', '(123)', new \stdClass()));

        //@@@ QA 130321-000022 Setup a fake phone mask and make sure the mask is picked up and stripped
        $country = $this->CI->model('Country')->get(1)->result;
        $country->PhoneMask = 'F(M#M#M#F)';
        $country->save();
        $this->assertIdentical('123', Connect::checkAndStripMask('Contact.Phones.OFFICE.number', '(123)', new \stdClass(), (object)array('value' => 1)));
        ConnectPHP\ConnectAPI::rollback();
    }

    function testGetPhoneOrPostalMaskName() {
        $method = $this->getMethod('getPhoneOrPostalMaskName');
        $this->assertIdentical('PhoneMask', $method('Contact.Phones.OFFICE.number'));
        $this->assertIdentical('PostalMask', $method('Contact.Address.PostalCode'));
        $this->assertNull($method('Contact.Login'));
    }

    function testGetMask() {
        list(, $fieldMetaData) = Connect::getObjectField(array('Contact', 'CustomFields', 'c', 'pets_name'));
        $this->assertIdentical('ULMLML', Connect::getMask('Contact', 'Contact.CustomFields.c.pets_name', $fieldMetaData));
        list(, $fieldMetaData) = Connect::getObjectField(array('Contact', 'Address', 'PostalCode'));
        $this->assertIdentical(null, Connect::getMask('Contact', 'Contact.Address.PostalCode', $fieldMetaData));
    }

    /**
    * Internal
    */

    function testMapCustomOrChannelFieldName() {
        $this->assertNull(Connect::mapCustomOrChannelFieldName(""));
        $this->assertNull(Connect::mapCustomOrChannelFieldName("c#sdf"));
        $this->assertNull(Connect::mapCustomOrChannelFieldName("csdf"));
        $this->assertSame("CustomFields.c.foo", Connect::mapCustomOrChannelFieldName('c$foo'));
        $this->assertSame("CustomFields.c.$", Connect::mapCustomOrChannelFieldName('c$$'));
        $this->assertSame("CustomFields.c.sdfsdfs", Connect::mapCustomOrChannelFieldName('c$sdfsdfs'));


        $this->assertNull(Connect::mapCustomOrChannelFieldName("channel$twitter"));
        $this->assertNull(Connect::mapCustomOrChannelFieldName("channel$1"));
        $this->assertNull(Connect::mapCustomOrChannelFieldName("channel$"));
        $this->assertIdentical('ChannelUsernames.TWITTER.Username', Connect::mapCustomOrChannelFieldName('channel$11'));
        $this->assertIdentical('ChannelUsernames.FACEBOOK.Username', Connect::mapCustomOrChannelFieldName('channel$14'));
        $this->assertIdentical('ChannelUsernames.YOUTUBE.Username', Connect::mapCustomOrChannelFieldName('channel$12'));
    }

    function testFind() {
        $find = $this->getMethod('find');
        $contact = $this->CI->model('Contact')->get(1)->result;
        $meta = $contact::getMetadata();
        $result = $find(array('LookupName'), $meta, $contact);
        $this->assertEqual(2, count($result));
        $lookupName = $result[0];
        $this->assertIsA($lookupName, 'string');
        $this->assertEqual(2, count(explode(' ', $lookupName))); // A first and last name
        $this->assertEqual('LookupName', $result[1]->name);

        try {
            $result = $find(array('Emails', 'DOESNOTEXIST', 'Address'), $meta, $contact);
            $this->fail();
        }
        catch (Exception $e) {
            $this->pass();
        }

        $lookup = array('Emails', 'PRIMARY', 'Address');
        $result = $find($lookup, $meta, $contact);
        $this->assertEqual(2, count($result));
        $this->assertIsA($result[0], 'string');
        $this->assertEqual('Address', $result[1]->name);

        $lookup = array('Emails', '0', 'Address');
        $result = $find($lookup, $meta, $contact);
        $this->assertEqual(2, count($result));
        $this->assertEqual('Address', $result[1]->name);

        $lookup = array('Address', 'City', 'constraints');
        $result = $find($lookup, $meta, $contact);
        $this->assertIsA($result, 'array');
        $this->assertNull($result[0]);
        $this->assertIsA($result[1], 'array');
        $this->assertIsA($result[1][0], 'RightNow\Connect\v1_4\_metadata');

        // Return contraints array item by index
        $lookup = array('Address', 'City', 'constraints', 0);
        $result = $find($lookup, $meta, $contact);
        $this->assertIsA($result, 'array');
        $this->assertNull($result[0]);
        $this->assertIsA($result[1], 'RightNow\Connect\v1_4\_metadata');

        $fieldName = "CO\\Defect";
        $lookup = array();
        $objectName = CONNECT_NAMESPACE_PREFIX . '\\' . $fieldName;
        $object = new $objectName();
        $meta = $object::getMetadata();
        $result = $find($lookup, $meta, $object);
    }

    function testGetPrimarySubObjectsFromField() {
        $fields = array(
            array(null, array()),
            array('', array()),
            array(array(), array()),
            array(1, array()),
            array('Incident.1', array()),
            array((object)array('foo' => 'whatever'), array()),
            array('Monkey.Wash.Donkey.Rinse', array()),
            array('Incident.Wash.Donkey.Rinse', array()),
            array('Incident', array()),
            array('Incident.SLA', array()),
            array('Incident.Subject', array()),
            array('Incident.ID', array()),
            array('Incident.StatusWithType.Status.ID', array()),
            array('Incident.Product', array('ServiceProduct')),
            array('Incident.Category', array('ServiceCategory')),
            array('Incident.PrimaryContact', array('Contact')),
            array('Incident.PrimaryContact.Name.First', array('Contact')),
            array('Incident.PrimaryContact.Emails.PRIMARY.Address', array('Contact')),
            array('Contact.Emails.PRIMARY.Address', array()),
            array('Contact.Address.StateOrProvince', array()),
            array('Contact.Address.Country', array('Country')),
            array('Contact.Name.Last', array()),
            array('Incident.Organization.ID', array('Organization')),
            array('Contact.Organization.Login', array('Organization')),
            array('Contact.Organization.Name', array('Organization')),
            array('Incident.CustomFields.CO.FieldDate', array()),
            //array('Incident.CustomFields.CO.MenuField', array()), // Need a menu custom attribute on our dev sites
        );
        foreach ($fields as $pair) {
            list($field, $expected) = $pair;
            $actual = Connect::getPrimarySubObjectsFromField($field);
            $message = sprintf("Field: '%s'\nExpected: '%s'\nActual: '%s'", var_export($field, true), var_export($expected, true), var_export($actual, true));
            $this->assertIsA($expected, 'array', $message);
            $this->assertIsA($actual, 'array', $message);
            $this->assertEqual(count($expected), count($actual), $message);
            $index = 0;
            foreach($actual as $connectObject) {
                $this->assertIsA($connectObject, CONNECT_NAMESPACE_PREFIX . '\\RNObject');
                $this->assertEqual($expected[$index], $connectObject::getMetadata()->COM_type);
                $index++;
            }
        }
    }

    function testFetchFromArray() {
        $contact = $this->CI->model('Contact')->get(1)->result;

        // Invalid inputs
        $this->assertNull(Connect::fetchFromArray($contact->Emails, 15));
        $this->assertNull(Connect::fetchFromArray($contact->Emails, 'DOESNOTEXIST'));

        try {
            $this->assertNull(Connect::fetchFromArray('NotAConnectObject', 'PRIMARY'));
            $this->fail();
        }
        catch (Exception $e) {
            $this->pass();
        }

        // Return an object from a field alias
        $result = Connect::fetchFromArray($contact->Emails, 'PRIMARY');
        $this->assertIsA($result, CONNECT_NAMESPACE_PREFIX . '\\Email');
        $this->assertIsA($result->Address, 'string');

        // Return an object from numerical index
        $result = Connect::fetchFromArray($contact->Emails, 0);
        $this->assertIsA($result, CONNECT_NAMESPACE_PREFIX . '\\Email');
        $this->assertIsA($result->Address, 'string');

        // Return a specific field
        $result = Connect::fetchFromArray($contact->Emails, 'PRIMARY', 'Address');
        $this->assertIsA($result, 'string');

        $contact = $this->CI->model('Contact')->getBlank()->result;
        //$contact->Emails null
        $this->assertNull(Connect::fetchFromArray($contact->Emails, 'PRIMARY'));

        //$contact->Emails empty
        $contact->Emails[] = new ConnectPHP\Email();
        $this->assertNull(Connect::fetchFromArray($contact->Emails, 'PRIMARY'));

        $email = new ConnectPHP\Email();
        $email->Address = 'one@two.three';
        $email->AddressType->ID = 0;
        $contact->Emails[] = $email;
        $result = Connect::fetchFromArray($contact->Emails, 'PRIMARY');
        $this->assertEqual('one@two.three', $result->Address);

        // Phones
        $this->assertNull(Connect::fetchFromArray($contact->Phones, 'OFFICE', 'Number'));
        $phone = new ConnectPHP\Phone();
        $phone->Number = '111.222.3333';
        $phone->PhoneType->ID = PHONE_OFFICE;
        $contact->Phones[] = $phone;

        $result = Connect::fetchFromArray($contact->Phones, 'OFFICE');
        $this->assertEqual('111.222.3333', $result->Number);

        $result = Connect::fetchFromArray($contact->Phones, 'OFFICE', 'Number');
        $this->assertEqual('111.222.3333', $result);

        //Channel usernames
        $this->assertNull(Connect::fetchFromArray($contact->ChannelUsernames, 'TWITTER', 'Username'));
        $channelUsername = new ConnectPHP\ChannelUsername();
        $channelUsername->Username = 'twitterUsername';
        $channelUsername->ChannelType = CHAN_TWITTER;
        $contact->ChannelUsernames[] = $channelUsername;

        $result = Connect::fetchFromArray($contact->ChannelUsernames, 'TWITTER');
        $this->assertEqual('twitterUsername', $result->Username);

        $result = Connect::fetchFromArray($contact->ChannelUsernames, 'TWITTER', 'Username');
        $this->assertEqual('twitterUsername', $result);
    }

    function testGetArrayFieldAliases(){
        $aliasArray = Connect::getArrayFieldAliases();

        $this->assertTrue(is_array($aliasArray));
        $this->assertTrue(is_array($aliasArray['EmailArray']));
        $this->assertTrue(is_array($aliasArray['PhoneArray']));
        $this->assertTrue(is_array($aliasArray['ChannelUsernameArray']));

        $this->assertIdentical($aliasArray['EmailArray']['PRIMARY'], CONNECT_EMAIL_PRIMARY);
        $this->assertIdentical($aliasArray['EmailArray']['ALT1'], CONNECT_EMAIL_ALT1);
        $this->assertIdentical($aliasArray['EmailArray']['ALT2'], CONNECT_EMAIL_ALT2);

        $this->assertIdentical($aliasArray['PhoneArray']['OFFICE'], PHONE_OFFICE);
        $this->assertIdentical($aliasArray['PhoneArray']['MOBILE'], PHONE_MOBILE);
        $this->assertIdentical($aliasArray['PhoneArray']['FAX'], PHONE_FAX);
        $this->assertIdentical($aliasArray['PhoneArray']['ASST'], PHONE_ASST);
        $this->assertIdentical($aliasArray['PhoneArray']['HOME'], PHONE_HOME);
        $this->assertIdentical($aliasArray['PhoneArray']['ALT1'], PHONE_ALT1);
        $this->assertIdentical($aliasArray['PhoneArray']['ALT2'], PHONE_ALT2);

        $this->assertIdentical($aliasArray['ChannelUsernameArray']['TWITTER'], CHAN_TWITTER);
        $this->assertIdentical($aliasArray['ChannelUsernameArray']['YOUTUBE'], CHAN_YOUTUBE);
        $this->assertIdentical($aliasArray['ChannelUsernameArray']['FACEBOOK'], CHAN_FACEBOOK);
    }

    function testGetClassSuffix() {
        $contact = $this->CI->model('Contact')->get(1)->result;
        $getClassSuffix = $this->getMethod('getClassSuffix');
        $this->assertEqual('EmailArray', $getClassSuffix($contact->Emails));
        $this->assertEqual('EmailArray', $getClassSuffix(get_class($contact->Emails)));
        $this->assertNull($getClassSuffix('whatever'));
    }

    function testGetIndexFromField() {
        $getIndexFromField = $this->getMethod('getIndexFromField');
        $this->assertNull($getIndexFromField('whatever'));
        $this->assertIdentical(1, $getIndexFromField(1));
        $this->assertIdentical(1, $getIndexFromField('1'));
        $this->assertIdentical(0, $getIndexFromField('PRIMARY', 'EmailArray'));
    }

    function testSave() {
        \RightNow\Libraries\AbuseDetection::isAbuse();
        $contact = $this->CI->model('Contact')->getBlank()->result;
        // Missing source
        try {
            Connect::save($contact, 0);
            $this->fail();
        }
        catch (\Exception $e) {
            // 0 is outside the range of valid End User level 2 sources.
            $this->pass();
        }

        $contact->Login = "testLoginFromSourceTesting";
        Connect::save($contact, SRC2_EU_NEW_CONTACT);
        $sourceLevel1 = \RightNow\Api::sql_get_int("select source_lvl1 from contacts where login='testLoginFromSourceTesting'");
        $sourceLevel2 = \RightNow\Api::sql_get_int("select source_lvl2 from contacts where login='testLoginFromSourceTesting'");
        $this->assertIdentical(SRC1_EU, $sourceLevel1);
        $this->assertIdentical(SRC2_EU_NEW_CONTACT, $sourceLevel2);

        ConnectPHP\ConnectApi::rollback();
    }

    function testImplicitSourceSetting(){
        $contact = $this->CI->model('Contact')->getBlank()->result;
        $contact->Login = "testImplicitSource";
        $contact->save();
        $sourceLevel1 = \RightNow\Api::sql_get_int("select source_lvl1 from contacts where login='testImplicitSource'");
        $sourceLevel2 = \RightNow\Api::sql_get_int("select source_lvl2 from contacts where login='testImplicitSource'");
        $this->assertIdentical(SRC1_EU, $sourceLevel1);
        $this->assertIdentical(SRC2_EU_CONNECT, $sourceLevel2);

        ConnectPHP\ConnectApi::rollback();
    }

    function testSetCustomFieldDefaults() {
        $contact = new ConnectPHP\Contact();
        $this->assertNull($contact->CustomFields->c->pets_name);
        $this->assertNull($contact->CustomFields->c->winner);
        Connect::setCustomFieldDefaults($contact);
        $this->assertEqual('Fred', $contact->CustomFields->c->pets_name);
        $this->assertIdentical(false, $contact->CustomFields->c->winner);

        $incident = new ConnectPHP\Incident();
        $this->assertNull($incident->CustomFields->CO->FieldDate);
        $this->assertNull($incident->CustomFields->CO->FieldDttm);
        Connect::setCustomFieldDefaults($incident);
        $this->assertNotNull($incident->CustomFields->CO->FieldDate);
        $this->assertIdentical(null, $incident->CustomFields->CO->FieldDttm);

        //TODO: Have a read-only custom field on our dev sites so we can test we don't try to set a default value.
    }

    function testGetContact() {
        $method = $this->getStaticMethod('getContact');

        // Blank contact
        $result = $method();
        $this->assertIsA($result, 'RightNow\Connect\v1_4\Contact');
        $this->assertNull($result->ID);

        // Blank object only
        $this->logIn();
        $result = $method(true);
        $this->assertIsA($result, 'RightNow\Connect\v1_4\Contact');
        $this->assertNull($result->ID);
        $this->logOut();

        // Logged-in contact
        $this->logIn();
        $result = $method();
        $this->assertIsA($result, 'RightNow\Connect\v1_4\Contact');
        $this->assertIsA($result->ID, 'int');

        $this->logOut();
    }

    function testGetIncident() {
        $method = $this->getStaticMethod('getIncident');

        // Blank incident
        $result = $method();
        $this->assertIsA($result, 'RightNow\Connect\v1_4\Incident');
        $this->assertNull($result->ID);

        // Blank object only with valid i_id
        $this->addUrlParameters(array('i_id', 149));
        $result = $method(true);
        $this->assertIsA($result, 'RightNow\Connect\v1_4\Incident');
        $this->assertNull($result->ID);
        $this->restoreUrlParameters();

        // Valid i_id but not logged-in contact -> blank incident
        $originalParameterSegment = $this->CI->config->item('parm_segment');
        $originalSegments = $segments = $this->CI->router->segments;
        $segments[] = 'i_id';
        $segments[] = '149';
        $this->CI->router->segments = $segments;
        $this->CI->router->setUriData();
        if (!\RightNow\Utils\Url::getParameter('i_id'))
            $this->CI->config->set_item('parm_segment', $originalParameterSegment - 1);

        $result = $method();
        $this->assertIsA($result, 'RightNow\Connect\v1_4\Incident');
        $this->assertNull($result->ID);

        $this->CI->config->set_item('parm_segment', $originalParameterSegment);
        $this->CI->router->segments = $originalSegments;
        $this->CI->router->setUriData();

        // Valid i_id (70) and logged-in contact (walkerj) for the incident -> populated incident
        $this->logIn('walkerj');

        $originalParameterSegment = $this->CI->config->item('parm_segment');
        $originalSegments = $segments = $this->CI->router->segments;
        $segments[] = 'i_id';
        $segments[] = '70';
        $this->CI->router->segments = $segments;
        $this->CI->router->setUriData();
        if (!\RightNow\Utils\Url::getParameter('i_id'))
            $this->CI->config->set_item('parm_segment', $originalParameterSegment - 1);

        $result = $method();
        $this->assertIsA($result, 'RightNow\Connect\v1_4\Incident');
        $this->assertIsA($result->ID, 'int');

        $this->logOut();

        $this->CI->config->set_item('parm_segment', $originalParameterSegment);
        $this->CI->router->segments = $originalSegments;
        $this->CI->router->setUriData();
    }

    function testGetAsset() {
        $method = $this->getStaticMethod('getAsset');

        // Blank asset
        $result = $method();
        $this->assertIsA($result, 'RightNow\Connect\v1_4\Asset');
        $this->assertNull($result->ID);

        // Blank object only with valid asset_id
        $this->addUrlParameters(array('asset_id', 5));
        $result = $method(true);
        $this->assertIsA($result, 'RightNow\Connect\v1_4\Asset');
        $this->assertNull($result->ID);
        $this->restoreUrlParameters();

        // Valid asset_id but not logged-in contact -> blank asset
        $originalParameterSegment = $this->CI->config->item('parm_segment');
        $originalSegments = $segments = $this->CI->router->segments;
        $segments[] = 'asset_id';
        $segments[] = '5';
        $this->CI->router->segments = $segments;
        $this->CI->router->setUriData();
        if (!\RightNow\Utils\Url::getParameter('asset_id'))
            $this->CI->config->set_item('parm_segment', $originalParameterSegment - 1);

        $result = $method();
        $this->assertIsA($result, 'RightNow\Connect\v1_4\Asset');
        $this->assertNull($result->ID);

        $this->CI->config->set_item('parm_segment', $originalParameterSegment);
        $this->CI->router->segments = $originalSegments;
        $this->CI->router->setUriData();

        $this->logIn('jerry@indigenous.example.com.invalid.070503.invalid');

        $originalParameterSegment = $this->CI->config->item('parm_segment');
        $originalSegments = $segments = $this->CI->router->segments;
        $segments[] = 'asset_id';
        $segments[] = '8';
        $this->CI->router->segments = $segments;
        $this->CI->router->setUriData();
        if (!\RightNow\Utils\Url::getParameter('asset_id'))
            $this->CI->config->set_item('parm_segment', $originalParameterSegment - 1);

        $result = $method();
        $this->assertIsA($result, 'RightNow\Connect\v1_4\Asset');
        $this->assertIsA($result->ID, 'int');

        $this->logOut();

        $this->CI->config->set_item('parm_segment', $originalParameterSegment);
        $this->CI->router->segments = $originalSegments;
        $this->CI->router->setUriData();
    }

    function testIsProductCatalogType() {
        $this->assertIdentical(false, Connect::isProductCatalogType(null));
        $this->assertIdentical(false, Connect::isProductCatalogType(""));
        $this->assertIdentical(false, Connect::isProductCatalogType(1));
        $this->assertIdentical(false, Connect::isProductCatalogType(false));
        $this->assertIdentical(false, Connect::isProductCatalogType(array()));
        $this->assertIdentical(false, Connect::isProductCatalogType((object)array()));
        $this->assertIdentical(false, Connect::isProductCatalogType(new ConnectPHP\ServiceProduct()));
        $this->assertIdentical(false, Connect::isProductCatalogType((object)array('COM_type' => 'SalesProduct')));

        $this->assertIdentical(true, Connect::isProductCatalogType(new ConnectPHP\SalesProduct()));
    }

    function testIsAssetType() {
        $this->assertIdentical(false, Connect::isAssetType(null));
        $this->assertIdentical(false, Connect::isAssetType(""));
        $this->assertIdentical(false, Connect::isAssetType(1));
        $this->assertIdentical(false, Connect::isAssetType(false));
        $this->assertIdentical(false, Connect::isAssetType(array()));
        $this->assertIdentical(false, Connect::isAssetType((object)array()));
        $this->assertIdentical(false, Connect::isAssetType(new ConnectPHP\ServiceProduct()));
        $this->assertIdentical(false, Connect::isAssetType((object)array('COM_type' => 'Asset')));

        $this->assertIdentical(true, Connect::isAssetType(new ConnectPHP\Asset()));
    }
}
