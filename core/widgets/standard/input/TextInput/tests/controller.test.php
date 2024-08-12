<?php

RightNow\UnitTest\Helper::loadTestedFile(__FILE__);

use RightNow\Utils\Text;

class TestTextInput extends WidgetTestCase {
    public $testingWidget = "standard/input/TextInput";

    function testGetDataForContactLastName() {
        $this->createWidgetInstance(array('name' => 'Contact.Name.Last'));
        $data = $this->getWidgetData();

        // attrs
        $this->assertSame('Contact.Name.Last', $data['attrs']['name']);
        $this->assertSame('Last', $data['attrs']['label_input']);
        $this->assertFalse($data['attrs']['required']);
        $this->assertFalse($data['attrs']['always_show_hint']);
        $this->assertFalse($data['attrs']['initial_focus']);
        $this->assertFalse($data['attrs']['validate_on_blur']);
        $this->assertFalse($data['attrs']['allow_external_login_updates']);
        $this->assertFalse($data['attrs']['hide_hint']);
        $this->assertFalse($data['attrs']['require_validation']);
        $this->assertFalse($data['attrs']['textarea']);
        $this->assertTrue($data['attrs']['always_show_mask']);

        // js
        $this->assertSame('String', $data['js']['type']);
        $this->assertFalse($data['js']['custom']);
        $this->assertNotNull($data['js']['constraints']);
        $this->assertNotNull($data['js']['constraints']['regex']);
        $this->assertSame(80, $data['js']['constraints']['maxLength']);
        $this->assertNotNull($data['js']['contactToken']);

        // others
        $this->assertNotNull($data['constraints']);
        $this->assertSame('Contact.Name.Last', $data['inputName']);
        $this->assertSame('', $data['value']);
        $this->assertSame('Text', $data['displayType']);
        $this->assertSame('text', $data['inputType']);
    }

    function testGetDataWithGoodTextarea() {
        $this->createWidgetInstance(array(
            'name' => 'Incident.Subject',
            'textarea' => true,
        ));
        $data = $this->getWidgetData();

        $this->assertTrue($data['attrs']['textarea']);
        $this->assertSame('textarea', $data['inputType']);
    }

    function testGetDataWithBadTextarea() {
        $this->createWidgetInstance(array(
            'name' => 'Contact.Address.Country',
            'textarea' => true,
        ));

        ob_start();
        $data = $this->getWidgetData();
        $error = ob_get_contents();
        ob_end_clean();

        $this->assertTrue(\RightNow\Utils\Text::stringContains($error, "Widget Error"));
        $this->assertTrue(\RightNow\Utils\Text::stringContains($error, "The data type for Country is not appropriate for a text input."));

        $this->assertTrue($data['attrs']['textarea']);
        $this->assertNull( $data['inputType']);
    }

    function testWidgetErrorForDisplayName() {
        $this->createWidgetInstance(array(
            'name' => 'CommunityUser.DisplayName',
        ));
        ob_start();
        $data = $this->getWidgetData();
        $error = ob_get_clean();
        $this->assertTrue(\RightNow\Utils\Text::stringContains($error, 'Widget Error'));
        $this->assertIdentical('', $data['value']);
    }

    function testGetDataWithMaxBytes() {
        $this->createWidgetInstance(array(
            'name' => 'Asset.Description',
            'textarea' => true,
        ));
        $data = $this->getWidgetData();
        $maxLength = $data['js']['constraints']['maxLength'];
        $maxBytes = $data['js']['constraints']['maxBytes'];
        $this->assertTrue($maxLength > 0);
        $this->assertTrue($maxBytes > 0);
        $this->assertEqual($maxBytes, $maxLength);

        //  'maximum_length' attr set
        $this->createWidgetInstance(array(
            'name' => 'Asset.Description',
            'textarea' => true,
            'maximum_length' => 3000,
        ));
        $data = $this->getWidgetData();
        $maxLength = $data['js']['constraints']['maxLength'];
        $maxBytes = $data['js']['constraints']['maxBytes'];
        $this->assertEqual(3000, $maxLength);
        $this->assertEqual(4000, $maxBytes);
    }

