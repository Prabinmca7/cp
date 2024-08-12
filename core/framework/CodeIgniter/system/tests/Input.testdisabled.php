<?php

use RightNow\UnitTest\Helper;

Helper::loadTestedFile(__DIR__ . '/CoreCodeIgniter.test.php');


class CIInput extends CPTestCase {
    public $testingClass = 'Input';

    function __construct() {
        $this->input = new \CI_Input;
    }

    function setUp() {
        parent::setUp();

        $this->originalPost = $_POST;
        $this->originalServer = $_SERVER;
        $this->originalGet = $_GET;
    }

    function tearDown() {
        parent::tearDown();

        $_GET = $this->originalGet;
        $_SERVER = $this->originalServer;
        $_POST = $this->originalPost;
    }

    function testRequestForPost() {
        $_SERVER['REQUEST_METHOD'] = 'POST';

        $this->assertFalse($this->input->request('bananas'));

        $_POST['bananas'] = null;
        $this->assertFalse($this->input->request('bananas'));

        $_POST['bananas'] = false;
        $this->assertFalse($this->input->request('bananas'));

        $_POST['bananas'] = 0;
        $this->assertIdentical('0', $this->input->request('bananas'));

        $_POST['bananas'] = '';
        $this->assertIdentical('', $this->input->request('bananas'));

        $_POST['bananas'] = 'remote';
        $this->assertIdentical('remote', $this->input->request('bananas'));

        $_POST['bananas'] = '<">';
        $this->assertIdentical('<&quot;>', $this->input->request('bananas'));
        $this->assertIdentical('<">', $this->input->request('bananas', false));
    }

    function testRequestForGet() {
        $_SERVER['REQUEST_METHOD'] = 'GET';

        $this->assertFalse($this->input->request('bananas'));

        $this->addUrlParameters(array('bananas' => ''));
        $this->assertFalse($this->input->request('bananas'));

        $this->addUrlParameters(array('bananas' => '0'));
        $this->assertIdentical('0', $this->input->request('bananas'));

        $this->addUrlParameters(array('bananas' => 'false'));
        $this->assertIdentical('false', $this->input->request('bananas'));

        $this->addUrlParameters(array('bananas' => 'null'));
        $this->assertIdentical('null', $this->input->request('bananas'));

        $this->addUrlParameters(array('bananas' => 'azamane'));
        $this->assertIdentical('azamane', $this->input->request('bananas'));

        $this->addUrlParameters(array('bananas' => 'azamane'));
        $this->assertIdentical('azamane', $this->input->request('bananas'));

        $this->addUrlParameters(array('bananas' => '<&quot;>'));
        $this->assertIdentical('<&quot;>', $this->input->request('bananas'));
        $this->assertIdentical('<">', $this->input->request('bananas', false));

        $this->restoreUrlParameters();
    }

    function testRequestPostTakesPrecedenceOverGet() {
        $_SERVER['REQUEST_METHOD'] = 'POST';

        $_POST['bananas'] = 'remote';
        $this->addUrlParameters(array('bananas' => 'azamane'));

        $this->assertIdentical('remote', $this->input->request('bananas'));

        $this->restoreUrlParameters();
    }

    function testXssClean() {
        $this->assertIdentical('javascript ', $this->input->xss_clean('j a v a s c r i p t '));
        $this->assertIdentical('dealer to', $this->input->xss_clean('dealer to'));
    }

    function testHtmlEntityDecode () {
        $origHex = "&#x40";
        $origDec = '&#64';
        $this->assertIdentical("@", $this->input->_html_entity_decode($origHex));
        $this->assertIdentical("@", $this->input->_html_entity_decode($origDec));
        $origHex = "&#x41";
        $origDec = '&#65';
        $this->assertIdentical("A", $this->input->_html_entity_decode($origHex));
        $this->assertIdentical("A", $this->input->_html_entity_decode($origDec));
        $origHex = "&#x30";
        $origDec = '&#48';
        $this->assertIdentical("0", $this->input->_html_entity_decode($origHex));
        $this->assertIdentical("0", $this->input->_html_entity_decode($origDec));
    }
}
