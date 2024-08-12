<?php
use RightNow\Api,
    RightNow\Utils\Framework,
    RightNow\Connect\v1_4 as ConnectPHP,
    RightNow\Internal\Libraries\ConnectMetaData;

RightNow\UnitTest\Helper::loadTestedFile(__FILE__);

class ConnectMetaDataTest extends CPTestCase {
    public $testingClass = 'RightNow\Internal\Libraries\ConnectMetaData';

    function testGetInstance()
    {
        $instance1 = ConnectMetaData::getInstance();
        $instance2 = ConnectMetaData::getInstance();
        $this->assertSame($instance1, $instance2);
    }

    function testGetMetaData()
    {
        $instance = ConnectMetaData::getInstance();
        $metaData = $instance->getMetaData();
        $this->assertTrue(is_array($metaData));

        $metaDataKeys = array_keys($metaData);
        $supportedObjects = array('Answer', 'Contact', 'Incident', 'Asset', 'ServiceProduct', 'ServiceCategory',
            'CommunityQuestion' , 'CommunityUser', 'CommunityComment');
        sort($supportedObjects);
        $this->assertIdentical($metaDataKeys, $supportedObjects);

        $this->assertTrue(array_key_exists('AnswerType', $metaData['Answer']));
        $this->assertTrue(array_key_exists('namedValues', $metaData['Answer']['AnswerType']));
        $this->assertTrue(is_array($metaData['Answer']['AnswerType']['namedValues']));
        $this->assertSame(3, count($metaData['Answer']['AnswerType']['namedValues']));

        $this->assertTrue(array_key_exists('Emails.PRIMARY.Address', $metaData['Contact']));
        $this->assertTrue(array_key_exists('Emails.ALT1.Address', $metaData['Contact']));
        $this->assertTrue(array_key_exists('Emails.ALT2.Address', $metaData['Contact']));
        $this->assertTrue(array_key_exists('constraints', $metaData['Contact']['Emails.PRIMARY.Address']['metaData']));
    }

    function testGetConstraintMapping()
    {
        $instance = ConnectMetaData::getInstance();
        $constraintMapping = $instance->getConstraintMapping();
        $this->assertTrue(is_array($constraintMapping));
        $this->assertIdentical(count($constraintMapping), 8);
        $this->assertIdentical($constraintMapping[1], "Minimum");
        $this->assertIdentical($constraintMapping[2], "Maximum");
        $this->assertIdentical($constraintMapping[3], "Minimum Length");
        $this->assertIdentical($constraintMapping[4], "Maximum Length");
        $this->assertIdentical($constraintMapping[5], "Maximum Bytes");
        $this->assertIdentical($constraintMapping[6], "in");
        $this->assertIdentical($constraintMapping[7], "Not");
        $this->assertIdentical($constraintMapping[8], "Pattern");
    }

    function testBuildFieldStrings(){
        list($reflectionClass, $buildFieldStrings, $metaDataProperty, $fieldStringsProperty) = $this->reflect(
            'method:buildFieldStrings', 'metaData', 'fieldStrings');
        $instance = ConnectMetaData::getInstance();

        //Unset some data in fieldStrings since it's populated in the constructor, which makes testing less than ideal
        $fieldStringsValue = $fieldStringsProperty->getValue($instance);
        unset($fieldStringsValue['Contact']['CustomFields.c.text1']);
        $fieldStringsProperty->setValue($instance, $fieldStringsValue);

        $buildFieldStrings->invoke($instance, 'Contact', 'CustomFields.c', 'ContactCustomFieldsc');

        $fieldStringsValue = $fieldStringsProperty->getValue($instance);
        $winnerCustomField = $fieldStringsValue['Contact']['CustomFields.c.text1'];
        $this->assertIdentical(true, is_array($winnerCustomField));
        $this->assertIdentical(true, is_array($winnerCustomField['namedValues']));
        $this->assertIdentical(true, is_object($winnerCustomField['metaData']));
        $this->assertIdentical(false, $winnerCustomField['metaData']->is_read_only_for_create);
        $this->assertIdentical(false, $winnerCustomField['metaData']->is_read_only_for_update);
        $this->assertIdentical(true, is_array($winnerCustomField['metaData']->constraints));
    }

    function testBuildFieldStringsForMenuOnlyAttributes(){
        list($reflectionClass, $buildFieldStrings, $metaDataProperty, $fieldStringsProperty, $primaryClassNamesProperty) = $this->reflect(
            'method:buildFieldStrings', 'metaData', 'fieldStrings', 'primaryClassNames');
        $instance = ConnectMetaData::getInstance();
        $fieldStringsProperty->setValue($instance, array());
        $primaryClassNamesProperty->setValue($instance, array('CO\\my_menu_only_object' => 'RightNow\\Connect\\v1_4\\CO\\my_menu_only_object'));
        $metaDataProperty->setValue($instance, array('IncidentCustomFieldsCO' => array('custom_menu_thing' => (object) array(
           'type_name' => 'RightNow\\Connect\\v1_4\\CO\\my_menu_only_object',
           'COM_type' => 'CO\\my_menu_only_object',
           'name' => 'custom_menu_thing',
           'container_class' => 'RightNow\\Connect\\v1_4\\IncidentCustomFieldsCO',
           'is_menu' => true,
           'is_enumerable' => true,
        ))));
        $buildFieldStrings->invoke($instance, 'Incident', 'CustomFields.CO', 'IncidentCustomFieldsCO');
        $fieldStrings = $fieldStringsProperty->getValue($instance);
        $this->assertIsA($fieldStrings['Incident']['CustomFields.CO.custom_menu_thing']['metaData'], 'stdClass');
    }

