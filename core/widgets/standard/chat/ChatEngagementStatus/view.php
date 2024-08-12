<div id="rn_<?=$this->instanceID;?>" class="<?= $this->classList ?> rn_Hidden" aria-live="assertive">
    <rn:block id="top"/>
    <rn:block id="preStatusDisplay"/>
    <h2>
        <span id="rn_<?=$this->instanceID;?>_Prefix" class="rn_StatusPrefix">
            <?=$this->data['attrs']['label_status_prefix'];?>
        </span>
        <span id="rn_<?=$this->instanceID;?>_Status" class="rn_Status">
            <?=$this->data['attrs']['label_status_searching'];?>
        </span>
    </h2>
    <rn:block id="postStatusDisplay"/>
    <rn:block id="preDetailDisplay"/>
    <div id="rn_<?=$this->instanceID;?>_Searching" class="rn_SearchingDetailMessage rn_Hidden">
        <img alt="" src="<?=$this->data['attrs']['loading_icon_path'];?>"/>&nbsp;<span><?=$this->data['attrs']['label_detail_searching'];?></span>
    </div>
    <div id="rn_<?=$this->instanceID;?>_Requeued" class="rn_RequeuedDetailMessage rn_Hidden">
        <?=$this->data['attrs']['label_detail_requeued'];?>
    </div>
    <div id="rn_<?=$this->instanceID;?>_Canceled_User" role="alert" class="rn_CanceledUserDetailMessage rn_Hidden">
        <?=$this->data['attrs']['label_detail_canceled_user'];?>&nbsp;
        <? if (!isset($this->data['attrs']['is_persistent_chat']) || $this->data['attrs']['is_persistent_chat'] !== true): ?>
        <span><?=$this->data['attrs']['label_close_window_message'];?></span>
        <? endif; ?>
    </div>
    <div id="rn_<?=$this->instanceID;?>_Canceled_Self_Service" class="rn_CanceledSelfServiceDetailMessage rn_Hidden">
        <?=$this->data['attrs']['label_detail_canceled_self_service'];?>
    </div>
    <div id="rn_<?=$this->instanceID;?>_Canceled_NoAgentsAvail" class="rn_CanceledNoAgentsAvailDetailMessage rn_Hidden">
        <?=$this->data['attrs']['label_detail_canceled_no_agents_avail'];?>
    </div>
    <div id="rn_<?=$this->instanceID;?>_Canceled_Queue_Timeout" class="rn_CanceledQueueTimeoutDetailMessage rn_Hidden">
        <?=$this->data['attrs']['label_detail_canceled_queue_timeout'];?>
    </div>
    <div id="rn_<?=$this->instanceID;?>_Canceled_Dequeued" class="rn_CanceledDequeuedDetailMessage rn_Hidden">
        <?=$this->data['attrs']['label_detail_canceled_dequeued'];?>
    </div>
    <div id="rn_<?=$this->instanceID;?>_Canceled_Browser" class="rn_CanceledBrowserDetailMessage rn_Hidden">
        <?=$this->data['attrs']['label_detail_canceled_browser'];?>
    </div>
    <rn:block id="postDetailDisplay"/>
    <rn:block id="bottom"/>
</div>
