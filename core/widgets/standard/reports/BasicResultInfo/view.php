<div class="<?= $this->classList ?>">
    <rn:block id="top"/>
    <? /* suggested searches */ ?>
    <?if(!$this->data['suggestionClass']):?>
    <div class="rn_Suggestion">
        <rn:block id="suggestions">
            <?=$this->data['attrs']['label_suggestion'];?>
            <? for($i = 0; $i < count($this->data['suggestionData']); $i++): ?>
                <a href="<?=$this->data['js']['linkUrl'].$this->data['suggestionData'][$i].'/suggested/1'.$this->data['appendedParameters'] . \RightNow\Utils\Url::sessionParameter();?>"><?=$this->data['suggestionData'][$i]?></a>&nbsp;
            <? endfor;?>
        </rn:block>
    </div>
    <?endif;?>
    <? /* spelling */ ?>
    <?if(!$this->data['spellClass']):?>
    <div class="rn_Spell">
        <rn:block id="spelling">
        <?=$this->data['attrs']['label_spell'];?>
        <?if($this->data['spellData']):?>
        <a href="<?=$this->data['js']['linkUrl'].$this->data['spellData'].'/dym/1'.$this->data['appendedParameters'] . \RightNow\Utils\Url::sessionParameter();?>"><?=$this->data['spellData'];?></a>
        <?endif;?>
        </rn:block>
    </div>
    <?endif;?>
    <? /* no results */ ?>
    <?if(!$this->data['noResultsClass']):?>
    <div class="rn_NoResults">
        <rn:block id="noResults">
        <?=$this->data['attrs']['label_no_results'];?>
        <br/><br/>
        <?=$this->data['attrs']['label_no_results_suggestions'];?>
        </rn:block>
    </div>
    <?endif;?>
    <? /* results */ ?>
    <? if($this->data['attrs']['display_results'] && !$this->data['resultClass']):?>
    <div class="rn_Results">
    <rn:block id="topResults"/>
    <? if($this->data['searchQuery']):?>
        <? $query = '';
            foreach($this->data['searchQuery'] as $searchTerm):?>
            <? if($searchTerm['stop']):?>
                <? $query .= "<span class='rn_Strike' title='{$this->data['attrs']['label_common']}'>{$searchTerm['word']}</span> ";?>
            <? elseif($searchTerm['notFound']):?>
                <? $query .= "<span class='rn_Strike' title='{$this->data['attrs']['label_dictionary']}'>{$searchTerm['word']}</span> ";?>
            <? else:?>
            <? $query .= '<a href="'.$this->data['js']['linkUrl'].$searchTerm['url'].$this->data['appendedParameters'] . \RightNow\Utils\Url::sessionParameter()."\">{$searchTerm['word']}</a> ";?>
            <? endif;?>
        <? endforeach;?>
        <? printf($this->data['attrs']['label_results_search_query'], $this->data['firstResult'], $this->data['lastResult'], $this->data['totalResults'], $query);?>
    <? else:?>
        <? printf($this->data['attrs']['label_results'], $this->data['firstResult'], $this->data['lastResult'], $this->data['totalResults']);?>
    <? endif;?>
    <rn:block id="bottomResults"/>
    </div>
    <? endif;?>
    <rn:block id="bottom"/>
</div>