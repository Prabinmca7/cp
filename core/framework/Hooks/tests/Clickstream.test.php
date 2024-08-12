<?php

use RightNow\Hooks,
    RightNow\Utils\Text;

RightNow\UnitTest\Helper::loadTestedFile(__FILE__);

class ClickstreamTest extends CPTestCase
{
    public $testingClass = 'RightNow\Hooks\Clickstream';
    private $mockedClasses = array(
        array('\RightNow\Controllers\MockBase', '\RightNow\Controllers\Base'),
        array('\RightNow\Models\MockClickstream', '\RightNow\Models\Clickstream'),
        array('\RightNow\Models\MockAnswer', '\RightNow\Models\Answer', 'Models/Answer.php'),
        array('\RightNow\Models\MockIncident', '\RightNow\Models\Incident', 'Models/Incident.php'),
        array('\RightNow\Models\MockAsset', '\RightNow\Models\Asset', 'Models/Asset.php'),
        array('\RightNow\Libraries\MockSession', '\RightNow\Libraries\Session'),
        array('MockCI_URI', 'CI_URI'),
        array('MockCI_Input', 'CI_Input'),
        array('MockCI_Router', 'CI_Router'),
        array('MockCI_Config', 'CI_Config'),
        array('MockRnow', 'Rnow'),
    );

    function __construct() {
        parent::__construct();

        // Generate all the mocks
        foreach ($this->mockedClasses as $className) {
            list($mockName, $classToMock, $requirePath) = $className;
            if (!class_exists($mockName)) {
                // Check if class already exists: some other test may
                // have already generated the mock,
                // in which case it's ready to go.
                if ($requirePath) {
                    require_once CPCORE . $requirePath;
                }
                Mock::generate($classToMock);
            }
        }
    }

    /**
     * Creates a mock instance of CI after
     * setting mocks for uri, uri.router, rnow,
     * input, config, and default models, if desired:
     *
     * * Clickstream: don't care what it returns, just
     *   as long as it's present
     * * Answer: stubbed #get
     * * Incident: stubbed #getIncidentIDFromRefno
     *
     * @param bool $attachModels Sets instances of
     * mock Clickstream, Answer, and Incident models if true;
     * if you want to set expectations on model methods, you'd
     * specify false and attach your own model mocks
     * @return object Mock CI instance
     */
    function CI($attachModels = true) {
        $CI = new \RightNow\Controllers\MockBase;

        if ($attachModels) {
            $CI->returns('model', new \RightNow\Models\MockClickstream, array('Clickstream'));

            $answerModel = new \RightNow\Models\MockAnswer;
            $answerModel->setReturnValue('get', (object) array('result' => (object) array('Summary' => 'blah')));
            $answerModel->setReturnValue('getAnswerSummary', (object) array('result' => array('52' => array('Summary' => 'blah'))));
            $CI->returns('model', $answerModel, array('Answer'));

            $incidentModel = new \RightNow\Models\MockIncident;
            $incidentModel->setReturnValue('getIncidentIDFromRefno', (object) array('result' => 52));
            $CI->returns('model', $incidentModel, array('Incident'));

            $assetModel = new \RightNow\Models\MockAsset;
            $assetModel->setReturnValue('get', (object) array('result' => 9));
            $CI->returns('model', $assetModel, array('Asset'));
        }

        $CI->uri = new \MockCI_URI;
        $CI->uri->router = new \MockCI_Router;
        $CI->rnow = new \MockRnow;
        $CI->input = new \MockCI_Input;
        // The post function returns false if the key cannot be found
        $CI->input->setReturnValue('post', false);
        $CI->config = new \MockCI_Config;
        // So that getUrlParameter always starts at the first
        // item in a mocked uri_to_assoc value.
        $CI->config->setReturnValue('item', 0);

        return $CI;
    }

    function testTrackSession() {
        list(
            $class,
            $useClickstream,
            $controllerClassName,
            $method
        ) = $this->reflect('useClickstream', 'controllerClassName', 'method:trackSession');

        // Spider
        $CI = $this->CI(false);
        $CI->rnow->setReturnValue('isSpider', true);
        $CI->uri->setReturnValue('uri_to_assoc', array());
        $model = new \RightNow\Models\MockClickstream;
        $model->expectOnce('insertSpider');
        $model->expectNever('insertAction');
        $CI->returns('model', $model, array('Clickstream'));
        $instance = $class->newInstanceArgs(array($CI));
        $useClickstream->setValue($instance, true);
        $method->invoke($instance);

        // Mail transaction
        $CI = $this->CI(false);
        $CI->uri->setReturnValue('uri_to_assoc', array('track' => 'banana'));
        $model = new \RightNow\Models\MockClickstream;
        $model->expectOnce('insertMailTransaction');
        $model->expectOnce('insertAction');
        $CI->returns('model', $model, array('Clickstream'));
        $instance = $class->newInstanceArgs(array($CI));
        $useClickstream->setValue($instance, true);
        $method->invoke($instance);

        // Actions with invalid input won't insert action
        $CI = $this->CI(false);
        $model = new \RightNow\Models\MockClickstream;
        $model->expectNever('insertAction');
        $CI->uri->setReturnValue('uri_to_assoc', array());
        $CI->returns('model', $model, array('Clickstream'));
        $instance = $class->newInstanceArgs(array($CI));
        $useClickstream->setValue($instance, true);
        // On-demand actions
        $method->invoke($instance, 'normal', 'answer_feedback');
        $method->invoke($instance, 'normal', 'answer_notification');
        $method->invoke($instance, 'normal', 'notification_update');
        $method->invoke($instance, 'normal', 'notification_delete');
        $method->invoke($instance, 'normal', 'email_answer');

        // Actions are recorded

        // Base case
        $CI = $this->CI(false);
        $CI->uri->setReturnValue('uri_to_assoc', array('a_id' => '52', 'kw' => 'bananas', 'p' => 'yep', 'c' => 'nope'));
        $model = new \RightNow\Models\MockClickstream;
        $model->expectOnce('insertAction', array(
            new \IsAExpectation('string'),
            new \IsAExpectation('bool'),
            new \IsAExpectation('int'),
            '/', null, null, null));
        $CI->returns('model', $model, array('Clickstream'));
        $instance = $class->newInstanceArgs(array($CI));
        $useClickstream->setValue($instance, true);
        $method->invoke($instance, 'normal', 'page_render');

        // Report (without filters)
        $CI = $this->CI(false);
        $CI->uri->setReturnValue('uri_to_assoc', array('a_id' => '52', 'kw' => 'bananas', 'p' => 'yep', 'c' => 'nope'));
        $model = new \RightNow\Models\MockClickstream;
        $model->expectOnce('insertAction', array(
            new \IsAExpectation('string'),
            new \IsAExpectation('bool'),
            new \IsAExpectation('int'),
            new \PatternExpectation('/ReportData\/Page/'),
            '', null, null));
        $CI->returns('model', $model, array('Clickstream'));
        $instance = $class->newInstanceArgs(array($CI));
        $useClickstream->setValue($instance, true);
        $method->invoke($instance, 'normal', 'report_data_service');

        $CI = $this->CI(false);
        $CI->uri->setReturnValue('uri_to_assoc', array());
        $model = new \RightNow\Models\MockClickstream;
        $model->expectOnce('insertAction', array(
            new \IsAExpectation('string'),
            new \IsAExpectation('bool'),
            new \IsAExpectation('int'),
            new \IdenticalExpectation('/ajaxRequest/FormTokenUpdate'),
            null, null, null));
        $CI->returns('model', $model, array('Clickstream'));
        $instance = $class->newInstanceArgs(array($CI));
        $useClickstream->setValue($instance, true);
        $controllerClassName->setValue($instance, 'ajaxRequest');
        $method->invoke($instance, 'normal', 'form_token_update');

        // Social Content Actions
        $actions = array(
            'question_delete' => array(
                'action' => '/CPSocialQuestionDelete/',
            ),
            'comment_update' => array(
                'action' => '/CPSocialCommentUpdate/',
            ),
            'comment_create' => array(
                'action' => '/CPSocialCommentCreate/',
            ),
            'comment_reply' => array(
                'action' => '/CPSocialCommentReply/',
            ),
            'comment_mark_best' => array(
                'action' => '/CPSocialCommentBestAnswer/',
            ),
            'social_content_flag' => array(
                'action' => '/CPSocialCommentFlag/',
            ),
            'social_content_rate' => array(
                'action' => '/CPSocialCommentRate/',
            ),
        );

        foreach($actions as $actionTag => $args) {
            $CI = $this->CI(false);
            $CI->input = new \MockCI_Input;
            $CI->input->setReturnValue('post', 123);
            $CI->uri->setReturnValue('uri_to_assoc', array());
            $model = new \RightNow\Models\MockClickstream;
            $model->expectOnce('insertAction', array(
                new \IsAExpectation('string'),
                new \IsAExpectation('bool'),
                new \IsAExpectation('int'),
                new \PatternExpectation($args['action']),
                123, 123, null));
            $CI->returns('model', $model, array('Clickstream'));
            $instance = $class->newInstanceArgs(array($CI));
            $useClickstream->setValue($instance, true);
            $method->invoke($instance, 'normal', $actionTag);
        }
    }

