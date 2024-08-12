<?php

namespace RightNow\Controllers\UnitTest;

use RightNow\Utils\Text,
    RightNow\Utils\FileSystem;

if (IS_HOSTED) {
    exit("Did we ship the unit tests?  That would be sub-optimal.");
}

class Documentation extends \RightNow\Controllers\Admin\Base {

    private $phpBinaryPath = '/nfs/local/linux/phpDocumentor/current/bin/php';
    private $phpDocRunnerScript = '/nfs/local/linux/phpDocumentor/current/bin/phpdoc.php';
    private $phpdocConfigFilePath, $phpdocResultsPath;

    private $ignoredLines = array('No page-level DocBlock was found in file ',
                                  'No DocBlock was found for method __construct()',
                                  'No short description for property ',
                                  'No DocBlock was found for property',
                                  'No DocBlock was found for method __get()',
                                  'No DocBlock was found for method __set()');

    private $ignoredFiles = array();

    function __construct()
    {
        parent::__construct(true, '_phonyLogin');
        umask(0);
        $this->phpdocBaseDirectory = DOCROOT . '/cp/extras/docGeneration/phpdoc/';
        $this->phpdocResultsPath = $this->phpdocBaseDirectory . '/output/';
    }

    /**
     * Default function when one is not specified. Generates help text for documentation unit tests
     */
    public function index()
    {
        $this->load->view('tests/documentationHelp.php');
    }

    /**
     * Executes the PHPDoc documentation generation tool.
     * Optional arguments:
     *      saveOutput: Won't delete resulting XML file either before or after the test.
     *      verbose: Displays output for every file scanned
     */
    public function php() {
        $options = $this->_parseRuntimeFlags(func_get_args());
        if(!$options['saveOutput']){
            $this->removePhpDocOutput();
        }
        exec("{$this->phpBinaryPath} {$this->phpDocRunnerScript} parse --force -c {$this->phpdocBaseDirectory}phpdoc.dist.xml 2>&1", $output);
        $testOutput = array();
        $currentFile = '';
        $failures = 0;
        foreach($output as $line){
            $line = trim($line);
            if(Text::stringContains($line, 'could not be created')){
                exit("$line. Please update this directory to be writable by PHP.");
            }
            //Ignore status lines
            if(Text::stringContains($line, 'Initializing parser and collecting files') ||
               Text::stringContains($line, 'Parsing file') ||
               Text::stringContains($line, 'Storing structure.xml in')){
                continue;
            }
            //New file is being parsed, save off the file name and continue on
            if(Text::stringContains($line, 'Parsing /')){
                $currentFile = Text::getSubstringAfter($line, '/rnw/scripts/');
                continue;
            }
            if($options['verbose']){
                if(!is_array($testOutput[$currentFile])){
                    $testOutput[$currentFile] = array('errorCount' => 0);
                }
                $testOutput[$currentFile][$line] = 'info';
            }

            //We don't care about these errors
            foreach($this->ignoredLines as $ignoredLine){
                if(Text::stringContains($line, $ignoredLine)){
                    continue 2;
                }
            }
            if(!is_array($testOutput[$currentFile])){
                $testOutput[$currentFile] = array('errorCount' => 0);
            }
            $testOutput[$currentFile][$line] = 'error';
            $testOutput[$currentFile]['errorCount']++;
            $failures++;
        }

        if(!$options['saveOutput']){
            $this->removePhpDocOutput();
        }
        $results = array('output' => $testOutput, 'failureCount' => $failures, 'verbose' => $options['verbose']);
        echo $this->load->view('tests/documentationResults.php', array('results' => $results), true);
        exit($failures ? 1 : 0);
    }

    /**
     * Removes entire output directory. Useful in case permissions get horked.
     */
    public function removePhpDocOutput(){
        FileSystem::removeDirectory($this->phpdocResultsPath, true);
    }

    protected function _phonyLogin() {
        // Yes, this should do nothing.
    }

    /**
     * Parses out any runtime flags that are specified in an easier to consume format.
     * @param array $methodArguments Runtime arguments
     * @return array Arguments converted into associative array of flags
     */
    private function _parseRuntimeFlags($methodArguments){
        $methodArguments = array_map('strtolower', $methodArguments);

        return array(
            'saveOutput' => array_search('saveoutput', $methodArguments) !== false ? true : false,
            'verbose' => array_search('verbose', $methodArguments) !== false ? true : false,
        );
    }
}