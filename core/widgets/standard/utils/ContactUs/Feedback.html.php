<div class="rn_FeedbackLink">
    <rn:widget path="feedback/SiteFeedback" label_link="#rn:php:$channelData['label']#" feedback_page_url="#rn:php:$channelData['url']#" sub_id="siteFeedback"/>
    <? if ($channelData['description']): ?>
        <div class="rn_FeedbackDescription">
            <?= $channelData['description'] ?>
        </div>
    <? endif ?>
</div>
