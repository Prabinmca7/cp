<?
    function lockedIcon($locked) {
        return ($locked) ? '<i class="fa fa-lock fa-lg" title="' . \RightNow\Utils\Config::getMessage(CONFIG_DEPLOYED_PRODUCTION_MSG) . '"></i>'
            : '<i class="fa fa-unlock-alt fa-lg" title="' . \RightNow\Utils\Config::getMessage(CONFIG_DEPLOYED_PRODUCTION_EDITED_MSG) . '"></i>';
    }
    function disabledIcon($class) {
        return "<i class=\"fa fa-ban fa-lg $class\" title=\"" . \RightNow\Utils\Config::getMessage(THIS_CONFIGURATION_IS_DISABLED_MSG) . '"></i>';
    }
    function customRowData($mapping, $value, $idSuffix) {
        $value = \RightNow\Utils\Text::escapeHtml($value);
        if(!$mapping->locked) {
            $id = $mapping->id . $idSuffix;
            return "<td><input type='text' id='$id' value='$value' maxlength='240' class='hide' autocomplete='off' required/><div id='{$id}_display' class=''>$value</div></td>";
        }
        return "<td>$value</td>";
    }
?>

<h2><?=\RightNow\Utils\Config::getMessage(PAGE_SET_MAPPING_LBL);?></h2>
<div id="flashMessage" class="invisible message" role="alert"></div>
<? /* Show/Hide disabled items */?>
<div class="right textright">
    <? printf(\RightNow\Utils\Config::getMessage(SHOW_PCT_S_PIPE_PCT_S_CMD), '<a id="showAll" class="selected" href="javascript:void(0)">' . \RightNow\Utils\Config::getMessage(ALL_LBL) . '</a>', '<a id="showEnabled" href="javascript:void(0)">' . \RightNow\Utils\Config::getMessage(ENABLED_LBL) . '</a>');?>
