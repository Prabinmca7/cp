<div id="rn_<?=$this->instanceID;?>" class="<?= $this->classList ?>">
    <rn:block id="top"/>
    <ul>
        <rn:block id="preList"/>
    <? $count = 0;
       foreach($this->data['results'] as $result):?>
        <li>
            <rn:block id="title">
            <span class="rn_PostTitle"><a href="<?=$result->webUrl . ($this->data['attrs']['post_link_base_url'] ? '' : \RightNow\Utils\Url::communitySsoToken());?>"><?=$result->name;?></a></span>
            </rn:block>
        <? if($this->data['attrs']['show_post_content']):?>
            <rn:block id="content">
            <span class="rn_PostContent"><?=$result->preview;?></span>
            </rn:block>
        <? endif;?>
        <? if($this->data['attrs']['show_author_name']):?>
            <rn:block id="preAuthor">
            <span class="rn_PostSubContent rn_PostAuthor">
                <?=sprintf($this->data['attrs']['label_author'], "<a href='{$this->data['baseUrl']}/people/{$result->createdByHash}" . ($this->data['attrs']['author_link_base_url'] ? '' : \RightNow\Utils\Url::communitySsoToken()) . "'" . ">{$result->createdByName}</a>");?>
            </span>
            </rn:block>
        <? endif;?>
        <? if($this->data['attrs']['show_updated_date']):?>
            <rn:block id="date">
            <span class="rn_PostSubContent rn_PostDate">
                <?=sprintf($this->data['attrs']['label_updated'], $result->lastActivity);?>
            </span>
            </rn:block>
        <? endif;?>
        <? if($this->data['attrs']['show_comment_count']):?>
            <rn:block id="commentCount">
            <span class="rn_PostSubContent rn_PostCommentCount">
                <?=sprintf($this->data['attrs']['label_comment'], $result->commentCount);?>
            </span>
            </rn:block>
        <? endif;?>
        </li>
    <? endforeach; ?>
        <rn:block id="postList"/>
    </ul>
<? if($this->data['attrs']['show_all_results_link']):?>
    <rn:block id="allResultsLink">
    <a href="<?=$this->data['fullResultsUrl'];?>" class="rn_AllResults"><?=$this->data['attrs']['label_all_results'];?></a>
    </rn:block>
<? endif;?>
    <rn:block id="bottom"/>
</div>