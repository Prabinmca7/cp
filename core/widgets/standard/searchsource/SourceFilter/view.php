<div id="rn_<?= $this->instanceID ?>" class="<?= $this->classList ?>">
    <rn:block id="top"/>
    <rn:block id="preLabel"/>
    <label for="rn_<?= $this->instanceID ?>_Dropdown">
        <?= $this->data['attrs']['label_input'] ?>
    </label>
    <rn:block id="postLabel"/>
    <rn:block id="preSelect"/>
    <select id="rn_<?= $this->instanceID ?>_Dropdown">
    <? if ($this->data['attrs']['label_default']): ?>
        <option <?= !is_null($this->data['js']['filter']['value']) ? '' : 'selected' ?> value="-1">
            <?= $this->data['attrs']['label_default'] ?>
        </option>
    <? endif; ?>
    <? foreach ($this->data['options'] as $index => $option): ?>
        <option value="<?= $option->ID ?>" <?= ($this->helper->isSelected($option, $this->data['js']['filter']['value'])) ? 'selected' : '' ?>>
            <?= $this->helper->labelForOption($index, $this->data['attrs']['labels'], $option) ?>
        </option>
    <? endforeach; ?>
    </select>
    <rn:block id="postSelect"/>
    <rn:block id="bottom"/>
</div>
