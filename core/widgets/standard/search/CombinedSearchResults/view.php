<rn:block id="topResultList">
<? $listItems = 0; ?>
</rn:block>

<rn:block id="resultListItem">
<?
$listItems++;
 if ($this->data['ordering'][$listItems]) {
    $this->displaySubBlock($this->data['ordering'][$listItems]);
    unset($this->data['ordering'][$listItems]);
    $listItems++;
 }
?>
    <li>
        <span class="rn_Element1"><?=$value[0];?></span>
        <? if($value[1]): ?>
        <span class="rn_Element2"><?=$value[1];?></span>
        <? endif; ?>
        <span class="rn_Element3"><?=$value[2];?></span>
        <? for ($i = 3; $i < $reportColumns; $i++): ?>
            <? $header = $this->data['reportData']['headers'][$i]; ?>
            <? if ($this->showColumn($value[$i], $header)): ?>
            <span class="rn_ElementsHeader"><?=$this->getHeader($header);?></span>
            <span class="rn_ElementsData"><?=$value[$i];?></span>
            <? endif; ?>
        <? endfor; ?>
    </li>
<?
if ($listItems === count($this->data['reportData']['data']) && $this->data['ordering']) {
    /* Display any remaining sub-blocks if their specified ordering is greater than number of report results */
    foreach ($this->data['ordering'] as $index => $view) {
        $this->displaySubBlock($view);
        unset($this->data['ordering'][$index]);
    }
}
?>
</rn:block>

<rn:block id="bottomContent">
<? /* Display any sub-blocks if there are no report results */ ?>
<? foreach ($this->data['ordering'] as $view): ?>
    <ul><? $this->displaySubBlock($view); ?></ul>
<? endforeach; ?>
</rn:block>
