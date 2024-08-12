<div id="rn_<?=$this->instanceID;?>" class="<?= $this->classList ?>">
    <rn:block id="top"/>
    <?
         /* Dialog Content */
         $i = 1;
         $id = "rn_{$this->instanceID}_{$this->data['attrs']['filter_type']}";
     ?>
     <rn:block id="preDialog"/>
     <form id="<?=$id;?>_Level1Input" class="rn_Hidden rn_Input rn_MobileProductCategorySearchFilter rn_Level1" onsubmit="return false;">
         <rn:block id="dialogTop"/>
    <? foreach ($this->data['firstLevel'] as $item): ?>
        <div class="rn_Parent <?=(isset($item['selected']) && $item['selected']) ? 'rn_Selected' : '';?>">
            <input type="radio" name="<?=$id;?>_Level1" id="<?=$id;?>_Input1_<?=$i?>" value="<?=$item['id'];?>"/>
            <? $class = (isset($item['hasChildren']) && $item['hasChildren']) ? 'rn_HasChildren' : '';?>
            <label class="<?=$class;?>" id="<?=$id;?>_Label1_<?=$i?>" for="<?=$id;?>_Input1_<?=$i;?>"><?=htmlspecialchars($item['label'], ENT_QUOTES, 'UTF-8');?>
            <? if (isset($item['hasChildren']) && $item['hasChildren']): ?><span class="rn_ParentMenuAlt"> <?=$this->data['attrs']['label_parent_menu_alt']?></span><? endif; ?>
            </label>
        </div>
    <? $i++;
       endforeach; ?>
       <rn:block id="dialogBottom"/>
    </form>
    <rn:block id="postDialog"/>
    <?
         /* Filters displayed on the page */
         $filtersToDisplay = isset($this->data['js']['initial']) ? count($this->data['js']['initial']) : 0;
    ?>
    <rn:block id="preDisplayedFilters"/>
    <div id="<?=$id;?>_FilterDisplay" class="rn_FilterDisplay">
        <rn:block id="preHeading"/>
        <div class="rn_Heading">
            <rn:block id="preLaunchLink"/>
            <a id="<?=$id;?>_Launch" href="javascript:void(0);" class="rn_Opener rn_LinkOpener"><?=$this->data['attrs']['label_input'];?></a>
            <rn:block id="postLaunchLink"/>
            <rn:block id="preFilterRemove"/>
            <a href="javascript:void(0);" id="<?=$id;?>_FilterRemove" class="rn_Remove <?=($filtersToDisplay) ? '' : 'rn_Hidden';?>">
                <?=$this->data['attrs']['label_filter_remove'];?>
            </a>
            <rn:block id="postFilterRemove"/>
        </div>
        <rn:block id="postHeading"/>
        <rn:block id="preFilters"/>
        <div id="<?=$id;?>_Filters" class="rn_Filters">
        <? if ($filtersToDisplay): ?>
        <? foreach ($this->data['js']['initial'] as $index => $value): ?>
            <? $class = ($index === count($this->data['js']['initial']) - 1) ? 'rn_Selected' : '';?>
            <rn:block id="preFilter"/>
            <a href="<?=(!$class) ? $this->data['js']['searchPage'] . $this->data['js']['searchName'] . '/' . $value['id'] : 'javascript:void(0);'?>" class="rn_FilterItem <?=$class;?>" id="<?=$id;?>_Filter<?=$value['id']?>"><?=htmlspecialchars($value['label'], ENT_QUOTES, 'UTF-8');?></a>
            <rn:block id="postFilter"/>
        <? endforeach; ?>
        <? endif; ?>
        </div>
        <rn:block id="postFilters"/>
    </div>
    <rn:block id="postDisplayedFilters"/>
    <rn:block id="bottom"/>
</div>
