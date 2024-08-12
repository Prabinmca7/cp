<? if (count($children) > 0): ?>
<ul class = "rn_CategoryExplorerList">
    <? foreach ($children as $key => $categoryValue):?>
        <? if (!$categoryValue->hasChildren ): ?>
            <li class= "rn_CategoryExplorerItem">
                <div class="rn_CategoryExplorerLeaf"></div>
                <a role="button" id="rn_<?=$this->instanceID;?>_<?=$categoryValue->referenceKey;?>_Collapsed" class="rn_CategoryExplorerCollapsedHidden" href="javascript:void(0)"><span class="rn_ScreenReaderOnly"><?=$this->data['attrs']['label_expand_icon']?></span></a>
                <a class="rn_LeafNode <?=$categoryValue->selectedClass;?>" id="rn_<?=$this->instanceID;?>_<?=$categoryValue->referenceKey;?>" data-id="<?=$categoryValue->referenceKey;?>" data-type="<?=$categoryValue->type;?>" data-depth="<?=$categoryValue->depth;?>" title="" href="javascript:void(0)"><?=$categoryValue->name;?></a>
            </li>
        <? else: ?>
            <li class="rn_CategoryExplorerItem">
                <a role="button" id="rn_<?=$this->instanceID;?>_<?=$categoryValue->referenceKey;?>_Expanded" class="rn_CategoryExplorerExpandedHidden" href="javascript:void(0)"><span class="rn_ScreenReaderOnly"><?=$this->data['attrs']['label_expand_icon']?></span></a>
                <a role="button" id="rn_<?=$this->instanceID;?>_<?=$categoryValue->referenceKey;?>_Collapsed" class="rn_CategoryExplorerCollapsed" href="javascript:void(0)"><span class="rn_ScreenReaderOnly"><?=$this->data['attrs']['label_collapse_icon']?></span></a>
                <a class="<?=$categoryValue->selectedClass;?>" id="rn_<?=$this->instanceID;?>_<?=$categoryValue->referenceKey;?>" data-id="<?=$categoryValue->referenceKey;?>" data-type="<?=$categoryValue->type;?>" data-depth="<?=$categoryValue->depth;?>" title="" href="javascript:void(0)"><?=$categoryValue->name;?></a>
            </li>
        <? endif; ?>
    <? endforeach; ?>
</ul>
<? endif; ?>