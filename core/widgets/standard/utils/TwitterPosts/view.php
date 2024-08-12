<div id="rn_<?= $this->instanceID ?>" class="<?= $this->classList ?>">
    <rn:block id="top"/>
    <div class="rn_displayTimeline" aria-live="polite">
        <a class="twitter-timeline"
           href="<?=$this->data['twitter_link'];?>"
           data-width="<?=$this->data['attrs']['tweets_container_width'];?>"
           data-height="<?=$this->data['attrs']['tweets_container_height'];?>"
           <? if($this->data['attrs']['twitter_widget_id']): ?>
           data-widget-id="<?=$this->data['attrs']['twitter_widget_id'];?>"
           <? endif; ?>
           <? if($this->data['attrs']['data_tweet_limit']): ?>
           data-tweet-limit="<?=$this->data['attrs']['data_tweet_limit'];?>"
           <? endif; ?>><?=$this->data['attrs']['label_twitter_link'];?></a>
    </div>
    <rn:block id="bottom"/>
</div>