    function testOutputContstraints() {
        $this->createWidgetInstance(array('name' => 'Contact.Name.Last'));
        $this->getWidgetData();
        $method = $this->getWidgetMethod('outputConstraints');
        $constraints = $method();

        $this->assertSame("maxlength='80'", $constraints);

        $this->setWidgetAttributes(array(
            'name' => 'Incident.Subject',
            'required' => true,
        ));
        $this->getWidgetData();
        $constraints = $method();

        $this->assertSame("maxlength='240' required", $constraints);

        $this->setWidgetAttributes(array(
            'name' => 'Contact.Login',
            'required' => false,
        ));
        $this->getWidgetData();
        $constraints = $method();

        $this->assertSame("maxlength='255' autocorrect='off' autocapitalize='off'", $constraints);
    }

    function testDetermineDisplayType() {
        $this->createWidgetInstance(array('name' => 'Contact.Name.Last'));
        $data = $this->getWidgetData();
        $method = $this->getWidgetMethod('determineDisplayType');

        $this->assertSame('Text', $method($data['inputName'], $data['js']['type'], $data['js']['constraints']));

        $this->setWidgetAttributes(array('name' => 'Contact.Emails.PRIMARY.Address'));
        $data = $this->getWidgetData();

        $this->assertSame('Email', $method($data['inputName'], $data['js']['type'], $data['js']['constraints']));

        $this->setWidgetAttributes(array('name' => 'Contact.CustomFields.c.int1'));
        $data = $this->getWidgetData();

        $this->assertSame('Number', $method($data['inputName'], $data['js']['type'], $data['js']['constraints']));

        $this->setWidgetAttributes(array('name' => 'Incident.Threads'));
        $data = $this->getWidgetData();

        $this->assertSame('Textarea', $method($data['inputName'], $data['js']['type'], $data['js']['constraints']));
    }

