<?php

use RightNow\Utils\Text,
    RightNow\Utils\Widgets,
    RightNow\Internal\Libraries\Widget\Registry,
    RightNow\Libraries\ThirdParty\SimpleHtmlDom as Dom,
    RightNow\Libraries\ThirdParty\SimpleHtmlDomExtension as DomExt;

RightNow\UnitTest\Helper::loadTestedFile(__FILE__);

class WidgetViewsTest extends CPTestCase {
    public $testingClass = 'RightNow\Internal\Utils\WidgetViews';

    function testExtendingViewCache() {
        $cache = $this->getStaticMethod('extendingViewCache');
        $cache('clearAll');
        $widgetPath = 'standard/reports/Grid';
        $view = 'Grid widget view code';
        $this->assertNull($cache('get'));
        $this->assertNull($cache('get', $widgetPath));
        $this->assertFalse($cache('clear', $widgetPath));
        $cache('set', $widgetPath, $view);
        $this->assertIdentical($view, $cache('get', $widgetPath));

        // 'get' with no $widgetPath specified and exactly one record should return that value
        $this->assertIdentical($view, $cache('get'));
        $cache('set', 'some/other/widget', 'some/other/view');
        $this->assertNull($cache('get'));
        $cache('clear', 'some/other/widget');

        $this->assertTrue($cache('clear', $widgetPath));
        $this->assertNull($cache('get', $widgetPath));
        $this->assertFalse($cache('clear', $widgetPath));

        try {
            $this->assertIdentical($view, $cache('someInvalidAction', $widgetPath));
            $this->fail();
        }
        catch (\Exception $e) {
            $this->pass();
        }
    }

    function testMergeExtendedWidgetViewBlocks() {
        // Merging: view without override
        $baseView = '<rn:block id="one">A</rn:block>';
        $extendingView = '<!-- <rn:block id="one"> </rn:block> -->';

        $mergeWidgetView = $this->getStaticMethod('mergeExtendedWidgetViewBlocks');
        $result = $mergeWidgetView($baseView, $extendingView);
        $this->assertSame($result, $baseView);

        // Merging: view with override
        $baseView = '<rn:block id="one">A</rn:block><rn:block id="two">A</rn:block>';
        $extendingView = '<rn:block id="two">B</rn:block>';

        $mergeWidgetView = $this->getStaticMethod('mergeExtendedWidgetViewBlocks');
        $result = $mergeWidgetView($baseView, $extendingView);
        $this->assertSame($result, '<rn:block id="two">B</rn:block><rn:block id="one">A</rn:block>');
    }

