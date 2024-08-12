<?php

use RightNow\Utils\Chat,
    RightNow\Api,
    RightNow\Connect\v1_4 as ConnectPHP;

\RightNow\UnitTest\Helper::loadTestedFile(__FILE__);

class ChatTest extends CPTestCase {
    private $urlRequest;

    function __construct()
    {
        parent::__construct();
        $this->urlRequest = '/ci/unitTest/wgetRecipient/invokeTestMethod/' . urlencode(__FILE__) . '/' . __CLASS__ . '/';
    }

    function testIsChatAvailable() {
        $output = $this->makeRequest($this->urlRequest . 'isChatAvailable');
        $this->assertIdentical('true', $output);

        $this->makeRequest($this->urlRequest . 'setChatHours');

        $output = $this->makeRequest($this->urlRequest . 'isChatAvailable');
        $this->assertIdentical('false', $output);

        $this->makeRequest($this->urlRequest . 'resetChatHours');
    }

    static function isChatAvailable() {
        echo var_export(Chat::isChatAvailable(), true);
    }

    static function setChatHours() {
        // set the available times to be 12 hours from now, so we know that chat should not be available
        $hour = date("H", time() + 43200);
        Api::test_sql_exec_direct("UPDATE rr_intervals SET tm_start = '2000-01-01 $hour:00:00', tm_end = '2000-01-01 $hour:01:00' WHERE rr_id = 2");
        // force a commit so it doesn't get rolled back when we leave the function
        ConnectPHP\ConnectAPI::commit();
    }

    static function resetChatHours() {
        Api::test_sql_exec_direct("UPDATE rr_intervals SET tm_start = '2000-01-01 00:00:00', tm_end = '2000-01-02 00:00:00' WHERE rr_id = 2");
        // force a commit so it doesn't get rolled back when we leave the function
        ConnectPHP\ConnectAPI::commit();
    }
}
