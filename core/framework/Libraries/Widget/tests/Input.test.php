<?php

use RightNow\Libraries\Widget\Input,
    RightNow\Libraries\Widget\Attribute,
    RightNow\Utils\Framework,
    RightNow\UnitTest\Helper,
    RightNow\Connect\v1_4 as Connect;

\RightNow\UnitTest\Helper::loadTestedFile(__FILE__);

require_once(\RightNow\Internal\Libraries\Widget\Registry::getWidgetPathInfo('standard/input/FormInput')->controller);
require_once(\RightNow\Internal\Libraries\Widget\Registry::getWidgetPathInfo('standard/input/DateInput')->controller);
require_once(\RightNow\Internal\Libraries\Widget\Registry::getWidgetPathInfo('standard/input/SelectionInput')->controller);
require_once(\RightNow\Internal\Libraries\Widget\Registry::getWidgetPathInfo('standard/input/TextInput')->controller);

class MockConnectObject {
    static function fetch($defaultValue) {
        return 'cucumber';
    }
}

class InputChild extends Input {
    function getData() {
        $this->initDataArray();
        return parent::getData();
    }

    function bufferGetData() {
        $this->initDataArray();
        ob_start();
        $response = $this->getData();
        ob_end_clean();
        return $response;
    }
}

class FormInputChild extends \RightNow\Widgets\FormInput {
    function getData() {
        $this->initDataArray();
        return parent::getData();
    }
}

class DateInputChild extends \RightNow\Widgets\DateInput {
    function getData() {
        $this->initDataArray();
        return parent::getData();
    }
}

class SelectionInputChild extends \RightNow\Widgets\SelectionInput {
    function getData() {
        $this->initDataArray();
        return parent::getData();
    }
}

class TextInputChild extends \RightNow\Widgets\TextInput {
    function getData() {
        $this->initDataArray();
        return parent::getData();
    }
}

class InputWidgetTest extends CPTestCase {
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

    private $testCases = array(
        array('class' => 'InputChild', 'expect' => false),
        array('name' => 'Contact.Login', 'class' => 'InputChild', 'expect' => null),
        array('name' => 'Contact.ID', 'class' => 'InputChild', 'expect' => false),
        array('name' => 'Incident.StatusWithType.Status.ID', 'class' => 'InputChild', 'expect' => false),
        array('name' => 'Incident.StatusWithType.Status.LookupName', 'class' => 'InputChild', 'expect' => false),
        array('name' => 'Contact.garbage', 'class' => 'InputChild', 'expect' => false),
        // Input will accept a product
        array('name' => 'Incident.Product', 'class' => 'InputChild', 'expect' => null),
        // FormInput will not accept a product
        array('name' => 'Incident.Product', 'class' => 'FormInputChild', 'expect' => false),
        // FormInput will not accept a nested primary sub-object (with a few exceptions)
        array('name' => 'Incident.PrimaryContact.Name.First', 'class' => 'FormInputChild', 'expect' => false),
        array('name' => 'Contact.Address.Country', 'class' => 'FormInputChild', 'expect' => null),
        array('name' => 'Incident.CustomFields.c.date1', 'class' => 'DateInputChild', 'expect' => null),
        array('name' => 'Contact.Login', 'class' => 'DateInputChild', 'expect' => false),
        array('name' => 'Incident.CustomFields.c.priority', 'class' => 'SelectionInputChild', 'expect' => null),
        array('name' => 'Contact.Login', 'class' => 'SelectionInputChild', 'expect' => false),
        array('name' => 'Incident.CustomFields.c.date1', 'class' => 'TextInputChild', 'expect' => false),
        array('name' => 'Contact.Login', 'class' => 'TextInputChild', 'expect' => null),
        array('name' => 'Incident.CustomFields.c.pets_name', 'class' => 'TextInputChild', 'expect' => false),
        array('name' => 'Contact.CustomFields.c.pets_name', 'class' => 'TextInputChild', 'expect' => null, 'data' => 'Fre'),

        array('name' => 'Asset.Product', 'class' => 'InputChild', 'expect' => null),
        array('name' => 'Asset.PurchasedDate', 'class' => 'DateInputChild', 'expect' => null),
        array('name' => 'Asset.Description', 'class' => 'DateInputChild', 'expect' => false),
    );