    function testPageRender() {
        list(
            $class,
            $method
        ) = $this->reflect('method:pageRender');
        $untouched = (object) array();

        $CI = $this->CI();
        $CI->page = 'banana';
        $CI->uri->setReturnValue('uri_to_assoc', array('nope' => 1));

        // Base case
        $instance = $class->newInstanceArgs(array($CI));
        $action = '';
        $context = (object) array();
        $args = array(&$action, $context);
        $this->assertTrue($method->invokeArgs($instance, $args));
        $this->assertIdentical($untouched, $context);
        $this->assertIdentical('/banana', $action);

        // Error page
        $CI = $this->CI();
        $CI->page = 'error';
        $CI->uri->setReturnValue('uri_to_assoc', array('error_id' => 'banana'));

        $instance = $class->newInstanceArgs(array($CI));
        $action = '';
        $context = (object) array();
        $args = array(&$action, $context);
        $this->assertTrue($method->invokeArgs($instance, $args));
        $this->assertIdentical((object) array('context1' => 'banana'), $context);
        $this->assertIdentical('/error', $action);

        // Meta: answers
        $CI = $this->CI();
        $CI->page = 'banana';
        $CI->uri->setReturnValue('uri_to_assoc', array('a_id' => '52'));
        $CI->meta = array('clickstream' => 'answer_preview');
        $instance = $class->newInstanceArgs(array($CI));
        $action = '';
        $context = (object) array();
        $args = array(&$action, $context);
        $this->assertTrue($method->invokeArgs($instance, $args));
        $this->assertIdentical('52', $context->context1);
        $this->assertIsA($context->context2, 'string');
        $this->assertIdentical('/Suggested/AnswerView', $action);

        // Meta: answers, but no a_id
        $CI = $this->CI();
        $CI->page = 'banana';
        $CI->meta = array('clickstream' => 'answer_print');
        $CI->uri->setReturnValue('uri_to_assoc', array());
        $instance = $class->newInstanceArgs(array($CI));
        $action = '';
        $context = (object) array();
        $args = array(&$action, $context);
        $this->assertFalse($method->invokeArgs($instance, $args));
        $this->assertIdentical($untouched, $context);
        $this->assertIdentical('/AnswerPrint', $action);

        // Meta: answer list
        $CI = $this->CI();
        $CI->page = 'banana';
        $CI->uri->setReturnValue('uri_to_assoc', array('p' => '52', 'c' => 'banana'));
        $CI->meta = array('clickstream' => 'answer_list');
        $instance = $class->newInstanceArgs(array($CI));
        $action = '';
        $context = (object) array();
        $args = array(&$action, $context);
        $this->assertTrue($method->invokeArgs($instance, $args));
        $this->assertNull($context->context1);
        $this->assertIdentical('52', $context->context2);
        $this->assertIdentical('banana', $context->context3);
        $this->assertIdentical('/AnswerList', $action);

        // Meta: incident, i_id
        $CI = $this->CI();
        $CI->page = 'banana';
        $CI->uri->setReturnValue('uri_to_assoc', array('i_id' => '52'));
        $CI->meta = array('clickstream' => 'incident_confirm');
        $instance = $class->newInstanceArgs(array($CI));
        $action = '';
        $context = (object) array();
        $args = array(&$action, $context);
        $this->assertTrue($method->invokeArgs($instance, $args));
        $this->assertIdentical((object) array('context1' => '52'), $context);
        $this->assertIdentical('/CPAskConfirm', $action);

        // Meta: asset, asset_id
        //@@@ QA 131111-000137 E2E CP ASSETS:  Clickstreams - /CPIncidentCreate is logged in clickstreams when creating an Asset
        $CI = $this->CI();
        $CI->page = 'banana';
        $CI->uri->setReturnValue('uri_to_assoc', array('asset_id' => '9'));
        $CI->meta = array('clickstream' => 'asset_update');
        $instance = $class->newInstanceArgs(array($CI));
        $action = '';
        $context = (object) array();
        $args = array(&$action, $context);
        $this->assertTrue($method->invokeArgs($instance, $args));
        $this->assertIdentical('/asset_update', $action);

        // Meta: incident, refno
        $CI = $this->CI();
        $CI->page = 'banana';
        $CI->uri->setReturnValue('uri_to_assoc', array('refno' => '100329-000002'));
        $CI->meta = array('clickstream' => 'incident_update');
        $instance = $class->newInstanceArgs(array($CI));
        $action = '';
        $context = (object) array();
        $args = array(&$action, $context);
        $this->assertTrue($method->invokeArgs($instance, $args));
        $this->assertIdentical((object) array('context1' => 52), $context);
        $this->assertIdentical('/IncidentUpdate', $action);

        // Search: dym
        $CI = $this->CI();
        $CI->page = 'banana';
        $CI->uri->setReturnValue('uri_to_assoc', array('search' => '1', 'dym' => '1'));
        $instance = $class->newInstanceArgs(array($CI));
        $action = '';
        $context = (object) array();
        $args = array(&$action, $context);
        $this->assertTrue($method->invokeArgs($instance, $args));
        $this->assertIdentical((object) array('context1' => null, 'context2' => null, 'context3' => null), $context);
        $this->assertIdentical('/banana/DYM/Search', $action);

        // Search: suggested
        $CI = $this->CI();
        $CI->page = 'banana';
        $CI->uri->setReturnValue('uri_to_assoc', array('search' => '1', 'suggested' => '1'));
        $instance = $class->newInstanceArgs(array($CI));
        $action = '';
        $context = (object) array();
        $args = array(&$action, $context);
        $this->assertTrue($method->invokeArgs($instance, $args));
        $this->assertIdentical((object) array('context1' => null, 'context2' => null, 'context3' => null), $context);
        $this->assertIdentical('/banana/Suggested/Search', $action);

        // Social Question View
        $CI = $this->CI();
        $CI->page = 'banana';
        $CI->uri->setReturnValue('uri_to_assoc', array('qid' => '1'));
        $CI->meta = array('clickstream' => 'question_view');
        $instance = $class->newInstanceArgs(array($CI));
        $action = '';
        $context = (object) array();
        $args = array(&$action, $context);
        $this->assertTrue($method->invokeArgs($instance, $args));
        $this->assertIdentical((object) array('context1' => '1'), $context);
        $this->assertIdentical('/CPSocialQuestionView', $action);

        // prod, cat, kw
        $CI = $this->CI();
        $CI->page = 'banana';
        $CI->uri->setReturnValue('uri_to_assoc', array('search' => '1', 'kw' => 'projector', 'p' => 'pro', 'c' => 'kitteh'));
        $instance = $class->newInstanceArgs(array($CI));
        $action = '';
        $context = (object) array();
        $args = array(&$action, $context);
        $this->assertTrue($method->invokeArgs($instance, $args));
        $this->assertIdentical((object) array('context1' => 'projector', 'context2' => 'pro', 'context3' => 'kitteh'), $context);
        $this->assertIdentical('/banana/Search', $action);

        // prod, cat, kw via POST
        $CI = $this->CI();
        $CI->page = 'banana';
        $CI->uri->setReturnValue('uri_to_assoc', array('p' => 'pro', 'c' => 'kitteh'));
        $CI->input = new \MockCI_Input;
        $CI->input->setReturnValue('post', 'projector');
        $instance = $class->newInstanceArgs(array($CI));
        $action = '';
        $context = (object) array();
        $args = array(&$action, $context);
        $this->assertTrue($method->invokeArgs($instance, $args));
        $this->assertIdentical((object) array('context1' => 'projector', 'context2' => 'pro', 'context3' => 'kitteh'), $context);
        $this->assertIdentical('/banana/Search', $action);

        // prod, cat, no kw via POST
        $CI = $this->CI();
        $CI->page = 'banana';
        $CI->uri->setReturnValue('uri_to_assoc', array('kw' => 'projector', 'p' => 'pro', 'c' => 'kitteh'));
        $instance = $class->newInstanceArgs(array($CI));
        $action = '';
        $context = (object) array();
        $args = array(&$action, $context);
        $this->assertTrue($method->invokeArgs($instance, $args));
        $this->assertIdentical((object) array(), $context);
        $this->assertIdentical('/banana', $action);
    }

