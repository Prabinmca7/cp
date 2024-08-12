<?$this->addHeadContent('<link rel="alternate" type="application/rss+xml" title="' . $this->data['attrs']['feed_title'] . '" href="' . \RightNow\Utils\Url::getCachedContentServer() . $this->data['href'] . $this->data['feedParams'] . '" />');?>

<span id="rn_<?=$this->instanceID;?>" class="<?= $this->classList ?>">
    <rn:block id="top"/>
    <rn:block id="link">
        <a class="rn_RssImgLink" title="<?= $this->data['attrs']['feed_title']; ?>" href="<?= \RightNow\Utils\Url::getCachedContentServer() . $this->data['href'] ?><?= $this->data['feedParams'] ?>"><span class="rn_ScreenReaderOnly"><?= $this->data['attrs']['feed_title']; ?></span></a>
    </rn:block>
    <rn:block id="bottom"/>
</span>
