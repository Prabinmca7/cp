<?php

namespace RightNow\Controllers\UnitTest;

use RightNow\Utils\Text,
    RightNow\Utils\FileSystem;

if (IS_HOSTED) {
    exit("Did we ship the unit tests?  That would be sub-optimal.");
}

class Rendering extends \RightNow\Controllers\Admin\Base {
    private static $dependencies = array(
        'simpletest1.1beta/unit_tester',
        'classes/Helper',
        'classes/TestReporter',
        'classes/RenderingTestParser',
        'classes/RenderingPageRequester',
        'classes/RenderingPagePublisher',
        'classes/RenderingTestUpdater',
        'classes/RenderingTestValidationManager',
        'classes/ViewPartialTestUpdater',
        'classes/ViewPartialTestCase',
    );

    /**
     * Optional keyword arguments that appear in the URL after the
     * controller method and before the subtree of tests.
     * @var  array
     */
    private $testMethodArguments = array();

    /**
     * Any options from $testMethodArguments specified in the URL
     * @var object
     */
    private $options;

    /**
     * Test type (one of `this->testTypes`)
     * @var object
     */
    private $currentTestTypes;

    function __construct() {
        self::requireDependencies();

        parent::__construct(true, '_phonyLogin');

        $this->options = (object) array();

        // types of tests
        $this->testTypes = array(
            'widgets' => array(
                'testRegex' => '@/tests/[^./][^/]*\.test$@',
                'corePath' => CORE_FILES,
                'basePath' => CORE_FILES . "widgets",
                'customPath'      => APPPATH,
                'customBasePath'  => APPPATH . "widgets",
            ),
            'views' => array(
                'testRegex' => '@/tests/.*\.test$@',
                'corePath' => CPCORE,
                'basePath' => CPCORE . "Views/Admin",
            ),
            'partials' => array(
                'testRegex'       => '@/tests/.*\.test\.php$@',
                'corePath'        => CPCORE,
                'basePath'        => CPCORE . 'Views/Partials',
            ),
        );

        // Arguments are either:
        // - bool: the presence of the keyword flips the arg on. (e.g. /verbose/skipDeploy/...)
        // - arg:  an additional option / keyword is required after the arg's keyword. (e.g. /reporter/TAP/...)
        $this->testMethodArguments = array(
            'skipDeploy'       => array('type' => 'bool', 'desc' => 'Run the test or update without deploying. This option only applies to the jsFunctional endpoint. Deploying is usually a good idea.'),
            'saveTestPages'    => array('type' => 'bool', 'desc' => 'Don\'t delete the test pages at the end of the run.'),
            'serialRequests'   => array('type' => 'bool', 'desc' => 'Requests the test pages one at a time, rather than many at once.  Resolves some weird problems regarding the clickstream table.'),
            'showLinks'        => array('type' => 'bool', 'desc' => 'Show the links to individual JavaScript Widget Interaction Test pages'),
            'saveWidgetOutput' => array('type' => 'bool', 'desc' => 'Save off the test page output so the JS Widget tests can use it later'),
            'subTestPaths'     => array('type' => 'arg',  'desc' => 'Whether these are a specific subset of rendering tests.  Only impacts "test" and "deployAndTest" requests and should only be enabled when running all tests. Options: normal, subTestPath# (refer to RenderingPageRequester.php to see list of valid options)'),
            'reporter'         => array('type' => 'arg',  'desc' => 'Reporter to use in order to display the test results. Options: ' . implode(', ', array_values(\TestReporter::$reporters))),
        );

        $this->argumentParser = new \RenderingTestParser($this->testMethodArguments);
        $this->requester = new \RenderingPageRequester($this->argumentParser);
        $this->publisher = new \RenderingPagePublisher($this->argumentParser, $this->requester);
    }

    /**
     * GET /ci/unitTest/rendering
     */
    function index() {
        $this->load->view('tests/rendering.php', array(
            'tests' => $this->_getTestFiles(),
        ));
    }

    /**
     * GET /ci/unitTest/rendering/clean
     *
     * Removes any previously generated test files.
     */
    function clean() {
        $this->publisher->clean();
    }

    /**
     * GET /ci/unitTest/rendering/update
     *
     * Updates the specified .test files with actual output.
     */
    function update() {
        $this->_setUp();

        if ($this->_testingPartials()) {
            $this->_updateViewPartials();
            echo "All test files have been updated.";
            exit(0);
        }

        $tests = $this->_generateTests();
        $updated = 0;

        foreach ($tests as $test) {
            if (\RenderingTestUpdater::update($test)) {
                $updated++;
            }
        }

        if (count($tests) === $updated) {
            echo "<br>$updated test files updated:<br>";
            echo implode("<br>", array_map(function ($test) { return $test->testPath; }, $tests));

            exit(0);
        }

        echo "There was a problem updating the test files.";
        exit(1);
    }