    function testGetParameter() {
        list(
            $class,
            $method
        ) = $this->reflect('method:getParameter');

        // GET
        $CI = $this->CI();
        $CI->uri->setReturnValue('uri_to_assoc', array('foo' => 'bar', 'banana' => 'baz', 'nope' => ''));
        $instance = $class->newInstanceArgs(array($CI));
        $this->assertSame('bar', $method->invoke($instance, 'foo'));
        $this->assertSame('baz', $method->invoke($instance, 'banana'));
        $this->assertSame('', $method->invoke($instance, 'nope'));
        $this->assertNull($method->invoke($instance, 'bananas'));

        // POST
        $CI = $this->CI();
        $instance = $class->newInstanceArgs(array($CI));
        $this->assertFalse($method->invoke($instance, 'foo', true));
        $CI->input = new \MockCI_Input;
        $CI->input->setReturnValue('post', 'no');
        $this->assertSame('no', $method->invoke($instance, 'foo', true));
    }

    function testReportData() {
        list(
            $class,
            $method
        ) = $this->reflect('method:reportData');
        $instance = $class->newInstance();

        // No filters. But, hey, we're probably paging...
        $action = '';
        $context = (object) array();
        $args = array(&$action, $context);
        $method->invokeArgs($instance, $args);
        $this->assertIdentical((object) array('context1' => ''), $context);
        $this->assertIdentical('/Page', $action);

        // Bad filters. Still probably paging...
        $_POST['filters'] = "sldkfj;\slfdkj;'lsfkdj";
        $action = '';
        $context = (object) array();
        $args = array(&$action, $context);
        $method->invokeArgs($instance, $args);
        $this->assertIdentical((object) array('context1' => ''), $context);
        $this->assertIdentical('/Page', $action);

        // Keyword
        $_POST['filters'] = json_encode((object) array('search' => '1', 'keyword' => (object) array('filters' => (object) array('data' => 'banana'))));
        $action = '';
        $context = (object) array();
        $args = array(&$action, $context);
        $method->invokeArgs($instance, $args);
        $this->assertIdentical((object) array('context1' => 'banana'), $context);
        $this->assertIdentical('/Search', $action);

        // Keyword array (?)
        $_POST['filters'] = json_encode((object) array('search' => '1', 'keyword' => (object) array('filters' => (object) array('data' => array('banana', 'no')))));
        $action = '';
        $context = (object) array();
        $args = array(&$action, $context);
        $method->invokeArgs($instance, $args);
        $this->assertIdentical((object) array('context1' => 'banana/no'), $context);
        $this->assertIdentical('/Search', $action);

        // prod
        $_POST['filters'] = json_encode((object) array('search' => '1', 'p' => (object) array('filters' => (object) array('data' => array('banana', 'no')))));
        $action = '';
        $context = (object) array();
        $args = array(&$action, $context);
        $method->invokeArgs($instance, $args);
        $this->assertIdentical((object) array('context2' => 'banana'), $context);
        $this->assertIdentical('/Search', $action);

        // cat
        $_POST['filters'] = json_encode((object) array('search' => '1', 'c' => (object) array('filters' => (object) array('data' => array('banana', 'no')))));
        $action = '';
        $context = (object) array();
        $args = array(&$action, $context);
        $method->invokeArgs($instance, $args);
        $this->assertIdentical((object) array('context3' => 'banana'), $context);
        $this->assertIdentical('/Search', $action);

        // prod + cat + kw
        $_POST['filters'] = json_encode((object) array(
            'search' => '1',
            'p' => (object) array('filters' => (object) array('data' => array('banana', 'no'))),
            'c' => (object) array('filters' => (object) array('data' => array('eyeoneye', 'no'))),
            'keyword' => (object) array('filters' => (object) array('data' => 'lazy')),
        ));
        $action = '';
        $context = (object) array();
        $args = array(&$action, $context);
        $method->invokeArgs($instance, $args);
        $this->assertIdentical((object) array('context1' => 'lazy', 'context2' => 'banana', 'context3' => 'eyeoneye'), $context);
        $this->assertIdentical('/Search', $action);

        // Paging!
        $_POST['filters'] = json_encode((object) array('page' => 'banana'));
        $action = '';
        $context = (object) array();
        $args = array(&$action, $context);
        $method->invokeArgs($instance, $args);
        $this->assertIdentical((object) array('context1' => 'banana'), $context);
        $this->assertIdentical('/Page', $action);

        // Paging! (non-string is casted into a string)
        $_POST['filters'] = json_encode((object) array('page' => 1));
        $action = '';
        $context = (object) array();
        $args = array(&$action, $context);
        $method->invokeArgs($instance, $args);
        $this->assertIdentical((object) array('context1' => '1'), $context);
        $this->assertIdentical('/Page', $action);

        unset($_POST['filters']);
    }

