<span id="rn_<?=$this->instanceID;?>" class="<?= $this->classList ?>">
    <rn:block id="top"/>
<? if($this->data['attrs']['subpages']): ?>
    <rn:block id="preLink"/>
    <a id="rn_<?=$this->instanceID;?>_Link" class="<?=isset($this->data['cssClass']) && $this->data['cssClass'] ? $this->data['cssClass'] : '';?> rn_DropDown" href="<?=$this->data['attrs']['link'];?>" target="<?=$this->data['attrs']['target'];?>">
        <span><?=$this->data['attrs']['label_tab'];?></span>
        <em id="rn_<?=$this->instanceID;?>_DropdownButton" class="rn_ButtonOff"></em>
    </a>
    <rn:block id="postLink"/>
    <rn:block id="preSubNavigation"/>
    <span id="rn_<?=$this->instanceID;?>_SubNavigation" class="rn_SubNavigation rn_ScreenReaderOnly">
    <? foreach($this->data['subpages'] as $subpage):?>
        <rn:block id="subNavigationLink">
        <a href="<?=$subpage['href'];?>" target="<?=$this->data['attrs']['target'];?>"><?=$subpage['title'];?></a>
        </rn:block>
    <? endforeach; ?>
    </span>
    <rn:block id="postSubNavigation"/>
<? else:?>
    <rn:block id="link">
    <a class="<?=isset($this->data['cssClass']) && $this->data['cssClass'] ? $this->data['cssClass'] : '';?>" href="<?=$this->data['attrs']['link'];?>" target="<?=$this->data['attrs']['target'];?>" aria-label="<?= isset($this->data['selectedAriaLabel']) && $this->data['selectedAriaLabel'] ? $this->data['selectedAriaLabel'] : '';?>">
        <span><?=$this->data['attrs']['label_tab'];?></span>
    </a>
    </rn:block>
<?endif;?>
    <rn:block id="bottom"/>
</span>
