<?

use RightNow\Api,
    RightNow\Utils\Text,
    RightNow\Utils\Config,
    RightNow\Utils\FileSystem,
    RightNow\UnitTest\Helper;

class RenderingTestCase extends CPTestCase {
    public static $docTypes = array(
        'basic'   => 'XHTML Basic 1.1',
        'default' => 'HTML5',
    );
    // Dynamic content that will differ between test runs, so needs to be stripped out.
    private static $filtered = array(
        'euf/core/js/\d+\.\d+/',
        'generated/optimized/0000000000/',
        '\.[a-f0-9]{32}\.js',
        '^l\(\'//\'[^\)]+\);',
        '/api/e/[a-z0-9]+/e.[js|css]+(\?[^>]*)?',
        '_rnq\.push[^;]*;',
        '<base[^>]*>',
        '"(maUrl|session|sessionID|contactToken|token|baseUrl|baseurl|DB_NAME)":"[^"]*"',
        '/euf/generated/optimized/[^/]+?/',
        'RightNow[.]Url[.]setSession[(]\'[^\']*\'[)];',
        '[\\\\]?/session[\\\\]?/[A-Za-z0-9=]{10,50}',
        'https?://[a-zA-z0-9_.-]+/?',
        'rnengage.qaload.lan/[a-zA-Z._/-]+\.js',
        'Interface[.]set(Message|Config)base[(].*[)]',
        '^W\\[c\\]\\(\\{.*?\\); *$',
        '<script[^>]*src[^>]*[.]test[.]js[^>]*></script>',
        'api/e/[a-zA-Z0-9]+/e\.js',
    );

    // Properties populated via constructor:
    public $output = '';
    public $testPath = '';
    public $statusCode = 404;
    public $filterRegex = '';
    public $replaceDateRegex = '@\d{2}/\d{2}/\d{4} \d{2}:\d{2} (A|P)M@';
    public $fullTestPath = '';
    public $outputHeaders = '';
    // Associative array whose keys are the YAML keys in the .test file.
    public $originalTestData = array();

    // Property assigned by validator:
    public $validationResult;

    function __construct(array $props = array()) {
        parent::__construct($props['label']);

        foreach ($props as $name => $val) {
            $this->{$name} = $val;
        }

        $this->filterRegex = '@' . Config::getConfig(OE_WEB_SERVER) . '|' . implode('|', self::$filtered) . '@m';
    }

    function testThereIsAtLeastOneTest () {
        $this->assertIdentical(1, preg_match('@^(/.+)/tests/.+\.test$@', $this->fullTestPath), "Must have at least one test");
    }

    function testPageWasRetrieved () {
        $this->assertTrue(strlen($this->outputHeaders) && strlen($this->output) > 5);
        $this->assertIdentical(200, $this->statusCode);
    }

    function testRedirect () {
        $widgetName = Text::getSubstringBefore($this->testPath, '/tests/');
        $testFile = Text::getSubstringAfter($this->testPath, 'tests/');
        $redirectGiven = Text::stringContains($this->outputHeaders, 'Location: https://' . Config::getConfig(OE_WEB_SERVER) . '/');
        $redirectToHttps = $this->originalTestData['redirecttohttps'];

        if($redirectToHttps === 'true' && !$redirectGiven) {
            $this->fail("A redirect to https was expected, but was not given for $widgetName/$testFile");
        }
        else if($redirectToHttps !== 'true' && $redirectGiven) {
            $this->fail("A redirect to https was not expected, but was given for $widgetName/$testFile");
        }
        else {
            $this->pass();
        }
    }

    function testOutputMatchesExpected () {
        $actual = $this->filteredOutput();
        // The expected output saved in the test file has already been filtered.
        $expected = $this->originalTestData['output'];
        if ($this->variables) {
            $expected = Helper::processFixtureData($this->variables, $expected);
        }

        $diffOutput = Helper::diff(Helper::trimLines($expected), Helper::trimLines($actual), array('type' => $this->reporter->contentType));

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

    function testOutputValidates () {
        if ($this->validationResult === 'pass') {
            $this->pass();
            return;
        }

        $this->assertTrue(is_object($this->validationResult), "HTML Validation did not complete properly");
        $this->assertFalse($this->validationResult->hasValidationErrors(), $this->validationResult->validationErrors());
    }

    function filteredOutput () {
        $output = $this->output;
        $regex = $this->originalTestData['filter'];
        $replace = $this->originalTestData['replace'];
        $removeContent = $this->originalTestData['removecontent'];

        if ($removeContent !== 'false') {
            $output = Text::getSubstringAfter(Text::getSubstringBefore($output, "<!-- ORACLERIGHTNOWREMOVECONTENTEND -->", $output), "<!-- ORACLERIGHTNOWREMOVECONTENTSTART -->", $output);
        }

        $output = preg_replace($this->filterRegex, '', trim($output));
        $output = preg_replace($this->replaceDateRegex, 'TIMEVALUE', $output);
        return ($regex) ? preg_replace($regex, $replace ?: '', $output) : $output;
    }

    private function errorRetrievingPage () {
        return <<<DOC
        %s
Failed to retrieve the page at {$this->testUrl}.
Response headers: {$this->outputHeaders}
Response body: {$this->output}
DOC;
    }
}
