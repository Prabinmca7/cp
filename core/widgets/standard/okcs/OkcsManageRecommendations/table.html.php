<table id="rn_<?=$this->instanceID;?>_Grid" class="yui3-datatable-table" role="grid">
    <caption class="yui3-datatable-caption rn_ScreenReaderOnly"><?= $this->data['attrs']['label_screen_reader_table_title'] ?></caption>
    <thead class="yui3-datatable-columns <?=$attrs['show_headers'] ? '' : 'rn_ScreenReaderOnly';?>">
        <tr>
            <? for ($i = 0; $i < count($fields); $i++):?>
                    <th id="rn_<?=$this->instanceID;?>_Header_<?= $i ?>" data-yui3-col-id="c<?= $i ?>" scope="col" class="yui3-datatable-header <?=(isset($attrs['type']) ? $attrs['type'] : '') === 'browse' ? 'yui3-datatable-sortable-column' : '' ?>">
                        <? if ((isset($attrs['type']) ? $attrs['type'] : '') === 'browse'): ?>
                            <span tabindex="<?= $i ?>" class="yui3-datatable-sort-liner"><?= $fields[$i]['label'] ?></span>
                        <? else: ?>
                            <?= $fields[$i]['label'] ?>
                        <? endif; ?>
                    </th>
            <? endfor;?>
        </tr>
    </thead>
    <tbody class="yui3-datatable-data">
        <? for ($i = 0; $i < count($data); $i++):?>
        <tr role="row" class='yui3-datatable-cell'>
            <? for ($j = 0; $j < count($fields); $j++):?>
                    <td role="gridcell" class="yui3-datatable-cell" headers="rn_<?=$this->instanceID;?>_Header_<?= $j ?>">
                        <? if ($fields[$j]['name'] === 'title'): ?>
                            <a href="javascript:void(0)" class="rn_RecommendationLink" id="<?=$data[$i]['recordID'];?>" title="<?= $data[$i]['title'] ?>"><?= $data[$i]['title'] ?></a>
                        <? else: ?>
                            <?= $data[$i][$fields[$j]['name']] ?>
                        <? endif; ?>
                    </td>
            <? endfor;?>
        </tr>
        <? endfor;?>
    </tbody>
</table>