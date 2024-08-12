<?

use RightNow\Utils\Text;

/**
 * Assigned to each rendering test case's `validationResult` property
 * by the ValidationManager with the result of running validation
 * against that test case.
 */
class RenderingTestValidator {
    public $exitStatus = -1;
    private $docType;
    private $validationOutput;

    /**
     * @param Int $exitStatus validation call's exist code
     * @param string $output     output from validation
     * @param string $docType    doc type that was validated against
     */
    function __construct($exitStatus, $output, $docType) {
        $this->exitStatus = $exitStatus;
        $this->validationOutput = $output;
        $this->docType = $docType;
    }

    function hasValidationErrors () {
        static $suppressedMessages = array(
            'XHTML Basic 1.1' => array(
                //The autocomplete attribute is not supported
                array('errorCode' => 'E620', 'regex' => "/autocomplete/i"),
                //Ignore aria-label errors since they still render and are read correctly by screen readers
                array('errorCode' => 'aria-label', 'regex' => '/aria-label/'),
            ),
            'HTML5' => array(
                //The autocorrect attribute is not standard
                array('errorCode' => 'E620', 'regex' => "/autocorrect/i"),
            ),
        );

        //No errors found
        $matches = array();
        if(preg_match("/total errors found.*?([0-9]+)/i", $this->validationOutput, $matches)
            && ($errorCount = $matches[1]) <= 0) {
            return false;
        }

        //Found errors, check if there are still errors after suppressing the above messages
        if($messages = $suppressedMessages[$this->docType]) {
            $validatorLines = explode("\n", $this->validationOutput);
            foreach($validatorLines as $line) {
                foreach($messages as $message) {
                    if(Text::stringContains($line, $message['errorCode'])
                        && preg_match($message['regex'], $line) && (--$errorCount) <= 0) {
                        return false;
                    }
                }
            }
        }
        return true;
    }

    function validationErrors () {
        preg_match_all('@<span class="problem">(.*?)</span>@', $this->validationOutput, $matches);
        if ($matches) {
            $errors = count($matches[1]) . ' validation errors: ' .
                htmlspecialchars_decode(implode(' ', $matches[1]));
        }
        return $errors;
    }
}
