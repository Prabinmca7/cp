<?php
\RightNow\UnitTest\Helper::loadTestedFile(__FILE__);

use RightNow\Libraries\ResponseObject, RightNow\Libraries\ResponseError;

class ResponseTestClass extends \RightNow\Models\Base {
    function __construct() {
        parent::__construct();
    }
}

class ResponseObjectTest extends CPTestCase {
    function testValidationTypes() {
        $types = array(
            'string' => array('is_string', 'aString'),
            'integer' => array('is_integer', 5),
            'array' => array('is_array', array(1, 2, 3)),
            'object' => array('is_object', new ResponseTestClass),
            'noValidation' => array(null, new ResponseTestClass),
        );

        foreach ($types as $type => $pair) {
            list($validationFunction, $return) = $pair;
            $ro = new ResponseObject($validationFunction);
            $ro->result = $return;
            $this->assertSame(0, count($ro->errors));
            $ro->result = null;
            if ($type !== 'noValidation') {
                $this->assertTrue(is_array($ro->errors));
                $this->assertEqual('Validation returned: false', "{$ro->errors[0]}");
            }
        }
    }

    function testCustomValidation() {
        $customError = 'This is not the array you seek.';
        $ro = new ResponseObject(function ($x) use ($customError) {
            return is_array($x) ? true : $customError;
        });
        $ro->result = 'notAnArray';
        $this->assertEqual("{$ro->errors[0]}", $customError);
    }

    function testErrorObject() {
        $ro = new ResponseObject();
        $ro->error = new ResponseError('foo');
        $this->assertEqual('foo', "{$ro->errors[0]}");

        $ro->error = array('externalMessage' => 'telephone');
        $this->assertEqual('telephone', $ro->errors[1]);

        $this->expectException();
        $ro->error = array();
    }

    function testToJson() {
        $return = array(
            'one' => 1,
            'two' => 1,
            'three' => 1,
        );
        $ro = new ResponseObject('is_string');
        $ro->result = $return;
        $ro->whatever = 'whatever';
        $expected = '{"result":{"one":1,"two":1,"three":1},"errors":[{"externalMessage":"Validation returned: false"}],"whatever":"whatever"}';
        $actual = $ro->toJson();
        $this->assertIdentical($expected, $actual);

        // Indicate is response object
        $expected = '{"result":{"one":1,"two":1,"three":1},"errors":[{"externalMessage":"Validation returned: false"}],"whatever":"whatever","isResponseObject":true}';
        $actual = $ro->toJson(array(), true);
        $this->assertIdentical($expected, $actual);

        // Specified Contact connect fields
        $ro = get_instance()->model('Contact')->get(1);
        $json = $ro->toJson(array(
            'LookupName',
            'Address.City',
            'Address.Country.LookupName',
            'Organization.LookupName',
            'SomeFieldThatDoesNotExist',
            'Another.Field.That.Does.Not.Exist',
        ));
        $contact = $ro->result;
        $array = json_decode($json);
        $data = $array->result;
        $this->assertIdentical($contact->Name->First . ' ' . $contact->Name->Last, $data->LookupName);
        $this->assertIdentical($contact->Address->City, $data->Address->City);
        $this->assertIdentical($contact->Address->Country->LookupName, $data->Address->Country->LookupName);
        $this->assertIdentical($contact->Organization->LookupName, $data->Organization->LookupName);

        $ro = new ResponseObject('is_string');
        $ro->result = "test return";
        $this->assertIdentical('{"result":"test return"}', $ro->toJson());
        $ro->warning = "test warning";
        $this->assertIdentical('{"result":"test return","warnings":["test warning"]}', $ro->toJson());
        $ro->error = "test error";
        $this->assertIdentical('{"result":"test return","errors":[{"externalMessage":"test error"}],"warnings":["test warning"]}', $ro->toJson());

        $ro->error = array(
            'externalMessage' => 'cardboard box',
            'errorCode' => 'too much postage',
            'source' => 'USPS',
            'internalMessage' => 'contents of box',
            'extraDetails' => 'delivery person was nice',
            'displayToUser' => true,
        );
        $this->assertIdentical('{"result":"test return","errors":[{"externalMessage":"test error"},{"externalMessage":"cardboard box","errorCode":"too much postage","source":"USPS","internalMessage":"contents of box","extraDetails":"delivery person was nice","displayToUser":true}],"warnings":["test warning"]}', $ro->toJson());

        $ro = new ResponseObject('is_string');
        $ro->error = array(
            'externalMessage' => 'array without all constructor args',
            'errorCode' => 'none, it\'s okay'
        );
        $this->assertIdentical('{"result":null,"errors":[{"externalMessage":"array without all constructor args","errorCode":"none, it\'s okay"}]}', $ro->toJson());
    }

    function testToString() {
        $return = array(
            'one' => 1,
            'two' => 1,
            'three' => 1,
        );
        $ro = new ResponseObject('is_array');
        $ro->result = $return;
        $expected = var_export($return, true);
        $actual = sprintf('%s', $ro);
        $this->assertIdentical($expected, $actual);
    }

    function testWrapper() {
        $responseTestClass = new ResponseTestClass;
        $ro = $responseTestClass->getResponseObject('aString', 'is_string');
        $this->assertTrue(empty($ro->errors));

        $warnings = array('warning 1', 'warning 2');
        $errors = array('some Error', 'some Other Error');
        $ro = $responseTestClass->getResponseObject('aString', 'is_object', $errors, $warnings);
        $this->assertEqual('Validation returned: false', "{$ro->errors[0]}");
        $this->assertEqual('some Error', "{$ro->errors[1]}");
        $this->assertEqual('some Other Error', "{$ro->errors[2]}");
        $this->assertIdentical($warnings, $ro->warnings);
    }
}

class ResponseErrorTest extends CPTestCase {
    function testToString() {
        $error = new ResponseError('banana');
        $this->assertSame('banana', "$error");
    }

    function testToStringDoesNotThrowANoReturnValueException() {
        $error = new ResponseError('');
        $this->assertSame('', "$error");
    }

    function testToArray() {
        $expectedResult = array(
            'externalMessage' => 'cucumber',
            'errorCode' => 42,
            'source' => 'hotdog'
        );

        $error = new ResponseError('cucumber', 42, 'hotdog');
        $this->assertSame($expectedResult, $error->toArray());

        $error = new ResponseError('cucumber', 42, 'hotdog', '', null);
        $this->assertSame($expectedResult, $error->toArray());
    }
}
