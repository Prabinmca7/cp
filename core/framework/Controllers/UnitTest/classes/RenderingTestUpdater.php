<?

use RightNow\Utils\Text,
    RightNow\Utils\FileSystem;

class RenderingTestUpdater {
    static function update ($test) {
        if ($test->output) {
            if (!is_writable($test->fullTestPath)) {
                throw new \Exception("{$test->fullTestPath} is not world writable. You need to `chmod` it.");
            }
            $originalTestData = $test->originalTestData;
            $originalTestData['output'] = $test->filteredOutput();
            if ($test->variables) {
                $originalTestData['output'] = \RightNow\UnitTest\Helper::processFixtureData($test->variables, $originalTestData['output'], true);
            }

            $newTestFile = self::createTestFile($originalTestData);
            FileSystem::filePutContentsOrThrowExceptionOnFailure($test->fullTestPath, $newTestFile);
            return true;
        }
        return false;
    }

    private static function createTestFile($testData) {
        $testFile = '';
        $testData = self::sortTestData($testData);

        foreach ($testData as $sectionName => $sectionValue) {
            if (is_array($sectionValue)) {
                // Convert array back into string.
                $sectionValue = json_encode($sectionValue);
            }

            $sectionValue = trim($sectionValue);
            $separator = (Text::stringContains($sectionValue, "\n")) ? "\n" : " ";
            $testFile .= "$sectionName:$separator$sectionValue\n";
        }

        return $testFile;
    }

    private static function sortTestData($testData) {
        uksort($testData, function ($a, $b) {
            if ($a === 'input')
                return $b === 'input' ? 0 : -1;
            if ($a === 'output')
                return $b === 'output' ? 0 : 1;
            if ($b === 'input')
                return $a === 'input' ? 0 : 1;
            if ($b === 'output')
                return $a === 'output' ? 0 : -1;
            return strcasecmp($a, $b);
        });

        return $testData;
    }
}
