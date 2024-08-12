<?php

require_once(dirname(__FILE__) . '/../reporter.php');

/**
 * Print 'Pass' message for every passing test.
 * http://simpletest.sourceforge.net/en/display_subclass_tutorial.html
 */
class ShowPasses extends HtmlReporter {
    function __construct() {
        parent::__construct();
        $this->testsRun = array();
    }

    function paintPass($message) {
        parent::paintPass($message);
        $testList = $this->getTestList();
        array_shift($testList);
        $breadcrumb = implode('->', $testList);
        $breadcrumb = $this->getSubstringAfter($breadcrumb, 'cp/core/framework/', $breadcrumb);
        if (!in_array($breadcrumb, $this->testsRun)) {
            $this->testsRun[] = $breadcrumb;
            print "<span class=\"pass\">Pass</span>: $breadcrumb<br />\n";
        }
        print("<script>window.scrollTo(0, document.body.scrollHeight);</script>");
    }

    function getSubstringAfter($haystack, $needle, $default = false) {
        $index = strpos($haystack, $needle);
        if ($index === false) {
            return $default;
        }
        return substr($haystack, $index + strlen($needle));
    }
}
