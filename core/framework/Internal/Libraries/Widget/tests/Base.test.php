<?php

use RightNow\Internal\Libraries\Widget\Base,
    RightNow\Utils\Text;

RightNow\UnitTest\Helper::loadTestedFile(__FILE__);

class WidgetBaseTestClass extends \RightNow\Libraries\Widget\Base {}

class WidgetBaseTest extends CPTestCase {
    public $testingClass = 'RightNow\Internal\Libraries\Widget';

    function getInstanceData() {
        return array(
            'info' => array(
                'controller_name' => 'SearchButton',
                'widget_name' => 'SearchButton',
                'w_id' => '4',
                'type' => 'the best type',
            ),
            'attrs' => array('potato'),
            'js' => array('dishwasher'),
        );
    }

    function getWigetAjaxTestData() {
        return array(
            array(
                'data'           => 'iamadata',
                'contextData'    => 'iamacontextData',
                'contextToken'   => 'iamacontextToken',
                'timestamp'      => 'iamatimestamp',
                'instanceID'     => 'iamainstanceID',
                'path'           => 'iamajavaScriptPath',
                'className'      => 'iamaclassName',
                'widgetID'       => 'iamasuffix',
                'showWarnings'   => 'iamashowWarnings',
            )
        );
    }

    function getTestingInstanceAndClass() {
        $mockupData = array(
            'w_id' => '4',
            'controller_path' => 'standard/search/SearchButton',
            'view_path' => 'standard/search/SearchButton',
            'js_path' => 'standard/search/SearchButton',
            'extends_js' => array(
                'standard/notifications/DiscussionSubscriptionManager',
                'standard/reports/Multiline'
            ),
            'version' => '1.0.1',
            'requires' => array(
                'framework' => array(
                    '0' => 3.0,
                    '1' => 3.1,
                    '2' => 3.2,
                ),
                'jsModule' => array(
                    '0' => 'standard',
                    '1' => 'mobile',
                ),
            ),
            'info' => array(
                'description' => 'This is a description',
            ),
            'relativePath' => 'standard/search/SearchButton',
            'controller_name' => 'SearchButton',
            'js_name' => 'RightNow.Widgets.SearchButton',
            'widget_name' => 'SearchButton',
            'widget_path' => 'standard/search/SearchButton',
        );

        $instanceData = array(
            'info' => array(
                'controller_name' => 'SearchButton',
                'widget_name' => 'SearchButton',
                'w_id' => '4',
            ),
            'attrs' => array()
        );

        $class = new \ReflectionClass('WidgetBaseTestClass');
        $instance = $class->newInstance(array());

        $clientLoader = new RightNow\Internal\Libraries\ClientLoader(new \RightNow\Internal\Libraries\DeployerClientLoaderOptions());
        $clientLoader->options = new RightNow\Internal\Libraries\ProductionModeClientLoaderOptions();
        $instance->CI->clientLoader = $clientLoader;
        $instance->data = $this->getInstanceData();

        $method = $class->getMethod('setInfo');
        $method->setAccessible(true);

        $result = $method->invoke($instance, $mockupData);
        return array($instance, $class);
    }

    function testWidgetError() {
        $widget = 'standard/input/DateInput';
        $error = 'stuff be broken';
        $template = sprintf('<div><b>Widget %%s: %s - %s</b></div>', $widget, $error);
        $this->assertEqual(sprintf($template, 'Error'), Base::widgetError($widget, $error));
        $this->assertEqual(sprintf($template, 'Warning'), Base::widgetError($widget, $error, false));
        $widget = \RightNow\Internal\Libraries\Widget\Registry::getWidgetPathInfo($widget);
        $this->assertEqual(sprintf($template, 'Warning'), Base::widgetError($widget, $error, false));
    }

