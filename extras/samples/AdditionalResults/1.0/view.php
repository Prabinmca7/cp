<?
/**
 * File: view.php
 * Abstract: Extending PHP view for AdditionalResults widget
 * Version: 1.0
 */
?>
<rn:block id='Multiline-postResultList'>
<? if ($this->data['additionalResults']->RelatedTopics): ?>
<div class="additionalresults">
<? if ($this->data['attrs']['label_heading']): ?>
    <h3><?= $this->data['attrs']['label_heading'] ?></h3>
<? endif; ?>
    <div id="rn_<?= $this->instanceID ?>" class="results">
    <? foreach ($this->data['additionalResults']->RelatedTopics as $topic): ?>
        <? if (!$topic->Result) continue; ?>
        <div class="result">
            <?if($topic->Icon && $topic->Icon->URL):?>
            <div class="icon">
                <img src="<?= $topic->Icon->URL ?>"/>
            </div>
            <?endif;?>
            <div class="content">
                <?= $topic->Result ?>
            </div>
        </div>
    <? endforeach; ?>
    </div>
</div>
<? endif; ?>
</rn:block>
