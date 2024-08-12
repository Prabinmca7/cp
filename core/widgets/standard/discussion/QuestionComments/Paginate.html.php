<rn:block id="top"/>
<div class="rn_Paginator" role="navigation">
    <? if($this->data['attrs']['label_back'] && $this->data['currentPage'] > 1): ?>
        <span class="rn_BackLink">
            <rn:block id="preBackLink"/>
            <a href="<?= $this->helper->paginationLinkUrl($this->data['currentPage'] - 1) ?>" data-pageID="<?= $this->data['currentPage'] - 1; ?>" rel="prev">
                <rn:block id="preBackLabel"/>
                <?= $this->data['attrs']['label_back']; ?>
                <rn:block id="postBackLabel"/>
            </a>
            <rn:block id="postBackLink"/>
        </span>
    <? endif; ?>

    <span class="rn_PageLinks">
        <rn:block id="listTop"/>
        <? for($pageNumber = 1; $pageNumber <= $this->data['endPage']; $pageNumber++): ?>
            <? $title = $this->helper->paginationLinkTitle($this->data['attrs']['label_page'], $pageNumber, $this->data['endPage']); ?>
            <rn:block id="preListItem"/>

            <? /* display page number without a link */ ?>
            <? if($this->helper->isCurrentPage($pageNumber, $this->data['currentPage'])): ?>
                <? $title = $this->helper->paginationLinkTitle($this->data['attrs']['label_current_page'], $pageNumber, $this->data['endPage']); ?>
                <rn:block id="preCurrentPage"/>
                <span tabindex="0" class="rn_CurrentPage" title="<?= $title; ?>" aria-label="<?= $title ?>"><?=$pageNumber;?></span>
                <rn:block id="postCurrentPage"/>

            <? /* display page number as link */ ?>
            <? elseif($this->helper->shouldShowPageNumber($pageNumber, $this->data['currentPage'], $this->data['endPage'])): ?>
                <rn:block id="preOtherPage"/>
                <a data-pageID="<?= $pageNumber; ?>" href="<?= $this->helper->paginationLinkUrl($pageNumber) ?>" title="<?= $title; ?>"><?=$pageNumber;?><span class="rn_ScreenReaderOnly"><?= $title; ?></span></a>
                <rn:block id="postOtherPage"/>

            <? /* display hellip */ ?>
            <? elseif($this->helper->shouldShowHellip($pageNumber, $this->data['currentPage'], $this->data['endPage'])): ?>
                <rn:block id="preHellip"/>
                <span class="rn_PageHellip">&hellip;</span>
                <rn:block id="postHellip"/>
            <? endif; ?>

            <rn:block id="postListItem"/>
        <? endfor; ?>
        <rn:block id="listBottom"/>
    </span>

    <? if($this->data['attrs']['label_forward'] && $this->data['endPage'] > $this->data['currentPage']): ?>
        <span class="rn_ForwardLink">
            <rn:block id="preForwardLink"/>
            <a href="<?= $this->helper->paginationLinkUrl($this->data['currentPage'] + 1) ?>" data-pageID="<?= $this->data['currentPage'] + 1; ?>" rel="next">
                <rn:block id="preForwardLabel"/>
                <?= $this->data['attrs']['label_forward']; ?>
                <rn:block id="postForwardLabel"/>
            </a>
            <rn:block id="postForwardLink"/>
        </span>
    <? endif; ?>
</div>
<rn:block id="bottom"/>