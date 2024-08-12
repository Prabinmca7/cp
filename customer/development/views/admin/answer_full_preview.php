<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8"/>
    <title><?=\RightNow\Utils\Config::getMessage(ANSWER_PRINT_PAGE_LBL);?></title>
    <style type='text/css'>
        .rn_ScreenReaderOnly{position:absolute; height:1px; left:-10000px; overflow:hidden; top:auto; width:1px;}
        .rn_Hidden{display:none;}
    </style>
    <link href="/euf/assets/themes/standard/site.css" rel="stylesheet" type="text/css" media="all" />
</head>

<body>
<div class="rn_AnswerPreview">
    <div class="rn_Header"></div>
    <div class="rn_Container">
        <div class="rn_Body">
            <div class="rn_MainColumn">
                <div class="rn_PageTitle rn_RecordDetail rn_AnswerDetail">
                    <h1 class="rn_Summary"><?=\RightNow\Utils\Text::escapeHtml($answer->Summary);?></h1>
                    <div class="rn_AnswerInfo"></div>
                    <div class="rn_AnswerQuestion">
                        <?=$answer->Question;?>
                    </div>
                </div>
                <div class="rn_PageContent rn_RecordDetail rn_AnswerDetail">
                    <div class="rn_RecordText rn_AnswerText">
                        <?=$answer->Solution;?>
                    </div>
                    <?if($answer->GuidedAssistance):?>
                    <div class="rn_Node rn_Question">
                        <div class="rn_QuestionText">
                            <p><?= \RightNow\Utils\Config::getMessage(GUIDE_TH_BUT_CANNOT_PREVIEWED_TH_PAGE_MSG);?></p>
                        </div>
                    </div>
                    <?endif;?>
                    <div class="rn_FileAttach" class="rn_FileListDisplay">
                        <?if(count($answer->FileAttachments) > 0):?>
                            <span class="rn_DataLabel"> <?= \RightNow\Utils\Config::getMessage(FILE_ATTACHMENTS_LBL);?> </span>
                            <div class="rn_DataValue rn_FileList">
                                <ul>
                                    <?foreach($answer->FileAttachments as $attachment):?>
                                    <li>
                                        <a href="<?=$attachment->URL . '/' . $attachment->CreatedTime . \RightNow\Utils\Url::sessionParameter();?>" target="_blank">
                                            <?=\RightNow\Utils\Framework::getIcon($attachment->FileName);?>
                                            <?=$attachment->FileName;?>
                                        </a>
                                    </li>
                                    <?endforeach;?>
                                </ul>
                            </div>
                        <?endif;?>
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
        //Let anchor links stay in the same window but all others should show in a new window due to issues using the dotnet client browser
        if(hashLocation[1] === undefined || hashLocation[0].indexOf("<?=\RightNow\Utils\Url::getShortEufBaseUrl('sameAsCurrentPage', '/');?>") !== 0){
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
