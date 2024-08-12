<div id="rn_<?=$this->instanceID;?>" class="<?= $this->classList ?>">
    <rn:block id="top"/>
<? if(count($this->data['notifications'])):?>
    <rn:block id="preList"/>
    <div id="rn_<?=$this->instanceID;?>_List">
    <? foreach($this->data['notifications'] as $index => $notification):?>
        <rn:block id="preItem"/>
        <div class="rn_Notification" data-id="<?=$notification['id']?>">
            <rn:block id="preInfo"/>
            <div class="rn_Notification_Info">
                <rn:block id="summary">
                <a href="<?=$notification['url']?>" target="_blank"><?=$notification['summary']?></a>
                </rn:block>
                <rn:block id="startDate">
                <span><?=sprintf(\RightNow\Utils\Config::getMessage(SUBSCRIBED_ON_PCT_S_LBL), $notification['startDate']);?></span>
                </rn:block>
                <rn:block id="expiration">
                <span><?= ($notification['expiresTime']) ? $notification['expiresTime'] : \RightNow\Utils\Config::getMessage(NO_EXPIRATION_DATE_LBL); ?></span>
                </rn:block>
            </div>
            <rn:block id="postInfo"/>
            <rn:block id="preActions"/>
            <div class="rn_Notification_Actions">
                <?if($this->data['js']['duration']):?>
                <rn:block id="preRenew"/>
                <button class="rn_Notification_Renew"><?=$this->data['attrs']['label_renew_button'];?></button>
                <rn:block id="postRenew"/>
                <?endif;?>
                <rn:block id="preDelete"/>
                <button class="rn_Notification_Delete"><?=$this->data['attrs']['label_delete_button'];?></button>
                <rn:block id="postDelete"/>
            </div>
            <rn:block id="postActions"/>
        </div>
        <rn:block id="postItem"/>
    <? endforeach;?>
    </div>
    <rn:block id="postList"/>
<? else:?>
    <rn:block id="preNoNotifications"/>
<?=$this->data['attrs']['label_no_notifs'];?>
    <rn:block id="postNoNotifications"/>
<? endif;?>
    <rn:block id="bottom"/>
</div>
