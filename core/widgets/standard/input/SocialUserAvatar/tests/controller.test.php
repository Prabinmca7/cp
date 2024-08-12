<?php

RightNow\UnitTest\Helper::loadTestedFile(__FILE__);

class TestSocialUserAvatar extends WidgetTestCase {
    public $testingWidget = "standard/input/SocialUserAvatar";

    function testGetData() {
        $this->logIn();
        $this->createWidgetInstance();
        $data = $this->getWidgetData();
        $this->assertNull($data['currentAvatar']['url']);
        $this->assertIdentical("other", $data['currentAvatar']['type']);
        $this->assertIdentical("perpetualslacontactnoorg@invalid.com", $data['js']['email']['address']);
        $this->assertIdentical("ca52a4de6c9903d35723d6da597e3b9e", $data['js']['email']['hash']);
    }

    function testSocialUserSameAsLoggedInUser() {
        $this->logIn();
        $instance = $this->createWidgetInstance(array('name' => 'SocialUserAvatar_123'));
        $data = $this->getWidgetData($instance);
        $this->assertTrue($data['js']['editingOwnAvatar']);
        $this->assertEqual($this->CI->session->getProfileData('socialUserID'), $data['js']['socialUser']);
    }

    function testPrivilegedUserCanEditUserFromURL() {
        $this->logIn('modactive1');
        $userID = 215; // useractive1
        $this->addUrlParameters(array('user' => $userID));
        $instance = $this->createWidgetInstance(array('name' => 'SocialUserAvatar_215'));
        $data = $this->getWidgetData($instance);
        $this->assertFalse($data['js']['editingOwnAvatar']);
        $this->assertEqual($userID, $data['js']['socialUser']);
        $this->restoreUrlParameters();
    }

    function testNonPrivilegedUserCannotEditUserFromURL() {
        $this->logIn('useractive1');
        $userID = 218; // modactive1
        $this->addUrlParameters(array('user' => $userID));
        $instance = $this->createWidgetInstance(array('name' => 'SocialUserAvatar_218'));
        $data = $this->getWidgetData($instance);
        $this->assertTrue($data['js']['editingOwnAvatar']);
        $this->assertEqual($this->CI->session->getProfileData('socialUserID'), $data['js']['socialUser']);
        $this->restoreUrlParameters();
    }

    function testAvatarLibraryAjax() {
        list($fixtureInstance, $user) = $this->getFixtures(array('UserActive2'));
        $defaultParamsToPass = array('content_type' => 'user');
        $this->createWidgetInstance($defaultParamsToPass);
        $f_tok = \RightNow\Utils\Framework::createTokenWithExpiration(0);
        $response = $this->callAjaxMethod('getImages', array('folder' => 'everyone', 'f_tok' => $f_tok), true, $this->widgetInstance, $defaultParamsToPass);
        $this->assertEqual(count($response->files), 18);
    }

    function testGetFilesWithSize() {
        //The file size of 'everyone/flower2.jpg' is 77kb, setting attribute 'avatar_library_max_image_size' = 50 so file 'everyone/flower2.jpg' should not be in result
        list($fixtureInstance) = $this->getFixtures(array('UserActive2'));
        $defaultParamsToPass = array('content_type' => 'user', 'folder' => 'everyone', 'avatar_library_max_image_size' => 50);
        $instance = $this->createWidgetInstance($defaultParamsToPass);
        $data = $this->getWidgetData($instance);
        $getFunction = $this->getWidgetMethod('getFiles', $this->widgetInstance);
        $result = $getFunction(HTMLROOT . $data['attrs']['avatar_library_image_location_gallery'], $data['attrs']['avatar_library_count'], 'everyone');
        $this->assertEqual(null, array_search('everyone/flower2.jpg', $result));
    }

