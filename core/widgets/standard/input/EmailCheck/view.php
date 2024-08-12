<div id="rn_<?=$this->instanceID;?>" class="<?= $this->classList ?>">
    <rn:block id="top"/>
    <form onsubmit="return false;">
        <rn:block id="formTop"/>
        <span class="rn_EmailCheckField">
            <rn:block id="preEmailLabel"/>
            <label for="rn_<?=$this->instanceID;?>_Email" class="rn_Label"><?=$this->data['attrs']['label_input'];?></label>
            <rn:block id="postEmailLabel"/>
            <rn:block id="preEmailInput"/>
            <input type="email" id="rn_<?=$this->instanceID;?>_Email" class="rn_Text" value="<?=$this->data['initialValue'];?>"/>
            <rn:block id="postEmailInput"/>
        </span>
        <span class="rn_EmailCheckSubmit">
            <? if($this->data['isIE']): ?>
            <label for="rn_<?=$this->instanceID;?>_HiddenInput" class="rn_Hidden">&nbsp;</label>
            <input id="rn_<?=$this->instanceID;?>_HiddenInput" type="text" class="rn_Hidden" disabled="disabled" />
            <? endif;?>
            <rn:block id="preSubmit"/>
            <input type="submit" id="rn_<?=$this->instanceID;?>_Submit" value="<?=$this->data['attrs']['label_button'];?>"/>
            <rn:block id="postSubmit"/>
            <? if($this->data['attrs']['loading_icon_path']):?>
            <img id="rn_<?=$this->instanceID;?>_LoadingIcon" class="rn_Hidden" alt="<?=\RightNow\Utils\Config::getMessage(LOADING_LBL)?>" src="<?=$this->data['attrs']['loading_icon_path'];?>" />
            <? endif;?>
        </span>
        <rn:block id="formBottom"/>
    </form>
    <rn:block id="bottom"/>
</div>
