<h2><?= \RightNow\Utils\Config::getMessage(SANDBOXED_CONFIGURATIONS_LBL) ?></h2>
<p><?= \RightNow\Utils\Config::getMessage(SANDBOXED_CUST_PORTAL_MSG) ?></p>
<table class="rn_Table" summary="<?= \RightNow\Utils\Config::getMessage(SANDBOXED_CONFIGS_VALS_SITE_LBL) ?>">
    <tr>
    <?foreach($headers as $header):?>
        <th><?=$header;?></th>
    <?endforeach;?>
    </tr>
    <?foreach($configurations as $values):?>
        <tr>
        <td><?=$values['displayName'];?></td>
        <td class="small"><?=$values['configName'];?></td>
        <?foreach($values['values'] as $value):?>
            <td><?=$value === true ? '1' : ($value === false ? '0' : $value);?></td>
        <?endforeach;?>
        </tr>
    <?endforeach;?>
</table>