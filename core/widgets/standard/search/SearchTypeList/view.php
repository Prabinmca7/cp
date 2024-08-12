<div id="rn_<?=$this->instanceID;?>" class="<?= $this->classList ?>">
    <rn:block id="top"/>
    <label for="rn_<?=$this->instanceID;?>_Options" ><?=$this->data['attrs']['label_text'];?></label>
    <rn:block id="preSelect"/>
    <select id="rn_<?=$this->instanceID;?>_Options">
        <?foreach($this->data['js']['filters'] as $key => $value):
             echo "<";
             ?>option value='<?=$value['fltr_id'];?>' <?if($value['fltr_id'] === $this->data['js']['defaultFilter']) echo "selected='selected'";?>><?=$value['prompt'];?></option>
        <?endforeach;?>
    </select>
    <rn:block id="postSelect"/>
    <rn:block id="bottom"/>
</div>
