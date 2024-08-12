<?php
RightNow\UnitTest\Helper::loadTestedFile(__FILE__);

use RightNow\Api,
    RightNow\Controllers\UnitTest,
    RightNow\Internal\Sql\Clickstream as Sql;

class ClickstreamModelTest extends CPTestCase {
    public $testingClass = '\RightNow\Models\Clickstream';

    function bigString($bounds) {
        return str_repeat('∞', $bounds);
    }

    function testInsertAction() {
        // Base
        list(
            $class,
            $productionMode
        ) = $this->reflect('productionMode');

        $logger = ClickstreamDataLogger;
        $model = new \RightNow\Models\Clickstream($logger);
        $productionMode->setValue($model, true); // Original unit tests pass invalid data so need to be in production mode to bypass edits
        $model->insertAction('', 0, 'banana', 'bananas', '', '', '');
        $result = $logger::$calledWith;
        $this->assertIdentical(DQA_CLICKSTREAM, $result[0]);
        $this->assertNull($result[1]['cid']);
        $this->assertNull($result[1]['referrer']);
        $this->assertNull($result[1]['psid']);
        $this->assertIdentical('', $result[1]['sid']);
        $this->assertIdentical('banana', $result[1]['app']);
        $this->assertIdentical('bananas', $result[1]['action']);
        $this->assertNull($result[1]['c1']);
        $this->assertNull($result[1]['c2']);
        $this->assertNull($result[1]['c3']);
        $this->assertIsA($result[1]['ts'], 'int');

        // Truncation
        $model->insertAction('banana', '0', 'app', $this->bigString(Sql::DQA_CONTEXT_FIELD_SIZE + 50), $this->bigString(Sql::DQA_CONTEXT_FIELD_SIZE + 50), $this->bigString(Sql::DQA_CONTEXT_FIELD_SIZE + 50), $this->bigString(Sql::DQA_CONTEXT_FIELD_SIZE + 50));
        $result = $logger::$calledWith;
        $this->assertIdentical(DQA_CLICKSTREAM, $result[0]);
        $this->assertNull($result[1]['cid']);
        $this->assertNull($result[1]['referrer']);
        $this->assertNull($result[1]['psid']);
        $this->assertSame('banana', $result[1]['sid']);
        $this->assertSame('app', $result[1]['app']);
        $this->assertSame(Sql::DQA_CONTEXT_FIELD_SIZE, Api::utf8_char_len($result[1]['action']));
        $this->assertSame(Sql::DQA_CONTEXT_FIELD_SIZE, Api::utf8_char_len($result[1]['c1']));
        $this->assertSame(Sql::DQA_CONTEXT_FIELD_SIZE, Api::utf8_char_len($result[1]['c2']));
        $this->assertSame(Sql::DQA_CONTEXT_FIELD_SIZE, Api::utf8_char_len($result[1]['c3']));
        $this->assertIsA($result[1]['ts'], 'int');

        //@@@ QA 140521-000082 Set to non-production mode to test development and staging edits

        $productionMode->setValue($model, false);

        try{
            $model->insertAction('', 0, '', 'bananas', '', '', '');
            $this->fail('Missing session ID not caught.');
        }
        catch(\Exception $e){
            $this->pass();
        }
        try{
            $model->insertAction('bananarama', 0, '', 'bananas', '', '', '');
            $this->fail('Session ID longer than 8 bytes not caught.');
        }
        catch(\Exception $e){
            $this->pass();
        }
        try{
            $model->insertAction('banana', 'banana', '', 'bananas', '', '', '');
            $this->fail('Non integer contact ID "banana" not caught.');
        }
        catch(\Exception $e){
            $this->pass();
        }
        try{
            $model->insertAction('banana', -1, '', 'bananas', '', '', '');
            $this->fail('Negative contact ID -1 not caught.');
        }
        catch(\Exception $e){
            $this->pass();
        }
        try{
            $model->insertAction('banana', 0,  'banana', 'bananas', '', '', '');
            $this->fail('Non integer app not caught.');
        }
        catch(\Exception $e){
            $this->pass();
        }
        try{
            $model->insertAction('banana', 0,  -1, 'bananas', '', '', '');
            $this->fail('Negative app not caught.');
        }
        catch(\Exception $e){
            $this->pass();
        }
        try{
            $model->insertAction('banana', 0,  200, 'bananas', '', '', '');
            $this->fail('app greater than max tiny int not caught.');
        }
        catch(\Exception $e){
            $this->pass();
        }
        try{
            $model->insertAction('4F8E876E', 10100,  10, 'bananas', '', '', '');
            $this->pass();
        }
        catch(\Exception $e){
            $this->fail('Valid entries caused exception in insertAction');
        }
        try{
            $model->insertAction('4F8E876E', INT_NULL,  INT_NOT_SET, 'bananas', '', '', '');
            $this->pass();
        }
        catch(\Exception $e){
            $this->fail('Valid entries caused exception in insertAction');
        }
        $productionMode->setValue($model, true);
    }

