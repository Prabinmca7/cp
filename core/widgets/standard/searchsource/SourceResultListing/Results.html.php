<rn:block id="preList"/>
<? if ($results): ?>
    <ul>
    <? foreach ($results as $index => $result): ?>
        <?= $this->render('Result', array('index' => $index, 'result' => $result, 'query' => $query)) ?>
    <? endforeach; ?>
    </ul>
<? else: ?>
    <span class="rn_NoResults"><?= $this->data['attrs']['label_no_results'] ?></span>
<? endif; ?>
<rn:block id="postList"/>
