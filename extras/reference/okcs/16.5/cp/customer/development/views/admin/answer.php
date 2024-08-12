<!DOCTYPE html>
<html>
    <head>
        <meta charset="utf-8"/>
        <style type='text/css'>
            .ok-highlight-title {background-color: #FF0;font-weight: bold;}
            .ok-highlight-sentence {background-color: #EBEFF5;}
            iframe {min-width: 100%; border-top-width: 0; border-left-width: 0; border-style: ridge; width: 100%; height: 100%;}
            .mainDiv {width: 100%;height: 100%;overflow: hidden;}
            .headerDiv {width: 100%;height: 5%;position: fixed;background-color: #40526b;top: 0px;min-height: 50px;color: white;font-size: 1em;line-height: 1.5em;font-weight: bold;padding: 10px 0 0px 15px;min-height: 50px;text-align: left;overflow: hidden;}
            .containerDiv {width: 100%;height: 90%;margin-top: 75px;overflow: hidden;position: fixed;}
            .childDiv {width: 100%;height: 100%;overflow: auto;}
            .iframeDiv {height: 95%;}
            .leftAnchor {padding: 0 10px;color: white;}
            .rightAnchor {padding-left: 10px;color: white; margin-right: 15px;}
        </style>
        <link type="text/css" rel="stylesheet" href="/euf/core/thirdParty/css/font-awesome.min.css"/>
    </head>
    <body style="margin: 0px;">
        <? if($error === null) : ?>
            <div class="mainDiv">
                <div class="headerDiv">
                    <? $CI = get_instance(); ?>
                    <? if(($type === 'PDF' && ($CI->agent->browser() === 'Internet Explorer')) || $type !== 'PDF'): ?>
                        <span style="margin-left:30%;text-align: center;"><?= $highlightMsg;?></span>
                        <span style="margin-right:5px;margin-top:5px;">
                            <? if($type === 'PDF' && ($CI->agent->browser() === 'Internet Explorer')): ?>
                                <i class="icon-info-sign rightAnchor" onclick="alert('Install dt-search plugin to view the highlighted version of the PDF')"></i>
                            <? endif; ?>
                        </span>
                        <span style="float:right;margin-right:5px;">
                        <a class="leftAnchor" href="javascript:void(0);" onclick="window.prompt('<?= $copyClipboardMsg?>', '<?= $url;?>')"><?= $copyLinkLable;?></a>|<a class="rightAnchor" href="javascript:void(0);" onclick="window.open('<?= $url;?>');"><?= $viewLabel;?></a>
                        </span>
                    <? elseif($type === 'PDF'): ?>
                        <span style="float:right;margin-right:5px;margin-top:5px;">
                            <a class="rightAnchor" href="javascript:void(0);" onclick="window.prompt('<?= $copyClipboardMsg?>', '<?= $url;?>')"><?= $copyLinkLable;?></a>
                        </span>
                    <? endif; ?>
                </div>
            
                <div class="containerDiv">
                    <div class="childDiv">
                    <? if($type !== null) : ?>
                        <div class="iframeDiv"><iframe src='<?= $file;?>'/></div>
                    <? elseif($html !== null): ?>
                        <div><frame><div><?= $html;?></div></frame></div>
                    <? endif; ?>
                    </div>
                </div>
            </div>
        <? else: ?>
            <?= $error;?>
        <? endif; ?>
    </body>
</html>
