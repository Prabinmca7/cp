<?php
RightNow\UnitTest\Helper::loadTestedFile(__FILE__);

use RightNow\Connect\v1_4 as Connect,
    RightNow\Utils\Config,
    RightNow\Utils\Url,
    RightNow\UnitTest\Fixture as Fixture;

class CommunityUserModelTest extends CPTestCase {
    public $testingClass = '\RightNow\Models\CommunityUser';

    // preconfigured contact for testing.  nzhang is a regular user
    private $nzhang;

    function __construct() {
        parent::__construct();
        $this->model = new RightNow\Models\CommunityUser;
        $this->nzhang = $this->CI->model('Contact')->get(1284)->result;
        $this->protocol = Url::isRequestHttps() ? 'https' : 'http';
        $this->host = Config::getConfig(OE_WEB_SERVER);
        $this->hostname = "{$this->protocol}://{$this->host}";
        $this->fixtureInstance = new Fixture();
    }

    function testGetBlank() {
        $response = $this->model->getBlank();
        $this->assertResponseObject($response);
        $this->assertConnectObject($response->result, 'CommunityUser');
    }

    function testCreate() {
        // create a new contact so we know it doesn't have a social user
        $contact = $this->createContact();

        $this->logIn('slatest');
        $input = array(
            'Communityuser.DisplayName' => (object) array('value' => 'Cuffy Meigs   '),
            'Communityuser.AvatarURL' => (object) array('value' => '  http://placekitten.com/200/200'),
            'Communityuser.Contact' => (object) array('value' => $contact->ID),
            'Bananas.Ignored' => (object) array('value' => 'bananas'),
        );
        $socialUser1 = $this->assertResponseObject($this->model->create($input))->result;

        $this->assertConnectObject($socialUser1, 'CommunityUser');
        $this->assertIsA($socialUser1->ID, 'int');
        $this->assertIdentical($input['Communityuser.DisplayName']->value, $socialUser1->DisplayName);
        $this->assertIdentical($input['Communityuser.AvatarURL']->value, $socialUser1->AvatarURL);
        $this->assertIdentical('Active', $socialUser1->StatusWithType->Status->LookupName);
        $this->assertIdentical($contact->ID, $socialUser1->Contact->ID);

        // CommunityUser doesn't get created without a DisplayName
        $socialUser2 = $this->model->create(array(
            'Communityuser.Contact' => (object) array('value' => $contact->ID),
        ))->result;
        $this->assertNull($socialUser2);

        // CommunityUser doesn't get created without a Contact
        $socialUser3 = $this->model->create(array(
            'Communityuser.DisplayName' => (object) array('value' => 'Cuffy Meigs   '),
        ))->result;
        $this->assertNull($socialUser3);

        // clean up objects that were actually created
        $this->destroyObject($socialUser1);
        $this->destroyObject($contact);
    }

    function testContactsCanCreateTheirOwnSocialUsers() {
        $result = $this->makeRequest("/ci/unitTest/wgetRecipient/testModel/CommunityUser/contactsCanCreateTheirOwnSocialUsers");
        $this->assertSame('', $result);
    }

    function contactsCanCreateTheirOwnSocialUsers() {
        // create a new contact so we know it doesn't have a social user
        $contact = $this->createContact();

        $this->logIn($contact->Login);
        $input = array(
            'Communityuser.DisplayName' => (object) array('value' => 'nzhang'),
            'Communityuser.Contact' => (object) array('value' => $contact->ID),
        );
        $socialUser = $this->assertResponseObject($this->model->create($input, true))->result;

        // now create a question to ensure the default permissions are in effect
        $question = $this->assertResponseObject($this->CI->model('CommunityQuestion')->create(array(
                'Communityquestion.Subject' => (object) array('value' => 'shibby'),
            )))->result;

        // clean up newly created user and contact
        $this->destroyObject($question);
        $this->destroyObject($socialUser);
        $this->destroyObject($contact);
        Connect\ConnectAPI::commit();
    }

    function testInvalidGet() {
        $response = $this->model->get('sdf');
        $this->assertNull($response->result);
        $this->assertSame(1, count($response->errors));

        $response = $this->model->get(null);
        $this->assertNull($response->result);
        $this->assertSame(1, count($response->errors));

        $response = $this->model->get("abc123");
        $this->assertNull($response->result);
        $this->assertSame(1, count($response->errors));
    }

