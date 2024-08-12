<? if ($this->data['attrs']['label_title']): ?>
<h2>
    <?= $this->data['attrs']['label_title'] ?>
</h2>
<? endif; ?>

<? if ($this->data['activityOrdering']): ?>
    <? foreach ($this->data['activityOrdering'] as $ordering): ?>
        <? /* Skip activities that happen on the same object, to avoid redundancy */ ?>
        <? if (isset($lastActivity) && $lastActivity && $lastActivity['id'] === $ordering['id']) continue; ?>
        <div class="rn_Activity rn_<?= ucfirst($ordering['type']) ?>" itemscope itemtype="http://schema.org/Question">
            <? if (isset($lastActivity['type']) && $lastActivity['type'] !== $ordering['type'] ? true : false): ?>
            <rn:block id="activityTitle">
            <h3 class="rn_ActivityTitle">
                <span class="rn_ActivityTitleLabel">
                    <?= $this->helper->labelForActivity($ordering['type'], $this->data['attrs']) ?>
                </span>
            </h3>
            </rn:block>
            <? endif; ?>

            <?= $this->render($ordering['type'], array(
                'action' => $this->data['activity'][$ordering['index']],
                'date' => $ordering['date']
             )) ?>
        </div>
        <? $lastActivity = $ordering; ?>
    <? endforeach; ?>
<? else: ?>
    <?=$this->data['attrs']['label_no_recent_activity']?>
<? endif; ?>
