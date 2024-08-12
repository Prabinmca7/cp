<?php


use RightNow\Controllers\UnitTest,
    RightNow\UnitTest\Helper,
    RightNow\Utils\Text;

Helper::loadTestedFile(__FILE__);

class TestOutputChild extends \RightNow\Libraries\Widget\Output {
    function __construct($attributes) {
        parent::__construct($attributes);
    }
    function getData() {
        $this->initDataArray();
        return parent::getData();
    }
}

class OutputWidgetTest extends CPTestCase {
    public $testingClass = "TestOutputChild";

    function _createAttributeArray($attrs) {
        if (is_string($attrs)) {
            $attrs = array('name' => $attrs);
        }
        $toReturn = array();

        foreach ($attrs as $name => $value) {
            $toReturn[$name] = new \RightNow\Libraries\Widget\Attribute(array(
                'name'  => $name,
                'type'  => 'STRING',
                'value' => $value,
            ));
        }

        return $toReturn;
    }

    /**
     * Run the getData method, but suppress any output it might generate. We don't need that mucking up the actual results.
     */
    function runWidgetGetData($widget){
        ob_start();
        $response = $widget->getData();
        ob_end_clean();
        return $response;
    }

    function testGetData()
    {
        //Widget requires a name attribute
        $testWidget = new TestOutputChild(array());
        $this->assertIdentical($this->runWidgetGetData($testWidget), false);

        //There isn't a banana field on Contact.. jeez that's ridiculous
        $testWidget = new TestOutputChild($this->_createAttributeArray('Contact.banana'));
        $this->assertIdentical($this->runWidgetGetData($testWidget), false);

        //No a_id parameter on the page, fail.
        $testWidget = new TestOutputChild($this->_createAttributeArray('Answer.Solution'));
        $this->assertIdentical($this->runWidgetGetData($testWidget), false);

        $cacheKey = 'Output_DataType_incidents.fattach';
        \RightNow\Utils\Framework::setCache($cacheKey, null);
        $output = new TestOutputChild($this->_createAttributeArray('incidents.fattach'));
        $output->getData();
        $this->assertIdentical('Incident.FileAttachments', $output->data['attrs']['name']);
        $output = new TestOutputChild($this->_createAttributeArray('incidents.fattach'));
        $output->getData();
        $this->assertIdentical('Incident.FileAttachments', $output->data['attrs']['name']);
        \RightNow\Utils\Framework::setCache($cacheKey, null);
    }
/*
    function testRetrieveAndInitializeData() {
        list (, $method) = $this->reflect('method:retrieveAndInitializeData');

        $instance = new TestOutputChild($this->_createAttributeArray(array(
            'Contact.Title' => null,
            'value' => 'Mr',
        )));
        $returnValue = $method->invoke($instance);
        $this->assertSame(true, $returnValue);

        $instance = new TestOutputChild($this->_createAttributeArray('Contact.FakeField'));
        $instance->initDataArray();

        // this should echo an error to the screen, suppress that
        ob_start();
        $returnValue = $method->invoke($instance);
        ob_end_clean();
        $this->assertSame(false, $returnValue);
    }

    function testSetErrorMessage() {
        $instance = new TestOutputChild(null);
        $errorMessage = "test message";
        ob_start();
        $returnValue = $instance->setErrorMessage($errorMessage);
        $output = ob_get_contents();
        ob_end_clean();

        // setErrorMessage() only does severe errors
        $testValue = sprintf(\RightNow\Utils\Config::getMessage(WIDGET_ERROR_PCT_S_PCT_S_LBL), '', $errorMessage);
        $this->assertEqual($output, "<div><b>$testValue</b></div>");
        $this->assertSame(false, $returnValue);
    }

    function testDoNotDisplayPasswordFields(){
        $testWidget = new TestOutputChild($this->_createAttributeArray('Contact.NewPassword'));
        $this->assertIdentical($this->runWidgetGetData($testWidget), false);

        $testWidget = new TestOutputChild($this->_createAttributeArray('Contact.Organization.NewPassword'));
        $this->assertIdentical($this->runWidgetGetData($testWidget), false);
    }

    function testLabelDefault() {
        $CI = get_instance();
        // Never changed because field doesn't have a value, so we don't bother.
        $testWidget = new TestOutputChild($this->_createAttributeArray(array(
            'name'  => 'Incident.Subject',
            'label' => '{default_label}',
        )));
        $this->runWidgetGetData($testWidget);
        $result = $testWidget->getDataArray();
        $this->assertSame('{default_label}', $result['attrs']['label']);

        // Changed to field's name: text field
        $this->logIn();

        $testWidget = new TestOutputChild($this->_createAttributeArray(array(
            'name'  => 'Contact.Emails.PRIMARY.Address',
            'label' => '{default_label}',
        )));
        $this->runWidgetGetData($testWidget);
        $result = $testWidget->getDataArray();
        $this->assertSame('Address', $result['attrs']['label']);

        // Changed to field's name: menu field
        $contact = $CI->model('Contact')->get($CI->session->getProfileData('contactID'))->result;
        $contact->CustomFields->c->pet_type = new \RightNow\Connect\v1_4\NamedIDLabel();
        $contact->CustomFields->c->pet_type->ID = 1;
        $testWidget = new TestOutputChild($this->_createAttributeArray(array(
            'name'  => 'Contact.CustomFields.c.pet_type',
            'label' => '{default_label}',
        )));
        $this->runWidgetGetData($testWidget);
        $result = $testWidget->getDataArray();
        $this->assertNotEqual('pet_type', $result['attrs']['label']);

        // The set value is honored
        $testWidget = new TestOutputChild($this->_createAttributeArray(array(
            'name'  => 'Contact.Emails.PRIMARY.Address',
            'label' => 'nondefault',
        )));
        $this->runWidgetGetData($testWidget);
        $result = $testWidget->getDataArray();
        $this->assertSame('nondefault', $result['attrs']['label']);

        $this->logOut();
    }

    function getSocialUserID() {
        if ($user = \RightNow\Utils\Text::getSubstringAfter($this->CI->uri->uri_string(), __FUNCTION__ . '/user/')) {
            $this->addUrlParameters(array('user' => $user));
        }
        $this->logIn();
        $testWidget = new TestOutputChild($this->_createAttributeArray(array(
            'name'  => 'Communityuser.DisplayName',
        )));
        $this->runWidgetGetData($testWidget);
        $data = $testWidget->getDataArray();
        $this->logOut();
        if ($user) {
            $this->restoreUrlParameters();
        }
        echo $data['socialUserID'];
    }

    function testGetSocialquestioncommentID() {
        list (, $method) = $this->reflect('method:getSocialquestioncommentID');
        $instance = new TestOutputChild($this->_createAttributeArray(array(
            'name'  => 'CommunityComment.FileAttachments',
            'parent_object_id' => '32',
        )));
        $this->runWidgetGetData($instance);
        $returnValue = $method->invoke($instance);
        $this->assertSame('32', $returnValue);
    }

    function testGetSocialquestionID() {
        list (, $method) = $this->reflect('method:getSocialquestionID');
        $instance = new TestOutputChild($this->_createAttributeArray(array(
            'name'  => 'CommunityQuestion.FileAttachments',
            'parent_object_id' => '32',
        )));
        $this->runWidgetGetData($instance);
        $returnValue = $method->invoke($instance);
        $this->assertSame('32', $returnValue);
    }

    function testSocialUserDisplayName() {
        $makeRequest = function($user = '') {
            return json_decode(Helper::makeRequest('/ci/unitTest/wgetRecipient/invokeTestMethod/' . urlencode(__FILE__) . '/' . __CLASS__ . "/getSocialUserID/user/$user"));
        };

        // data['socialUserID'] should match logged-in user in absence of `user` url parameter
        $this->logIn();
        $this->assertNotEqual($this->CI->session->getProfileData('socialUserID'), $userActive1->ID);
        $this->assertEqual($this->CI->session->getProfileData('socialUserID'), $makeRequest());

        // data['socialUserID'] should match logged-in user in absence of `user` url parameter
        list($fixtureInstance, $userActive1) = $this->getFixtures(array('UserActive1'));
        $this->assertEqual($userActive1->ID, $makeRequest($userActive1->ID));

        $this->logOut();
    }

    function testSetLabel() {
        list($reflectionClass, $getData, $setLabel, $fieldNameProperty, $fieldMetaDataProperty, $dataProperty) = $this->reflect(
            'method:getData', 'method:setLabel', 'fieldName', 'fieldMetaData', 'data');

        $instance = $reflectionClass->newInstance(array());

        // NamedIDLabel uses fieldValue
        $label = 'ic_prov';
        $dataProperty->setValue($instance, array('attrs' => array(
            'name'  => 'Contact.CustomFields.CO.ic_prov',
            'label' => '{default_label}'
        )));
        $fieldNameProperty->setValue($instance, $label);
        $fieldMetaDataProperty->setValue($instance, (object) array('label' => 'NamedIDLabel'));
        $setLabel->invoke($instance);
        $data = $dataProperty->getValue($instance);
        $this->assertEqual($label, $data['attrs']['label']);

        // Unspecified fieldMetaData->label uses fieldValue
        $label = 'someOtherField';
        $dataProperty->setValue($instance, array('attrs' => array(
            'name'  => 'Contact.CustomFields.CO.someOtherField',
            'label' => '{default_label}'
        )));
        $fieldNameProperty->setValue($instance, $label);
        $fieldMetaDataProperty->setValue($instance, (object) array('label' => null));
        $setLabel->invoke($instance);
        $data = $dataProperty->getValue($instance);
        $this->assertEqual($label, $data['attrs']['label']);
    }

    function testMask() {
        $testWidget = new TestOutputChild($this->_createAttributeArray(array(
            'name'  => 'Contact.CustomFields.c.pets_name',
        )));
        $this->runWidgetGetData($testWidget);
        $result = $testWidget->getDataArray();
        $this->assertSame('ULMLML', $result['mask']);
    }
*/
}