    function testGet() {
        $response = $this->model->get(456334);
        $this->assertResponseObject($response, 'is_null', 1, 0);

        // create a new contact so we know it doesn't have a social user
        $contact = $this->createContact();

        // create a CommunityUser so we can select it
        $this->logIn();
        $input = array(
            'Communityuser.DisplayName' => (object) array('value' => 'Cuffy Meigs   '),
            'Communityuser.Contact' => (object) array('value' => $contact->ID),
        );
        $response = $this->model->create($input);
        $this->assertResponseObject($response);
        $this->assertConnectObject($response->result, 'CommunityUser');
        $socialUserID = $response->result->ID;

        $response = $this->model->get($socialUserID);
        $this->assertResponseObject($response);
        $response = $response->result;
        $this->assertConnectObject($response, 'CommunityUser');
        $this->assertSame($socialUserID, $response->ID);

        // OK, we have created a social user.  But is it linked to its contact?
        $socialUserFromContact = $contact->CommunityUser;
        $this->assertNotNull($socialUserFromContact, "Contact's CommunityUser aspect should not be null");
        $this->assertEqual($socialUserID, $socialUserFromContact->ID, "Contact's CommunityUser aspect has the wrong ID");

        // clean up
        $this->destroyObject($socialUserFromContact);
        $this->destroyObject($contact);

        $this->logIn();

        $response = $this->model->get();
        $this->assertResponseObject($response);
        $this->assertConnectObject($response->result, 'CommunityUser');
        $this->assertIsA($response->result->SocialPermissions, 'RightNow\Decorators\SocialUserPermissions');

        $this->logIn('weide@rightnow.com.invalid');

        $response = $this->model->get();
        $this->assertResponseObject($response, 'is_null', 1);

        $this->logOut();
    }

    function testGetForContact() {
        // should get an error for invalid input
        $response = $this->model->getForContact('bananas');
        $this->assertSame(1, count($response->errors));

        // should get an error for a valid but nonexistent ID
        $response = $this->model->getForContact(999999);
        $this->assertSame(1, count($response->errors));

        // create a new contact so we know it doesn't have a social user
        $contact = $this->createContact();

        // should get an error when Contact is not associated with a CommunityUser
        $response = $this->model->getForContact($contact->ID);
        $this->assertSame(1, count($response->errors));

        // now create a social user for that contact
        $this->logIn();
        $newSocialUser = new Connect\CommunityUser();
        $newSocialUser->DisplayName = $contact->Login;
        $newSocialUser->Contact = $contact;
        $newSocialUser->save();
        Connect\ConnectAPI::commit();
        $this->assertFalse(empty($newSocialUser->ID), "CommunityUser should have been assigned an ID");

        // now that we have a contact with a social user, ensure we can retrieve the social user from the contact
        $response = $this->model->getForContact($contact->ID);
        $this->assertResponseObject($response);
        $this->assertSame(0, count($response->errors), print_r($response->errors, true));
        $this->assertSame(0, count($response->warnings), print_r($response->warnings, true));
        $this->assertConnectObject($response->result, 'CommunityUser');
        $this->assertSame($newSocialUser->ID, $response->result->ID, "Fetched wrong CommunityUser");

        // clean up - delete the Contact and the CommunityUser (NB: this does not also delete the common_user row)
        $this->destroyObject($newSocialUser);
        $this->destroyObject($contact);
    }