    function testIncidentSubmit() {
        list(
            $class,
            $method
        ) = $this->reflect('method:incidentSubmit');
        $instance = $class->newInstance();
        $untouched = (object) array();

        // Bad form
        $action = '';
        $context = (object) array();
        $args = array(&$action, $context);
        $method->invokeArgs($instance, $args);
        $this->assertIdentical($untouched, $context);
        $this->assertIdentical('', $action);

        $_POST['form'] = "bananas'foo";
        $method->invokeArgs($instance, $args);
        $this->assertIdentical($untouched, $context);
        $this->assertIdentical('', $action);

        // Doesn't contain contact or incident fields
        $_POST['form'] = json_encode(array(
            (object) array('name' => 'contacts.login', 'value' => 'bananas'),
            (object) array('name' => 'incidents.subject', 'value' => 'no'),
            (object) array('name' => 'hey.ho', 'value' => 'no'),
        ));
        $method->invokeArgs($instance, $args);
        $this->assertIdentical($untouched, $context);
        $this->assertIdentical('', $action);

        // Incident update
        $ajaxForm = json_encode(array(
            (object) array('name' => 'Incident.Subject', 'value' => 'no'),
            (object) array('name' => 'hey.ho', 'value' => 'no'),
        ));
        $_POST['form'] = $ajaxForm;
        $_POST['updateIDs'] = '{"i_id":127}';
        $method->invokeArgs($instance, $args);
        $this->assertIdentical((object) array('context1' => 127), $context);
        $this->assertTrue(Text::stringContains($action, 'IncidentUpdate'));
        unset($_POST['form']);

        unset($_POST['updateIDs']);
        $_POST['formData'] = array('Incident.Subject' => 'no', 'hey.no' => 'no');
        $method->invokeArgs($instance, $args);
        $this->assertIdentical((object) array('context1' => 127), $context);
        $this->assertTrue(Text::stringContains($action, 'IncidentUpdate'));
        unset($_POST['formData'], $_POST['updateIDs']);

        // Incident update with i_id in URL
        $this->addUrlParameters(array('i_id' => 127));

        $_POST['form'] = $ajaxForm;
        $method->invokeArgs($instance, $args);
        $this->assertIdentical((object) array('context1' => '127'), $context);
        $this->assertTrue(Text::stringContains($action, 'IncidentUpdate'));
        unset($_POST['form']);

        $_POST['formData'] = array('Incident.Subject' => 'no', 'hey.no' => 'no');
        $method->invokeArgs($instance, $args);
        $this->assertIdentical((object) array('context1' => '127'), $context);
        $this->assertTrue(Text::stringContains($action, 'IncidentUpdate'));
        unset($_POST['i_id']);

        $this->restoreUrlParameters();

        // Incident create: Smart Assistant (no context set)
        $_POST['form'] = $ajaxForm;
        $_POST['smrt_asst'] = 'false';
        $action = '';
        $context = (object) array();
        $args = array(&$action, $context);
        $method->invokeArgs($instance, $args);
        $this->assertIdentical($untouched, $context);
        $this->assertTrue(Text::stringContains($action, 'SAIncidentCreate'));

        // Incident create, w/o Smart Assistant is basically a no-op (?)
        $_POST['smrt_asst'] = 'true';
        $action = '';
        $context = (object) array();
        $args = array(&$action, $context);
        $method->invokeArgs($instance, $args);
        $this->assertIdentical($untouched, $context);
        $this->assertIdentical('', $action);

        unset($_POST['form'], $_POST['smrt_asst']);

        // Incident + Account creation: no email (?)
        $_POST['form'] = json_encode(array(
            (object) array('name' => 'Incident.Subject', 'value' => 'no'),
            (object) array('name' => 'Contact.Login', 'value' => 'banana'),
        ));
        $action = '';
        $context = (object) array();
        $args = array(&$action, $context);
        $method->invokeArgs($instance, $args);
        $this->assertIdentical((object) array('context2' => '1'), $context);
        $this->assertIdentical('', $action);

        // Incident update + Account creation: email
        $_POST['form'] = json_encode(array(
            (object) array('name' => 'Incident.Subject', 'value' => 'no'),
            (object) array('name' => 'Contact.Emails.PRIMARY.Address', 'value' => 'banana@'),
        ));
        $action = '';
        $context = (object) array();
        $args = array(&$action, $context);
        $method->invokeArgs($instance, $args);
        $this->assertIdentical((object) array('context2' => '1'), $context);
        $this->assertIdentical('', $action);

        // Social Question Update
        $this->addUrlParameters(array('qid' => 120));

        $_POST['form'] = json_encode(array(
            (object) array('name' => 'CommunityQuestion.Subject', 'value' => 'test value'),
        ));
        $action = '';
        $context = (object) array();
        $args = array(&$action, $context);
        $method->invokeArgs($instance, $args);
        $this->assertIdentical((object) array('context1' => '120'), $context);
        $this->assertStringContains($action, 'CPSocialQuestionUpdate');

        $this->restoreUrlParameters();

        // Social Question Create
        $_POST['form'] = json_encode(array(
            (object) array('name' => 'CommunityQuestion.Subject', 'value' => 'test value'),
        ));
        $action = '';
        $context = (object) array();
        $args = array(&$action, $context);
        $method->invokeArgs($instance, $args);
        $this->assertIdentical((object) array(), $context);
        $this->assertStringContains($action, 'CPSocialQuestionCreate');

        // No account creation: email already exists
        $_POST['form'] = json_encode(array(
            (object) array('name' => 'Incident.Subject', 'value' => 'no'),
            (object) array('name' => 'Contact.Emails.PRIMARY.Address', 'value' => 'eturner@rightnow.com.invalid'),
        ));
        $action = '';
        $context = (object) array();
        $args = array(&$action, $context);
        $method->invokeArgs($instance, $args);
        $this->assertIdentical($untouched, $context);
        $this->assertIdentical('', $action);

        unset($_POST['form']);
        $_POST['formData'] = array('Incident.Subject' => 'no',
                                   'Contact.Emails.PRIMARY.Address' => 'eturner@rightnow.com.invalid');
        $action = '';
        $context = (object) array();
        $args = array(&$action, $context);
        $method->invokeArgs($instance, $args);
        $this->assertIdentical($untouched, $context);
        $this->assertIdentical('', $action);
        unset($_POST['formData']);

        // No incident fields: Account creation
        $_POST['form'] = json_encode(array(
            (object) array('name' => 'Contact.Login', 'value' => 'banana'),
            (object) array('name' => 'Contact.Emails.PRIMARY.Address', 'value' => 'banana@'),
        ));
        $action = '';
        $context = (object) array();
        $args = array(&$action, $context);
        $method->invokeArgs($instance, $args);
        $this->assertIdentical($untouched, $context);
        $this->assertTrue(Text::stringContains($action, 'AccountCreate'));

        unset($_POST['form']);
    }

