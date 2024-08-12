<div id="rn_<?=$this->instanceID;?>" class="<?= $this->classList ?>">
    <rn:block id="top"/>
    <div id="rn_<?=$this->instanceID;?>_List">
    <? if(isset($this->data['notifications']) ? count($this->data['notifications']) : 0):?>
        <rn:block id="preLoop"/>
        <? foreach($this->data['notifications'] as $index => $notification):?>
        <div id="rn_<?=$this->instanceID;?>_Notification_<?=$index;?>" class="rn_Notification">
            <div class="rn_Notification_Info">
                <rn:block id="link">
                <a href="<?=$notification['url'];?>" target="_blank"><?=htmlspecialchars($notification['label'], ENT_QUOTES, 'UTF-8');?></a>
                <span><? printf(\RightNow\Utils\Config::getMessage(SUBSCRIBED_ON_PCT_S_LBL), $notification['startDate']);?></span>
                <span id="rn_<?=$this->instanceID;?>_Expiration_<?=$index;?>">
                    <?= $notification['expiresTime'] ? $notification['expiresTime'] : \RightNow\Utils\Config::getMessage(NO_EXPIRATION_DATE_LBL) ?>
                </span>
                </rn:block>
            </div>
            <div class="rn_Notification_Actions">
                <? if($this->data['js']['duration']):?>
                    <rn:block id="preRenewButton"/>
                    <button id="rn_<?=$this->instanceID;?>_Renew_<?=$index;?>"><?=$this->data['attrs']['label_renew_button'];?></button>
                    <rn:block id="postRenewButton"/>
                <? endif;?>
                <rn:block id="preDeleteButton"/>
                <button id="rn_<?=$this->instanceID;?>_Delete_<?=$index;?>"><?=$this->data['attrs']['label_delete_button'];?></button>
                <rn:block id="postDeleteButton"/>
            </div>
        </div>
        <? endforeach;?>
        <rn:block id="postLoop"/>
    <? else:?>
    <?=$this->data['attrs']['label_no_notifs'];?>
    <? endif;?>
    </div>
    <div class="rn_ButtonGroup">
        <rn:block id="preAddButton"/>
        <button id="rn_<?=$this->instanceID;?>_AddButton" class="rn_AddButton"><?=$this->data['attrs']['label_add_button'];?></button>
        <rn:block id="postAddButton"/>
    </div>
    <form id="rn_<?= $this->instanceID ?>_Form">
        <div id="rn_<?=$this->instanceID;?>_Dialog" class="rn_ProdCatNotificationManager_Dialog rn_Hidden">
            <div class="rn_SelectionWidget">
                <rn:widget path="input/ProductCategoryInput" name="Incident.Product" label_set_button="#rn:msg:ADD_PRODUCT_CMD#" set_button="true" linking_off="true" sub_id="prod"/>
            </div>
            <div class="rn_SelectionWidget">
                <rn:widget path="input/ProductCategoryInput" name="Incident.Category" data_type="Category" label_input="#rn:msg:CATEGORY_LBL#" label_nothing_selected="#rn:msg:SELECT_A_CATEGORY_LBL#" label_set_button="#rn:msg:ADD_CATEGORY_CMD#" set_button="true" linking_off="true" sub_id="cat"/>
            </div>
        </div>
    </form>
    <rn:block id="bottom"/>
</div>
