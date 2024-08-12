<div class="<?= $this->classList ?>">
    <rn:block id="top"/>
    <? if ($this->data['js']['currentPage'] > 1 && $this->data['js']['endPage'] && $this->data['attrs']['label_back']): ?>
        <rn:block id="prePreviousLink"/>
        <a href="<?= $this->data['js']['pageUrl'] . ($this->data['js']['currentPage'] - 1) . $this->data['appendedParameters'];?>" class="<?=$this->data['backClass'];?>"><?=$this->data['attrs']['label_back'];?></a>
        <rn:block id="postPreviousLink"/>
    <? endif; ?>
    <? if ($this->data['js']['endPage'] > 1): ?>
        <span class="rn_PageLinks">
            <rn:block id="listTop"/>
            <? for($i = 1; $i <= $this->data['js']['endPage']; $i++):?>
                <rn:block id="preListItem"/>
                <? /* display page number without a link */ ?>
                <? if($i == $this->data['js']['currentPage']):?>
                    <rn:block id="preCurrentPage"/>
                    <span class="rn_CurrentPage"><?=$i;?></span>
                    <rn:block id="postCurrentPage"/>

                <? /* display page number as link */ ?>
                <? elseif ($this->helper('Pagination')->shouldShowPageNumber($i, $this->data['js']['currentPage'], $this->data['js']['endPage'])): ?>
                    <? $title = $this->helper('Pagination')->paginationLinkTitle($this->data['attrs']['label_page'], $i, $this->data['js']['endPage']); ?>
                    <rn:block id="preOtherPage"/>
                    <a href="<?= $this->data['js']['pageUrl'] . $i . $this->data['appendedParameters'] ?>" title="<?= $title; ?>"><?= $i ?><span class="rn_ScreenReaderOnly"><?= $title; ?></span></a>
                    <rn:block id="postOtherPage"/>

                <? /* display hellip */ ?>
                <? elseif ($this->helper('Pagination')->shouldShowHellip($i, $this->data['js']['currentPage'], $this->data['js']['endPage'])): ?>
                    <rn:block id="preHellip"/>
                    <span class="rn_PageHellip">&hellip;</span>
                    <rn:block id="postHellip"/>
                <? endif; ?>
                <rn:block id="postListItem"/>
            <? endfor;?>
            <rn:block id="listBottom"/>
        </span>
    <? endif; ?>
    <? if ($this->data['attrs']['label_forward'] && $this->data['js']['endPage'] > $this->data['js']['currentPage']): ?>
        <rn:block id="preNextLink"/>
        <a href="<?= $this->data['js']['pageUrl'] . ($this->data['js']['currentPage'] + 1) . $this->data['appendedParameters'] . \RightNow\Utils\Url::sessionParameter();?>" class="<?=$this->data['forwardClass'];?>"><?=$this->data['attrs']['label_forward']?></a>
        <rn:block id="postNextLink"/>
    <? endif; ?>
    <rn:block id="bottom"/>
</div>
