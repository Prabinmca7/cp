<rn:block id="top">
    <h2><?= $this->data['attrs']['label_heading'] ?></h2>
</rn:block>

<rn:block id="resultListItem">
    <li class="rn_RelatedKnowledgebaseAnswersItem">
        <span class="rn_Title">
            <a href="<?= $this->data['attrs']['answers_detail_url'] . "/a_id/$answer->ID" . \RightNow\Utils\Url::sessionParameter() ?>" <?= $this->helper->getTarget($answer, $this->data['attrs']['url_type_target']) ?>>
                <?= $this->helper->getTitle($answer, $this->data['attrs']['truncate_title_at']) ?>
            </a>
        </span>
    <? if($this->data['attrs']['show_excerpt']): ?>
        <span class="rn_Excerpt"><?= \RightNow\Utils\Text::escapeHtml($answer->Excerpt, false) ?></span>
    <? endif; ?>
    </li>
</rn:block>
