<div id="rn_<?=$this->instanceID;?>" class="<?= $this->classList ?>">
    <rn:block id="top"/>
    <? if($this->data['attrs']['show_profile_picture']):?>
    <rn:block id="avatar">
    <img src="<?=$this->data['user']->avatar;?>" alt=""/>
    </rn:block>
    <? endif;?>
    <rn:block id="userName">
    <span class="rn_UserName"><?=$this->data['user']->name;?></span>
    </rn:block>
    <rn:block id="bottom"/>
</div>