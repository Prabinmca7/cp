<div id="rn_<?= $this->instanceID ?>" class="<?= $this->classList ?>">
    <rn:block id="top"/>
	<? if($this->data['attrs']['show_loading_indicator']):?>
    <div id="rn_PageLoadingIndicator" class="rn_OkcsLoading"></div>
	<?else:?>
	<div id="rn_PageLoadingIndicator"></div>
	<?endif;?>
    <rn:block id="bottom"/>
</div>
