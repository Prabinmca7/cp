<!DOCTYPE html>
<html>
    <head>
        <rn:meta javascript_module="mobile"/>
        <title><rn:page_title/></title>
        <rn:theme path="/euf/assets/themes/mobile"/>
    </head>
    <body>
        <rn:page_content/>
        <script src="<?= \RightNow\Utils\Url::getYUICodePath('combined-yui.js') ?>"></script>
        <script type="text/javascript" src="<?= $coreAssetPrefix ?>debug-js/tests/RightNow.UnitTest.js"></script>
    </body>
</html>
