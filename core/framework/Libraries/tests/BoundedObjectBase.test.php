<?php

RightNow\UnitTest\Helper::loadTestedFile(__FILE__);

class FakeBoundedObject extends \RightNow\Libraries\BoundedObjectBase
{
    public $publicVariable;
    protected $protectedVariable;
}

class BoundedObjectBaseTest extends CPTestCase 
{
    private $instance;

    function setUp()
    {
        $this->instance = new FakeBoundedObject();
        parent::setUp();
    }

    function testSetterAndGetter()
    {
        try
        {
            $this->instance->someVariable = "some value";
            $this->fail();
        }
        catch (\Exception $ex)
        {
            $this->pass();
        }

        try
        {
            $this->instance->publicVariable = "some public value";
            $this->instance->protectedVariable = "some protected value";
            $this->pass();
        }
        catch (\Exception $ex)
        {
            $this->fail();
        }

        try
        {
            $this->instance->someVariable;
            $this->fail();
        }
        catch (\Exception $ex)
        {
            $this->pass();
        }

        try
        {
            $this->assertTrue(is_string($this->instance->publicVariable));
            $this->assertTrue(is_string($this->instance->protectedVariable));
            $this->pass();
        }
        catch (\Exception $ex)
        {
            $this->fail();
        }
    }
}
