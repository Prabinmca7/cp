<? if(\RightNow\Utils\Config::getConfig(ANS_NOTIF_ENABLED)):?>
    <div id="rn_<?=$this->instanceID;?>" class="<?= $this->classList ?>">
    <rn:block id="top"/>
        <rn:block id="preLink"/>
            <? if(isset($this->data['js']['pta'])):?>
                <a href="<?=$this->data['loginUrl'];?>" id="rn_<?=$this->instanceID;?>_Trigger" title="<?=$this->data['attrs']['label_tooltip'];?>">
            <? else:?>
                <a href="javascript:void(0);" id="rn_<?=$this->instanceID;?>_Trigger" title="<?=$this->data['attrs']['label_tooltip'];?>">
            <? endif;?>
            <? if($this->data['attrs']['label_link']):?>
               <span><?=$this->data['attrs']['label_link'];?></span>
            <? endif;?>
            </a>
        <rn:block id="postLink"/>
        <? if(!\RightNow\Utils\Framework::isLoggedIn() && (!isset($this->data['js']['pta']) || !$this->data['js']['pta'])):?>
            <rn:widget path="login/LoginDialog" trigger_element="#rn:php:'rn_' . $this->instanceID . '_Trigger'#" append_to_url="/notif/1" label_window_title="#rn:msg:PLEASE_LOG_GET_NOTIFICATION_MSG#"/>
        <? endif;?>
    <rn:block id="bottom"/>
    </div>
<? endif;?>