    function testCheckForExistingContact() {
        $widgetInstance = $this->createWidgetInstance(array('name' => 'Contact.Name.Last'));

        // Existing contact
        $data = $this->getWidgetData();
        $response = $this->callAjaxMethod(
            'existingContactCheck',
            array(
                'email' => "mat@indigenous.example.invalid",
                'contactToken' => \RightNow\Utils\Framework::createTokenWithExpiration(1),
                'f_tok' => \RightNow\Utils\Framework::createTokenWithExpiration(0),
                ),
            true,
            $widgetInstance,
            array('validate_on_blur' => true, 'name' => $widgetInstance->data['attrs']['name']),
            true
        );
        $this->assertIsA($response, 'stdClass');
        $this->assertNotNull($response->message);
        $this->assertIsA($response->message, 'string');
        $this->assertTrue(Text::stringContains($response->message, "Email address already in use"));

        // Existing contact fails when validation is not enabled
        $data = $this->getWidgetData();
        $response = $this->callAjaxMethod(
            'existingContactCheck',
            array(
                'email' => "mat@indigenous.example.invalid",
                'contactToken' => \RightNow\Utils\Framework::createTokenWithExpiration(1),
                'f_tok' => \RightNow\Utils\Framework::createTokenWithExpiration(0),
                ),
            true,
            $widgetInstance,
            array('validate_on_blur' => false, 'name' => $widgetInstance->data['attrs']['name']),
            true
        );
        $this->assertNotNull($response);
        $this->assertFalse($response);

        $response = $this->callAjaxMethod(
            'existingContactCheck',
            array(
                'email' => "mat@indigenous.example.invalid",
                'contactToken' => \RightNow\Utils\Framework::createTokenWithExpiration(1),
                'f_tok' => \RightNow\Utils\Framework::createTokenWithExpiration(0),
                ),
            true,
            $widgetInstance,
            array('validate_on_blur' => true, 'name' => $widgetInstance->data['attrs']['name']),
            true
        );
        $this->assertNotNull($response);
        $this->assertTrue(Text::stringContains($response->message, "Email address already in use"));

        // Nonexistent contact
        $response = $this->callAjaxMethod(
            'existingContactCheck',
            array(
                'email' => "chuckb@bball3.invalid",
                'contactToken' => \RightNow\Utils\Framework::createTokenWithExpiration(1),
                'f_tok' => \RightNow\Utils\Framework::createTokenWithExpiration(0),
                ),
            true,
            $widgetInstance,
            array('validate_on_blur' => true, 'name' => $widgetInstance->data['attrs']['name']),
            true
        );
        $this->assertNotNull($response);
        $this->assertFalse($response);

        // lookup by login
        $response = $this->callAjaxMethod(
            'existingContactCheck',
            array(
                'login' => "slatest",
                'contactToken' => \RightNow\Utils\Framework::createTokenWithExpiration(1),
                'f_tok' => \RightNow\Utils\Framework::createTokenWithExpiration(0),
                ),
            true,
            $widgetInstance,
            array('validate_on_blur' => true, 'name' => $widgetInstance->data['attrs']['name']),
            true
        );
        $this->assertIsA($response, 'stdClass');
        $this->assertNotNull($response->message);
        $this->assertIsA($response->message, 'string');
        $this->assertTrue(Text::stringContains($response->message, "existing account already has this username"));

        // nonexistent login
        $response = $this->callAjaxMethod(
            'existingContactCheck',
            array(
                'login' => "not_a_valid_login",
                'contactToken' => \RightNow\Utils\Framework::createTokenWithExpiration(1),
                'f_tok' => \RightNow\Utils\Framework::createTokenWithExpiration(0),
                ),
            true,
            $widgetInstance,
            array('validate_on_blur' => true, 'name' => $widgetInstance->data['attrs']['name']),
            true
        );
        $this->assertNotNull($response);
        $this->assertFalse($response);

        // ignore login if email is also provided
        $response = $this->callAjaxMethod(
            'existingContactCheck',
            array(
                'email' => "mat@indigenous.example.invalid",
                'login' => "not_a_valid_login",
                'contactToken' => \RightNow\Utils\Framework::createTokenWithExpiration(1),
                'f_tok' => \RightNow\Utils\Framework::createTokenWithExpiration(0),
                ),
            true,
            $widgetInstance,
            array('validate_on_blur' => true, 'name' => $widgetInstance->data['attrs']['name']),
            true
        );
        $this->assertIsA($response, 'stdClass');
        $this->assertNotNull($response->message);
        $this->assertIsA($response->message, 'string');
        $this->assertTrue(Text::stringContains($response->message, "Email address already in use"));

        // contact token required
        $response = $this->callAjaxMethod(
            'existingContactCheck',
            array(
                'login' => "slatest",
                'f_tok' => \RightNow\Utils\Framework::createTokenWithExpiration(0),
                ),
            true,
            $widgetInstance,
            array('validate_on_blur' => true, 'name' => $widgetInstance->data['attrs']['name']),
            true
        );
        $this->assertNotNull($response);
        $this->assertFalse($response);
        
        // f_tok required
        $response = $this->callAjaxMethod(
            'existingContactCheck',
            array(
                'login' => "slatest",
                'contactToken' => \RightNow\Utils\Framework::createTokenWithExpiration(1)
                ),
            true,
            $widgetInstance,
            array('validate_on_blur' => true, 'name' => $widgetInstance->data['attrs']['name']),
            true
        );
        $this->assertIsA($response, 'stdClass');
        $this->assertIsA($response->errors[0], 'stdClass');
        $this->assertTrue(Text::stringContains($response->errors[0]->externalMessage, "action cannot be completed at this time"));

        // valid contact token required
        $response = $this->callAjaxMethod(
            'existingContactCheck',
            array(
                'login' => "slatest",
                'contactToken' => "not_a_valid_contact_token",
                'f_tok' => \RightNow\Utils\Framework::createTokenWithExpiration(0),
                ),
            true,
            $widgetInstance,
            array('validate_on_blur' => true, 'name' => $widgetInstance->data['attrs']['name']),
            true
        );
        $this->assertNotNull($response);
        $this->assertFalse($response);

        // does not throw error if no parameters are passed
        $response = $this->callAjaxMethod(
            'existingContactCheck',
            array('f_tok' => \RightNow\Utils\Framework::createTokenWithExpiration(0)),
            true,
            $widgetInstance,
            array('validate_on_blur' => true, 'name' => $widgetInstance->data['attrs']['name']),
            true
        );
        $this->assertNotNull($response);
        $this->assertFalse($response);
    }
}
