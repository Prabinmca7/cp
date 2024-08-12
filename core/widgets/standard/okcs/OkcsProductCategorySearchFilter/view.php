<div id="rn_<?=$this->instanceID;?>" class="<?= $this->classList?>">
<? if ($this->data['attrs']['view_type'] === 'explorer'): ?>
    <div class="rn_CategoryExplorer">
        <div class="rn_CategoryExplorerContent">
            <? if ($this->data['attrs']['show_headers']): ?>
                <div class="rn_CategoryExplorerTitleDiv"><?= $this->data['attrs']['label_input'] ?></div>
            <? endif; ?>
            <rn:block id="preExplorerTree"/>
            <div id="rn_<?= $this->instanceID ?>_Tree" class="rn_CategoryExplorerContentDiv">
                <? if (isset($this->data['results'])): ?>
                 <? if (isset($this->data['results']->items)): ?>
                        <? $parentCount = count($this->data['results']->items); ?>
                    <? endif; ?>
                    <? $categoryFound = false; ?>
                    <ul class = "rn_CategoryExplorerList">
                        <? foreach ($this->data['results']->items as $categoryValue):?>
                            <? if ($categoryValue->referenceKey): ?>
                                <? $categoryFound = true; ?>
                                <? if (!$categoryValue->hasChildren ): ?>
                                    <li class= "rn_CategoryExplorerItem">
                                        <div class="rn_CategoryExplorerLeaf"></div>
                                        <a role="button" id="rn_<?=$this->instanceID;?>_<?=$categoryValue->referenceKey;?>_Collapsed" class="rn_CategoryExplorerCollapsedHidden" href="javascript:void(0)" title="<?=$this->data['attrs']['label_expand_icon']?>"></a>
                                        <a class="rn_LeafNode rn_CategoryExplorerLink" id="rn_<?=$this->instanceID;?>_<?=$categoryValue->referenceKey;?>" data-id="<?=$categoryValue->referenceKey;?>" data-type="<?=$categoryValue->type;?>" data-depth="<?=$categoryValue->depth;?>" title="" href="javascript:void(0)"><?=$categoryValue->name;?></a>
                                    </li>
                                <? else: ?>
                                    <li class="rn_CategoryExplorerItem">
                                        <? $expandedSuffix = ($parentCount === 1) ? '' : 'Hidden';?>
                                        <? $collapsedSuffix = ($parentCount === 1) ? 'Hidden' : '';?>
                                        <a role="button" id="rn_<?=$this->instanceID;?>_<?=$categoryValue->referenceKey;?>_Expanded" class="rn_CategoryExplorerExpanded<?= $expandedSuffix;?>" href="javascript:void(0)" title="<?=$this->data['attrs']['label_expand_icon']?>"></a>
                                        <a role="button" id="rn_<?=$this->instanceID;?>_<?=$categoryValue->referenceKey;?>_Collapsed" class="rn_CategoryExplorerCollapsed<?= $collapsedSuffix;?>" href="javascript:void(0)" title="<?=$this->data['attrs']['label_collapse_icon']?>"></a>
                                        <a class="rn_CategoryExplorerLink" id="rn_<?=$this->instanceID;?>_<?=$categoryValue->referenceKey;?>" data-id="<?=$categoryValue->referenceKey;?>" data-type="<?=$categoryValue->type;?>" data-depth="<?=$categoryValue->depth;?>" title="" href="javascript:void(0)"><?=$categoryValue->name;?></a>
                                        <? if($parentCount === 1) : ?>
                                            <?= $this->render('categoryNode', array('children' => $categoryValue->children)) ?>
                                        <? endif; ?>
                                    </li>
                                <? endif; ?>
                            <? endif; ?>
                        <? endforeach; ?>
                        <? if(!$categoryFound): ?>
                            <div class="rn_NoCategoriesMsg"><?= $this->data['js']['noDataFoundMessage'] ?></div>
                        <? endif; ?>
                    </ul>
                <? else: ?>
                    <div class="rn_NoCategoriesMsg"><?= $this->data['js']['noDataFoundMessage'] ?></div>
                <? endif; ?>
                </div>
        </div>
    </div>
    <? if($this->data['results']->hasMore): ?>
        <a href="javascript:void(0)" class="rn_GetMoreProdCatLink"><?=$this->data['hasMoreMsg']?></a>
    <? endif; ?>
<? else: ?>
    <rn:block id="preLink"/>
   <a role="button" href="javascript:void(0);" class="rn_ScreenReaderOnly" id="rn_<?= $this->instanceID ?>_LinksTrigger"><? printf($this->data['attrs']['label_screen_reader_accessible_option'], $this->data['attrs']['label_input']) ?>&nbsp;<span id="rn_<?= $this->instanceID ?>_TreeDescription"></span></a>
   <rn:block id="postLink"/>
   <? if ($this->data['attrs']['label_input']): ?>
   <rn:block id="preLabel"/>
   <span class="rn_Label"><?= $this->data['attrs']['label_input'] ?></span>
   <rn:block id="postLabel"/>
   <? endif; ?>
   <rn:block id="preButton"/>
   <button type="button" id="rn_<?= $this->instanceID ?>_<?= $this->data['attrs']['filter_type'] ?>_Button" class="rn_DisplayButton"><span class="rn_ScreenReaderOnly"><?= $this->data['attrs']['label_accessible_interface'] ?></span> <span id="rn_<?= $this->instanceID ?>_ButtonVisibleText"><?= $this->data['attrs']['label_nothing_selected'] ?></span></button>
   <rn:block id="postButton"/>
   <div class="rn_ProductCategoryLinks rn_Hidden" id="rn_<?= $this->instanceID ?>_Links"></div>
   <div id="rn_<?= $this->instanceID ?>_TreeContainer" class="rn_PanelContainer rn_Hidden">
       <rn:block id="preTree"/>
       <div id="rn_<?= $this->instanceID ?>_Tree" class="rn_Panel"><? /* Product / Category Tree goes here */?></div>
       <rn:block id="postTree"/>
   </div>
<? endif; ?>
<rn:block id="bottom"/>
</div>