    function testUpdateAsRegularUser() {
        $this->_testUserPermissionsOnModel(array(1, array(), false), $this->model, 'update');

        $this->logIn();

        // create a CommunityUser which does not belong to nzhang
        $contact = $this->createContact();
        $newSocialUser = $this->assertResponseObject($this->model->create(array(
            'Communityuser.DisplayName' => (object) array('value' => 'Cuffy Meigs   '),
            'Communityuser.Contact' => (object) array('value' => $contact->ID),
        )))->result;
        $this->assertConnectObject($newSocialUser, 'CommunityUser');

        $this->logOut();
        $this->logIn($this->nzhang->Login);

        // try a couple of update scenarios, they should all fail (technically for different reasons)
        $this->assertResponseObject($this->model->update($newSocialUser->ID, array(
            'Communityuser.DisplayName' => (object) array('value' => 'UPDATED NAME'),
        )), 'is_null', 1);
        $this->assertResponseObject($this->model->update($newSocialUser->ID, array(
            'CommunityUser.AvatarURL' => (object) array('value' => 'https://www.gravatar.com/avatar/d41d8cd98f00b204e9800998ecf8427e?d=404&s=256', 'avatarSelectionType' => 'gravatar'),
        )), 'is_null', 1);
        $this->assertResponseObject($this->model->update($newSocialUser->ID, array(
            'CommunityUser.StatusWithType.Status.ID' => (object) array('value' => 40), // deleted
        )), 'is_null', 1);


        // This should not throw an error since permissions are only checked when a change in value is detected (i.e. status is already 38)
        $this->assertResponseObject($this->model->update($newSocialUser->ID, array(
            'CommunityUser.StatusWithType.Status.ID' => (object) array('value' => 38), // active
        )));

        // now try the same updates for nzhang's social user, they should all succeed except the status updates
        $mySocialUser = $this->model->get()->result;
        $this->assertResponseObject($this->model->update($mySocialUser->ID, array(
            'Communityuser.DisplayName' => (object) array('value' => 'UPDATED NAME'),
        )));
        $this->assertResponseObject($this->model->update($mySocialUser->ID, array(
            'CommunityUser.AvatarURL' => (object) array('value' => 'https://www.gravatar.com/avatar/c7b9421b24779568556beb3c9248f56a?d=404&s=256', 'avatarSelectionType' => 'gravatar'),
        )));
        // users cannot delete themselves
        $this->assertResponseObject($this->model->update($mySocialUser->ID, array(
            'CommunityUser.StatusWithType.Status.ID' => (object) array('value' => 40), // deleted
        )), 'is_null', 1);
        // and cannot otherwise change their own status
        $this->assertResponseObject($this->model->update($mySocialUser->ID, array(
            'CommunityUser.StatusWithType.Status.ID' => (object) array('value' => 39), // suspended
        )), 'is_null', 1);

        $this->destroyObject($newSocialUser);
        $this->destroyObject($contact);
    }

    function testUpdateAsUserAdmin() {
        $this->logIn('useradmin');
        // create a CommunityUser so we can select it
        $contact = $this->createContact();
        $input = array(
            'Communityuser.DisplayName' => (object) array('value' => 'Cuffy Meigs   '),
            'Communityuser.Contact' => (object) array('value' => $contact->ID),
        );
        $newSocialUser = $this->assertResponseObject($this->model->create($input))->result;

        // update the user, making sure to update avatar, display name, and set status to anything but deleted
        $input = array(
            'CommunityUser.DisplayName' => (object) array('value' => 'UPDATED NAME'),
            'CommunityUser.AvatarURL' => (object) array('value' => 'https://www.gravatar.com/avatar/d41d8cd98f00b204e9800998ecf8427e?d=404&s=256', 'avatarSelectionType' => 'gravatar'),
            'CommunityUser.StatusWithType.Status.ID' => (object) array('value' => 39), // suspended
            // Ignored
            'Bananas.Ignored' => (object) array('value' => 'bananas'),
        );
        $response = $this->assertResponseObject($this->model->update($newSocialUser->ID, $input))->result;

        $this->assertConnectObject($response, 'CommunityUser');
        $this->assertSame($newSocialUser->ID, $response->ID);
        $this->assertIdentical($input['CommunityUser.DisplayName']->value, $response->DisplayName);
        $this->assertIdentical($input['CommunityUser.AvatarURL']->value, $response->AvatarURL);

        // update again, setting the status to deleted (this takes a different code path)
        $input = array(
            'CommunityUser.DisplayName' => (object) array('value' => 'UPDATED NAME'),
            'CommunityUser.AvatarURL' => (object) array('value' => $this->hostname.'/euf/assets/images/avatar_library/display/everyone/mountain2.jpg', 'avatarSelectionType' => 'avatar_library'),
            'CommunityUser.StatusWithType.Status.ID' => (object) array('value' => 40), // deleted
        );
        $response = $this->assertResponseObject($this->model->update($newSocialUser->ID, $input))->result;

        // Does not exist
        $response = $this->model->update(2343141, $input);
        $this->assertNull($response->result);
        $this->assertSame(1, count($response->errors));
        $this->assertSame(0, count($response->warnings));

        $this->destroyObject($newSocialUser);
        $this->destroyObject($contact);
    }

