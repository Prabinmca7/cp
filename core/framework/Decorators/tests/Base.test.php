<?php
RightNow\UnitTest\Helper::loadTestedFile(__FILE__);

use RightNow\Utils\Url,
    RightNow\Utils\Text,
    RightNow\Controllers\UnitTest\PhpFunctional;

class TestBaseDecoratorByExtendingIt extends RightNow\Decorators\Base {
    public $connectTypes = array(
        'bananas',
    );

    function feel () {
        return 'me';
    }

    protected function decoratorAdded () {
        $this->decorator = $this->connectObj;
    }
}

class MockedConnectObjectForBaseDecoratorTest {
    static $comType = '';
    function __construct($comType) {
        self::$comType = $comType;
    }
    static function getMetadata() {
        return (object) array(
            'COM_type' => self::$comType,
        );
    }
}

class BaseDecoratorTest extends CPTestCase {
    function testConstructorThrowsIfCOMTypeDoesNotMatch () {
        try {
            new TestBaseDecoratorByExtendingIt(new MockedConnectObjectForBaseDecoratorTest('fine'));
            $this->fail("Should've thrown an exception");
        }
        catch (\Exception $e) {
            $this->assertStringContains($e->getMessage(), 'TestBaseDecoratorByExtendingIt');
            $this->assertStringContains($e->getMessage(), 'fine');
        }
    }

    function testConstructorCallsDecoratorAddedHook () {
        $mock = new MockedConnectObjectForBaseDecoratorTest('bananas');
        $decorated = new TestBaseDecoratorByExtendingIt($mock);
        $this->assertSame($mock, $decorated->decorator);
    }

    function testMethodCalledOnObjectWorks () {
        $decorator = new TestBaseDecoratorByExtendingIt(new MockedConnectObjectForBaseDecoratorTest('bananas'));
        $this->assertSame('me', $decorator->feel());
    }
}
