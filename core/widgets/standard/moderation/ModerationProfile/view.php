<div id="rn_<?= $this->instanceID ?>" class="<?= $this->classList ?>">
    <rn:block id="top"/>
    <fieldset>
        <legend><?=$this->data['attrs']['label_userinfo_title']?></legend>
        <span class="rn_FieldLabel"><?=$this->data['attrs']['label_user_email']?></span>
        <span class="rn_FieldValue"><?=$this->data['userData']['email']?></span>

        <span class="rn_FieldLabel"><?=$this->data['attrs']['label_user_first_name']?></span>
        <span class="rn_FieldValue"><?=$this->data['userData']['firstName']?></span>

        <span class="rn_FieldLabel"><?=$this->data['attrs']['label_user_last_name']?></span>
        <span class="rn_FieldValue"><?=$this->data['userData']['lastName']?></span>
    </fieldset>
    <rn:block id="bottom"/>
</div>