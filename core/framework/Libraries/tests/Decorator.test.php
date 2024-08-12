<?php

use RightNow\Utils\FileSystem,
    RightNow\Libraries\Decorator;

RightNow\UnitTest\Helper::loadTestedFile(__FILE__);

class StandardDecoratorTest extends CPTestCase {
    public $testingClass = '\RightNow\Libraries\Decorator';

    function testAdd() {
        $obj = (object) array();
        $this->assertIdentical($obj, Decorator::add($obj));

        try {
            Decorator::add((object) array(), 'bananas');
            $this->fail("Should've thrown");
        }
        catch (\Exception $e) {
            $this->assertStringContains($e->getMessage(), 'bananas');
        }

        $thread = new FakeIncidentThreadObject();
        $this->assertIsA(Decorator::add($thread, 'Present/IncidentThreadPresenter'), 'FakeIncidentThreadObject');
        $this->assertIsA($thread->IncidentThreadPresenter, 'RightNow\Decorators\IncidentThreadPresenter');

        $thread2 = new FakeIncidentThreadObject();
        $this->assertIsA(Decorator::add($thread2, array('class' => 'Present/IncidentThreadPresenter', 'property' => 'MyOwnName')), 'FakeIncidentThreadObject');
        $this->assertNull($thread2->IncidentThreadPresenter);
        $this->assertIsA($thread2->MyOwnName, 'RightNow\Decorators\IncidentThreadPresenter');
    }

    function testRemove() {
        $thread = new FakeIncidentThreadObject();
        $this->assertFalse(Decorator::remove($thread, 'id', 'bananas'));

        $this->assertFalse(Decorator::remove($thread, 'Present/IncidentThreadPresenter'));
        $this->assertNull($thread->IncidentThreadPresenter);

        $thread = Decorator::add($thread, 'Present/IncidentThreadPresenter');
        $this->assertTrue(Decorator::remove($thread, 'Present/IncidentThreadPresenter'));
        $this->assertNull($thread->IncidentThreadPresenter);

        $thread = Decorator::add($thread, array('class' => 'Present/IncidentThreadPresenter', 'property' => 'MyOwnName'));
        $this->assertFalse(Decorator::remove($thread, 'Present/IncidentThreadPresenter'));
        $this->assertTrue(Decorator::remove($thread, 'MyOwnName'));
        $this->assertNull($thread->IncidentThreadPresenter);
        $this->assertNull($thread->MyOwnName);
    }

    function testAddingWhenObjectHasExistingField(){
        $thread = new FakeIncidentThreadObject();
        try{
            Decorator::add($thread, array('class' => 'Present/IncidentThreadPresenter', 'property' => 'existingProperty'));
            $this->fail("Should have thrown an exception since we're trying to overwrite an existing property name");
        }
        catch(\Exception $e){
            $this->assertStringContains($e->getMessage(), 'existingProperty');
            $this->assertIdentical($thread->existingProperty, 'test');
        }
    }

    function testAddingWhenObjectHasAlreadyBeenDecorated(){
        $thread = new FakeIncidentThreadObject();
        $this->assertIsA(Decorator::add($thread, array('class' => 'Present/IncidentThreadPresenter', 'property' => 'MyOwnName')), 'FakeIncidentThreadObject');
        $this->assertIsA(Decorator::add($thread, array('class' => 'Present/IncidentThreadPresenter', 'property' => 'MyOwnName')), 'FakeIncidentThreadObject');
    }
}

class StandardDecoratorWithCustomOverrideTest extends CPTestCase {
    public $testingClass = 'RightNow\Internal\Utils\Framework';

    private $extending = <<<CLASS
<?
namespace Custom\Decorators;
class Escuadra extends \RightNow\Decorators\IncidentThreadPresenter {
    function bananas () {}
}
?>
CLASS;

    private $notExtending = <<<CLASS
<?
namespace Custom\Decorators;
class SomethingElse {
    function fail () {}
}
?>
CLASS;

    function __construct() {
        umask(0000);
        list($class, $prop) = $this->reflect('codeExtensions');
        $prop->setAccessible(true);
        $this->cache = $prop;
    }

    function setUp () {
        FileSystem::mkdirOrThrowExceptionOnFailure(APPPATH . 'decorators');
        $this->cache->setValue(null);
        $this->originalYaml = file_get_contents(APPPATH . 'config/extensions.yml');
    }

    function tearDown () {
        $this->writeToExtension($this->originalYaml, false);
        FileSystem::removeDirectory(APPPATH . 'decorators', true);
    }

    function writeToExtension($contents, $yamlize = true) {
        FileSystem::filePutContentsOrThrowExceptionOnFailure(APPPATH . 'config/extensions.yml', $yamlize ? yaml_emit($contents) : $contents);
    }

