<div id="rn_<?=$this->instanceID;?>" class="<?= $this->classList ?> rn_Hidden">
    <rn:block id="top"/>
    <div id="rn_<?=$this->instanceID;?>_IntroParagraph">
        <rn:block id="preIntroParagraph"/>
        <?=$this->data['attrs']['intro_paragraph'];?>
        <rn:block id="postIntroParagraph"/>
    </div>
    <rn:block id="bottom"/>
</div>