    function testGetDataType() {
        foreach ($this->testCases as $test) {
            $class = $test['class'];
            $attributes = ($test['name']) ? $this->_createAttributeArray($test['name']) : null;
            $widget = new $class($attributes);
            ob_start();
            $result = $widget->getData();
            ob_end_clean();
            $this->assertIdentical($test['expect'], $result, "{$test['name']} => {$test['class']}");
            if ($test['data']) {
                $data = $widget->getDataArray();
                $this->assertIdentical($test['data'], $data['value']);
            }
        }

        //@@@ QA 130715-000105 130619-000073 NamedIDOptLists and NamedIDLabels should have their IDs transformed so that
        //a default value and previous value can be set correctly on the field.

        $this->logIn('lwickland@rightnow.com.invalid');
        $attributes = $this->_createAttributeArray("Contact.MarketingSettings.EmailFormat");
        $widget = new SelectionInputChild($attributes);
        $this->assertIdentical(null, $widget->getData());
        $this->assertIdentical(2, $widget->field);
        $this->logOut();
    }

    function testGetDataTypeDirectly() {
        $attributes = $this->_createAttributeArray('Asset.Product');

        $cacheKey = 'Input_DataType_Asset.Product';
        $this->login();
        Framework::setCache($cacheKey, null);
        $input = new InputChild($attributes);
        $input->getData();
        $this->assertIdentical('SalesProduct', $input->dataType);
        $this->assertNull($input->field);

        Framework::setCache($cacheKey, null);
        $this->addUrlParameters(array('asset_id' => '4'));
        $input = new InputChild($attributes);
        $input->getData();
        $this->assertIdentical('SalesProduct', $input->dataType);
        $this->assertIdentical(6, $input->field);
        Framework::setCache($assetProductCacheKey, null);

        $this->logOut();
        Framework::setCache($cacheKey, null);
        $input = new InputChild($attributes);
        $input->getData();
        $this->assertIdentical('SalesProduct', $input->dataType);
        $this->assertNull($input->field);
        Framework::setCache($assetProductCacheKey, null);

        $this->restoreUrlParameters();

        $cacheKey = 'Input_DataType_Contact.Address.Country';
        Framework::setCache($cacheKey, null);
        $input = new InputChild($this->_createAttributeArray('Contact.Address.Country'));
        $input->getData();
        $this->assertIdentical('Country', $input->dataType);
        $this->assertNull($input->field);
        Framework::setCache($cacheKey, null);

        $this->login();
        Framework::setCache($cacheKey, null);
        $input = new InputChild($this->_createAttributeArray('Contact.Address.Country'));
        $input->getData();
        $this->assertIdentical('Country', $input->dataType);
        $this->assertIdentical(1, $input->field);

        $cacheKey = 'Input_DataType_Contact.Name.First';
        $this->logOut();
        Framework::setCache($cacheKey, null);
        $input = new InputChild($this->_createAttributeArray('Contact.Name.First'));
        $input->getData();
        $this->assertIdentical('String', $input->dataType);
        $this->assertNull($input->field);
        Framework::setCache($cacheKey, null);

        $this->login();
        Framework::setCache($cacheKey, null);
        $input = new InputChild($this->_createAttributeArray('Contact.Name.First'));
        $input->getData();
        $this->assertIdentical('String', $input->dataType);
        $this->assertIdentical('perpetual sla contact no org first', $input->field);
        Framework::setCache($cacheKey, null);

        $this->logOut();

        $cacheKey = 'Input_DataType_incidents.fattach';
        Framework::setCache($cacheKey, null);
        $input = new InputChild($this->_createAttributeArray('incidents.fattach'));
        $input->getData();
        $this->assertIdentical('Incident.FileAttachments', $input->data['attrs']['name']);
        $input = new InputChild($this->_createAttributeArray('incidents.fattach'));
        $input->getData();
        $this->assertIdentical('Incident.FileAttachments', $input->data['attrs']['name']);
        Framework::setCache($cacheKey, null);
    }

    function testinvalidSubObjectExists() {
        $invalidSubObjectExists = function($name, $table, $field) {
            $input = new FormInputChild(array('name' => new Attribute(array('name' => 'name', 'type' => 'STRING', 'value' => $name))));
            $reflectionMethod = new \ReflectionMethod($input, 'invalidSubObjectExists');
            $reflectionMethod->setAccessible(true);
            return $reflectionMethod->invokeArgs($input, array($name, $table, $field));
        };

        $this->assertFalse($invalidSubObjectExists('Incident.Product', 'Incident', 'Product'));
        $this->assertFalse($invalidSubObjectExists('Incident.Category', 'Incident', 'Category'));
        $this->assertFalse($invalidSubObjectExists('Contact.Organization.Login', 'Contact', 'Login'));
        $this->assertTrue($invalidSubObjectExists('Contact.Organization.Name', 'Contact', 'Name'));
        $this->assertFalse($invalidSubObjectExists('Contact.Address.Country', 'Contact', 'Country'));
        $this->assertFalse($invalidSubObjectExists('Contact.Login', 'Contact', 'Login'));
        $this->assertTrue($invalidSubObjectExists('Incident.PrimaryContact.Name.First', 'Incident', 'First'));
        $this->assertFalse($invalidSubObjectExists('Incident.Asset', 'Incident', 'Asset'));
        $this->assertFalse($invalidSubObjectExists('Asset.Status', 'Asset', 'Status'));
        $this->assertFalse($invalidSubObjectExists('Asset.Product', 'Asset', 'Product'));
    }

