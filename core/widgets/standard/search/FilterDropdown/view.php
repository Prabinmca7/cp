<div id="rn_<?=$this->instanceID;?>" class="<?= $this->classList ?>">
    <rn:block id="top"/>
    <label for="rn_<?=$this->instanceID;?>_Options"><?=$this->data['js']['name'];?></label>
    <rn:block id="preSelect"/>
    <select id="rn_<?=$this->instanceID;?>_Options">
        <option value='<?=ANY_FILTER_VALUE;?>'><?=$this->data['attrs']['label_any'];?></option>
        <? foreach($this->data['js']['list'] as $key => $value):
            $selected = '';
            if($value['id'] === intval($this->data['js']['defaultValue'])) $selected = 'selected';?>
            <option value="<?=$value['id']?>" <?=$selected?>><?=htmlspecialchars($value['label'], ENT_QUOTES, 'UTF-8');?></option>
        <? endforeach;?>
    </select>
    <rn:block id="postSelect"/>
    <rn:block id="bottom"/>
</div>
