<?php
/**
 * File: view.php
 * Abstract: View file for the DisplayChartReport widget
 * Version: 1.0
 */
?>

<div id="rn_<?=$this->instanceID;?>" class="rn_DisplayChartReport">
    <div class="rn_ChartHeaderText">
        <?= $this->data['attrs']['chart_header'];?>
        <a href="javascript:void(0);" id="rn_<?=$this->instanceID;?>_ResultsLink" class="rn_UpdateButton"><?=$this->data['attrs']['label_result_link'];?></a>
    </div>
    <div id="rn_<?=$this->instanceID;?>_Container" class="rn_ChartContainer"></div>
</div>