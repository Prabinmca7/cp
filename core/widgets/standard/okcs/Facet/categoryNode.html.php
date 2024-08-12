<? if (count($children) > 0): ?>
<ul class = "rn_CategoryExplorerList">
    <? $index = 0; $isMoreDisplayed = false;?>
    <? foreach ($children as $key => $categoryValue):?>
        <? if (!$isMoreDisplayed && $index >= $this->data['attrs']['max_sub_facet_size']): ?>
            <li class= "rn_CategoryExplorerItem">
                <div class="rn_CategoryExplorerLeaf"></div>
                <a role="button" class="rn_CategoryExplorerCollapsedHidden" href="javascript:void(0)">
                <span class="rn_ScreenReaderOnly"><?=$this->data['attrs']['label_expand_icon']?></span></a>
                <a class="rn_LeafNode rn_CategoryExplorerLink" data-id="MoreChildLevels" data-key="<?=$parentKey?>/<?=$categoryValue->referenceKey;?>/<?=$index?>" data-type="<?=$categoryValue->type;?>" data-depth="<?=$categoryValue->depth;?>" href="javascript:void(0)"><?=$this->data['attrs']['label_more']?></a>
            </li>
            <? $isMoreDisplayed = true; ?>
        <? elseif (!$isMoreDisplayed && !$categoryValue->hasChildren): ?>
            <li class= "rn_CategoryExplorerItem">
                <div class="rn_CategoryExplorerLeaf"></div>
                <a role="button" id="rn_<?=$this->instanceID;?>_<?=$categoryValue->referenceKey;?>_Collapsed" class="rn_CategoryExplorerCollapsedHidden" href="javascript:void(0)"><span class="rn_ScreenReaderOnly"><?=$this->data['attrs']['label_expand_icon']?></span></a>
                <a class="rn_LeafNode <?=$categoryValue->selectedClass;?>" id="rn_<?=$this->instanceID;?>_<?=$categoryValue->referenceKey;?>" data-id="<?=$parentKey?>.<?=$categoryValue->referenceKey;?>" data-type="<?=$categoryValue->type;?>" data-depth="<?=$categoryValue->depth;?>" title="" href="javascript:void(0)"><?=$categoryValue->name;?>
                <? if(strpos($categoryValue->selectedClass, 'rn_ActiveFacet') !== false): ?>
                    <span class='rn_ScreenReaderOnly'><?= $this->data['attrs']['label_active_filter_screenreader']?></span>
                    <span class='rn_FacetClearIcon'></span>
                <? endif; ?>
                </a>
            </li>
        <? elseif (!$isMoreDisplayed): ?>
            <li class="rn_CategoryExplorerItem">
                <a role="button" id="rn_<?=$this->instanceID;?>_<?=$categoryValue->referenceKey;?>_Expanded" class="rn_CategoryExplorerExpandedHidden" href="javascript:void(0)"><span class="rn_ScreenReaderOnly"><?=$this->data['attrs']['label_expand_icon']?></span></a>
                <a role="button" id="rn_<?=$this->instanceID;?>_<?=$categoryValue->referenceKey;?>_Collapsed" class="rn_CategoryExplorerCollapsed" data-id="<?=$parentKey?>.<?=$categoryValue->referenceKey;?>" href="javascript:void(0)"><span class="rn_ScreenReaderOnly"><?=$this->data['attrs']['label_collapse_icon']?></span></a>
                <a class="<?=$categoryValue->selectedClass;?>" id="rn_<?=$this->instanceID;?>_<?=$categoryValue->referenceKey;?>" data-id="<?=$parentKey?>.<?=$categoryValue->referenceKey;?>" data-type="<?=$categoryValue->type;?>" data-depth="<?=$categoryValue->depth;?>" title="" href="javascript:void(0)"><?=$categoryValue->name;?>
                <? if(strpos($categoryValue->selectedClass, 'rn_ActiveFacet') !== false): ?>
                    <span class='rn_ScreenReaderOnly'><?= $this->data['attrs']['label_active_filter_screenreader']?></span>
                    <span class='rn_FacetClearIcon'></span>
                <? endif; ?>
                </a>
            </li>
        <? endif; ?>
        <? ++$index; ?>
    <? endforeach; ?>
</ul>
<? endif; ?>