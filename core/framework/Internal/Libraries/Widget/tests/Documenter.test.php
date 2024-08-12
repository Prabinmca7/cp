<?php

use RightNow\Utils\Text,
    RightNow\Utils\Widgets,
    RightNow\Internal\Libraries\Widget\Documenter,
    RightNow\Internal\Libraries\Widget\Registry;

RightNow\UnitTest\Helper::loadTestedFile(__FILE__);

class DocumenterTest extends CPTestCase {

    function getPrivate($method) {
        return \RightNow\UnitTest\Helper::getMethodInvoker('\RightNow\Internal\Libraries\Widget\Documenter', $method);
    }

    function testGetWidgetDetails() {
        $widget = Registry::getWidgetPathInfo('standard/Utils/AnnouncementText');
        $results = Documenter::getWidgetDetails($widget);
        $this->assertIsA($results, 'array');
        $this->assertIsA($results['controllerLocation'], 'string');
        $this->assertIsA($results['controllerClass'], 'string');
        $this->assertIsA($results['attributes'], 'array');
        $this->assertIsA($results['info'], 'array');
        $this->assertIsA($results['info']['description'], 'string');
        $this->assertIsA($results['info']['notes'], 'string');
        $this->assertIsA($results['events'], 'array');
        $this->assertIsA($results['previewFiles'], 'array');
        $this->assertIsA($results['containingWidgets'], 'array');
        $this->assertSame(1, count($results['previewFiles']));
        $this->assertSame(0, count($results['containingWidgets']));
    }

    function testGetWidgetAttributes() {
        $widget = Registry::getWidgetPathInfo('standard/Utils/AnnouncementText');
        $results = Documenter::getWidgetAttributes($widget);
        $this->assertIsA($results, 'array');
        foreach ($results as $key => $attr) {
            $this->assertIsA($attr, 'RightNow\Libraries\Widget\Attribute');
            $this->assertFalse(Text::stringContains($attr->description, 'rn:msg:'));
        }

        $widget = Registry::getWidgetPathInfo('standard/Utils/AnnouncementText');
        $results = Documenter::getWidgetAttributes($widget, true);
        $this->assertIsA($results, 'array');
        foreach ($results as $key => $attr) {
            $this->assertIsA($attr, 'array');
            $this->assertTrue(Text::stringContains($attr['description'], 'rn:msg:') || Text::stringContains($attr['description'], 'rn:msg:ELLIPSIS_MSG'));
        }
    }

    function testGetWidgetInfo() {
        // Doesn't have url params
        $widget = Registry::getWidgetPathInfo('standard/Utils/AnnouncementText');
        $results = Documenter::getWidgetInfo($widget, 'urlParameters');
        $this->assertNull($results);

        // rn: tags are converted
        $widget = Registry::getWidgetPathInfo('standard/feedback/AnswerFeedback');
        $results = Documenter::getWidgetInfo($widget, 'urlParameters');
        $this->assertIsA($results, 'array');
        foreach ($results as $key => $param) {
            $this->assertIsA($param, 'array');
            $this->assertFalse(Text::stringContains($param['description'], 'rn:msg:'));
        }

        // raw params
        $results = Documenter::getWidgetInfo($widget, 'urlParameters', true);
        $this->assertIsA($results, 'array');
        foreach ($results as $key => $param) {
            $this->assertIsA($param, 'array');
            $this->assertTrue(Text::stringContains($param['description'], 'rn:msg:'));
        }

        // No key
        $results = Documenter::getWidgetInfo($widget);
        $this->assertIsA($results, 'array');
        $this->assertTrue(array_key_exists('description', $results));
        $this->assertTrue(array_key_exists('urlParameters', $results));

        // Key, raw is no-op because urlParameters isn't the key
        $results = Documenter::getWidgetInfo($widget, 'description', true);
        $this->assertIsA($results, 'string');
        $this->assertTrue(Text::stringContains($results, 'rn:'));

        // Key doesn't exist
        $results = Documenter::getWidgetInfo($widget, 'bananas');
        $this->assertNull($results);
    }

    function testGetWidgetRequirements() {
        $widget = Registry::getWidgetPathInfo('standard/feedback/AnswerFeedback');

        // No key
        $results = Documenter::getWidgetRequirements($widget);
        $this->assertIsA($results, 'array');
        $this->assertTrue(array_key_exists('framework', $results));
        $this->assertTrue(array_key_exists('jsModule', $results));

        // Key doesn't exist
        $results = Documenter::getWidgetRequirements($widget, 'bananas');
        $this->assertNull($results);

        // Key exists
        $results = Documenter::getWidgetRequirements($widget, 'framework');
        $this->assertIsA($results, 'array');
        $this->assertFalse(array_key_exists('framework', $results));
        $this->assertFalse(array_key_exists('jsModule', $results));
    }

