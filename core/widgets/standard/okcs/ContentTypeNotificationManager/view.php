<div id="rn_<?=$this->instanceID;?>" class="<?= $this->classList ?>">
    <rn:block id="top"/>
    <div class="rn_ButtonGroup">
        <rn:block id="preAddButton"/>
        <button id="rn_<?=$this->instanceID;?>_AddButton" class="rn_AddButton"><?=$this->data['attrs']['label_add_notifications_button'];?></button>
        <rn:block id="postAddButton"/>
    </div>
    <form id="rn_<?= $this->instanceID ?>_Form">
        <div id="rn_<?=$this->instanceID;?>_Dialog" class="rn_ContentTypeNotificationManager_Dialog rn_Hidden">
            <div id="rn_<?=$this->instanceID;?>_ErrorLocation" aria-live="assertive"></div>
            <div class="rn_SelectionWidget">
                <span class="rn_Label" id="rn_<?= $this->instanceID ?>_Label">
                <rn:block id="preLabel"/>
                <?=$this->data['attrs']['label_notif_name']?>
                <span id="rn_<?=$this->instanceID;?>_RequiredLabel" class="rn_Required"> <?=\RightNow\Utils\Config::getMessage(FIELD_REQUIRED_MARK_LBL);?></span>
                <span id="rn_<?=$this->instanceID;?>_ErrorLabel" class="rn_ErrorLabel">
                <span class="rn_ScreenReaderOnly"> <?=\RightNow\Utils\Config::getMessage(REQUIRED_LBL);?> </span>
                </span>
                <rn:block id="postLabel"/>
                </span>
            <input type="text" id="rn_<?=$this->instanceID;?>_Name" name="name" class="rn_Name" maxlength="100"/>
            </div>
            <div class="rn_SelectionWidget">
                <rn:widget path="okcs/OkcsProductCategoryInput" filter_type="ContentType" is_required="true" />
            </div>
            <div class="rn_SelectionWidget">
                <rn:widget path="okcs/OkcsProductCategoryInput" />
            </div>
            <div class="rn_SelectionWidget">
                <rn:widget path="okcs/OkcsProductCategoryInput" filter_type="Category" />
            </div>
        </div>
    </form>
    <div id="rn_<?=$this->instanceID;?>_List" class="rn_NotificationList">
        <? if(isset($this->data['subscriptionList']) && is_array($this->data['subscriptionList']) && count($this->data['subscriptionList'])):?>
        <rn:block id="preLoop"/>
        <? foreach($this->data['subscriptionList'] as $index => $notification):?>
        <div id="rn_<?=$this->instanceID;?>_<?= $notification['subscriptionID'] ?>" class="rn_Notification">
            <rn:block id="preInfo"/>
            <div class="rn_Notification_Info">
                <rn:block id="subscriptionName">
                    <span><?= $notification['name'] ?></span>
                </rn:block>
                <rn:block id="subscriptionDetail">
                    <? $subscriptionDetail = $this->data['attrs']['label_content_type'] . ' - ' . $notification['contentTypeName']; ?>
                    <? if($notification['product']): ?>
                        <? if(!$this->data['attrs']['disable_prod_categ_hyperlink']): ?>
                            <? $subscriptionDetail .= ', ' . $this->data['attrs']['label_product'] . ' -  <a href="' . $notification['phref'] . '" target="' . $this->data['attrs']['target'] . '">' . $notification['product'] . '</a>' ?>
                        <? else : ?>
                            <? $subscriptionDetail .= ', ' . $this->data['attrs']['label_product'] . ' - ' . $notification['product']; ?>
                        <? endif; ?>
                    <? endif; ?>
                    <? if($notification['category']): ?>
                        <? if(!$this->data['attrs']['disable_prod_categ_hyperlink']): ?>
                            <? $subscriptionDetail .= ', ' . $this->data['attrs']['label_category'] . ' -  <a href="' . $notification['chref'] . '" target="' . $this->data['attrs']['target'] . '">' . $notification['category'] . '</a>' ?>
                        <? else : ?>
                            <? $subscriptionDetail .= ', ' . $this->data['attrs']['label_category'] . ' - ' . $notification['category']; ?>
                        <? endif; ?>
                    <? endif; ?>
                    <span><?= $subscriptionDetail ?></span>
                </rn:block>
                <rn:block id="startDate">
                <span><?=sprintf(\RightNow\Utils\Config::getMessage(SUBSCRIBED_ON_PCT_S_LBL), $notification['startDate']);?></span>
                </rn:block>
            </div>
            <rn:block id="postInfo"/>
            <rn:block id="preActions"/>
            <div class="rn_Notification_Actions">
                <rn:block id="preDelete"/>
                <button id="<?= $notification['subscriptionID'] ?>" class="rn_Notification_Delete"><?=$this->data['attrs']['label_delete_button'];?></button>
                <rn:block id="postDelete"/>
            </div>
            <rn:block id="postActions"/>
        </div>
        <rn:block id="postItem"/>
        <? endforeach;?>
        <rn:block id="postLoop"/>
<? else:?>
    <rn:block id="preNoNotifications"/>
    <div class="rn_NoNotification"><?= $this->data['attrs']['label_no_notifs'] ?></div>
    <rn:block id="postNoNotifications"/>
<? endif;?>
</div>
    <rn:block id="bottom"/>
</div>