    // @@@ 171102-000127 prevent directory traversal attacks
    function testGetFilesPreventsDirectoryTraversal() {
        $instance = $this->createWidgetInstance();
        $data = $this->getWidgetData($instance);
        $getFunction = $this->getWidgetMethod('getFiles', $this->widgetInstance);

        // control group - protect against false positives by ensuring there are files present as expected
        $result = $this->widgetInstance->getFiles(HTMLROOT . $data['attrs']['avatar_library_image_location_gallery'], $data['attrs']['avatar_library_count'], 'everyone');
        $this->assertTrue(count($result) > 0);

        // should not allow access to parent dirs using '..' (should not throw an error, just return no files)
        // note that '../gallery/everyone' is equivalent to 'everyone'
        $result = $this->widgetInstance->getFiles(HTMLROOT . $data['attrs']['avatar_library_image_location_gallery'], $data['attrs']['avatar_library_count'], '../gallery/everyone');
        $this->assertEqual(0, count($result));

        // should also require that the resolved dir (using realpath()) is within the gallery
        // this is difficult to test given the prohibition on '..' above, so we will construct
        // a string that bypasses the '..' check with a zero-width space and show that it fails as well
        $result = $this->widgetInstance->getFiles(HTMLROOT . $data['attrs']['avatar_library_image_location_gallery'], $data['attrs']['avatar_library_count'], ".\u200b./gallery/everyone");
        $this->assertEqual(0, count($result));
    }

    function testfilterFolderAndLabels() {
        $this->logIn('useractive1');
        $defaultParamsToPass = array('content_type' => 'user');
        $instance = $this->createWidgetInstance($defaultParamsToPass);
        $data = $this->getWidgetData($instance);
        $getFunction = $this->getWidgetMethod('filterFolderAndLabels', $this->widgetInstance);
        $getFunction($data['attrs']['avatar_library_folder_roleset_map']);
        $this->assertEqual('everyone', $data['js']['defaultTab']);
        $this->assertEqual(array('everyone' => 'All Users'), $data['js']['rolesetsFolderMap']);
    }

    function testUpdateProfilePictureAjax() {
        list($fixtureInstance, $user) = $this->getFixtures(array('UserActive1'));
        $defaultParamsToPass = array('content_type' => 'user');
        $this->createWidgetInstance($defaultParamsToPass);
        $f_tok = \RightNow\Utils\Framework::createTokenWithExpiration(0);
        $response = $this->callAjaxMethod('updateProfilePicture', array('socialUser' => 11277, 'value' => null, 'avatarSelectionType' => 'default', 'f_tok' => $f_tok), true, $this->widgetInstance, $defaultParamsToPass);
        $this->assertNotNull($response);
    }

    function testValidProfilePictureUrl() {
        $this->logIn('useractive1');
        $getFunction = $this->getWidgetMethod('validateUserAvatarUrl', $this->widgetInstance);
        $postParam = array('socialUser' => 11277, 'avatarSelectionType' => 'avatar_library', 'value' => 'everyone/hawk.jpg', 'validationUrl' => 'everyone/orcl_logo.png');
        $result = $getFunction($postParam);
        $this->assertFalse($result);
        $postParam = array('socialUser' => 11277, 'avatarSelectionType' => 'avatar_library', 'value' => 'https://www.invalid.com/everyone/hawk.jpg', 'validationUrl' => 'https://www.invalid.com/everyone/hawk.jpg');
        $result = $getFunction($postParam);
        $this->assertFalse($result);
        $postParam = array('socialUser' => 11277, 'avatarSelectionType' => '', 'value' => 'https://www.invalid.com/everyone/hawk.jpg', 'currentAvatar' => 'https://www.invalid.com/everyone/hawk.jpg');
        $result = $getFunction($postParam);
        $this->assertFalse($result);
    }

    function testFacebookProfilePictureUrl() {
        $this->logIn('useractive1');
        $getFunction = $this->getWidgetMethod('validateUserAvatarUrl', $this->widgetInstance);
        $postParam = array('socialUser' => 11277, 'value' => "http://graph.facebook.com/1932838382192/picture?return_ssl_resources=1&type=large", 'avatarSelectionType' => 'facebook');
        $result = $getFunction($postParam);
        $this->assertFalse($result);
    }
}
