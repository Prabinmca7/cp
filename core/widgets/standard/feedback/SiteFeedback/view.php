<div id="rn_<?=$this->instanceID?>" class="<?= $this->classList ?>">
    <rn:block id="top"/>
    <?  // Link to click to open the feedback form. ?>
    <rn:block id="preLink"/>
    <a id="rn_<?=$this->instanceID?>_FeedbackLink" href="javascript:void(0);"><?=$this->data['attrs']['label_link'];?></a>
    <? /* Container for feedback form.  It's hidden on the page. */ ?>
    <rn:block id="postLink"/>
    <div class="rn_Hidden">
        <rn:block id="preForm"/>
        <div id="rn_<?=$this->instanceID?>_SiteFeedbackForm" class="rn_SiteFeedbackForm">
            <rn:block id="formTop"/>
            <div id="rn_<?=$this->instanceID;?>_ErrorMessage"></div>
            <form>
            <? if (!$this->data['js']['isProfile']): ?>
                <rn:block id="preEmailInput"/>
                <label for="rn_<?=$this->instanceID?>_EmailInput">
                    <?= $this->data['attrs']['label_email_address'] ?>
                    <?= $this->render('Partials.Forms.RequiredLabel') ?>
                </label>
                <input id="rn_<?=$this->instanceID?>_EmailInput" class="rn_EmailField" type="text" value="<?=$this->data['js']['email'];?>"/>
                <rn:block id="postEmailInput"/>
            <? endif;?>
            <rn:block id="preTextInput"/>
            <label for="rn_<?=$this->instanceID?>_FeedbackTextarea">
                <?= $this->data['attrs']['label_comment_box'] ?>
                <?= $this->render('Partials.Forms.RequiredLabel') ?>
            </label>
            <textarea id="rn_<?=$this->instanceID?>_FeedbackTextarea" name="rn_<?=$this->instanceID?>_FeedbackTextarea" class="rn_Textarea" rows="4" cols="60"></textarea>
            <rn:block id="postTextInput"/>
            </form>
            <rn:block id="formBottom"/>
        </div>
        <rn:block id="postForm"/>
        <? /* End form */ ?>
    </div>
    <rn:block id="bottom"/>
</div>
