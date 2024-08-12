<?php

use RightNow\Connect\v1_4 as ConnectPHP,
    RightNow\Utils\Text,
    RightNow\Utils\Validation;

RightNow\UnitTest\Helper::loadTestedFile(__FILE__);

class ValidationTest extends CPTestCase {
    public $testingClass = 'RightNow\Utils\Validation';

    function __construct() {
        $this->CI = get_instance();
    }

    function testGetPasswordRequirements() {
        $method = $this->getMethod('getPasswordRequirements');

        $results = $method(null);
        $this->assertIdentical(array(), $results);
        $results = $method('');
        $this->assertIdentical(array(), $results);
        $results = $method(array(), 'expiration');
        $this->assertIdentical(array(), $results);
        $results = $method((object) array(), 'all');
        $this->assertIdentical(array('validations' => array(), 'expiration' => array()), $results);

        $expected = array(
            'validations' => array(
                'length' => array(
                    'bounds' => 'min',
                    'count' => 10,
                ),
                'occurrences' => array(
                    'bounds' => 'max',
                    'count' => 3,
                ),
                'old' => array(
                    'bounds' => 'max',
                    'count' => 1,
                ),
                'repetitions' => array(
                    'bounds' => 'max',
                    'count' => 2,
                ),
                'lowercase' => array(
                    'bounds' => 'min',
                    'count' => 3,
                ),
                'specialAndDigits' => array(
                    'bounds' => 'min',
                    'count' => 6,
                ),
                'special' => array(
                    'bounds' => 'min',
                    'count' => 5,
                ),
                'uppercase' => array(
                    'bounds' => 'min',
                    'count' => 6,
                ),
            ),
            'expiration' => array(
                'interval' => 30,
                'gracePeriod' => 2,
                'warningPeriod' => 10,
            ),
        );
        $meta = (object) array(
            'constraints' => array(
                (object) array(
                    'kind' => ConnectPHP\Constraint::MinLength,
                    'value' => $expected['validations']['length']['count'],
                ),
                (object) array(
                    'kind' => ConnectPHP\Constraint::MaxLength,
                    'value' => 20,
                ),
                (object) array(
                    'kind' => 9,
                    'value' => $expected['expiration']['interval'],
                ),
                (object) array(
                    'kind' => 10,
                    'value' => $expected['expiration']['gracePeriod'],
                ),
                (object) array(
                    'kind' => 11,
                    'value' => $expected['expiration']['warningPeriod'],
                ),
                (object) array(
                    'kind' => 12,
                    'value' => $expected['validations']['occurrences']['count'],
                ),
                (object) array(
                    'kind' => 13,
                    'value' => $expected['validations']['old']['count'],
                ),
                (object) array(
                    'kind' => 14,
                    'value' => $expected['validations']['repetitions']['count'],
                ),
                (object) array(
                    'kind' => 15,
                    'value' => $expected['validations']['lowercase']['count'],
                ),
                (object) array(
                    'kind' => 16,
                    'value' => $expected['validations']['specialAndDigits']['count'],
                ),
                (object) array(
                    'kind' => 17,
                    'value' => $expected['validations']['special']['count'],
                ),
                (object) array(
                    'kind' => 18,
                    'value' => $expected['validations']['uppercase']['count'],
                ),
            ),
        );
        $results = $method($meta, 'all');
        $this->assertIdentical($expected['expiration'], $results['expiration']);
        $this->assertIdentical($expected['validations'], $results['validations']);

        $validations = $method($meta);
        $this->assertIsA($validations, 'array');
        $this->assertTrue(count($validations) > 0);

        $validations2 = $method($meta, 'validations');
        $this->assertIdentical($validations, $validations2);

        $interval = $method($meta, 'expiration');
        $this->assertIsA($interval, 'array');
        $this->assertTrue(count($interval) > 0);

        $all = $method($meta, 'all');
        $this->assertIsA($all, 'array');
        $this->assertSame(2, count($all));
        $this->assertIdentical($interval, $all['expiration']);
        $this->assertIdentical($validations2, $all['validations']);
    }