</div>
<table id="mappingTable" class="rn_Table" summary="<?= \RightNow\Utils\Config::getMessage(ADD_REMOVE_MODIFY_PG_SET_MAPPINGS_CMD) ?>">
  <tr>
    <th id="pageSetID" scope="col" title="<?=\RightNow\Utils\Config::getMessage(ID_REPORTED_CLICKSTREAMS_AGT_MSG);?>"><?=\RightNow\Utils\Config::getMessage(ID_LBL);?><span class="screenreader"><?=\RightNow\Utils\Config::getMessage(ID_REPORTED_CLICKSTREAMS_AGT_MSG);?></span><i class="fa fa-info-circle moreInfo"></i></th>
    <th id="expression" scope="col" title="<?=\RightNow\Utils\Config::getMessage(RGULAR_EXPR_COMP_AGT_STRING_DET_PG_MSG);?>"><?=\RightNow\Utils\Config::getMessage(USER_AGENT_REGULAR_EXPRESSION_LBL);?><span class="screenreader"><?=\RightNow\Utils\Config::getMessage(RGULAR_EXPR_COMP_AGT_STRING_DET_PG_MSG);?></span><i class="fa fa-info-circle moreInfo"></i></th>
    <th id="description" scope="col" title="<?=\RightNow\Utils\Config::getMessage(COMMON_DESC_THES_VALS_GROUPED_MSG);?>"><?=\RightNow\Utils\Config::getMessage(DESCRIPTION_LBL);?><span class="required">&nbsp;*</span><span class="screenreader"><?=\RightNow\Utils\Config::getMessage(COMMON_DESC_THES_VALS_GROUPED_MSG);?></span><i class="fa fa-info-circle moreInfo"></i></th>
    <th id="pageSet" scope="col" title="<?=\RightNow\Utils\Config::getMessage(PG_SET_DIRECTED_REGULAR_EXPR_MSG);?>"><?=\RightNow\Utils\Config::getMessage(PAGE_SET_LBL);?><span class="required">&nbsp;*</span><span class="screenreader"><?=\RightNow\Utils\Config::getMessage(PG_SET_DIRECTED_REGULAR_EXPR_MSG);?></span><i class="fa fa-info-circle moreInfo"></i></th>
    <th scope="col">&nbsp;</th>
    <th scope="col">&nbsp;</th>
  </tr>
  <? 
  foreach ($vars['standard'] as $mapping):
        $enabledString = ($mapping->enabled) ? '0' : '1';
  ?>
  <? /** STANDARD ROWS */ ?>
  <tr id="standard_<?=$mapping->id?>" class="standard <?=($mapping->enabled) ? '' : 'disabled';?>">
    <td><?= $mapping->id ?></td>
    <td class="nowrap"><?= $mapping->item ?></td>
    <td><?= $mapping->description ?></td>
    <td><?= $mapping->value ?></td>
    <td><?=lockedIcon($mapping->locked);?> <?=(disabledIcon(($mapping->enabled ? 'hide' : '')));?></td>
    <td><a href="javascript:void(0);" role='button' data-enable="<?= $enabledString ?>" data-row="<?= $mapping->id ?>"><?=($mapping->enabled === true) ? \RightNow\Utils\Config::getMessage(DISABLE_CMD) : \RightNow\Utils\Config::getMessage(ENABLE_CMD);?></a></td>
  </tr>
  <? endforeach; ?>

  <? /** CUSTOM ROWS */ ?>
  <?  $customMapping = (is_array($vars['custom'])) ? $vars['custom'] : array();
        foreach ($customMapping as $mapping):
            $enabledString = ($mapping->enabled) ? '0' : '1';
            $mappingID = $mapping->id;
            $mappingIsLocked = $mapping->locked;
  ?>
  <tr id="custom_<?=$mapping->id;?>" class="<?=(($mappingIsLocked ? 'locked' : '') . (($mapping->enabled) ? '' : ' disabled'));?>">
    <? /* ID */?>
    <td><?=$mappingID?></td>
    <? /* Regex */?>
    <?=customRowData($mapping, $mapping->item, '_item');?>
    <? /* Description */?>
    <?=customRowData($mapping, $mapping->description, '_description');?>
    <? /* Page set */?>
    <?=customRowData($mapping, $mapping->value, '_value');?>
    <? /* Locked / Disabled */?>
    <td>
        <?=lockedIcon($mappingIsLocked);?>
        <?=(disabledIcon(($mapping->enabled ? 'hide' : '')));?>
    </td>
    <? /* Enable / Disable */?>
    <td>
        <a href="javascript:void(0);" role='button' id="enable_<?=$mappingID?>" data-enable="<?= $enabledString ?>" data-row="<?= $mappingID ?>"><?=($mapping->enabled === true) ? \RightNow\Utils\Config::getMessage(DISABLE_CMD) : \RightNow\Utils\Config::getMessage(ENABLE_CMD);?></a>
        <? if (!$mappingIsLocked): ?>
        <a href="javascript:void(0);" role='button' data-row="<?= $mappingID ?>" class='edit'><?=\RightNow\Utils\Config::getMessage(EDIT_LBL)?></a>
        <? endif; ?>
    </td>
    <? /* Edit mode actions (delete / save) initially hidden*/?>
    <? if (!$mappingIsLocked): ?>
    <td colspan='2' class='hide'>
        <div class="editMode">
            <i class="fa fa-check-square-o"></i><a href="javascript:void(0);" role='button' class='save action' data-row='<?= $mappingID ?>' data-operation='update'><?= \RightNow\Utils\Config::getMessage(SAVE_CMD) ?></a>
            <i class="fa fa-times"></i><a href="javascript:void(0);" role='button' class='delete action' data-row='<?= $mappingID ?>'><?= \RightNow\Utils\Config::getMessage(DELETE_CMD) ?></a>
        </div>
    </td>
    <? endif; ?>
  </tr>
  <? endforeach; ?>
</table>

<script id="newMappingRow" type="text/x-yui3-template">
<tr class='custom' id='custom_<%= this.id %>' data-new-row='true'>
    <td class='newLabel'><%= this.newLabel %></td>
    <td>
        <label class='screenreader' for='<%= this.id %>_item'>
            <%= this.expressionLabel %>
        </label>
        <input type='text' id='<%= this.id %>_item' maxlength='240' autocomplete='off'/>
    </td>
    <td>
        <label class='screenreader' for='<%= this.id %>_description'>
            <%= this.descriptionLabel %>
        </label>
        <input type='text' id='<%= this.id %>_description' maxlength='240' required autocomplete='off'/>
    </td>
    <td>
        <label class='screenreader' for='<%= this.id %>_value'>
            <%= this.pageSetLabel %>
        </label>
        <input type='text' id='<%= this.id %>_value' maxlength='240' required autocomplete='off'/>
    </td>
    <td class='hide'></td>
    <td class='hide'></td>
    <td colspan='2'>
        <div class='editMode'>
            <i class='fa fa-check-square-o' aria-hidden='true' role='presentation'></i>
            <a id='custom_save_<%= this.id %>' role='button' class='save action' data-row='<%= this.id %>' data-operation='add' href='javascript:void(0);'><%= this.save %></a>
            <i class='fa fa-times' aria-hidden='true' role='presentation'></i>
            <a id='custom_delete_<%= this.id %>' role='button' class='delete action' data-row='<%= this.id %>' href='javascript:void(0);'><%= this.deleteLabel %></a>
        </div>
    </td>
