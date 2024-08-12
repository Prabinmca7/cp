<? if ($this->data['readOnly']): ?>
    <rn:block id="preReadOnlyField"/>
    <rn:widget path="output/FieldDisplay" label="#rn:php:$this->data['attrs']['label_input']#" left_justify="true" sub_id="readOnlyField"/>
    <rn:block id="postReadOnlyField"/>
<? else: ?>
<div class="<?= $this->classList ?>">
<rn:block id="top"/>
<fieldset>
<? if ($this->data['attrs']['label_input']): ?>
    <rn:block id="preLegend"/>
    <legend class="rn_Label"><?= $this->data['attrs']['label_input'] ?>
    <? if ($this->data['attrs']['required']): ?>
        <?= $this->render('Partials.Forms.RequiredLabel') ?>
    <? endif; ?>
    <? if ($this->data['attrs']['hint']): ?>
        <?= $this->data['attrs']['hint_separator'] . ' ' . $this->data['attrs']['hint'] ?>
    <? endif; ?>
    </legend>
    <rn:block id="postLegend"/>
<? endif; ?>

<? for ($i = 0; $i < 3; $i++): ?>

    <? /*Year*/ ?>
    <? if ($this->data['yearOrder'] === $i): ?>
    <label for="rn_<?= $this->instanceID ?>_<?= $this->data['js']['name'] ?>_Year" class="rn_ScreenReaderOnly"><?= $this->data['yearLabel']?></label>
    <rn:block id="preYearSelect"/>
    <select id="rn_<?= $this->instanceID ?>_<?= $this->data['js']['name'] ?>_Year" name="formData[<?= $this->data['inputName'] ?>#Year]">
        <rn:block id="topYearSelect"/>
        <option value=''>--</option>
        <? for ($j = $this->data['attrs']['max_year']; $j >= $this->data['attrs']['min_year']; $j--): ?>
        <option value="<?= $j ?>" <?= $this->outputSelected(2, $j) ?>><?= $j ?></option>
        <? endfor; ?>
        <rn:block id="bottomYearSelect"/>
    </select>
    <rn:block id="postYearSelect"/>

    <? /*Month*/ ?>
    <? elseif ($this->data['monthOrder'] === $i): ?>
    <label for="rn_<?= $this->instanceID ?>_<?= $this->data['js']['name'] ?>_Month" class="rn_ScreenReaderOnly"><?= $this->data['monthLabel'] ?></label>
    <rn:block id="preMonthSelect"/>
    <select id="rn_<?= $this->instanceID ?>_<?= $this->data['js']['name'] ?>_Month" name="formData[<?= $this->data['inputName']?>#Month]">
        <rn:block id="topMonthSelect"/>
        <option value=''>--</option>
        <? for ($j = 1; $j < 13; $j++): ?>
        <option value="<?= $j ?>" <?= $this->outputSelected(0, $j) ?>><?= $j ?></option>
        <? endfor; ?>
        <rn:block id="bottomMonthSelect"/>
    </select>
    <rn:block id="postMonthSelect"/>

    <? /*Day*/ ?>
    <? else: ?>
    <label for="rn_<?= $this->instanceID ?>_<?= $this->data['js']['name'] ?>_Day" class="rn_ScreenReaderOnly"><?= $this->data['dayLabel'] ?></label>
    <rn:block id="preDaySelect"/>
    <select id="rn_<?= $this->instanceID ?>_<?= $this->data['js']['name'] ?>_Day" name="formData[<?= $this->data['inputName']?>#Day]">
        <rn:block id="topDaySelect"/>
        <option value=''>--</option>
        <? for ($j = 1; $j < 32; $j++): ?>
        <option value="<?= $j ?>" <?= $this->outputSelected(1, $j) ?>><?= $j ?></option>
        <? endfor; ?>
        <rn:block id="bottomDaySelect"/>
    </select>
    <rn:block id="postDaySelect"/>
    <? endif; ?>
<? endfor; ?>

<? if ($this->data['displayType'] === 'DateTime'): ?>

    <? /*Hour*/ ?>
    <label for="rn_<?= $this->instanceID ?>_<?= $this->data['js']['name'] ?>_Hour" class="rn_ScreenReaderOnly"><?= $this->data['hourLabel'] ?></label>
    <rn:block id="preHourSelect"/>
    <select id="rn_<?= $this->instanceID ?>_<?= $this->data['js']['name'] ?>_Hour" name="formData[<?=$this->data['inputName']?>#Hour]">
        <rn:block id="topHourSelect"/>
        <option value=''>--</option>
        <? for ($j = 0; $j < 24; $j++): ?>
        <option value="<?= $j ?>" <?= $this->outputSelected(3, $j) ?>><?= $j ?></option>
        <rn:block id="bottomHourSelect"/>
        <? endfor; ?>
    </select>
    <rn:block id="postHourSelect"/>

    <? /*Minute*/ ?>
    <label for="rn_<?= $this->instanceID ?>_<?= $this->data['js']['name'] ?>_Minute" class="rn_ScreenReaderOnly"><?= $this->data['minuteLabel'] ?></label>
    <rn:block id="preMinuteSelect"/>
    <select id="rn_<?= $this->instanceID ?>_<?= $this->data['js']['name'] ?>_Minute" name="formData[<?= $this->data['inputName']?>#Minute]">
        <rn:block id="topMinuteSelect"/>
        <option value=''>--</option>
        <? for ($j = 0; $j < 60; $j++): ?>
        <option value="<?= $j ?>" <?= $this->outputSelected(4, $j) ?>><?= $j ?></option>
        <? endfor; ?>
        <rn:block id="bottomMinuteSelect"/>
    </select>
    <rn:block id="postMinuteSelect"/>
<? endif; ?>
</fieldset>
<rn:block id="bottom"/>
</div>
<? endif; ?>
