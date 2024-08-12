<!DOCTYPE html>
<html lang="en">
<head>
    <title>JavaScript Widget Interaction Test Links</title>
    <style type="text/css" media="screen">
        body{
            font-family: 'Helvetica Neue', Helvetica, sans-serif;
            margin: auto;
            padding:20px 0;
            text-shadow:0 1px 0 #fff;
            width: 80%;
        }
        h2{
            color: #447AA4;
            background-color: #e7f3f8;
            -moz-border-radius: 6px;
            -webkit-border-radius: 6px;
            border-radius: 6px;
            font-weight: normal;
            padding: 10px;
        }
        h3{
            color: #082d3f;
            font-weight: normal;
            margin-left: 10px;
        }
        a{
            color: #4e7f98;
            text-decoration: none;
        }
        a:hover,a:focus{
            text-decoration: underline;
        }
        button{
            float: right;
        }
    </style>
</head>
<body>
    <h1>Available JavaScript Widget Interaction Tests</h1><p>All links open in a new tab.</p>
<?
$group; $widget; $testContents = '';
foreach($tests as $testPage) {
    $fields = explode('/', $testPage, 10); // e.g. ci/unitTest/rendering/widgets/standard/folder/WidgetName/tests/testName
    $testName = array_pop($fields);
    array_pop($fields); // 'tests'
    $widgetName = array_pop($fields);
    $folder = array_pop($fields);

    if ($folder !== $group) {
        if ($widget !== null) {
            $testContents .= "</ul></div>\n";
        }
        $testContents .= "<h2>$folder</h2>\n";
        $widget = null;
        $group = $folder;
    }
    if ($widgetName !== $widget) {
        if ($widget !== null) {
            $testContents .= "</ul></div>\n";
        }
        $testContents .= "<div><button type='button'>Run all tests</button><h3>$widgetName</h3>\n<ul>\n";
        $widget = $widgetName;
    }
    $page = explode('/', $testName, 2);
    $params = explode('/', $page[1]);

    //Strip fixture tags from urlparams, since they are unused in widgetJS unit tests
    if(\RightNow\Utils\Text::stringContains($page[1], "%")) {
        for($i = 0; $i <= sizeof($params); $i++) {
            if($i % 2 === 1 &&
               \RightNow\Utils\Text::beginsWith($params[$i], "%") &&
               \RightNow\Utils\Text::endsWith($params[$i], "%")) {
                unset($params[$i]);
                unset($params[$i-1]);
            }
        }

        $testPage = \RightNow\Utils\Text::getSubStringBefore($testPage, $page[1]);
        if($params) {
            $page[1] = implode('/', $params);
            $testPage .= $page[1];
        }
        else {
            $testName = \RightNow\Utils\Text::getSubStringBefore($testName, '/' . $page[1]);
            unset($page[1]);
        }
    }
    $testContents .= "<li><a target='_blank' href='/$testPage'>" . (count($page) > 1 ? "{$page[0]}</a> <small>(/{$page[1]})</small>" : "$testName</a>") . "</li>\n";
}
echo $testContents;
?>
<script src="http://yui.yahooapis.com/3.3.0/build/yui/yui-min.js"></script>
<script type="text/javascript">
YUI().use('node-base', function(Y) {
    Y.on("click", function(e) {
        e.target.get("parentNode").all("a").each(function(a) {
            window.open(a.get("href"));
        });
    }, "button");
});
</script>
</body>
</html>