    function testValidateFields() {
        //Validation should be skipped on a non-existent object
        $errors = $warnings = array();
        $this->assertTrue(Validation::validateFields(
            array(
                'NonExistentObject.Test.Test' => (object) array(
                    'required' => true,
                    'value' => ''
                )
            ),
            $errors, $warnings
        ));
        $this->assertTrue(count($errors) === 0);
        $this->assertTrue(count($warnings) === 0);

        //Valid field with valid constraints
        $this->assertTrue(Validation::validateFields(
            array(
                'Contact.CustomFields.c.int1' => (object) array(
                    'required' => false,
                    'value' => '5',
                    'constraints' => array(
                        'minValue' => 4,
                        'maxValue' => 6
                    )
                )
            ),
            $errors, $warnings
        ));
        $this->assertTrue(count($errors) === 0);
        $this->assertTrue(count($warnings) === 0);

        //Valid field that does not meet constraints
        $this->assertFalse(Validation::validateFields(
            array(
                'Contact.CustomFields.c.int1' => (object) array(
                    'required' => false,
                    'value' => '7',
                    'constraints' => array(
                        'minValue' => 4,
                        'maxValue' => 6
                    )
                )
            ),
            $errors, $warnings
        ));
        $this->assertTrue(count($errors) === 1);
        $this->assertTrue(count($warnings) === 0);
    }

    function testValidate() {
        //When a label is given, it should override the label provided by the metadata 
        $errors = array();
        $this->assertFalse(Validation::validate(
            (object) array(
                'label' => 'Given Name',
                'value' => '',
                'required' => true
            ),
            'API Name',
            (object) array(
                'name' => 'Metadata Name'
            ),
            $errors
        ));
        $this->assertTrue(count($errors) === 1);
        $this->assertTrue(Text::stringContains($errors[0], 'Given Name'));

        //When a label is NOT given, the metadata label should be used 
        $errors = array();
        $this->assertFalse(Validation::validate(
            (object) array(
                'value' => '',
                'required' => true
            ),
            'API Name',
            (object) array(
                'name' => 'Metadata Name'
            ),
            $errors
        ));
        $this->assertTrue(count($errors) === 1);
        $this->assertTrue(Text::stringContains($errors[0], 'Metadata Name'));

        //All fields except password are trimmed and '', null and false don't meet required checks
        $values = array(
            'Contact.NewPassword' => '   ',
            'Contact.Login' => '  test  ',
            'Contact.Login2' => '',
            'Contact.Login3' => '   ',
            'Contact.Login4' => false,
            'Contact.Login5' => null,
        );
        $expected = array(
            true, 
            true,
            false,
            false,
            false,
            false
        );

        $result = $errors = array();
        foreach($values as $name => $value) {
            $result[] = Validation::validate((object) array('value' => $value, 'required' => true), $name, (object) array('name' => $name), $errors);
        }
        $this->assertIdentical($result, $expected);
        
        //Given an array of errors, they array should always be appended
        $errors = array('firstError');

        //Required error
        Validation::validate((object) array('value' => '', 'required' => true), 'test', new \stdClass(), $errors);
        $this->assertTrue(count($errors) === 2);
        $this->assertIdentical($errors[0], 'firstError');

        //Data Type error
        Validation::validate((object) array('value' => 'abcd'), 'test', (object) array('COM_type' => 'Integer'), $errors);
        $this->assertTrue(count($errors) === 3);
        $this->assertIdentical($errors[0], 'firstError');

        //Constraint error
        Validation::validate((object) array('value' => '6', 'constraints' => array('minValue' => 7)), 'test', (object) array('COM_type' => 'Integer'), $errors);
        $this->assertTrue(count($errors) === 4);
        $this->assertIdentical($errors[0], 'firstError');

        //Data Consistency error
        Validation::validate((object) array('value' => 'test', 'requireValidation' => true), 'test', new \stdClass(), $errors);
        $this->assertTrue(count($errors) === 5);
        $this->assertIdentical($errors[0], 'firstError');

        // maxBytes
        $errors = array();
        Validation::validate((object) array('value' => '¡¡¡', 'constraints' => array('maxLength' => 3, 'maxBytes' => 5)), 'test', new \stdClass(), $errors);
        $this->assertBeginsWith($errors[0], ' exceeds its size limit');
        
        //HTML Type
        $errors = array();
        Validation::validate((object) array('value' => '&nbsp;', 'required' => true), 'test', (object) array('usageType' => 20), $errors);
        $this->assertBeginsWith($errors[0], ' is required');
        
        $errors = array();
        Validation::validate((object) array('value' => '&nbsp;&nbsp;&nbsp;', 'required' => true), 'test', (object) array('usageType' => 20), $errors);
        $this->assertBeginsWith($errors[0], ' is required');
        
        $errors = array();
        Validation::validate((object) array('value' => '&nbsp;<img src="http://aabc.com/a.png">', 'required' => true), 'test', (object) array('usageType' => 20), $errors);
        $this->assertTrue(count($errors) === 0);
        
        $errors = array();
        Validation::validate((object) array('value' => '<img src="http://aabc.com/a.png">', 'required' => true), 'test', (object) array('usageType' => 20), $errors);
        $this->assertTrue(count($errors) === 0);
    }

