<?
/**
 * File: view.php
 * Abstract: PHP view for FilteredTopAnswers widget
 * Version: 1.0
 */
?>
<div id="rn_<?=$this->instanceID;?>" class="<?= $this->classList ?>">
    <rn:block id="top"/>
<?if(is_array($this->data['results']['data']) && count($this->data['results']['data']) > 0): ?>
    <ul>
    <?foreach ($this->data['results']['data'] as $reportRow): ?>
        <rn:block id="resultItem">
            <li><?=$reportRow[0];?></li>
        </rn:block>
    <?endforeach;?>
    </ul>
<?endif;?>
    <rn:block id="bottom"/>
</div>