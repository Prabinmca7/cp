<div id="rn_<?= $this->instanceID ?>" class="<?= $this->classList ?>">
    <h2 class="rn_Content" border = '1px solid #cbcbcb' border-bottom = '1px solid #ccc' font = '"Lucida Sans Unicode","Lucida Grande",Garuda,sans-serif' margin-bottom = '8' text-align = 'center' padding = '10'>Performance Test Widget</h2>
    <table id="rn_<?=$this->instanceID;?>_Grid" class="yui3-datatable-table rn_PerformanceTest_table" >
    <tr>
    <th class = "rn_PerformanceTest_Header">API URL | Method Type | Status Code</th>
    <th class = "rn_PerformanceTest_Header">Time Taken (in secs)</th>
    </tr>
        <? for ($i = 0; $i < count($this->data['js']); $i++):?>
            <? if($this->data['js'][$i]['key']): ?>
                <tr>
                    <td class = 'rn_PerformanceTest_Cell1'><?= $this->data['js'][$i]['key'] ?></td>
                    <td class = 'rn_PerformanceTest_Cell2'><?= $this->data['js'][$i]['value']?></td>
                </tr>
            <? endif; ?>
        <? endfor;?>
    </table>
</div>