    function testInsertSolvedCount() {
        $logger = ClickstreamDataLogger;
        $model = new \RightNow\Models\Clickstream($logger);

        $model->insertSolvedCount('', 'rating');
        $result = $logger::$calledWith;
        $this->assertIdentical(DQA_SOLVED_COUNT, $result[0]);
        $this->assertIdentical(0, $result[1]['a_id']);
        $this->assertSame('rating', $result[1]['rating']);
        $this->assertIsA($result[1]['last_access'], 'int');

        $model->insertSolvedCount('27', 56);
        $result = $logger::$calledWith;
        $this->assertIdentical(DQA_SOLVED_COUNT, $result[0]);
        $this->assertIdentical(27, $result[1]['a_id']);
        $this->assertIdentical(56, $result[1]['rating']);
        $this->assertIsA($result[1]['last_access'], 'int');
    }

    function testInsertLink() {
        $logger = ClickstreamDataLogger;
        $logger::$calledWith = null;
        $model = new \RightNow\Models\Clickstream($logger);

        $model->insertLink('', '');
        $this->assertNull($logger::$calledWith);
        $model->insertLink('0', '0');
        $this->assertNull($logger::$calledWith);
        $model->insertLink(-1, -1);
        $this->assertNull($logger::$calledWith);
        $model->insertLink('57', '0');
        $this->assertNull($logger::$calledWith);
        $model->insertLink(57, '0');
        $this->assertNull($logger::$calledWith);
        $model->insertLink(array(), 57);
        $this->assertNull($logger::$calledWith);

        $model->insertLink('54', '67');
        $result = $logger::$calledWith;
        $this->assertIdentical(DQA_LINKS, $result[0]);
        $this->assertIdentical(54, $result[1]['from']);
        $this->assertIdentical(67, $result[1]['to']);
        $this->assertIsA($result[1]['access_time'], 'int');
    }

    function testInsertQuery() {
        list(
            $class,
            $productionMode,
            $insertQuery,
        ) = $this->reflect('productionMode', 'method:insertQuery');

        $logger = ClickstreamDataLogger;
        $logger::$calledWith = null;
        $model = $class->newInstanceArgs(array($logger));

        // Not production mode
        $insertQuery->invoke($model, 'banana', (object) array('foo' => 'bar'));
        $this->assertNull($logger::$calledWith);

        // Production mode
        $productionMode->setValue($model, true);
        $insertQuery->invoke($model, 'banana', (object) array('foo' => 'bar'));
        $result = $logger::$calledWith;
        $this->assertSame('banana', $result[0]);
        $this->assertSame('bar', $result[1]->foo);
        $this->assertIsA($result[1]->ts, 'int');
    }

    function testInsertWidgetStats() {
        list(
            $class,
            $productionMode,
            $insertWidgetStats,
        ) = $this->reflect('productionMode', 'method:insertWidgetStats');

        $logger = ClickstreamDataLogger;
        $logger::$calledWith = null;
        $model = $class->newInstanceArgs(array($logger));

        // Not production mode
        $insertWidgetStats->invoke($model, 'banana', (object) array('foo' => 'bar'));
        $this->assertNull($logger::$calledWith);

        // Production mode
        $productionMode->setValue($model, true);
        $insertWidgetStats->invoke($model, 'banana', (object) array('foo' => 'bar'));
        $result = $logger::$calledWith;
        $this->assertSame('banana', $result[0]);
        $this->assertSame('bar', $result[1]->foo);
        $this->assertIsA($result[1]->ts, 'int');
    }

