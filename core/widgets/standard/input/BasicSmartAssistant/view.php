<div class="<?= $this->classList ?>">
    <rn:block id="top"/>
    <?  if ($smartAssistantResults = $this->data['smartAssistantResults']): ?>
        <rn:block id="preTopHeader"/>
        <hr/>
        <rn:block id="postTopHeader"/>
        <rn:block id="preBanner"/>
        <h2 class="rn_Banner"><?= (!$smartAssistantResults['canEscalate'] && $this->data['attrs']['dnc_label_banner']) ? $this->data['attrs']['dnc_label_banner'] : $this->data['attrs']['label_banner'] ?></h2>
        <rn:block id="postBanner"/>
        <rn:block id="preClarification"/>
        <div><?= (!$smartAssistantResults['canEscalate'] && $this->data['attrs']['dnc_label_clarification']) ? $this->data['attrs']['dnc_label_clarification'] : $this->data['attrs']['label_clarification'] ?></div>
        <br/>
        <rn:block id="postClarification"/>
        <? if ($this->data['showHeader']): ?>
            <rn:block id="preHeaderContent"/>
            <? if ($this->data['attrs']['label_solved_link']): ?>
            <rn:block id="headerSolvedLink">
            <div>
                <a href='<?= $this->data['attrs']['solved_url'] . "/saResultToken/" . $smartAssistantResults['token'] . \RightNow\Utils\Url::sessionParameter() ?>'><?= $this->data['attrs']['label_solved_link'] ?></a>
            </div>
            <br/>
            </rn:block>
            <? endif; ?>
            <? if ($smartAssistantResults['canEscalate']): ?>
            <rn:block id="headerEscalate">
            <div>
                <input type='submit' id='rn_<?= $this->instanceID ?>_Header_Button' value='<?= $this->data['attrs']['label_submit_button'] ?>'/>
            </div>
            <br/>
            </rn:block>
            <? endif; ?>
            <rn:block id="postHeaderContent"/>
        <? endif; ?>
        <? if (!is_array($smartAssistantResults['suggestions']) || !count($smartAssistantResults['suggestions'])): ?>
            <rn:block id="preNoResults"/>
            <div><?= $this->data['attrs']['label_no_results'] ?></div>
            <rn:block id="postNoResults"/>
        <? else: ?>
            <? foreach ($smartAssistantResults['suggestions'] as $suggestion): ?>
                <? if ($suggestion['type'] === 'AnswerSummary'): ?>
                <rn:block id="preAnswerSummaryPrompt"/>
                    <div><?= $this->data['attrs']['label_prompt'] ?></div>
                    <rn:block id="postAnswerSummaryPrompt"/>
                    <rn:block id="preAnswerList"/>
                    <ul>
                        <? foreach ($suggestion['list'] as $answer): ?>
                            <rn:block id="preAnswerLink"/>
                            <li><a target='_blank' href='/app/answers/detail/a_id/<?= $answer['ID'] . \RightNow\Utils\Url::sessionParameter() ?>'><?= $answer['title'] ?></a></li>
                            <rn:block id="postAnswerLink"/>
                        <? endforeach; ?>
                    </ul>
                    <rn:block id="postAnswerList"/>
                <? elseif($suggestion['type'] === 'Answer'): ?>
                    <? if($suggestion['FileAttachments'] !== null): ?>
                        <rn:block id="preAttachmentAnswerLink"/>
                        <a target='_blank' href='/app/answers/detail/a_id/<?= $suggestion['ID'] . \RightNow\Utils\Url::sessionParameter() ?>'><?= $suggestion['title'] ?></a>
                        <rn:block id="postAttachmentAnswerLink"/>
                    <? else: ?>
                        <rn:block id="preAnswerTitle"/>
                        <div><h2><?= $suggestion['title'] ?></h2></div>
                        <rn:block id="postAnswerTitle"/>
                        <rn:block id="preAnswerContent"/>
                        <div><?= $suggestion['content'] ?></div>
                        <rn:block id="postAnswerContent"/>
                    <? endif; ?>
                <? elseif($suggestion['type'] === 'QuestionSummary'): ?>
                    <rn:block id=”preQuestionSummary”/>
                    <rn:block id=”postQuestionSummary”/>
                <? else: ?>
                    <rn:block id="preStandardContent"/>
                    <div><?= $suggestion['content'] ?></div>
                    <rn:block id="postStandardContent"/>
                <? endif; ?>
            <? endforeach; ?>
        <? endif; ?>
        <br/>
        <? if ($this->data['attrs']['label_solved_link']): ?>
        <rn:block id="footerSolvedLink">
        <div>
            <a href='<?= $this->data['attrs']['solved_url'] . "/saResultToken/" . $smartAssistantResults['token'] . \RightNow\Utils\Url::sessionParameter() ?>'><?= $this->data['attrs']['label_solved_link'] ?></a>
        </div>
        <br/>
        </rn:block>
        <? endif; ?>
        <? if ($smartAssistantResults['canEscalate']): ?>
        <rn:block id="footerEscalate">
        <div>
            <input type='submit' id='rn_<?= $this->instanceID ?>_Button' value='<?= $this->data['attrs']['label_submit_button'] ?>'/>
        </div>
        <br/>
        </rn:block>
        <? endif; ?>
        <rn:block id="preBottomHeader"/>
        <hr/>
        <rn:block id="postBottomHeader"/>
        <input type="hidden" name="saToken" value="<?= $smartAssistantResults['token'] ?>"/>
        <input type="hidden" name="smart_assistant" value="<?= $smartAssistantResults['canEscalate'] ? 'false' : 'true' ?>"/>
    <? elseif ($this->data['disableSmartAssistant']): ?>
        <input type="hidden" name="smart_assistant" value="false"/>
    <? else: ?>
        <input type="hidden" name="smart_assistant" value="true"/>
    <? endif; ?>
    <rn:block id="bottom"/>
</div>
