<? use \RightNow\Utils\Config; ?>
<div class="meta">
    <? if ($error): ?>
        <?= $error; ?>
    <? endif; ?>
    <div id="breadcrumb">
        <? if ($fields): ?>
            <?$lastField = array_pop($fields);
              $fullFieldName = '';?>
            <a href="javascript:void(0);" class="link" data-field="showObjects"><i class="fa fa-list-ul"></i>&nbsp;<?=Config::getMessage(OBJECTS_LBL);?></a>
            <? foreach ($fields as $field): ?>
                <?$fullFieldName .= ($fullFieldName ? '.' : '') . $field;?>
                <i class="fa fa-angle-right"></i>
                <a href="javascript:void(0)" class="link" data-field="<?= $fullFieldName; ?>" data-id="<?= $objectID; ?>"><?= $field; ?></a>
            <? endforeach; ?>
            <i class="fa fa-angle-right"></i>
            <?=$lastField;?>
        <? endif; ?>
    </div>
    <div style="overflow: auto;">
        <table class="objectExplorer">
            <? if ($fields && $rows): ?>
                <tr><th><?= Config::getMessage(FIELD_NAME_LBL); ?></th>
                <th><?= Config::getMessage(VALUE_LBL); ?></th>
                <th><?= Config::getMessage(FIELD_TYPE_LBL); ?></th></tr>
            <? elseif ($rows): ?>
                <tr><th><?= Config::getMessage(OBJECT_NAME_LBL); ?></th></tr>
            <? endif; ?>
        <? if (!$rows): ?>
            <tr><td><span class="gray"><?= Config::getMessage(NULL_LBL); ?></span></td></tr>
        <? else: ?>
        <? foreach ($rows as $row): ?>
            <tr>
                <td>
                    <? if (isset($row['parent']) && $row['parent']): ?>
                        &nbsp;-&nbsp;
                    <? endif; ?>
                    <? if (isset($row['link']) && $row['link']): ?>
                        <a href="javascript:void(0);" class="link" data-field="<?= $row['link']; ?>" data-id="<?= $objectID; ?>" ><?= $row['field']; ?></a>
                    <? else: ?>
                        <?=$row['field'] ?>
                    <? endif; ?>
                </td>
                <? if ($fields): ?>
                    <td>
                        <?= $row['value']; ?>
                    </td>
                    <td>
                        <? if ($connectObject = \RightNow\Utils\Text::getSubstringAfter($row['type'], $connectNamespace)): ?>
                            <i><?= $connectObject; ?></i>
                        <? else: ?>
                            <?= $row['type']; ?>
                        <? endif; ?>
                    </td>
                <? endif; ?>
            </tr>
        <? endforeach;?>
        <? endif;?>
        </table>
    </div>
</div>
