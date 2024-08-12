<?
$ariaAttribute = '';
if(get_instance()->meta['template'] !== 'basic.php') {
    $ariaLabel = isset($screenReaderLabel) && $screenReaderLabel ? $screenReaderLabel : \RightNow\Utils\Config::getMessage(REQUIRED_LBL);
    $ariaAttribute = 'aria-label="' . $ariaLabel . '"';
}
?>
<span class="rn_Required" <?= $ariaAttribute ?>>
<?= isset($requiredLabel) && $requiredLabel ? $requiredLabel : \RightNow\Utils\Config::getMessage(FIELD_REQUIRED_MARK_LBL) ?>
</span>