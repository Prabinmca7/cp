<div>
    <h2><?=$promoteLabel;?></h2>
    <div class="box">
        <?=$buttons;?>
        <div id="contentsContainer">
            <div id="statusContainer"></div>
            <div id="responseContainer" class="rn_ResponseContainer">
                <div id="fileTable" class="table"><?=$fileTable;?></div>
                <div id="versionTable" class="table"><?=$versionsTable;?></div>
                <div class='lighterShadedBox'>
                    <div id="commentArea" class="rn_CommentArea"><?=$comment;?></div>
                </div>
            </div>
        </div>
        <div id="submitArea" class="lastStep">
            <button type="submit" id="promoteSubmit" class="warning" title="<?=$promoteTitle;?>"><?=$promoteLabel;?></button>
            <label for="promoteSubmit" class=""><?=$promoteTitle;?><br></label>
            <br>
            <div class="explain">
                <?=$backupLabel;?>
            </div>
        </div>
    </div>
</div>

<script>
var promoteLabels = <?= json_encode(array(
    'accountID'    => $accountID,
    'deployType'   => $promoteLabel,
    'confirmLabel' => $confirmLabel,
    'proceedLabel' => $proceedLabel,
    'loadingLabel' => $loadingLabel,
    'loadingBody'  => $loadingBody,
)) ?>;
</script>
