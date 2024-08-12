<html>
    <head>
        <meta charset="utf-8"/>
        <style type='text/css'>
            .ok-highlight-title {background-color: #FF0;font-weight: bold;}
            .ok-highlight-sentence {background-color: #EBEFF5;}
            iframe {min-width: 100%; border-top-width:0; border-left-width:0; border-style:ridge; width:100%}
            #headerDiv {line-height: 1.5em; text-align: left;margin-top:-5px;font-size: 95%; height: 25px; padding: 1px 0 5px 15px; text-shadow: 0 1px 0 #fff;}
            #frameDiv {min-width: 100%;border-width: 0;border:1;}
            .leftAnchor {padding: 0 10px;}
            .rightAnchor {padding-left: 10px;}
        </style>
    </head>
    <body>
        <?if($error === null) : ?>
            <div id="headerDiv">
                <span style="margin-left:30%;text-align: center;"><?= $highlightMsg;?></span>
                <span style="float:right;margin-right:5px;">
                    <a class="leftAnchor" href="javascript:void(0);" onclick="window.prompt('<?= $copyClipboardMsg?>', '<?= $url;?>')"><?= $copyLinkLable;?></a>|<a class="rightAnchor" href="javascript:void(0);" onclick="window.open('<?= $url;?>');"><?= $viewLabel;?></a>
                </span>
            </div>
            <?if($type !== null) : ?>
                <iframe src='<?= $file;?>' style="height: 94%;"/>
            <?else if($html !== null): ?>
                <frame><div id="frameDiv"><?= $html;?><div></frame>
            <?endif; ?>
        <?else: ?>
            <?= $error;?>
        <?endif; ?>
    </body>
</html>