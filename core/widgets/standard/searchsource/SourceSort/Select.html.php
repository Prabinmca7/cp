<rn:block id="preSelect"/>
    <rn:block id="preSelectLabel"/>
    <label for="rn_<?= $this->instanceID ?>_<?= ucfirst($type) ?>">
        <?= $this->data['attrs']['label_' . $type . '_input'] ?>
    </label>
    <rn:block id="postSelectLabel"/>

    <rn:block id="preSelectDropdown"/>
    <select id="rn_<?= $this->instanceID ?>_<?= ucfirst($type) ?>">
        <?= $this->helper->outputOption('-1', $this->data['attrs']['label_' . $type . '_default'], is_null($this->data['js']['filter_' . $type]['value'])) ?>
    <? foreach ($this->data['attrs'][$type . '_order'] as $index):
        $optionElement = $this->data['options_' . $type][$index];
        $selected = $this->helper->isSelected($optionElement, $this->data['js']['filter_' . $type]['value']);
        $label = $this->helper->labelForOption($index, $this->data['attrs'][$type . '_label_list']); ?>
        <?= $this->helper->outputOption($optionElement->ID, $label, $selected) ?>
    <? endforeach; ?>
    </select>
    <rn:block id="postSelectDropdown"/>
<rn:block id="postSelect"/>
