<? if(is_array($data['favoritesList']) && count($data['favoritesList'])):?>
    <rn:block id="preList"/>
    <div id="rn_<?=$this->instanceID;?>_List" class="rn_FavoriteList">
    <? for($i = 0; $i < (is_array($data['favoritesList']) ? count($data['favoritesList']) : 0); $i++):?>
        <rn:block id="preItem"/>
        <div class="rn_Favorite" id="rn_<?=$this->instanceID;?>_<?= $data['favoritesList'][$i]['answerId'] ?>">
            <rn:block id="preInfo"/>
            <div class="rn_Favorite_Info">
                <rn:block id="summary">
                <a href="<?= $this->data['answerUrl'] ?><?= $this->data['favoritesList'][$i]['answerId']?>" target="<?= $this->data['attrs']['target'] ?>"><?=$data['favoritesList'][$i]['title']?></a>
                </rn:block>
                <rn:block id="documentId">
                <span><?= \RightNow\Utils\Config::getMessage(DOC_ID_LBL) ?> - <?= $data['favoritesList'][$i]['documentId'] ?></span>
                </rn:block>
            </div>
            <rn:block id="postInfo"/>
            <rn:block id="preActions"/>
            <div class="rn_Favorite_Actions">
                <rn:block id="preDelete"/>
                <button id="<?= $data['favoritesList'][$i]['answerId'] ?>" class="rn_Favorite_Delete"><?=$data['attrs']['label_delete_button'];?></button>
                <rn:block id="postDelete"/>
            </div>
            <rn:block id="postActions"/>
        </div>
        <rn:block id="postItem"/>
    <? endfor;?>
    </div>
    <rn:block id="postList"/>
<? else:?>
    <rn:block id="preNoFavorites"/>
    <div class="rn_NoFavorite"><?= $this->data['attrs']['label_no_favorites_list'] ?></div>
    <rn:block id="postNoFavorites"/>
<? endif;?>
