<? if ($this->data['readOnly']): ?>
    <rn:block id="preReadOnlyField"/>
    <rn:widget path="output/FieldDisplay" label="#rn:php:$this->data['attrs']['label_input']#" left_justify="true" sub_id="readOnlyField"/>
    <rn:block id="postReadOnlyField"/>
<? elseif($this->data['displayType'] !== 'Select' || !$this->data['attrs']['hide_when_no_options'] || !empty($this->data['menuItems'])): ?>
    <div class="<?= $this->classList ?>">
        <rn:block id="top"/>
    <? if ($this->data['attrs']['label_input'] && $this->data['displayType'] !== 'Radio'): ?>
        <rn:block id="preLabel"/>
        <label for="rn_<?= $this->instanceID ?>_<?= $this->data['js']['name'] ?>" class="rn_Label"><?= $this->data['attrs']['label_input'] ?>
        <? if ($this->data['attrs']['required']): ?>
            <rn:block id="preRequired"/>
                <?= $this->render('Partials.Forms.RequiredLabel') ?>
            <rn:block id="postRequired"/>
        <? endif; ?>
        <? if ($this->data['attrs']['hint']): ?>
            <rn:block id="preHint"/>
            <?= $this->data['attrs']['hint_separator'] . ' ' . $this->data['attrs']['hint'] ?>
            <rn:block id="postHint"/>
        <? endif; ?>
        </label><br/>
        <rn:block id="postLabel"/>
    <? endif; ?>
<? if ($this->data['displayType'] === 'Select'): ?>
    <rn:block id="preInput"/>
    <select id="rn_<?= $this->instanceID ?>_<?= $this->data['js']['name'] ?>" class="rn_BasicSelection" name="formData[<?= $this->data['inputName'] ?>]">
        <rn:block id="inputTop"/>
    <? if (!$this->data['hideEmptyOption']): ?>
        <option value="">--</option>
    <? endif; ?>
    <? if (is_array($this->data['menuItems'])): ?>
        <? foreach ($this->data['menuItems'] as $key => $item): ?>
            <option value="<?= $key ?>" <?= $this->outputSelected($key) ?>><?= $item ?></option>
        <? endforeach; ?>
    <? endif; ?>
        <rn:block id="inputBottom"/>
    </select>
    <rn:block id="postInput"/>
<? else: ?>
    <? if ($this->data['displayType'] === 'Checkbox'): ?>
        <rn:block id="preInput"/>
        <input type="checkbox" id="rn_<?= $this->instanceID ?>_<?= $this->data['js']['name'] ?>" name="formData[<?= $this->data['inputName'] ?>]" <?= $this->outputChecked(1) ?> value="1"/>
        <rn:block id="postInput"/>
    <? else: ?>
        <fieldset>
        <? if ($this->data['attrs']['label_input']): ?>
            <rn:block id="preLabel"/>
            <legend id="rn_<?= $this->instanceID ?>_Label" class="rn_Label">
            <?= $this->data['attrs']['label_input'] ?>
            <? if ($this->data['attrs']['required']): ?>
                <rn:block id="preRequired"/>
                    <?= $this->render('Partials.Forms.RequiredLabel') ?>
                <rn:block id="postRequired"/>
            <? endif; ?>
            <? if ($this->data['attrs']['hint']): ?>
                <rn:block id="preHint"/>
                <?= $this->data['attrs']['hint_separator'] . $this->data['attrs']['hint'] ?><br/>
                <rn:block id="postHint"/>
            <? endif; ?>
            </legend>
            <rn:block id="postLabel"/>
        <? endif; ?>
        <rn:block id="preInput"/>
        <? for($i = 1; $i >= 0; $i--):
                $id = "rn_{$this->instanceID}_{$this->data['js']['name']}_$i"; ?>
            <rn:block id="preRadioInput"/>
            <input type="radio" name="formData[<?= $this->data['inputName']?>]" id="<?= $id ?>" <?= $this->outputChecked($i) ?> value="<?= $i ?>"/>
            <rn:block id="postRadioInput"/>
            <rn:block id="preRadioLabel"/>
            <label for="<?= $id ?>">
            <?= $this->data['radioLabel'][$i] ?>
            </label>
            <rn:block id="postRadioLabel"/>
        <? endfor; ?>
        <rn:block id="postInput"/>
        </fieldset>
    <?endif; ?>
<? endif; ?>
<rn:block id="bottom"/>
</div>
<? endif; ?>