    function testMergeConstraints() {
        $method = $this->getMethod('mergeConstraints');
        $givenConstraints = array(
            'maxLength' => 20,
            'minValue' => 2,
            'maxValue' => 10,
            'regex' => '@[\d]{3}@'
        );
        $apiConstraints = array(
            (object) array(
                'kind' => ConnectPHP\Constraint::Min,
                'value' => 1,
            ),
            (object) array(
                'kind' => ConnectPHP\Constraint::Max,
                'value' => 8,
            ),
            (object) array(
                'kind' => ConnectPHP\Constraint::MinLength,
                'value' => 12,
            ),
            (object) array(
                'kind' => ConnectPHP\Constraint::MaxLength,
                'value' => 18,
            ),
            (object) array(
                'kind' => ConnectPHP\Constraint::Pattern,
                'value' => '@[\d]{4}@',
            ),
        );
        $result = $method($givenConstraints, $apiConstraints);
        $expected = array(
            'maxLength' => 18,
            'minValue' => 2,
            'maxValue' => 8,
            'regex' => array('@[\d]{3}@', '@[\d]{4}@'),
            'minLength' => 12,
        );
        $this->assertIdentical($expected, $result); 

        // Same case as above but with no givenConstraints values.
        $givenConstraints = array();
        $result = $method($givenConstraints, $apiConstraints);
        $expected = array(
            'minValue' => 1,
            'maxValue' => 8,
            'minLength' => 12,
            'maxLength' => 18,
            'regex' => '@[\d]{4}@',
        );
        $this->assertIdentical($expected, $result); 

        // For minValue = 0 in $apiConstraints and some value for minValue in $givenConstraints
        $givenConstraints = array('minValue' => 2); 
        $apiConstraints = array(
            (object) array(
                'kind' => ConnectPHP\Constraint::Min,
                'value' => 0,
                )
            );
        $result = $method($givenConstraints, $apiConstraints);
        $expected = array(
            'minValue' => 2,
        );
        $this->assertIdentical($expected, $result);

        // For minValue = 0 in $apiConstraints and no $givenConstraints
        $givenConstraints = array(); 
        $apiConstraints = array(
            (object) array(
                'kind' => ConnectPHP\Constraint::Min,
                'value' => 0,
                )
            );
        $result = $method($givenConstraints, $apiConstraints);
        $expected = array(
            'minValue' => 0,
        );
        $this->assertIdentical($expected, $result);
    }

    function testCheckDataType() {
        $method = $this->getMethod('checkDataType');

        //All of the following should be directed to the correct handler and error out
        $result = $method('abcd', '', 'Contact.CustomFields.c.int1', (object) array('COM_type' => 'Integer')); 
        $this->assertTrue(Text::stringContains($result, 'integer'));

        $result = $method('test@', '', 'Contact.Emails.PRIMARY.Address', new \stdClass());
        $this->assertTrue(Text::stringContains($result, 'Email'));

        $result = $method('test@', '', 'Contact.CustomFields.c.customEmail', (object) array('usageType' => ConnectPHP\PropertyUsage::EmailAddress));
        $this->assertTrue(Text::stringContains($result, 'Email'));

        $result = $method('abcd', '', 'Contact.CustomFields.c.url1', (object) array('usageType' => ConnectPHP\PropertyUsage::URI)); 
        $this->assertTrue(Text::stringContains($result, 'URL'));

        $result = $method('abcd', '', 'Contact.CustomFields.c.date1', (object) array('COM_type' => 'Date')); 
        $this->assertTrue(Text::stringContains($result, 'not completely filled in'));

        //@@@ QA 130222-000087
        $result = $method('-1-13-32 00:00:00', '', 'Contact.CustomFields.c.datetime1', (object) array('COM_type' => 'DateTime')); 
        $this->assertTrue(Text::stringContains($result, 'not completely filled in'));

        //@@@ QA 130320-000031
        $result = $method('2009-13-32 00:00:00', '', 'Contact.CustomFields.c.datetime1', (object) array('COM_type' => 'DateTime')); 
        $this->assertTrue(Text::stringContains($result, 'not a valid date'));

        $result = $method('2009-2-31 00:00:00', '', 'Contact.CustomFields.c.datetime1', (object) array('COM_type' => 'DateTime')); 
        $this->assertTrue(Text::stringContains($result, 'not a valid date'));

        $result = $method('2009-2-29 00:00:00', '', 'Contact.CustomFields.c.datetime1', (object) array('COM_type' => 'DateTime')); 
        $this->assertTrue(Text::stringContains($result, 'not a valid date'));

        $result = $method('2008-2-29 00:00:00', '', 'Contact.CustomFields.c.datetime1', (object) array('COM_type' => 'DateTime')); 
        $this->assertNull($result);

        $result = $method('abcd<', '', 'Contact.Login', new \stdClass()); 
        $this->assertTrue(Text::stringContains($result, 'cannot contain'));
    }

