<!DOCTYPE html>
<html lang="<?=\RightNow\Utils\Text::getLanguageCode();?>" class="noBg">
<head>
    <meta http-equiv="x-ua-compatible" content="IE=9">
    <meta charset="utf-8"/>
    <title><?=$siteTitle;?></title>
    <base href="<?=\RightNow\Utils\Url::getShortEufBaseUrl('sameAsRequest', \RightNow\Utils\Url::getCoreAssetPath());?>"></base>
    <link rel="stylesheet" type="text/css" href="admin/css/admin.css" media="screen,projection"/>
    <link rel="icon" href="/euf/core/static/favicon.ico" type="image/x-icon"/>
    <script src="<?=\RightNow\Utils\Url::getYUICodePath('combined-yui.js');?>"></script>
    <script>
        YUI_config = {'comboBase':'<?=((IS_HOSTED) ? "//" . getConfig(CACHED_CONTENT_SERVER) : "") . "/ci/cache/yuiCombo/";?>','lang':['<?=\RightNow\Utils\Text::getLanguageCode();?>','en-US'],'fetchCSS':false};
        labels = {};
        <?if($formToken && $submitTokenExp && $labels):?>
            formToken = "<?= $formToken ?>";
            submitTokenExp = <?= $submitTokenExp ?>;
            <?foreach($labels as $key => $value):?>
                  labels.<?=$key?> = "<?=$value?>";
            <?endforeach;?>
        <?endif;?>
    </script>
    <?= $css ?>
</head>

<body class="yui-skin-sam yui3-skin-sam plain">
<div id="wrap" class="plain">
    <div id="content">
        <?=$content;?>
    </div>
</div>
<script src="admin/js/formToken.js"></script>
<?= $js ?>
</body>
</html>
