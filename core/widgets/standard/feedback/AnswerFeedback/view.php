<div id="rn_<?=$this->instanceID?>" class="<?= $this->classList ?>">
    <rn:block id="top"/>
    <div id="rn_<?=$this->instanceID?>_AnswerFeedbackControl" class="rn_AnswerFeedbackControl">
        <? if ($this->data['attrs']['label_title']): ?>
            <h2 class="rn_Title"><?=$this->data['attrs']['label_title']?></h2>
        <? endif;?>
        <? if ($this->data['js']['buttonView']): ?>
            <?= $this->render('buttonView') ?>
        <? elseif ($this->data['attrs']['use_rank_labels']):?>
            <?= $this->render('rankLabels') ?>
        <? else:?>
            <?= $this->render('ratingMeter') ?>
        <? endif;?>
    </div>
    <rn:block id="bottom"/>
</div>
