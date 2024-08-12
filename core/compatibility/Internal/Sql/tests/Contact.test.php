<?php
RightNow\UnitTest\Helper::loadTestedFile(__FILE__);

class ContactSqlTest extends CPTestCase
{
    public $testingClass = 'RightNow\Internal\Sql\Contact';

    function testGetPasswordHash() {
        \RightNow\Connect\v1_4\CustomerPortal::unlockAbusableMethods();
        $method = $this->getMethod('getPasswordHash');

        $contact = new \RightNow\Connect\v1_4\Contact();
        $contact->NewPassword = 'acloudstory';
        $contact->save();
        $this->assertIsA($method($contact->ID), 'string');
        $this->destroyObject($contact);

        $contact = new \RightNow\Connect\v1_4\Contact();
        $contact->Login = 'darkbananas';
        $contact->save();
        $this->assertNull($method($contact->ID));
        $this->destroyObject($contact);
    }

    function testGetOrganizationIDFromCredentials(){
        $method = $this->getMethod('getOrganizationIDFromCredentials');

        //Set up some test data
        $password = pw_rev_encrypt('ravipassword');
        test_sql_exec_direct("update orgs set login='ravi', password_encrypt='$password' where name='Ravisonix'");
        test_sql_exec_direct("update orgs set login='domo' where name='Pepperdomo'");

        $this->assertFalse($method('', ''));
        $this->assertFalse($method('brindell', ''));
        $this->assertFalse($method('msi', 'foo'));
        $this->assertFalse($method('ravi', ''));
        $this->assertFalse($method('ravi', null));

        $this->assertIdentical(43, $method('ravi', 'ravipassword'));
        $this->assertIdentical(46, $method('domo', ''));
        $this->assertIdentical(46, $method('domo', null));

        test_sql_exec_direct("update orgs set login=null, password_encrypt=null where name='Ravisonix'");
        test_sql_exec_direct("update orgs set login=null where name='Pepperdomo'");
    }

    function testCheckOldPassword() {
        $method = $this->getMethod('checkOldPassword');

        $this->assertFalse($method(null, str_repeat('a', 21)));
        $this->assertFalse($method(null, str_repeat('(ﾉಥ益ಥ）ﾉ﻿ ┻━┻', 21)));
        $this->assertTrue($method(1, ''));

        $sessionClass = new \ReflectionClass('RightNow\Libraries\Session');
        $profileData = $sessionClass->getProperty('profileData');
        $profileData->setAccessible(true);
        $session = get_instance()->session;
        $oldProfileData = $profileData->getValue($session);
        $newProfileData = (object)array('openLoginUsed' => true);
        $profileData->setValue($session, $newProfileData);

        $this->assertFalse($method(1293, ''));
        $this->assertFalse($method(1293, null));
        $this->assertFalse($method(1293, 'blahblah'));

        $profileData->setValue($session, $oldProfileData);
    }
}
