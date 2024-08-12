<?

require_once __DIR__ . '/../simpletest1.1beta/unit_tester.php';
require_once __DIR__ . '/../simpletest1.1beta/mock_objects.php';
require_once __DIR__ . '/../simpletest1.1beta/collector.php';
require_once __DIR__ . '/Helper.php';
require_once __DIR__ . '/CPTestCase.php';
require_once __DIR__ . '/ViewPartialTestCase.php';

class ViewPartialTestRunner {
    function __construct ($pathToTestFiles, array $testFiles) {
        $this->pathToTestFiles = $pathToTestFiles;
        $this->testFiles = $testFiles;
        $this->suite = $this->buildSuite();
    }

    function run () {
        return $this->suite->run(new HtmlReporter());
    }

    private function buildSuite () {
        ksort($this->testFiles);
        $suite = new TestSuite();
        $suite->TestSuite('Unit tests for ' . basename($this->pathToTestFiles));

        foreach ($this->testFiles as $path) {
            $suite->addFile("{$this->pathToTestFiles}/{$path}");
        }

        return $suite;
    }
}
