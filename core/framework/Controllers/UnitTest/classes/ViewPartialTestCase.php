<?php

require_once __DIR__ . '/CPTestCase.php';

use RightNow\Utils\Text,
    RightNow\Utils\Framework;

class ViewPartialTestCase extends CPTestCase {
    function __construct () {
        if (!$this->testingClass) throw new \Exception("A `testingClass` property is required");

        $this->path = CPCORE . 'Views/' . $this->testingClass;
    }

    function getExpected ($testName) {
        $forFile = basename($this->testingClass);
        $parentDir = dirname($this->path);

        return file_get_contents("{$parentDir}/tests/{$forFile}.{$testName}.test.html");
    }

    function assertViewIsUnchanged ($nameOfTestScenario) {
        $actual = $this->{$nameOfTestScenario}();
        $expected = $this->getExpected($nameOfTestScenario);

        $diffOutput = \RightNow\UnitTest\Helper::diff(trim($expected), trim($actual), array('type' => $this->reporter->contentType));

        if ($diffOutput !== false) {
            if ($this->reporter->contentType === 'html') {
                // Parent htmlentities-izes the output if we pass it to #fail, but we just want to display the raw html.
                echo $diffOutput;
                $this->fail();
            }
            else {
                $this->fail($diffOutput);
            }
        }
        else {
            $this->pass();
        }
    }

    function render (array $testData = array()) {
        extract($testData);
        ob_start();
        include $this->path . '.html.php';
        return ob_get_clean();
    }
}