</tr>
</script>

<script id="savedMappingRow" type="text/x-yui3-template">
<tr class='custom' id='custom_<%= this.id %>'>
    <td><%= this.id %></td>
    <td>
        <label class='screenreader' for='<%= this.id %>_item'>
            <%= this.expressionLabel %>
        </label>
        <input type='text' id='<%= this.id %>_item' maxlength='240' class='hide' value='<%= this.item %>' autocomplete='off'/>
        <div id='<%= this.id %>_item_display'>
        <%= this.item %>
        </div>
    </td>
    <td>
        <label class='screenreader' for='<%= this.id %>_description'>
            <%= this.descriptionLabel %>
        </label>
        <input type='text' id='<%= this.id %>_description' maxlength='240' class='hide' value='<%= this.description %>' required autocomplete='off'/>
        <div id='<%= this.id %>_description_display'>
        <%= this.description %>
        </div>
    </td>
    <td>
        <label class='screenreader' for='<%= this.id %>_value'>
            <%= this.pageSetLabel %>
        </label>
        <input type='text' id='<%= this.id %>_value' maxlength='240' class='hide' value='<%= this.value %>' required autocomplete='off'/>
        <div id='<%= this.id %>_value_display'>
        <%= this.value %>
        </div>
    </td>
    <td>
        <i class='fa fa-unlock-alt fa-lg' title='<%= this.unlocked %>'><span class='screenreader'><%= this.unlocked %></span></i>
        <i class='fa fa-ban fa-lg hide' title='<%= this.disabled %>'><span class='screenreader'><%= this.disabled %></span></i>
    </td>
    <td>
        <a data-enable='0' role='button' data-row='<%= this.id %>' id='enable_<%= this.id %>' href='javascript:void(0);'><%= this.disable %></a>
        <a class='edit' data-row='<%= this.id %>' href='javascript:void(0);'><%= this.edit %></a>
    </td>
    <td colspan='2' class='hide'>
        <div class='editMode'>
            <i class='fa fa-check-square-o' aria-hidden='true' role='presentation'></i>
            <a id='custom_save_<%= this.id %>' role='button' class='save action' data-row='<%= this.id %>' data-operation='save' href='javascript:void(0);'><%= this.save %></a>
            <i class='fa fa-times' aria-hidden='true' role='presentation'></i>
            <a id='custom_delete_<%= this.id %>' role='button' class='delete action' data-row='<%= this.id %>' href='javascript:void(0);'><%= this.deleteLabel %></a>
        </div>
    </td>
</tr>
</script>

<? /** ADD NEW ROW */ ?>
<button id="addPageSetButton"><i class="fa fa-plus-circle fa-lg"></i><?=\RightNow\Utils\Config::getMessage(ADD_MAPPING_CMD);?></button>
<script>
messages = <?= json_encode(array(
    'deleteLabel'       => \RightNow\Utils\Config::getMessage(DELETE_CMD),
    'disable'           => \RightNow\Utils\Config::getMessage(DISABLE_CMD),
    'disabled'          => \RightNow\Utils\Config::getMessage(THIS_CONFIGURATION_IS_DISABLED_MSG),
    'edit'              => \RightNow\Utils\Config::getMessage(EDIT_LBL),
    'emptyDesc'         => \RightNow\Utils\Config::getMessage(DESCRIPTION_MUST_CONTAIN_A_VALUE_MSG),
    'emptyPageSet'      => \RightNow\Utils\Config::getMessage(PAGE_SET_MUST_CONTAIN_A_VALUE_MSG),
    'enable'            => \RightNow\Utils\Config::getMessage(ENABLE_CMD),
    'invalidPageSet'    => \RightNow\Utils\Config::getMessage(PAGE_SET_VALUE_CONTAIN_SPACES_MSG),
    'newLabel'          => \RightNow\Utils\Config::getMessage(NEW_LBL),
    'save'              => \RightNow\Utils\Config::getMessage(SAVE_CMD),
    'testLink'          => '<a href="/ci/admin/overview/setmode">' . \RightNow\Utils\Config::getMessage(TEST_THE_PAGE_SET_CMD) . '</a>',
    'unlocked'          => \RightNow\Utils\Config::getMessage(CONFIG_DEPLOYED_PRODUCTION_EDITED_MSG),
    'expressionLabel'   => \RightNow\Utils\Config::getMessage(USER_AGENT_REGULAR_EXPRESSION_LBL),
    'descriptionLabel'  => \RightNow\Utils\Config::getMessage(DESCRIPTION_LBL),
    'pageSetLabel'      => \RightNow\Utils\Config::getMessage(PAGE_SET_LBL),
)) ?>;
</script>
