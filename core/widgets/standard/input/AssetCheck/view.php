<div id="rn_<?=$this->instanceID;?>" class="<?= $this->classList ?>">
    <rn:block id="top"/>
    <form onsubmit="return false;" id="rn_<?=$this->instanceID;?>_AssetCheckForm">
        <rn:block id="formTop"/>
        <div class="rn_Submit">
            <rn:block id="preProductSelectInput"/>
            <rn:widget path="input/ProductCatalogInput"/>
            <rn:block id="postProductSelectInput"/>
            <div id="rn_<?=$this->instanceID;?>_ProductSelectedMsgScreenReader" class="rn_ScreenReaderOnly" role="alert"></div>
        </div>

        <div class="rn_Submit">
            <rn:block id="preAssetSerialNumberLabel"/>
            <label for="rn_<?=$this->instanceID;?>_AssetSerialNumberInput" class="rn_Label" id="rn_<?=$this->instanceID;?>_Label"><?=$this->data['attrs']['serial_number_label_input'];?></label>
            <rn:block id="postAssetSerialNumberLabel"/>
            <rn:block id="preAssetSerialNumberInput"/>
            <input id="rn_<?=$this->instanceID;?>_AssetSerialNumberInput" class="rn_Text" disabled />
            <div id="rn_<?=$this->instanceID;?>_ProductSelectedMsg" class="rn_Hidden"></div>
            <rn:block id="postAssetSerialNumberInput"/>
        </div>

        <div class="rn_Submit">
            <rn:block id="preSubmit"/>
            <input type="submit" id="rn_<?=$this->instanceID;?>_SerialNumberSubmit" value="<?=$this->data['attrs']['label_button'];?>"/>
            <rn:block id="postSubmit"/>
            <? if($this->data['attrs']['loading_icon_path']):?>
                <img id="rn_<?=$this->instanceID;?>_LoadingIcon" class="rn_Hidden" alt="<?=\RightNow\Utils\Config::getMessage(LOADING_LBL)?>" src="<?=$this->data['attrs']['loading_icon_path'];?>" />
            <? endif;?>
        </div>
        <rn:block id="formBottom"/>
    </form>
    <rn:block id="bottom"/>
</div>