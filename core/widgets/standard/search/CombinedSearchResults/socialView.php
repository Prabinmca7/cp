    <li id="rn_<?=$this->instanceID;?>_Social" class="rn_Social">
    <? if(is_array($this->data['socialData']['results']) && count($this->data['socialData']['results'])):?>
        <ul class="rn_SocialList">
            <li>
                <? if($this->data['attrs']['label_social_results_heading']):?>
                <a class="rn_Link rn_Heading" href="javascript:void(0);"><?=$this->data['attrs']['label_social_results_heading'];?></a>
                <? endif;?>
                <ul class="rn_Links">
                    <? $count = 0; $className = '';?>
                    <? foreach($this->data['socialData']['results'] as $result):?>
                    <? if($count++ === $this->data['attrs']['maximum_social_results']) $className = 'rn_Hidden';?>
                    <li class="<?=$className;?>">
                        <span class="rn_PostAuthor"><img src="<?=$result->createdByAvatar;?>" alt=""/></span>
                        <span class="rn_PostTitle"><a href="<?=$result->webUrl . ($this->data['attrs']['social_post_link_base_url'] ? '' : \RightNow\Utils\Url::communitySsoToken());?>"><?=$result->name;?></a></span>
                        <span class="rn_PostSnippet"><?=$result->preview;?></span>
                        <span class="rn_PostAuthorName">
                            <? if ($this->data['attrs']['display_social_author_link']): ?>
                            <a href="<?=($this->data['baseUrl'] . '/people/' . $result->createdByHash . ($this->data['attrs']['author_link_base_url'] ? '' : \RightNow\Utils\Url::communitySsoToken()))?>"><?=$result->createdByName?></a>
                            <? else: ?>
                            <?=$result->createdByName;?>
                            <? endif; ?>
                        </span>
                        <span class="rn_PostDate"><?=$result->lastActivity;?></span>
                    </li>
                    <? endforeach;?>
                </ul>
                <? if($this->data['attrs']['label_more_social_results'] && $count > $this->data['attrs']['maximum_social_results']):?>
                <? $moreResults = $count - $this->data['attrs']['maximum_social_results'];
                   $moreResultsLabel = ($moreResults === 1) ? $this->data['attrs']['label_single_social_result'] : $this->data['attrs']['label_more_social_results'];?>
                <a href="javascript:void(0);" class="rn_More"><? printf($moreResultsLabel, $moreResults);?> <span class="rn_ScreenReaderOnly"><?=\RightNow\Utils\Config::getMessage(CLICK_TO_EXPAND_CMD)?></span></a>
                <? endif;?>
            </li>
        </ul>
    <? endif;?>
    </li>