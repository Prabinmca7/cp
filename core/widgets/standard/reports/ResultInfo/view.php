<div id="rn_<?=$this->instanceID;?>" class="<?= $this->classList ?>">
    <rn:block id="top"/>
    <? /* suggested searches */ ?>
    <div id="rn_<?=$this->instanceID;?>_Suggestion" class="rn_Suggestion <?=$this->data['suggestionClass'];?>">
        <rn:block id="suggestions">
        <?=$this->data['attrs']['label_suggestion'];?>
        <? if(isset($this->data['suggestionData']) && $this->data['suggestionData']): ?>
        <ul>
        <? for($i = 0; $i < count($this->data['suggestionData']); $i++): ?>
            <rn:block id="suggestionLink">
            <li>
            <a href="<?=$this->data['js']['linkUrl'].$this->data['suggestionData'][$i].'/suggested/1'.$this->data['appendedParameters'] . \RightNow\Utils\Url::sessionParameter();?>"><?=$this->data['suggestionData'][$i]?></a>
            </li>
            </rn:block>
        <? endfor;?>
        </ul>
        <?endif;?>
        </rn:block>
    </div>
    <? /* spelling */ ?>
    <div id="rn_<?=$this->instanceID;?>_Spell" class="rn_Spell <?=$this->data['spellClass'];?>">
        <rn:block id="spelling">
        <?=$this->data['attrs']['label_spell'];?>
        <?if(isset($this->data['spellData']) && $this->data['spellData']):?>
        <a href="<?=$this->data['js']['linkUrl'].$this->data['spellData'].'/dym/1'.$this->data['appendedParameters'] . \RightNow\Utils\Url::sessionParameter();?>"><?=$this->data['spellData'];?></a>
        <?endif;?>
        </rn:block>
    </div>
    <? /* no results */ ?>
    <div id="rn_<?=$this->instanceID;?>_NoResults" class="rn_NoResults <?=$this->data['noResultsClass'];?>">
        <rn:block id="noResults">
        <?=$this->data['attrs']['label_no_results'];?>
        <br/><br/>
        <?=$this->data['attrs']['label_no_results_suggestions'];?>
        </rn:block>
    </div>
    <? /* results */ ?>
    <? if($this->data['attrs']['display_results']):?>
    <div id="rn_<?=$this->instanceID;?>_Results" class="rn_Results <?=$this->data['resultClass'];?>">
    <rn:block id="topResults"/>
    <? if(isset($this->data['searchQuery']) && $this->data['searchQuery']):?>
        <? $query = '';
            foreach($this->data['searchQuery'] as $searchTerm):?>
            <? if(isset($searchTerm['stop']) && $searchTerm['stop']):?>
                <? $query .= "<span class='rn_Strike' title='{$this->data['attrs']['label_common']}'>{$searchTerm['word']}</span> ";?>
            <? elseif(isset($searchTerm['notFound']) && $searchTerm['notFound']):?>
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
