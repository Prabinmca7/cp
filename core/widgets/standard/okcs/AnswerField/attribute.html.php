<div class='rn_SchemaAttribute'>
    <? if ($type === 'CHECKBOX') : ?>
        <? $checkboxID = $className . $index; 
        $checked = $value === 'Y' ? 'checked' : '';
        ?>
        <label for="<?= $checkboxID ?>" class="rn_AttributeCheckboxLabel"><?= $label ?></label>
        <input id="<?= $checkboxID ?>" type="checkbox" disabled <?= $checked ?> class="rn_AttributeCheckbox"/>
    <? else: ?>
    <?= $label ?>
    <? endif; ?>
</div>
