<div class="<?= $this->classList ?>">
    <rn:block id="top"/>
        <input type="submit" id="rn_<?= $this->instanceID ?>_Button" value="<?= $this->data['attrs']['label_button'] ?>"/>
    <rn:block id="bottom"/>
    <input type="hidden" name="f_tok" value="<?=$this->data['f_tok']?>"/>
    <? foreach($this->data['format'] as $key => $value): ?>
        <? if($value): ?>
            <input type="hidden" name="format[<?=$key?>]" value="<?=$value?>"/>
        <? endif; ?>
    <? endforeach; ?>
</div>