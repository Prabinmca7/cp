<div id="rn_<?= $this->instanceID ?>" class="<?= $this->classList ?>">
    <rn:block id="top"/>
        <h2 class="rn_OkcsNotificationHeder"><?=$this->data['attrs']['label_notification_frequency'];?></h2>
        <? $value = $this->data['scheduleValue']?>
        <select id="rn_<?=$this->instanceID;?>_selectedScheduleValue" class="rn_OkcsScheduleValueData" name="scheduleValue">
            <option value="0" <?php echo (($value == 0) ? "selected" : "") ?> id="dontSendEmails"><?=$this->data['attrs']['label_dont_send_emails'];?></option>
            <option value="1" <?php echo (($value == 1) ? "selected" : "") ?> id="immediately"><?=$this->data['attrs']['label_immediately'];?></option>
            <option value="2" <?php echo (($value == 2) ? "selected" : "") ?> id="oncePerDay"><?=$this->data['attrs']['label_once_per_day'];?></option>
            <option value="4" <?php echo (($value == 4) ? "selected" : "") ?> id="oncePerWeek"><?=$this->data['attrs']['label_once_per_week'];?></option>
        </select>     
        <button class="rn_subscriptionButton" name="submitButton" id="rn_<?=$this->instanceID;?>_SubmitButton" disabled><?=$this->data['attrs']['label_submit_button'];?></button>
    <rn:block id="bottom"/>
</div>
