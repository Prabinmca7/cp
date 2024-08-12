<?php
RightNow\UnitTest\Helper::loadTestedFile(__FILE__);

use RightNow\Utils\Text,
    RightNow\Internal\Libraries\Openlogin\User;

class OpenLoginUserTest extends CPTestCase {
    public $testingClass = 'RightNow\Internal\Libraries\Openlogin\User';

    function testConstructor() {
        $user = new User;
        $this->assertNull($user->email);
        $this->assertNull($user->lastName);
        $this->assertNull($user->firstName);

        $user = new User((object) array('email' => 'blah', 'firstName' => 'idea', 'lastName' => 'moreno'));
        $this->assertNull($user->email);
        $this->assertNull($user->lastName);
        $this->assertNull($user->firstName);
    }

    function testConstructorWithPopulatedThirdPartyMapping() {
        $user = new ExtendingUser;
        $this->assertNull($user->email);
        $this->assertNull($user->lastName);
        $this->assertNull($user->firstName);

        $user = new ExtendingUser((object) array(
            'sabre' => 'goto',
            'idea' => 'tengo',
            'final' => 'deer_creek',
            'lastName' => 'moreno',
        ));
        $this->assertSame('goto', $user->bananas);
        $this->assertSame('tengo', $user->enterre);
        $this->assertSame('deer_creek', $user->panal);
        $this->assertNull($user->email);
        $this->assertNull($user->lastName);
        $this->assertNull($user->firstName);
    }

    function testConstructorWithPopulatedThirdPartyMappingAndArrayInput() {
        $user = new ExtendingUser(array(
            'sabre' => 'goto',
            'idea' => 'tengo',
            'final' => 'deer_creek',
            'lastName' => 'moreno',
        ));
        $this->assertSame('goto', $user->bananas);
        $this->assertSame('tengo', $user->enterre);
        $this->assertSame('deer_creek', $user->panal);
    }

    function testFirstNameIsTruncated() {
        $user = new User;
        $input = str_repeat("G", User::MAX_CONTACT_NAME_LENGTH + 1);
        $user->firstName = $input;
        $this->assertTrue(strlen($user->firstName) < strlen($input));
        $this->assertSame(User::MAX_CONTACT_NAME_LENGTH, strlen($user->firstName));

        $input = str_repeat("G", User::MAX_CONTACT_NAME_LENGTH);
        $user->firstName = $input;
        $this->assertSame(strlen($user->firstName), strlen($input));
        $this->assertSame(User::MAX_CONTACT_NAME_LENGTH, strlen($user->firstName));

        $input = '';
        $user->firstName = $input;
        $this->assertSame($user->firstName, '');
    }

    function testLastNameIsTruncated() {
        $user = new User;
        $input = str_repeat("益", User::MAX_CONTACT_NAME_LENGTH + 1);
        $user->lastName = $input;
        $this->assertTrue(Text::getMultibyteStringLength($user->lastName) < Text::getMultibyteStringLength($input));
        $this->assertSame(User::MAX_CONTACT_NAME_LENGTH, Text::getMultibyteStringLength($user->lastName));

        $input = str_repeat("益", User::MAX_CONTACT_NAME_LENGTH);
        $user->lastName = $input;
        $this->assertSame(Text::getMultibyteStringLength($user->lastName), Text::getMultibyteStringLength($input));
        $this->assertSame(User::MAX_CONTACT_NAME_LENGTH, Text::getMultibyteStringLength($user->lastName));

        $input = '';
        $user->lastName = $input;
        $this->assertSame($user->lastName, '');
    }

    function testToContactArray() {
        $user = new User;
        $this->assertIdentical(array(), $user->toContactArray());
    }

    function testToContactArrayWithPopulatedValues() {
        $user = new User;
        $user->firstName = 'bananas';
        $user->lastName = 'cahoone';
        $user->email = 'still@we.move';
        $user->userName = 'word';
        $user->avatarUrl = 'http://placesheen.com/200/200';

        $this->assertIdentical(array(
            'Contact.Name.First'             => (object) array('value' => 'bananas'),
            'Contact.Name.Last'              => (object) array('value' => 'cahoone'),
            'Contact.Emails.PRIMARY.Address' => (object) array('value' => 'still@we.move'),
            'Contact.Login'                  => (object) array('value' => 'still@we.move'),
        ), $user->toContactArray());
    }

    function testToSocialUserArray() {
        $user = new User;
        $this->assertIdentical(array(), $user->toContactArray());
    }

    function testToSocialUserArrayWithPopulatedValues() {
        $user = new User;
        $user->userName = 'word';
        $user->avatarUrl = 'http://placesheen.com/200/200';

        $this->assertIdentical(array(
            'Communityuser.DisplayName'         => (object) array('value' => 'word'),
            'Communityuser.AvatarURL'           => (object) array('value' => 'http://placesheen.com/200/200'),
        ), $user->toSocialUserArray());
    }

    function testToContactArrayWithServiceSpecificFields() {
        $user = new ExtendingUser;

        $this->assertIdentical($user->serviceSpecificFields(), $user->toContactArray());
    }
}

class ExtendingUser extends \RightNow\Internal\Libraries\OpenLogin\User {
    function serviceSpecificFields() {
        return array(
            'bananas' => 'rejoice',
            'Contact.Name.First' => 'mis muertos',
        );
    }

    function thirdPartyFieldMapping() {
        return array(
            'bananas' => 'sabre',
            'enterre' => 'idea',
            'panal'   => 'final',
        );
    }
}
