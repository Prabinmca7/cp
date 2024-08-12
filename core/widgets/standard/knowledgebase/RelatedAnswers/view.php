<div id="rn_<?= $this->instanceID ?>" class="<?= $this->classList ?>">
<rn:block id="top"/>
    <? if ($this->data['attrs']['label_title']): ?>
        <rn:block id="title">
            <h2><?= $this->data['attrs']['label_title'] ?></h2>
        </rn:block>
    <? endif; ?>

    <rn:block id="preList"/>
    <ul>
    <? foreach ($this->data['relatedAnswers'] as $answer): ?>
        <rn:block id="listItem">
            <li>
            <a href="<?= $this->data['attrs']['url'] . '/a_id/' . $answer->ID . $this->data['appendedParameters'] ?>" target="<?= $this->data['attrs']['target'] ?>">
                <?= $this->helper->formatTitle(
                    $answer->Title,
                    $this->data['attrs']['truncate_size'],
                    $this->data['attrs']['highlight']
                ) ?>
            </a>
        </li>
        </rn:block>
    <? endforeach; ?>
    </ul>
    <rn:block id="postList"/>
<rn:block id="bottom"/>
</div>
