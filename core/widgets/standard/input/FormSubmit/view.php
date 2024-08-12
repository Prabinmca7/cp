<div id="rn_<?= $this->instanceID ?>" class="<?= $this->classList ?>">
    <rn:block id="top"/>

    <rn:block id="preSubmit"/>
    <button type="submit" class="rn_DisplayButton" id="rn_<?= $this->instanceID ?>_Button" disabled>
    <?= $this->data['attrs']['label_button'] ?>
    </button>
    <rn:block id="postSubmit"/>

    <span class="rn_Hidden">
        <input id="rn_<?= $this->instanceID ?>_Submission" type="checkbox" class="rn_Hidden"/>
        <label for="rn_<?= $this->instanceID ?>_Submission" class="rn_Hidden">&nbsp;</label>
    </span>

    <rn:block id="bottom"/>
</div>
