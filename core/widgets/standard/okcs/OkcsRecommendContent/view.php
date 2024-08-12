<div id="rn_<?= $this->instanceID ?>" class="<?= $this->classList ?>">
    <rn:block id="top"/>
    <div id="rn_<?=$this->instanceID;?>_Alert" role="alert" class="rn_ScreenReaderOnly"></div>
    <div id="rn_<?=$this->instanceID;?>_RecommendContent" class="rn_RecommendButtonContainer <?=$this->data['recommendationClass'];?>">
        <button class="rn_RecommendContentButton" id="rn_<?=$this->instanceID;?>_RecommendContentButton">
            <?= $this->data['button_label'];?>
        </button>
    </div>
    <rn:block id="preItem"/>
    <div id="rn_<?=$this->instanceID;?>_RecommendForm" class="rn_Hidden">
        <form id="rn_<?=$this->instanceID;?>_RecommendFormSubmit">
        <fieldset class="rn_Fieldset">
            <div id="rn_<?=$this->instanceID;?>_ErrorLocation" aria-live="assertive"></div>
            <div class="rn_RecommendationHeader"><?= $this->data['attrs']['label_recommendation'] ?></div>
            <? $contentTypeClass = $this->data['js']['selectedContentType'] === '' ? '' : 'rn_Hidden'; ?>
            <? if(!$this->data['js']['isRecommendChange']) :?>
                <section class="rn_DropDown <?= $contentTypeClass;?>">
                    <label for="rn_<?=$this->instanceID;?>_ContentType" class="rn_LabelInput"><?= $this->data['attrs']['label_recommend_content_type'] ?>
                    <span class="rn_Required"><?= \RightNow\Utils\Config::getMessage(FIELD_REQUIRED_MARK_LBL) ?></span>
                    <span class="rn_ScreenReaderOnly"><?= \RightNow\Utils\Config::getMessage(REQUIRED_LBL) ?></span></label>
                    <div>
                        <select id="rn_<?=$this->instanceID;?>_ContentType" class="rn_BasicSelection" name="ContentType" >
                            <option value="" selected hidden><?= $this->data['attrs']['label_default_content_type'] ?></option>
                        </select>
                    </div>
                </section>
            <? endif; ?>
            <section>
                <label for="rn_<?=$this->instanceID;?>_Title" class="rn_LabelInput"><?= $this->data['attrs']['label_recommend_title'] ?>
                <span class="rn_Required"><?= \RightNow\Utils\Config::getMessage(FIELD_REQUIRED_MARK_LBL) ?></span>
                <span class="rn_ScreenReaderOnly"><?= \RightNow\Utils\Config::getMessage(REQUIRED_LBL) ?></span></label>
                <div><input type="text" id="rn_<?=$this->instanceID;?>_Title" name="title" class="rn_Title" maxlength="255" value="<?= $this->data['js']['title']?>"/></div>
            </section>
            <section>
                <label for="rn_<?=$this->instanceID;?>_Description" class="rn_LabelInput"><?= $this->data['attrs']['label_recommend_description'] ?>
                <span class="rn_Required"><?= \RightNow\Utils\Config::getMessage(FIELD_REQUIRED_MARK_LBL) ?></span>
                <span class="rn_ScreenReaderOnly"><?= \RightNow\Utils\Config::getMessage(REQUIRED_LBL) ?></span></label>
                <textarea id="rn_<?=$this->instanceID;?>_Description" class="rn_TextArea" rows="7" cols="60" maxlength=<?= $this->data['attrs']['default_maxlength_value'] ?> required=""></textarea>
                <div id="rn_<?=$this->instanceID;?>_CharacterRemaining"></div>
            </section>
            <? if($this->data['attrs']['show_recommend_case_number']) :?>
            <section>
                <label for="rn_<?=$this->instanceID;?>_CaseNumber" class="rn_LabelInput"><?= $this->data['attrs']['label_recommend_case_number'] ?></label>
                <div><input type="text" id="rn_<?=$this->instanceID;?>_CaseNumber" name="caseNumber" class="rn_CaseNumber" maxlength="100"/></div>
            </section>
            <? endif; ?>
            <section>
                <label for="rn_<?=$this->instanceID;?>_Attachment" class="rn_LabelInput"><?=$this->data['attrs']['label_recommend_attachment'] ?></label>
                <div><input name="file" id="rn_<?=$this->instanceID;?>_FileInput" type="file"/></div>
                <label><a href='javascript:void(0)' id="rn_<?=$this->instanceID;?>_Remove" class='rn_fileRemove rn_Hidden'><?=$this->data['attrs']['label_remove'] ?></a></label>
                <div id="rn_<?=$this->instanceID;?>_StatusMessage" class="rn_Hidden" style="color:red;"></div>
            </section>
            <? if($this->data['attrs']['show_recommend_priority']) :?>
            <section>
                <label for="rn_<?=$this->instanceID;?>_Priority" class="rn_LabelInput"><?= $this->data['attrs']['label_recommend_priority'] ?></label>
                <div>
                    <select id="rn_<?=$this->instanceID;?>_Priority" class="rn_BasicSelection" name="Priority" >
                        <option value="None" selected hidden><?= $this->data['attrs']['label_default_priority'] ?></option>
                        <option value="None"><?= \RightNow\Utils\Config::getMessage(NONE_LBL) ?></option>
                        <option value="LOW"><?= \RightNow\Utils\Config::getMessage(LOW_LBL) ?></option>
                        <option value="MEDIUM"><?= \RightNow\Utils\Config::getMessage(MEDIUM_LBL) ?></option>
                        <option value="HIGH"><?= \RightNow\Utils\Config::getMessage(HIGH_LBL) ?></option>
                    </select>
                </div>
            </section>
            <? endif; ?>
            <div class="rn_ButtonDiv">
                <button id="rn_<?=$this->instanceID;?>_RecommendationSubmit"><?= $this->data['attrs']['label_recommend_submit'];?></button>
            </div>
            <div class="rn_ButtonDiv">
                <button type="button" id="rn_<?=$this->instanceID;?>_RecommendationCancel"><?= $this->data['attrs']['label_recommend_cancel'];?></button>
            </div>
            <div>
                <span id="rn_<?= $this->instanceID ?>_StatusMessage" class="rn_Hidden"><?= $this->data['attrs']['label_submitting_message'] ?></span>
            </div>
        </fieldset>
        </form>
    </div>
    <rn:block id="bottom"/>
</div>
