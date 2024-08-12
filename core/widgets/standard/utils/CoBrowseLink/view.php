<div id="rn_<?=$this->instanceID;?>" class="<?= $this->classList ?>">
    <rn:block id="top"/>
    <a id="rn_<?=$this->instanceID;?>_Link" title="<?=$this->data['attrs']['label_tooltip'];?>"
        href="<?=$this->data['screen_sharing_url']?>"
        <?if($this->data['attrs']['popup_window']):?>
            onclick="window.open('<?=$this->data['screen_sharing_url']?>', '', 'toolbar=0,scrollbars=0,location=0,statusbar=0,menubar=0,resizable=0,titlebar=0,status=0,width=<?=$this->data['attrs']['popup_window_width']?>,height=<?=$this->data['attrs']['popup_window_height']?>'); return false;">
        <?else:?>
            target="_blank">
        <?endif;?>
        <?if($this->data['attrs']['icon_path']):?>
        <img id="rn_<?=$this->instanceID;?>_Image" src="<?=$this->data['attrs']['icon_path'];?>" alt="" />
        <?endif;?>
        <span><?=$this->data['attrs']['label_cobrowse'];?></span>
    </a>
    <rn:block id="bottom"/>
</div>