    function testCheckConstraints() {
        $method = $this->getMethod('checkConstraints');

        //Invalid constraints should be skipped
        $errors = $method((object) array('value' => 'test'), array('invalidConstraint' => 5), 'Test Field');
        $this->assertTrue(count($errors) === 0);

        //Null constraints should be skipped
        $errors = $method((object) array('value' => 'test'), array('minLength' => null), 'Test Field');
        $this->assertTrue(count($errors) === 0);

        //Valid constraints should generate errors
        $errors = $method((object) array('value' => 'test'), array('minLength' => 5), 'Test Field');
        $this->assertTrue(Text::stringContains($errors[0], 'Test Field'));
    }

    function testCheckDataConsistency() {
        $method = $this->getMethod('checkDataConsistency');

        //Field with a mask should be validated and generate an array of errors
        $errors = $method((object) array('value' => 'test'), 'Test Phone', 'Contact.CustomFields.c.testField', (object) array('inputMask' => 'F(M#M#M#F)'));
        $this->assertTrue(count($errors) !== 0);

        //Field with a validation requirement should be checked with label and labelValidation
        $errors = $method((object) array('value' => 'test', 'requireValidation' => true, 'labelValidation' => 'TestLabel'), 'TestName', 'Contact.CustomFields.c.validatedField', new \stdClass());
        $this->assertTrue(Text::stringContains($errors[0], 'TestLabel'));
        $this->assertTrue(Text::stringContains($errors[0], 'TestName'));
    }

    function testValidMask() {
        $validate = $this->getMethod('validMask');
        $inputs = array(
            array('123-45-6789', 'M#M#M#F-M#M#F-M#M#M#M#', null),
            array('12-4-6789', 'M#M#M#F-M#M#F-M#M#M#M#', 
                   array("Test Field Expected input: ###-##-####", "Test Field Character '-' at position [3] is not a number")),
            array('12b-45-6789', 'M#M#M#F-M#M#F-M#M#M#M#',
                  array("Test Field Expected input: ###-##-####", "Test Field Character 'b' at position [3] is not a number")),
            array('123+45-6789', 'M#M#M#F-M#M#F-M#M#M#M#',
                  array("Test Field Expected input: ###-##-####", "Test Field Character '+' at position [4] does not match expected formatting character '-'")),
            array('(123) 456-7890', 'F(M#M#M#F)F M#M#M#F-M#M#M#M#', null),
            array('123 45-7890', 'F(M#M#M#F)F M#M#M#F-M#M#M#M#',
                  array("Test Field Expected input: (###) ###-####", "Test Field Character '1' at position [1] does not match expected formatting character '('")),
            array('(123) 456-7890a', 'F(M#M#M#F)F M#M#M#F-M#M#M#M#',
                  array('Test Field Expected input: (###) ###-####', 'Test Field The input is too long')),
            array('12345-6789', 'M#M#M#M#M#F-M#M#M#M#', null),
            array('12345-6789a', 'M#M#M#M#M#F-M#M#M#M#',
                  array('Test Field Expected input: #####-####', 'Test Field The input is too long')),
            array('A1B 2C3', 'ULM#ULF M#ULM#', null),
            array('AA1 2CA', 'ULM#ULF M#ULM#',
                  array("Test Field Expected input: A#A #A#", "Test Field Character 'A' at position [2] is not a number")),
            array('A1B 2C3a', 'ULM#ULF M#ULM#', 
                  array('Test Field Expected input: A#A #A#', 'Test Field The input is too long')),
        );
        
        foreach($inputs as $triple) {
            list($value, $mask, $expected) = $triple;
            $actual = $validate($value, $mask, 'Test Field');
            $error = sprintf("Expected '%s' got '%s' for value '$value' mask '$mask'", var_export($expected, true), var_export($actual, true));
            $this->assertEqual($expected, $actual, $error);
        }
    }

