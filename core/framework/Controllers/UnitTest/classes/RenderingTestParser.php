<?

use RightNow\Utils\Text;

/**
 * Parses out:
 * - URL segment keywords in the current URL
 * - .test YAML-like data format
 * - Arrays are done in JSON style to allow for nested structures.
 */
class RenderingTestParser {
    /**
     * YAML keys for rendering tests.
     * @var array
     */
    private static $testKeywords = array(
        // required: either a snippet of HTML or a full HTML document comprising the basis for the unit test page.
        'input',
        // The document containing unit tests for a widget from "input:" located in the same folder as the validation test.
        'jstestfile',
        // The exact document that was returned by the test page created from "input:". Defaults to an empty document.
        'output',
        // Specifies the template that partial HTML document inputs will use. Options are default (HTML5 document using standard theme) and mobile (simple HTML5 document using mobile theme). If not specified default is used. If not specified and Input contains an &lt;html&gt; tag, no template will be used.
        'template',
        // Parameters that will be appended to the test page\'s URL when requested. Must begin with a "/". No default.
        'urlparameters',
        // Used to remove text for the actual and expected values to gloss over details which differ with each run or based on the site. This should be an expession that can be passed to preg_match. It must include the beginning and ending delimiters. <br>Example: <pre>@ignore.*this@</pre>',
        'filter',
        // The replacement pattern or string used with the filter keyword',
        'replace',
        // Specify the contact login that will be used to request the page. Assumes the contact has an empty password.
        'contact',
        // Removes any surrounding content from the input snippet to make the file comparisons cleaner. Valid values are "true" and "false".  Defaults to "true".
        'removecontent',
        // Indicates that the widget\'s output should be checked for HTML5 validity. Valid values are "true" and "false".  Defaults to "true".
        'validate',
        // Specifies one or more config values that should be set temporarily for the test. Values should be a comma separated list of "slot name|value" tuples.
        'config',
        // Extra POST data to include in the request.
        'post',
        // Create a form token and pass along with the other POST data. Assumes that the test does not use a logged in user.
        'postFTok',
        // Validation token for Basic form submits. The current interface will replace the interface value in the validationToken. Use 'true' for anonymous or provide the c_id of the logged in user.
        'validationToken',
        // Extra cookie data to include in the request.
        'cookies',
        // Set to true to indicate that it is expected that the page will be forced to redirect to use HTTPS.
        'redirectToHttps',
        // Value to pass directly into test_sql_exec_direct prior to the widget being rendered. You should likely revert this in the "postSql" directive so it does not affect other tests',
        'preSql',
        // Value to pass directly into test_sql_exec_direct after the widget has rendered.
        'postSql',
        // Any session keys and values to be used to request the page. Example: `session: {"answersViewed": 2, "numberOfSearches": 1, "urlParameters":[{"a_id":52},{"a_id":54}], "ptaUsed": true, "previouslySeenEmail": "bananas@banana.ape"}`
        'session',
        // Fixture(s) (defined in UnitTest/fixtures/) to perform any necessary setup, data substitution, and tear down
        'fixture',
        'fixtures',
    );

    function __construct(array $args) {
        $this->args = $args;
    }

    /**
     * Parses keyword options out of the current url.
     * @return array Associative array of keyword options
     */
    function parseArguments() {
        $CI = get_instance();
        $options = array();
        $segments = array_slice($CI->uri->segment_array(), $CI->config->item('parm_segment') - 1);
        $arguments = array_keys($this->args);

        //Locate the functions in the URL setting state for any that are encountered.
        while ($argument = array_shift($segments)) {
            if (in_array($argument, $arguments)) {
                // Args are either boolean type or arg type.
                if ($this->args[$argument]['type'] === 'bool') {
                    $options[$argument] = true;
                }
                else {
                    $options[$argument] = array_shift($segments);
                }
            }
            else {
                array_unshift($segments, $argument);
                break;
            }
        }
        $options['segments'] = $segments;

        return $options;
    }

    /**
     * Parses out the `name|value,name|value` config line in test files.
     * @param  string $configSetterLine line from the test file
     * @return array key value tuples
     */
    function parseConfigSetterLine($configSetterLine) {
        $tuples = array_filter(array_map('trim', explode(',', $configSetterLine)), 'strlen');
        foreach ($tuples as &$tuple) {
            $tuple = array_map('trim', explode('|', $tuple));
        }
        return $tuples;
    }

    /**
     *  Parse the <test_name>.test file from a widgets/standard/type/widgetName/tests directory and
     *  ensure that the file contains the required YAML-like keys.
     *  @throws \Exception If $inputFile is not readable
     */
    function parseTest($inputFile) {
        if (!is_readable($inputFile)) {
            throw new \Exception("I can't read $inputFile.");
        }
        $sections = $this->extractSections($inputFile);
        $sections = $this->cleanSections($sections);
        $sections['removecontent'] = !isset($sections['removecontent'])
            || $sections['removecontent'] !== 'false' ? 'true' : 'false';

        return $sections;
    }

    private function cleanSections($sections) {
        foreach ($sections as $sectionName => $sectionLines) {
            if (array_values($sectionLines) === $sectionLines) {
                // If the value is a string and not an array-like structure, then collapse all lines and trim it up.
                $sections[$sectionName] = trim(implode('', $sectionLines));
            }
        }

        return $sections;
    }

    private function extractSections($inputFile) {
        $sections = array();
        $currentKeyword = false;

        foreach (file($inputFile) as $line) {
            if (preg_match($this->getTestKeywordRegex(), $line, $matches)) {
                $currentKeyword = strtolower($matches[1]);
                $sections[$currentKeyword] = array($matches[2]);

                $arrayLike = trim($matches[2]);
                if ((Text::beginsWith($arrayLike, '[') && Text::endsWith($arrayLike, ']')) ||
                    (Text::beginsWith($arrayLike, '{') && Text::endsWith($arrayLike, '}'))) {
                    if($formattedArray = $this->decodeJson($arrayLike)) {
                        $sections[$currentKeyword] = $formattedArray;
                    }
                }
            }
            else if (!$currentKeyword) {
                throw new \Exception("$inputFile is not well formed.  It needs to contain a section declaration before any data appears.");
            }
            else {
                $sections[$currentKeyword] []= $line;
            }
        }

        return $sections;
    }

    function decodeJson($string) {
         $decodedString = json_decode($string, true);
         return (json_last_error() === JSON_ERROR_NONE ? $decodedString : false);
    }

    private function getTestKeywordRegex() {
        static $regex = false;
        if (!$regex) {
            $keywords = implode('|', self::$testKeywords);
            $regex = "@^($keywords):\\s*(.*)$@i";
        }
        return $regex;
    }
}
