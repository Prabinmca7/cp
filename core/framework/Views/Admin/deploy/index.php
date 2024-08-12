<h2><?=\RightNow\Utils\Config::getMessage(DEPLOY_LBL);?></h2>

<?foreach($deployMenuItems as $href => $data):
    $link = "<a href=\"$href\">{$data['label']}</a>";
    $description = $data['description'];
    if ($data['disabled'] === true) {
        $link = "<span class=\"disabledText\">{$data['label']}</span>";
        $description = "<span class=\"disabledText\">{$data['description']}</span>";
    }
    ?>
    <h3><span title="<?=htmlspecialchars($data['description']);?>"><?=$link;?></span></h3>
    <?
    echo $description;
    if ($data['lastRunDate']){
        $link = sprintf("<a href=\"/ci/admin/logs/viewDeployLog/%s\" target=\"_blank\">{$data['label']}</a>", strtolower($data['logName']));
        printf("<br><small>%s</small>", sprintf($lastEventLabel, $link, $data['lastRunDate']));
    }
    ?>
    <br><br>
<?endforeach;?>
