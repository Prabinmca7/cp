<?php

RightNow\UnitTest\Helper::loadTestedFile(__FILE__);

use RightNow\Utils\Text;

class TestAssetCheck extends WidgetTestCase
{
    public $testingWidget = "standard/input/AssetCheck";

    function __construct() {
        $data = array('name' => 'AssetCheck_1');
        $this->createWidgetInstance($data);
        $this->instance = $this->getWidgetInstance($data);
        $this->data = $this->getWidgetData($this->instance);
        parent::__construct();
    }

    function testAssetSerialNumberValidation() {
        $methodName = 'assetSerialNumberValidation';
        //Valid serialNumber, valid productID
        $response = $this->callAjaxMethod($methodName, array(
            'serialNumber' => '123456789!@#$%^&*()',
            'productID' => '7',
            'f_tok' => \RightNow\Utils\Framework::createTokenWithExpiration(0),
        ));
        $this->assertIdentical(7, $response);

        //Correct serialNumber, invalid productID
        $response = $this->callAjaxMethod($methodName, array(
            'serialNumber' => '123456789!@#$%^&*()',
            'productID' => '8',
            'f_tok' => \RightNow\Utils\Framework::createTokenWithExpiration(0),
        ));
        $this->assertFalse($response);

        //Invalid serialNumber, correct productID
        $response = $this->callAjaxMethod($methodName, array(
            'serialNumber' => '123456789!@#$%',
            'productID' => '7',
            'f_tok' => \RightNow\Utils\Framework::createTokenWithExpiration(0),
        ));
        $this->assertFalse($response);

        //Only productID, No serialNumber
        $response = $this->callAjaxMethod($methodName, array(
            'productID' => '7',
            'f_tok' => \RightNow\Utils\Framework::createTokenWithExpiration(0),
        ));
        $this->assertFalse($response);

        //No serialNumber, No productID
        $response = $this->callAjaxMethod($methodName, array('f_tok' => \RightNow\Utils\Framework::createTokenWithExpiration(0)));
        $this->assertFalse($response);
        
        //No f_tok
        $response = $this->callAjaxMethod($methodName, array());
        $this->assertTrue(Text::stringContains($response->errors[0]->externalMessage, 'action cannot be completed at this time'));

        //Only serialNumber, No productID
        $response = $this->callAjaxMethod($methodName, array(
            'serialNumber' => '123456789!@#$%^&*()',
            'f_tok' => \RightNow\Utils\Framework::createTokenWithExpiration(0),
        ));
        $this->assertFalse($response);

        //Valid serialNumber (000), valid productID
        $response = $this->callAjaxMethod($methodName, array(
            'serialNumber' => '000',
            'productID' => '7',
            'f_tok' => \RightNow\Utils\Framework::createTokenWithExpiration(0),
        ));
        $this->assertIdentical(10, $response);
    }
}