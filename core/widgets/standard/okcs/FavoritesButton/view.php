<div id="rn_<?= $this->instanceID ?>" class="<?= $this->classList ?>">
    <rn:block id="top"/>
    <div id="rn_<?=$this->instanceID;?>_Alert" role="alert" class="rn_ScreenReaderOnly"></div>
    <div id="rn_<?=$this->instanceID;?>_Favorite">
    <? $favoriteClass = $this->data['js']['enabled'] ? '' : 'rn_Hidden'; ?>
    <? if(\RightNow\Utils\Framework::isLoggedIn()) : ?>
        <button class="rn_FavoritesButton <?= $favoriteClass ?>" id="rn_<?=$this->instanceID;?>_FavoritesButton">
        <?=isset($this->data['js']['favoriteID']) ? $this->data['attrs']['label_remove_favorite_button'] : $this->data['attrs']['label_add_favorite_button'];?></button>
    <? endif; ?>
    </div>
    <rn:block id="bottom"/>
</div>
