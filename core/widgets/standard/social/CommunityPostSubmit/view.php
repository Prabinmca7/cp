<div id="rn_<?=$this->instanceID;?>" class="<?= $this->classList ?>">
<rn:block id="top"/>
<? if($this->data['createAccountURL']):?>
    <rn:block id="link">
    <a href="<?=$this->data['createAccountURL'];?>"><?=$this->data['attrs']['label_create_account']?></a>
    </rn:block>
<? else:?>
    <form id="rn_<?=$this->instanceID;?>_Form" onsubmit="return false;">
        <rn:block id="preTitle"/>
        <label for="rn_<?=$this->instanceID;?>_Title" class="rn_Label">
            <?=$this->data['attrs']['label_title_field']?>
                <?= $this->render('Partials.Forms.RequiredLabel') ?>
            <? if($this->data['attrs']['label_title_hint']):?>
                <span class="rn_ScreenReaderOnly"> <?=$this->data['attrs']['label_title_hint'];?></span>
            <? endif;?>
        </label>
        <rn:block id="postTitle"/>
        <input type="text" id="rn_<?=$this->instanceID;?>_Title" name="<?=$this->data['fields'][0]->id;?>"/>
        <label for="rn_<?=$this->instanceID;?>_Body" class="rn_Label">
            <?=$this->data['attrs']['label_body_field']?>
                <?= $this->render('Partials.Forms.RequiredLabel') ?>
            <? if($this->data['attrs']['label_body_hint']):?>
                <span class="rn_ScreenReaderOnly"> <?=$this->data['attrs']['label_body_hint'];?></span>
            <? endif;?>
        </label>
        <rn:block id="preTextInput"/>
        <textarea rows="4" cols="5" id="rn_<?=$this->instanceID;?>_Body" name="<?=$this->data['fields'][1]->id;?>"></textarea>
        <rn:block id="postTextInput"/>
        <rn:block id="preSubmit"/>
        <input id="rn_<?=$this->instanceID;?>_Submit" type="submit" value="<?=$this->data['attrs']['label_submit_button']?>"/>
        <rn:block id="postSubmit"/>
        <? if($this->data['attrs']['loading_icon_path']):?>
            <img id="rn_<?=$this->instanceID;?>_LoadingIcon" class="rn_Hidden" alt="<?=\RightNow\Utils\Config::getMessage(LOADING_LBL)?>" src="<?=$this->data['attrs']['loading_icon_path'];?>"/>
        <? endif;?>
        <rn:block id="preStatus"/>
        <span id="rn_<?=$this->instanceID;?>_StatusMessage"></span>
        <rn:block id="postStatus"/>
    </form>
<? endif;?>
<rn:block id="bottom"/>
</div>
