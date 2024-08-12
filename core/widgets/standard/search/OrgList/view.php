<div id="rn_<?=$this->instanceID;?>" class="<?= $this->classList ?>">
    <rn:block id="top"/>
    <label for="rn_<?=$this->instanceID;?>_Options"><?=$this->data['attrs']['label_title']?></label>
    <rn:block id="preSelect"/>
    <select id="rn_<?=$this->instanceID;?>_Options">
    <?foreach($this->data['js']['options'] as $key => $value):?>
        <?="<";?>option value='<?=$key;?>' <?if($this->data['js']['defaultIndex'] === $key) echo 'selected="selected"';?>><?=$value['label'];?></option>
    <?endforeach;?>
    </select>
    <rn:block id="postSelect"/>
    <rn:block id="bottom"/>
</div>