    function testCombineWidgetViewBlocks() {
        $extendWidgetView = $this->getStaticMethod('combineWidgetViewBlocks');

        $return = $extendWidgetView('foo', '');
        $this->assertSame('', $return);

        $return = $extendWidgetView('foo', '<block/>');
        $this->assertSame('<block/>', $return);

        $return = $extendWidgetView('foo', '<rn:block/>');
        $this->assertSame('', $return);

        // Block inside other html is honored
        $baseView = '<div id="name">
            <rn:block id="foo"/>
        </div>';
        $extendView = '<div id="nono">
            <rn:block id="foo">BANANAS</rn:block>
        </div>';
        $return = $extendWidgetView('foo', $baseView, $extendView);
        $this->assertTrue(Text::stringContains($return, 'BANANAS'));
        $this->assertFalse(Text::stringContains($return, 'rn:block'));
        $this->assertFalse(Text::stringContains($return, 'nono'));

        // Same thing, but with multiple blocks in the base, one of which isn't self-closing
        $baseView = '<div id="name">
            <rn:block id="foo"/>
            <rn:block id="bar"></rn:block>
        </div>';
        $extendView = '<div id="nono">
            <rn:block id="foo">BANANAS</rn:block>
        </div>';
        $return = $extendWidgetView('foo', $baseView, $extendView);
        $this->assertTrue(Text::stringContains($return, 'BANANAS'));
        $this->assertFalse(Text::stringContains($return, 'rn:block'));
        $this->assertFalse(Text::stringContains($return, 'nono'));

        // Same thing, but with invalid container div in the base and child
        $baseView = '<div id="name">
            <rn:block id="foo"/>
            <rn:block id="bar"></rn:block>
        ';
        $extendView = '<div id="nono">
            <rn:block id="foo">BANANAS</rn:block>
        </>';
        $return = $extendWidgetView('foo', $baseView, $extendView);
        $this->assertTrue(Text::stringContains($return, 'BANANAS'));
        $this->assertFalse(Text::stringContains($return, 'rn:block'));
        $this->assertFalse(Text::stringContains($return, 'nono'));

        // PHP in base that's unfinished
        $baseView = '<?if($foo):?>
            <div id="name">
            <rn:block id="foo"/>
            <rn:block id="bar">
            </rn:block><?endif;?>
        ';
        $extendView = '<div id="nono">
            <rn:block id="foo">BANANAS</rn:block>
        </>';
        $return = $extendWidgetView('foo', $baseView, $extendView);
        $this->assertTrue(Text::stringContains($return, 'BANANAS'));
        $this->assertFalse(Text::stringContains($return, 'rn:block'));
        $this->assertFalse(Text::stringContains($return, 'nono'));

        // JS condition
        $baseView = '<% if(foo.bar.baz === true) { %>
            <div id="name">
            <rn:block id="foo"/>
            <rn:block id="bar">
            </rn:block><% } %>
        ';
        $extendView = '<div id="nono">
            <rn:block id="foo">BANANAS <%=i%></rn:block>
        </>';
        $return = $extendWidgetView('foo', $baseView, $extendView);
        $this->assertTrue(Text::stringContains($return, 'BANANAS'));
        $this->assertTrue(Text::stringContains($return, '<%=i%>'));
        $this->assertFalse(Text::stringContains($return, 'rn:block'));
        $this->assertFalse(Text::stringContains($return, 'nono'));
    }

    function testCombineWidgetViewBlocksWithWidgetNameSelectors() {
        $extendWidgetView = $this->getStaticMethod('combineWidgetViewBlocks');

        // matching name + "-blockID" selector
        $baseView = '<div id="name">
            <rn:block id="foo"/>
        </div>';
        $extendView = '<div id="nono">
            <rn:block id="WidgetName-foo">BANANAS</rn:block>
        </div>';
        $return = $extendWidgetView('WidgetName', $baseView, $extendView);
        $this->assertTrue(Text::stringContains($return, 'BANANAS'));
        $this->assertFalse(Text::stringContains($return, 'rn:block'));
        $this->assertFalse(Text::stringContains($return, 'nono'));

        // Same thing, with non-self closing block in base
        $baseView = '<div id="name">
            <rn:block id="foo"/>
            <rn:block id="bar"></rn:block>
        </div>';
        $extendView = '<div id="nono">
            <rn:block id="WidgetName-foo">BANANAS</rn:block>
        </div>';
        $return = $extendWidgetView('WidgetName', $baseView, $extendView);
        $this->assertTrue(Text::stringContains($return, 'BANANAS'));
        $this->assertFalse(Text::stringContains($return, 'rn:block'));
        $this->assertFalse(Text::stringContains($return, 'nono'));

        // Same thing, with invalid containers in base and child
        $baseView = '<div id="name">
            <rn:block id="foo"/>
            <rn:block id="bar"></rn:block>
        ';
        $extendView = '<div id="nono">
            <rn:block id="WidgetName-foo">BANANAS</rn:block>
        </>';
        $return = $extendWidgetView('WidgetName', $baseView, $extendView);
        $this->assertTrue(Text::stringContains($return, 'BANANAS'));
        $this->assertFalse(Text::stringContains($return, 'rn:block'));
        $this->assertFalse(Text::stringContains($return, 'nono'));

        $baseView = '<?if($foo):?>
            <div id="name">
            <rn:block id="foo"/>
            <rn:block id="bar">
            </rn:block><?endif;?>
        ';
        $extendView = '<div id="nono">
            <rn:block id="WidgetName-foo">BANANAS</rn:block>
        </>';
        $return = $extendWidgetView('WidgetName', $baseView, $extendView);
        $this->assertTrue(Text::stringContains($return, 'BANANAS'));
        $this->assertFalse(Text::stringContains($return, 'rn:block'));
        $this->assertFalse(Text::stringContains($return, 'nono'));

        $baseView = '<% if(foo.bar.baz === true) { %>
            <div id="name">
            <rn:block id="foo"/>
            <rn:block id="bar">
            </rn:block><% } %>
        ';
        $extendView = '<div id="nono">
            <rn:block id="WidgetName-foo">BANANAS <%=i%></rn:block>
        </>';
        $return = $extendWidgetView('WidgetName', $baseView, $extendView);
        $this->assertTrue(Text::stringContains($return, 'BANANAS'));
        $this->assertTrue(Text::stringContains($return, '<%=i%>'));
        $this->assertFalse(Text::stringContains($return, 'rn:block'));
        $this->assertFalse(Text::stringContains($return, 'nono'));

        $baseView = '<% if(foo.bar.baz === true) { %>
            <div id="name">
            <rn:block id="foo"/>
            <rn:block id="bar">
            </rn:block><% } %>
        ';
        $extendView = '<div id="nono">
            <rn:block id="WidgetName-foo">BANANAS <%=i%></rn:block>
            <rn:block id="foo">PLUMS <%=i%></rn:block>
        </>';
        $return = $extendWidgetView('WidgetName', $baseView, $extendView);
        $this->assertTrue(Text::stringContains($return, 'PLUMS'));
        $this->assertTrue(Text::stringContains($return, '<%=i%>'));
        $this->assertFalse(Text::stringContains($return, 'rn:block'));
        $this->assertFalse(Text::stringContains($return, 'nono'));

        $baseView = '<% if(foo.bar.baz === true) { %>
            <div id="name">
            <rn:block id="foo"/>
            <rn:block id="bar">
            </rn:block><% } %>
        ';
        $extendView = '<div id="nono">
            <rn:block id="WidgetName-foo">BANANAS <%=i%></rn:block>
            <rn:block id="foo">PLUMS <%=i%></rn:block>
            <rn:block id="WidgetName-foo">APPLES <%=i%></rn:block>
        </>';
        $return = $extendWidgetView('WidgetName', $baseView, $extendView);
        $this->assertTrue(Text::stringContains($return, 'APPLES'));
        $this->assertTrue(Text::stringContains($return, '<%=i%>'));
        $this->assertFalse(Text::stringContains($return, 'rn:block'));
        $this->assertFalse(Text::stringContains($return, 'nono'));
    }

    function testCombineWidgetViewBlocksRequiringWidgetNameSelectors() {
        $extendWidgetView = $this->getStaticMethod('combineWidgetViewBlocks');

        $baseView = '<% if(foo.bar.baz === true) { %>
            <div id="name">
            <rn:block id="foo"/>
            <rn:block id="bar">
            </rn:block><% } %>
        ';
        $extendView = '<div id="nono">
            <rn:block id="foo">BANANAS <%=i%></rn:block>
            <rn:block id="WidgetName-foo">PLUMS <%=j%></rn:block>
        </>';
        $return = $extendWidgetView('WidgetName', $baseView, $extendView, true);
        $this->assertFalse(Text::stringContains($return, 'BANANAS'));
        $this->assertTrue(Text::stringContains($return, 'PLUMS'));
        $this->assertTrue(Text::stringContains($return, '<%=j%>'));
        $this->assertFalse(Text::stringContains($return, 'rn:block'));
        $this->assertFalse(Text::stringContains($return, 'nono'));
    }

    function testNestedBlocksInBaseViewAreProperlyExtracted() {
        $extendWidgetView = $this->getStaticMethod('combineWidgetViewBlocks');

        // Inner block is removed when extending block replaces surrounding block;
        // Matching block in extending view is then ignored
        $base =
        '<rn:block id="preList"/>
        <ul>
        <rn:block id="listBody">
            <li>
                <rn:block id="listItemBody">
                <?= $something ?>
                </rn:block>
            </li>
        </rn:block>
        </ul>
        <rn:block id="postList"/>';

        $extend =
        '<rn:block id="listBody">THIS</rn:block>
        <rn:block id="listItemBody">NO</rn:block>';

        $return = $extendWidgetView('name', $base, $extend);
        $this->assertSame('<ul>THIS</ul>', preg_replace('/\s/', '', $return));

        // Replace inner block
        $extend =
        '<rn:block id="listItemBody">NO</rn:block>';

        $return = $extendWidgetView('name', $base, $extend);
        $this->assertSame('<ul><li>NO</li></ul>', preg_replace('/\s/', '', $return));

        // No extending view; blocks are removed from base
        $extend = '';
        $return = $extendWidgetView('name', $base, $extend);
        $this->assertSame('<ul><li><?=$something?></li></ul>', preg_replace('/\s/', '', $return));
    }

    function testUnmatchedTagsInExtendingBlocks() {
        $extendWidgetView = $this->getStaticMethod('combineWidgetViewBlocks');

        $base =
        '<rn:block id="foo"/>
        <p></p>
        <rn:block id="bar"/>';

        $extend =
        '<rn:block id="foo">
        <div><div>
        </rn:block>
        <!-- never matched -->
        <rn:block="bar">';

        $return = $extendWidgetView('name', $base, $extend);
        $this->assertSame('<div><div><p></p>', preg_replace('/\s/', '', $return));

        $extend =
        '<rn:block id="foo">
        <div><div>
        </rn:block>
        <!-- wrapping widget content with double divs -->
        <rn:block id="bar">
        </div>
        </div>
        </rn:block>';

        $return = $extendWidgetView('name', $base, $extend);
        $this->assertSame('<div><div><p></p></div></div>', preg_replace('/\s/', '', $return));
    }

    function testErroneousExtendingBlocks() {
        $extendWidgetView = $this->getStaticMethod('combineWidgetViewBlocks');

        // Nested blocks in extending
        $base =
        '<div>
        <rn:block id="foo">
        OH
        </rn:block>
        </div>';

        $extend =
        '<rn:block id="foo">
        <rn:block id="somethingelse">
        HAI
        </rn:block>
        </rn:block>';

        $return = $extendWidgetView('name', $base, $extend);
        $this->assertSame('<div><rn:blockid="somethingelse">HAI</div>', preg_replace('/\s/', '', $return));

        // Unclosed id
        $extend =
        '<rn:block id="foo>
        HAI
        </rn:block>';

        $return = $extendWidgetView('name', $base, $extend);
        $this->assertSame('<div>OH</div>', preg_replace('/\s/', '', $return));

        // Misquoted id
        $extend =
        '<rn:block id="foo\'>
        HAI
        </rn:block>';

        $return = $extendWidgetView('name', $base, $extend);
        $this->assertSame('<div>OH</div>', preg_replace('/\s/', '', $return));

        // Nonquoted id: we're cool like that.
        $extend =
        '<rn:block id=foo>
        HAI
        </rn:block>';

        $return = $extendWidgetView('name', $base, $extend);
        $this->assertSame('<div>HAI</div>', preg_replace('/\s/', '', $return));

        // Invalid tag
        $extend =
        '<rn:block id=foo
        HAI
        </rn:block>';

        $return = $extendWidgetView('name', $base, $extend);
        $this->assertSame('<div>OH</div>', preg_replace('/\s/', '', $return));

        $extend =
        '<rn:block id=foo>
        HAI
        <rn:block>';

        $return = $extendWidgetView('name', $base, $extend);
        $this->assertSame('<div>HAI<rn:block></div>', preg_replace('/\s/', '', $return));

        // Unclosed tag
        $extend =
        '<rn:block id=foo>
        HAI';

        $return = $extendWidgetView('name', $base, $extend);
        $this->assertSame('<div>HAI</div>', preg_replace('/\s/', '', $return));

        // Self-closed tags
        $extend =
        '<rn:block id="foo"/>
        HAI
        <rn:block id="bar"/>';

        $return = $extendWidgetView('name', $base, $extend);
        $this->assertSame('<div></div>', preg_replace('/\s/', '', $return));
    }

    function testCombinePHPViews() {
        $extendWidgetView = $this->getStaticMethod('combinePHPViews');

        // number of replacements
        $baseView = '<% if(foo.bar.baz === true) { %>
            <div id="name">
            <rn:block id="foo"/>
            <rn:block id="bar">
            </rn:block><% } %>
        ';
        $extendView = '<div id="nono">
            <rn:block id="foo">BANANAS <%=i%></rn:block>
            <rn:block id="WidgetName-foo">PLUMS <%=j%></rn:block>
        </>';
        $return = $extendWidgetView('WidgetName', $baseView, $extendView, true);
        $this->assertSame(1, $return['replacementsMade']);
        $baseView = '<% if(foo.bar.baz === true) { %>
            <div id="name">
            <rn:block id="foo"/>
            <rn:block id="bar">
            </rn:block><% } %>
        ';
        $extendView = '<div id="nono">
            <rn:block id="WidgetName-foo">BANANAS <%=i%></rn:block>
            <rn:block id="WidgetName-bar">PLUMS <%=j%></rn:block>
        </>';
        $return = $extendWidgetView('WidgetName', $baseView, $extendView, true);
        $this->assertSame(2, $return['replacementsMade']);
        $baseView = '<% if(foo.bar.baz === true) { %>
            <div id="name">
            <rn:block id="foo"/>
            <rn:block id="bar">
            </rn:block><% } %>
        ';
        $extendView = '<div id="nono">
            <rn:block id="WidgetName-foo">BANANAS <%=i%></rn:block>
            <rn:block id="WidgetName-bar">PLUMS <%=j%></rn:block>
        </>';
        $return = $extendWidgetView('WidgetName', $baseView, $extendView, false);
        $this->assertSame(2, $return['replacementsMade']);

        $baseView = '<% if(foo.bar.baz === true) { %>
            <div id="name">
            <rn:block id="foo"/>
            <rn:block id="bar">
            </rn:block><% } %>
        ';
        $extendView = '<div id="nono">
            <rn:block id="-foo">BANANAS <%=i%></rn:block>
            <rn:block id="baz">PLUMS <%=j%></rn:block>
        </>';
        $return = $extendWidgetView('WidgetName', $baseView, $extendView, false);
        $this->assertSame(0, $return['replacementsMade']);
    }

    function testGetExtendedWidgetPhpView() {
        $method = $this->getStaticMethod('getExtendedWidgetPhpView');
        $getWidgetInfo = function($widgetName) {
            \RightNow\Internal\Utils\WidgetViews::removeExtendingView($widgetName);
            $widget = Registry::getWidgetPathInfo($widgetName);
            $view = file_get_contents($widget->view);
            $meta = Widgets::getWidgetInfo($widget);
            return array($view, $meta, $widget);
        };

        list($view, $meta, $widget) = $getWidgetInfo('standard/input/FormInput');
        $this->assertEqual('<block/>', $method('<block/>', array(), $widget));
        $this->assertEqual('', $method('<rn:block/>', array(), $widget));

        // Ensure rn:block tags are removed
        $this->assertTrue(Text::stringContains($view, 'rn:block'));
        $result = $method($view, $meta, $widget);
        $this->assertIsA($result, 'string');
        $this->assertTrue(strlen($result) > 0);
        $this->assertFalse(Text::stringContains($result, 'rn:block'));

        // Widget that extends its view from the parent
        list($view, $meta, $widget) = $getWidgetInfo('standard/reports/Multiline');
        $parentView = $method($view, $meta, $widget);
        list($view, $meta, $widget) = $getWidgetInfo('standard/search/CombinedSearchResults');
        $childView = $method($view, $meta, $widget);
        $this->assertTrue(strlen($childView) > strlen($parentView));

        // If widget does not have a view of its own, having more than one extended view should be allowed
        $meta = array('extends_info' => array(
            'view_path' => '',
            'view' => array(
                'standard/input/FormInputParent',
                'standard/input/FormInputChild',
            )
        ));
        $this->assertIsA($method($view, $meta, $widget), 'string');

        // If widget does have it's own view and there are multiple extended views, it should also be allowed
        $meta['view_path'] = 'standard/input/FormInput/1.0/view.php';
        $result = $method($view, $meta, $widget);
        $this->assertFalse(Text::stringContains($result, 'rn:block'));
        // remove CombinedSearchResults from extending view cache
        \RightNow\Internal\Utils\WidgetViews::removeExtendingView('standard/search/CombinedSearchResults');

        //@@@ QA 130422-000082 verify that we don't try to array_shift on null and that something is returned
        $view = false;
        $meta = array('extends_info' => array(
            'view_path' => '',
            'view' => array(
                'standard/search/BasicKeywordSearch',
                'standard/search/BasicKeywordSearch',
            )
        ));
        $widget = new \RightNow\Internal\Libraries\Widget\PathInfo('custom', 'custom/custom/customBasicKeywordSearch', CUSTOMER_FILES . 'widgets/custom/custom/customBasicKeywordSearch', '1.0');
        $result = $method($view, $meta, $widget);
        $this->assertIsA($result, 'string');
        $this->assertTrue(strlen($result) > 0);

        // multiple extensions with override view and logic, first create a base widget to extend from
        \RightNow\Utils\FileSystem::filePutContentsOrThrowExceptionOnFailure(CUSTOMER_FILES . "widgets/custom/test/WidgetA/1.0/info.yml",
            yaml_emit(array(
                'version' => '1.0',
                'requires' => array(
                    'jsModule' => array('standard', 'mobile'),
                ),
            ))
        );
        // create a view for test/WidgetA so it shows up in widget extensions
        \RightNow\Utils\FileSystem::filePutContentsOrThrowExceptionOnFailure(CUSTOMER_FILES . "widgets/custom/test/WidgetA/1.0/view.php", "<rn:block id=\"top\"/>\n<div>test/WidgetA</div>\n<rn:block id=\"bottom\"/>");

        // activate test/WidgetA
        $allWidgets = Widgets::getDeclaredWidgetVersions(CUSTOMER_FILES);
        $allWidgets['custom/test/WidgetA'] = '1.0';
        Widgets::updateDeclaredWidgetVersions($allWidgets);
        Registry::setTargetPages('development');

        // create a widget that extends the view from test/WidgetA
        \RightNow\Utils\FileSystem::filePutContentsOrThrowExceptionOnFailure(CUSTOMER_FILES . "widgets/custom/test/WidgetB/1.0/info.yml",
            yaml_emit(array(
                'version' => '1.0',
                'requires' => array(
                    'jsModule' => array('standard', 'mobile'),
                ),
                'extends' => array(
                    'widget' => 'custom/test/WidgetA',
                    'components' => array('view'),
                ),
            ))
        );

        // create a widget that overrides view and logic for test/WidgetB
        \RightNow\Utils\FileSystem::filePutContentsOrThrowExceptionOnFailure(CUSTOMER_FILES . "widgets/custom/test/WidgetC/1.0/info.yml",
            yaml_emit(array(
                'version' => '1.0',
                'requires' => array(
                    'jsModule' => array('standard', 'mobile'),
                ),
                'extends' => array(
                    'widget' => 'custom/test/WidgetB',
                    'components' => array(),
                    'overrideViewAndLogic' => 'true',
                ),
            ))
        );

        // make sure that the view for test/WidgetB contains the view from test/WidgetA since it extends that widget
        $widget = new \RightNow\Internal\Libraries\Widget\PathInfo('custom', 'custom/test/WidgetB', CUSTOMER_FILES . 'widgets/custom/test/WidgetB', '1.0');
        $meta = array(
            'view_path' => 'custom/test/WidgetB',
            'extends' => array(
                'widget' => 'custom/test/WidgetA',
                'components' => array('view' => true),
            ),
            'extends_info' => array(
                'view_path' => '',
                'view' => array(
                    'custom/test/WidgetA',
                ),
                'parent' => 'custom/test/WidgetA',
            ),
        );
        $result = $method('<rn:block id="top">test/WidgetB</rn:block>', $meta, $widget);

        // view should contain block data from test/WidgetB merged with view from test/WidgetA
        $this->assertStringContains($result, 'test/WidgetB', "The view should include test/WidgetB since it was emitted in a rn:block tag");
        $this->assertStringContains($result, '<div>test/WidgetA</div>', "The view should include test/WidgetA as it extended that widget");
        $this->assertStringDoesNotContain($result, 'test/WidgetC', "The view should include test/WidgetC as that is a grandchild of the current widget");

        // make sure that the view for test/WidgetC doesn't include any other views, since it overrides view and logic
        $widget = new \RightNow\Internal\Libraries\Widget\PathInfo('custom', 'custom/test/WidgetC', CUSTOMER_FILES . 'widgets/custom/test/WidgetC', '1.0');
        $meta = array(
            'view_path' => 'custom/test/WidgetC',
            'extends' => array(
                'widget' => 'custom/test/WidgetB',
                'components' => array(),
                'overrideViewAndLogic' => 'true',
            ),
            'extends_info' => array(
                'view_path' => '',
                'view' => array(
                    'custom/test/WidgetC',
                ),
                'parent' => 'custom/test/WidgetB',
            ),
        );
        $result = $method('<div>test/WidgetC</div>', $meta, $widget);
        $this->assertStringContains($result, '<div>test/WidgetC</div>', "The view should only include the test/WidgetC view");
        $this->assertStringDoesNotContain($result, 'test/WidgetA', "The view should not contain the test/WidgetA view");
        $this->assertStringDoesNotContain($result, 'test/WidgetB', "The view should not contain the test/WidgetB view");

        // update test/WidgetB to override test/WidgetA's view and logic and re-test test/WidgetB and test/WidgetC
        \RightNow\Utils\FileSystem::filePutContentsOrThrowExceptionOnFailure(CUSTOMER_FILES . "widgets/custom/test/WidgetB/1.0/info.yml",
            yaml_emit(array(
                'version' => '1.0',
                'requires' => array(
                    'jsModule' => array('standard', 'mobile'),
                ),
                'extends' => array(
                    'widget' => 'custom/test/WidgetA',
                    'components' => array(),
                    'overrideViewAndLogic' => 'true',
                ),
            ))
        );

        // make sure that the view for test/WidgetB doesn't include any other views, since it overrides view and logic
        $widget = new \RightNow\Internal\Libraries\Widget\PathInfo('custom', 'custom/test/WidgetB', CUSTOMER_FILES . 'widgets/custom/test/WidgetB', '1.0');
        $meta = array(
            'view_path' => 'custom/test/WidgetB',
            'extends' => array(
                'widget' => 'custom/test/WidgetA',
                'components' => array(),
                'overrideViewAndLogic' => 'true',
            ),
            'extends_info' => array(
                'view_path' => '',
                'view' => array(
                    'custom/test/WidgetB',
                ),
                'parent' => 'custom/test/WidgetA',
            ),
        );
        $result = $method('<div>test/WidgetB</div>', $meta, $widget);
        $this->assertStringContains($result, '<div>test/WidgetB</div>', "The view should only include the test/WidgetB view");
        $this->assertStringDoesNotContain($result, 'test/WidgetA', "The view should not contain the test/WidgetA view");
        $this->assertStringDoesNotContain($result, 'test/WidgetC', "The view should not contain the test/WidgetC view");

        // make sure that the view for test/WidgetC doesn't include any other views, since it overrides view and logic
        $widget = new \RightNow\Internal\Libraries\Widget\PathInfo('custom', 'custom/test/WidgetC', CUSTOMER_FILES . 'widgets/custom/test/WidgetC', '1.0');
        $meta = array(
            'view_path' => 'custom/test/WidgetC',
            'extends' => array(
                'widget' => 'custom/test/WidgetB',
                'components' => array(),
                'overrideViewAndLogic' => 'true',
            ),
            'extends_info' => array(
                'view_path' => '',
                'parent' => 'custom/test/WidgetB',
            ),
        );
        $result = $method('<div>test/WidgetC</div>', $meta, $widget);
        $this->assertStringContains($result, '<div>test/WidgetC</div>', "The view should only include the test/WidgetC view");
        $this->assertStringDoesNotContain($result, 'test/WidgetA', "The view should not contain the test/WidgetA view");
        $this->assertStringDoesNotContain($result, 'test/WidgetB', "The view should not contain the test/WidgetB view");

        // cleanup test widgets
        \RightNow\Utils\FileSystem::removeDirectoryRecursivelyOrThrowExceptionOnFailure(CUSTOMER_FILES . "widgets/custom/test", true);
        unset($allWidgets['custom/test/WidgetA']);
        Widgets::updateDeclaredWidgetVersions($allWidgets);
        \RightNow\Internal\Utils\Version::clearCacheVariables();
        Widgets::killCacheVariables();
    }

    function testGetExtendedWidgetJsViews() {
        $extendWidgetView = $this->getStaticMethod('getExtendedWidgetJsViews');
        $widget = Registry::getWidgetPathInfo('standard/input/FormInput');

        // Base cases
        $return = $extendWidgetView(array(), $widget);
        $this->assertSame(array(), $return);

        $return = $extendWidgetView(array('js_templates'), $widget);
        $this->assertSame(array(), $return);

        // Typical case
        $return = $extendWidgetView(
            array(
                'js_templates' => array(
                    'foo' =>
                        '<% for (var i = 0; i < 10; i++) { %>
                            <div class="item"/>
                        <% } %>
                        <rn:block id="no"/>')),
            $widget);
        $this->assertSame(1, count($return));
        $this->assertTrue(array_key_exists('foo', $return));
        $return = $return['foo'];
        $this->assertFalse(Text::stringContains($return, 'block'));
        $this->assertFalse(Text::stringContains($return, 'rn:'));

        // Extending: parent widget doesn't exist
        $return = $extendWidgetView(
            array(
                'js_templates' => array(
                    'foo' =>
                        '<% for (var i = 0; i < 10; i++) { %>
                            <div class="item"/>
                        <% } %>
                        <rn:block id="no"/>'),
                'extends_info' => array(
                    'js_templates' => array(array('bar' => '<div></div>')),
                    'parent' => 'foo/bart/baz')),
            $widget);
        $this->assertSame(0, count($return));

        // Extending: parent exists, template names don't match.
        $return = $extendWidgetView(
            array(
                'js_templates' => array(
                    'foo' =>
                        '<% for (var i = 0; i < 10; i++) { %>
                            <div class="item"/>
                        <% } %>
                        <rn:block id="no"/>'),
                'extends_info' => array(
                    'js_templates' => array(array('bar' => '<div></div>')),
                    'parent' => 'standard/feedback/AnswerFeedback')),
            $widget);
        $this->assertSame(2, count($return));
        $this->assertTrue(array_key_exists('foo', $return));
        $return = $return['foo'];
        $this->assertFalse(Text::stringContains($return, 'block'));
        $this->assertFalse(Text::stringContains($return, 'rn:'));

        // Extending: parent exists, but child overrides view and logic and template names match.
        $return = $extendWidgetView(
            array(
                'extends'      => array('overrideViewAndLogic' => 'true'),
                'js_templates' => array('view' => 'overridden view'),
                'extends_info' => array(
                    'js_templates' => array(), // when overrideViewAndLogic is true, this will always be empty
                    'parent' => 'standard/feedback/AnswerFeedback')),
            $widget);
        $this->assertSame(1, count($return));
        $this->assertTrue(array_key_exists('view', $return));
        $this->assertSame('overridden view', $return['view'], "EJS View did not override parent properly");

        // Extending: parent exists, but child overrides view and logic and template names don't match.
        $return = $extendWidgetView(
            array(
                'extends'      => array('overrideViewAndLogic' => 'true'),
                'js_templates' => array('foo' => 'overridden view'),
                'extends_info' => array(
                    'js_templates' => array(), // when overrideViewAndLogic is true, this will always be empty
                    'parent' => 'standard/feedback/AnswerFeedback')),
            $widget);
        // since we are overriding, there should only be one even though the names don't match
        $this->assertSame(1, count($return));
        $this->assertTrue(array_key_exists('foo', $return));
        $this->assertSame('overridden view', $return['foo'], "EJS View did not override parent properly");

        // Extending: multiple templates
        $return = $extendWidgetView(
            array(
                'js_templates' => array(
                    'foo' => '<rn:block id="no">BANANA</rn:block>',
                    'bar' => '<div>BANANA</div>',
                    ),
                'extends_info' => array(
                    'js_templates' => array(array(
                        'foo' => '<div></div>',
                        'bar' => '<rn:block id="foo"></rn:block>',
                    )),
                    'parent' => 'standard/feedback/AnswerFeedback')),
            $widget);
        $this->assertSame(2, count($return));
        $this->assertTrue(array_key_exists('foo', $return));
        $this->assertTrue(array_key_exists('bar', $return));
        $foo = $return['foo'];
        $this->assertSame('<div></div>', $foo);
        $bar = $return['bar'];
        $this->assertSame('', $bar);

        // Extending: parent is already extending something else
        $return = $extendWidgetView(
            array(
                'js_templates' => array(
                    'foo' => '<rn:block id="no">BANANA</rn:block>',
                    'bar' => '<div>BANANA</div>',
                ),
                'extends_info' => array(
                    'js_templates' => array(array(
                        'foo' => '<div></div>',
                        'bar' => '<rn:block id="foo"></rn:block>',
                    ), array('baz' => '...',)),
                    'parent' => 'standard/feedback/AnswerFeedback',
                ),
            ),
            $widget);
        $this->assertSame('<div></div>', $return['foo']);
        $this->assertSame('', $return['bar']);

        // Extending: parent is already extending something else with block overrides in each extension
        $return = $extendWidgetView(
            array(
                'js_templates' => array(
                    // view of grandchild
                    'view'   => '<rn:block id="two">C</rn:block><rn:block id="three">C</rn:block>',
                ),
                'extends_info' => array(
                    'js_templates' => array(
                        // parent view
                        array('view' => '<rn:block id="one">B</rn:block>'),
                        // grandparent view
                        array('view' => '<rn:block id="one">A</rn:block><rn:block id="two">A</rn:block><rn:block id="three">A</rn:block><rn:block id="four">A</rn:block>'),
                    ),
                    'parent' => 'standard/feedback/AnswerFeedback',
                ),
            ), $widget);
        $this->assertSame('BCCA', $return['view']);

        // Extending: block overrides
        $return = $extendWidgetView(
            array(
                'js_templates' => array(
                    'foo' => '<rn:block id="no">BANANA</rn:block>',
                    'bar' => '<div>BANANA</div>',
                ),
                'extends_info' => array(
                    'js_templates' => array(array(
                        'foo' => '<rn:block id="no"/>',
                        'bar' => '<rn:block id="foo"></rn:block>',
                    )),
                    'parent' => 'standard/feedback/AnswerFeedback')),
            $widget);
        $this->assertSame(2, count($return));
        $this->assertTrue(array_key_exists('foo', $return));
        $this->assertTrue(array_key_exists('bar', $return));
        $foo = $return['foo'];
        $this->assertFalse(Text::stringContains($foo, 'block'));
        $this->assertTrue(Text::stringContains($foo, 'BANANA'));
        $bar = $return['bar'];
        $this->assertSame('', $bar);

        // Extending: child doesn't have any templates, blocks are reduced in parent
        $return = $extendWidgetView(
            array(
                'extends_info' => array(
                    'js_templates' => array(array(
                        'foo' => '<rn:block id="no">BANANA</rn:block>',
                        'bar' => '<rn:block id="top"/><div>BANANA</div><rn:block id="bottom"/>',
                    )),
                    'parent' => 'standard/feedback/AnswerFeedback')),
            $widget);
        $this->assertSame(2, count($return));
        $this->assertTrue(array_key_exists('foo', $return));
        $this->assertTrue(array_key_exists('bar', $return));
        $foo = $return['foo'];
        $this->assertSame('BANANA', $foo);
        $bar = $return['bar'];
        $this->assertSame('<div>BANANA</div>', $bar);

        // A widget without a view of it's own, should use a combination of the parent and base views.
        $return = $extendWidgetView(array(
            'extends_info' => array(
                'js_templates'  => array(
                    // parent widget
                    array(
                        'view'  => '<rn:block id="top">top-</rn:block><rn:block id="bottom">-bottom</rn:block>',
                    ),
                    // base ancestor
                    array(
                        'item'  => '<rn:block id="top"/>item<rn:block id="bottom"/>',
                        'view'  => '<rn:block id="top"/>view<rn:block id="bottom"/>',
                    ),
                ),
                parent => 'standard/feedback/AnswerFeedback')
            ), $widget);
        $this->assertIdentical(2, count($return));
        $this->assertIdentical("top-view-bottom", $return["view"]);
        $this->assertIdentical("item", $return["item"]);

        // A widget without a view of it's own, should use a combination of the parent, grandparent and base views.
        $return = $extendWidgetView(array(
            'extends_info' => array(
                'js_templates'  => array(
                    // parent widget
                    array(
                        'view'  => '<rn:block id="top">parentTop-</rn:block>',
                        'item'  => '<rn:block id="bottom">-parentBottom</rn:block>',
                    ),
                    // grandparent widget
                    array(
                        'view'  => '<rn:block id="top">top-</rn:block><rn:block id="bottom">-bottom</rn:block>',
                    ), 
                    // base ancestor
                    array(
                        'item'  => '<rn:block id="top"/>item<rn:block id="bottom"/>',
                        'view'  => '<rn:block id="top"/>view<rn:block id="bottom"/>',
                    ),
                ),
                parent => 'standard/feedback/AnswerFeedback')
            ), $widget);
        $this->assertIdentical(2, count($return));
        $this->assertIdentical('parentTop-view-bottom', $return['view']);
        $this->assertIdentical('item-parentBottom', $return['item']);

        // A widget without a view of it's own, should use a combination of the parent, grandparent and base views.
        $return = $extendWidgetView(array(
            'extends_info' => array(
                'js_templates'  => array(
                    // parent widget
                    array(
                        'view'  => '<rn:block id="top">parentViewTop-</rn:block>',
                    ),
                    // grandparent widget
                    array(
                        'view'  => '<rn:block id="top">viewTop-</rn:block><rn:block id="bottom">-viewBottom</rn:block>',
                        'item'  => '<rn:block id="bottom">-parentItemBottom</rn:block>',
                    ), 
                    // base ancestor
                    array(
                        'item'  => '<rn:block id="top"/>item<rn:block id="bottom"/>',
                        'view'  => '<rn:block id="top"/>view<rn:block id="bottom"/>',
                    ),
                ),
                parent => 'standard/feedback/AnswerFeedback')
            ), $widget);
        $this->assertIdentical(2, count($return));
        $this->assertIdentical('item-parentItemBottom', $return['item']);
        $this->assertIdentical('parentViewTop-view-viewBottom', $return['view']);

        // Multiple extensions of a view, including the current widget
        $return = $extendWidgetView(array(
            'js_templates' => array(
                'view' => '<rn:block id="top">top-</rn:block>',
            ),
            'extends_info' => array(
                'js_templates'  => array(
                    // parent widget
                    array(
                        'view'  => '<rn:block id="top">parentTop-</rn:block><rn:block id="bottom">-parentBottom</rn:block>',
                    ),
                    // grandparent widget
                    array(
                        'view'  => '<rn:block id="top">grandparentViewTop-</rn:block><rn:block id="bottom">-grandparentViewBottom</rn:block>',
                    ), 
                    // base ancestor
                    array(
                        'view'  => '<rn:block id="top"/>view<rn:block id="bottom"/>',
                    ),
                ),
                parent => 'standard/feedback/AnswerFeedback')
            ), $widget);
        $this->assertIdentical(1, count($return));
        $this->assertIdentical('top-view-parentBottom', $return['view']);

        // Multiple views, with ancestors at various points in the stack, all with multiple extensions
        $return = $extendWidgetView(array(
            'js_templates' => array(
                'view' => '<rn:block id="top">top-</rn:block>',
                'item' => '<rn:block id="pre">pre-</rn:block>',
            ),
            'extends_info' => array(
                'js_templates'  => array(
                    // parent widget
                    array(
                        'view'  => '<rn:block id="top">parentTop-</rn:block><rn:block id="bottom">-parentBottom</rn:block>',
                    ),
                    // grandparent widget, adds a new view
                    array(
                        'view'  => '<rn:block id="top">grandparentViewTop-</rn:block><rn:block id="bottom">-grandparentViewBottom</rn:block>',
                        'item'  => '<rn:block id="pre"/>item<rn:block id="post"/>',
                    ), 
                    // base ancestor
                    array(
                        'view'  => '<rn:block id="top"/>view<rn:block id="bottom"/>',
                    ),
                ),
                parent => 'standard/feedback/AnswerFeedback')
            ), $widget);
        $this->assertIdentical(2, count($return));
        $this->assertIdentical('top-view-parentBottom', $return['view']);
        $this->assertIdentical('pre-item', $return['item']);

        // Multiple views, with ancestors at various points in the stack, all with multiple extensions
        $return = $extendWidgetView(array(
            'js_templates' => array(
                'view' => '<rn:block id="top">top-</rn:block>',
                'item' => '<rn:block id="pre">pre-</rn:block>',
            ),
            'extends_info' => array(
                'js_templates'  => array(
                    array(
                        'item'  => '<rn:block id="post">-post</rn:block>',
                    ),
                    array(
                        'foo'   => '<rn:block id="bar">bar</rn:block>',
                    ),
                    // extension that doesn't touch/include EJS views
                    array(
                    ),
                    array(
                        'view'  => '<rn:block id="top">parentTop-</rn:block><rn:block id="bottom">-parentBottom</rn:block>',
                        'foo'   => 'foo<rn:block id="bar"/>',
                    ),
                    array(
                        'view'  => '<rn:block id="top">grandparentViewTop-</rn:block><rn:block id="bottom">-grandparentViewBottom</rn:block>',
                        'item'  => '<rn:block id="pre"/>item<rn:block id="post"/>',
                    ), 
                    array(
                        'view'  => '<rn:block id="top"/>view<rn:block id="bottom"/>',
                    ),
                ),
                parent => 'standard/feedback/AnswerFeedback')
            ), $widget);
        $this->assertIdentical(3, count($return));
        $this->assertIdentical('top-view-parentBottom', $return['view']);
        $this->assertIdentical('pre-item-post', $return['item']);
        $this->assertIdentical('foobar', $return['foo']);

        // Multiple views, with ancestors at various points in the stack, all with multiple extensions. New view in current widget.
        $return = $extendWidgetView(array(
            'js_templates' => array(
                'view' => '<rn:block id="top">top-</rn:block>',
                'item' => '<rn:block id="pre">pre-</rn:block>',
                'new'  => '<rn:block id="newTop"/>new<rn:block id="newBottom"/>',
            ),
            'extends_info' => array(
                'js_templates'  => array(
                    array(
                        'item'  => '<rn:block id="post">-post</rn:block>',
                    ),
                    array(
                        'foo'   => '<rn:block id="bar">bar</rn:block>',
                    ),
                    // extension that doesn't touch/include EJS views
                    array(
                    ),
                    array(
                        'view'  => '<rn:block id="top">parentTop-</rn:block><rn:block id="bottom">-parentBottom</rn:block>',
                        'foo'   => 'foo<rn:block id="bar"/>',
                    ),
                    array(
                        'view'  => '<rn:block id="top">grandparentViewTop-</rn:block><rn:block id="bottom">-grandparentViewBottom</rn:block>',
                        'item'  => '<rn:block id="pre"/>item<rn:block id="post"/>',
                    ), 
                    array(
                        'view'  => '<rn:block id="top"/>view<rn:block id="bottom"/>',
                    ),
                ),
                parent => 'standard/feedback/AnswerFeedback')
            ), $widget);
        $this->assertIdentical(4, count($return));
        $this->assertIdentical('top-view-parentBottom', $return['view']);
        $this->assertIdentical('pre-item-post', $return['item']);
        $this->assertIdentical('foobar', $return['foo']);
        $this->assertIdentical('new', $return['new']);
    }

    function testCombinePartialsWorksForExtendedViewHierarchies() {
        $method = $this->getStaticMethod('combinePartials');
        $content = $method('ratingMeter.html.php', 'custom/feedback/ExtendedCustomAnswerFeedback');
        $this->assertStringDoesNotContain($content, 'rn:block');
        $this->assertStringContains($content, 'ExtendedCustomAnswerFeedback - ratingMeter partial');
    }

    function testCombinePartialsSkipsInvalidInput() {
        $method = $this->getStaticMethod('combinePartials');

        // Invalid partial.
        $this->assertFalse($method('bananas', 'custom/feedback/ExtendedCustomAnswerFeedback'));
        // Invalid widget.
        $this->assertFalse($method('bananas', 'custom/bananas'));
    }

    function testCombinePartialsWorksForSingleNonExtendedView() {
        $method = $this->getStaticMethod('combinePartials');
        $content = $method('ratingMeter.html.php', 'standard/feedback/AnswerFeedback');
        $this->assertIsA($content, 'string');
        $this->assertStringDoesNotContain($content, 'rn:block');
    }
}

