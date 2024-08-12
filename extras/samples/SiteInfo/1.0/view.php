<?
/**
 * File: view.php
 * Abstract: PHP view for the SiteInfo widget
 * Version: 1.0
 */
?>

<div id="rn_<?= $this->instanceID ?>" class="rn_TextInput">
<rn:block id="top"/>
<rn:block id="preLabel"/>
<label for="rn_<?= $this->instanceID ?>_Input" id="rn_<?= $this->instanceID ?>_Label" class="rn_Label">
<?= $this->data['attrs']['label_site_url'] ?>
<? if ($this->data['attrs']['required']): ?>
    <rn:block id="preRequired"/>
    <span class="rn_Required"> <?= \RightNow\Utils\Config::getMessage(FIELD_REQUIRED_MARK_LBL) ?></span><span class="rn_ScreenReaderOnly"> <?= \RightNow\Utils\Config::getMessage(REQUIRED_LBL) ?></span>
    <rn:block id="postRequired"/>
<? endif; ?>
</label>
<rn:block id="postLabel"/>
<rn:block id="preInput"/>
    <input type="text" id="rn_<?= $this->instanceID ?>_Input" name="SiteInfo" class="rn_Text" <? if($this->data['value'] !== null && $this->data['value'] !== '') echo "value='{$this->data['value']}'"; ?> />
<rn:block id="postInput"/>
<rn:block id="bottom"/>
</div>