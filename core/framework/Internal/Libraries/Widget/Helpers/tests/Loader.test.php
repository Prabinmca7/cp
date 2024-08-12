<?php

namespace {

    use RightNow\Internal\Libraries\Widget\Helpers\Loader;

    RightNow\UnitTest\Helper::loadTestedFile(__FILE__);

    class WidgetHelperLoader extends CPTestCase {
        public $testingClass = 'RightNow\Internal\Libraries\Widget\Helpers\Loader';

        function __construct($label = false) {
            parent::__construct($label);

            // Basically a super-convoluted way to do dependency injection for a static singleton.
            // Override a static property that's used as the class name that's instantiated to do the loading functionality.
            // This is so that the separate class can be mocked out.
            $class = new \ReflectionClass($this->testingClass);
            $this->loaderProp = $class->getProperty('loaderClass');
            $this->loaderProp->setAccessible(true);
        }

        function setUp () {
            $this->origLoaderValue = $this->loaderProp->getValue();
            $this->loaderProp->setValue('MockLoaderClass');
        }

        function tearDown () {
            $this->loaderProp->setValue($this->origLoaderValue);
            MockLoaderClass::reset();
        }

        function testFetchWidgetHelperReturnsBaseHelperClassForWidgetsWithoutHelpers () {
            $class = Loader::fetchWidgetHelper('standard/utils/PrintPage');
            $this->assertIdentical('RightNow\Libraries\Widget\Helper', $class);
        }

        function testFetchWidgetHelperReturnsWidgetHelperClass () {
            $class = Loader::fetchWidgetHelper('standard/discussion/QuestionComments');
            $this->assertIdentical('RightNow\Helpers\QuestionCommentsHelper', $class);
        }

        function testFetchSharedHelpersNotifiesWhenItCannotLoadSpecifiedHelper () {
            list($returned, $echoed) = $this->returnResultAndContent(function () {
                return Loader::fetchSharedHelpers(array(
                    'artist',
                    'Mary',
                ));
            });
            $this->assertIdentical(array(), $returned);
            $this->assertStringContains($echoed, "'artist.php'");
            $this->assertStringContains($echoed, "'Mary.php'");

            $this->assertSame(2, MockLoaderClass::$called);
            $this->assertIdentical(array('artist', 'artist.php'), MockLoaderClass::$calledWith[0]);
            $this->assertIdentical(array('Mary', 'Mary.php'), MockLoaderClass::$calledWith[1]);
        }

        function testCachedRetrievalWhenAlreadyLoaded () {
            // Since this mocked return value indicates that the
            // custom helper was not loaded, the custom helper's
            // classname at the bottom of this file should not be
            // returned by Loader#fetchSharedHelpers.
            MockLoaderClass::$returnValue = array(
                'core' => true,
            );
            $firstReturn = Loader::fetchSharedHelpers(array('Mock'));
            $this->assertIdentical(array('Mock' => 'RightNow\Helpers\MockHelper'), $firstReturn);
            $secondReturn = Loader::fetchSharedHelpers(array('Mock'));
            $this->assertIdentical($firstReturn, $secondReturn);
            $this->assertSame(1, MockLoaderClass::$called);
        }

        function testWidgetWithoutHelperButExtendingFromWidgetWithHelperWorks () {
            $class = Loader::fetchWidgetHelper('custom/viewhelpertest/ExtendWithoutAnyHelper');
            $this->assertIdentical('RightNow\Helpers\SourceResultListingHelper', $class);
        }

        function testCoreHelperClassName () {
            $this->assertIdentical('RightNow\Helpers\BobHelper', Loader::coreHelperClassName('Bob'));
        }

        function testCustomHelperClassName () {
            $this->assertIdentical('Custom\Helpers\BobHelper', Loader::customHelperClassName('Bob'));
        }
    }

    // Used as the mock for RightNow\Internal\Libraries\Widget\ExtensionLoader.
    // #loadExtension returns what you want it to by returning the static `$returnValue`
    // property.
    class MockLoaderClass {
        public static $calledWith = array();
        public static $called = 0;
        public static $returnValue = array();

        function loadExtension ($name, $fileName) {
            self::$called++;
            self::$calledWith []= func_get_args();

            return self::$returnValue;
        }

        static function reset () {
            self::$called = 0;
            self::$calledWith = self::$returnValue = array();
        }
    }
}

namespace RightNow\Helpers {
    class MockHelper {}
}

namespace Custom\Helpers {
    class MockHelper {}
}
