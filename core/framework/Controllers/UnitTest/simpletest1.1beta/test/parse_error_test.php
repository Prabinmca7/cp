<?php
// $Id: parse_error_test.php,v 1.1 2012/05/04 00:16:23 jwatson Exp $
require_once('../unit_tester.php');
require_once('../reporter.php');

$test = new TestSuite('This should fail');
$test->addFile('test_with_parse_error.php');
$test->run(new HtmlReporter());
?>