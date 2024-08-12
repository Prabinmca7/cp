<?
$question = $this->data['firstQuestion'];
$questionID = "{$this->data['guideID']}_{$question->questionID}"; ?>
<div id="rn_<?= $this->instanceID ?>" class="<?= $this->classList ?>">
    <rn:block id="top"/>
<? if ($this->data['attrs']['popup_window_url']): ?>
    <rn:block id="prePopupButton"/>
    <button class="rn_PopupLaunchButton" onclick="window.open('<?= $this->data['attrs']['popup_window_url'] . \RightNow\Utils\Url::sessionParameter() ?>');"><?= $this->data['attrs']['label_popup_launch_button'] ?></button>
    <rn:block id="postPopupButton"/>
<? else: ?>
    <a id="rn_<?= $this->instanceID ?>_SamePageAnchor"></a>
    <rn:block id="preGuide"/>
    <div id="rn_<?= $this->instanceID ?>_Guide<?= $this->data['guideID'] ?>" class="rn_Guide">
        <rn:block id="guideTop"/>
        <div id="rn_<?= $this->instanceID ?>_Question<?= $questionID ?>" class="rn_Node rn_Question">
            <rn:block id="questionTop"/>
            <div class="rn_QuestionText">
                <?= $question->text ?>
            </div>
            <rn:block id="preResponse"/>
        <? switch ($question->type):
             case GA_BUTTON_QUESTION: ?>
            <div id="rn_<?= $this->instanceID ?>_Response<?= $questionID ?>" class="rn_Response rn_ButtonQuestion">
            <? foreach ($question->responses as $response): ?>
                <button data-level="1" data-guide="<?= $this->data['guideID'] ?>" data-question="<?= $question->questionID ?>" data-response="<?= $response->responseID ?>"><?= $response->text ?></button>
            <? endforeach ?>
            </div>
        <? break;
             case GA_MENU_QUESTION:
             case GA_LIST_QUESTION: ?>
            <?
               $className = $question->type === GA_LIST_QUESTION ? "List" : "Menu";
               $sizeAttribute = $question->type === GA_LIST_QUESTION ? (count($question->responses) + 1) : 0;
            ?>
            <div id="rn_<?= $this->instanceID ?>_Response<?= $questionID ?>" class="rn_Response rn_<?= $className ?>Question">
                <select data-level="1" data-guide="<?= $this->data['guideID'] ?>" data-question="<?= $question->questionID ?>" title="<?= $question->taglessText ?>" size="<?= $sizeAttribute ?>">
                    <option value="">--</option>
                <? foreach ($question->responses as $response): ?>
                    <option value="<?= $response->responseID ?>"><?= $response->text ?></option>
                <? endforeach; ?>
                </select>
            </div>
        <? break;
            case GA_LINK_QUESTION: ?>
            <div id="rn_<?= $this->instanceID ?>_Response<?= $questionID ?>" class="rn_Response rn_LinkQuestion">
                <fieldset>
                    <legend>
                        <span class="rn_ScreenReaderOnly"><?= $question->taglessText ?></span>
                    </legend>
                <? $inputClass = $this->data['js']['mobile'] ? "rn_TransparentScreenReaderOnly" : "rn_ScreenReaderOnly";
                foreach ($question->responses as $response):
                    $id = "rn_{$this->instanceID}_Response{$questionID}_{$response->responseID}"; ?>
                    <div>
                        <input tabindex="-1" data-level="1" data-guide="<?= $this->data['guideID'] ?>" data-question="<?= $question->questionID ?>" data-response="<?= $response->responseID ?>" class="<?= $inputClass?>" type="radio" id="<?= $id ?>" name="rn_<?= $this->instanceID ?>_LinkResponse<?= $questionID ?>" value="<?= $response->responseID ?>"/>
                        <label for="<?= $id ?>"><a href="javascript:void(0);" onclick="document.getElementById('<?= $id ?>').click(); return false;"><?= $response->text ?></a></label>
                    </div>
                <? endforeach; ?>
                </fieldset>
            </div>
        <? break;
            case GA_RADIO_QUESTION: ?>
            <div id="rn_<?= $this->instanceID ?>_Response<?= $questionID ?>" class="rn_Response rn_RadioQuestion">
                <fieldset>
                    <legend>
                        <span class="rn_ScreenReaderOnly"><?= $question->taglessText ?></span>
                    </legend>
                <? foreach ($question->responses as $response): ?>
                    <div>
                        <input type="radio" data-level="1" data-guide="<?= $this->data['guideID'] ?>" data-question="<?= $question->questionID ?>" data-response="<?= $response->responseID ?>" id="rn_<?= $this->instanceID ?>_Response<?= $questionID ?>_<?= $response->responseID ?>" name="rn_<?= $this->instanceID ?>_RadioResponse<?= $questionID ?>" value="<?= $response->responseID ?>"/>
                        <label for="rn_<?= $this->instanceID ?>_Response<?= $questionID ?>_<?= $response->responseID ?>"><?= $response->text ?></label>
                    </div>
                <? endforeach; ?>
                </fieldset>
            </div>
        <? break;
            case GA_IMAGE_QUESTION: ?>
            <div id="rn_<?= $this->instanceID ?>_Response<?= $questionID ?>" class="rn_Response rn_ImageQuestion">
                <fieldset>
                    <legend>
                        <span class="rn_ScreenReaderOnly"><?= $question->taglessText ?></span>
                    </legend>
                <? foreach ($question->responses as $response): ?>
                    <div>
                        <label for="rn_<?= $this->instanceID ?>_Response<?= $questionID ?>_<?= $response->responseID ?>">
                        <? $altText = (!$response->showCaption) ? $response->text : ""; ?>
                        <img src="/ci/fattach/get/<?= $response->imageID ?>" alt="<?= $altText ?>"/>
                            <span class="rn_ImageCaption">
                                <input type="radio" data-level="1" data-guide="<?= $this->data['guideID'] ?>" data-question="<?= $question->questionID ?>" data-response="<?= $response->responseID ?>" id="rn_<?= $this->instanceID ?>_Response<?= $questionID ?>_<?= $response->responseID ?>" name="rn_<?= $this->instanceID ?>_ImageResponse<?= $questionID ?>" value="<?= $response->responseID ?>"/>
                                <? if ($response->showCaption): ?>
                                <?= $response->text ?>
                                <? endif; ?>
                            </span>
                        </label>
                    </div>
                <? endforeach; ?>
                </fieldset>
            </div>
        <? break;
            case GA_TEXT_QUESTION: ?>
            <div id="rn_<?= $this->instanceID ?>_Response<?= $questionID ?>" class="rn_Response rn_TextQuestion">
                <? $response = $question->responses[0]; ?>
                <label class="rn_Label" for="rn_<?= $this->instanceID ?>_Response<?= $questionID ?>_<?= $response->responseID ?>"><?= $response->text ?>
                    <?= $this->render('Partials.Forms.RequiredLabel') ?>
                </label>
                <input type="text" id="rn_<?= $this->instanceID ?>_Response<?= $questionID ?>_<?= $response->responseID ?>" maxlength="255" aria-required="true"/>
                <button data-level="1" data-guide="<?= $this->data['guideID'] ?>" data-question="<?= $question->questionID ?>" data-response="<?= $response->responseID ?>"><?= $this->data['attrs']['label_text_response_button'] ?></button>
            </div>
        <? break;
           endswitch; ?>
           <rn:block id="postResponse"/>
        <? if (isset($this->data['js']['agentMode']) && $this->data['js']['agentMode'] && $this->data['js']['agentMode'] !== 'enduserPreview'): ?>
            <? if ($question->agentText): ?>
                <pre class="rn_AgentText"><em><?= \RightNow\Utils\Config::getMessage(AGT_TEXT_LBL) ?></em><?= $question->agentText ?></pre>
            <? endif; ?>
            <? if(isset($this->data['js']['isChatAgent']) && $this->data['js']['isChatAgent']): ?>
                <a class="rn_ChatLink" href='javascript:void(0);' data-guide="<?= $this->data['guideID'] ?>" data-question="<?= $question->questionID ?>"><?= \RightNow\Utils\Config::getMessage(ADD_TO_CHAT_CMD) ?></a>
            <? endif; ?>
        <? endif; ?>
            <rn:block id="questionBottom"/>
        </div>
        <rn:block id="guideBottom"/>
    </div>
    <rn:block id="postGuide"/>
    <? if ($this->data['attrs']['single_question_display']): ?>
    <rn:block id="preBackButton"/>
    <button id="rn_<?= $this->instanceID ?>_BackButton" class="rn_Hidden rn_BackButton"><?= $this->data['attrs']['label_question_back'] ?></button>
    <rn:block id="postBackButton"/>
    <? endif; ?>
<? endif; ?>
    <rn:block id="bottom"/>
</div>