    function testValidateAttributeValue() {
        $class = new \ReflectionClass('WidgetBaseTestClass');
        $instance = $class->newInstance(array(
            'banana' => new \RightNow\Libraries\Widget\Attribute(array('name' => 'banana', 'type' => 'STRING', 'description' => 'desc', 'default' => 'defaultBanana')),
        ));
        $method = $class->getMethod('validateAttributeValue');
        $method->setAccessible(true);
        $attrs = $class->getProperty('attrs');
        $attrs->setAccessible(true);

        // Doesn't exist
        $this->assertTrue($method->invokeArgs($instance, array('foo', 'bar')));

        // String validation
        $this->assertTrue($method->invokeArgs($instance, array('banana', 'yeah')));
        $this->assertTrue($method->invokeArgs($instance, array('banana', '0')));
        $this->assertTrue($method->invokeArgs($instance, array('banana', 0)));
        $this->assertIsA($method->invokeArgs($instance, array('banana', true)), 'string');
        $this->assertIsA($method->invokeArgs($instance, array('banana', array())), 'string');

        // Boolean validation
        $attrs->setValue($instance, array(
            'yes' => new \RightNow\Libraries\Widget\Attribute(array('name' => 'yes', 'type' => 'BOOL', 'description' => 'desc', 'default' => false)),
            'no' => new \RightNow\Libraries\Widget\Attribute(array('name' => 'no', 'type' => 'boolean', 'description' => 'desc', 'default' => true)),
        ));
        $this->assertTrue($method->invokeArgs($instance, array('yes', true)));
        $this->assertTrue($method->invokeArgs($instance, array('no', false)));
        $this->assertTrue($method->invokeArgs($instance, array('no', 'false')));
        $this->assertTrue($method->invokeArgs($instance, array('yes', 'TRUE')));
        $this->assertIsA($method->invokeArgs($instance, array('yes', 0)), 'string');
        $this->assertIsA($method->invokeArgs($instance, array('yes', 1)), 'string');
        $this->assertIsA($method->invokeArgs($instance, array('yes', 'string')), 'string');

        // Int validation
        $attrs->setValue($instance, array(
            'yes' => new \RightNow\Libraries\Widget\Attribute(array('name' => 'yes', 'type' => 'int', 'description' => 'desc', 'default' => 0)),
            'no' => new \RightNow\Libraries\Widget\Attribute(array('name' => 'no', 'type' => 'Integer', 'description' => 'desc', 'default' => 1)),
        ));
        $this->assertTrue($method->invokeArgs($instance, array('yes', 1)));
        $this->assertTrue($method->invokeArgs($instance, array('no', 3432)));
        $this->assertTrue($method->invokeArgs($instance, array('no', '23')));
        $this->assertTrue($method->invokeArgs($instance, array('yes', '+0123.45e6')));
        $this->assertIsA($method->invokeArgs($instance, array('yes', 'string')), 'string');
        $this->assertIsA($method->invokeArgs($instance, array('yes', array())), 'string');
        $this->assertIsA($method->invokeArgs($instance, array('yes', '23zser')), 'string');

        // Option validation
        $attrs->setValue($instance, array(
            'no' => new \RightNow\Libraries\Widget\Attribute(array('name' => 'no', 'type' => 'option', 'description' => 'desc', 'default' => 'foo', 'options' => array('foo', 'bar', 'baz'))),
            'yes' => new \RightNow\Libraries\Widget\Attribute(array('name' => 'yes', 'type' => 'option', 'description' => 'desc', 'default' => 1, 'options' => array(1, 2, 3))),
        ));
        $this->assertTrue($method->invokeArgs($instance, array('yes', 1)));
        $this->assertTrue($method->invokeArgs($instance, array('yes', 2)));
        $this->assertTrue($method->invokeArgs($instance, array('yes', '1')));
        $this->assertTrue($method->invokeArgs($instance, array('no', 'foo')));
        $this->assertTrue($method->invokeArgs($instance, array('no', 'baz')));
        $this->assertIsA($method->invokeArgs($instance, array('no', '23')), 'string');
        $this->assertIsA($method->invokeArgs($instance, array('yes', 'string')), 'string');
        $this->assertIsA($method->invokeArgs($instance, array('yes', '23zser')), 'string');

        // Multioption validation
        $attrs->setValue($instance, array(
            'no' => new \RightNow\Libraries\Widget\Attribute(array('name' => 'no', 'type' => 'multioption', 'description' => 'desc', 'default' => 'foo', 'options' => array('foo', 'bar', 'baz'))),
            'yes' => new \RightNow\Libraries\Widget\Attribute(array('name' => 'yes', 'type' => 'multioption', 'description' => 'desc', 'default' => 1, 'options' => array(1, 2, 3))),
            'empty' => new \RightNow\Libraries\Widget\Attribute(array('name' => 'yes', 'type' => 'multioption', 'description' => 'desc', 'default' => array(), 'options' => array('', 'a'))),
        ));
        $this->assertTrue($method->invokeArgs($instance, array('yes', 1)));
        $this->assertTrue($method->invokeArgs($instance, array('yes', 2)));
        $this->assertTrue($method->invokeArgs($instance, array('yes', '1')));
        $this->assertTrue($method->invokeArgs($instance, array('yes', '1,2')));
        $this->assertTrue($method->invokeArgs($instance, array('yes', '3,1,2')));
        $this->assertTrue($method->invokeArgs($instance, array('yes', '    1   ,    2     ')));
        $this->assertTrue($method->invokeArgs($instance, array('no', 'foo')));
        $this->assertTrue($method->invokeArgs($instance, array('no', 'baz')));
        $this->assertTrue($method->invokeArgs($instance, array('no', 'baz, foo, bar')));
        $this->assertTrue($method->invokeArgs($instance, array('no', 'bar, baz')));
        $this->assertTrue($method->invokeArgs($instance, array('no', 'bAR, bAz')));
        $this->assertTrue($method->invokeArgs($instance, array('empty', '')));
        $this->assertTrue($method->invokeArgs($instance, array('empty', null)));
        $this->assertTrue($method->invokeArgs($instance, array('empty', false)));
        $this->assertIsA($method->invokeArgs($instance, array('no', '')), 'string');
        $this->assertIsA($method->invokeArgs($instance, array('no', '23')), 'string');
        $this->assertIsA($method->invokeArgs($instance, array('no', 'foo, bar, stuff')), 'string');
        $this->assertIsA($method->invokeArgs($instance, array('no', 'foo,,, bar')), 'string');
        $this->assertIsA($method->invokeArgs($instance, array('no', ',foo, bar,')), 'string');
        $this->assertIsA($method->invokeArgs($instance, array('yes', '')), 'string');
        $this->assertIsA($method->invokeArgs($instance, array('yes', '6')), 'string');
        $this->assertIsA($method->invokeArgs($instance, array('yes', '1,2,3,4')), 'string');
    }

