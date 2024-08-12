<? $facetsClass = (count($data['facets']) > 0) ? '' : 'rn_Hidden' ?>
<div class="rn_FacetsList <?= $facetsClass ?>">
    <rn:block id="top"/>
    <div id="rn_<?=$this->instanceID;?>_Alert" role="alert" class="rn_ScreenReaderOnly"></div>
    <rn:block id="topContent"/>
        <ul>
        <? foreach ($data['orderedFacets'] as $facetId => $facetDesc): ?>
            <? if($facetId === 'DOC_TYPES'): ?>
    <li class="rn_DocTypes">
                    <? foreach ($data['facets'] as $facet): ?>
                        <? if($facet->id === 'DOC_TYPES'): ?>
                            <?= $this->render('browse_docType_Collec', array('facet' => $facet)) ?>
                        <? endif; ?>
                    <? endforeach; ?>
    </li>
            <? elseif ($facetId === 'COLLECTIONS'): ?>
    <li class="rn_Collections">
                <? foreach ($data['facets'] as $facet): ?>
                    <? if($facet->id === 'COLLECTIONS'): ?>
                        <?= $this->render('browse_docType_Collec', array('facet' => $facet)) ?>
                    <? endif; ?>
                <? endforeach; ?>
    </li>
            <? elseif ($facetId === 'CMS-PRODUCT'): ?>
                <? if ($facetDesc && count($data['products']['topLevelProduct'])): ?>
                    <li class="rn_FacetProduct"><?=$facetDesc;?>
                <? endif; ?>
                    <?= $this->render('browse_prodCateg', array('data' => $data['products']['topLevelProduct'],
                                                                'size' => $data['attrs']['max_sub_facet_size'],
                                                                'attrs' => $data['attrs'])) ?>
                <? if ($facetDesc && count($data['products']['topLevelProduct'])): ?>
                    </li>
                <? endif; ?>
            <? elseif ($facetId === 'CMS-CATEGORY_REF'): ?>
                <? if ($facetDesc && count($data['categories']['topLevelCategory'])): ?>
                    <li class="rn_FacetCategory"><?=$facetDesc;?>
                <? endif; ?>
                    <?= $this->render('browse_prodCateg', array('data' => $data['categories']['topLevelCategory'],
                                                                'size' => $data['attrs']['max_sub_facet_size'],
                                                                'attrs' => $data['attrs'])) ?>
                <? if ($facetDesc && count($data['categories']['topLevelCategory'])): ?>
                    </li>
                <? endif; ?>
            <? endif; ?>
        <? endforeach; ?>
        </ul>
</div>