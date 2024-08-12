<div id="rn_<?= $this->instanceID ?>" class="<?= $this->classList ?>">
    <rn:block id="top"/>
    <rn:block id="prePageList"/>
    <div class="rn_PaginationLinks" role="navigation" aria-label="<?= $this->data['attrs']['label_screen_reader_description']; ?>">
        <ul>
            <? if ($this->data['js']['currentPage'] > 1 && $this->data['js']['size'] && $this->data['attrs']['label_back']): ?>
                <rn:block id="prePreviousLink"/>
                <li class="rn_PreviousPage">
                    <span>
                        <a data-rel="previous" href="<?= $this->helper('Pagination')->pageLink($this->data['js']['currentPage'] - 1, $this->data['js']['filter']) ?>" data-pageID="<?= $this->data['js']['currentPage'] - 1; ?>">
                            <?= $this->data['attrs']['label_back']; ?>
                        </a>
                    </span>
                </li>
                <rn:block id="postPreviousLink"/>
            <? endif; ?>

            <rn:block id="listTop"/>
            <? if ($this->data['js']['numberOfPages'] > 1): ?>
                <? for ($pageNumber = 1; $pageNumber <= $this->data['js']['numberOfPages']; $pageNumber++): ?>
                    <? $title = $this->helper('Pagination')->paginationLinkTitle($this->data['attrs']['label_page'], $pageNumber, $this->data['js']['numberOfPages']); ?>
                    <rn:block id="prePageLink"/>
                    <? /* display page number without a link */ ?>
                    <? if ($this->helper('Pagination')->isCurrentPage($pageNumber, $this->data['js']['currentPage'])): ?>
                        <? $title = $this->helper('Pagination')->paginationLinkTitle($this->data['attrs']['label_current_page'], $pageNumber, $this->data['js']['numberOfPages']); ?>
                        <rn:block id="preCurrentPage"/>
                        <li class="rn_CurrentPage"><span tabindex="0" title="<?= $title; ?>" aria-label="<?= $title ?>"><?=$pageNumber;?></span></li>
                        <rn:block id="postCurrentPage"/>

                    <? /* display page number as link */ ?>
                    <? elseif ($this->helper('Pagination')->shouldShowPageNumber($pageNumber, $this->data['js']['currentPage'], $this->data['js']['numberOfPages'])): ?>
                        <rn:block id="preOtherPage"/>
                        <li><a data-rel="<?= $pageNumber; ?>" href="<?= $this->helper('Pagination')->pageLink($pageNumber, $this->data['js']['filter']) ?>" title="<?= $title; ?>" aria-label="<?= $title; ?>"><?=$pageNumber;?></a></li>
                        <rn:block id="postOtherPage"/>

                    <? /* display hellip */ ?>
                    <? elseif ($this->helper('Pagination')->shouldShowHellip($pageNumber, $this->data['js']['currentPage'], $this->data['js']['numberOfPages'])): ?>
                        <rn:block id="preHellip"/>
                        <li><span class="rn_PageHellip">&hellip;</span></li>
                        <rn:block id="postHellip"/>
                    <? endif; ?>
                    <rn:block id="postPageLink"/>
                <? endfor; ?>
            <? endif; ?>
            <rn:block id="listBottom"/>

            <? if (isset($this->data['attrs']['label_forward']) && $this->data['attrs']['label_forward'] && $this->data['js']['numberOfPages'] > $this->data['js']['currentPage']): ?>
                <rn:block id="preNextLink"/>
                <li class="rn_NextPage">
                    <span>
                        <a data-rel="next" href="<?= $this->helper('Pagination')->pageLink($this->data['js']['currentPage'] + 1, $this->data['js']['filter']) ?>" data-pageID="<?= $this->data['js']['currentPage'] + 1; ?>">
                            <?= $this->data['attrs']['label_forward']; ?>
                        </a>
                    </span>
                </li>
                <rn:block id="postNextLink"/>
            <? endif; ?>
        </ul>
    </div>
    <rn:block id="postPageList"/>
    <rn:block id="bottom"/>
</div>
