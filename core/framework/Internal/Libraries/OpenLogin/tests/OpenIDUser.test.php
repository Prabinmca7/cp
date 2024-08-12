<?php
RightNow\UnitTest\Helper::loadTestedFile(__FILE__);

use RightNow\Internal\Libraries\Openlogin\OpenIDUser as User;

class OpenIDUserTest extends CPTestCase {
    public $testingClass = 'RightNow\Internal\Libraries\Openlogin\OpenIDUser';

    function testFieldsAreSetCorrectly() {
        $input = array(
            'contact/email' => 'oh.my@aol.com',
            'openIDUrl' => 'http://placesheen.com/200/200',
            'namePerson/last' => 'Deam',
            'namePerson/first' => 'Electrixra',
        );
        $user = new User($input);

        $this->assertSame($input['contact/email'], $user->email);
        $this->assertSame($input['openIDUrl'], $user->openIDUrl);
        $this->assertSame($input['namePerson/last'], $user->lastName);
        $this->assertSame($input['namePerson/first'], $user->firstName);
    }

    function testnamePersonIsUsed() {
        $user = new User(array(
            'namePerson' => 'Deam Electrixra',
        ));

        $this->assertSame('Electrixra', $user->lastName);
        $this->assertSame('Deam', $user->firstName);

        $user = new User(array(
            'namePerson' => 'Katie',
        ));

        $this->assertSame('Katie', $user->lastName);
        $this->assertSame('Katie', $user->firstName);

        $user = new User(array(
            'namePerson' => 'Katie Blue Gowns',
        ));

        $this->assertSame('Blue Gowns', $user->lastName);
        $this->assertSame('Katie', $user->firstName);
    }

    function testnamePersonFriendlyIsUsed() {
        $user = new User(array(
            'namePerson/friendly' => 'Deam Electrixra',
        ));

        $this->assertSame('Electrixra', $user->lastName);
        $this->assertSame('Deam', $user->firstName);

        $user = new User(array(
            'namePerson/friendly' => 'Katie',
        ));

        $this->assertSame('Katie', $user->lastName);
        $this->assertSame('Katie', $user->firstName);

        $user = new User(array(
            'namePerson/friendly' => 'Katie Blue Gowns',
        ));

        $this->assertSame('Blue Gowns', $user->lastName);
        $this->assertSame('Katie', $user->firstName);
    }

    function testChannelUsernamesAreCorrect() {
        $user = new User;
        $user->openIDUrl = 'http://placesheen.com/200/200';
        $converted = $user->toContactArray();
        $this->assertIdentical($user->openIDUrl, $converted['Contact.OpenIDAccounts.0.URL']->value);
    }
}
