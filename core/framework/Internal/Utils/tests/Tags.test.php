<?php
use RightNow\Internal\Utils;

RightNow\UnitTest\Helper::loadTestedFile(__FILE__);

class InternalTagsTest extends CPTestCase {

    public $testingClass = 'RightNow\Internal\Utils\Tags';

    public static $body;

    public static $template;

    function testGetWidgetTagPosition(){
        $getWidgetTagPosition = $this->getMethod("getWidgetTagPosition", true);
        $mergedContent = str_replace("<rn:page_content/>", self::$body, self::$template);
        $parseOptions = array("mergedContent" => $mergedContent, "template" => self::$template, "body" => self::$body, "noMetaBody" => self::$body);

        $widgetTag = '<rn:widget path="utils/ClickjackPrevention"/>';
        list($position, $parseOptions) = $getWidgetTagPosition(array($widgetTag), $parseOptions);
        $this->assertEqual($position, strpos(self::$template, $widgetTag, strpos(self::$template, $widgetTag))."@template@".strlen($widgetTag));

        $widgetTag = '<rn:widget path="searchsource/SourceSearchField" initial_focus="true"/>';
        list($position, $parseOptions) = $getWidgetTagPosition(array($widgetTag), $parseOptions);
        $this->assertEqual($position, ($actualPos = strpos(self::$body, $widgetTag, strpos(self::$body, $widgetTag)))."@body@".strlen($widgetTag));

        $widgetTag = '<rn:widget path="searchsource/SourceSearchField" initial_focus="true"/>';
        list($position, $parseOptions) = $getWidgetTagPosition(array($widgetTag), $parseOptions);
        $this->assertEqual($position, strpos(self::$body, $widgetTag, $actualPos+1)."@body@".strlen($widgetTag));

        $widgetTag = '<rn:widget path="searchsource/SourceSearchField" initial_focus="true"/>';
        list($position, $parseOptions) = $getWidgetTagPosition(array($widgetTag), $parseOptions);
        $this->assertEqual($position, strpos(self::$template, $widgetTag, strpos(self::$template, $widgetTag))."@template@".strlen($widgetTag));
    }
}

InternalTagsTest::$body = <<<EOT
    <form method="get" action="/app/results">
        <rn:container source_id="KFSearch">
            <!--rn:widget path="searchsource/SourceSearchField" initial_focus="true"/-->
            <div class="rn_SearchInput">
                <rn:widget path="searchsource/SourceSearchField" initial_focus="true"/>
            </div>
            <rn:widget path="searchsource/SourceSearchButton" search_results_url="/app/results"/>
            <div id="duplicate">
                <rn:widget path="searchsource/SourceSearchField" initial_focus="true"/>
            </div>
        </rn:container>
    </form>
EOT;

InternalTagsTest::$template = <<<EOT
    <!DOCTYPE html>
    <html lang="#rn:language_code#">
    <rn:meta javascript_module="standard"/>
    <head>
        <rn:widget path="utils/ClickjackPrevention"/>
        <rn:widget path="utils/AdvancedSecurityHeaders"/>
        <meta name="viewport" content="width=device-width, initial-scale=1">
    </head>
    <body class="yui-skin-sam yui3-skin-sam" itemscope itemtype="http://schema.org/WebPage">
        <rn:page_content/>
        <footer class="rn_Footer">
            <div class="rn_Container">
                <rn:widget path="search/ProductCategoryList" report_page_url="/app/products/detail"/>
                <div class="rn_Misc">
                    <rn:widget path="utils/PageSetSelector"/>
                    <rn:widget path="utils/OracleLogo"/>
                </div>
                <rn:widget path="searchsource/SourceSearchField" initial_focus="true"/>
            </div>
        </footer>
    </body>
    </html>
EOT;
