<?

use RightNow\Utils\Text,
    RightNow\Utils\FileSystem;

require_once __DIR__ . '/../simpletest1.1beta/unit_tester.php';
require_once __DIR__ . '/Helper.php';
require_once __DIR__ . '/CPTestCase.php';
require_once __DIR__ . '/ViewPartialTestCase.php';

class ViewPartialTestUpdater {
    function __construct (array $testFiles) {
        $this->testFiles = $testFiles;
    }

    function run () {
        foreach ($this->testFiles as $path) {
            require_once $path;

            $viewName = Text::getSubstringBefore(basename($path), '.'); // path = ViewName.test.php.
            $className = "Test{$viewName}Partial";
            $class = new $className();

            $tests = FileSystem::listDirectory(dirname($path), true, false,
                array('match', "/^$viewName.*\.test\.html$/"));

            foreach ($tests as $testPath) { // testPath = /abs/to/tests/ViewName.testName.test.html.
                $test = basename($testPath);
                $test = explode('.', $test);
                $test = $test[1];
                FileSystem::filePutContentsOrThrowExceptionOnFailure($testPath, $class->$test());
            }
        }
    }
}