    function testDoesAttributeValueMeetDataRequirements(){
        $class = new \ReflectionClass('WidgetBaseTestClass');
        $instance = $class->newInstance(array(
            'banana' => new \RightNow\Libraries\Widget\Attribute(array('name' => 'banana', 'type' => 'STRING', 'description' => 'desc', 'default' => 'defaultBanana')),
        ));
        $method = $class->getMethod('doesAttributeValueMeetDataRequirements');
        $method->setAccessible(true);
        $attrs = $class->getProperty('attrs');
        $attrs->setAccessible(true);

        // Min & max
        $attrs->setValue($instance, array(
            'aint' => new \RightNow\Libraries\Widget\Attribute(array('name' => 'aint', 'type' => 'int', 'description' => 'desc', 'default' => 1, 'min' => 1, 'max' => 3)),
        ));
        $this->assertTrue($method->invokeArgs($instance, array('aint', 2)));
        $this->assertTrue($method->invokeArgs($instance, array('aint', 1)));
        $this->assertIsA($method->invokeArgs($instance, array('aint', 0)), 'string');
        $this->assertIsA($method->invokeArgs($instance, array('aint', 4)), 'string');

        // Required
        $attrs->setValue($instance, array(
            'abool'   => new \RightNow\Libraries\Widget\Attribute(array('name' => 'abool', 'type' => 'BOOL', 'description' => 'desc', 'default' => false, 'required' => true)),
            'astring' => new \RightNow\Libraries\Widget\Attribute(array('name' => 'astring', 'type' => 'string', 'description' => 'desc', 'default' => 'true', 'required' => true)),
            'aint'    => new \RightNow\Libraries\Widget\Attribute(array('name' => 'aint', 'type' => 'int', 'description' => 'desc', 'default' => 1, 'required' => true)),
            'aoption' => new \RightNow\Libraries\Widget\Attribute(array('name' => 'aoption', 'type' => 'option', 'description' => 'desc', 'default' => 1, 'options' => array(1, 2, 3), 'required' => true)),
            'boption' => new \RightNow\Libraries\Widget\Attribute(array('name' => 'aoption', 'type' => 'multiopion', 'description' => 'desc', 'default' => array(1, 2), 'options' => array(1, 2, 3), 'required' => true)),
        ));
        $this->assertIsA($method->invokeArgs($instance, array('abool', '')), 'string');
        $this->assertIsA($method->invokeArgs($instance, array('abool', null)), 'string');
        $this->assertIsA($method->invokeArgs($instance, array('astring', '')), 'string');
        $this->assertIsA($method->invokeArgs($instance, array('astring', null)), 'string');
        $this->assertIsA($method->invokeArgs($instance, array('aint', '')), 'string');
        $this->assertIsA($method->invokeArgs($instance, array('aint', null)), 'string');
        $this->assertIsA($method->invokeArgs($instance, array('aoption', '')), 'string');
        $this->assertIsA($method->invokeArgs($instance, array('aoption', null)), 'string');
        $this->assertIsA($method->invokeArgs($instance, array('boption', '')), 'string');
        $this->assertIsA($method->invokeArgs($instance, array('boption', null)), 'string');
        $this->assertIsA($method->invokeArgs($instance, array('boption', array())), 'string');
    }

