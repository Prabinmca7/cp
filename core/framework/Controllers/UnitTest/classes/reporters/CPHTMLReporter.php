<?

use RightNow\Utils\Text;

class CPHTMLReporter extends HTMLReporter {
    public $contentType = 'html';

    function __construct (array $files = array(), $baseUri = '') {
        ob_start();
        parent::__construct('UTF-8');
        $this->testFilesRun = $files;
        $this->baseUri = $baseUri;
    }

    function paintFooter ($testName) {
        parent::paintFooter($testName);
        echo $this->appendContent(ob_get_clean());
    }

    function paintHTML ($message) {
        // Default painter encodes the content.
        echo $message;
    }

    private static function append ($content, $toAppend) {
        $bodyClosePosition = strpos($content, '</body>') ?: strlen($content);
        $before = substr($content, 0, $bodyClosePosition);
        $end = substr($content, $bodyClosePosition);

        return "$before\n$toAppend\n$end";
    }

    private function appendContent ($content) {
        if (Text::stringContains($content, 'rn_verboseContent')) {
            $appendedContent = <<<DOC
                <br><a href='javascript:void(0);' onclick='toggleVerboseContent();'>Toggle echo'ed output</a>
                <style>
                .rn_Hidden {
                    display:none;
                }
                </style>
                <script>
                function toggleVerboseContent() {
                    var elementsToClick = document.getElementsByClassName('rn_verboseContent'),
                        i, length = elementsToClick.length;
                    for (i=0; i < length; i++) {
                        elementsToClick[i].classList.toggle('rn_Hidden');
                    }
                }
                </script>
DOC;
        }
        else {
            $appendedContent = '';
        }

        if ($this->testFilesRun) {
            $appendedContent .= "<br>Test files ran:<br><ul>\n";
            foreach ($this->testFilesRun as $path) {
                $appendedContent .= "<li><a href='//{$this->baseUri}/test/{$path}' target='_blank'>$path</a></li>\n";
            }
            $appendedContent .= "</ul>\n<br>\n";
        }

        if ($appendedContent) {
            return self::append($content, $appendedContent);
        }
        return $content;
    }
}
