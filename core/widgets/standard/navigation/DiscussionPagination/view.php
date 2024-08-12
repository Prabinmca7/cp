<div id="rn_<?= $this->instanceID ?>" class="<?= $this->classList ?>">
    <rn:block id="top"/>
        <? if (!(isset($this->data['isOldestQuestion']) && isset($this->data['isNewestQuestion']) && $this->data['isOldestQuestion'] && $this->data['isNewestQuestion'])): ?>
            <div id="rn_<?= $this->instanceID ?>_DiscussionPagination" class="rn_DiscussionPaginationLinks">
            <? if (!isset($this->data['isOldestQuestion']) || !$this->data['isOldestQuestion']): ?>
                <rn:block id="preFirstLink"/>
                <a href="javascript:void(0);" role="link" data-type="oldestQuestion" id="rn_<?= $this->instanceID; ?>_FirstDiscussion" class="rn_FirstDiscussion" title="<?= sprintf($this->data['attrs']['label_first_tooltip'], $this->data['prodcat_name']) ?>">
                    <?=$this->data['attrs']['label_first_link'];?>
                </a>
                <rn:block id="postFirstLink"/>
                <span class="rn_PageHellip">&hellip;</span>
                <rn:block id="prePrevLink"/>
                <a href="javascript:void(0);" role="link" data-type="prevQuestion" id="rn_<?= $this->instanceID; ?>_PreviousDiscussion" class="rn_PreviousDiscussion" title="<?= sprintf($this->data['attrs']['label_previous_tooltip'], $this->data['prodcat_name']) ?>">
                    <?=$this->data['attrs']['label_previous_link'];?>
                </a>
                <rn:block id="postPrevLink"/>
                <? endif;?>
            <span class="rn_Separator"></span>
            <? if (!isset($this->data['isNewestQuestion']) || !$this->data['isNewestQuestion']): ?>
                <rn:block id="preNextLink"/>
                <a href="javascript:void(0);" role="link" data-type="nextQuestion" id="rn_<?= $this->instanceID; ?>_NextDiscussion" class="rn_NextDiscussion" title="<?= sprintf($this->data['attrs']['label_next_tooltip'], $this->data['prodcat_name']) ?>">
                    <?=$this->data['attrs']['label_next_link'];?>
                </a>
                <rn:block id="postNextLink"/>
                <span class="rn_PageHellip">&hellip;</span>
                <rn:block id="preLastLink"/>
                <a href="javascript:void(0);" role="link" data-type="newestQuestion" id="rn_<?= $this->instanceID; ?>_LastDiscussion" class="rn_LastDiscussion" title="<?= sprintf($this->data['attrs']['label_last_tooltip'], $this->data['prodcat_name']) ?>">
                    <?=$this->data['attrs']['label_last_link'];?>
                </a>
                <rn:block id="postLastLink"/>
            <? endif;?>
            </div>
        <? endif;?>
    <rn:block id="bottom"/>
</div>
