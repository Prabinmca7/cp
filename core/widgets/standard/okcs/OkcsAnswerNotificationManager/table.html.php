<rn:block id="preLoadingIndicator"/>
<div id="rn_<?=$this->instanceID;?>_Loading"></div>
<rn:block id="postLoadingIndicator"/>
<div id="rn_<?=$this->instanceID;?>_Content" class="yui3-skin-sam">
    <table id="rn_<?=$this->instanceID;?>_Grid" class="yui3-datatable-table" role="grid">
    <caption><?=$data['attrs']['label_caption']?></caption>
        <thead class="yui3-datatable-columns">
        <rn:block id="topHeader"/>
            <tr>
             <? for ($i = 0; $i < count($data['fields']); $i++):?>
                <rn:block id="headerData">
                    <? $headerClass = 'yui3-datatable-header rn_GridColumn_' . ($i + 1); ?>
                    <? if(($data['fields'][$i]['name'] === 'documentId' || $data['fields'][$i]['name'] === 'expires') && (count($data['subscriptionList']) > 1)): ?>
                        <? $headerClass .= ' yui3-datatable-sortable-column'; ?>
                    <? endif;?>
                    <th id="rn_<?=$this->instanceID;?>_<?= $data['fields'][$i]['name'] ?>" class="<?= $headerClass ?>" aria-labelledby="rn_<?=$this->instanceID;?>_<?= $data['fields'][$i]['name'] ?>" tabindex="0" scope="col">
                        <?= $data['fields'][$i]['label'] ?>
                        <? if(($data['fields'][$i]['name'] === 'documentId' || $data['fields'][$i]['name'] === 'expires') && (count($data['subscriptionList']) > 1)): ?>
                            <span class="yui3-datatable-sort-indicator"></span>
                            <span class="rn_ScreenReaderOnly"><?= $data['clickToSortMsg'] ?></span>
                        <? endif;?>
                    </th>
                </rn:block>
            <? endfor;?>
            </tr>
            <rn:block id="bottomHeader"/>
        </thead>
        <? if(count($data['subscriptionList']) > 0): ?>
        <tbody id="rn_<?=$this->instanceID;?>_Body" class="yui3-datatable-data">
        <rn:block id="topBody"/>
            <? for ($i = 0; $i < count($data['subscriptionList']); $i++):?>
                <rn:block id="preBodyRow"/>
                <tr id="rn_<?=$this->instanceID;?>_<?= $data['subscriptionList'][$i]['subscriptionID'] ?>" role="row" class="<?= ($i % 2 === 0) ? 'yui3-datatable-even' : 'yui3-datatable-odd' ?>">
                    <? for ($j = 0; $j < count($data['fields']); $j++):?>
                        <td role="gridcell" class="yui3-datatable-cell" headers="rn_<?=$this->instanceID;?>_<?= $data['fields'][$j]['name'] ?>">
                            <? if($this->data['fields'][$j]['name'] === 'title'): ?>
                                <a href="<?= $this->data['answerUrl'] ?><?= $this->data['subscriptionList'][$i]['answerId']?>" target="<?= $this->data['attrs']['target'] ?>" ><?= $this->data['subscriptionList'][$i][$this->data['fields'][$j]['name']] ?></a>
                            <? else: ?>
                                <?= $this->data['subscriptionList'][$i][$this->data['fields'][$j]['name']] ?>
                            <? endif;?>
                        </td>
                    <? endfor;?>
                </tr>
                <rn:block id="postBodyRow"/>
            <? endfor;?>
            <rn:block id="bottomBody"/>
        </tbody>
        <? else: ?>
        <tbody class="yui3-datatable-message">
            <tr>
                <td colspan="<?=count($data['fields']);?>" class="yui3-datatable-message-content"><?=$data['attrs']['label_no_subscription']?></td>
            </tr>
        </tbody>
        <? endif;?>
    </table>
    </div>
    <rn:block id="bottomContent"/>