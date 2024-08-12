<?php
RightNow\UnitTest\Helper::loadTestedFile(__FILE__);

class GuidedAssistanceTest extends CPTestCase
{
    public $testingClass = 'RightNow\Models\GuidedAssistance';

    function __construct()
    {
        parent::__construct();
        $this->model = new \RightNow\Models\GuidedAssistance();
    }

    public function testInvalidGet()
    {
        $response = $this->model->get('sdf');
        $this->assertIsA($response, 'RightNow\Libraries\ResponseObject');
        $this->assertNull($response->result);
        $this->assertSame(1, count($response->errors));

        $response = $this->model->get(null);
        $this->assertIsA($response, 'RightNow\Libraries\ResponseObject');
        $this->assertNull($response->result);
        $this->assertSame(1, count($response->errors));

        $response = $this->model->get('abc123');
        $this->assertIsA($response, 'RightNow\Libraries\ResponseObject');
        $this->assertNull($response->result);
        $this->assertSame(1, count($response->errors));

        $response = $this->model->get(456334);
        $this->assertIsA($response, 'RightNow\Libraries\ResponseObject');
        $this->assertNull($response->result);
        $this->assertSame(1, count($response->errors));
    }

    public function testValidGet()
    {
        $response = $this->model->get(3);
        $this->assertIsA($response, 'RightNow\Libraries\ResponseObject');
        $this->assertTrue(is_object($response->result));
        $this->assertSame(0, count($response->errors));
        $this->assertSame(0, count($response->warnings));
        $ga = $response->result;
        $this->assertIsA($ga, 'RightNow\Libraries\GuidedAssistance');
    }

    public function testValidGetAsArray()
    {
        $response = $this->model->get(4)->result->toArray();
        $this->assertTrue(is_array($response));
        $this->assertSame(4, $response['guideID']);

    }

    public function testGetGuideSession(){
        $method = $this->getMethod('getGuideSession');
        $this->assertTrue(is_string($method()));
    }

    public function testGetLabel(){
        $method = $this->getMethod('getLabel');

        $this->assertIdentical('test', $method(array('lbl_item0' => array('label' => 'test')), null));
        $this->assertIdentical('&gt;&amp;&quot;&#039;&lt;', $method(array('lbl_item0' => array('label' => '&gt;&amp;&quot;&#039;&lt;')), null));
        $this->assertIdentical('<&"\'>', $method(array('lbl_item0' => array('label' => '&lt;&amp;&quot;&#039;&gt;')), null, true));
        $this->assertIdentical('test', $method(array('lbl_item0' => array('label' => 'test')), 10));

        $labels = array('lbl_item0' => array('label' => 'test', 'lang_id' => 10),
                        'lbl_item1' => array('label' => 'test2', 'lang_id' => 12));
        $this->assertIdentical('test', $method($labels, 10));
        $this->assertIdentical('test', $method($labels, "10"));
        $this->assertIdentical('test2', $method($labels, 12));
    }
}