    function testInsertMailTransaction() {
        $logger = ClickstreamDataLogger;
        $logger::$calledWith = null;
        $model = new \RightNow\Models\Clickstream($logger);

        $model->insertMailTransaction('', 'banana', '', '');
        $result = $logger::$calledWith;
        $this->assertNULL($result[0]);

        //@@@ QA 130308-000133 Do not insert log entry if track parameter is corrupted
        $model->insertMailTransaction('AvMG', \RightNow\Utils\Config::getConfig(CP_ANSWERS_DETAIL_URL), 27, 54);
        $this->assertNULL($result[0]);

        //@@@ QA 240309-000000 cs_session_id property is not included in transaction if its session id is null
        $cs_session_id = null;
        $model->insertMailTransaction('AvMG~wqwDv8S~Rb~GrIe~yKZLv8KMi75Mv8~~zj~PP~2', \RightNow\Utils\Config::getConfig(CP_ANSWERS_DETAIL_URL), $cs_session_id, 54);
        $result = $logger::$calledWith;
        $this->assertFalse(array_key_exists('cs_session_id', $result[1]));

        $model->insertMailTransaction('AvMG~wqwDv8S~Rb~GrIe~yKZLv8KMi75Mv8~~zj~PP~2', \RightNow\Utils\Config::getConfig(CP_ANSWERS_DETAIL_URL), 27, 54);
        $result = $logger::$calledWith;

        $this->assertIdentical(DQA_MESSAGE_TRANS, $result[0]);
        $this->assertIdentical(MA_TRANS_CLICK_ANSWER, $result[1]['trans_type']);
        $this->assertIsA($result[1]['created'], 'int');
        $this->assertIdentical(1254, $result[1]['c_id']);
        $this->assertIdentical(1, $result[1]['email_type']);
        $this->assertIdentical(166, $result[1]['thread_id']);
        $this->assertIdentical(51, $result[1]['doc_id']);
        $this->assertIdentical(54, $result[1]['a_id']);
        $this->assertIdentical('27', $result[1]['cs_session_id']);
    }

    function testInsertSpider() {
        $logger = ClickstreamDataLogger;
        $logger::$calledWith = null;
        $model = new \RightNow\Models\Clickstream($logger);
        $model->insertSpider('session', 'ip', '    ', $this->bigString(Sql::DQA_CONTEXT_FIELD_SIZE + 30), 'page type', 'spider type');
        $result = $logger::$calledWith;
        $this->assertIdentical(DQA_SPIDER, $result[0]);
        $this->assertSame('session', $result[1]['sid']);
        $this->assertSame('ip', $result[1]['ip']);
        $this->assertSame('page type', $result[1]['page_type']);
        $this->assertSame('spider type', $result[1]['spider_type']);
        $this->assertSame('Empty', $result[1]['ua']);
        $this->assertSame(Sql::DQA_CONTEXT_FIELD_SIZE, API::utf8_char_len($result[1]['page']));

        // UA is truncated
        $model->insertSpider('session', 'ip', $this->bigString(Sql::DQA_USER_AGENT_FIELD_SIZE + 30), 'page', 'page type', 'spider type');
        $result = $logger::$calledWith;
        $this->assertSame(Sql::DQA_USER_AGENT_FIELD_SIZE, API::utf8_char_len($result[1]['ua']));
    }

