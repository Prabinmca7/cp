<?php
RightNow\UnitTest\Helper::loadTestedFile(__FILE__);

use RightNow\Internal\Libraries\Openlogin\TwitterUser as User;

class TwitterUserTest extends CPTestCase {
    public $testingClass = 'RightNow\Internal\Libraries\Openlogin\TwitterUser';

    function testFieldsAreSetCorrectly() {
        $input = (object) array(
            'id_str' => 23423423112,
            'screen_name' => 'one_to_blame',
            'profile_image_url' => 'http://placesheen.com/200/200',
            'name' => 'shaking hands',
            'email' => 'test@invalid.com',
        );
        $user = new User($input);

        $this->assertIdentical($input->id_str . '', $user->id);
        $this->assertIdentical($input->screen_name, $user->userName);
        $this->assertIdentical($input->profile_image_url, $user->avatarUrl);
        $this->assertIdentical('shaking', $user->firstName);
        $this->assertIdentical('hands', $user->lastName);
        $this->assertIdentical('test@invalid.com', $user->email);
    }

    function testSecondSpaceSeparatedSequenceIsUsedForLastName() {
        $user = new User((object) array(
            'name' => 'your friend in sound',
        ));

        $this->assertIdentical('your', $user->firstName);
        $this->assertIdentical('friend in sound', $user->lastName);
    }

    function testFirstNameIsReusedForLastNameIfNoSpaces() {
        $user = new User((object) array(
            'name' => 'anyway',
        ));

        $this->assertIdentical('anyway', $user->firstName);
        $this->assertIdentical('anyway', $user->lastName);
    }

    function testChannelUsernamesAreCorrect() {
        $user = new User;
        $user->id = 'bananas';
        $user->userName = 'lilac';

        $converted = $user->toContactArray();
        $this->assertIdentical('bananas', $converted['Contact.ChannelUsernames.TWITTER.UserNumber']->value);
        $this->assertIdentical('lilac', $converted['Contact.ChannelUsernames.TWITTER.Username']->value);
    }
}
