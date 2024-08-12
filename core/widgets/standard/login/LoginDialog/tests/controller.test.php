<?php

use RightNow\UnitTest\Helper;

Helper::loadTestedFile(__FILE__);

class TestLoginDialog extends WidgetTestCase {
    public $testingWidget = "standard/login/LoginDialog";

    function testGetCreateAccountFields() {
        $widget = $this->createWidgetInstance();
        $method = $this->getWidgetMethod('getCreateAccountFields', $widget);

        // Default
        $expected = array('Contact.Emails.PRIMARY.Address', 'Contact.Login', 'CommunityUser.DisplayName', 'Contact.NewPassword', 'Contact.Name.First', 'Contact.Name.Last');
        $actual = $method($widget->data['attrs']['create_account_fields']);
        $this->assertIdentical($expected, $actual);

        // null
        $this->assertIdentical(array(), $method(''));
        $this->assertIdentical(array(), $method(null));

        // Expand 'Contact.FullName' to order determined by intl_nameorder config
        $expected = array('Contact.Name.First', 'Contact.Name.Last');
        $actual = $method('Contact.FullName');
        $this->assertIdentical($expected, $actual);

        Helper::setConfigValues(array('intl_nameorder' => 1));
        $expected = array('Contact.Name.Last', 'Contact.Name.First');
        $actual = $method('Contact.FullName');
        $this->assertIdentical($expected, $actual);
        Helper::setConfigValues(array('intl_nameorder' => 0));

        // verify that we trim the values
        $expected = array('Contact.Emails.PRIMARY.Address', 'Contact.Login', 'CommunityUser.DisplayName', 'Contact.NewPassword');
        $actual = $method('Contact.Emails.PRIMARY.Address; Contact.Login; CommunityUser.DisplayName; Contact.NewPassword');
        $this->assertIdentical($expected, $actual);
    }
}