    function testValidEmail() {
        $validate = $this->getMethod('validEmail');
        $inputs = array(
            array(null, 'foo@bar.com'),
            array('TestField is not a valid Email', 'not.an.email.address'),
            array('TestField is not a valid Email', false),
            array('TestField is not a valid Email', 0),
        );

        foreach($inputs as $pair) {
            list($expected, $value) = $pair;
            $actual = $validate($value, 'TestField');
            $this->assertEqual($expected, $actual);
        }
    }
    //@@@ QA 130509-000120. CP validating alternateemail field incorrectly.    
    function testValidEmailList() {
        $validate = $this->getMethod('validEmailList');
        $inputs = array(
            array(null, 'foo@bar.com,foo1@bar.com,foo2@bar.com'),
            array(null, 'foo@bar.com;foo1@bar.com;foo2@bar.com'),
            array(null, ' foo@bar.com; foo1@bar.com ,  foo2@bar.com '),
            array(null, 'single@email.com'),
            array('TestField is not a valid Email', 'valid@email.com, invalidemail.com'),
            array('TestField is not a valid Email', false),
            array('TestField is not a valid Email', 0),
        );

        foreach($inputs as $pair) {
            list($expected, $value) = $pair;
            $actual = $validate($value, 'TestField');
            $this->assertEqual($expected, $actual);
        }
    }

    function testValidInteger() {
        $validate = $this->getMethod('validInteger');
        $errorMsg = 'TestField must be an integer';
        $inputs = array(
            array($errorMsg, 'not.an.int'),
            array($errorMsg, array()),
            array($errorMsg, '~9'),
            array($errorMsg, 'a9'),
            array(null, '09'),
            array(null, '009'),
            array(null, 09),
            array(null, 0),
            array(null, '0'),
            array(null, 123),
            array(null, -123),
        );

        foreach($inputs as $pair) {
            list($expected, $value) = $pair;
            $actual = $validate($value, 'TestField');
            $error = sprintf("Expected '%s' got '%s' for value '$value'", var_export($expected, true), var_export($actual, true));
            $this->assertEqual($expected, $actual, $error);
        }
    }

    function testValidURL() {
        $validate = $this->getMethod('validUrl');
        $inputs = array(
            array('TestField is not a valid URL', ''),
            array('TestField is not a valid URL', null),
            array('TestField is not a valid URL', '/foo.com/'),
            array(null, 'google.com'),
            array(null, 'http://google.com'),
            array(null, 'http://www.google.com'),
        );

        foreach($inputs as $pair) {
            list($expected, $value) = $pair;
            $actual = $validate($value, 'TestField');
            $this->assertEqual($expected, $actual);
        }
    }
    
    function testValidDate() {
        $validate = $this->getMethod('validDate');
        $inputs = array(
            array('TestField is not completely filled in', array()),
            array('TestField is not completely filled in', '1910-13-23'),
            array('TestField is not completely filled in', 'not/a/date'),
            array(null, '2013-02-24 05:01:02'),
            array(null, '2013-02-24'),
            array(null, '1970-01-01 00:00:00'),
            array(null, '1970-01-01'),
            array(null, '2100-12-31 23:59:59'),
            array(null, '12301823'),
        );

        foreach($inputs as $pair) {
            list($expected, $value) = $pair;
            $actual = $validate($value, 'TestField');
            $this->assertEqual($expected, $actual);
        }
    }