    function testAnswerNotification() {
        list(
            $class,
            $method
        ) = $this->reflect('method:answerNotification');
        $instance = $class->newInstance();

        $untouched = (object) array();

        // No-op
        $action = '';
        $context = (object) array();
        $args = array(&$action, $context);
        $this->assertFalse($method->invokeArgs($instance, $args));
        $this->assertIdentical($untouched, $context);
        $this->assertIdentical('', $action);

        // Post accepts a_id; no action
        $_POST['a_id'] = '52';
        $_POST['status'] = '2';
        $this->assertNull($method->invokeArgs($instance, $args));
        $this->assertIdentical('52', $context->context1);
        $this->assertIsA($context->context2, 'string');
        $this->assertIdentical('', $action);

        // Post accepts answerID; action is populated
        unset($_POST['a_id']);
        $_POST['answerID'] = '52';
        $_POST['status'] = '0';
        $context = (object) array();
        $action = '';
        $args = array(&$action, $context);
        $this->assertNull($method->invokeArgs($instance, $args));
        $this->assertIdentical('52', $context->context1);
        $this->assertIsA($context->context2, 'string');
        $this->assertTrue(Text::stringContains($action, 'NotificationRenew'));

        // Post accepts a_id; action is populated
        unset($_POST['a_id']);
        $_POST['answerID'] = '52';
        $_POST['status'] = '-4';
        $context = (object) array();
        $action = '';
        $args = array(&$action, $context);
        $this->assertNull($method->invokeArgs($instance, $args));
        $this->assertIdentical('52', $context->context1);
        $this->assertIsA($context->context2, 'string');
        $this->assertTrue(Text::stringContains($action, 'NotificationDelete'));

        // Valid id format but answer doesn't exist
        $_POST['answerID'] = '234231';
        $context = (object) array();
        $action = '';
        $args = array(&$action, $context);
        $this->assertFalse($method->invokeArgs($instance, $args));
        $this->assertIdentical($untouched, $context);
        $this->assertIdentical('', $action);

        unset($_POST['answerID'], $_POST['status']);
    }

    function testProductCategoryNotification() {
        $method = $this->getMethod('productCategoryNotification');

        // No-op
        $context = (object) array();
        $method($context);
        $this->assertIdentical((object) array(), $context);

        $_POST['chain'] = 'banana';

        // Lax product checking
        $_POST['filter_type'] = 'prod';
        $context = (object) array();
        $method($context);
        $this->assertIdentical((object) array('context2' => 'banana'), $context);

        $_POST['filter_type'] = 'products';
        $context = (object) array();
        $method($context);
        $this->assertIdentical((object) array('context2' => 'banana'), $context);

        $_POST['filter_type'] = 'BananaProd';
        $context = (object) array();
        $method($context);
        $this->assertIdentical((object) array('context2' => 'banana'), $context);

        $_POST['filter_type'] = HM_PRODUCTS;
        $context = (object) array();
        $method($context);
        $this->assertIdentical((object) array('context2' => 'banana'), $context);

        $_POST['filter_type'] = HM_PRODUCTS . '';
        $context = (object) array();
        $method($context);
        $this->assertIdentical((object) array('context2' => 'banana'), $context);

        // Lax category checking
        $_POST['filter_type'] = 'cat';
        $context = (object) array();
        $method($context);
        $this->assertIdentical((object) array('context3' => 'banana'), $context);

        $_POST['filter_type'] = 'categories';
        $context = (object) array();
        $method($context);
        $this->assertIdentical((object) array('context3' => 'banana'), $context);

        $_POST['filter_type'] = 'BananaCates';
        $context = (object) array();
        $method($context);
        $this->assertIdentical((object) array('context3' => 'banana'), $context);

        $_POST['filter_type'] = HM_CATEGORIES;
        $context = (object) array();
        $method($context);
        $this->assertIdentical((object) array('context3' => 'banana'), $context);

        $_POST['filter_type'] = HM_CATEGORIES . '';
        $context = (object) array();
        $method($context);
        $this->assertIdentical((object) array('context3' => 'banana'), $context);

        // Prod beats cat if they're both present
        $_POST['filter_type'] = 'CategoriesAndProducts';
        $context = (object) array();
        $method($context);
        $this->assertIdentical((object) array('context2' => 'banana'), $context);

        unset($_POST['chain'], $_POST['filter_type']);
    }

    function testSocialContentActions(){
        $method = $this->getMethod('socialContentActions');

        $getEventContext = function() {
            return (object) array('context1' => null, 'context2' => null, 'context3' => null);
        };

        $reset = function() {
            unset($_POST['questionID']);
            unset($_POST['commentID']);
        };

        $questionID = 123;
        $commentID = 456;

        // question
        $_POST['questionID'] = $questionID;
        $eventContext = $getEventContext();
        $action = $method('/ajaxRequest/CPSocialQuestionDelete', 'question_delete', $eventContext);
        $this->assertEqual($questionID, $eventContext->context1);
        $this->assertFalse($eventContext->context2);

        // comment
        $_POST['commentID'] = $commentID;
        $eventContext = $getEventContext();
        $action = $method('/ajaxRequest/CPSocialCommentDelete', 'comment_delete', $eventContext);
        $this->assertEqual($questionID, $eventContext->context1);
        $this->assertEqual($commentID, $eventContext->context2);

        // social content flag
        $reset();
        $_POST['questionID'] = $questionID;
        $eventContext = $getEventContext();
        $action = $method('/ajaxRequest/whatever', 'social_content_flag', $eventContext);
        $this->assertStringContains($action, 'CPSocialQuestionFlag');
        $this->assertEqual($questionID, $eventContext->context1);
        $this->assertFalse($eventContext->context2);

        $reset();
        $_POST['questionID'] = $questionID;
        $_POST['commentID'] = $commentID;
        $eventContext = $getEventContext();
        $action = $method('/ajaxRequest/whatever', 'social_content_flag', $eventContext);
        $this->assertStringContains($action, 'CPSocialCommentFlag');
        $this->assertEqual($questionID, $eventContext->context1);
        $this->assertEqual($commentID, $eventContext->context2);

        // social content rate
        $reset();
        $_POST['questionID'] = $questionID;
        $eventContext = $getEventContext();
        $action = $method('/ajaxRequest/whatever', 'social_content_rate', $eventContext);
        $this->assertStringContains($action, 'CPSocialQuestionRate');
        $this->assertEqual($questionID, $eventContext->context1);
        $this->assertFalse($eventContext->context2);

        $reset();
        $_POST['questionID'] = $questionID;
        $_POST['commentID'] = $commentID;
        $eventContext = $getEventContext();
        $action = $method('/ajaxRequest/whatever', 'social_content_rate', $eventContext);
        $this->assertStringContains($action, 'CPSocialCommentRate');
        $this->assertEqual($questionID, $eventContext->context1);
        $this->assertEqual($commentID, $eventContext->context2);

        $reset();
    }

