<?
/**
 * File: view.php
 * Abstract: PHP view for ClickCounterWithAjax widget
 * Version: 1.0
 */
?>
<div id="rn_<?= $this->instanceID ?>" class="<?= $this->classList ?>">
<rn:block id="top"/>
    <div id="rn_<?=$this->instanceID?>_Message" class="rn_ClickCounterWithAjaxMessage">
        <span class="<?=$this->data['spanClass']?>"><?=$this->data['attrs']['label_message']?></span>
    </div>
    <rn:block id="middle"/>
    <div>
        <button id="rn_<?=$this->instanceID?>_Button"><?=$this->data['attrs']['label_button']?></button>
    </div>
<rn:block id="bottom"/>
</div>
