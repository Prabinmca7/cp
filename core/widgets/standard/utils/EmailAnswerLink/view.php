<div id="rn_<?=$this->instanceID?>" class="<?= $this->classList ?>">
    <rn:block id="top"/>
    <a id="rn_<?=$this->instanceID?>_Link" href="javascript:void(0);" title="<?=$this->data['attrs']['label_tooltip'];?>">
        <span><?=$this->data['attrs']['label_link'];?></span>
    </a>
    <? /* Dialog: Initially hidden on the page. */ ?>
    <div id="rn_<?=$this->instanceID?>_EmailAnswerLinkForm" class="rn_EmailAnswerLinkForm rn_Hidden">
        <div id="rn_<?=$this->instanceID;?>_ErrorMessage"></div>
        <rn:block id="preForm"/>
        <form onsubmit="return false;">
        <rn:block id="preRecipient"/>
        <label id="rn_<?=$this->instanceID?>_LabelRecipientEmail" for="rn_<?=$this->instanceID?>_InputRecipientEmail">
            <?=$this->data['attrs']['label_to'];?>
            <?= $this->render('Partials.Forms.RequiredLabel') ?>
        </label>
        <input id="rn_<?=$this->instanceID?>_InputRecipientEmail" type="email"/>
        <rn:block id="postRecipient"/>
        <? if($this->data['attrs']['object_type'] === 'answer'):?>
            <? if(!$this->data['js']['isProfile']):?>
                <rn:block id="preSenderEmail"/>
                <label id="rn_<?=$this->instanceID?>_LabelSenderEmail" for="rn_<?=$this->instanceID?>_InputSenderEmail">
                    <?=$this->data['attrs']['label_sender_email'];?>
                    <?= $this->render('Partials.Forms.RequiredLabel') ?>
                </label>
                <input id="rn_<?=$this->instanceID?>_InputSenderEmail" type="email" value="<?=$this->data['js']['senderEmail'];?>"/>
                <rn:block id="postSenderEmail"/>
            <? endif;?>
            <? if(!isset($this->data['js']['senderName']) || !$this->data['js']['senderName']):?>
                <rn:block id="preSenderName"/>
                <label id="rn_<?=$this->instanceID?>_LabelSenderName" for="rn_<?=$this->instanceID?>_InputSenderName">
                    <?=$this->data['attrs']['label_sender_name'];?>
                    <?= $this->render('Partials.Forms.RequiredLabel') ?>
                </label>
                <input id="rn_<?=$this->instanceID?>_InputSenderName" maxlength="70" type="text" value="<?= isset($this->data['js']['senderName']) ? $this->data['js']['senderName'] : '';?>"/>
                <rn:block id="postSenderName"/>
            <? endif;?>
        <? endif;?>
        </form>
        <rn:block id="postForm"/>
    </div>
    <rn:block id="bottom"/>
</div>
