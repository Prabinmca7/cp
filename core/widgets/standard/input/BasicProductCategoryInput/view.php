<div class="<?=$this->classList?>">
    <rn:block id="top"/>
    <? if($this->data['attrs']['label_input']):?>
        <?if($this->data['attrs']['read_only']):?>
            <span class="rn_Label">
                <rn:block id="preLabel"/>
                <?=$this->data['attrs']['label_input'];?>
                <rn:block id="postLabel"/>
            </span>
        <? else: ?>
            <label class="rn_Label" for="rn_<?=$this->instanceID;?>_<?=$this->data['attrs']['name'];?>">
                <rn:block id="preLabel"/>
                <?=$this->data['attrs']['label_input'];?>
                <rn:block id="postLabel"/>
            </label>
        <? endif; ?>
        <? if ($this->data['attrs']['required']): ?>
            <rn:block id="preRequired"/>
                <?= $this->render('Partials.Forms.RequiredLabel') ?>
            <rn:block id="postRequired"/>
        <? endif; ?>
        <? if ($this->data['attrs']['hint']): ?>
            <?= $this->data['attrs']['hint_separator'] . ' ' . $this->data['attrs']['hint'] ?>
        <? endif; ?>
    <? endif; ?>
    <? if($this->data['ancestors']): ?>
        <rn:block id="preList"/>
        <ul>
        <? foreach($this->data['ancestors'] as $data): ?>
            <rn:block id="topListItem"/>
            <li>
            <? if($this->data['selected'] === $data['id']):?>
                <strong><?=$data['label'];?></strong>
            <? else: ?>
                <?=$data['label'];?>
            <? endif; ?>
            </li>
            <rn:block id="bottomListItem"/>
        <? endforeach; ?>
        </ul>
        <rn:block id="postList"/>
    <? endif; ?>
    <?if($this->data['attrs']['read_only']):?>
        <input type="hidden" name="formData[<?=$this->data['attrs']['name'];?>]>" value="<?=$this->data['selected'];?>"/>
        <? foreach($this->data['hierData'] as $data): ?>
            <?if($data['selected']):?>
                <span><?=$data['label'];?></span>
                <?break;
            endif;?>
        <? endforeach;?>
    <?else:?>
    <rn:block id="preInput"/>
    <div>
        <select id="rn_<?=$this->instanceID;?>_<?=$this->data['attrs']['name'];?>" name="formData[<?=$this->data['attrs']['name'];?>]">
        <? foreach($this->data['hierData'] as $data): ?>
            <rn:block id="topInput"/>
            <option value="<?=$data['id'];?>" <?=$data['selected'] ? ' selected="selected"' : '';?>><?=$data['child'] ? " - {$data['label']}" : $data['label'];?></option>
            <rn:block id="bottomInput"/>
        <? endforeach;?>
        </select>
    </div>
    <rn:block id="postInput"/>
    <?endif;?>
    <rn:block id="bottom"/>
</div>
