<div id="rn_<?=$this->instanceID;?>" class="<?= $this->classList ?>">
    <rn:block id="top"/>
<?if($this->data['attrs']['label_heading'] !== ''):?>
	<rn:block id="heading">
    <h2><?=$this->data['attrs']['label_heading'];?></h2>
	</rn:block>
<?endif;?>
    <?=$this->data['announcement']?>
    <rn:block id="bottom"/>
</div>