    function testRecordNotification(){
        $method = $this->getMethod('recordNotification');
        $actionPlaceholder = "";

        $eventContext = (object) array("context1" => null, "context2" => null, "context3" => null);
        $_POST['id'] = 12;
        $_POST['filter_type'] = 'Product';
        $method($actionPlaceholder, $eventContext);
        $this->assertNull($eventContext->context1);
        $this->assertNull($eventContext->context3);
        $this->assertIdentical(12, $eventContext->context2);

        $eventContext = (object) array("context1" => null, "context2" => null, "context3" => null);
        $_POST['filter_type'] = 'Category';
        $method($actionPlaceholder, $eventContext);
        $this->assertNull($eventContext->context1);
        $this->assertNull($eventContext->context2);
        $this->assertIdentical(12, $eventContext->context3);

        $eventContext = (object) array("context1" => null, "context2" => null, "context3" => null);
        $_POST['id'] = 12;
        $_POST['filter_type'] = HM_PRODUCTS;
        $method($actionPlaceholder, $eventContext);
        $this->assertNull($eventContext->context1);
        $this->assertNull($eventContext->context3);
        $this->assertIdentical(12, $eventContext->context2);

        $eventContext = (object) array("context1" => null, "context2" => null, "context3" => null);
        $_POST['filter_type'] = HM_CATEGORIES;
        $method($actionPlaceholder, $eventContext);
        $this->assertNull($eventContext->context1);
        $this->assertNull($eventContext->context2);
        $this->assertIdentical(12, $eventContext->context3);

        $eventContext = (object) array("context1" => null, "context2" => null, "context3" => null);
        $_POST['id'] = 1;
        $_POST['filter_type'] = 'Answer';
        $method($actionPlaceholder, $eventContext);
        $this->assertIdentical(1, $eventContext->context1);
        $this->assertTrue(is_string($eventContext->context2));
        $this->assertNull($eventContext->context3);

        unset($_POST['id'], $_POST['filter_type']);
    }

    function testEmailAnswer() {
        $method = $this->getMethod('emailAnswer');

        $context = (object) array();
        $untouched = (object) array();

        $this->assertFalse($method($context));
        $this->assertIdentical($untouched, $context);

        // Post accepts a_id; context3 is false since to isn't present
        $_POST['a_id'] = '52';
        $method($context);
        $this->assertIdentical('52', $context->context1);
        $this->assertIsA($context->context2, 'string');
        $this->assertFalse($context->context3);

        // Post accepts answerID; context3 is populated
        unset($_POST['a_id']);
        $_POST['answerID'] = '52';
        $_POST['to'] = 'banana';
        $context = (object) array();
        $method($context);
        $this->assertIdentical('52', $context->context1);
        $this->assertIsA($context->context2, 'string');
        $this->assertSame('banana', $context->context3);

        // Valid id format but answer doesn't exist
        $_POST['answerID'] = '234231';
        $context = (object) array();
        $this->assertFalse($method($context));
        $this->assertIdentical($untouched, $context);

        unset($_POST['answerID']);
    }

    function testAttachmentView() {
        $method = $this->getMethod('attachmentView');

        // No-op base case
        $context = (object) array();
        $method($context);
        $this->assertIdentical((object) array(), $context);

        list(
            $class,
            $method
        ) = $this->reflect('method:attachmentView');

        // Spoof params
        $CI = $this->CI();
        $CI->uri->setReturnValue('segment_array', array('foo', 'bar', 'baz', '12'));

        $instance = $class->newInstanceArgs(array($CI));

        // Context grabs the fattach id
        $method->invoke($instance, $context);
        $this->assertIdentical((object) array('context1' => '12'), $context);

        // Strict equals redirect param check
        $CI->uri->setReturnValue('uri_to_assoc', array('redirect' => '1'));
        $instance = $class->newInstanceArgs(array($CI));
        $context = (object) array();
        $method->invoke($instance, $context);
        $this->assertIdentical((object) array('context1' => '12', 'context2' => 1), $context);
        $CI = $this->CI();
        $CI->uri->setReturnValue('uri_to_assoc', array('redirect' => 1));
        $CI->uri->setReturnValue('segment_array', array());
        $instance = $class->newInstanceArgs(array($CI));
        $context = (object) array();
        $method->invoke($instance, $context);
        $this->assertIdentical((object) array(), $context);
    }

    function testOpenSearch() {
        $originalQueryString = $_SERVER['QUERY_STRING'];

        list(
            $class,
            $method
        ) = $this->reflect('method:openSearch');
        $instance = $class->newInstance();

        // No-op for empty query string
        $_SERVER['QUERY_STRING'] = '';
        $context = (object) array();
        $action = null;
        $args = array(&$action, $context);
        $method->invokeArgs($instance, $args);

        $this->assertNull($action);
        $this->assertIdentical((object) array(), $context);

        // Q is honored before KW
        $_SERVER['QUERY_STRING'] = 'kw=no&q=yes';
        $context = (object) array();
        $action = '';
        $args = array(&$action, $context);
        $method->invokeArgs($instance, $args);

        $this->assertSame("/Search", $action);
        $this->assertIdentical((object) array('context1' => 'yes'), $context);

        // But KW is also honored
        $_SERVER['QUERY_STRING'] = 'kw=yes';
        $context = (object) array();
        $action = '';
        $args = array(&$action, $context);
        $method->invokeArgs($instance, $args);

        $this->assertSame("/Search", $action);
        $this->assertIdentical((object) array('context1' => 'yes'), $context);

        // context1 is url decoded
        $_SERVER['QUERY_STRING'] = 'kw=no+yes';
        $context = (object) array();
        $action = '';
        $args = array(&$action, $context);
        $method->invokeArgs($instance, $args);

        $this->assertSame("/Search", $action);
        $this->assertIdentical((object) array('context1' => 'no yes'), $context);

        // Only paging is counted
        $_SERVER['QUERY_STRING'] = 'startIndex=1267&kw=no&q=yes';
        $context = (object) array();
        $action = '';
        $args = array(&$action, $context);
        $method->invokeArgs($instance, $args);

        $this->assertSame("/Page", $action);
        $this->assertIdentical((object) array('context1' => 'yes'), $context);

        // P and C are only counted if values are present
        $_SERVER['QUERY_STRING'] = 'kw=yes&p=&c=';
        $context = (object) array();
        $action = '';
        $args = array(&$action, $context);
        $method->invokeArgs($instance, $args);

        $this->assertSame("/Search", $action);
        $this->assertIdentical((object) array('context1' => 'yes'), $context);

        $_SERVER['QUERY_STRING'] = 'kw=yes&p=1,2,3&c=64';
        $context = (object) array();
        $action = '';
        $args = array(&$action, $context);
        $method->invokeArgs($instance, $args);

        $this->assertSame("/Search", $action);
        $this->assertIdentical((object) array('context1' => 'yes', 'context2' => '1,2,3', 'context3' => '64'), $context);

        // Action appends
        $_SERVER['QUERY_STRING'] = 'q=yes';
        $context = (object) array();
        $action = 'bananas';
        $args = array(&$action, $context);
        $method->invokeArgs($instance, $args);

        $this->assertSame("bananas/Search", $action);
        $this->assertIdentical((object) array('context1' => 'yes'), $context);


        $_SERVER['QUERY_STRING'] = $originalQueryString;
    }

