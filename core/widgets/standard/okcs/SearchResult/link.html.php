<? $fileClass = 'rn_SearchResultIcon rn_File_' . str_replace('-', '_', strtolower($answer->fileType)) ?>
<span class="<?= $fileClass ?>"></span>
<? $dataHref = \RightNow\Utils\Text::stringContains($answer->href, 'ci/okcs') ? $answer->href : $this->data['js']['answerPageUrl'] . $answer->href ?>
<? $href = \RightNow\Utils\Text::stringContains($answer->href, 'ci/okcs') ? $answer->dataHref : $this->data['js']['answerPageUrl'] . $answer->dataHref . \RightNow\Utils\Url::sessionParameter(); ?>
<a id="<?= 'rn_' . $this->instanceID . '_' . $answer->answerId ?>" data-id="<?= $answer->docId ?>" href="<?= $href ?>" data-href="<?= $dataHref ?>" data-url="<?= $answer->clickThroughUrl ?>" data-type="<?= $answer->fileType ?>" data-isHighlighted="<?= $answer->isHighlightingEnabled ?>"><?= $title ?>
<? $docId = explode(':', $answer->link); ?>
<? if (($docId[5] !== null) && ($this->data['attrs']['display_doc_id'])) : ?>
    (<?= $docId[5] ?>)
<? endif; ?>
</a>