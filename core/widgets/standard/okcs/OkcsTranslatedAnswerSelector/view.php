<div id="rn_<?= $this->instanceID ?>" class="<?= $this->classList ?>">
    <rn:block id="top"/>
    <div class="rn_OkcsTranslatedAnswerSelector">
        <rn:block id="preButton"/>
        <div class="rn_DropdownButtonLabel">
        <span id="rn_<?= $this->instanceID ?>_DropdownButtonLabel"><?= $this->data['attrs']['label_drop_down'] ?></span>
        </div>
        <div class="rn_DropdownButton">
            <button type="button" id="rn_<?=$this->instanceID;?>_DropdownButton" class="rn_DisplayButton rn_Disabled">
                <rn:block id="preDropdownTrigger"/>
                    <span id="rn_<?= $this->instanceID ?>_DisplayLanguage"><?= $this->data['attrs']['label_loading'] ?>
                    <span class="rn_ScreenReaderOnly"><?=$this->data['attrs']['label_drop_down_accessibility']?></span>
                    </span>
                <rn:block id="postDropdownTrigger"/>
            </button>
            <rn:block id="postButton"/>
            <div id="rn_<?=$this->instanceID?>_SubNavigationParent" class="rn_SubNavigationParent" tabindex="-1">
                <ul id="rn_<?=$this->instanceID?>_SubNavigation" class="rn_SubNavigation rn_Hidden"></ul>
                <ul id="rn_<?=$this->instanceID?>_SubNavigationHidden" class="rn_SubNavigationHidden"></ul>
            </div>
        </div>
    </div>
    <rn:block id="bottom"/>
</div>