    function testDocument() {
        list(
            $class,
            $controllerClass,
            $controllerFunction,
            $method
        ) = $this->reflect('controllerClassName', 'controllerFunctionName', 'method:document');
        $instance = $class->newInstance();

        $controllerClass->setValue($instance, 'banana');
        $controllerFunction->setValue($instance, 'no');

        $context = (object) array();

        $args = array(&$action, $context, &$app, &$contactID);
        $this->assertIdentical(CS_APP_MA, $method->invokeArgs($instance, $args));
        $this->assertIdentical((object) array('context1' => null), $context);
        $this->assertNull($contactID);
    }

    function testAnswerObserved() {
        $method = $this->getMethod('answerObserved');

        $context = (object) array();
        $untouched = (object) array();

        $this->assertNull($method($context));
        $this->assertIdentical($untouched, $context);
        $this->assertNull($method($context, false));
        $this->assertIdentical($untouched, $context);

        // Post accepts a_id
        $_POST['a_id'] = '52';
        $this->assertIdentical('52', $method($context));
        $this->assertIdentical('52', $context->context1);
        $this->assertIsA($context->context2, 'string');

        // Post accepts answerID
        unset($_POST['a_id']);
        $_POST['answerID'] = '52';
        $context = (object) array();
        $this->assertIdentical('52', $method($context));
        $this->assertIdentical('52', $context->context1);
        $this->assertIsA($context->context2, 'string');

        // Valid id format but answer doesn't exist
        $_POST['answerID'] = '234231';
        $context = (object) array();
        $this->assertNull($method($context));
        $this->assertIdentical($untouched, $context);
        $this->assertNull($method($context, false));
        $this->assertIdentical($untouched, $context);

        unset($_POST['answerID']);
    }

    function testGetAnswerSummary() {
        $method = $this->getMethod('getAnswerSummary');

        $this->assertNull($method(232123));
        $this->assertNull($method(52e23));
        $this->assertNull($method('bananas'));
        $this->assertIsA($method('52'), 'string');
        $this->assertIsA($method(52), 'string');

        list(
            $class,
            $method
        ) = $this->reflect('method:getAnswerSummary');

        // Second param to Answer model needs to specify not to escape the summary.
        $CI = $this->CI(false);
        $model = new \RightNow\Models\MockAnswer;
        $model->expectOnce('getAnswerSummary', array(52, false, false));
        $CI->returns('model', new \RightNow\Models\MockClickstream, array('Clickstream'));
        $CI->returns('model', $model, array('Answer'));
        $instance = $class->newInstanceArgs(array($CI));
        $method->invoke($instance, 52);
    }

    function testBuildAction() {
        list($class, $controllerClass, $buildAction) = $this->reflect('controllerClassName', 'method:buildAction');
        $instance = $class->newInstance();

        $controllerClass->setValue($instance, 'Banana');

        $this->assertSame('/Banana/nope', $buildAction->invoke($instance, 'nope'));
        $this->assertSame('/Banana/', $buildAction->invoke($instance, null));
        $this->assertSame('/Banana/0', $buildAction->invoke($instance, 0));
    }

    function testShouldTrackSession() {
        list(
            $class,
            $controllerClass,
            $controllerFunction,
            $useClickstream,
            $shouldTrackSession
        ) = $this->reflect('controllerClassName', 'controllerFunctionName', 'useClickstream', 'method:shouldTrackSession');
        $instance = $class->newInstance();

        // useClickstream should be false by default
        $this->assertFalse($shouldTrackSession->invoke($instance, 'foo', 'bar'));

        // Honors useClickstream
        $useClickstream->setValue($instance, true);
        $this->assertTrue($shouldTrackSession->invoke($instance, 'foo', 'bar'));

        // Honors tag gallery
        $referer = $_SERVER['HTTP_REFERER'];
        $_SERVER['HTTP_REFERER'] = 'blah/ci/admin/docs/syndicatedWidgets';
        $this->assertFalse($shouldTrackSession->invoke($instance, 'foo', 'bar'));
        $_SERVER['HTTP_REFERER'] = $referer;

        // Honors page / facebook special case...
        $controllerClass->setValue($instance, 'facebook');
        $controllerFunction->setValue($instance, 'render');
        $this->assertFalse($shouldTrackSession->invoke($instance, 'normal', 'bar'));
        $controllerClass->setValue($instance, 'page');
        $this->assertFalse($shouldTrackSession->invoke($instance, 'normal', 'bar'));

        // ...But not case-insensitively
        $controllerClass->setValue($instance, 'Page');
        $this->assertTrue($shouldTrackSession->invoke($instance, 'normal', 'bar'));
        $controllerClass->setValue($instance, 'Facebook');
        $this->assertTrue($shouldTrackSession->invoke($instance, 'normal', 'bar'));

        // Honors ajax controller when onDemandAction is empty string
        $controllerClass->setValue($instance, 'ajax');
        $this->assertFalse($shouldTrackSession->invoke($instance, 'normal', ''));
        $this->assertTrue($shouldTrackSession->invoke($instance, 'normal', 'bar'));
        $this->assertTrue($shouldTrackSession->invoke($instance, 'normal', null));

        $_SERVER["HTTP_USER_AGENT"] = 'RNT_SITE_MONITOR';
        $this->assertFalse($shouldTrackSession->invoke($instance, 'foo', 'bar'));

    }

