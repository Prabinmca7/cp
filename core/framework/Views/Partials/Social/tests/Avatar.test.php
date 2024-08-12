<?php

class TestAvatarPartial extends ViewPartialTestCase {
    public $testingClass = 'Partials/Social/Avatar';

    function userHasAnAvatarURL () {
        return $this->render(array(
            'avatarUrl' => '//placesheen.com/200/200',
            'fileExists'    => 'true'
        ));
    }

    function sizeForUserAvatarURL () {
        return $this->render(array(
            'avatarUrl' => '//placesheen.com/100/100',
            'size' => '1px',
            'className' => 'largest',
            'fileExists'    => 'true'
        ));
    }

    function defaultSpan () {
        return $this->render(array(
            'user'          => (object) array(),
            'defaultAvatar' => array(
                'text'  => 'bananas',
                'color' => 'yellow',
            ),
            'fileExists'    => 'true'
        ));
    }

    function profileUrl () {
        return $this->render(array(
            'profileUrl' => '/app/public_profile/user/123',
            'displayName' => 'Uma',
            'title' => 'uma',
            'className' => 'guts',
            'defaultAvatar' => array(
                'text'  => 'U',
                'color' => 1,
            ),
            'fileExists'    => 'true'
        ));
    }

    function noProfileUrl () {
        return $this->render(array(
            'profileUrl' => null,
            'displayName' => 'Ima',
            'title' => null,
            'className' => 'alex',
            'defaultAvatar' => array(
                'text'  => 'I',
                'color' => 2,
            ),
            'fileExists'    => 'true'
        ));
    }

    function target () {
        return $this->render(array(
            'profileUrl' => '/app/public_profile/user/123',
            'displayName' => 'Uma',
            'title' => 'uma',
            'className' => 'guts',
            'defaultAvatar' => array(
                'text'  => 'U',
                'color' => 1,
            ),
            'target' => 'what',
            'fileExists'    => 'true'
        ));
    }

    function hideDisplayName () {
        return $this->render(array(
            'profileUrl' => '/app/public_profile/user/123',
            'displayName' => 'Uma',
            'title' => 'uma',
            'className' => 'guts',
            'hideDisplayName' => true,
            'size' => '0',
            'defaultAvatar' => array(
                'text'  => 'U',
                'color' => 1,
            ),
            'fileExists'    => 'true'
        ));
    }

    function imageFileExists () {
        return $this->render(array(
            'profileUrl' => '/app/public_profile/user/123',
            'displayName' => 'Uma',
            'title' => 'uma',
            'className' => 'guts',
            'defaultAvatar' => array(
                'text'  => 'U',
                'color' => 1,
            ),
            'fileExists'    => 'false'
        ));
    }

    function testUserHasAnAvatarURL () {
        $this->assertViewIsUnchanged('userHasAnAvatarURL');
    }

    function testSizeForUserAvatarURL () {
        $this->assertViewIsUnchanged('sizeForUserAvatarURL');
    }

    function testDefaultSpan () {
        $this->assertViewIsUnchanged('defaultSpan');
    }

    function testProfileUrl() {
        $this->assertViewIsUnchanged('profileUrl');
    }

    function testNoProfileUrl() {
        $this->assertViewIsUnchanged('noProfileUrl');
    }

    function testTarget() {
        $this->assertViewIsUnchanged('target');
    }

    function testHideDisplayName() {
        $this->assertViewIsUnchanged('hideDisplayName');
    }

    function testImageFileExists() {
        $this->assertViewIsUnchanged('imageFileExists');
    }
}