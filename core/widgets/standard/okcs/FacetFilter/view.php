<div id="rn_<?= $this->instanceID ?>" class="<?= $this->classList ?>">
    <?if($data['js']['selectedFacets'] === null || $data['js']['selectedFacets'] === undefined) : ?>
    <?$hiddenClass = 'rn_Hidden'?>
    <? endif; ?>
    <? $contentClass = ($this->data['js']['selectedFacets'] === null || $this->data['js']['selectedFacets'] === 'undefined') ? 'rn_Hidden' : ''; ?>
    <div id="rn_<?=$this->instanceID;?>_Content" class="rn_FacetFilter_Content <?=$contentClass ?>">
    <rn:block id="top"/>
    <? if($contentClass === ''): ?>
        <div class="rn_FilterLabel">
            <span class="rn_FacetFilterTitle"><?= $this->data['attrs']['label_filter'] ?>:</span>
        </div>
        <div class="rn_FilterContent">
            <? foreach ($this->data['orderedFacets'] as $orderedFacetId => $orderedFacetDesc): ?>
                <? if(count($this->data['facetObject'][$orderedFacetId]) > 0): ?>
                    <span class="rn_FacetFilterProduct">
                    <span class="rn_FilterChoiceTitle rn_Product"><?= $orderedFacetDesc ?>: </span>
                    <? foreach ($this->data['facetObject'][$orderedFacetId] as $facet): ?>
                        <? $pos = strpos($facet, ':');?>
                        <? $facetRef = substr($facet, 0, $pos); ?>
                        <? $facetLabel = substr($facet, $pos + 1); ?>
                        <span class="rn_filterChoice" data-id="<?= $facetRef ?>"><?= $facetLabel ?></span>
                        <span class="rn_FacetFilterClearIcon" data-id="<?= $facetRef ?>"></span>
                    <? endforeach; ?>
                    </span>
                <? endif; ?>
            <? endforeach; ?>
            <span class="rn_ResetFilter">
                <button id="rn_<?=$this->instanceID;?>_ResetFilterButton" class="rn_ResetFilterBtn"><?= $this->data['attrs']['label_reset'];?></button>
            </span>
        </div>
    <? endif; ?>
    <rn:block id="bottom"/>
    </div>
</div>