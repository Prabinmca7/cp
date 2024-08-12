<rn:block id='Grid-headerData'>
<? if($header['width'] !== null):?>
    <? if($headerNumber === 0):?>
        <th class="yui3-datatable-header" style='width:"<?=$header['width'];?>%"'>
            <span class="rn_ScreenReaderOnly"><?=$this->data['attrs']['label_select_all'];?></span>
            <input <?= count(isset($this->data['tableData']['data']) ? $this->data['tableData']['data'] : []) === 0 ? 'disabled="disabled" ' : '' ?>type="checkbox" value="1"/>
        </th>
    <? else:?>
        <th class="yui3-datatable-header yui3-datatable-sortable-column rn_GridColumn_<?=$header['col_id'];?> rn_HideText" style='width:"<?=$header['width'];?>%"'>
            <?=$header['heading'];?>
        </th>
    <? endif;?>
<? else:?>
    <? if($headerNumber === 0):?>
        <th class="yui3-datatable-header">
            <span class="rn_ScreenReaderOnly"><?=$this->data['attrs']['label_select_all'];?></span>
            <input <?= count(isset($this->data['tableData']['data']) ? $this->data['tableData']['data'] : []) === 0 ? 'disabled="disabled" ' : '' ?>type="checkbox" value="1"/>
        </th>
    <? else:?>
        <th class="yui3-datatable-header yui3-datatable-sortable-column rn_GridColumn_<?=$header['col_id'];?> rn_HideText">
            <?=$header['heading'];?>
        </th>
    <? endif;?>
<? endif;?>
</rn:block>

<rn:block id='Grid-columnData'>
    <div id="rn_<?=$this->instanceID;?>_<?= 'r' . $i . 'c' . $j;?>">
        <? if($j === 0):?>
            <input type="checkbox" aria-labelledby="rn_<?=$this->instanceID;?>_<?= 'r' . $i . 'c' . $this->data['attrs']['primary_info_column_index'];?>" value="<?=($this->data['tableData']['data'][$i][$j])?>"/>
        <? elseif($j === $this->data['attrs']['avatar_column_index']):?>
            <div class="rn_Author">
                <rn:widget path="user/AvatarDisplay" user_id="#rn:php:$this->data['tableData']['data'][$i][0]#" avatar_size="small">
            </div>
        <? else:?>
            <?=($this->data['tableData']['data'][$i][$j] !== '' && $this->data['tableData']['data'][$i][$j] !== null && $this->data['tableData']['data'][$i][$j] !== false) ? $this->data['tableData']['data'][$i][$j] : '&nbsp;' ?>
        <? endif;?>
    </div>
</rn:block>
