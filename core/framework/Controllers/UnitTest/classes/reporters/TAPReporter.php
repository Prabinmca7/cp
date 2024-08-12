<?

require_once __DIR__ . '/RegularReporter.php';

class TAPReporter extends \RegularReporter {
    public $contentType = 'text';

    function outputPlan () {
        echo "TAP version 13\n";
    }

    protected function outputSummary () {
        echo '1..' . count($this->cases);
    }

    protected function logResult($type, $message) {
        return new \TAPLine($type, $message);
    }

    function paintFormattedMessage () {
        // Called when `$this->dump` is called in a test case.
        // No-op for now. Eventually integrate into YAML output
        // for the test case's TAP line.
    }
}

/**
 * TAP output line for each test case.
 *
 *      ok test number - suite name : test case name
 *      not ok number - suite name : test case name
 *        ---
 *        errors:
 *          - php error message
 *          - assertion fail message
 *        ...
 *      ok test number # skip - suite name : test case name
 *
 */
class TAPLine extends \RegularOutputLine {
    private static $status = array(
        'pass' => array('status' => 'ok'),
        'fail' => array('status' => 'not ok'),
        'skip' => array('status' => 'ok', 'directive' => '# skip'),
    );

    protected function outputTestCaseResult ($testNumber, $testName) {
        assert($testNumber);
        $outcome = self::$status[$this->type];
        $directive = $outcome['directive'] ? " {$outcome['directive']}" : '';
        echo "{$outcome['status']} {$testNumber}{$directive} - {$testName}\n";
    }

    protected function outputErrorDetails () {
        echo "  ---\n";
        echo "  errors:\n" . self::produceYamlArray($this->details['fail'], "  ");
        echo "  ...\n";
    }

    private static function produceYamlArray (array $list, $indentationLevel) {
        return implode("\n", array_map(function ($item) use ($indentationLevel) {
            return "{$indentationLevel}  - {$item}";
        }, $list)) . "\n";
    }
}
