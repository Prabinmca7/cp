<div id="rn_<?= $this->instanceID ?>" class="<?= $this->classList ?>">
    <rn:block id="top"/>
    <form class="rn_Padding">
            <div class="rn_SocialFilterItem">
                <fieldset class="rn_SocialFilterItem_FieldSet">
                    <h3 id="rn_<?= $this->instanceID ?>_Title"><?= $this->data['attrs']['label_title']; ?></h3>
                       <ul aria-labelledby="rn_<?= $this->instanceID ?>_Title">
                        <? foreach($this->data['js']['options'] as $value => $label):?>
                            <li>
                                <? if($value !== 'custom'): ?>
                                <input name="rn_<?= $this->instanceID ?>_DateFilter" id="rn_<?= $this->instanceID ?>_DateFilter_<?= $value; ?>" type="radio" value="<?= $value ?>">
                                <label class="rn_Label" for="rn_<?= $this->instanceID ?>_DateFilter_<?= $value; ?>">
                                    <?= $label ?>
                                </label>
                                <? else: ?>
                                    <input name="rn_<?= $this->instanceID ?>_DateFilter" id="rn_<?= $this->instanceID ?>_DateFilter_<?= $value; ?>" type="radio" value="<?= $value ?>">
                                    <label class="rn_Label" for="rn_<?= $this->instanceID ?>_DateFilter_<?= $value; ?>">
                                        <?= $label ?>
                                    </label>
                                    <div class="rn_DateRangeContainer">
                                        <input name="rn_<?= $this->instanceID ?>_EditedOnFrom" id="rn_<?= $this->instanceID ?>_EditedOnFrom" type="text" value="" maxlength="10" size="10" placeholder="<?= $this->data['attrs']['label_from_date'] ?>" class="rn_DateInput"/>
                                        <button id="rn_<?= $this->instanceID ?>_EditedOnFromIcon" title="<?= $this->data['attrs']['label_calendar_from_icon']; ?>" class="rn_CalButton">
                                            <span class="rn_ScreenReaderOnly" aria-live="polite"><?= $this->data['attrs']['label_calendar_from_icon']; ?></span>
                                        </button>
                                        <div id="rn_<?= $this->instanceID ?>_EditedOnFromBoundingBox" class="rn_DatePopupContainer" >
                                            <div id="rn_<?= $this->instanceID ?>_EditedOnFromCal"></div>
                                        </div>
                                        <input name="rn_<?= $this->instanceID ?>_EditedOnTo" id="rn_<?= $this->instanceID ?>_EditedOnTo" type="text" value="" maxlength="10" size="10" placeholder="<?= $this->data['attrs']['label_to_date'] ?>" class="rn_DateInput"/>
                                        <button id="rn_<?= $this->instanceID ?>_EditedOnToIcon" title="<?= $this->data['attrs']['label_calendar_to_icon']; ?>" class="rn_CalButton">
                                            <span class="rn_ScreenReaderOnly" aria-live="polite"><?= $this->data['attrs']['label_calendar_to_icon']; ?></span>
                                        </button>
                                        <div id="rn_<?= $this->instanceID ?>_EditedOnToBoundingBox" class="rn_DatePopupContainer">
                                            <div id="rn_<?= $this->instanceID ?>_EditedOnToCal"></div>
                                        </div>
                                    </div>
                                <? endif; ?>
                            </li>
                        <? endforeach; ?>
                    </ul>
                </fieldset>
            </div>
    </form>
    <rn:block id="bottom"/>
</div>
