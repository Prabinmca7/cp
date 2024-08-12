<? if (isset($this->data['js']['readOnly']) && $this->data['js']['readOnly']): ?>
<rn:widget path="output/ProductCategoryDisplay" name="#rn:php:$this->data['attrs']['name']#" label="#rn:php:$this->data['attrs']['label_input']#" left_justify="true"/>
<? else: ?>
<? $i = 1; $id = "rn_{$this->instanceID}_{$this->data['js']['data_type']}"; ?>
<div id="rn_<?= $this->instanceID ?>" class="<?= $this->classList ?>">
    <rn:block id="top"/>
    <? /* Dialog Content */ ?>
    <rn:block id="preDialog"/>
    <div id="<?= $id ?>_Level1Input" class="rn_Hidden rn_Input rn_MobileProductCategoryInput rn_Level1">
        <rn:block id="dialogTop"/>
    <? foreach ($this->data['firstLevel'] as $item): ?>
        <div class="rn_Parent <?=(isset($item['selected']) && $item['selected']) ? 'rn_Selected' : '';?>">
            <input type="radio" name="<?= $id ?>_Level1" id="<?= $id ?>_Input1_<?= $i ?>" value="<?= $item['id'] ?>"/>
                <? $class = (isset($item['hasChildren']) && $item['hasChildren'] && $this->data['attrs']['max_lvl'] !== 1) ? 'rn_HasChildren' : ''; ?>
                <label class="<?= $class ?>" id="<?= $id ?>_Label1_<?= $i ?>" for="<?= $id ?>_Input1_<?= $i ?>"><?= htmlspecialchars($item['label'], ENT_QUOTES, 'UTF-8') ?>
                <? if (isset($item['hasChildren']) && $item['hasChildren']): ?><span class="rn_ParentMenuAlt"> <?= $this->data['attrs']['label_parent_menu_alt'] ?></span><? endif; ?>
                </label>
            </div>
        <? $i++;
         endforeach; ?>
         <rn:block id="dialogBottom"/>
    </div>
    <rn:block id="postDialog"/>
    <? /* Displayed on the page */ ?>
    <label class="rn_Label" id="<?= $id ?>_Label" for="<?= $id ?>_Launch" aria-hidden="true">
        <rn:block id="labelTop"/>
        <?= $this->data['attrs']['label_input'] ?>
        <? if ($this->data['attrs']['label_input'] && $this->data['attrs']['required_lvl']): ?>
            <span class="rn_Required"> <?= \RightNow\Utils\Config::getMessage(FIELD_REQUIRED_MARK_LBL) ?></span>
            <span class="rn_ScreenReaderOnly"><?= \RightNow\Utils\Config::getMessage(REQUIRED_LBL) ?></span>
        <? endif; ?>
        <? // Workaround for iOS VoiceOver since it only reads label text, not button text if a label is present.
           // By giving the label aria-hidden the label won't be read, but by adding aria-labelledby on the button
           // the label is used as the button text.  This solution also works on Android TalkBack. Note that this
           // solution breaks JAWS, but since this is the mobile pageset, that is okay. ?>
        <span class="rn_ScreenReaderOnly"><?= $this->data['promptLabel'] ?></span>
        <rn:block id="labelBottom"/>
    </label>
    <rn:block id="preDisplayButton"/>
    <button type="button" id="<?=$id;?>_Launch" class="rn_DisplayButton" aria-labelledby="<?= $id ?>_Label"><?= $this->data['promptLabel'] ?></button>
    <rn:block id="postDisplayButton"/>
    <rn:block id="bottom"/>
</div>
<? endif;?>