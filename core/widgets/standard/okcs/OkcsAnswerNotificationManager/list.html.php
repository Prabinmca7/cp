<? if(isset($data['subscriptionList']) && is_array($data['subscriptionList']) && count($data['subscriptionList'])):?>
    <rn:block id="preList"/>
    <div id="rn_<?=$this->instanceID;?>_List" class="rn_NotificationList">
    <? for($i = 0; $i < count($data['subscriptionList']); $i++):?>
        <rn:block id="preItem"/>
        <div class="rn_Notification" id="rn_<?=$this->instanceID;?>_<?= $data['subscriptionList'][$i]['subscriptionID'] ?>">
            <rn:block id="preInfo"/>
            <div class="rn_Notification_Info">
                <rn:block id="summary">
                <a href="<?= $this->data['answerUrl'] ?><?= $this->data['subscriptionList'][$i]['answerId']?>" target="<?= $this->data['attrs']['target'] ?>"><?=$data['subscriptionList'][$i]['title']?></a>
                </rn:block>
                <rn:block id="documentId">
                <span><?= \RightNow\Utils\Config::getMessage(DOC_ID_LBL) ?> - <?= $data['subscriptionList'][$i]['documentId'] ?></span>
                </rn:block>
                <rn:block id="startDate">
                <span><?=sprintf(\RightNow\Utils\Config::getMessage(SUBSCRIBED_ON_PCT_S_LBL), $data['subscriptionList'][$i]['expires']);?></span>
                </rn:block>
            </div>
            <rn:block id="postInfo"/>
            <rn:block id="preActions"/>
            <div class="rn_Notification_Actions">
                <rn:block id="preDelete"/>
                <button id="<?= $data['subscriptionList'][$i]['subscriptionID'] ?>" class="rn_Notification_Delete"><?=$data['attrs']['label_delete_button'];?></button>
                <rn:block id="postDelete"/>
            </div>
            <rn:block id="postActions"/>
        </div>
        <rn:block id="postItem"/>
    <? endfor;?>
    </div>
    <rn:block id="postList"/>
<? else:?>
    <rn:block id="preNoNotifications"/>
    <div class="rn_NoNotification"><?= $this->data['attrs']['label_no_notifs'] ?></div>
    <rn:block id="postNoNotifications"/>
<? endif;?>
