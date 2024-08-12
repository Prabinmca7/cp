<div id="rn_<?=$this->instanceID?>" class="<?= $this->classList ?>">
    <rn:block id="top"/>
    <rn:block id="preExpandLink"/>
    <span class="rn_RefineLabel"><a href="<?=$this->data['resetUrl']?>"><?=$this->data['attrs']['label_title']?></a></span>
    <rn:block id="postExpandLink"/>
    <? if (!empty($this->data['selectedData'])): ?>
        <rn:block id="preDisplayedFilters"/>
        <? for ($i = 0; $i < count($this->data['selectedData']); ++$i): ?>
            <rn:block id="preFilter"/>
            <? if ($i < (count($this->data['selectedData']) - 1)): ?>
                <a href="<?=$this->data['selectedData'][$i]['url']?>"><?=$this->data['selectedData'][$i]['label']?></a> &gt;
            <? else: ?>
                <?=$this->data['selectedData'][$i]['label']?>
            <? endif; ?>
            <rn:block id="postFilter"/>
        <? endfor ?>
        <rn:block id="postDisplayedFilters"/>
    <? endif; ?>
    <? if (!empty($this->data['levelData'])): ?>
        <rn:block id="preLevelData"/>
        <ul>
            <?if($this->data['allowNextStep']):?>
            <li>
                <a href="<?=$this->data['applyUrl']?>"><?=$this->data['attrs']['label_all_values']?></a>
            </li>
            <?endif;?>
        <? foreach ($this->data['levelData'] as $item): ?>
            <li>
                <a href="<?=$item['url']?>"><?=$item['label']?></a>
                <? if($item['hasChildren']): ?> &gt; <? endif; ?>
            </li>
        <? endforeach; ?>
        </ul>
        <rn:block id="postLevelData"/>
    <? endif; ?>
    <rn:block id="preSearchControls"/>
    <div class="rn_AdvancedSearchButtons">
    <?if($this->data['allowNextStep']):?>
        <form class="rn_AdvancedSearchSubmit" method="get" action="<?=$this->data['applyUrl']?>">
            <div>
                <rn:block id="preSearchButton"/>
                <input type="submit" value="<?=$this->data['attrs']['label_search_button']?>" />
                <rn:block id="postSearchButton"/>
            </div>
        </form>
    <?endif;?>
        <form method="get" action="<?=$this->data['resetUrl']?>">
            <div>
                <rn:block id="preResetButton"/>
                <input type="submit" value="<?=$this->data['attrs']['label_clear_filters_button']?>" />
                <rn:block id="postResetButton"/>
            </div>
        </form>
    </div>
    <rn:block id="postSearchControls"/>
    <rn:block id="bottom"/>
</div>
