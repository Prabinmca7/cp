<!DOCTYPE html>
<html>
    <head>
        <title>Core JavaScript Unit Test</title>
        <script>
            YUI_config = {
                comboBase: "<?= $comboService ?>"
            };
            labels = {};
        <? if( $formToken && $submitTokenExp ){ ?> 
            formToken = "<?= $formToken ?>";
            submitTokenExp = <?= $submitTokenExp ?>;
        <?
            foreach($labels as $key => $value){
        ?>
              labels.<?=$key?> = "<?=$value?>";          
        <?  }
           }
        ?>
        </script>
    </head>
    <body>
        <?= $pageContent ?>
        <script src="<?=\RightNow\Utils\Url::getYUICodePath('combined-yui.js');?>"></script>
        <script src="<?= $coreAssetPrefix ?>debug-js/RightNow.js"></script>
        <script src="<?= $coreAssetPrefix ?>debug-js/tests/RightNow.UnitTest.js"></script>
        <? if($group === 'admin'){ ?>
            <script src="<?= $coreAssetPrefix ?>/admin/js/formToken.js"></script>
        <?}?>
        <script src="<?= $testFile ?>"></script>
    </body>
</html>
