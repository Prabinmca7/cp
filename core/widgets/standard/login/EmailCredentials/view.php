<div id="rn_<?=$this->instanceID;?>" class="<?= $this->classList ?>">
    <rn:block id="top"/>
    <?if($this->data['attrs']['label_heading'] !== ''):?>
        <rn:block id="heading">
        <h2><?=$this->data['attrs']['label_heading']?></h2>
        </rn:block>
    <?endif;?>
    <?if($this->data['attrs']['label_description'] !== ''):?>
        <rn:block id="description">
        <p><?=$this->data['attrs']['label_description']?></p>
        </rn:block>
    <?endif;?>
    <? $selectorPrefix = "rn_{$this->instanceID}_{$this->data['attrs']['credential_type']}"; ?>
    <form id="<?=$selectorPrefix;?>_Form" onsubmit="return false;">
        <rn:block id="label">
        <label for="<?=$selectorPrefix;?>_Input"><?=$this->data['attrs']['label_input'];?></label>
        </rn:block>
        <rn:block id="preInput"/>
        <input id="<?=$selectorPrefix;?>_Input" name="<?=$this->data['js']['request_type'];?>" type="text" maxlength="255" autocorrect="off" autocapitalize="off" value="<?= isset($this->data['email']) ? $this->data['email'] : '';?>" />
        <rn:block id="postInput"/>
        <rn:block id="preSubmit"/>
        <input id="<?=$selectorPrefix;?>_Submit" type="submit" value="<?=$this->data['attrs']['label_button']?>" />
        <rn:block id="postSubmit"/>
        <div id="<?=$selectorPrefix;?>_LoadingIcon"></div>
    </form>
    <rn:block id="bottom"/>
</div>
