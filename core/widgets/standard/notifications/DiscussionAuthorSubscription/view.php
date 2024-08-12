<div id="rn_<?= $this->instanceID ?>" class="<?= $this->classList ?>">
    <rn:block id="top"/>
    <div id="rn_<?= $this->instanceID ?>_AuthorSubscription" class="rn_AuthorSubscription">
        <rn:block id="preSubscription"/>
        <div id="rn_<?= $this->instanceID ?>_SubscribeMe" class="<?= $this->data['subscription_id'] ? 'rn_Hidden' : '' ?> rn_SubscribeMe">            
            <input id="rn_<?= $this->instanceID ?>_SubscribeMe_Check" type="checkbox" name="CommunityUser.Subscribe" <?= (!$this->data['subscription_id'] && $this->data['attrs']['subscribe_me_default']) ? 'checked="checked"' : '' ?> value="1">
            <label for="rn_<?= $this->instanceID ?>_SubscribeMe_Check" class="rn_Label">
                <?=$this->data['attrs']['label_subscribe_me'];?>
            </label>
        </div>
        <div id="rn_<?= $this->instanceID ?>_Subscribed" class="<?= $this->data['subscription_id'] ? '' : 'rn_Hidden' ?> rn_Subscribed">
            <span>
                <?= sprintf($this->data['attrs']['label_subscribed_to_prodcat'], ($this->data['attrs']['prodcat_type'] === 'Product' ? \RightNow\Utils\Config::getMessage(PRODUCT_LC_LBL) : \RightNow\Utils\Config::getMessage(CATEGORY_LWR_LBL)));?>
            </span>
        </div>
        <rn:block id="postSubscription"/>
    </div>
    <rn:block id="preLoadingIcon"/>
    <div class="rn_Loading rn_Hidden" id="rn_<?= $this->instanceID ?>_LoadingIcon">
        <span class="rn_ScreenReaderOnly">
            <?= \RightNow\Utils\Config::getMessage(LOADING_LBL) ?>
        </span>
    </div>
    <rn:block id="postLoadingIcon"/>
    <rn:block id="bottom"/>
</div>
