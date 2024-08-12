<?use RightNow\Utils\Text;?>
<?= isset($title) ? $title : '';?>
<?if ($buttons):?>
<div class="shadedBox">
    <div class="steps">
        <ul>
        <? foreach ($buttons as $button => $info): ?>
        <li><a href="javascript:void(0);" id="<?=$info['id'];?>" data-next-step="<?=$info['id'];?>" class="<?=$info['className'];?>" title="<?=$info['title'];?>"<?=$info['disabled'];?>><?=$info['label'];?></a></li>
        <? endforeach; ?>
        </ul>
    </div>
</div>
<?endif;?>
<script>
var messages = <?= $messages ?>,
    postData = <?= $postData ?>;
</script>