require_once CPCORE . 'Libraries/ThirdParty/SimpleHtmlDomExtension.php';

class DomBlockTest extends DomExt\BlockDom {
    public $parsings = array();

    function __construct($view) {
        parent::__construct(null, true, true, Dom\Defines::DEFAULT_TARGET_CHARSET, false);
        $this->load($view, true, false);
    }

    function read_tag() {
        $return = parent::read_tag();

        $this->parsings []= (object) array(
            'return'  => $return,
            'char'    => $this->char,
            'content' => $this->node->_[Dom\Defines::HDOM_INFO_INNER],
            'pos'     => $this->pos,
        );

        return $return;
    }
}

class SimpleHtmlDomExtensionTest extends CPTestCase {
    function testLoadDom() {
        $this->assertFalse(DomExt\loadDom(''));
        $this->assertFalse(DomExt\loadDom(null));
        $this->assertFalse(DomExt\loadDom(0));
        $this->assertIsA(DomExt\loadDom('<div/>'), 'RightNow\Libraries\ThirdParty\SimpleHtmlDom\simple_html_dom');
        $this->assertIsA(DomExt\loadDom('<div/>', 1), 'RightNow\Libraries\ThirdParty\SimpleHtmlDom\simple_html_dom');
        $this->assertIsA(DomExt\loadDom('<div/>', false), 'RightNow\Libraries\ThirdParty\SimpleHtmlDomExtension\BlockDom');
    }

