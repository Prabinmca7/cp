<?php
use RightNow\Utils\Permissions\Social;

class SocialPermissionsTest extends CPTestCase {

    function testUserCanEdit() {
        list($fixtureInstance, $userActive1, $userActive2, $userArchive, $userModActive) = $this->getFixtures(array(
            'UserActive1',
            'UserActive2',
            'UserArchive',
            'UserModActive',
        ));

        // Not logged in
        $errors = array();
        $this->assertFalse(Social::userCanEdit('Avatar', null, null, $errors));
        $this->assertEqual('User does not have permission to update this social user', $errors[0]);

        $this->logIn($userActive1->Contact->Login);
        //Test for self
        $this->addUrlParameters(array('user' => $userActive1->ID));
        $userID = \RightNow\Utils\Url::getParameter('user');
        $this->assertTrue(Social::userCanEdit('Avatar', $userID));
        $this->assertTrue(Social::userCanEdit('AvatarOrDisplayName', $userID));
        $this->assertTrue(Social::userCanEdit('DisplayName', $userID));
        $this->assertTrue(Social::userCanEdit('Some.Invalid.Field.But.Still.Should.Return.True', $userID));
        // Status
        $errors = array();
        $this->assertFalse(Social::userCanEdit('StatusWithType.Status.ID', $userID, null, $errors));
        $this->assertEqual('User does not have permission to change status on this social user', $errors[0]);
        $errors = array();
        $this->assertFalse(Social::userCanEdit('StatusWithType.Status.ID', $userID, 40, $errors));
        $this->assertEqual('User does not have permission to delete this social user', $errors[0]);

        // Not passing in $userID should return true as the 'user' url param will be used.
        $this->assertTrue(Social::userCanEdit('DisplayName'));
        $this->restoreUrlParameters();
        // Not passing in $userID should return true as the logged-in user will be used.
        $this->assertTrue(Social::userCanEdit('DisplayName'));

        //Test for active user
        $this->addUrlParameters(array('user' => $userActive2->ID));
        $userID = \RightNow\Utils\Url::getParameter('user');
        $this->assertFalse(Social::userCanEdit('Avatar', $userID));
        $this->assertFalse(Social::userCanEdit('AvatarOrDisplayName', $userID));
        $errors = array();
        $this->assertFalse(Social::userCanEdit('DisplayName', null, null, $errors));
        $this->assertEqual('User does not have permission to update this social user', $errors[0]);
        $this->restoreUrlParameters();

        //Test for archived user
        $this->addUrlParameters(array('user' => $userArchive->ID));
        $userID = \RightNow\Utils\Url::getParameter('user');
        $this->assertFalse(Social::userCanEdit('Avatar', $userID));
        $this->assertFalse(Social::userCanEdit('AvatarOrDisplayName', $userID));
        $this->restoreUrlParameters();

        $this->logOut();
        $this->logIn($userModActive->Contact->Login);

        //Test for self
        $this->addUrlParameters(array('user' => $userModActive->ID));
        $userID = \RightNow\Utils\Url::getParameter('user');
        $this->assertTrue(Social::userCanEdit('Avatar', $userID));
        $this->assertTrue(Social::userCanEdit('AvatarOrDisplayName', $userID));
        $this->restoreUrlParameters();

        //Test for active user
        $this->addUrlParameters(array('user' => $userActive1->ID));
        $userID = \RightNow\Utils\Url::getParameter('user');
        $this->assertTrue(Social::userCanEdit('Avatar', $userID));
        $this->assertTrue(Social::userCanEdit('AvatarOrDisplayName', $userID));
        $this->restoreUrlParameters();

        //Test for archived user
        $this->addUrlParameters(array('user' => $userArchive->ID));
        $userID = \RightNow\Utils\Url::getParameter('user');
        $this->assertFalse(Social::userCanEdit('Avatar', $userID));
        $this->assertFalse(Social::userCanEdit('AvatarOrDisplayName', $userID));
        $this->restoreUrlParameters();

        $this->logOut();

        $fixtureInstance->destroy();
    }

    function testGetUserAndSource() {
        list($fixtureInstance, $userActive) = $this->getFixtures(array('UserActive1'));

        // Not logged in
        $this->assertNull(Social::getUserAndSource());
        $this->assertNull(Social::getUserAndSource(109));
        $this->assertNull(Social::getUserAndSource(0));
        $this->assertNull(Social::getUserAndSource('blah'));

        $fromProfile = function() {
            return get_instance()->session->getProfileData('socialUserID');
        };

        // Not sending in $userID should obtain from session
        $this->logIn();
        list($user, $source) = Social::getUserAndSource();
        $this->assertIdentical('session', $source);
        $this->assertIdentical($fromProfile(), $user->ID);

        // userID of logged-in user explicitly sent in
        list($user, $source) = Social::getUserAndSource($fromProfile());
        $this->assertIdentical('input', $source);
        $this->assertIdentical($fromProfile(), $user->ID);

        // Setting the url parameter and not passing in $userID
        $this->addUrlParameters(array('user' => $userActive->ID));
        list($user, $source) = Social::getUserAndSource();
        $this->assertIdentical('url', $source);
        $this->assertIdentical($userActive->ID, $user->ID);
        $this->restoreUrlParameters();

        // Setting the url parameter and passing in that $userID
        $this->addUrlParameters(array('user' => $userActive->ID));
        list($user, $source) = Social::getUserAndSource($userActive->ID);
        $this->assertIdentical('input', $source);
        $this->assertIdentical($userActive->ID, $user->ID);
        $this->restoreUrlParameters();

        $this->logOut();
        $fixtureInstance->destroy();
    }
}