    function testBuildWidgetDetails() {
        $method = $this->getPrivate('buildWidgetDetails');

        $widget = Registry::getWidgetPathInfo('standard/utils/AnnouncementText');
        $results = $method(array(), $widget);

        $this->assertIdentical(
            array('controllerLocation', 'controllerClass', 'attributes', 'events', 'previewFiles', 'containingWidgets'),
            array_keys($results)
        );

        $widget = Registry::getWidgetPathInfo('standard/utils/AnnouncementText');
        $results = $method(array('attributes' => array()), $widget);

        $this->assertIdentical(
            array('controllerLocation', 'controllerClass', 'attributes', 'events', 'previewFiles', 'containingWidgets'),
            array_keys($results)
        );

        $results = $method(array('attributes' => array()), $widget, array('events' => false, 'previewFiles' => false));

        $this->assertIdentical(
            array('controllerLocation', 'controllerClass', 'attributes', 'containingWidgets'),
            array_keys($results)
        );
    }

    function testGetSubWidgetDetailsIncludesWidgetsInViewPartials() {
        $method = $this->getPrivate('getSubWidgetDetails');

        $widget = Registry::getWidgetPathInfo('custom/viewpartialtest/WidgetsInViewPartials');
        $metaInfo = Widgets::getWidgetInfo($widget);
        $metaInfo = Widgets::convertAttributeTagsToValues($metaInfo, array('validate' => true, 'eval' => true));
        $metaInfo = Widgets::convertUrlParameterTagsToValues($metaInfo);

        $result = $method($widget, $metaInfo);

        $this->assertSame(2, count($result));
        $expectedSubWidgetKeys = array('file', 'path', 'description', 'match', 'attributes', 'matchedPath');
        $this->assertIdentical($expectedSubWidgetKeys, array_keys($result[0]));
        $this->assertIdentical($expectedSubWidgetKeys, array_keys($result[1]));
    }

    function testGetWidgetPieces() {
        $method = $this->getPrivate('getWidgetPieces');

        // No widget info sent in, should default to blank controller
        $this->assertIdentical(array('controllerLocation' => 'standard/utils/Blank/controller.php'), $method(array()));

        // Widget info lists multiple extended controllers that don't exist. Specifies a controller_path that does not exist on disk
        $input = array(
            'extends_info' => array(
                'controller' => array('foo', 'bar'),
                'logic' => array('foo', 'bar'),
            ),
            'js_path' => 'banana',
            'base_css' => 'base',
            'presentation_css' => 'yep',
            'controller_path' => 'controllme'
        );
        // Should return controllerLocation as blank controller, since controller_path did not exist
        $this->assertIdentical(array(
            'controllerExtends' => array('bar/controller.php', 'foo/controller.php'),
            'logicExtends' => array('bar/logic.js', 'foo/logic.js'),
            'jsPath' => 'banana/logic.js',
            'baseCss' => 'base',
            'presentationCss' => 'yep',
            'controllerLocation' => 'standard/utils/Blank/controller.php',
        ), $method($input));

        // Widget info lists multiple extended controllers (one of which exists). Specifies a controller_path that does not exist on disk
        $input = array(
            'extends_info' => array(
                'controller' => array('foo', 'input/TextInput', 'bar'),
                'logic' => array('foo', 'bar'),
            ),
            'js_path' => 'banana',
            'base_css' => 'base',
            'presentation_css' => 'yep',
            'controller_path' => 'controllme'
        );
        // Should return controllerLocation as the first extended controller that exists, since controller_path did not
        $this->assertIdentical(array(
            'controllerExtends' => array('bar/controller.php', 'input/TextInput/controller.php', 'foo/controller.php'),
            'logicExtends' => array('bar/logic.js', 'foo/logic.js'),
            'jsPath' => 'banana/logic.js',
            'baseCss' => 'base',
            'presentationCss' => 'yep',
            'controllerLocation' => 'input/TextInput/controller.php',
        ), $method($input));

        // Widget info from a valid standard widget
        $widgetPathInfo = Registry::getWidgetPathInfo('input/TextInput');
        $widgetInfo = Widgets::getWidgetInfo($widgetPathInfo);

        // Should return controllerLocation and jsPath for the standard widget
        $this->assertIdentical(array(
            'jsPath' => 'standard/input/TextInput/logic.js',
            'viewPath' => 'standard/input/TextInput/view.php',
            'jsTemplates' => array('standard/input/TextInput/label.ejs', 'standard/input/TextInput/labelValidate.ejs'),
            'baseCss' => array('standard/input/TextInput/base.css'),
            'controllerLocation' => 'standard/input/TextInput/controller.php',
        ), $method($widgetInfo));

        // Widget info lists multiple extended controllers. Specifies NO controller_path
        $input = array(
            'extends_info' => array('controller' => array('yeah', 'no')),
        );
        // Should default to the blank controller, since no controller_path was specified
        $this->assertIdentical(array(
            'controllerExtends' => array('no/controller.php', 'yeah/controller.php'),
            'controllerLocation' => 'standard/utils/Blank/controller.php',
        ), $method($input));


    }

