<div id="rn_<?= $this->instanceID ?>" class="<?= $this->classList ?>">
    <rn:block id="top"/>
    <a href="javascript:void(0);" id="rn_<?=$this->instanceID;?>_TriggerLink" class="rn_SelectFilterLink"><?=$this->data['attrs']['label_link'];?></a>
    <rn:block id="preDialog"/>
    <div id="rn_<?=$this->instanceID;?>_DialogContent" class="rn_DialogContent rn_Hidden">
        <div id="rn_<?= $this->instanceID ?>_ErrorLocation"></div>
        <div class="rn_SocialFilter">
            <? if ($this->data['attrs']['object_type'] !== 'CommunityUser' && in_array('date', $this->data['attrs']['include_filters'])): ?>
                <div class="rn_SocialDialogFilterItem">
                    <rn:widget path="moderation/ModerationDateFilter" sub_id="date" />
                </div>
            <? endif; ?>
            <? if (in_array('status', $this->data['attrs']['include_filters'])): ?>
                <div class="rn_SocialDialogFilterItem">
                    <rn:widget path="moderation/ModerationStatusFilter" sub_id="status" />
                </div>
            <? endif; ?>
            <? if ($this->data['attrs']['object_type'] !== 'CommunityUser' && in_array('prodcat', $this->data['attrs']['include_filters'])): ?>
                <div class="rn_SocialDialogFilterItem">
                    <rn:widget path="search/ProductCategorySearchFilter" verify_permissions="true" sub_id="prodcat" enable_prod_cat_no_value_option="true"/>
                </div>
            <? endif; ?>
            <? if ($this->data['attrs']['object_type'] !== 'CommunityUser' && in_array('flag', $this->data['attrs']['include_filters'])): ?>
                <div class="rn_SocialDialogFilterItem">
                    <rn:widget path="moderation/ModerationContentFlagFilter" sub_id="flag"/>
                </div>
            <? endif; ?>
        </div>
    </div>
    <rn:block id="postDialog"/>
    <rn:block id="bottom"/>
</div>
