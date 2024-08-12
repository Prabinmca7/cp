<div id="rn_<?=$this->instanceID;?>" class="<?= $this->classList ?>">
    <rn:block id="top"/>
    <div class="rn_Title">
        <rn:block id="title">
        <?=$this->data['attrs']['label_title'];?>
        </rn:block>
    </div>
    <?if(!isset($this->data['noResultsClass']) || !$this->data['noResultsClass']):?>
        <rn:block id="preList"/>
        <ul id="rn_<?=$this->instanceID;?>_SuggestionsList">
            <? foreach ($this->data['relatedProducts'] as $product): ?>
                <rn:block id="listItem">
                <li>
                    <a href="<?=$this->data['attrs']['report_page_url'] . '/' . $this->data['js']['productFilter'] . '/' . $product['id'] . $this->data['parameters']['p'];?>"><?= $product['label'] ?></a>
                </li>
                </rn:block>
            <? endforeach; ?>
            <? foreach ($this->data['relatedCategories'] as $category): ?>
                <rn:block id="listItem">
                <li>
                    <a href="<?=$this->data['attrs']['report_page_url'] . '/' . $this->data['js']['categoryFilter'] . '/' . $category['id'] . $this->data['parameters']['c'];?>"><?= $category['label'] ?></a>
                </li>
                </rn:block>
            <? endforeach; ?>
        </ul>
        <rn:block id="postList"/>
    <?endif;?>
    <rn:block id="bottom"/>
</div>
