<div id="rn_<?=$this->instanceID;?>" class="<?= $this->classList ?>">
    <rn:block id="top"/>
    <div class="rn_Title">
        <rn:block id="title">
        <?=$this->data['resultMessage'];?>
        </rn:block>
    </div>
    <div class="rn_AnswerSummaryDisplay">
        <rn:block id="answerLink">
        <a href="<?=$this->data['answerUrl'];?>" target='_blank'>#rn:msg:VIEW_ANS_LBL#</a>
        </rn:block>
    </div>
    <div id="rn_<?=$this->instanceID;?>_NotificationList">
        <rn:block id="preNotificationList"/>
        <?= isset($this->data['instructMessage']) ? $this->data['instructMessage'] : '';?>
        <?if(isset($this->data['js']['notifications'])):?>
        <?foreach($this->data['js']['notifications'] as $index => $notification):?>
            <rn:block id="preNotificationItem"/>
            <fieldset id="rn_<?=$this->instanceID;?>_<?=$index;?>">
                <legend><?=$notification['label'];?></legend>
                <rn:block id="preNotificationButton"/>
                <button data-index="<?=$index;?>"><?=(count($this->data['js']['notifications']) === 1 ? $this->data['attrs']['label_resub_button'] : $this->data['attrs']['label_unsub_button']);?></button>
                <rn:block id="postNotificationButton"/>
            </fieldset>
            <rn:block id="postNotificationItem"/>
        <?endforeach;?>
        <?endif;?>
        <rn:block id="postNotificationList"/>
    </div>
    <rn:block id="bottom"/>
</div>
