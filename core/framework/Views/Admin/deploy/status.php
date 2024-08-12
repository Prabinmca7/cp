<div id='formSubmitted'>
    <h1><?=$statusLabel;?><span class="<?=$status;?>Text"><?=$statusMessage;?></span></h1>
    <?if (is_array($errors)):?>
        <?foreach($errors as $error):?>
            <div class="errorLogEntry"><?=$errorLabel;?>&nbsp;<?=$error;?></div>
        <?endforeach;?>
    <?endif;?>
    <?if (isset($warnings) && is_array($warnings)): ?>
        <?foreach($warnings as $warning):?>
            <div class="warnLogEntry"><?=$warning;?></div>
        <?endforeach;?>
    <?endif;?>
    <br/>
    <div class="underlinedLink">
        <?if (is_array($links)):?>
            <?foreach($links as $href => $label):?>
                <a href="<?=$href;?>"><?=$label;?></a><br>
            <?endforeach;?>
        <?endif;?>
        <div>
        <?=$logContents;?>
        </div>
    </div>
</div>
