<div id="rn_<?=$this->instanceID?>_RatingButtons" class="rn_RatingButtons">
    <rn:block id="preYesButton"/>
    <button id="rn_<?=$this->instanceID?>_RatingYesButton" type="button"><?=$this->data['attrs']['label_yes_button']?></button>
    <rn:block id="postYesButton"/>

    <rn:block id="preNoButton"/>
    <button id="rn_<?=$this->instanceID?>_RatingNoButton" type="button"><?=$this->data['attrs']['label_no_button']?></button>
    <rn:block id="postNoButton"/>
</div>
