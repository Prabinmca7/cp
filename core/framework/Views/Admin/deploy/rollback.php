<? use RightNow\Utils\Text;?>

<? if ($rollbackDisabled): ?>
    <h2><?= $rollbackLabel ?></h2>
    <div class="info"><?= $reason ?></div>
<? else: ?>
<div>
    <h2><?=$rollbackLabel;?></h2>
    <div class="box">
        <?=$buttons;?>
        <div id="contentsContainer">
            <div id="statusContainer"></div>
            <div id="responseContainer" class="underlinedLink rn_ResponseContainer">
                <?= $warningLabel ?>
                <div class="table">
                    <?= $rollbackDetails ?>
                </div>
                <div class="lighterShadedBox rn_CommentArea">
                    <?= $comment ?>
                </div>
            </div>
        </div>
        <div id="submitArea" class="lastStep">
            <button type="submit" id="rollbackSubmit" class="warning" title="<?=$rollbackTitle;?>">
                <?=$rollbackLabel;?>
            </button>
            <label for="rollbackSubmit"><?=$rollbackTitle;?></label>
        </div>
    </div>
</div>
<? endif; ?>
<script>
var rollbackLabels = <?= json_encode(array(
    'accountID'    => $accountID,
    'deployType'   => $rollbackLabels,
    'confirmLabel' => $confirmLabel,
    'proceedLabel' => $proceedLabel,
    'loadingLabel' => $loadingLabel,
    'loadingBody'  => $loadingBody,
)) ?>;
</script>