    function testGetWidgetPreviewImages() {
        $method = $this->getPrivate('getWidgetPreviewImages');

        $widget = Registry::getWidgetPathInfo('standard/utils/AnnouncementText');

        $results = $method($widget);
        $this->assertSame(1, count($results));
        $this->assertTrue(Text::beginsWith($results[0], 'standard/utils/AnnouncementText'));
        $this->assertTrue(Text::endsWith($results[0], '.png'));

        $widget = Registry::getWidgetPathInfo('custom/sample/SampleWidget');
        $results = $method($widget);
        $this->assertIdentical(array(), $results);
    }

    function testPopulateWidgetEvents() {
        $method = $this->getPrivate('populateWidgetEvents');

        $this->assertSame(array('subscribe' => array(), 'fire' => array()), $method(array()));

        $result = $method(array('js_path' => 'standard/utils/EmailAnswerLink'));
        $this->assertSame(array('subscribe' => array('evt_inlineModerationStatusUpdate'), 'fire' => array('evt_requireLogin', 'evt_userInfoRequired', 'evt_emailLinkRequest', 'evt_emailLinkSubmitResponse')), $result);

        $result = $method(array('js_path' => 'standard/input/SmartAssistantDialog'));
        $this->assertIdentical(array('subscribe', 'fire'), array_keys($result));
        $this->assertTrue(count($result['subscribe']) > 0);
        foreach ($result['subscribe'] as $eventName) {
            $this->assertBeginsWith($eventName, 'evt_');
        }
        $this->assertTrue(count($result['fire']) > 0);
        foreach ($result['fire'] as $eventName) {
            $this->assertBeginsWith($eventName, 'evt_');
        }
    }

    function testParseContainingWidgets() {
        $method = $this->getPrivate('parseContainingWidgets');

        $this->assertSame(array(), $method('', '', array()));
        $this->assertSame(array(), $method('<rn:widget path="foo/barr"', '', array()));
        $this->assertSame(array(), $method('<rn:widget path="foo/barr/>"', '', array()));
        $this->assertSame(array(), $method('<rn:widget path="foo/barr"/>', '', array()));

        $tag = '<rn:widget path="reports/Multiline" report_id="194" per_page="12"/>';
        $result = $method($tag, 'storm', array());
        $this->assertSame(1, count($result));
        $this->assertSame('storm', $result[0]['file']);
        $this->assertSame('standard/reports/Multiline', $result[0]['path']);
        $this->assertSame('reports/Multiline', $result[0]['matchedPath']);
        $this->assertSame(2, count($result[0]['attributes']));
        $this->assertSame('report_id="194"', $result[0]['attributes'][0]);
        $this->assertSame('per_page="12"', $result[0]['attributes'][1]);
        $this->assertSame($tag, $result[0]['match']);
        $this->assertSame('', $result[0]['description']);

        $containsInfo = array(0 => array('description' => "bananas", 'widget' => "standard/reports/Multiline"));
        $result = $method($tag, '', $containsInfo);
        $this->assertSame('bananas', $result[0]['description']);
    }

