<?php
use RightNow\Utils\Url;
require_once DOCROOT.'/ma/util.phph';
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>
<title><?=\RightNow\Utils\Config::getMessage(POLLING_SURVEY_RESULTS_LBL) ?></title>
<style type="text/css">
.chart
{
width:400px;
height:300px;
margin-top: 20px;
padding-left: 20px;
margin-bottom: 20px;
}
.resultsDiv
{
margin: 20px;
border: 1px solid #000;
padding: 5px;
}
p
{
color:#083772;
margin:0px;
}
#wrap
{
margin: 0px auto;
overflow: hidden;
width: 920px;
background-color:#FFFFFF;
}
body
{
background-color:#000000;
}
#titleHeading
{
margin-left: 20px;
color:#1A8740;
float:left;
}
#options
{
margin: 20px;
float:right;
}
#heading
{
height: 90px;
}
</style>
<link rel="shortcut icon" href="/euf/core/static/favicon.ico" type="image/x-icon"/>
<link rel="stylesheet" type="text/css" href="<?=\RightNow\Utils\Url::getYUICodePath('widget-base/assets/skins/sam/widget-base.css');?>"/>
<link rel="stylesheet" type="text/css" href="<?=\RightNow\Utils\Url::getYUICodePath('widget-stack/assets/skins/sam/widget-stack.css');?>"/>

<script>
    <?
        $cachedContentServer = "/ci/cache/yuiCombo/";
        if(IS_HOSTED && ($cachedServerConfig = getConfig(CACHED_CONTENT_SERVER))){
            $cachedContentServer = "//{$cachedServerConfig}{$cachedContentServer}";
        }
    ?>
    YUI_config = {'comboBase':'<?=$cachedContentServer;?>','lang':['<?=\RightNow\Utils\Text::getLanguageCode();?>','en-US'],'fetchCSS':false};
</script>
<script src="<?=\RightNow\Utils\Url::getYUICodePath('combined-yui.js');?>"></script>
<script type="text/javascript">
function buildChart(jsonString, elementName)
{
    YUI().use('charts', 'json-parse', function(Y) {

        data = Y.JSON.parse(jsonString);
        var dataValues = [];
        for (var i = 0; i < data.length; i++)
            dataValues.push({category:data[i].response, values:data[i].percent_total});

        var chartType = 'bar';
        var chartAxes = {
            x:{
                keys:["values"],
                position:"bottom",
                type:"numeric",
                styles:{
                    majorTicks:{display: "none", length: 0},
                    minorTicks:{display: "none", length: 0},
                    label:{display: "none"},
                    line:{color: "#000000"}
                }
            },
            y:{
                keys:["category"],
                position:"left",
                type:"category",
                styles:{
                    majorTicks:{display: "none", length: 0},
                    minorTicks:{display: "none", length: 0},
                    label:{color: "#083772"},
                    line:{color: "#000000"}
                }
            }
        };
        var seriesCollection = [
            {
                categoryKey: "category",
                valueKey: "values",
                styles: {
                    fill: {color: "#1A8740"}
                }
            }
        ];

        var chartTooltip = {
            markerLabelFunction: function(categoryItem, valueItem, itemIndex, series, seriesIndex){
                return categoryItem.value + "\n" + valueItem.value + "%";
            },
            styles: {
                backgroundColor: "#000000",
                color: "#FFFFFF",
                border: "none"
            }
        };

        var chart =  new Y.Chart({
                       dataProvider: dataValues,
                       axes: chartAxes,
                       type: chartType,
                       seriesCollection: seriesCollection,
                       render: '#' + elementName,
                       tooltip: chartTooltip
                 });

    });
}
function changeQuestion()
{
    var questionMenu = document.getElementById("questionMenu");
    var selectedQuestionID = questionMenu.options[questionMenu.selectedIndex].value;
    window.location = "/ci/pollingResults/index/questionID/" + selectedQuestionID + "/surveyID/<?=$this->surveyID?>";
}
</script>
</head>

<body>
<div id="wrap">
<div id="heading">
    <h1 id="titleHeading"><?=$this->title?></h1>
    <div id="options">
    <p><?=\RightNow\Utils\Config::getMessage(SELECT_QUESTION_LBL);?></p>
    <select id="questionMenu">
        <?foreach ($this->questionIDNameList as $questionID => $name): ?>
        <?if ($questionID === intval($this->questionID)):?>
        <option selected="selected" value=<?=$questionID?>><?=$name?></option>
        <?else:?>
        <option value=<?=$questionID?>><?=$name?></option>
        <?endif;?>
        <?endforeach;?>
    </select>
    <button onclick="changeQuestion()"><?=\RightNow\Utils\Config::getMessage(CONSOLE_GO_CMD);?></button>
    </div>
</div>
<div class="resultsDiv">
    <p><?=$this->results['question_name'];?></p>
    <hr>
    <div id="rn_<?=$i?>" class="chart">
        <script type="text/javascript">
        buildChart('<?=$this->results['question_results']?>', "rn_<?=$i++?>");
        </script>
    </div>
    <hr>
    <div>
    <p><?=\RightNow\Utils\Config::getMessage(TOTAL_VOTES_LBL);?> <?=$this->results['total'];?></p>
    </div>
</div>
</body>
</html>
