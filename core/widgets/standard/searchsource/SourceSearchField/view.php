<div id="rn_<?= $this->instanceID ?>" class="<?= $this->classList ?>">
    <rn:block id="top"/>
    <label for="rn_<?= $this->instanceID ?>_SearchInput">
    <?= $this->data['attrs']['label_input'] ?>
    </label>

    <input type="search" id="rn_<?= $this->instanceID ?>_SearchInput" name="<?= $this->data['js']['filter']['key'] ?>" placeholder="<?= $this->data['attrs']['label_placeholder'] ?>" value="<?= $this->data['js']['prefill'] ?>" maxlength="255">
    <rn:block id="bottom"/>
</div>