    function testSetStatusWithTypeForAllTypes() {
        // retrieve all the possible types
        $statuses = Connect\ConnectAPI::getNamedValues('RightNow\Connect\v1_4\CommunityUser.StatusWithType.Status');
        $this->assertTrue(count($statuses) > 0, "Need at least one status");

        // create a social user we can update (if we created a new social user each time, we'd have
        // to create a contact each time as well)
        $this->logIn('useradmin');
        $contact = $this->createContact();
        $socialUser = $this->model->create(array(
            'Communityuser.DisplayName' => (object) array('value' => 'Cuffy Meigs   '),
            'Communityuser.Contact' => (object) array('value' => $contact->ID),
        ))->result;

        // try setting the status by ID - note that setting by LookupName doesn't work for CommunityUser
        foreach ($statuses as $status) {
            $this->assertResponseObject($this->model->update($socialUser->ID, array(
                'CommunityUser.StatusWithType.Status.ID' => (object) array('value' => $status->ID),
            )));
            $this->assertIdentical($status->ID, $socialUser->StatusWithType->Status->ID);
        }

        $this->destroyObject($socialUser);
        $this->logOut();
    }

    function testIsValidSocialObjectToModerate() {
        $this->logIn('slatest');
        list($fixtureInstance, $user) = $this->getFixtures(array(
            'UserActive1',
        ));

        // invalid object id
        $response = $this->model->isValidSocialObjectToModerate(0, 'CommunityUser');
        $this->assertIsA($response, 'RightNow\Libraries\ResponseObject');
        $this->assertSame(1, count($response->errors));

        // valid active object
        $response = $this->model->isValidSocialObjectToModerate($user->ID, 'CommunityUser');
        $this->assertResponseObject($response);
        $this->assertNotNull($response->result);

        $fixtureInstance->destroy();
    }

    function testGetSocialObjectStatuses() {
        // get a reference list of status types
        $statusTypes = Connect\ConnectAPI::getNamedValues('RightNow\Connect\v1_4\CommunityUser.StatusWithType.StatusType');
        $this->assertTrue(count($statusTypes) > 0);

        // test with all status types
        $statuses = $this->assertResponseObject($this->model->getSocialObjectStatuses())->result;
        $this->assertEqual(count($statusTypes), count($statuses));
        foreach ($statusTypes as $index => $statusType) {
            $this->assertEqual($statusType->ID, $statuses[$index]->StatusType->ID);
        }

        // test with a particular status type
        $statuses = $this->assertResponseObject($this->model->getSocialObjectStatuses(STATUS_TYPE_SSS_USER_DELETED))->result;
        $this->assertEqual(1, count($statuses));
        $this->assertEqual(40, $statuses[0]->Status->ID);
        $this->assertEqual(STATUS_TYPE_SSS_USER_DELETED, $statuses[0]->StatusType->ID);
    }

