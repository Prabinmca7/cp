<div id="rn_<?=$this->instanceID?>" class="<?= $this->classList ?>">
    <rn:block id="top"/>
    <rn:block id="prePageList"/>
    <div class="rn_PaginationLinks" role="navigation" aria-label="<?= $this->data['attrs']['label_screen_reader_description']; ?>">
        <ul>
            <rn:block id="prePreviousLink"/>
            <li class="rn_PreviousPage <?= $this->data['js']['currentPage'] > 1 ?: 'rn_Hidden' ?>">
                <span>
                    <a data-rel="previous" href="<?= $this->data['js']['pageUrl'] . ($this->data['js']['currentPage'] - 1) ?>" data-pageID="<?= $this->data['js']['currentPage'] - 1; ?>">
                        <?= $this->data['attrs']['label_back']; ?>
                    </a>
                </span>
            </li>
            <rn:block id="postPreviousLink"/>

            <rn:block id="listTop"/>
            <? if ($this->data['js']['endPage'] > 1): ?>
                <? for ($pageNumber = 1; $pageNumber <= $this->data['js']['endPage']; $pageNumber++): ?>
                    <? $title = $this->helper('Pagination')->paginationLinkTitle($this->data['attrs']['label_page'], $pageNumber, $this->data['js']['endPage']); ?>
                    <rn:block id="prePageLink"/>
                    <? /* display page number without a link */ ?>
                    <? if ($this->helper('Pagination')->isCurrentPage($pageNumber, $this->data['js']['currentPage'])): ?>
                        <? $title = $this->helper('Pagination')->paginationLinkTitle($this->data['attrs']['label_current_page'], $pageNumber, $this->data['js']['endPage']); ?>
                        <rn:block id="preCurrentPage"/>
                        <li class="rn_CurrentPage"><span tabindex="0" title="<?= $title; ?>" aria-label="<?= $title ?>"><?=$pageNumber;?></span></li>
                        <rn:block id="postCurrentPage"/>

                    <? /* display page number as link */ ?>
                    <? elseif ($this->helper('Pagination')->shouldShowPageNumber($pageNumber, $this->data['js']['currentPage'], $this->data['js']['endPage'])): ?>
                        <rn:block id="preOtherPage"/>
                        <li><a id="rn_<?=$this->instanceID?>_PageLink_<?= $pageNumber ?>" data-rel="<?= $pageNumber; ?>" href="<?= $this->data['js']['pageUrl'] . $pageNumber ?>" title="<?= $title; ?>" aria-label="<?= $title ?>"><?=$pageNumber;?></a></li>
                        <rn:block id="postOtherPage"/>

                    <? /* display hellip */ ?>
                    <? elseif ($this->helper('Pagination')->shouldShowHellip($pageNumber, $this->data['js']['currentPage'], $this->data['js']['endPage'])): ?>
                        <rn:block id="preHellip"/>
                        <li><span class="rn_PageHellip">&hellip;</span></li>
                        <rn:block id="postHellip"/>
                    <? endif; ?>
                    <rn:block id="postPageLink"/>
                <? endfor; ?>
            <? endif; ?>
            <rn:block id="listBottom"/>

            <rn:block id="preNextLink"/>
            <li class="rn_NextPage <?= $this->data['js']['endPage'] > $this->data['js']['currentPage'] ?: 'rn_Hidden' ?>">
                <span>
                    <a data-rel="next" href="<?= $this->data['js']['pageUrl'] . ($this->data['js']['currentPage'] + 1) ?>" data-pageID="<?= $this->data['js']['currentPage'] + 1; ?>">
                        <?= $this->data['attrs']['label_forward']; ?>
                    </a>
                </span>
            </li>
            <rn:block id="postNextLink"/>
        </ul>
    </div>
    <rn:block id="postPageList"/>
    <rn:block id="bottom"/>
</div>
