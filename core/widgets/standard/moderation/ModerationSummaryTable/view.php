<div id="rn_<?=$this->instanceID;?>" class="<?= $this->classList ?>">
    <rn:block id="top"/>
    <div id="rn_<?=$this->instanceID;?>_Content">
        <rn:block id="topContent"/>
        <table id="rn_<?=$this->instanceID;?>_ModerationSummaryTable">
            <caption><?=$this->data['attrs']['label_caption']?></caption>
        <? if($this->data['attrs']['headers']):?>
            <thead>
                <rn:block id="topHeader"/>
                <tr>
                <? foreach($this->data['tableData']['headers'] as $column => $header):?>
                    <rn:block id="headerData">
                        <th <?= ($column !== 0) ? 'scope="col"' : '' ?>><?=$header['heading'];?></th>
                    </rn:block>
                <? endforeach;?>
                </tr>
                <rn:block id="bottomHeader"/>
            </thead>
        <? endif;?>
        <? if(count($this->data['tableData']['data']) > 0): ?>
            <tbody>
                <rn:block id="topBody"/>
            <? for($i = 0; $i < count($this->data['tableData']['data']); $i++): ?>
                <rn:block id="preBodyRow"/>
                <tr>
                <? for($j = 0; $j < count($this->data['tableData']['headers']); $j++):?>
                    <?= ($j === 0) ? '<th scope="row">' : '<td>';?>
                        <rn:block id="columnData">
                            <? if(is_array($this->data['tableData']['data'][$i][$j]) && $this->data['tableData']['data'][$i][$j]): ?>
                                <? if (isset($this->data['tableData']['data'][$i][$j]['link']) && $this->data['tableData']['data'][$i][$j]['link']): ?><a href="<?=$this->data['tableData']['data'][$i][$j]['link'];?>" target="<?=$this->data['attrs']['moderation_url_target_type']?>"><? endif;?>
                                <?=($this->data['tableData']['data'][$i][$j]['value'] !== '' && $this->data['tableData']['data'][$i][$j]['value'] !== null && $this->data['tableData']['data'][$i][$j]['value'] !== false) ? $this->data['tableData']['data'][$i][$j]['value'] : '&nbsp;' ?>
                                <? if (isset($this->data['tableData']['data'][$i][$j]['link']) && $this->data['tableData']['data'][$i][$j]['link']): ?></a><? endif;?>
                            <? else:?>
                                <?=($this->data['tableData']['data'][$i][$j] !== '' && $this->data['tableData']['data'][$i][$j] !== null && $this->data['tableData']['data'][$i][$j] !== false) ? $this->data['tableData']['data'][$i][$j] : '&nbsp;' ?>
                            <? endif;?>
                        </rn:block>
                    <?= ($j === 0) ? '</th>' : '</td>';?>
                <? endfor;?>
                </tr>
                <rn:block id="postBodyRow"/>
            <? endfor;?>
                <rn:block id="bottomBody"/>
            </tbody>
        <? endif;?>
        </table>
        <div class="rn_SummaryNote"><?= $this->data['noteLabels'][$this->data['attrs']['date_filter_options']]; ?></div>
        <rn:block id="bottomContent"/>
    </div>
    <rn:block id="bottom"/>
</div>