    function testDetermineSessionHandling() {
        list(
            $class,
            $controllerClassName,
            $controllerFunctionName,
            $method,
        ) = $this->reflect('controllerClassName', 'controllerFunctionName', 'method:determineSessionHandling');

        $instance = $class->newInstance();

        // Base
        $createSession = null;
        $useFakeSession = null;
        $args = array(&$createSession, &$useFakeSession);
        $this->assertFalse($method->invokeArgs($instance, $args));
        $this->assertTrue($createSession);
        $this->assertFalse($useFakeSession);

        // AjaxRequestMin
        $createSession = null;
        $useFakeSession = null;
        $args = array(&$createSession, &$useFakeSession);
        $controllerClassName->setValue($instance, 'ajaxrequestmin');
        $this->assertFalse($method->invokeArgs($instance, $args));
        $this->assertTrue($createSession);
        $this->assertFalse($useFakeSession);

        // Pta logout
        $createSession = null;
        $useFakeSession = null;
        $args = array(&$createSession, &$useFakeSession);
        $controllerClassName->setValue($instance, 'pta');
        $controllerFunctionName->setValue($instance, 'LOGOUT');
        $this->assertFalse($method->invokeArgs($instance, $args));
        $this->assertTrue($createSession);
        $this->assertFalse($useFakeSession);

        // Ignored standard routes
        $createSession = null;
        $useFakeSession = null;
        $args = array(&$createSession, &$useFakeSession);
        $controllerClassName->setValue($instance, 'ajaxrequest');
        $controllerFunctionName->setValue($instance, 'getChatQueueAndInformation');
        $this->assertFalse($method->invokeArgs($instance, $args));
        $this->assertFalse($createSession);
        $this->assertFalse($useFakeSession);

        //@@@ QA 130409-000021
        $createSession = null;
        $useFakeSession = null;
        $args = array(&$createSession, &$useFakeSession);
        $controllerClassName->setValue($instance, 'cache');
        $controllerFunctionName->setValue($instance, 'rss');
        $this->assertFalse($method->invokeArgs($instance, $args));
        $this->assertTrue($createSession);
        $this->assertTrue($useFakeSession);

        // Ignored controllers
        $createSession = null;
        $useFakeSession = null;
        $args = array(&$createSession, &$useFakeSession);
        $controllerClassName->setValue($instance, 'answerpreview');
        $this->assertFalse($method->invokeArgs($instance, $args));
        $this->assertFalse($createSession);
        $this->assertFalse($useFakeSession);
        $controllerClassName->setValue($instance, 'browsersearch');
        $this->assertFalse($method->invokeArgs($instance, $args));
        $this->assertFalse($createSession);
        $this->assertFalse($useFakeSession);
        $controllerClassName->setValue($instance, 'dqa');
        $this->assertFalse($method->invokeArgs($instance, $args));
        $this->assertFalse($createSession);
        $this->assertFalse($useFakeSession);
        $controllerClassName->setValue($instance, 'inlineimage');
        $this->assertFalse($method->invokeArgs($instance, $args));
        $this->assertFalse($createSession);
        $this->assertFalse($useFakeSession);
        $controllerClassName->setValue($instance, 'inlineimg');
        $this->assertFalse($method->invokeArgs($instance, $args));
        $this->assertFalse($createSession);
        $this->assertFalse($useFakeSession);
        $controllerClassName->setValue($instance, 'redirect');
        $this->assertFalse($method->invokeArgs($instance, $args));
        $this->assertFalse($createSession);
        $this->assertFalse($useFakeSession);
        $controllerClassName->setValue($instance, 'webdav');
        $this->assertFalse($method->invokeArgs($instance, $args));
        $this->assertFalse($createSession);
        $this->assertFalse($useFakeSession);

        // Admin
        $requestURI = $_SERVER['REQUEST_URI'];
        $_SERVER['REQUEST_URI'] = '/ci/admin/bananas';
        $controllerClassName->setValue($instance, 'bananas');
        $this->assertFalse($method->invokeArgs($instance, $args));
        $this->assertFalse($createSession);
        $this->assertFalse($useFakeSession);

        // docs controller (?)
        $controllerClassName->setValue($instance, 'docs');
        $this->assertFalse($method->invokeArgs($instance, $args));
        $this->assertTrue($createSession);
        $this->assertTrue($useFakeSession);

        $_SERVER['REQUEST_URI'] = $requestURI;

        // documents controller, without the ma encoded thingy
        $controllerClassName->setValue($instance, 'documents');
        $this->assertFalse($method->invokeArgs($instance, $args));
        $this->assertTrue($createSession);
        $this->assertFalse($useFakeSession);

        $request_method = $_SERVER['REQUEST_METHOD'];
        $_SERVER['REQUEST_METHOD'] = 'OPTIONS';

        // ignored OPTIONS request for oit/getconfigs endpoint
        $createSession = null;
        $useFakeSession = null;
        $args = array(&$createSession, &$useFakeSession);
        $controllerClassName->setValue($instance, 'oit');
        $controllerFunctionName->setValue($instance, 'getConfigs');
        $this->assertFalse($method->invokeArgs($instance, $args));
        $this->assertFalse($createSession);
        $this->assertFalse($useFakeSession);

        // ignored OPTIONS request for oit/authenticateChat endpoint
        $createSession = null;
        $useFakeSession = null;
        $args = array(&$createSession, &$useFakeSession);
        $controllerClassName->setValue($instance, 'oit');
        $controllerFunctionName->setValue($instance, 'authenticateChat');
        $this->assertFalse($method->invokeArgs($instance, $args));
        $this->assertFalse($createSession);
        $this->assertFalse($useFakeSession);

        // ignored OPTIONS request for oit/fileUpload endpoint
        $createSession = null;
        $useFakeSession = null;
        $args = array(&$createSession, &$useFakeSession);
        $controllerClassName->setValue($instance, 'oit');
        $controllerFunctionName->setValue($instance, 'fileUpload');
        $this->assertFalse($method->invokeArgs($instance, $args));
        $this->assertFalse($createSession);
        $this->assertFalse($useFakeSession);

        // ignored OPTIONS request for api/v1 endpoint
        $createSession = null;
        $useFakeSession = null;
        $args = array(&$createSession, &$useFakeSession);
        $controllerClassName->setValue($instance, 'api');
        $controllerFunctionName->setValue($instance, 'v1');
        $this->assertFalse($method->invokeArgs($instance, $args));
        $this->assertFalse($createSession);
        $this->assertFalse($useFakeSession);

        $_SERVER['REQUEST_METHOD'] = $request_method;
    }
}

class ClickstreamActionMappingTest extends CPTestCase {
    private $types = array(
        'answer' => array(
            'answer_list',
            'answer_view',
            'answer_rating',
            'answer_preview',
            'answer_print',
        ),
        'notification' => array(
            'answer_notification_update',
            'answer_notification_delete',
            'product_category_notification_update',
            'product_category_notification_delete',
        ),
        'incident' => array(
            'incident_submit',
            'incident_create_smart',
            'incident_view',
            'incident_print',
            'incident_update',
        ),
        'account' => array(
            'account_create',
            'account_update',
            'account_login',
            'account_logout',
        ),
        'chat'         => array('chat_request', 'chat_landing'),
        'attachment'   => array('attachment_view', 'attachement_upload'),
        'feedback'     => array('answer_feedback', 'site_feedback'),
        'ask'          => array('incident_create', 'incident_confirm'),
        'notify'       => array('answer_notification', 'product_category_notification'),
        'questionList' => array('incident_list'),
        'related'      => array('answer_view_related'),
        'home'         => array('home'),
        'email'        => array('email_answer'),
        'search'       => array('search'),
        'page'         => array('paging'),
        'openSearch'   => array('opensearch_service'),
        'reportData'   => array('report_data_service'),
    );

    function verifyReturn($actual, $containsExpected, $action) {
        $this->assertIsA($actual, 'string');
        $this->assertTrue(Text::stringContains($actual, $containsExpected), "For $action, $actual doesn't contain $containsExpected");
    }

    function testUnexpectedInputPassesThru() {
        $this->assertNull(Hooks\ClickstreamActionMapping::getAction(null));
        $this->assertIdentical(0, Hooks\ClickstreamActionMapping::getAction(0));
        $this->assertIdentical(1, Hooks\ClickstreamActionMapping::getAction(1));
        $this->assertIdentical(array(), Hooks\ClickstreamActionMapping::getAction(array()));
    }

    function testDefaultValue() {
        $this->assertSame('banana', Hooks\ClickstreamActionMapping::getAction('nonono', 'banana'));
        $this->assertSame('banana', Hooks\ClickstreamActionMapping::getAction(456, 'banana'));
        $this->assertSame('banana', Hooks\ClickstreamActionMapping::getAction(null, 'banana'));
        $this->assertIdentical(0, Hooks\ClickstreamActionMapping::getAction(null, 0));
        $this->assertIdentical('', Hooks\ClickstreamActionMapping::getAction(0, ''));
    }

    function testDefaultValueIsNotReturnedWhenAValidActionIsSpecified() {
        $this->assertNotEqual('banana', Hooks\ClickstreamActionMapping::getAction('home', 'banana'));
    }

    function testMappingReturns() {
        foreach ($this->types as $type => $actions) {
            foreach ($actions as $action) {
                $this->verifyReturn(Hooks\ClickstreamActionMapping::getAction($action), ucfirst($type), $action);
            }
        }
    }
}