    function testAssignFormFieldValues() {
        $this->logIn();
        $contact = $this->createContact('Herbert');
        $user = $this->assertResponseObject($this->model->create(array(
            'Communityuser.DisplayName' => (object) array('value' => 'H.I. McDunnough'),
            'Communityuser.Contact' => (object) array('value' => $contact->ID),
        )))->result;
        $this->assertConnectObject($user, 'CommunityUser');
        $this->logOut();

        list($reflectionClass, $method) = $this->reflect('method:assignFormFieldValues');
        $instance = $reflectionClass->newInstance();

        // 'useractive1' does not have permissions to update this user, but since the fields don't reflect a change, no errors should be thrown.
        $this->logIn('useractive1');
        $errors = $warnings = array();
        $fields = array(
            'Communityuser.DisplayName' => (object) array('value' => 'H.I. McDunnough'),
        );
        $method->invokeArgs($instance, array($user, $fields, &$errors, &$warnings));
        $this->assertEqual(0, count($errors));

        // When a change is present, then we do expect an error.
        $errors = $warnings = array();
        $fields = array(
            'Communityuser.DisplayName' => (object) array('value' => 'Herbert'),
        );
        $method->invokeArgs($instance, array($user, $fields, &$errors, &$warnings));
        $this->assertEqual('User does not have permission to update this social user', $errors[0]);

        // Ensure adding display name to ignored fields results in 0 errors
        $errors = $warnings = array();
        $method->invokeArgs($instance, array($user, $fields, &$errors, &$warnings, true, array('Communityuser.DisplayName')));
        $this->assertEqual(0, count($errors));

        // Ensure setting $checkPermissions to false results in 0 errors
        $errors = $warnings = array();
        $method->invokeArgs($instance, array($user, $fields, &$errors, &$warnings, false));
        $this->assertEqual(0, count($errors));

        // Logged in as moderator, no errors
        $this->logIn('slatest');
        $errors = $warnings = array();
        $method->invokeArgs($instance, array($user, $fields, &$errors, &$warnings));
        $this->assertEqual(0, count($errors));
        $this->assertEqual(0, count($warnings));

        $this->logOut();
        $this->destroyObject($user);
        $this->destroyObject($contact);
    }

    function testNefariousDisplayName() {
        $errors = $warnings = array();

        $this->logIn();
        $contact = $this->createContact('walrus');
        $user = $this->assertResponseObject($this->model->create(array(
            'Communityuser.DisplayName' => (object) array('value' => 'shoes'),
            'Communityuser.Contact' => (object) array('value' => $contact->ID),
        )))->result;
        $this->assertConnectObject($user, 'CommunityUser');

        list($reflectionClass, $method) = $this->reflect('method:assignFormFieldValues');
        $instance = $reflectionClass->newInstance();

        // make sure special characters are properly encoded
        $fields = array(
            'Communityuser.DisplayName' => (object) array('value' => 'alberto" onmouseover="alert(1)'),
        );
        $method->invokeArgs($instance, array($user, $fields, &$errors, &$warnings));
        $this->assertEqual(0, count($errors));
        $this->assertEqual("alberto&quot; onmouseover=&quot;alert(1)", $user->DisplayName);
        //171206-000052 : should be properly encode <P & G Customer Care>
        $fields = array(
            'Communityuser.DisplayName' => (object) array('value' => '<P & G Customer Care>'),
        );
        $method->invokeArgs($instance, array($user, $fields, &$errors, &$warnings));
        $this->assertEqual(0, count($errors));
        $this->assertEqual("&lt;P &amp; G Customer Care&gt;", $user->DisplayName);
        $fields = array(
            'Communityuser.DisplayName' => (object) array('value' => '&lt;Customer Care&gt;'),
        );
        $method->invokeArgs($instance, array($user, $fields, &$errors, &$warnings));
        $this->assertEqual(0, count($errors));
        $this->assertEqual("&amp;lt;Customer Care&amp;gt;", $user->DisplayName);
        $this->logOut();
    }

    function testGetRecentlyActiveUsers() {
        $question = $this->fixtureInstance->make('QuestionActiveSingleComment');

        $response = $this->assertResponseObject($this->model->getRecentlyActiveUsers(1, 'week'));
        $this->assertEqual($response->result[0]['user']->DisplayName, $question->CreatedByCommunityUser->DisplayName);
        $this->destroyObject($question);
    }

    function testGetListOfUsers() {
        // Top Contributor in the past year
        $response = $this->assertResponseObject($this->model->getListOfUsers('questions', 1, 'year'));
        $this->assertEqual($response->result[0]['user']->DisplayName, 'useractive1');

        //Top Contributor in the past month
        $response = $this->assertResponseObject($this->model->getListOfUsers('questions', 1, 'month'));
        $this->assertEqual($response->result[0]['user']->DisplayName, 'useractive1');
        $this->assertEqual(count($response->result), 1);

        //The result returns 5 users when the count is set to 5
        $response = $this->assertResponseObject($this->model->getListOfUsers('questions', 5, 'month'));
        $this->assertEqual(count($response->result), 5);
    }
}
