<?php
RightNow\UnitTest\Helper::loadTestedFile(__FILE__);

use RightNow\Api,
    RightNow\Controllers\UnitTest;


class DqaController extends CPTestCase {
    public $testingClass = '\RightNow\Controllers\Dqa';

    function __construct() {
        parent::__construct();
    }
    // @@@ 140624-000086
    function testPublish() {
        $base = new \ReflectionClass('RightNow\Controllers\Dqa');
        $productionMode = $base->getProperty('productionMode');
        $productionMode->setAccessible(true);
        $context = $base->getProperty('context');
        $context->setAccessible(true);
        $method = $base->getMethod('publish');
        $method->setAccessible(true);
        $instance = $base->newInstance();

        $productionMode->setValue($instance, false);  // Test exception generating in non-production mode
        $context->setValue($instance, array((object)array('type' => 1, 'action' => array('1' => '2'))));
        try{
            $method->invoke($instance);
            $this->pass();
        }
        catch(\Exception $e){
            $this->fail('Good data should not have cased an exception to be thrown in non-production mode.');
            $this->pass();
        }

        $context->setValue($instance, array((object)array('type' => 1, 'action' => 'http://www.oracle.com')));
        try{
            $method->invoke($instance);
            $this->fail('Non-array action did not cause exception in non-production mode.');
        }
        catch(\Exception $e){
            $this->pass();
        }

        $context->setValue($instance, array());
        try{
            $method->invoke($instance);
            $this->fail('Passing no data in the array should cause an exception to be thrown in non-production mode.');
        }
        catch(\Exception $e){
            $this->pass();
        }

        $productionMode->setValue($instance, true);   // Test no exception for bad data in produciton mode
        $context->setValue($instance, array((object)array('type' => 1, 'action' => 'http://www.oracle.com')));
        try{
            $method->invoke($instance);
            $this->pass();
        }
        catch(\Exception $e){
            $this->fail('Non-array action should not have caused an exception to be thrown in production mode.');
            $this->pass();
        }

        $context->setValue($instance, array());
        try{
            $method->invoke($instance);
            $this->pass();
        }
        catch(\Exception $e){
            $this->fail('Passing no data action should not have caused an exception to be thrown in production mode.');
            $this->pass();
        }
    }

    function testValidateQuery() {
        $base = new \ReflectionClass('RightNow\Controllers\Dqa');
        $method = $base->getMethod('_validateQuery');
        $method->setAccessible(true);
        $instance = $base->newInstance();


        $response = $method->invoke($instance, 1, array('1' => '2'));
        $this->assertIdentical(true, $response);
        $response = $method->invoke($instance, 1, (object)array('1' => '2'));
        $this->assertIdentical(true, $response);
        $response = $method->invoke($instance, '1', (object)array('1' => '2'));
        $this->assertIdentical("DQA type is not a positive integer: '1'", $response);
        $response = $method->invoke($instance, 1, "('1' => '2')");
        $this->assertIdentical("DQA action is not an array or object: '(\'1\' => \'2\')'", $response);
        $response = $method->invoke($instance, 1, "http://www.google.com");
        $this->assertIdentical("DQA action is not an array or object: 'http://www.google.com'", $response);
    }
}