    function testCustomIsNotIncludedIfNotInExtenstionsYaml () {
        $thread = new FakeIncidentThreadObject();
        Decorator::add($thread, 'Present/IncidentThreadPresenter');
        $this->assertFalse(method_exists($thread->IncidentThreadPresenter, 'bananas'));
        $this->assertTrue(method_exists($thread->IncidentThreadPresenter, 'getAuthorName'));
    }

    function testCustomIsIncludedAndApplied () {
        FileSystem::filePutContentsOrThrowExceptionOnFailure(APPPATH . 'decorators/Escuadra.php', $this->extending);
        $this->writeToExtension(array('decoratorExtensions' => array('Present/IncidentThreadPresenter' => 'Escuadra')));

        $thread = new FakeIncidentThreadObject();
        Decorator::add($thread, 'Present/IncidentThreadPresenter');
        $this->assertTrue(method_exists($thread->IncidentThreadPresenter, 'bananas'));
        $this->assertTrue(method_exists($thread->IncidentThreadPresenter, 'getAuthorName'));

        $thread2 = new FakeIncidentThreadObject();
        Decorator::add($thread2, array('class' => 'Present/IncidentThreadPresenter', 'property' => 'MyOwnName'));
        $this->assertNull($thread2->IncidentThreadPresenter);
        $this->assertTrue(method_exists($thread2->MyOwnName, 'bananas'));
        $this->assertTrue(method_exists($thread2->MyOwnName, 'getAuthorName'));
    }

    function testExceptionWhenOverridingClassDoesNotExtendStandard () {
        FileSystem::filePutContentsOrThrowExceptionOnFailure(APPPATH . 'decorators/SomethingElse.php', $this->notExtending);
        $this->writeToExtension(array('decoratorExtensions' => array('Present/IncidentThreadPresenter' => 'SomethingElse')));

        $thread = new FakeIncidentThreadObject();

        try {
            Decorator::add($thread, 'Present/IncidentThreadPresenter');
            $this->fail("Should've thrown");
        }
        catch (\Exception $e) {
            $this->assertStringContains($e->getMessage(), 'must extend from');
        }
        $this->assertNull($thread->IncidentThreadPresenter);
    }

    function testExceptionWhenOverridingFileDoesNotExist () {
        $this->writeToExtension(array('decoratorExtensions' => array('Present/IncidentThreadPresenter' => 'Nil')));

        $thread = new FakeIncidentThreadObject();

        try {
            Decorator::add($thread, 'Present/IncidentThreadPresenter');
            $this->fail("Should've thrown");
        }
        catch (\Exception $e) {
            $this->assertStringContains($e->getMessage(), 'IncidentThreadPresenter');
        }
        $this->assertNull($thread->IncidentThreadPresenter);
    }
}

class CustomDecoratorTest extends StandardDecoratorWithCustomOverrideTest {
    private $customDecorator = <<<CLASS
<?
namespace Custom\Decorators;
class Bananas {
    function banana () {}
}
?>
CLASS;

    function testCustomDecoratorIsUsedWhenStandardOneWithThatNameIsNotPresent () {
        FileSystem::filePutContentsOrThrowExceptionOnFailure(APPPATH . 'decorators/Bananas.php', $this->customDecorator);
        $thread = new FakeIncidentThreadObject();
        Decorator::add($thread, 'Bananas');
        $this->assertIsA($thread->Bananas, 'Custom\Decorators\Bananas');
        $this->assertTrue(method_exists($thread->Bananas, 'banana'));

        $thread2 = new FakeIncidentThreadObject();
        Decorator::add($thread2, array('class' => 'Bananas', 'property' => 'MyOwnName'));
        $this->assertNull($thread2->Bananas);
        $this->assertIsA($thread2->MyOwnName, 'Custom\Decorators\Bananas');
        $this->assertTrue(method_exists($thread2->MyOwnName, 'banana'));
    }

    function testCustomDecoratorIsRemoved () {
        FileSystem::filePutContentsOrThrowExceptionOnFailure(APPPATH . 'decorators/Bananas.php', $this->customDecorator);
        $thread = new FakeIncidentThreadObject();
        Decorator::add($thread, 'Bananas');
        Decorator::remove($thread, 'Bananas');
        $this->assertFalse(property_exists($thread, 'Bananas'));

        $thread2 = new FakeIncidentThreadObject();
        Decorator::add($thread2, array('class' => 'Bananas', 'property' => 'MyOwnName'));
        Decorator::remove($thread, 'Bananas');
        $this->assertIsA($thread2->MyOwnName, 'Custom\Decorators\Bananas');
        Decorator::remove($thread, 'MyOwnName');
        $this->assertFalse(property_exists($thread2, 'Bananas'));
    }
}

class FakeIncidentThreadObject {
    static function getMetadata() {
        return (object) array( 'COM_type' => 'Thread' );
    }

    public $existingProperty = 'test';
}
