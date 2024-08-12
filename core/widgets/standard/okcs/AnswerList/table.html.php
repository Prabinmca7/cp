<table id="rn_<?=$this->instanceID;?>_Grid" class="yui3-datatable-table" role="grid">
    <caption class="yui3-datatable-caption <?=$this->data['js']['showHeaders'] ? '' : 'rn_ScreenReaderOnly';?>"><?= ($this->data['attrs']['type'] === 'browse' && $this->data['attrs']['label_browse_list_title'] === '') ? $this->data['attrs']['label_table_title'] : $this->data['attrs']['label_' . $this->data['attrs']['type'] . '_list_title'] ?></caption>
    <thead class="yui3-datatable-columns <?=$attrs['show_headers'] ? '' : 'rn_ScreenReaderOnly';?>">
        <tr>
            <? for ($i = 0; $i < count($fields); $i++):?>
                    <th id="rn_<?=$this->instanceID;?>_Header_<?= $i ?>" data-yui3-col-id="c<?= $i ?>" scope="col" class="yui3-datatable-header <?=$attrs['type'] === 'browse' ? 'yui3-datatable-sortable-column' : '' ?>">
                        <? if ($attrs['type'] === 'browse'): ?>
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
        <tr role="row" class="<?= ($i % 2 === 0) ? 'yui3-datatable-even' : 'yui3-datatable-odd' ?>">
            <? for ($j = 0; $j < count($fields); $j++):?>
                    <td role="gridcell" class="yui3-datatable-cell" headers="rn_<?=$this->instanceID;?>_Header_<?= $j ?>">
                        <? if ($fields[$j]['name'] === 'title'): ?>
                            <?
                                $href = $url . '/a_id/' . $data[$i]['answerId'] . '/accessType/' . $attrs['access_type'];
                                if(!$data[$i]['published']) {
                                    $href .= '/answer_data/' . $data[$i]['encryptedUrl'];
                                }
                            ?>
                            <a href="<?= $href . \RightNow\Utils\Url::sessionParameter(); ?>" target="<?= $attrs['target'] ?>"><?= $data[$i]['title'] ?></a>
                        <? else: ?>
                            <?= $data[$i][$fields[$j]['name']] ?>
                        <? endif; ?>
                    </td>
            <? endfor;?>
        </tr>
        <? endfor;?>
    </tbody>
</table>
