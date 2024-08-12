<div id="rn_<?=$this->instanceID;?>" class="<?= $this->classList ?>">
    <rn:block id="top"/>
    <label for="rn_<?=$this->instanceID;?>_Headings"><?=$this->data['attrs']['label_text']?></label>
    <rn:block id="preHeadingSelect"/>
    <select id="rn_<?=$this->instanceID;?>_Headings" class="rn_Headings">
        <option value="-1"><?=$this->data['attrs']['label_default']?></option>
        <? foreach ($this->data['js']['headers'] as $key => $value): ?>
            <option value="<?=$value['col_id']?>" <?=($value['col_id'] === $this->data['js']['col_id']) ? 'selected="selected"' : '';?>><?=$value['heading']?></option>
        <? endforeach; ?>
    </select>
    <rn:block id="postHeadingSelect"/>
    <label for="rn_<?=$this->instanceID;?>_Direction"><?=\RightNow\Utils\Config::getMessage(DIRECTION_LBL)?></label>
    <rn:block id="preDirectionSelect"/>
    <select id='rn_<?=$this->instanceID;?>_Direction' class="rn_Direction">
        <option value="1" <?=($this->data['js']['sort_direction'] === 1) ? 'selected="selected"' : '';?>><?=$this->data['attrs']['label_ascending']?></option>
        <option value="2" <?=($this->data['js']['sort_direction'] === 2) ? 'selected="selected"' : '';?>><?=$this->data['attrs']['label_descending']?></option>
    </select>
    <rn:block id="postDirectionSelect"/>
    <rn:block id="bottom"/>
</div>