    function testReadTag() {
        // Our custom dealie didn't run.
        $view = "<rn:block/>";
        $dom = new DomBlockTest($view);
        $this->assertSame(2, count($dom->parsings));
        $dom = $dom->parsings[0];
        $this->assertTrue($dom->return);
        $this->assertIdentical(strlen($view), $dom->pos);
        $this->assertNull($dom->char);

        // Ditto.
        $view = "<div id='foo'></div>";
        $dom = new DomBlockTest($view);
        $this->assertSame(3, count($dom->parsings));
        $dom = $dom->parsings[2];
        $this->assertFalse($dom->return);
        $this->assertIdentical(strlen($view), $dom->pos);
        $this->assertNull($dom->char);

        // Ditto.
        $view = "<div id='foo";
        $dom = new DomBlockTest($view);
        $this->assertSame(2, count($dom->parsings));
        $dom = $dom->parsings[0];
        $this->assertTrue($dom->return);
        $this->assertIdentical(strlen($view) + 1, $dom->pos);
        $this->assertNull($dom->char);


        // Runs, no inner content
        $view = "<rn:block></rn:block>";
        $dom = new DomBlockTest($view);
        $this->assertSame(2, count($dom->parsings));
        $dom = $dom->parsings[0];
        $this->assertTrue($dom->return);
        $this->assertIdentical(strlen($view), $dom->pos);
        $this->assertIdentical('', $dom->char);

        // Runs, inner content isn't parsed
        $view = "<rn:block id='foo'><div><div><div></rn:block>";
        $dom = new DomBlockTest($view);
        $this->assertSame(2, count($dom->parsings));
        $dom = $dom->parsings[0];
        $this->assertTrue($dom->return);
        $this->assertIdentical(strlen($view), $dom->pos);
        $this->assertIdentical('', $dom->char);
        $this->assertIdentical('<div><div><div>', $dom->content);

        // Runs, inner content isn't parsed, php "noise" is replaced
        $view = '<rn:block id="foo"><div><?= "<div>" ?><div></rn:block>';
        $dom = new DomBlockTest($view);
        $this->assertSame(2, count($dom->parsings));
        $dom = $dom->parsings[0];
        $this->assertTrue($dom->return);
        $this->assertIdentical(strlen($view), $dom->pos);
        $this->assertIdentical('', $dom->char);
        $this->assertIdentical('<div><?= "<div>" ?><div>', $dom->content);
    }

}
