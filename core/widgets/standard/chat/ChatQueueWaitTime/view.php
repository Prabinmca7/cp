<div id="rn_<?=$this->instanceID;?>" class="<?= $this->classList ?> rn_Hidden">
    <rn:block id="top"/>
    <rn:block id="preBrowserWarning"/>
    <div id="rn_<?=$this->instanceID;?>_BrowserWarning" class="rn_BrowserWarning rn_Hidden">
        <?=$this->data['attrs']['label_leave_screen_warning'];?>
        <br/><br/>
    </div>
    <rn:block id="postBrowserWarning"/>
    <rn:block id="preQueuePosition"/>
    <span id="rn_<?=$this->instanceID;?>_QueuePosition" class="rn_ChatQueuePosition rn_Hidden">
        <?=$this->data['attrs']['label_queue_position_not_available'];?>
    </span>
    <rn:block id="postQueuePosition"/>
    <rn:block id="preEstimatedWaitTime"/>
    <span id="rn_<?=$this->instanceID;?>_EstimatedWaitTime" class="rn_ChatEstimatedWaitTime rn_Hidden">
        <?=$this->data['attrs']['label_estimated_wait_time_not_available'];?>
    </span>
    <rn:block id="postEstimatedWaitTime"/>
    <rn:block id="preAverageWaitTime"/>
    <span id="rn_<?=$this->instanceID;?>_AverageWaitTime" class="rn_ChatAverageWaitTime rn_Hidden">
        <?=$this->data['attrs']['label_average_wait_time_not_available'];?>
    </span>
    <rn:block id="postAverageWaitTime"/>
    <rn:block id="bottom"/>
</div>