    /**
     * GET /ci/unitTest/rendering/jsFunctional
     *
     * Use if you want to test the resulting pages using /getTestPage or Selenium.
     * Leaves the pages (customer/development/views/pages/unitTest/rendering) intact after
     * execution.
     */
    function jsFunctional($arg) {

        if ($arg === 'showHelp') {
            $this->_showJSFunctionalHelp();
            return;
        }

        $this->_setUp();
        // Don't pull in view partial or views testing, just widgets.
        $this->currentTestTypes = array('widgets');
        $this->requester->jsFunctional = true;
        $this->publisher->saveOutputForJSWidget($this->_generateTests());

        if ($this->options->showLinks) {
            $this->_showJSFunctionalLinks();
            return;
        }

        exit("JavaScript Widget Interaction Tests generated. Ready for selenium testing.");
    }

    /**
     * GET /ci/unitTest/rendering/deployAndTest
     *
     * Publishes the test pages and runs them.
     * You'll possibly encounter Apache timeouts using this route.
     * So you may want to hit /deploy and /test separately
     */
    function deployAndTest() {
        $this->_setUp();
        $this->publisher->skipDeploy = $this->requester->skipDeploy = $this->options->skipDeploy = false;
        $this->requester->subTestPaths = $this->options->subTestPaths ?: 'normal';

        $this->_test($this->_deploy());
        if (!$this->options->saveTestPages) {
            $this->publisher->deleteTestPages();
        }
    }

    /**
     * GET /ci/unitTest/rendering/test
     *
     * Runs the gamut of rendering tests on the specified subtree of .test files.
     * /deploy must have already been hit.
     * Use /deployAndTest to deploy and test in one request.
     */
    function test() {
        $this->_setUp();
        $this->requester->subTestPaths = $this->options->subTestPaths ?: 'normal';
        $this->_test($this->publisher->getTestCasesForPublishedPages($this->currentTestTypes, $this->testTypes));
    }

    /**
     * GET /ci/unitTest/rendering/deploy
     *
     * Produces the specified test cases and
     * deploys the site, priming it for testing.
     * Hit /test to test the published test cases.
     */
    function deploy() {
        $this->_setUp();
        $this->publisher->skipDeploy = $this->requester->skipDeploy = $this->options->skipDeploy = false;
        $this->_deploy();
        echo "Finished deploying";
    }

    /**
     * GET /ci/unitTest/rendering/getTestPage/[pathToWidget]
     *
     * Retrieves a saved-off HTML file saved by #jsFunctional.
     */
    function getTestPage(/* widgets/standard/folder/Name/tests/testName[/url params] */) {
        $segments = implode('/', array_slice(func_get_args(), 0, 6));
        $testFile = $this->publisher->testPagesPath . $segments . '.html';

        if (FileSystem::isReadableFile($testFile)) {
            ob_clean();
            flush();
            header('Content-Type:text/html; charset=UTF-8');
            readfile($testFile);
            exit;
        }

        echo "Error: Unable to retrieve test file {$testFile}";
    }

    protected function _phonyLogin() { /* Yes, this should do nothing. */ }

    private function _test(array $tests) {
        $result = ($this->_testingPartials())
            ? $this->_testViewPartials($tests)
            : $this->_testRenderedViews($tests);

        exit($result ? 0 : 1);
    }

    /**
     * Handles view partial testing.
     * @param  string $type update or test
     * @return boolean       Result of operation
     */
    private function _testViewPartials(array $tests) {
        $suite = new \TestSuite();
        $suite->TestSuite('Rendering tests for ' . $this->_getTestSuiteName());

        foreach ($tests as $test) {
            $suite->addFile($test);
        }
        return $suite->run(\TestReporter::reporter($this->options->reporter ?: 'CPHTML'));
    }

    private function _updateViewPartials() {
        $tests = $this->publisher->getTestCasesForPublishedPages($this->currentTestTypes, $this->testTypes);
        $updater = new \ViewPartialTestUpdater($tests);
        $updater->run();
    }

    private function _testRenderedViews(array $tests) {
        // create reporter immediately so headers get sent first
        $reporter = \TestReporter::reporter($this->options->reporter ?: 'CPHTML');

        $tests = $this->requester->requestTestPages($tests);

        $suite = new \TestSuite();
        $suite->TestSuite('Rendering tests for ' . $this->_getTestSuiteName());

        // Start validating.
        // The validator updates each test case when it finishes validating.
        $validator = new \RenderingTestValidationManager($tests);
        $validator->serialRequests = $this->options->serialRequests;
        $validator->saveTestPages = $this->options->saveTestPages;
        $validator->validate();

        foreach ($tests as $test) {
            if (is_string($test)) {
                $suite->addFile($test);
            }
            else {
                $suite->add($test);
            }
        }
        $result = $suite->run($reporter);

        if ($this->options->saveWidgetOutput) {
            $this->publisher->saveOutputForJSWidget($tests);
        }

        return $result;
    }

