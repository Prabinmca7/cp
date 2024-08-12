<? if (count($facet->children)): ?>
    <? if ($facet->desc): ?>
        <?=$facet->desc;?><ul>
    <? endif; ?>
        <? $facetHTML = null; ?>
        <? $this->findChildren($facet, $facetHTML, $this->data['attrs']['max_sub_facet_size']); ?>
    <? if ($facet->desc): ?>
        </ul>
    <? endif; ?>
<? endif; ?>