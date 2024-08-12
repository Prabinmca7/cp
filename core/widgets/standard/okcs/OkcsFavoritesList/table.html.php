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
                    
                    <th id="rn_<?=$this->instanceID;?>_<?= $data['fields'][$i]['name'] ?>" class="<?= $headerClass ?>" aria-labelledby="rn_<?=$this->instanceID;?>_<?= $data['fields'][$i]['name'] ?>" tabindex="0" scope="col">
                        <?= $data['fields'][$i]['label'] ?>
                        
                    </th>
                </rn:block>
            <? endfor;?>
            </tr>
            <rn:block id="bottomHeader"/>
        </thead>
        <? if(isset($data['favoritesList']) && is_array($data['favoritesList']) && count($data['favoritesList']) > 0): ?>
        <tbody id="rn_<?=$this->instanceID;?>_Body" class="yui3-datatable-data">
        <rn:block id="topBody"/>
            <? for ($i = 0; $i < count($data['favoritesList']); $i++):?>
                <rn:block id="preBodyRow"/>
                <tr id="rn_<?=$this->instanceID;?>_<?= $data['favoritesList'][$i]['answerId'] ?>" role="row" class="<?= ($i % 2 === 0) ? 'yui3-datatable-even' : 'yui3-datatable-odd' ?>">
                    <? for ($j = 0; $j < count($data['fields']); $j++):?>
                        <td role="gridcell" class="yui3-datatable-cell" headers="rn_<?=$this->instanceID;?>_<?= $data['fields'][$j]['name'] ?>">
                            <? if($this->data['fields'][$j]['name'] === 'title'): ?>
                                <a href="<?= $this->data['answerUrl'] ?><?= $this->data['favoritesList'][$i]['answerId']?>" title="<?= $this->data['favoritesList'][$i][$this->data['fields'][$j]['name']] ?>" target="<?= $this->data['attrs']['target'] ?>" ><?= $this->data['favoritesList'][$i][$this->data['fields'][$j]['name']] ?></a>
                            <? else: ?>
                                <?= $this->data['favoritesList'][$i][$this->data['fields'][$j]['name']] ?>
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
                <td colspan="<?=count($data['fields']);?>" class="yui3-datatable-message-content"><?=$data['attrs']['label_no_favorites_table']?></td>
            </tr>
        </tbody>
        <? endif;?>
    </table>
    <div id="rn_<?= $this->instanceID ?>_PaginateDiv" class="rn_PaginateDiv">
        <? if(isset($this->data['hasMore']) && $this->data['hasMore'] && $this->data['attrs']['rows_to_display'] > 0): ?>
        <ul>
            <li>
                <a class="rn_NextPage" data-rel="next" rel="next">
                    <span><?= $this->data['attrs']['label_forward'] ?></span>
                </a>
            </li>
        </ul>
        <? endif; ?>
    </div>
    </div>
    <rn:block id="bottomContent"/>