    private function _deploy() {
        return $this->publisher->publishTestPages($this->currentTestTypes, $this->testTypes);
    }

    private function _setUp() {
        $this->_populateOptionsFromUrlSegments();
        $this->_contentEncoding();
    }

    private function _getTestSuiteName () {
        if (count($this->currentTestTypes) > 1) {
            // Running all the view tests.
            return implode(' + ', $this->currentTestTypes);
        }

        $testType = $this->testTypes[$this->currentTestTypes[0]];
        return \RightNow\Utils\Text::getSubstringAfter($testType['basePath'], $testType['corePath']);
    }

    /**
     * Publish and deploy test cases.
     * @return array Contains test cases
     */
    private function _generateTests() {
        $tests = $this->_deploy();
        if (!$this->options->skipDeploy) {
            echo "Finished deploying";
        }
        return $this->requester->requestTestPages($tests);
    }

    /**
     * Sets test options according to specific
     * keywords in the url.
     */
    private function _populateOptionsFromUrlSegments() {
        $options = $this->argumentParser->parseArguments();
        foreach ($options as $name => $value) {
            $this->options->{$name} = $value;
        }
        //Load in the path to the specific tests, if one isn't provided use the default in $this->testTypes
        if (count($this->options->segments) > 0) {
            $testType = array_shift($this->options->segments);
            $this->currentTestTypes = array($testType);

            if (count($this->options->segments) > 0) {
                $path = '/' . implode('/', $this->options->segments);

                if (Text::beginsWith($path, '/custom')) {
                    $this->testTypes[$testType]['basePath'] = $this->testTypes[$testType]['customBasePath'] .  $path;
                    $this->testTypes[$testType]['corePath'] = $this->testTypes[$testType]['customPath'];
                }
                else {
                    $this->testTypes[$testType]['basePath'] .=  $path;
                    unset($this->testTypes[$testType]['customBasePath']);
                    unset($this->testTypes[$testType]['customPath']);
                }
            }
        }
        else {
            $this->currentTestTypes = array_keys($this->testTypes);
        }

        foreach ($this->currentTestTypes as $testType) {
            if (!isset($this->testTypes[$testType])) {
                throw new \Exception("The test type ($testType) does not exist.");
            }
            if (!is_dir($this->testTypes[$testType]['basePath']) || !is_readable($this->testTypes[$testType]['basePath'])) {
                throw new \Exception("The basePath ({$this->testTypes[$testType]['basePath']}) does not point to a readable directory.");
            }
        }

        $this->publisher->showLinks = $this->options->showLinks;
        $this->publisher->skipDeploy = $this->requester->skipDeploy = $this->options->skipDeploy;
        $this->requester->serialRequests = $this->options->serialRequests;
    }

    private function _contentEncoding() {
        if($this->options->verbose || $this->options->extraVerbose) {
            //Enable streaming output
            header("Content-Encoding: none");
        }
    }

    private function _showJSFunctionalLinks() {
        $this->load->view('tests/widgetJavascript.php', array(
            'tests' => $this->publisher->functionalTests,
        ));
    }

    private function _showJSFunctionalHelp () {
        $this->load->view('tests/widgetJavascriptHelp.php', array(
            'dirs' => $this->_getWidgetsAsCategories(),
        ));
    }

    private function _getTestFiles() {
        $files = array();

        foreach ($this->testTypes as $groupName => $testType) {
            $files[$groupName] = array_keys(FileSystem::removeDirectoriesFromGetDirTreeResult(FileSystem::getDirectoryTree($testType['basePath'], array(
                'regex' => $testType['testRegex'],
            ))));
        }

        return $files;
    }

    private function _testingPartials() {
        $currentTestTypes = array_unique($this->currentTestTypes);
        return count($currentTestTypes) === 1 && $currentTestTypes[0] === 'partials';
    }

    private function _getWidgetsAsCategories() {
        $widgets = array();
        $files = $this->_getTestFiles();

        foreach ($files['widgets'] as $filePath) {
            list($type, $category, $name) = explode('/', $filePath);
            $widgets[$category] || ($widgets[$category] = array());
            if (!in_array($name, $widgets[$category])) {
                $widgets[$category] []= $name;
            }
        }

        return $widgets;
    }

    private static function requireDependencies() {
        foreach (self::$dependencies as $path) {
            require_once __DIR__ . "/{$path}.php";
        }
    }
}
