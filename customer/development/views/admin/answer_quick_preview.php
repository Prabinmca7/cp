<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8"/>
    <base href="<?=\RightNow\Utils\Url::getShortEufBaseUrl(false, '/');?>" />
    <title><?=\RightNow\Utils\Config::getMessage(ANSWER_QUICK_PREVIEW_LBL);?></title>
    <link href="/euf/assets/themes/standard/site.css" rel="stylesheet" type="text/css" media="all" />
</head>
<body>
<div class="rn_AnswerPreview">
    <div class="rn_Header"></div>
    <div class="rn_Container">
        <div class="rn_Body">
            <div class="rn_MainColumn">
                <div class="rn_PageTitle rn_RecordDetail rn_AnswerDetail">
                    <h1 class="rn_Summary"><?=\RightNow\Utils\Text::escapeHtml($summary);?></h1>
                    <div class="rn_AnswerInfo"></div>
                    <div class="rn_AnswerQuestion">
                        <?=$description;?>
                    </div>
                </div>
                <div class="rn_PageContent rn_RecordDetail rn_AnswerDetail">
                    <div class="rn_RecordText rn_AnswerText">
                        <?=$solution;?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<script type="text/javascript">
    var tags = document.getElementsByTagName('a');
    for (var i=0; i<tags.length; i++)
    {
        var hashLocation = tags[i].href.split("#");
        //Fix anchor links (i.e. href="#Bottom") because of the base tag. Also don't change their target
        if(hashLocation[1] !== undefined && hashLocation[0] === "<?=\RightNow\Utils\Url::getShortEufBaseUrl(false, '/');?>"){
            tags[i].href = "about:blank#" + hashLocation[1];
        }
        else{
            tags[i].target = "_blank";
        }
    }

    tags = document.getElementsByTagName('form');
    for (var i=0; i<tags.length; i++)
    {
        tags[i].onsubmit = function(){alert("<?=\RightNow\Utils\Config::getMessageJS(DISABLED_FOR_PREVIEW_MSG);?>"); return false;};
    }
</script>
</body>
</html>
