<ul class="rn_BestAnswerList" aria-live="polite">
<? foreach ($bestAnswers as $bestAnswer): ?>
<?= $this->render('BestAnswer', array(
    'bestAnswer' => $bestAnswer,
    'question' => $question,
)) ?>
<? endforeach; ?>
</ul>