    function testInsertResultList() {
        list(
            $class,
            $clickstreamEnabled,
            $method
        ) = $this->reflect('clickstreamEnabled', 'method:insertResultList');

        $logger = ClickstreamDataLogger;
        $logger::$calledWith = null;
        $model = $class->newInstanceArgs(array($logger));

        // Immediate return conditions
        $method->invoke($model, array());
        $this->assertNull($logger::$calledWith);
        $method->invoke($model, array('list' => 'banana,foo'));
        $this->assertNull($logger::$calledWith);

        // Didn't immediately return, but stem and list are empty
        $clickstreamEnabled->setValue($model, true);
        $method->invoke($model, array('list' => 'banana,foo'));
        $this->assertNull($logger::$calledWith);

        $method->invoke($model, array('list' => array('banana', 'foo'), 'page' => '56'));
        $result = $logger::$calledWith;
        $this->assertIdentical(DQA_CLICKSTREAM, $result[0]);
        $this->assertIsA($result[1]['sid'], 'string');
        $this->assertNull($result[1]['cid']);
        $this->assertNull($result[1]['c1']);
        $this->assertIdentical(CS_APP_EU, $result[1]['app']);
        $this->assertIdentical('56', $result[1]['c3']);
        $this->assertSame('/ResultList', $result[1]['action']);
        $this->assertIdentical('banana,foo', $result[1]['c2']);

        $logger::$calledWith = null;
        //rnkl_stem is weird. It uses a process cache to return the value, but clears out that cache after a call. If we didn't
        //call it here, the next time it was invoked, it would return 'PHONE' which could cause other tests to fail. Calling it here
        //with a fake value will clear that cache.
        \RightNow\Internal\Api::rnkl_stem('fake value', 0);
        $method->invoke($model, array('sa' => true, 'list' => array('banana', 'foo'), 'term' => 'monkeys', 'page' => '56'));
        $result = $logger::$calledWith;
        $this->assertIdentical(DQA_CLICKSTREAM, $result[0]);
        $this->assertIsA($result[1]['sid'], 'string');
        $this->assertNull($result[1]['cid']);
        $this->assertIdentical(CS_APP_EU, $result[1]['app']);
        $this->assertIdentical('56', $result[1]['c3']);
        $this->assertIdentical('MONKEY', $result[1]['c1']);
        $this->assertIdentical('banana,foo', $result[1]['c2']);
        $this->assertSame('/SAResultList', $result[1]['action']);
    }

    function testSetClickstreamEnabled() {
        list(
            $class,
            $clickstreamEnabled,
            $method
        ) = $this->reflect('clickstreamEnabled', 'method:setClickstreamEnabled');

        $model = $class->newInstance();
        $clickstreamEnabled->setValue($model, null);
        $method->invoke($model, true);
        $this->assertTrue($clickstreamEnabled->getValue($model));

        $clickstreamEnabled->setValue($model, true);
        try {
            $method->invoke($model, false);
            $this->fail("Exception should've been thrown and was not");
        }
        catch (\Exception $e) {
            $this->pass();
        }
    }

    function testInsertKeywords() {
        $logger = ClickstreamDataLogger;
        $logger::$calledWith = null;
        $model = new \RightNow\Models\Clickstream($logger);
        $model->insertKeywords(array());
        $result = $logger::$calledWith;
        $this->assertNull($result);

        $logger = ClickstreamDataLogger;
        $logger::$calledWith = null;
        $model = new \RightNow\Models\Clickstream($logger);
        $model->insertKeywords(array('term' => 'banana', 'list' => array(), 'total' => 'total', 'source' => 'source'));
        $result = $logger::$calledWith;
        $this->assertIdentical(DQA_KEYWORD_SEARCHES, $result[0]);
        $this->assertIdentical('banana', $result[1]['query']);
        $this->assertIdentical('BANANA', $result[1]['stem']);
        $this->assertIdentical(1, $result[1]['word_count']);
        $this->assertNull($result[1]['result_list']);
        $this->assertSame('total', $result[1]['results']);
        $this->assertSame('source', $result[1]['source']);
        $this->assertIsA($result[1]['ts'], 'int');

        $model->insertKeywords(array('term' => 'banana', 'list' => array('foo', 'bar', 'foo')));
        $result = $logger::$calledWith;
        $this->assertIdentical(DQA_KEYWORD_SEARCHES, $result[0]);
        $this->assertIdentical('banana', $result[1]['query']);
        $this->assertIdentical('BANANA', $result[1]['stem']);
        $this->assertIdentical(1, $result[1]['word_count']);
        $this->assertSame('foo,bar,', $result[1]['result_list']);
        $this->assertNull($result[1]['results']);
        $this->assertNull($result[1]['source']);
        $this->assertIsA($result[1]['ts'], 'int');
    }

