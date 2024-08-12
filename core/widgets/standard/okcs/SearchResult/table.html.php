<table>
    <rn:block id="topResultList"/>
        <? $rowNum = 1; ?>
        <? foreach ($this->data['results'] as $value): ?>
            <rn:block id="resultListItem">
            <tr>
                <td class="rn_SearchResultAnswer">
                    <? $resultItemStyle = ''?>
                    <? if ($value->type === 'template' && $this->data['attrs']['apply_style_on_intents']) : ?>
                            <? $value->fileType = 'intent'?>
                            <? $typeOfFile = 'intent'?>
                            <? $resultItemStyle = 'rn_IntentResult'?>
                    <? endif;?>
                    <? $fileCss = 'rn_ResultIcon rn_File_' . str_replace('-', '_', strtolower($value->fileType)) ?>
                    <div class="rn_ResultElement <?= $resultItemStyle?>">
                        <span class="<?= $fileCss ?>"></span>
                        <span class="rn_Element1">
                            <? $typeOfFile = str_replace('-', '_', strtolower($value->fileType)); ?>
                            <? if ($value->type === 'template') : ?>
                                <? $value->fileType = 'intent'?>
                                <? $typeOfFile = 'intent'?>
                            <? endif; ?>
                            <?= $this->render('link', array('answer' => $value, 'title' => \RightNow\Utils\Text::truncateText($value->title, $this->data['attrs']['truncate_size']), 'fileType' => $this->data['fileDescription'][$typeOfFile])) ?>
                        </span>
                        <? if (((strpos($value->link, "IM:") === 0) && $value->fileType !== "CMS-XML" && $value->type !== 'template') && ($this->data['attrs']['open_parent_answer'])) : ?>
                            <span><a href="<?= $this->data['js']['answerPageUrl'] .'/a_id/'. $value->globalAnswerId ?>" target="_blank"> <span class="rn_ResultIcon rn_File_Open_article" title="<?= $this->data['attrs']['label_open_article'] ?>"></span></a></span>
                        <? endif; ?>
                        <? if(isset($value->textElements) && is_array($value->textElements) && $value->textElements && count($value->textElements) > 0) : ?>
                            <div class="rn_SearchResultExcerpt" >
                                <? $excerptElement = ''; ?>
                                <? foreach ($value->textElements as $excerptSnippet) : ?>
                                    <? foreach ($excerptSnippet->snippets as $snippet) : ?>
                                        <? if ($excerptSnippet->type === 'HTML') : ?>
                                            <? $excerptElement .= '<span class="rn_SnippetLevel' . $snippet->level . '">' . $snippet->text . '</span>' ?>
                                        <? else : ?>
                                            <? $excerptElement .= '<span class="rn_SnippetLevel' . $snippet->level . '">' . htmlspecialchars($snippet->text) . '</span>' ?>
                                        <? endif; ?>
                                    <? endforeach; ?>
                                <? endforeach; ?>
                                <?= $excerptElement ?>
                            </div>
                        <? endif; ?>
                    </div>
                </td>
            </tr>
            </rn:block>
            <? $rowNum++; ?>
        <? endforeach; ?>
    <rn:block id="bottomResultList"/>
</table>