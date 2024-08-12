<? if ($this->data['readOnly']): ?>
    <rn:block id="preReadOnlyField"/>
    <rn:widget path="output/FieldDisplay" label="#rn:php:$this->data['attrs']['label_input']#" left_justify="true" sub_id="readOnlyField"/>
    <rn:block id="postReadOnlyField"/>
<? else: ?>
<div id="rn_<?= $this->instanceID ?>" class="<?= $this->classList ?>">
    <rn:block id="top"/>
<? if ($this->data['displayType'] !== 'Radio'): ?>
    <div id="rn_<?= $this->instanceID ?>_LabelContainer">
        <rn:block id="preLabel"/>
        <label for="rn_<?= $this->instanceID ?>_<?= $this->data['js']['name'] ?>" id="rn_<?= $this->instanceID ?>_Label" class="rn_Label"><?= $this->data['attrs']['label_input'] ?>
        <? if ($this->data['attrs']['label_input'] && $this->data['attrs']['required']): ?>
            <rn:block id="preRequired"/>
                <?= $this->render('Partials.Forms.RequiredLabel') ?>
            <rn:block id="postRequired"/>
        <? endif; ?>
        <? if ($this->data['attrs']['hint']): ?>
            <span class="rn_ScreenReaderOnly"><?= $this->data['attrs']['hint'] ?></span>
        <? endif; ?>
        </label>
        <rn:block id="postLabel"/>
    </div>
<? endif; ?>
<? if ($this->data['displayType'] === 'Select'): ?>
    <rn:block id="preInput"/>
    <select id="rn_<?= $this->instanceID ?>_<?= $this->data['js']['name'] ?>" name="<?= $this->data['inputName'] ?>">
        <rn:block id="inputTop"/>
    <? if (!$this->data['hideEmptyOption']): ?>
        <option value="">--</option>
    <? endif; ?>
    <? if (is_array($this->data['menuItems'])): ?>
        <? foreach ($this->data['menuItems'] as $key => $item): ?>
            <option value="<?= $key ?>" <?= $this->outputSelected($key) ?>><?=\RightNow\Utils\Text::escapeHtml($item, false);?></option>
        <? endforeach; ?>
    <? endif; ?>
        <rn:block id="inputBottom"/>
    </select>
    <rn:block id="postInput"/>
    <? if ($this->data['attrs']['hint'] && $this->data['attrs']['always_show_hint']): ?>
        <rn:block id="preHint"/>
        <span id="rn_<?= $this->instanceID ?>_Hint" class="rn_HintText"><?= $this->data['attrs']['hint'] ?></span>
        <rn:block id="postHint"/>
    <? endif; ?>
<? else: ?>
    <? if ($this->data['displayType'] === 'Checkbox'): ?>
        <rn:block id="preInput"/>
        <input type="checkbox" id="rn_<?= $this->instanceID ?>_<?= $this->data['js']['name'] ?>" name="<?= $this->data['inputName'] ?>" <?= $this->outputChecked(1) ?> value="1"/>
        <rn:block id="postInput"/>
        <? if ($this->data['attrs']['hint'] && $this->data['attrs']['always_show_hint']): ?>
            <rn:block id="preHint"/>
            <span id="rn_<?= $this->instanceID ?>_Hint" class="rn_HintText"><?= $this->data['attrs']['hint'] ?></span>
            <rn:block id="postHint"/>
        <? endif; ?>
    <? else: ?>
        <fieldset>
            <legend id="rn_<?= $this->instanceID ?>_Label" class="rn_Label">
                <rn:block id="preLabel"/>
                <?= $this->data['attrs']['label_input'] ?>
                <? if ($this->data['attrs']['label_input'] && $this->data['attrs']['required']): ?>
                    <rn:block id="preRequired"/>
                        <?= $this->render('Partials.Forms.RequiredLabel') ?>
                    <rn:block id="postRequired"/>
                <? endif; ?>
                <rn:block id="postLabel"/>
            </legend>
        <rn:block id="preInput"/>
        <? for($i = 1; $i >= 0; $i--):
                $id = "rn_{$this->instanceID}_{$this->data['js']['name']}_$i"; ?>
            <rn:block id="preRadioInput"/>
            <input type="radio" name="<?= $this->data['inputName']?>" id="<?= $id ?>" <?= $this->outputChecked($i) ?> value="<?= $i ?>"/>
            <rn:block id="postRadioInput"/>
            <rn:block id="preRadioLabel"/>
            <label for="<?= $id ?>">
            <?= $this->data['radioLabel'][$i] ?>
            <? if ($this->data['attrs']['hint'] && $i === 1): ?>
                <span class="rn_ScreenReaderOnly"><?= $this->data['attrs']['hint'] ?></span>
            <? endif; ?>
            </label>
            <rn:block id="postRadioLabel"/>
        <? endfor; ?>
        <rn:block id="postInput"/>
        <? if ($this->data['attrs']['hint'] && $this->data['attrs']['always_show_hint']): ?>
            <rn:block id="preHint"/>
            <span id="rn_<?= $this->instanceID ?>_Hint"  class="rn_HintText"><?= $this->data['attrs']['hint'] ?></span>
            <rn:block id="postHint"/>
        <? endif; ?>
        </fieldset>
    <?endif; ?>
<? endif; ?>
<rn:block id="bottom"/>
</div>
<? endif; ?>
