<div id="rn_<?=$this->instanceID;?>" class="<?= $this->classList ?>">
    <rn:block id="top"/>
    <label for='rn_<?=$this->instanceID;?>_Options'><?=$this->data['attrs']['label_sort']?></label>
    <rn:block id="preSelect"/>
    <select id='rn_<?=$this->instanceID;?>_Options'>
        <? foreach($this->data['sortOptions'] as $key => $value):
             echo "<";
             ?>option value="<?=$key;?>"<?=($js['sortDefault'] == $key) ? ' selected="selected"' : '';?>><?=$value?></option>
        <? endforeach;?>
    </select>
    <rn:block id="postSelect"/>
    <rn:block id="bottom"/>
</div>
