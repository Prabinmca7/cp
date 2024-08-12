<div id="rn_<?= $this->instanceID ?>" class="<?= $this->classList ?>">
    <rn:block id="top"/>
    <div class="rn_UsersView">
        <? if($this->data['attrs']['content_load_mode'] === 'synchronous') : ?>
            <?= $this->render($this->data['attrs']['content_display_type']); ?>
        <? endif;?>
    </div>
    <? if($this->data['attrs']['content_load_mode'] !== 'synchronous') : ?>
        <div class="rn_LoadingIcon" role="aria-alert">
            <img id="rn_LoadingIconGif" alt="<?=$this->data['attrs']['label_content_load']?>" src="<?= $this->data['attrs']['loading_icon_path'] ?>" />
        </div>
        <div class="rn_ErrorMsg rn_Hidden"> 
        </div>
    <? endif;?>
    <rn:block id="bottom"/>
</div>