    function createWidgetForPartials() {
        $path = 'standard/feedback/AnswerFeedback';
        $widgetPath = \RightNow\Utils\Widgets::getFullWidgetVersionDirectory($path);
        require_once CORE_WIDGET_FILES . $widgetPath . 'controller.php';
        $widget = new \RightNow\Widgets\AnswerFeedback(array());
        $widget->path = $path;
        return $widget;
    }

    function createWidgetForPartialsWithAttributes() {
        $path = 'standard/discussion/QuestionComments';
        $widgetPath = \RightNow\Utils\Widgets::getFullWidgetVersionDirectory($path);
        require_once CORE_WIDGET_FILES . $widgetPath . 'controller.php';
        require_once CORE_FILES. "framework/Helpers/Social.php";
        $ratingTypeAttribute = new \RightNow\Libraries\Widget\Attribute(array('name' => 'rating_type', 'type' => 'option', 'description' => 'desc', 'default' => "star", 'options' => array("upvote","star"), 'required' => true));
        $widget = new \RightNow\Widgets\QuestionComments(array("rating_type" => $ratingTypeAttribute));
        $widget->path = $path;
        $widget->setHelper();
        $widget->helper->Social = new \RightNow\Helpers\SocialHelper();
        return $widget;
    }

    function testRenderPartialErrors() {
        $widget = $this->createWidgetForPartials();
        $this->assertFalse($widget->render('buttonView', array('this' => 'is')));
        $this->assertFalse($widget->render('buttonView', array('dontCollideVarNameViewContent' => 'is')));
        $this->assertFalse($widget->render('bananas', array()));
        $this->assertFalse($widget->render(null, array()));
        $this->assertFalse($widget->render(array(), array()));
    }

    function testRenderWithWidgetPartial() {
        $widget = $this->createWidgetForPartials();
        $content = $widget->render('buttonView');
        $this->assertIsA($content, 'string');
        $this->assertStringDoesNotContain($content, 'rn:block');
    }

