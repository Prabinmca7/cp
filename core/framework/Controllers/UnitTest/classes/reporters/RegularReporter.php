<?

/**
 * Simple PHP Test calls Reporter hook methods
 * per assertion rather than per test case.
 * This class deals with that idiosyncracy by
 * keeping track of which asserts belong to
 * which test case and outputting the result
 * of the test case when all of its assertions
 * have been called.
 */
class RegularReporter extends SimpleReporter {
    protected $currentTestCase;
    protected $cases = array();
    protected $failures = 0;
    protected $count = 0;

    function __construct() {
        parent::__construct();
        // Stream, rather than waiting for Apache to buffer + gzip everything.
        header("Content-Encoding: none");
    }

    // Called once for every assertion pass.
    function paintPass ($message) {
        $this->recordResult('pass', $message);
    }

    // Called once for every assertion failure.
    function paintFail ($message) {
        $this->recordResult('fail', $message);
    }

    // Called when an uncaught exception's thrown.
    function paintException ($exception) {
        $this->recordResult('fail', $this->formatException($exception));
    }

    // Called when an error's hit.
    function paintError ($message) {
        $this->paintFail($message);
    }

    // The only time this is called is when the 'subtests' parameter
    // is specified. It gets called once regardless of the number of
    // skipped tests.
    function paintSkip ($message) {
        $this->recordResult('skip', $message);
    }

    // Called before test suite runs.
    function paintHeader ($testName) {
        $this->outputPlan($testName);
    }

    // Called after test suite runs.
    function paintFooter () {
        $this->outputPreviousTest();
        $this->outputSummary();
    }

    // Subclasses should override.
    protected function logResult ($type, $message) {
        return new \RegularOutputLine($type, $message);
    }

    // Subclasses should override.
    protected function outputPlan ($testName) {
        echo "$testName\n";
    }

    // Subclasses should override.
    protected function outputSummary () {
        echo "\nTOTAL: " . $this->count;
        echo "\nPASS: " . ($this->count - $this->failures);
        echo "\nFAIL: " . $this->failures;
    }

    private function recordResult ($type, $message) {
        $case = $this->getTestCaseLabel();

        if (!$this->cases[$case]) {
            // New test case.
            if ($this->currentTestCase) {
                // Output the previous test case's outcome
                // since all of its assertions have been called.
                $this->outputPreviousTest();
            }
            $this->cases[$case] = $this->logResult($type, $message);
        }
        else {
            $this->cases[$case]->addDetails($type, $message);
        }

        $this->currentTestCase = $case;

        if ($type === 'fail') {
            $this->failures++;
        }
    }

    private function getTestCaseLabel () {
        list($desc, $path, $suite, $case) = $this->getTestList();

        if (!$case) {
            // Rendering tests are added via TestCase instances rather
            // than via the suite's #addFile API. As such, the $case
            // is computed differently.
            $case = $suite;
            // Suite is the full URL to the rendering test page.
            $suite = $path;
        }
        else {
            // Include test file's name.
            $suite = basename($path) . " > {$suite}";
        }

        return "{$suite} : {$case}";
    }

    private function formatException ($exception) {
        return 'Unexpected exception of type [' . get_class($exception) .
                '] with message ['. $exception->getMessage() .
                '] in ['. $exception->getFile() .
                ' line ' . $exception->getLine() . ']';
    }

    private function outputPreviousTest () {
        $this->count++;
        $this->cases[$this->currentTestCase]->output($this->count, $this->currentTestCase);
    }
}

/**
 * Super minimal output line for each test case.
 *
 *      pass: TestSuite/TestCaseName1
 *      fail: TestSuite/TestCaseName2: error message(s)
 *
 */
class RegularOutputLine {
    protected $type;               // Either 'pass', 'fail', or 'skip'
    protected $details = array(
        'fail' => array(),
        'pass' => array(),
        'skip' => array(),
    );

    function __construct ($type, $details) {
        $this->type = $type;
        $this->details[$type] []= $details;
    }

    function output ($testNumber, $testName) {
        $this->outputTestCaseResult($testNumber, $testName);

        if ($this->type !== 'pass') {
            $this->outputErrorDetails();
        }

        ob_flush();
    }

    function addDetails ($type, $details) {
        if ($type === 'fail') {
            // Failure overrides any other existing type that
            // had been previously set.
            $this->type = $type;
        }
        $this->details[$type] []= $details;
    }

    // Subclasses should override.
    protected function outputTestCaseResult ($testNumber, $testName) {
        assert($testNumber);
        echo "\n" . $this->type . ": {$testName}";
    }

    // Subclasses should override.
    protected function outputErrorDetails () {
        echo ': ' . implode(', ', $this->details['fail']);
    }
}
