<div id="rn_<?=$this->instanceID; ?>" class="<?= $this->classList ?>">
    <rn:block id="top"/>
    <rn:block id="preLoadingIndicator"/>
    <div id="rn_<?= $this->instanceID ?>_Loading"></div>
    <rn:block id="posLoadingIndicator"/>
    <div id="rn_<?= $this->instanceID ?>_Content">
        <rn:block id="topContent"/>
    <? if (is_array($this->data['results']) && count($this->data['results']) > 0): ?>
            <rn:block id="preResultList"/>
            <ul>
                <rn:block id="topResultList"/>
            <? foreach ($this->data['results'] as $result): ?>
                <rn:block id="prelistItem"/>
                <? if ($this->data['attrs']['show_profile_picture']): ?>
                <li class="rn_HasProfilePicture">
                <? else: ?>
                <li>
                <? endif; ?>
                    <rn:block id="topListItem"/>
                    <span class="rn_PostTitle">
                        <a href="<?= $result->webUrl . ($this->data['attrs']['post_link_base_url'] ? '' : \RightNow\Utils\Url::communitySsoToken()) ?>"><?= $result->name ?></a>
                    </span>
                <? if ($this->data['attrs']['show_post_content']): ?>
                    <span class="rn_PostContent"><?= $result->preview ?></span>
                <? endif; ?>
                <? if ($this->data['attrs']['show_author_name']): ?>
                    <span class="rn_PostSubContent rn_PostAuthor">
                        <? printf($this->data['attrs']['label_author'], "<a href='{$this->data['baseUrl']}/people/{$result->createdByHash}" . ($this->data['attrs']['author_link_base_url'] ? '' : \RightNow\Utils\Url::communitySsoToken()) . "'>{$result->createdByName}</a>"); ?>
                    <? if ($this->data['attrs']['show_profile_picture']): ?>
                        <img src="<?= $result->createdByAvatar ?>" alt=""/>
                    <? endif; ?>
                    </span>
                <? endif; ?>
                <? if ($this->data['attrs']['show_updated_date']): ?>
                    <span class="rn_PostSubContent rn_PostDate">
                        <? printf($this->data['attrs']['label_updated'], $result->lastActivity); ?>
                    </span>
                <? endif; ?>
                <? if ($this->data['attrs']['show_rating_count'] && $result->ratingTotal && ($rating = (int) $result->ratingTotal / 100) > 0): ?>
                    <span class="rn_PostSubContent rn_PostRating">
                        <? printf((($rating === 1) ? $this->data['attrs']['label_single_rating'] : $this->data['attrs']['label_rating']), $rating); ?>
                    </span>
                <? endif; ?>
                <? if ($this->data['attrs']['show_comment_count']): ?>
                    <span class="rn_PostSubContent rn_PostCount">
                        <? printf($this->data['attrs']['label_comment'], $result->commentCount); ?>
                    </span>
                <? endif; ?>
                    <rn:block id="bottomListItem"/>
                </li>
                <rn:block id="postlistItem"/>
            <? endforeach; ?>
                <rn:block id="bottomResultList"/>
            </ul>
            <rn:block id="postResultList"/>
        <? if ($this->data['attrs']['show_all_results_link']): ?>
            <a href="<?= $this->data['js']['fullResultsUrl'] ?>" class="rn_AllResults"><?= $this->data['attrs']['label_all_results'] ?></a>
        <? endif; ?>
    <? else: ?>
        <span class="rn_NoResults"><?= $this->data['attrs']['label_no_results'] ?></span>
    <? endif; ?>
        <rn:block id="bottomContent"/>
    </div>
    <? if ($this->data['attrs']['pagination_enabled']): ?>
        <rn:block id="prePagination"/>
        <div id="rn_<?= $this->instanceID ?>_Pagination" class="rn_Pagination <?= $this->data['paginationClass'] ?>">
            <rn:block id="preBackLink"/>
            <a href="javascript:void(0)" id="rn_<?= $this->instanceID ?>_Back" data-page="<?= $this->data['js']['currentPage'] - 1 ?>" class="<?= $this->data['backClass'] ?>"><?= $this->data['attrs']['label_back'] ?></a>
            <rn:block id="postBackLink"/>
            <rn:block id="prePageLinks"/>
            <span id="rn_<?= $this->instanceID ?>_Pages" class="rn_PageLinks">
                <rn:block id="pageLinksTop"/>
                <? for ($i = $this->data['js']['startPage']; $i <= $this->data['js']['endPage']; $i++): ?>
                    <rn:block id="prePageLink"/>
                    <? if ($i === $this->data['js']['currentPage']): ?>
                        <span class="rn_CurrentPage"><?= $i ?></span>
                    <? else: ?>
                        <a href="javascript:void(0)" data-page="<?= $i ?>" title="<? printf($this->data['attrs']['label_page'], $i, $this->data['totalPages']); ?>"><?= $i ?></a>
                    <? endif; ?>
                    <rn:block id="postPageLink"/>
                <? endfor; ?>
                <rn:block id="pageLinksBottom"/>
            </span>
            <rn:block id="postPageLinks"/>
            <rn:block id="preForwardLink"/>
            <a href="javascript:void(0)" id="rn_<?= $this->instanceID ?>_Forward" data-page="<?= $this->data['js']['currentPage'] + 1 ?>" class="<?= $this->data['forwardClass'] ?>"><?= $this->data['attrs']['label_forward'] ?></a>
            <rn:block id="postForwardLink"/>
        </div>
        <rn:block id="postPagination"/>
    <? endif; ?>
    <rn:block id="bottom"/>
</div>