    function testInsertKeywordSearch() {
        $logger = ClickstreamDataLogger;
        $logger::$calledWith = null;
        $model = new \RightNow\Models\Clickstream($logger);
        $model->insertKeywordSearch('', '', '', '');
        $result = $logger::$calledWith;
        $this->assertNull($result);

        $model->insertKeywordSearch('phone', 'results', 'banana', 'source');
        $result = $logger::$calledWith;
        $this->assertIdentical(DQA_KEYWORD_SEARCHES, $result[0]);
        $this->assertIdentical('phone', $result[1]['query']);
        $this->assertIdentical('PHONE', $result[1]['stem']);
        $this->assertIdentical(1, $result[1]['word_count']);
        $this->assertSame('banana', $result[1]['result_list']);
        $this->assertSame('results', $result[1]['results']);
        $this->assertSame('source', $result[1]['source']);
        $this->assertIsA($result[1]['ts'], 'int');

        // Invalid query
        $model->insertKeywordSearch('∑', 'results', 'banana', 'source');
        $result = $logger::$calledWith;
        $this->assertIdentical(DQA_KEYWORD_SEARCHES, $result[0]);
        $this->assertIdentical('∑', $result[1]['query']);
        $this->assertIdentical($model::DQA_INVALID_QUERY, $result[1]['stem']);
        $this->assertIdentical(0, $result[1]['word_count']);
        $this->assertSame('banana', $result[1]['result_list']);
        $this->assertSame('results', $result[1]['results']);
        $this->assertSame('source', $result[1]['source']);
        $this->assertIsA($result[1]['ts'], 'int');

        // Truncation
        $model->insertKeywordSearch(str_repeat('phone ', Sql::DQA_CONTEXT_FIELD_SIZE + 30), 'results', 'banana,no', 'source');
        $result = $logger::$calledWith;
        $this->assertIdentical(DQA_KEYWORD_SEARCHES, $result[0]);
        $this->assertSame(Sql::DQA_CONTEXT_FIELD_SIZE, Api::utf8_char_len($result[1]['query']));
        $this->assertTrue(Sql::DQA_CONTEXT_FIELD_SIZE > Api::utf8_char_len($result[1]['stem']));
        $this->assertSame('banana,no', $result[1]['result_list']);
        $this->assertSame('results', $result[1]['results']);
        $this->assertSame('source', $result[1]['source']);
        $this->assertIsA($result[1]['ts'], 'int');
    }

    function testGetMaAppType() {
        $model = new \RightNow\Models\Clickstream;
        $result = $model->getMaAppType('', '', '');
        $this->assertIsA($result, 'RightNow\Libraries\ResponseObject');
        $this->assertIdentical(CS_APP_MA, $result->result);

        $result = $model->getMaAppType('', '27', '');
        $this->assertIsA($result, 'RightNow\Libraries\ResponseObject');
        $this->assertIdentical(CS_APP_FB, $result->result);

        $result = $model->getMaAppType('shortcut', '', 'hidden shortcut');
        $this->assertIsA($result, 'RightNow\Libraries\ResponseObject');
        $this->assertIdentical(CS_APP_MA, $result->result);
    }

    function testTruncateList() {
        $method = $this->getMethod('truncateList');

        $this->assertSame('banana,foo', $method('banana,foo', 10));
        $this->assertSame('banana,', $method('banana,foo', 9));
        $this->assertSame('banana.foo', $method('banana.foo', 10));
        $this->assertSame('banana.fo', $method('banana.foo', 9));
        $this->assertSame('banana.', $method('banana.foo', 9, '.'));
    }
}

class ClickstreamDataLogger {
    public static $calledWith;

    static function dqa_insert() {
        self::$calledWith = func_get_args();
    }
}
