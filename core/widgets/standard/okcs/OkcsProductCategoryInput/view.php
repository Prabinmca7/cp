<div id="rn_<?=$this->instanceID;?>" class="<?= $this->classList ?>">
    <rn:block id="top"/>
    <a href="javascript:void(0);" class="rn_ScreenReaderOnly" id="rn_<?=$this->instanceID?>_LinksTrigger"><span id="rn_<?=$this->instanceID;?>_TreeDescription"></span>&nbsp;<?printf($this->data['attrs']['label_screen_reader_accessible_option'], $this->data['attrs']['label_input'])?></a>
    <span class="rn_Label" id="rn_<?= $this->instanceID ?>_Label">
        <rn:block id="preLabel"/>
        <?=$this->data['attrs']['label_input']?>
        <? if($this->data['attrs']['label_input']):?>
            <span id="rn_<?=$this->instanceID;?>_RequiredLabel" class="rn_Required rn_Hidden"> <?=\RightNow\Utils\Config::getMessage(FIELD_REQUIRED_MARK_LBL);?></span>
            <span id="rn_<?=$this->instanceID;?>_ErrorLabel" class="rn_ErrorLabel">
                <? if(isset($this->data['attrs']['isRequired']) && $this->data['attrs']['isRequired']):?>
                    <span class="rn_ScreenReaderOnly"> <?=\RightNow\Utils\Config::getMessage(REQUIRED_LBL);?> </span>
                <? endif;?>
            </span>
        <? endif;?>
        <rn:block id="postLabel"/>
    </span>
    <rn:block id="preButton"/>
    <button type="button" id="rn_<?= $this->instanceID ?>_<?= $this->data['attrs']['filter_type'] ?>_Button" class="rn_DisplayButton"><span class="rn_ScreenReaderOnly"><?= $this->data['attrs']['label_accessible_interface'] ?></span> <span id="rn_<?= $this->instanceID ?>_ButtonVisibleText"><?= $this->data['attrs']['label_nothing_selected'] ?></span></button>
    <rn:block id="postButton"/>
    <div class="rn_ProductCategoryLinks rn_Hidden" id="rn_<?=$this->instanceID;?>_Links"></div>
    <div id="rn_<?=$this->instanceID;?>_TreeContainer" class="rn_PanelContainer rn_Hidden">
        <rn:block id="preTree"/>
        <div id="rn_<?=$this->instanceID;?>_Tree" class="rn_Panel">
            <? /* Product / Category YUI TreeView is created here */?>
        </div>
        <rn:block id="postTree"/>
    </div>
    <rn:block id="bottom"/>
</div>

