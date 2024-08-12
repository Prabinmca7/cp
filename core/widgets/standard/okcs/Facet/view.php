<div id="rn_<?=$this->instanceID;?>" class="<?=$this->classList;?>">
<? $facetsClass = (isset($this->data['facets']) && is_array($this->data['facets']) && count($this->data['facets']) > 0) ? '' : ' rn_Hidden' ?>
    <div id="rn_<?=$this->instanceID;?>_Content" class="rn_Content<?= $facetsClass ?>">
        <div id="rn_<?=$this->instanceID;?>_Title" class="rn_FacetsTitle"><?=$this->data['attrs']['label_filter'];?>
            <span class="rn_ClearContainer">[<a role="button" class="rn_ClearFacets" href="javascript:void(0)"><?=$this->data['attrs']['label_clear'];?><span class="rn_ScreenReaderOnly"><?= $this->data['attrs']['label_clear_screenreader'] ?></span></a>]</span>
        </div>
        <? if ($this->data['attrs']['enable_multi_select']) : ?>
            <?= $this->render('browse', array('data' => $this->data)) ?>
        <? else : ?>
        <div class="rn_FacetsList">
            <rn:block id="top"/>
            <div id="rn_<?=$this->instanceID;?>_Alert" role="alert" class="rn_ScreenReaderOnly"></div>
            <rn:block id="topContent"/>
            <ul>
            <? if (isset($this->data['facets']) && is_array($this->data['facets']) && count($this->data['facets']) > 0) : ?>
                <? foreach ($this->data['facets'] as $facet): ?>
                    <? if (isset($facet->children) && ($facet->children instanceof Countable || is_array($facet->children)) && count($facet->children)): ?>
                        <li>
                        <? if ($facet->desc): ?>
                            <?=$facet->desc;?><ul>
                        <? endif; ?>
						<? $facetHTML = null; ?>
                            <? $this->findChildren($facet, $facetHTML, $this->data['attrs']['max_sub_facet_size']); ?>
                        <? if ($facet->desc): ?>
                            </ul>
                        <? endif; ?>
                        </li>
                    <? endif; ?>
                <? endforeach; ?>
            <? endif; ?>
            </ul>
        </div>
        <? endif; ?>
    </div>
    <rn:block id="bottom"/>
</div>
