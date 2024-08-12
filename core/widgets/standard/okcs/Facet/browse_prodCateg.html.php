<? if (count($data) > 0): ?>
    <? $parentCount = count($data);$index = 0; $isMoreDisplayed = false;?>
    <? $categoryFound = false; ?>
    <ul class = "rn_CategoryExplorerList">
        <? foreach ($data as $key => $categoryValue): ?>
            <? if ($categoryValue->referenceKey): ?>
                <? $categoryFound = true; ?>
                <? if (!$isMoreDisplayed && $index >= $size): ?>
                    <li class= "rn_CategoryExplorerItem">
                        <div class="rn_CategoryExplorerLeaf"></div>
                        <a role="button" class="rn_CategoryExplorerCollapsedHidden" href="javascript:void(0)">
                        <span class="rn_ScreenReaderOnly"><?=$attrs['label_expand_icon']?></span></a>
                        <a class="rn_LeafNode rn_CategoryExplorerLink" data-id="MoreTopLevels" data-key="<?=$categoryValue->referenceKey?>/<?=$index?>" data-type="<?=$categoryValue->type;?>" data-depth="<?=$categoryValue->depth;?>" href="javascript:void(0)"><?=$attrs['label_more']?></a>
                    </li>
                    <? $isMoreDisplayed = true; ?>
                <? elseif (!$isMoreDisplayed && !$categoryValue->hasChildren): ?>
                <li class= "rn_CategoryExplorerItem">
                    <div class="rn_CategoryExplorerLeaf"></div>
                    <a role="button" id="rn_<?=$this->instanceID;?>_<?=$categoryValue->referenceKey;?>_Collapsed" class="rn_CategoryExplorerCollapsedHidden" href="javascript:void(0)" title="<?=$attrs['label_expand_icon']?>"></a>
                    <a class="rn_LeafNode rn_CategoryExplorerLink <?=isset($categoryValue->selectedClass) ? $categoryValue->selectedClass : '' ?>" id="rn_<?=$this->instanceID;?>_<?=$categoryValue->referenceKey;?>" data-id="<?=$categoryValue->referenceKey;?>" data-type="<?=$categoryValue->type;?>" data-depth="<?=$categoryValue->depth;?>" title="" href="javascript:void(0)"><?=$categoryValue->name;?>
                    <? if(isset($categoryValue->selectedClass) && strpos($categoryValue->selectedClass, 'rn_ActiveFacet') !== false): ?>
                        <span class='rn_ScreenReaderOnly'><?= $this->data['attrs']['label_active_filter_screenreader']?></span>
                        <span class='rn_FacetClearIcon'></span>
                    <? endif; ?>
                    </a>
                </li>
                <? elseif (!$isMoreDisplayed): ?>
                    <li class="rn_CategoryExplorerItem">
                        <? $expandedSuffix = '';?>
                        <? $collapsedSuffix = 'Hidden';?>
                        <a role="button" id="rn_<?=$this->instanceID;?>_<?=$categoryValue->referenceKey;?>_Expanded" class="rn_CategoryExplorerExpanded<?= $expandedSuffix;?>" href="javascript:void(0)" title="<?=$attrs['label_expand_icon']?>"></a>
                        <a role="button" id="rn_<?=$this->instanceID;?>_<?=$categoryValue->referenceKey;?>_Collapsed" class="rn_CategoryExplorerCollapsed<?= $collapsedSuffix;?>" href="javascript:void(0)" title="<?=$attrs['label_collapse_icon']?>"></a>
                        <a class="<?=$categoryValue->selectedClass ?>" id="rn_<?=$this->instanceID;?>_<?=$categoryValue->referenceKey;?>" data-id="<?=$categoryValue->referenceKey;?>" data-type="<?=$categoryValue->type;?>" data-depth="<?=$categoryValue->depth;?>" title="" href="javascript:void(0)"><?=$categoryValue->name;?>
                        <? if(strpos($categoryValue->selectedClass, 'rn_ActiveFacet') !== false): ?>
                            <span class='rn_ScreenReaderOnly'><?= $this->data['attrs']['label_active_filter_screenreader']?></span>
                            <span class='rn_FacetClearIcon'></span>
                        <? endif; ?>
                        </a>
                        <?= $this->render('categoryNode', array('children' => $categoryValue->children, 'parentKey' => $categoryValue->referenceKey)) ?>
                    </li>
                <? endif; ?>
            <? endif; ?>
            <? ++$index; ?>
        <? endforeach; ?>
    </ul>
<? endif; ?>