    function testValidLogin() {
        $validate = $this->getMethod('validLogin');
        $inputs = array(
            array('TestField cannot contain spaces, double quotes or <>', 'login <'),
            array('TestField cannot contain spaces, double quotes or <>', 'login >'),
            array('TestField cannot contain spaces, double quotes or <>', 'login '),
            array('TestField cannot contain spaces, double quotes or <>', 'login "'),
            array(null, 'valid'),
        );

        foreach($inputs as $pair) {
            list($expected, $value) = $pair;
            $actual = $validate($value, 'TestField');
            $this->assertEqual($expected, $actual);
        }
    }

    function testRegex() {
        $this->assertNull(Validation::regex('banana', '', null));
        $this->assertNull(Validation::regex('banana', '^.*', null));
        $return = Validation::regex('banana', '[0-9]+', 'foo');
        $this->assertIsA($return, 'string');
        $this->assertTrue(Text::stringContains($return, 'foo'));
        $return = Validation::regex('banana', '^[ ]+', 'foo');
        $this->assertIsA($return, 'string');
        $this->assertTrue(Text::stringContains($return, 'foo'));
    }

    function testMinLength() {
        $minLength = $this->getMethod('minLength');
        $this->assertNull($minLength('banana', 2, 'banana'));
        $this->assertNull($minLength('banana', 6, 'banana'));

        $return = $minLength('banana', 20, 'foo');
        $this->assertIsA($return, 'string');
        $this->assertTrue(Text::stringContains($return, 'foo'));
        $this->assertTrue(Text::stringContains($return, '14'));
        $this->assertFalse(Text::stringContains($return, 'banana'));

        $return = $minLength('banana', 7, 'foo');
        $this->assertIsA($return, 'string');
        $this->assertTrue(Text::stringContains($return, 'foo'));
        $this->assertTrue(Text::stringContains($return, '1'));
        $this->assertFalse(Text::stringContains($return, 'banana'));

        $return = $minLength('', 7, 'foo');
        $this->assertIsA($return, 'string');
        $this->assertTrue(Text::stringContains($return, 'foo'));
        $this->assertTrue(Text::stringContains($return, '7'));
        $this->assertFalse(Text::stringContains($return, ''));

        $return = $minLength('b', 7, 'foo');
        $this->assertIsA($return, 'string');
        $this->assertTrue(Text::stringContains($return, 'foo'));
        $this->assertTrue(Text::stringContains($return, '6'));
        $this->assertFalse(Text::stringContains($return, 'b'));
    }

    function testMaxLength() {
        $maxLength = $this->getMethod('maxLength');
        $this->assertNull($maxLength('banana', 20, 'banana'));
        $this->assertNull($maxLength('banana', 6, 'banana'));
        $return = $maxLength('banana', 2, 'foo');
        $this->assertIsA($return, 'string');
        $this->assertTrue(Text::stringContains($return, 'foo'));
        $this->assertTrue(Text::stringContains($return, '2'));
        $this->assertTrue(Text::stringContains($return, '4'));
        $this->assertFalse(Text::stringContains($return, 'banana'));
        $return = $maxLength('banana', 5, 'foo');
        $this->assertIsA($return, 'string');
        $this->assertTrue(Text::stringContains($return, 'foo'));
        $this->assertTrue(Text::stringContains($return, '5'));
        $this->assertTrue(Text::stringContains($return, '1'));
        $this->assertFalse(Text::stringContains($return, 'banana'));

        // Password field - '<' and '>' are translated to'&lt;' and '&gt;' as part of XSS expansion,
        // so a password can pass the input element's maxlength restriction, but fail on server side validation.
        $return = $maxLength('&lt;&lt;&lt;&lt;&lt;&lt;', 20, 'NewPassword');
        $this->assertEqual(sprintf(\RightNow\Utils\Config::getMessage(PASSWD_ENTERED_EXCEEDS_MAX_CHARS_MSG), 20), $return);

        // DisplayName field - '<' and '>' are translated to'&lt;' and '&gt;' as part of XSS expansion,
        // so a DisplayName can pass the input element's maxlength restriction, but fail on server side validation.
        $return = $maxLength('&lt;&lt;&lt;&lt;&lt;&lt;&lt;&lt;&lt;&lt;&lt;&lt;&lt;&lt;&lt;&lt;&lt;&lt;&lt;&lt;&lt;&lt;&lt;&lt;&lt;&lt;&lt;&lt;&lt;&lt;', 80, 'DisplayName');
        $this->assertEqual(sprintf(\RightNow\Utils\Config::getMessage(DISPLYNM_NT_MX_CHRS_CHRS_XP_SBM_SZ_XCDD_MSG), 80), $return);
    }

