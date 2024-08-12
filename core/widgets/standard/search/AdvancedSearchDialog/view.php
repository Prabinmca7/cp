<div id="rn_<?=$this->instanceID;?>" class="<?= $this->classList ?>">
    <rn:block id="top"/>
    <a href="javascript:void(0);" id="rn_<?=$this->instanceID;?>_TriggerLink" class="rn_AdvancedLink"><?=$this->data['attrs']['label_link'];?><span class="rn_ScreenReaderOnly"><?=$this->data['attrs']['label_opens_new_dialog'];?></span></a>
    <rn:block id="preDialog"/>
    <div id="rn_<?=$this->instanceID;?>_DialogContent" class="rn_DialogContent rn_Hidden">
        <rn:block id="dialogTop"/>
        <? if ($this->data['attrs']['search_tips_url']): ?>
        <a class="rn_SearchTips" href="javascript:void(0);" onclick="window.open('<?=$this->data['attrs']['search_tips_url']?>', '', 'scrollbars,resizable,width=720,height=700'); return false;">#rn:msg:SEARCH_TIPS_LBL#</a>
        <? endif; ?>
        <div class="rn_AdvancedKeyword rn_AdvancedSubWidget">
            <rn:widget path="search/KeywordText" label_text="#rn:msg:SEARCH_TERMS_UC_CMD#" sub_id="keywordText"/>
        </div>
    <? if(isset($this->data['searchTypeFilters']) && strlen($this->data['searchTypeFilters'])): ?>
        <div class="rn_AdvancedSearchType rn_AdvancedSubWidget">
            <rn:widget path="search/SearchTypeList" filter_list="#rn:php:$this->data['searchTypeFilters']#" sub_id="searchTypeList"/>
        </div>
    <? endif; ?>
    <? if($this->data['webSearch']): ?>
        <div class="rn_AdvancedSort rn_AdvancedSubWidget">
            <rn:widget path="search/WebSearchSort" sub_id="webSearchSort"/>
            <rn:widget path="search/WebSearchType" sub_id="webSearchType"/>
        </div>
    <? else: ?>
        <? if ($this->data['attrs']['display_products_filter']): ?>
        <div class="rn_AdvancedFilter rn_AdvancedSubWidget"><rn:widget path="search/ProductCategorySearchFilter" filter_type="products" sub_id="prod"/></div>
        <? endif; ?>
        <? if ($this->data['attrs']['display_categories_filter']): ?>
        <div class="rn_AdvancedFilter rn_AdvancedSubWidget"><rn:widget path="search/ProductCategorySearchFilter" filter_type="categories" sub_id="cat"/></div>
        <? endif; ?>
        <? if (isset($this->data['menuFilters']) && count($this->data['menuFilters'])): ?>
            <? foreach ($this->data['menuFilters'] as $filter): ?>
            <div class="rn_AdvancedFilter rn_AdvancedSubWidget">
                <rn:widget path="search/FilterDropdown" filter_name="#rn:php:$filter#" sub_id='#rn:php:"filter_$filter"#'/>
            </div>
            <? endforeach; ?>
        <? endif; ?>
        <? if ($this->data['attrs']['display_sort_filter']):?>
        <div class="rn_AdvancedSort rn_AdvancedSubWidget"><rn:widget path="search/SortList" sub_id="sortList"/></div>
        <? endif; ?>
    <? endif;?>
        <rn:block id="dialogBottom"/>
    </div>
    <rn:block id="postDialog"/>
    <rn:block id="bottom"/>
</div>
