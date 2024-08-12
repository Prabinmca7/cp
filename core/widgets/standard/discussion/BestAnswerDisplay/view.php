<div id="rn_<?= $this->instanceID ?>" class="<?= $this->classList ?>">
    <rn:block id="top"/>
    <h2><?= $this->data['attrs']['label_title'] ?></h2>

    <? if ($this->data['bestAnswers']): ?>
    <?= $this->render('BestAnswers', array(
        'bestAnswers' => $this->data['bestAnswers'],
        'question'    => $this->data['question'],
    )) ?>
    <? else: ?>
    <p><?= $this->data['attrs']['label_no_best_answers'] ?></p>
    <? endif; ?>
    <rn:block id="bottom"/>
</div>
