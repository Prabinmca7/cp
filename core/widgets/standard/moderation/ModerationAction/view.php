<div id="rn_<?= $this->instanceID ?>" class="<?= $this->classList ?>">
    <rn:block id="top"/>
        <div id="rn_<?= $this->instanceID ?>_ActionButtons">
            <? if(isset($this->data['actions']['suspend']) && $this->data['actions']['suspend']): ?>
                <rn:block id="beforeSuspendButton"/>
                <button type="button" name="suspend" id="rn_<?=$this->instanceID;?>_SuspendButton" value="<?= $this->data['actions']['suspend']; ?>" disabled="disabled"><?= $this->data['attrs']['label_action_suspend']; ?></button>
                <rn:block id="afterSuspendButton"/>
            <? endif;?>

            <? if(isset($this->data['actions']['restore']) && $this->data['actions']['restore']): ?>
                <rn:block id='beforeRestoreButton'/>
                <button type="button" name="restore" id="rn_<?=$this->instanceID;?>_RestoreButton" value="<?= $this->data['actions']['restore']; ?>" disabled="disabled"><?= $this->data['attrs']['label_action_approve_restore']; ?></button>
                <rn:block id="afterRestoreButton"/>
            <? endif;?>

            <? if(isset($this->data['actions']['move']) && $this->data['actions']['move']): ?>
                <rn:block id="beforeMoveButton"/>
                <button type="button" name="move" id="rn_<?= $this->instanceID; ?>_MoveButton" value="<?= $this->data['actions']['move']; ?>" disabled="disabled"><?= $this->data['attrs']['label_action_move']; ?></button>
                <rn:block id="afterMoveButton"/>
            <? endif;?>
            
            <? if(isset($this->data['actions']['archive']) && $this->data['actions']['archive']): ?>
                <rn:block id="beforeArchiveButton"/>
                <button type="button" name="archive" id="rn_<?=$this->instanceID;?>_ArchiveButton" value="<?= $this->data['actions']['archive']; ?>" disabled="disabled"><?= $this->data['attrs']['label_action_archive']; ?></button>
                <rn:block id="afterArchiveButton"/>
            <? endif;?>
            
            <? if(isset($this->data['actions']['delete']) && $this->data['actions']['delete']): ?>
                <rn:block id="beforeDeleteButton"/>
                <button type="button" name="delete" id="rn_<?=$this->instanceID;?>_DeleteButton" value="<?= $this->data['actions']['delete']; ?>" disabled="disabled"><?= $this->data['attrs']['label_action_delete']; ?></button>
                <rn:block id="afterDeleteButton"/>
            <? endif;?>

            <? if(isset($this->data['actions']['reset_flags']) && $this->data['actions']['reset_flags']): ?>
                <rn:block id="beforeResetFlagsButton"/>
                <button type="button" name="reset_flags" id="rn_<?=$this->instanceID;?>_ResetFlagsButton" value="<?= $this->data['actions']['reset_flags']; ?>" disabled="disabled"><?= $this->data['attrs']['label_action_reset_flags']; ?></button>
                <rn:block id="afterResetFlagsButton"/>
            <? endif;?>

            <? if(isset($this->data['actions']['suspend_user']) && $this->data['actions']['suspend_user']): ?>
                <rn:block id="beforeSuspendUserButton"/>
                <button type="button" name="suspend_user" id="rn_<?=$this->instanceID;?>_SuspendUserButton" value="<?= $this->data['actions']['suspend_user']; ?>" disabled="disabled"><?= $this->data['attrs']['label_action_suspend_user']; ?></button>
                <rn:block id="afterSuspendUserButton"/>
            <? endif;?>

            <? if(isset($this->data['actions']['restore_user']) && $this->data['actions']['restore_user']): ?>
                <rn:block id="beforeRestoreUserButton"/>
                <button type="button" name="restore_user" id="rn_<?= $this->instanceID; ?>_RestoreUserButton" value="<?= $this->data['actions']['restore_user']; ?>" disabled="disabled"><?= $this->data['attrs']['label_action_approve_restore_user']; ?></button>
                <rn:block id="afterRestoreUserButton"/>
            <? endif;?>
        </div>
        <? if(isset($this->data['actions']['move']) && $this->data['actions']['move']): ?>
            <div id="rn_<?=$this->instanceID;?>_MoveDialogBody" class="rn_Hidden">
                <form id="rn_<?= $this->instanceID ?>_Form">
                    <div class="rn_SelectionWidget">
                        <? if ($this->data['attrs']['prodcat_type'] === 'Product'): ?>
                            <rn:widget path="input/ProductCategoryInput" name="CommunityQuestion.product" label_input="#rn:msg:PRODUCT_LBL#" label_nothing_selected="#rn:msg:SELECT_A_PRODUCT_LBL#" verify_permissions="Create" sub_id="prodcat"/>
                        <? elseif ($this->data['attrs']['prodcat_type'] === 'Category'): ?>
                            <rn:widget path="input/ProductCategoryInput" name="CommunityQuestion.Category" label_input="#rn:msg:CATEGORY_LBL#" label_nothing_selected="#rn:msg:SELECT_A_CATEGORY_LBL#" verify_permissions="Create" sub_id="prodcat"/>
                        <? endif;?>
                    </div>
                </form>
                <div class="rn_InfoMessage">
                    <rn:block id="preInfoMessage"/>
                    <span id="rn_<?=$this->instanceID;?>_DialogInfoMessage"><?= $this->data['attrs']['label_move_dialog_information_note'];?></span>
                    <rn:block id="postInfoMessage"/>
                </div>
            </div>
        <? endif;?>
    <rn:block id="bottom"/>
</div>