    function testMaxBytes() {
        $maxBytes = $this->getMethod('maxBytes');
        $fieldName = 'Description';

        $this->assertNull($maxBytes('guadalupe', 20, $fieldName));
        $errorMessage = "$fieldName exceeds its size limit";
        $results = $maxBytes('guadalupe', 2, $fieldName);
        $this->assertBeginsWith($results, $errorMessage);

        // Double-byte
        $results = $maxBytes('¡', 1, $fieldName);
        $this->assertBeginsWith($results, $errorMessage);
    }

    function testMinValue() {
        $minValue = $this->getMethod('minValue');
        $this->assertNull($minValue(null, 2, 'banana'));
        $this->assertNull($minValue(6, 2, 'banana'));
        $this->assertNull($minValue(2, 2, 'banana'));

        $return = $minValue(6, 20, 'foo');
        $this->assertIsA($return, 'string');
        $this->assertTrue(Text::stringContains($return, 'foo'));
        $this->assertTrue(Text::stringContains($return, '20'));
        $this->assertFalse(Text::stringContains($return, 'banana'));

        $return = $minValue(6, 7, 'foo');
        $this->assertIsA($return, 'string');
        $this->assertTrue(Text::stringContains($return, 'foo'));
        $this->assertTrue(Text::stringContains($return, '7'));
        $this->assertFalse(Text::stringContains($return, 'banana'));

        $return = $minValue(0, 7, 'foo');
        $this->assertIsA($return, 'string');
        $this->assertTrue(Text::stringContains($return, 'foo'));
        $this->assertTrue(Text::stringContains($return, '7'));
        $this->assertFalse(Text::stringContains($return, 'banana'));

        //Invalid date, just ignore
        $this->assertNull($minValue('2010-13-32 12:27:17', 10, 'foo'));

        //A valid date should be converted to a timestamp and compared
        $this->assertNull($minValue('2010-12-12 12:27:19', '2010-12-12 12:27:18', 'foo'));

        $return = $minValue('2010-12-12 12:27:17', '2010-12-12 12:27:18', 'foo');
        $this->assertIsA($return, 'string');
        $this->assertTrue(Text::stringContains($return, 'foo'));
        $this->assertTrue(Text::stringContains($return, '12/12/2010'));
    }

    function testMaxValue() {
        $maxValue = $this->getMethod('maxValue');
        $this->assertNull($maxValue(6, 20, 'banana'));
        $this->assertNull($maxValue(6, 6, 'banana'));

        $return = $maxValue(6, 2, 'foo');
        $this->assertIsA($return, 'string');
        $this->assertTrue(Text::stringContains($return, '2'));
        $this->assertTrue(Text::stringContains($return, 'foo'));

        $return = $maxValue(7, 6, 'foo');
        $this->assertIsA($return, 'string');
        $this->assertTrue(Text::stringContains($return, '6'));
        $this->assertTrue(Text::stringContains($return, 'foo'));

        //Invalid date, just ignore
        $this->assertNull($maxValue('2010-13-32 12:27:17', 10, 'foo'));

        //A valid date should be converted to a timestamp and compared
        $this->assertNull($maxValue('2010-12-12 12:27:17', '2010-12-12 12:27:18', 'foo'));

        $return = $maxValue('2010-12-12 12:27:19', '2010-12-12 12:27:18', 'foo');
        $this->assertIsA($return, 'string');
        $this->assertTrue(Text::stringContains($return, 'foo'));
        $this->assertTrue(Text::stringContains($return, '12/12/2010'));
    }

    function testMaxOccurrences() {
        $method = $this->getMethod('maxOccurrences');
        $this->assertNull($method('', 1, null));
        $this->assertNull($method('abcd', 1, null));
        $this->assertNull($method('aabbccdd', 2, null));
        $this->assertIdentical("foo cannot contain more than 2 repeated characters", $method('aabbbccdd', 2, 'foo'));
        $this->assertIdentical("foo cannot have repeated characters", $method('abbcd', 1, 'foo'));
    }

