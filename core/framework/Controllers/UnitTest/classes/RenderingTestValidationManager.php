<?

use RightNow\Api,
    RightNow\Utils\Text,
    RightNow\Utils\FileSystem;

require_once __DIR__ . '/RenderingTestValidator.php';

/**
 * Fires off a bunch of processes to validate each test case.
 * After validation completes for a test case, the result of the validation
 * is assigned as a RenderingTestValidator instance onto the test case.
 */
class RenderingTestValidationManager {
    const MAX_CONCURRENT_JOBS = 50;

    public $serialRequests = false;
    public $saveTestPages = false;

    private static $path = '/nfs/local/generic/totalvalidatorpro/current';
    private static $java = '/nfs/local/linux/jdk/1.6/current/bin/java';

    /**
     * Supply an array containing RenderingTestCase instances.
     * @param array $testCases RenderingTestCase instances
     */
    function __construct (array $testCases) {
        $this->testCases = $testCases;
        $this->tempDirBase = get_cfg_var('upload_tmp_dir') . '/unitTest/rendering/';
    }

    /**
     * Kicks off processes to do the validation on each test case.
     */
    function validate () {
        $maxPending = $this->serialRequests ? 1 : self::MAX_CONCURRENT_JOBS;
        $pendingJobs = array();
        $jobs = $this->getValidationJobs();

        while ($jobs || $pendingJobs) {
            while ($jobs && count($pendingJobs) < $maxPending) {
                $job = array_shift($jobs);
                $tempFile = $this->createTempInputFile($job);
                $pendingJobs []= $this->runValidatorProcess($tempFile, $job);
            }

            while ($pendingJobs) {
                foreach ($pendingJobs as $index => &$pendingJob) {
                    $status = array();
                    if (!feof($pendingJob->pipes[1])) {
                        $pendingJob->output .= fread($pendingJob->pipes[1], 8192);
                    }

                    $status = proc_get_status($pendingJob->handle);

                    if (!$status['running']) {
                        while (!feof($pendingJob->pipes[1])) {
                            $pendingJob->output .= fread($pendingJob->pipes[1], 8192);
                        }
                        fclose($pendingJob->pipes[1]);
                        proc_close($pendingJob->handle);

                        $this->populateResultOnTestCase($pendingJob, $status['exitcode']);

                        unset($pendingJobs[$index]);
                    }
                }
            }
        }

        if (!$this->saveTestPages) {
            $this->removeTempDir();
        }
    }

    /**
     * Once a validation process is finished, the RenderingTestCase instance that the
     * validation job was for gets assigned a property (a \Validator instance) that the
     * test case will perform assertions against in time.
     */
    private function populateResultOnTestCase ($finishedJob, $exitStatus) {
        $this->testCases[$finishedJob->test->index]->validationResult = new \RenderingTestValidator($exitStatus, $finishedJob->output, $finishedJob->test->docType);
    }

    /**
     * For each of the RenderingTestCases that need to be validated, produce
     * a job object containing all the relevant info needed for validation.
     * @return array containing jobs:
     *                          - doctype: doctype to validate against
     *                          - path: path to use as the temp file name
     *                          - output: the rendered content of the test
     *                          - index: index in the original `testCases` array of the test case instance
     */
    private function getValidationJobs () {
        $index = -1;
        $jobs = array_map(function ($testCase) use (&$index) {
            $index++;

            if (!($testCase instanceof RenderingTestCase)) return;

            if (isset($testCase->originalTestData['validate']) && $testCase->originalTestData['validate'] === 'false') {
                $testCase->validationResult = 'pass';
                return;
            }

            return (object) array(
                'docType' => \RenderingTestCase::$docTypes[$testCase->originalTestData['template']] ?: \RenderingTestCase::$docTypes['default'],
                'path'    => $testCase->testPath,
                'output'  => $testCase->output,
                'index'   => $index,
            );
        }, $this->testCases);

        return array_filter($jobs);
    }

    /**
     * Starts the process, executing the validation command.
     * @param  string $inputFilePath path to file containing the output
     * @param  object $job           job object
     * @return object                info about the process
     */
    private function runValidatorProcess ($inputFilePath, $job) {
        $outputDir = $this->generateTempDirName();
        FileSystem::mkdirOrThrowExceptionOnFailure($outputDir, true);
        $command = self::createShellCommand($inputFilePath, $job->docType, $outputDir);

        return (object) array(
            'test'    => $job,
            'handle'  => proc_open($command, array(1 => array('pipe', 'w')), $pipes),
            'pipes'   => $pipes,
            'output'  => '',
        );
    }

    /**
     * Creates the totalvalidator shell command
     * @param  string $input   input file path
     * @param  string $docType doctype
     * @param  string $output  output file to write the results
     * @return string          shell command
     */
    private static function createShellCommand ($input, $docType, $output) {
        $flags = array(
            '-W001',
            '-stdout',
            '-hideresults',
            '-extendedstatus',
            "-file $input",
            "-dtd \"$docType\"",
            "-resultsfolder $output",
        );
        $commands = array(
            'cd ' . self::$path,
            self::$java . " -jar " . self::$path . "/commandline.jar",
        );

        return implode(" && ", $commands) . ' ' . implode(' ', $flags) . " 2>&1";
    }

    private function generateTempDirName () {
        return $this->tempDirBase . Api::intf_id() . microtime(true) . rand();
    }

    private function createTempInputFile ($testCase) {
        $file = "{$this->tempDirBase}{$testCase->path}";

        FileSystem::filePutContentsOrThrowExceptionOnFailure($file, $testCase->output);

        return $file;
    }

    private function removeTempDir () {
        FileSystem::removeDirectory($this->tempDirBase, true);
    }
}
