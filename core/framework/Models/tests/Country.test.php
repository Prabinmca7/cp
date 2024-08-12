<?php
RightNow\UnitTest\Helper::loadTestedFile(__FILE__);
use RightNow\Connect\v1_4 as Connect;
class CountryTest extends CPTestCase {
    function __construct() {
        parent::__construct();
        $this->model = new RightNow\Models\Country();
        $this->CI = get_instance();
    }
    
    function getMethodInvoker($methodName) {
        return RightNow\UnitTest\Helper::getMethodInvoker('RightNow\Models\Country', $methodName);
    }
    
    function testInvalidGet() {
        $response = $this->model->get('sdf');
        $this->assertIsA($response, 'RightNow\Libraries\ResponseObject');
        $this->assertNull($response->result);
        $this->assertSame(1, count($response->errors));

        $response = $this->model->get(null);
        $this->assertIsA($response, 'RightNow\Libraries\ResponseObject');
        $this->assertNull($response->result);
        $this->assertSame(1, count($response->errors));

        $response = $this->model->get("abc123");
        $this->assertIsA($response, 'RightNow\Libraries\ResponseObject');
        $this->assertNull($response->result);
        $this->assertSame(1, count($response->errors));

        $response = $this->model->get(456334);
        $this->assertIsA($response, 'RightNow\Libraries\ResponseObject');
        $this->assertNull($response->result);
        $this->assertSame(1, count($response->errors));
    }

    function testValidID(){
        $response = $this->model->get(1);

        $this->assertIsA($response, 'RightNow\Libraries\ResponseObject');
        $this->assertTrue(is_object($response->result));
        $country = $response->result;
        $this->assertIsA($country, CONNECT_NAMESPACE_PREFIX . '\Country');
        $this->assertSame(1, $country->ID);

        $response = $this->model->get("1");

        $this->assertIsA($response, 'RightNow\Libraries\ResponseObject');
        $this->assertTrue(is_object($response->result));
        $country = $response->result;
        $this->assertIsA($country, CONNECT_NAMESPACE_PREFIX . '\Country');
        $this->assertSame(1, $country->ID);
    }
    
    function testGetAll() {
        $response = $this->model->getAll();
        $this->assertIsA($response, 'RightNow\Libraries\ResponseObject');
        $this->assertTrue(is_array($response->result));
        $this->assertTrue(is_object($response->result[0]));
        $this->assertTrue(is_int($response->result[0]->ID));
        $this->assertTrue(is_string($response->result[0]->Name));
        $this->assertTrue(is_string($response->result[0]->LookupName));
    }

    function testValidateStateAndCountry() {
        //Invalid ID and combinations (Country ID 1 = US, State ID 1 = AL)
        $this->assertFalse($this->model->validateStateAndCountry(null, null));
        $this->assertFalse($this->model->validateStateAndCountry(null, 1));
        $this->assertFalse($this->model->validateStateAndCountry(1, null));

        //Valid Combo
        $this->assertTrue($this->model->validateStateAndCountry(1, 1));

        //Invalid Combo (ID 62 = imaginary UK county A. Powersville)
        $this->assertFalse($this->model->validateStateAndCountry(62, 1));
    }
}