    function testRenderWithWidgetPartialAndAttributes() {
        $this->fixtureInstance = new RightNow\UnitTest\Fixture();
        $question = $this->fixtureInstance->make('QuestionActiveAuthorBestAnswer');
        $comments = $this->CI->model('CommunityQuestion')->getComments($question)->result;
        $widget = $this->createWidgetForPartialsWithAttributes();
        //Content Flagging widget throws coud not find helper Social exception. Excluding flagging widget for now with this testcase
        $widget->data["attrs"] = array("best_answer_types" => array("author", "none"), "paginate_comments_position" => array("top", "bottom"), "sub:commentRating:rating_type" => "star", "sub:commentFlag:content_type" => "doNotIncludeFlagging");
        $widget->data["author_roleset_callout"] = array("5" => "Posted by a moderator");
        $widget->question = $question;
        $widget->helper->question = $question;
        $widget->helper->bestAnswerTypes = array(
            'author'    => SSS_BEST_ANSWER_AUTHOR,
            'moderator' => SSS_BEST_ANSWER_MODERATOR,
            'community' => SSS_BEST_ANSWER_COMMUNITY,
        );
        $this->CI->clientLoader = new \RightNow\Libraries\ClientLoader(new \RightNow\Internal\Libraries\DevelopmentModeClientLoaderOptions());
        $content = $widget->render('CommentList', array('comments' => $comments, 'questionID' => $question->ID));
        $this->assertIsA($content, 'string');
        $this->assertStringContains($content, "rn_StarVoting");
        $this->fixtureInstance->destroy();
    }

    function testRenderWithSharedPartial() {
        $widget = $this->createWidgetForPartials();
        $content = $widget->render('Partials.Forms.RequiredLabel');
        $this->assertIsA($content, 'string');
        $this->assertStringContains($content, 'Required');
        $this->assertStringContains($content, 'aria-label');
    }

    function testSetWidgetInstance() {
        list($instance, $class) = $this->getTestingInstanceAndClass();

        $method = $class->getMethod('setWidgetInstance');
        $method->setAccessible(true);

        $result = $method->invokeArgs($instance, array('production'));

        $this->assertIsA($result, 'array');

        foreach(array('data', 'instanceID', 'path', 'className', 'widgetID', 'static', 'contextData', 'contextToken', 'timestamp') as $requiredKey) {
            $this->assertTrue(array_key_exists($requiredKey, $result));
        }
        $this->assertEqual(0, count($instance->CI->clientLoader->javaScriptFiles->getDependencySortedWidgetJavaScriptFiles()));

        $result = $method->invokeArgs($instance, array('development'));
        $sortedFiles = $instance->CI->clientLoader->javaScriptFiles->getDependencySortedWidgetJavaScriptFiles();
        $this->assertEqual(2, count($sortedFiles));
        $this->assertTrue(Text::endsWith($sortedFiles[0], 'standard/reports/Multiline/logic.js'));
        $this->assertTrue(Text::endsWith($sortedFiles[1], 'standard/notifications/DiscussionSubscriptionManager/logic.js'));
    }

    function testGetAjaxContent() {
        list($instance, $class) = $this->getTestingInstanceAndClass();

        $method = $class->getMethod('getAjaxContent');
        $method->setAccessible(true);

        $result = $method->invokeArgs($instance, $this->getWigetAjaxTestData());
        $this->assertNotNull($result);

        $this->assertStringContains($result, "<script type='text/json'>");

        $result = Text::getSubstringAfter($result, "<script type='text/json'>");
        $result = Text::getSubStringBefore($result, "</script>");
        $result = json_decode($result);

        $this->assertNotNull($result);
        $this->assertTrue(is_object($result));
        foreach(array('data', 'contextData', 'contextToken', 'timestamp', 'instanceID', 'javaScriptPath', 'className', 'suffix', 'showWarnings') as $requiredKey) {
            $this->assertTrue($result->$requiredKey === 'iama' . $requiredKey);
        }
    }

    function testFormatWidgetData() {
        $class = new \ReflectionClass('WidgetBaseTestClass');
        $instance = $class->newInstance(array());

        $method = $class->getMethod('formatWidgetData');
        $method->setAccessible(true);

        $expectedResult = array(
            'i' => array(
                'c' => 'SearchButton',
                'n' => 'SearchButton',
                'w' => '4',
                't' => 'the best type',
            ),
            'a' => array('potato'),
            'j' => array('dishwasher'),
        );

        $result = $method->invoke($instance, $this->getInstanceData());
        $this->assertSame($result, $expectedResult);
    }
}
