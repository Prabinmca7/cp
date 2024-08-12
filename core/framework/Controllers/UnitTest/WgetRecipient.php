<?php

namespace RightNow\Controllers\UnitTest;
use RightNow\Api;

if (IS_HOSTED) {
    exit("Did we ship the unit tests?  That would be sub-optimal.");
}

/**
 * This controller is used by unit tests to verify functionality with
 * configuration values. Since configuration values are cached, the unit
 * tests cannot update the configuration value and have that updated value
 * read in the same process. By calling this controller with wget, the
 * unit test can modify the configuration value and then perform unit tests
 * on the updated value.
 */
class WgetRecipient extends \RightNow\Controllers\Admin\Base {
    function __construct() {
        parent::__construct(true, '_phonyLogin');
    }

    /**
    * Supposed to do nothing.
    */
    protected function _phonyLogin() {}

    /**
     * This controller should be accessed by unit tests and not be
     * used by anyone directly just hitting the index.
     */
    function index() {
        echo "";
    }

    /**
     * Endpoint to spit out all request headers.
     */
    function echoHeaders() {
        // @codingStandardsIgnoreStart
        print_r($_SERVER);
        // @codingStandardsIgnoreEnd
    }

    /**
     * Invokes the specified test method in a model's test file.
     * @param $name String Name of the test file
     * @param $method String Name of the method to invoke
     */
    function testModel($name, $method) {
        $this->invokeTestMethod(urlencode(CPCORE . "/Models/tests/$name.test.php"), ucfirst($name) . "ModelTest", $method);
    }

    /**
     * Manually invokes a method in the specified test file as a test method;
     * used to run methods that aren't prefixed with "test" (and are thus
     * ignored by the unit test's auto-invoker) in order to run assertions
     * (usually involving config changes) in a separate process.
     * @param $filePath String Path of the test file; URL-encoded
     * @param $testClassName String class name of the test class
     * @param $testMethod String name of the method to invoke
     */
    function invokeTestMethod($filePath, $testClassName, $testMethod) {
        require_once __DIR__ . '/PhpFunctional.php';
        require_once __DIR__ . '/simpletest1.1beta/unit_tester.php';
        require_once __DIR__ . '/simpletest1.1beta/mock_objects.php';
        require_once __DIR__ . '/simpletest1.1beta/collector.php';
        require_once __DIR__ . '/classes/CPTestCase.php';
        require_once __DIR__ . '/classes/WidgetTestCase.php';
        require_once urldecode($filePath);

        $class = new \ReflectionClass($testClassName);
        $instance = $class->newInstance();
        \SimpleTest::getContext()->setTest($instance);
        // the 'reporter' property must be set when
        // manually calling a unit test case, so
        // make it accessible and set it
        $reporter = $class->getProperty('reporter');
        $reporter->setAccessible(true);
        $reporter->setValue($instance, new \HTMLReporter);

        $method = $class->getMethod('createInvoker');
        $invoker = $method->invoke($instance);
        echo $invoker->invoke($testMethod);
    }

    /**
     * End point for invoking a compatibility layer SQL function.
     * @param $modelName [string] - Obvious.
     * @param $method [string] - method name
     * @param [...] - Any number of additional arguments that should be fed to the method
     */
    function invokeCompatibilitySQLFunction($modelName, $method) {
        $args = array_slice(func_get_args(), 2);
        require_once __DIR__ . '/classes/Helper.php';
        require_once DOCROOT . "/cp/core/compatibility/Internal/Sql/$modelName.php";
        $invoker = \RightNow\UnitTest\Helper::getStaticMethodInvoker('\RightNow\Internal\Sql\\' . ucfirst($modelName), $method);
        echo json_encode(call_user_func_array($invoker, $args));
    }
}