    function testGetDateString() {
        $method = $this->getMethod('getDateString');
        $date = '10/16/2013';
        $dateTime = "$date 12:26:00";
        $this->assertEqual($date, $method(strtotime($date)));
        $this->assertEqual($date, $method(strtotime($dateTime)));
        $this->assertEqual($dateTime, $method(strtotime($dateTime), true));
    }

    function testMaxRepetitions() {
        $method = $this->getMethod('maxRepetitions');
        $this->assertNull($method('', 1, null));
        $this->assertNull($method('abcd', 1, null));
        $this->assertNull($method('abcdabcd', 1, null));
        $this->assertIdentical("foo cannot have a character repeated in a row", $method('aabcd', 1, 'foo'));
        $this->assertIdentical("foo cannot contain more than 2 repeated characters in a row", $method('abbcccdd', 2, 'foo'));
    }

    function testMinLowercaseChars() {
        $method = $this->getMethod('minLowercaseChars');
        $this->assertNull($method('a', 1, null));
        $this->assertNull($method('abCD', 2, null));
        $this->assertNull($method('abcd', 4, null));
        $this->assertIdentical("foo must contain at least 1 lower-case character", $method('ABC', 1, 'foo'));
        $this->assertIdentical("foo must contain at least 2 lower-case characters", $method('ABcD', 2, 'foo'));
    }

    function testMinUppercaseChars() {
        $method = $this->getMethod('minUppercaseChars');
        $this->assertNull($method('A', 1, null));
        $this->assertNull($method('ABcd', 2, null));
        $this->assertNull($method('ABCD', 4, null));
        $this->assertIdentical("foo must contain at least 1 upper-case character", $method('abc', 1, 'foo'));
        $this->assertIdentical("foo must contain at least 2 upper-case characters", $method('abCd', 2, 'foo'));
    }

    function testMinSpecialChars() {
        $method = $this->getMethod('minSpecialChars');
        $this->assertNull($method('#', 1, null));
        $this->assertNull($method('!@ab', 2, null));
        $this->assertNull($method('!@*#', 4, null));
        $this->assertIdentical("foo must contain at least 1 special-character", $method('ABC', 1, 'foo'));
        $this->assertIdentical("foo must contain at least 2 special-characters", $method('abD!', 2, 'foo'));
    }

    function testMinSpecialAndDigitChars() {
        $method = $this->getMethod('minSpecialAndDigitChars');
        $this->assertNull($method('#', 1, null));
        $this->assertNull($method('!2ab', 2, null));
        $this->assertNull($method('!@23', 4, null));
        $this->assertIdentical("foo must contain at least 1 number", $method('ABC', 1, 'foo'));
        $this->assertIdentical("foo must contain at least 2 numbers", $method('aBc2', 2, 'foo'));
        $this->assertIdentical("foo must contain at least 3 numbers", $method('abD!2', 3, 'foo'));
    }
    function testGetErrorByType() {
        $method = $this->getMethod('getErrorByType');
        $this->assertNull($method('', '', '', ''));

        $cases = array(
            array('singular' => 'abcd', 'plural' => 'Abcd', 'type' => 'uppercase'),
            array('singular' => 'ABCD', 'plural' => 'ABcD', 'type' => 'lowercase'),
            array('singular' => '4abd', 'plural' => '4ab#', 'type' => 'special'),
            array('singular' => 'abcd', 'plural' => '4abc', 'type' => 'specialAndDigit')
        );

        foreach($cases as $case) {
            $singular = $method($case['singular'], 1, 'foo', $case['type']);
            $plural = $method($case['plural'], 2, 'foo', $case['type']);
            $this->assertIsA($singular, 'string');
            $this->assertIsA($plural, 'string');
            $this->assertTrue($singular !== $plural);
        }
    }

    function testIsPassword() {
        $isPassword = $this->getMethod('isPassword');
        $this->assertTrue($isPassword('NewPassword'));
        $this->assertTrue($isPassword('Contact.NewPassword'));
        $this->assertTrue($isPassword('NewPassword_Validate'));
        $this->assertFalse($isPassword('Threads'));
        $this->assertFalse($isPassword(null));
    }

    function testIsDisplayName() {
        $isDisplayName = $this->getMethod('isDisplayName');
        $this->assertTrue($isDisplayName('DisplayName'));
        $this->assertTrue($isDisplayName('Contact.DisplayName'));
        $this->assertFalse($isDisplayName('Threads'));
        $this->assertFalse($isDisplayName(null));
    }
}