    function getSocialUserID() {
	$this->CI->router->setUriData();
        if ($user = \RightNow\Utils\Text::getSubstringAfter($this->CI->uri->uri_string(), __FUNCTION__ . '/user/')) {
            $this->addUrlParameters(array('user' => $user));
        }
        $this->logIn();
        $testWidget = new InputChild($this->_createAttributeArray(array(
            'name'  => 'Communityuser.DisplayName',
        )));
        $testWidget->bufferGetData();
        $data = $testWidget->getDataArray();
        $this->logOut();
        if ($user) {
            $this->restoreUrlParameters();
        }
        echo $data['socialUserID'];
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

    function testLabelDefault() {
        $CI = get_instance();

        // Changed to field's name: text field
        $this->logIn();
        $testWidget = new InputChild($this->_createAttributeArray(array(
            'name'  => 'Contact.Emails.PRIMARY.Address',
            'label_input' => '{default_label}',
        )));
        $testWidget->bufferGetData();
        $result = $testWidget->getDataArray();
        $this->assertSame('Address', $result['attrs']['label_input']);

        // Changed to field's name: menu field
        $contact = $CI->model('Contact')->get($CI->session->getProfileData('contactID'))->result;
        // $contact->CustomFields->c->pet_type = new \RightNow\Connect\v1_4\NamedIDLabel();
        // $contact->CustomFields->c->pet_type->ID = 1;
        $testWidget = new InputChild($this->_createAttributeArray(array(
            'name'  => 'Contact.CustomFields.c.pet_type',
            'label_input' => '{default_label}',
        )));
        $testWidget->bufferGetData();
        $result = $testWidget->getDataArray();
        $this->assertNotEqual('pet_type', $result['attrs']['label_input']);

        // The set value is honored
        $testWidget = new InputChild($this->_createAttributeArray(array(
            'name'  => 'Contact.Emails.PRIMARY.Address',
            'label_input' => 'nondefault',
        )));
        $testWidget->bufferGetData();
        $result = $testWidget->getDataArray();
        $this->assertSame('nondefault', $result['attrs']['label_input']);

        $this->logOut();
    }

    function testMask() {
        $testWidget = new InputChild($this->_createAttributeArray(array(
            'name'  => 'Contact.CustomFields.c.pets_name',
        )));
        $testWidget->bufferGetData();
        $result = $testWidget->getDataArray();
        $this->assertSame('ULMLML', $result['js']['mask']);
    }

    function testSetFieldValue(){
        $setFieldValue = function($name, $defaultValue = null, $dataType = null, $fieldValueToUse = null, $fieldMetaData = null, $widgetName = 'TextInputChild') {
            $testWidget = new $widgetName(array());
            $testWidget->data['attrs']['name'] = $name;
            if($defaultValue !== null){
                $testWidget->data['attrs']['default_value'] = $defaultValue;
            }
            if($dataType !== null){
                $testWidget->dataType = $dataType;
            }
            if($fieldValueToUse !== null){
                $testWidget->field = $fieldValueToUse;
            }
            if($fieldMetaData !== null) {
                $testWidget->fieldMetaData = $fieldMetaData;
                if($testWidget->dataType === 'Menu') {
                    $testWidget->fieldMetaData->type_name = 'RightNow\Connect\v1_4\CO\OutletType';
                }
            }
            $reflectionMethod = new \ReflectionMethod($testWidget, 'setFieldValue');
            $reflectionMethod->setAccessible(true);
            return $reflectionMethod->invoke($testWidget);
        };

        $this->assertIdentical('', $setFieldValue('Incident.Subject'));
        $this->assertIdentical('default value', $setFieldValue('Incident.Subject', 'default value'));
        //Get value from POST tests

        $_POST['Incident_Subject'] = "posted value";
        $this->assertIdentical('posted value', $setFieldValue('Incident.Subject'));
        unset($_POST['Incident_Subject']);

        $_POST['incidents_subject'] = "posted value";
        $this->assertIdentical('posted value', $setFieldValue('Incident.Subject'));
        unset($_POST['incidents_subject']);

        $_POST['Incident.Subject'] = "posted value";
        $this->assertIdentical('', $setFieldValue('Incident.Subject'));
        unset($_POST['Incident.Subject']);

        $_POST['Incident.Subject'] = false;
        $this->assertIdentical('', $setFieldValue('Incident.Subject'));
        unset($_POST['Incident.Subject']);

        $_POST['Incident_Subject'] = '';
        $this->assertIdentical('', $setFieldValue('Incident.Subject'));
        unset($_POST['Incident_Subject']);

        $_POST['Incident_Subject'] = "' and \"";
        $this->assertIdentical('&#039; and &quot;', $setFieldValue('Incident.Subject'));
        unset($_POST['Incident_Subject']);

        $_POST['Incident_Subject'] = "normal name";
        $_POST['incidents_subject'] = "old name";
        $_POST['Incident.Subject'] = "with period";
        $this->assertIdentical('normal name', $setFieldValue('Incident.Subject'));
        $this->assertIdentical('normal name', $setFieldValue('Incident.Subject', 'default value'));
        unset($_POST['Incident_Subject']);
        unset($_POST['incidents_subject']);
        unset($_POST['Incident.Subject']);

        // @@@ QA 130312-000003 - post values should take precedent over db field values
        $_POST['Incident_Subject'] = 'postValue';
        $this->assertIdentical('postValue', $setFieldValue('Incident.Subject', null, null, 'otherValue'));
        $_POST['Incident_Subject'] = '';
        $this->assertIdentical('', $setFieldValue('Incident.Subject', null, null, 'otherValue'));
        unset($_POST['Incident_Subject']);

        //Get value from URL tests
        $CI = get_instance();

        $existingSegments = $CI->router->segments;
        $existingDirectory= $CI->router->directory;
        $existingParameterSegment = $CI->config->item('parm_segment');

        $CI->router->segments = array('page', 'render', 'home', 'Incident.Subject', 'url test');
        $CI->router->directory = '';
        $CI->config->set_item('parm_segment', 4);

        $this->assertIdentical('url test', $setFieldValue('Incident.Subject'));

        $CI->router->segments[3] = "incidents.subject";
        $this->assertIdentical('url test', $setFieldValue('Incident.Subject'));

        $CI->router->segments[3] = "Incident_Subject";
        $this->assertIdentical('', $setFieldValue('Incident.Subject'));

        $CI->router->segments[3] = 'Incident.Subject';
        $CI->router->segments[5] = 'incidents.subject';
        $CI->router->segments[6] = 'old value as well';
        $this->assertIdentical('url test', $setFieldValue('Incident.Subject'));
        $_POST['Incident_Subject'] = "post test";
        $this->assertIdentical('post test', $setFieldValue('Incident.Subject'));
        unset($_POST['Incident_Subject']);
        $this->assertIdentical('url test', $setFieldValue('Incident.Subject', 'default value'));

        $CI->router->segments = array('page', 'render', 'home', 'Incident.CustomFields.c.date1', '8 November 2012');
        $this->assertIdentical('8 November 2012', $setFieldValue('Incident.CustomFields.c.date1', null, 'Date'));
        $this->assertIdentical('8 November 2012', $setFieldValue('Incident.CustomFields.c.date1', null, 'DateTime'));

        $CI->router->segments = $existingSegments;
        $CI->router->directory = $existingDirectory;
        $CI->config->set_item('parm_segment', $existingParameterSegment);

        $this->assertIdentical('existing value test', $setFieldValue('Incident.Subject', null, null, 'existing value test'));
        $this->assertIdentical(false, $setFieldValue('Incident.Subject', null, null, false));
        $this->assertIdentical(true, $setFieldValue('Incident.Subject', null, null, true));
        $this->assertIdentical(100, $setFieldValue('Incident.Subject', null, null, 100));
        $this->assertIdentical(-100, $setFieldValue('Incident.Subject', null, null, -100));
        $this->assertIdentical("&amp; and &quot; and &#039; and &lt; and &gt;" , $setFieldValue('Incident.Subject', null, null, '& and " and \' and < and >'));
        $this->assertIdentical(array(), $setFieldValue('Incident.Subject', null, null, array()));
        $this->assertIdentical(array(1, 2, 3), $setFieldValue('Incident.Subject', null, null, array(1, 2, 3)));
        $connectArray = new Connect\ConnectArray();
        $this->assertIdentical($connectArray, $setFieldValue('Incident.Subject', null, null, $connectArray));

        //@@@ QA 130313-000008 test dates
        $this->assertIdentical('', $setFieldValue('Incident.CustomFields.c.date1', null, 'DateTime', null));
        $this->assertIdentical(false, $setFieldValue('Incident.CustomFields.c.date1', null, 'DateTime', false));
        $this->assertIdentical('', $setFieldValue('Incident.CustomFields.c.date1', null, 'DateTime', ''));
        $this->assertIdentical('abc', $setFieldValue('Incident.CustomFields.c.date1', null, 'DateTime', 'abc'));
        $this->assertIdentical('2000-3-13 00:00:00', $setFieldValue('Incident.CustomFields.c.date1', null, 'DateTime', '2000-3-13 00:00:00'));
        $this->assertIdentical('2000-3-13 12:34:56', $setFieldValue('Incident.CustomFields.c.date1', null, 'DateTime', '2000-3-13 12:34:56'));
        $this->assertIdentical('952976096', $setFieldValue('Incident.CustomFields.c.date1', null, 'DateTime', '952976096'));
        $this->assertIdentical(952976096, $setFieldValue('Incident.CustomFields.c.date1', null, 'DateTime', 952976096));

        $this->assertIdentical('', $setFieldValue('Incident.CustomFields.c.date1', null, 'DateTime'));
        $this->assertIdentical(false, $setFieldValue('Incident.CustomFields.c.date1', false, 'DateTime'));
        $this->assertIdentical('', $setFieldValue('Incident.CustomFields.c.date1', '', 'DateTime'));
        $this->assertIdentical('abc', $setFieldValue('Incident.CustomFields.c.date1', 'abc', 'DateTime'));
        $this->assertIdentical('2000-3-13 00:00:00', $setFieldValue('Incident.CustomFields.c.date1', '2000-3-13 00:00:00', 'DateTime'));
        $this->assertIdentical('2000-3-13 12:34:56', $setFieldValue('Incident.CustomFields.c.date1', '2000-3-13 12:34:56', 'DateTime'));
        $this->assertIdentical('952976096', $setFieldValue('Incident.CustomFields.c.date1', '952976096', 'DateTime'));
        $this->assertIdentical(952976096, $setFieldValue('Incident.CustomFields.c.date1', 952976096, 'DateTime'));

        $_POST['Incident_CustomFields_c_date1'] = null;
        $this->assertIdentical('', $setFieldValue('Incident.CustomFields.c.date1', null, 'DateTime'));
        $_POST['Incident_CustomFields_c_date1'] = false;
        $this->assertIdentical('', $setFieldValue('Incident.CustomFields.c.date1', null, 'DateTime'));
        $_POST['Incident_CustomFields_c_date1'] = '';
        $this->assertIdentical('', $setFieldValue('Incident.CustomFields.c.date1', null, 'DateTime'));
        $_POST['Incident_CustomFields_c_date1'] = 'abc';
        $this->assertIdentical('abc', $setFieldValue('Incident.CustomFields.c.date1', null, 'DateTime'));
        $_POST['Incident_CustomFields_c_date1'] = '2000-3-13 00:00:00';
        $this->assertIdentical('2000-3-13 00:00:00', $setFieldValue('Incident.CustomFields.c.date1', null, 'DateTime'));
        $_POST['Incident_CustomFields_c_date1'] = '2000-3-13 12:34:56';
        $this->assertIdentical('2000-3-13 12:34:56', $setFieldValue('Incident.CustomFields.c.date1', null, 'DateTime'));
        $_POST['Incident_CustomFields_c_date1'] = '952976096';
        $this->assertIdentical('952976096', $setFieldValue('Incident.CustomFields.c.date1', null, 'DateTime'));
        $_POST['Incident_CustomFields_c_date1'] = 952976096;
        $this->assertIdentical('952976096', $setFieldValue('Incident.CustomFields.c.date1', null, 'DateTime'));
        unset($_POST['Incident_CustomFields_c_date1']);

        //@@@ QA 130417-000127 - custom attributes with default values
        $CI->router->segments = array('page', 'render', 'home', 'Incident.CustomFields.CO.FieldInteger', null);
        $CI->router->directory = '';
        $CI->config->set_item('parm_segment', 4);

        $this->assertIdentical(42, $setFieldValue('Incident.CustomFields.CO.FieldInteger', 34, 'Integer', 42));
        $CI->router->segments[4] = 65;
        $this->assertIdentical('65', $setFieldValue('Incident.CustomFields.CO.FieldInteger', 34, 'Integer', null));

        $CI->router->segments = array('page', 'render', 'home', 'Incident.CustomFields.c.date1', null);
        $CI->router->directory = '';
        $CI->config->set_item('parm_segment', 4);

        $this->assertIdentical('', $setFieldValue('Incident.CustomFields.c.date1', null, 'DateTime'));
        $CI->router->segments[4] = false;
        $this->assertIdentical('', $setFieldValue('Incident.CustomFields.c.date1', null, 'DateTime'));
        $CI->router->segments[4] = '';
        $this->assertIdentical('', $setFieldValue('Incident.CustomFields.c.date1', null, 'DateTime'));
        $CI->router->segments[4] = 'abc';
        $this->assertIdentical('abc', $setFieldValue('Incident.CustomFields.c.date1', null, 'DateTime'));
        $CI->router->segments[4] = '2000-3-13 00:00:00';
        $this->assertIdentical('2000-3-13 00:00:00', $setFieldValue('Incident.CustomFields.c.date1', null, 'DateTime'));
        $CI->router->segments[4] = '2000-3-13 12:34:56';
        $this->assertIdentical('2000-3-13 12:34:56', $setFieldValue('Incident.CustomFields.c.date1', null, 'DateTime'));
        $CI->router->segments[4] = '952976096';
        $this->assertIdentical('952976096', $setFieldValue('Incident.CustomFields.c.date1', null, 'DateTime'));
        $CI->router->segments[4] = 952976096;
        $this->assertIdentical('952976096', $setFieldValue('Incident.CustomFields.c.date1', null, 'DateTime'));

        //@@@ QA 130320-000031
        $CI->router->segments[4] = '2008-2-29 00:00:00';
        $this->assertIdentical('2008-2-29 00:00:00', $setFieldValue('Incident.CustomFields.c.date1', null, 'DateTime'));
        $CI->router->segments[4] = '2009-2-29 12:34:56';
        $this->assertIdentical('2009-2-29 12:34:56', $setFieldValue('Incident.CustomFields.c.date1', null, 'DateTime'));

        $CI->router->segments = $existingSegments;
        $CI->router->directory = $existingDirectory;

        $CI->config->set_item('parm_segment', $existingParameterSegment);

        //@@@ QA 130603-000039 An incident thread URL default value should be correctly applied
        $this->addUrlParameters(array('Incident.Threads' => 'Test thread value'));
        list($field, $metaData) = \RightNow\Utils\Connect::getObjectField(array('Incident', 'Threads'));
        $this->assertIdentical('Test thread value', $setFieldValue('Incident.Threads', null, 'Thread', $field, $metaData));
        $this->restoreUrlParameters();

        // Menu fields
        $field = (object) array('ID' => 1, 'DisplayName' => 'itemTheFirst');
        $meta = (object) array('is_menu' => true);
        $this->assertIdentical($field->ID, $setFieldValue('Incident.CustomFields.CO.ic_menu_attr', 1, 'Menu', $field, $meta, 'SelectionInputChild')->ID);
        $this->assertIdentical($field, $setFieldValue('Incident.CustomFields.CO.ic_menu_attr', null, 'Menu', $field, $meta, 'SelectionInputChild'));

        $field = (object) array('ID' => null, 'DisplayName' => null);
        $meta = (object) array('is_menu' => true);
        $this->assertIdentical($field, $setFieldValue('Incident.CustomFields.CO.ic_menu_attr', null, 'Menu', $field, $meta, 'SelectionInputChild'));
    }

    function testGetPostAndUrlDefaults() {
        $widget = new SelectionInputChild(array());
        $fieldName = 'Incident.CustomFields.CO.ic_menu_attr';
        $method = new \ReflectionMethod($widget, 'getPostAndUrlDefaults');
        $method->setAccessible(true);

        $expected = array('url' => null, 'post' => false);
        $actual = $method->invoke($widget, $fieldName);
        $this->assertIdentical($expected, $actual);

        $this->addUrlParameters(array($fieldName => 1));
        $_POST[str_replace('.', '_', $fieldName)] = 2;
        $expected = array('url' => '1', 'post' => 2);
        $actual = $method->invoke($widget, $fieldName);
        $this->assertIdentical($expected, $actual);
        unset($_POST[str_replace('.', '_', $fieldName)]);
        $this->restoreUrlParameters();
    }

    function testGetDefaultValues() {
        $widget = new SelectionInputChild(array());
        $fieldName = 'Incident.CustomFields.CO.ic_menu_attr';
        $method = new \ReflectionMethod($widget, 'getDefaultValues');
        $method->setAccessible(true);

        $expected = array (
            'url' => null,
            'post' => false,
            'meta' => '',
            'attrs' => null,
        );
        $actual = $method->invoke($widget, $fieldName);
        $this->assertIdentical($expected, $actual);

        // Set everything, ensure 'url' specified as dynamic
        $this->addUrlParameters(array($fieldName => 1));
        $_POST[str_replace('.', '_', $fieldName)] = 2;
        $widget->fieldMetaData = (object) array('default' => 3);
        $widget->data['attrs']['default_value'] = 4;
        $expected = array(
            'url' => '1',
            'post' => '2',
            'meta' => 3,
            'attrs' => 4,
            'dynamic' => 'url',
        );
        $actual = $method->invoke($widget, $fieldName);
        $this->assertIdentical($expected, $actual);
        unset($_POST[str_replace('.', '_', $fieldName)]);
        $this->restoreUrlParameters();
        $widget->fieldMetaData = (object) array('default' => '');

        // When 'attrs' set and not 'url', ensure 'attrs' set as dynamic
        $widget->data['attrs']['default_value'] = 4;
        $expected = array(
            'url' => null,
            'post' => false,
            'meta' => '',
            'attrs' => 4,
            'dynamic' => 'attrs',
        );
        $actual = $method->invoke($widget, $fieldName);
        $this->assertIdentical($expected, $actual);
        unset($_POST[str_replace('.', '_', $fieldName)]);
        $this->restoreUrlParameters();
    }

    function testGetMenuFieldValue() {
        $menuParam = (object) array(
            'ID' => 1,
            'Name' => 'itemTheFirst',
            'DisplayName' => 'itemTheFirst',
        );

        $widget = new SelectionInputChild(array());
        $fieldName = 'Incident.CustomFields.CO.ic_menu_attr';
        $getDefaultValues = new \ReflectionMethod($widget, 'getDefaultValues');
        $getDefaultValues->setAccessible(true);
        $defaultParam = $getDefaultValues->invoke($widget, $fieldName);
        $method = new \ReflectionMethod($widget, 'getMenuFieldValue');
        $method->setAccessible(true);

        $fieldValue = $method->invoke($widget, $menuParam, $defaultParam);
        $this->assertIdentical($menuParam, $fieldValue);

        $this->logIn('slatest');
        $attributes = $this->_createAttributeArray("Contact.MarketingSettings.EmailFormat");
        $defaultParam = array(
            'post' => true
        );
        $fieldMetaData = (object) array(
            'type_name' => 'MockConnectObject'
        );

        // Check for menu object - does not exist
        $widget = new SelectionInputChild($attributes);
        $widget->getData();
        $widget->table = null;
        $widget->fieldMetaData = $fieldMetaData;

        $method = new \ReflectionMethod($widget, 'getMenuFieldValue');
        $method->setAccessible(true);
        $fieldValue = $method->invoke($widget, $menuParam, $defaultParam);
        $this->assertIdentical('cucumber', $fieldValue);

        // Check for menu object - does exist
        $widget = new SelectionInputChild($attributes);
        $widget->getData();
        $widget->fieldMetaData = $fieldMetaData;

        $method = new \ReflectionMethod($widget, 'getMenuFieldValue');
        $method->setAccessible(true);
        $fieldValue = $method->invoke($widget, $menuParam, $defaultParam);
        $this->assertIdentical($menuParam, $fieldValue);

        $this->logOut('slatest');
    }

    //@@@ QA 130313-000008 test dates
    function testModifyDateValue(){
        $modifyDateValue = function($fieldValue = null, $dataType = 'String') {
            $testWidget = new TextInputChild(array());
            $testWidget->dataType = $dataType;
            $reflectionMethod = new \ReflectionMethod($testWidget, 'modifyDateValue');
            $reflectionMethod->setAccessible(true);
            return $reflectionMethod->invoke($testWidget, $fieldValue);
        };

        $this->assertIdentical(null, $modifyDateValue(null));
        $this->assertIdentical(false, $modifyDateValue(false));
        $this->assertIdentical('', $modifyDateValue(''));
        $this->assertIdentical('abc', $modifyDateValue('abc'));
        $this->assertIdentical('2000-3-13 00:00:00', $modifyDateValue('2000-3-13 00:00:00'));
        $this->assertIdentical('2000-3-13 12:34:56', $modifyDateValue('2000-3-13 12:34:56'));
        $this->assertIdentical('952976096', $modifyDateValue('952976096'));
        $this->assertIdentical(952976096, $modifyDateValue(952976096));

        $this->assertIdentical(null, $modifyDateValue(null, 'Date'));
        $this->assertIdentical(false, $modifyDateValue(false, 'Date'));
        $this->assertIdentical('', $modifyDateValue('', 'Date'));
        $this->assertIdentical('abc', $modifyDateValue('abc', 'Date'));
        $this->assertIdentical('2000-3-13 00:00:00', $modifyDateValue('2000-3-13 00:00:00', 'Date'));
        $this->assertIdentical('2000-3-13 12:34:56', $modifyDateValue('2000-3-13 12:34:56', 'Date'));
        $this->assertIdentical('952976096', $modifyDateValue('952976096', 'Date'));
        $this->assertIdentical(952976096, $modifyDateValue(952976096, 'Date'));

        $this->assertIdentical(null, $modifyDateValue(null, 'DateTime'));
        $this->assertIdentical(false, $modifyDateValue(false, 'DateTime'));
        $this->assertIdentical('', $modifyDateValue('', 'DateTime'));
        $this->assertIdentical('abc', $modifyDateValue('abc', 'Date'));
        $this->assertIdentical('2000-3-13 00:00:00', $modifyDateValue('2000-3-13 00:00:00', 'DateTime'));
        $this->assertIdentical('2000-3-13 12:34:56', $modifyDateValue('2000-3-13 12:34:56', 'DateTime'));
        $this->assertIdentical('952976096', $modifyDateValue('952976096', 'DateTime'));
        $this->assertIdentical(952976096, $modifyDateValue(952976096, 'DateTime'));

        //@@@ QA 130320-000031
        $this->assertIdentical('2008-2-29 00:00:00', $modifyDateValue('2008-2-29 00:00:00', 'DateTime'));
        $this->assertIdentical('2009-2-29 12:34:56', $modifyDateValue('2009-2-29 12:34:56', 'DateTime'));
    }

    function testCache() {
        $this->logIn();
        $fieldName = 'Contact.Name.First';
        $widget = new InputChild($this->_createAttributeArray($fieldName));
        $method = new \ReflectionMethod($widget, 'cache');
        $method->setAccessible(true);

        // set
        $widget->getData();
        $dataIn = $method->invoke($widget, 'set');
        $this->assertIdentical($fieldName, $dataIn['name']);
        $this->assertIdentical($fieldName, $dataIn['inputName']);
        $this->assertIdentical('First', $dataIn['fieldName']);
        $this->assertNotNull($dataIn['meta']);

        // get
        $dataOut = $method->invoke($widget, 'get');
        $this->assertIdentical($dataIn, $dataOut);

        // set error
        $cacheData = array('error' => "something's not right");
        $dataIn = $method->invoke($widget, 'set', $cacheData);
        $this->assertIdentical($cacheData, $dataIn);
        $this->assertIdentical($cacheData, $method->invoke($widget, 'get'));

        $this->logOut();
    }

    function testSetPropertiesFromCache() {
        $widget = new InputChild($this->_createAttributeArray('Contact.Name.First'));
        $method = new \ReflectionMethod($widget, 'setPropertiesFromCache');
        $method->setAccessible(true);


        // Verify properties are null, as `getData` not yet called
        $this->assertNull($widget->data['attrs']['name']);
        $this->assertNull($widget->data['inputName']);
        $this->assertNull($widget->field);
        $this->assertNull($widget->table);
        $this->assertNull($widget->fieldName);
        $this->assertNull($widget->dataType);
        $this->assertNull($widget->data['js']['mask']);
        $this->assertNull($widget->data['socialUserID']);
        $this->assertNull($widget->fieldMetaData);

        $cacheData = array(
            'name' => 'Contact.Name.First',
            'inputName' => 'Contact.Name.First',
            'field' => 'perpetual sla contact no org first',
            'table' => 'Contact',
            'fieldName' => 'First',
            'dataType' => 'String',
            'mask' => 'zorro',
            'socialUserID' => 123,
            'meta' => serialize(array('one' => 1, 'two' => 2, 'three' => 3)),
        );

        $method->invoke($widget, $cacheData);

        $this->assertEqual($cacheData['name'], $widget->data['attrs']['name']);
        $this->assertEqual($cacheData['inputName'], $widget->data['inputName']);
        $this->assertEqual($cacheData['field'], $widget->field);
        $this->assertEqual($cacheData['fieldName'], $widget->fieldName);
        $this->assertEqual($cacheData['dataType'], $widget->dataType);
        $this->assertEqual($cacheData['mask'], $widget->data['js']['mask']);
        $this->assertEqual($cacheData['socialUserID'], $widget->data['socialUserID']);
        $this->assertEqual(unserialize($cacheData['meta']), $widget->fieldMetaData);
    }
}
