<div id="rn_<?=$this->instanceID;?>" class="<?= $this->classList ?>">
    <rn:block id="top"/>
    <rn:block id="preButton"/>
    <button id="rn_<?=$this->instanceID;?>_Button" class="rn_Hidden" title="<?=$this->data['attrs']['label_tooltip'];?>">
        <? if($this->data['attrs']['end_cobrowse_icon_path']) :?>
            <rn:block id="preCobrowseIcon"/>
            <img src="<?=$this->data['attrs']['end_cobrowse_icon_path']?>" alt="<?=$this->data['attrs']['label_tooltip'];?>" />
            <rn:block id="postCobrowseIcon"/>
        <? endif; ?> <?=$this->data['attrs']['label_end_cobrowse']?>
    </button>
    <rn:block id="postButton"/>
    <rn:block id="preIFrame"/>
    <iframe id="rn_<?=$this->instanceID;?>_IFrame" class="rn_Hidden" title="<?=$this->data['attrs']['label_cobrowse_session'];?>"></iframe>
    <rn:block id="postIFrame"/>
    <rn:block id="bottom"/>
</div>
