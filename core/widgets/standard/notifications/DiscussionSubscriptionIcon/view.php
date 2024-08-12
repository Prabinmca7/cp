<div id="rn_<?= $this->instanceID ?>" class="<?= $this->classList ?>">
    <rn:block id="top"/>
    <rn:block id="preLoadingIcon"/>
    <div class="rn_Loading rn_Hidden" id="rn_<?= $this->instanceID ?>_LoadingIcon">
        <span class="rn_ScreenReaderOnly">
            <?= \RightNow\Utils\Config::getMessage(LOADING_LBL) ?>
        </span>
    </div>
    <rn:block id="postLoadingIcon"/>

    <rn:block id="preSubscription"/>
    <div id="rn_<?= $this->instanceID ?>_Subscription" class="rn_Subscription">
        <div id="rn_<?= $this->instanceID ?>_Unsubscribe" class="<?= (isset($this->data['subscriptionID']) && $this->data['subscriptionID'] !== null) ? "rn_Show" : "rn_Hidden" ?> rn_Unsubscribe" aria-label="Required">
            <a href="javascript:void(0);" title="<?=$this->data['attrs']['label_unsubscribe_title'];?>">
                <span>
                    <?=$this->data['attrs']['label_unsubscribe'];?>
                </span>
            </a>
        </div>
        <? if(isset($this->data['js']['prodSubscriptionID']) && $this->data['js']['prodSubscriptionID'] !== null): ?>
            <div id="rn_<?= $this->instanceID ?>_ProdSubscribed" class="<?= ($this->data['subscriptionID'] === null) ? "rn_SpillOverText" : "rn_Hidden" ?> rn_Unsubscribe">
                <a href="/app/<?= \RightNow\Utils\Config::getConfig(CP_PRODUCTS_DETAIL_URL) ?>/p/<?= $this->data['productID'] ?>#rn:session#" title="<?= $this->data['attrs']['label_subscribed_to_product_category_title'] ?>">
                    <span>
                        <? printf($this->data['attrs']['label_subscribed_to_product'], \RightNow\Utils\Text::escapeHtml($this->data['productName'])); ?>
                    </span>
                </a>
            </div>
        <? else: ?>
            <? if(isset($this->data['js']['catSubscriptionID']) && $this->data['js']['catSubscriptionID'] !== null): ?>
                <div id="rn_<?= $this->instanceID ?>_CatSubscribed" class="<?= ($this->data['subscriptionID'] === null) ? "rn_SpillOverText" : "rn_Hidden" ?> rn_Unsubscribe">
                    <a href="/app/<?= \RightNow\Utils\Config::getConfig(CP_CATEGORIES_DETAIL_URL) ?>/c/<?= $this->data['categoryID'] ?>#rn:session#" title="<?= $this->data['attrs']['label_subscribed_to_product_category_title'] ?>">
                        <span>
                            <? printf($this->data['attrs']['label_subscribed_to_product'], \RightNow\Utils\Text::escapeHtml($this->data['categoryName'])); ?>
                        </span>
                    </a>
                </div>
            <? else: ?>
                <div id="rn_<?= $this->instanceID ?>_Subscribe" class="<?= (!isset($this->data['subscriptionID']) || $this->data['subscriptionID'] === null) ? "rn_Show" : "rn_Hidden" ?> rn_Subscribe" aria-label="Required">
                    <a href="javascript:void(0);" title="<?=$this->data['attrs']['label_subscribe_title'];?>">
                        <span>
                            <?=$this->data['attrs']['label_subscribe'];?>
                        </span>
                    </a>
                </div>
            <? endif ?>
    <? endif ?>
    </div>
    <rn:block id="postSubscription"/>
   <rn:block id="bottom"/>
</div>
