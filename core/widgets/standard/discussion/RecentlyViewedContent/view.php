<div id="rn_<?= $this->instanceID ?>" class="<?= $this->classList ?>">
<rn:block id="top">
<?if($this->data['attrs']['label_heading']):?>
    <rn:block id="title">
        <h2><?= $this->data['attrs']['label_heading'] ?></h2>
    </rn:block>
<?endif;?>
    <rn:block id="preList"/>
    <ul>
    <? foreach($this->data['previousContent'] as $content): ?>
        <rn:block id="recentlyViewedItem">
        <li class="rn_<?= $content['type'] ?>Item">
            <a href="<?= $content['url'] ?>">
                <?= $content['text'] ?>
            </a>
        </li>
        </rn:block>
    <? endforeach; ?>
    </ul>
    <rn:block id="postList"/>
<rn:block id="bottom"/>
</div>