    function testGetContainingWidgets() {
        $this->assertSame(array(), Documenter::getContainingWidgets(''));
        $this->assertSame(array(), Documenter::getContainingWidgets(null));
        $this->assertSame(array(), Documenter::getContainingWidgets('<rn:condition logged_in="true"></rn:condition>'));
        $this->assertSame(array(), Documenter::getContainingWidgets(Registry::getWidgetPathInfo('standard/utils/AnnouncementText')));
        $this->assertIdentical(array(
            Registry::getWidgetPathInfo('standard/output/FieldDisplay'),
        ), Documenter::getContainingWidgets(file_get_contents(Registry::getWidgetPathInfo('standard/input/SelectionInput')->view)));

        $results = Documenter::getContainingWidgets(file_get_contents(Registry::getWidgetPathInfo('standard/input/SelectionInput')->view), true);
        $this->assertIdentical(Registry::getWidgetPathInfo('standard/output/FieldDisplay'), $results[0]['pathInfo']);
        $this->assertIdentical('output/FieldDisplay', $results[0]['attributes'][0]->attributeValue);
        $this->assertIdentical('output/FieldDisplay', $results[0]['matchedPath']);
        $this->assertIdentical('<rn:widget path="output/FieldDisplay" label="#rn:php:$this->data[\'attrs\'][\'label_input\']#" left_justify="true" sub_id="readOnlyField"/>', $results[0]['match']);
        $this->assertIdentical('#rn:php:$this->data[\'attrs\'][\'label_input\']#', $results[0]['attributes'][1]->attributeValue);
        $this->assertIdentical('true', $results[0]['attributes'][2]->attributeValue);
    }

    function testCategorizeAttributes() {
        $method = $this->getPrivate('categorizeAttributes');
        $input = array(
            'banana' => (object) array('type' => 'STRING', 'value' => 'banana'),
            'label_banana' => (object) array('type' => 'STRING', 'value' => 'startsWithLabel'),
            'banana_label' => (object) array('type' => 'STRING', 'value' => 'endsWithLabel'),
            'banana_label_banana' => (object) array('type' => 'STRING', 'value' => 'containsLabel'),
            'banana_url' => (object) array('type' => 'STRING', 'value' => 'banana_url'),
            'foo' => (object) array('type' => 'foo', 'value' => 'whatev'),
            'toggle_time' => (object) array('type' => 'BOOL', 'value' => false),
            'options' => (object) array('type' => 'OPTION', 'value' => ''),
            'ajaxy' => (object) array('type' => 'AJAX', 'value' => '/ci/foo/bar'),
            'number' => (object) array('type' => 'INT', 'value' => 23),
            'file' => (object) array('type' => 'FILEPATH', 'value' => '/euf/foo/bar'),
        );

        $results = $method($input);
        $this->assertIdentical(array('required', 'labels', 'bool', 'option', 'filepath', 'urls', 'ajax', 'other'), array_keys($results));
        $this->assertIdentical('startsWithLabel', $results['labels']['values']['label_banana']->value);
        $this->assertIdentical('endsWithLabel', $results['labels']['values']['banana_label']->value);
        $this->assertIdentical('containsLabel', $results['labels']['values']['banana_label_banana']->value);
        $this->assertIdentical(array('banana_url' => (object) array('type' => 'STRING', 'value' => 'banana_url')), $results['urls']['values']);
        $this->assertIdentical(array('toggle_time' => (object) array('type' => 'BOOL', 'value' => false)), $results['bool']['values']);
        $this->assertIdentical(array('options' => (object) array('type' => 'OPTION', 'value' => '')), $results['option']['values']);
        $this->assertIdentical(array('ajaxy' => (object) array('type' => 'AJAX', 'value' => '/ci/foo/bar')), $results['ajax']['values']);
        $this->assertSame(3, count($results['other']['values']));
    }

    function testSortByValues() {
        $method = $this->getPrivate('sortByValues');
        $categories = array(
            'labels' => array(
                'values' => array(
                    'label_zebra' => (object) array(
                        'name' => 'cebra',
                        'type' => 'STRING',
                    ),
                    'label_cat' => (object) array(
                        'name' => 'gato',
                        'type' => 'STRING',
                    ),
                    'label_dog' => (object) array(
                        'name' => 'perro',
                        'type' => 'STRING',
                    ),
                ),
            ),
        );
        $expected = array(
            'labels' => array(
                'values' => array(
                    'label_cat' => (object) array(
                        'name' => 'gato',
                        'type' => 'STRING',
                    ),
                    'label_dog' => (object) array(
                        'name' => 'perro',
                        'type' => 'STRING',
                    ),
                    'label_zebra' => (object) array(
                        'name' => 'cebra',
                        'type' => 'STRING',
                    ),
                ),
            ),
        );
        $actual = $method($categories);
        $this->assertIdentical($expected, $actual);
    }
}
