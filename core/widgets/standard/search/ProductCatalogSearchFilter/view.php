<div id="rn_<?=$this->instanceID;?>" class="<?= $this->classList ?>">
    <rn:block id="top"/>
    <rn:block id="preLink"/>
    <a href="javascript:void(0);" class="rn_ScreenReaderOnly" id="rn_<?=$this->instanceID?>_LinksTrigger"><?printf($this->data['attrs']['label_screen_reader_accessible_option'], $this->data['attrs']['label_input'])?>&nbsp;<span id="rn_<?=$this->instanceID;?>_TreeDescription"></span></a>
    <rn:block id="postLink"/>
    <rn:block id="preLabel"/>
    <span class="rn_Label"><?= $this->data['attrs']['label_input'] ?></span>
    <rn:block id="postLabel"/>
    <rn:block id="preButton"/>
    <button type="button" id="rn_<?=$this->instanceID;?>_ProductCatalog_Button" class="rn_DisplayButton"><span class="rn_ScreenReaderOnly"><?=$this->data['attrs']['label_accessible_interface']?></span> <span id="rn_<?=$this->instanceID?>_Button_Visible_Text"><?=$this->data['attrs']['label_nothing_selected'];?></span></button>
    <rn:block id="postButton"/>
    <div class="rn_ProductCatalogLinks rn_Hidden" id="rn_<?=$this->instanceID;?>_Links"></div>
    <div id="rn_<?=$this->instanceID;?>_TreeContainer" class="rn_PanelContainer rn_Hidden">
        <rn:block id="preTree"/>
        <div id="rn_<?=$this->instanceID;?>_Tree" class="rn_Panel"><? /* YUI TreeView is created here */?></div>
    <? if ($this->data['attrs']['show_confirm_button_in_dialog']): ?>
        <rn:block id="preConfirmButton"/>
        <div id="rn_<?=$this->instanceID;?>_SelectionButtons" class="rn_SelectionButtons">
            <rn:block id="confirmButtonTop"/>
            <button type="button" id="rn_<?=$this->instanceID;?>_ProductCatalog_ConfirmButton"><?=$this->data['attrs']['label_confirm_button'];?></button>
            <button type="button" id="rn_<?=$this->instanceID;?>_ProductCatalog_CancelButton"><?=$this->data['attrs']['label_cancel_button'];?></button>
            <rn:block id="confirmButtonBottom"/>
        </div>
        <rn:block id="postConfirmButton"/>
    <? endif; ?>
        <rn:block id="postTree"/>
    </div>
    <rn:block id="bottom"/>
</div>