<div id="rn_<?= $this->instanceID ?>" class="<?= $this->classList ?>">
    <rn:block id="top"/>
    <? if (count($this->data['js']['questions']) > 0): ?>
        <? foreach ($this->data['js']['questions'] as $question): ?>
            <div class="rn_RecentlyAskedQuestionsItem">
                <rn:block id="preQuestion"/>
                <?= $this->render('Question', array(
                    'question' => $question,
                    'link'     => $this->helper->questionLink($question, $this->data['attrs']['question_detail_url']),
                    'excerpt'  => $this->data['attrs']['show_excerpt'] ? \RightNow\Utils\Text::truncateText(\RightNow\Libraries\Formatter::formatTextEntry($question->Body, $question->BodyContentType->LookupName, false), $this->data['attrs']['excerpt_max_length'], true) : null
                )) ?>
                <rn:block id="postQuestion"/>
            </div>
        <? endforeach; ?>
    <? else: ?>
        <div class="rn_NoQuestions"><?=$this->data['attrs']['label_no_questions']?></div>
    <? endif; ?>
    <rn:block id="bottom"/>
</div>
