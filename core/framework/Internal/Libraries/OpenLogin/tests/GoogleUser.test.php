<?php
RightNow\UnitTest\Helper::loadTestedFile(__FILE__);

use RightNow\Internal\Libraries\Openlogin\GoogleUser as User;

class GoogleUserTest extends CPTestCase {
    public $testingClass = 'RightNow\Internal\Libraries\Openlogin\GoogleUserTest';

    function testFieldsAreSetCorrectly() {
        $input = (object) array(
            'email'         => 'spiderman@thedailybugle.com',
            'profile'       => 'https://plus.google.com/+Spidermannews/posts',
            'given_name'    => 'spider',
            'family_name'   => 'man',
            'picture'       => 'http://placespiderman.com/200/150',
            'sub'           => 'spider4life'
        );
        $user = new User($input);

        $this->assertIdentical($input->email, $user->email);
        $this->assertIdentical($input->profile, $user->openIDUrl);
        $this->assertIdentical($input->given_name, $user->firstName);
        $this->assertIdentical($input->family_name, $user->lastName);
        $this->assertIdentical($input->picture, $user->avatarUrl);
        $this->assertIdentical($input->sub, $user->id);
    }
}