    function testFormatDateMeta() {
        $instance = ConnectMetaData::getInstance();
        $method = new \ReflectionMethod($instance, 'formatDateMeta');
        $method->setAccessible(true);

        $meta = (object) array(
            'COM_type' => 'Date',
            'default' => 1370242800
        );
        $results = $method->invoke($instance, $meta);
        $this->assertIdentical($results->default, '06/03/2013');

        $meta = (object) array(
            'COM_type' => 'DateTime',
            'default' => 1370242800
        );
        $results = $method->invoke($instance, $meta);
        $this->assertIdentical($results->default, '06/03/2013 01:00 AM');

        $meta = (object) array(
            'COM_type' => 'String',
            'default' => 1370242800
        );
        $expected = "Not a date type: 'String'";
        try {
            $method->invoke($instance, $meta);
            $this->fail("Expected '$expected'");
        }
        catch (\Exception $e) {
            $this->assertEqual($expected, $e->getMessage());
        }

        $meta = (object) array(
            'COM_type' => 'Date',
            'default' => null
        );
        $results = $method->invoke($instance, $meta);
        $this->assertIdentical($meta, $results);

        $meta = (object) array();
        $expected = "Not a date type: ''";
        try {
            $method->invoke($instance, $meta);
            $this->fail("Expected '$expected'");
        }
        catch (\Exception $e) {
            $this->assertEqual($expected, $e->getMessage());
        }

        // @@@ QA 130614-000091 Date constraints for Date and DateTime fields should be converted
        $meta = (object) array(
            'COM_type' => 'Date',
            'constraints' => array(
                (object) array(
                    'kind' => ConnectPHP\Constraint::Min,
                    'value' => -2145830400
                ),
                (object) array(
                    'kind' => ConnectPHP\Constraint::Max,
                    'value' => 2147385599
                )
            )
        );
        $results = $method->invoke($instance, $meta);
        $this->assertIdentical($meta->constraints['0']->value, '01/02/1902');
        $this->assertIdentical($meta->constraints['1']->value, '01/17/2038');

        $meta = (object) array(
            'COM_type' => 'DateTime',
            'constraints' => array(
                (object) array(
                    'kind' => ConnectPHP\Constraint::Min,
                    'value' => 86400
                ),
                (object) array(
                    'kind' => ConnectPHP\Constraint::Max,
                    'value' => 2147338799
                )
            )
        );
        $results = $method->invoke($instance, $meta);

        $this->assertIdentical($meta->constraints['0']->value, '01/02/1970 12:00 AM');
        $this->assertIdentical($meta->constraints['1']->value, '01/17/2038 10:59 AM');
    }

    // @@@ QA 130104-000060 - Testing requiredness for old style custom fields
    function testCustomFieldRequired() {
        $instance = ConnectMetaData::getInstance();
        $fieldStringsProperty = new \ReflectionProperty($instance, 'fieldStrings');
        $fieldStringsProperty->setAccessible(true);
        $fieldStringsMethod = new \ReflectionMethod($instance, 'buildFieldStrings');
        $fieldStringsMethod->setAccessible(true);

        //pull currently cache custom field info
        $cachedValue = Framework::checkCache("getCustomField-Incident-yesno1");
        //set the cached value
        $cachedValue['required'] = true;
        Framework::setCache("getCustomField-Incident-yesno1", $cachedValue);

        //call the private constructor of ConnectMetaData to re-pull in custom field info
        $refClass = new \ReflectionClass('RightNow\Internal\Libraries\ConnectMetaData');
        $constructor = $refClass->getConstructor();
        $constructor->setAccessible(true);
        $constructor->invoke($instance);

        //actually check the metadata
        $fieldStringsMethod->invoke($instance, 'Incident', 'CustomFields.c', 'IncidentCustomFieldsc');
        $fieldStringsValue = $fieldStringsProperty->getValue($instance);
        $this->assertTrue($fieldStringsValue['Incident']['CustomFields.c.yesno1']['metaData']->is_required_for_create);
        $this->assertTrue($fieldStringsValue['Incident']['CustomFields.c.yesno1']['metaData']->is_required_for_update);

        //reset the cached value
        $cachedValue['required'] = false;
        Framework::setCache("getCustomField-Incident-yesno1", $cachedValue);

        //call the private constructor of ConnectMetaData to re-pull in custom field info
        $constructor->invoke($instance);

        $constructor->invoke($instance);
        $instance = ConnectMetaData::getInstance();
        $fieldStringsProperty = new \ReflectionProperty($instance, 'fieldStrings');
        $fieldStringsProperty->setAccessible(true);
        $fieldStringsMethod = new \ReflectionMethod($instance, 'buildFieldStrings');
        $fieldStringsMethod->setAccessible(true);

        $fieldStringsMethod->invoke($instance, 'Incident', 'CustomFields.c', 'IncidentCustomFieldsc');
        $fieldStringsValue = $fieldStringsProperty->getValue($instance);
        $this->assertFalse($fieldStringsValue['Incident']['CustomFields.c.yesno1']['metaData']->is_required_for_create);
        $this->assertFalse($fieldStringsValue['Incident']['CustomFields.c.yesno1']['metaData']->is_required_for_update);
    }

    function testIsDate() {
        $instance = ConnectMetaData::getInstance();
        $method = new \ReflectionMethod($instance, 'isDate');
        $method->setAccessible(true);

        $this->assertTrue($method->invoke($instance, (object) array('COM_type' => 'Date')));
        $this->assertTrue($method->invoke($instance, (object) array('COM_type' => 'DateTime')));
        $this->assertTrue($method->invoke($instance, 'DateTime'));
        $this->assertTrue($method->invoke($instance, 'Date'));
        $this->assertFalse($method->invoke($instance, (object) array('COM_type' => 'String')));
        $this->assertFalse($method->invoke($instance, 'String'));
    }
}
