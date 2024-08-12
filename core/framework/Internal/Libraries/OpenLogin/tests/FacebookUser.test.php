<?php
RightNow\UnitTest\Helper::loadTestedFile(__FILE__);

use RightNow\Internal\Libraries\Openlogin\FacebookUser as User;

class FacebookUserTest extends CPTestCase {
    public $testingClass = 'RightNow\Internal\Libraries\Openlogin\FacebookUserTest';

    function testFieldsAreSetCorrectly() {
        $input = (object) array(
            'id' => 2334,
            'email' => 'any.way@you.like',
            'name' => 'herewithme',
            'first_name' => 'first',
            'last_name' => 'last',
        );
        $user = new User($input);

        $this->assertIdentical($input->id . '', $user->id);
        $this->assertIdentical($input->email, $user->email);
        $this->assertIdentical($input->name, $user->userName);
        $this->assertIdentical($input->first_name, $user->firstName);
        $this->assertIdentical($input->last_name, $user->lastName);
        $this->assertIdentical(sprintf(User::FB_PROFILE_PIC_URL, $input->id), $user->avatarUrl);
    }

    function testChannelUsernamesAreCorrect() {
        $user = new User;
        $user->id = 'bananas';
        $user->userName = 'lilac';

        $converted = $user->toContactArray();
        $this->assertIdentical('bananas', $converted['Contact.ChannelUsernames.FACEBOOK.UserNumber']->value);
        $this->assertIdentical('lilac', $converted['Contact.ChannelUsernames.FACEBOOK.Username']->value);
    }
}

