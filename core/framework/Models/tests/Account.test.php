<?php
RightNow\UnitTest\Helper::loadTestedFile(__FILE__);
use RightNow\Connect\v1_4 as Connect;
class AccountTest extends CPTestCase {
    function __construct() {
        parent::__construct();
        $this->model = new RightNow\Models\Account();
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

    function testValidGet() {
        $response = $this->model->get(1);
        $this->assertIsA($response, 'RightNow\Libraries\ResponseObject');
        $this->assertTrue(is_object($response->result));
        $this->assertSame(0, count($response->errors));
        $this->assertSame(0, count($response->warnings));
        $account = $response->result;
        $this->assertIsA($account, CONNECT_NAMESPACE_PREFIX . '\Account');
        $this->assertSame(1, $account->ID);

        $response = $this->model->get("1");
        $this->assertIsA($response, 'RightNow\Libraries\ResponseObject');
        $this->assertTrue(is_object($response->result));
        $this->assertSame(0, count($response->errors));
        $this->assertSame(0, count($response->warnings));
        $account = $response->result;
        $this->assertIsA($account, CONNECT_NAMESPACE_PREFIX . '\Account');
        $this->assertSame(1, $account->ID);
    }
}