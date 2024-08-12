<rn:block id="top">
<div id="rn_<?= $this->instanceID ?>_UnsubscribeAll" class="<?= empty($this->data['reportData']['data']) ? "rn_Hidden" : "" ?> rn_UnsubscribeAll">
    <a href="javascript:void(0);" role="button"><?= $this->data['attrs']['label_unsubscribe_all']; ?></a>
</div>
</rn:block>

<rn:block id="resultListItem">
<li class="rn_Discussions" data-id="<?=$value[0];?>">
    <div class="rn_Discussion_Info" >
        <span class="rn_Element1"><?=$value[1];?></span>
        <span class="rn_ElementsHeader"><?=$this->getHeader($this->data['reportData']['headers'][2]);?></span>
        <span class="rn_ElementsData"><?=$value[2];?></span>
    </div>
    <div class="rn_Discussion_Actions">
        <button class="rn_Discussion_Delete"><?= $this->data['attrs']['label_unsubscribe']; ?></button>
    </div>
</li>
</rn:block>

<rn:block id="noResultListItem">
<?= $this->data['attrs']['label_no_notification']; ?>
</rn:block>

<rn:block id="bottom">
<div id="rn_<?=$this->instanceID;?>_Bottom" class="rn_Bottom">
<? if($this->data['attrs']['subscription_type'] !== 'Question'): ?>
    <div class="rn_ButtonGroup">
        <button id="rn_<?=$this->instanceID;?>_AddButton" class="rn_AddButton"><?=$this->data['attrs']['label_add_prodcat_notification_button'];?></button>
    </div>
    <form id="rn_<?= $this->instanceID ?>_Form">
        <div id="rn_<?=$this->instanceID;?>_Dialog" class="rn_Hidden">
            <div id="rn_<?= $this->instanceID; ?>_ErrorMessage"></div>
            <div class="rn_SelectionWidget">
                <? if($this->data['attrs']['subscription_type'] === 'Product'): ?>
                    <rn:widget path="input/ProductCategoryInput" name="CommunityQuestion.product" verify_permissions="Read" sub_id="prod"/>
                <? else: ?>
                    <rn:widget path="input/ProductCategoryInput" name="CommunityQuestion.Category" verify_permissions="Read" sub_id="cat"/>
                <? endif;?>
            </div>
        </div>
    </form>
<? endif;?>
</div>
</rn:block>