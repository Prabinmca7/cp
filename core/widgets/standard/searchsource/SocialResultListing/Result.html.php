<rn:block id="listItemContent">

<div class="rn_AuthorAvatar">
    <?= $this->render('Partials.Social.Avatar', $this->helper('Social')->defaultAvatarArgs($result->SocialSearch->author, array(
        'size' => isset($this->data['attrs']['avatar_size']) ? $this->data['attrs']['avatar_size'] : 'medium',
        'target' => $this->data['attrs']['target'],
    ))) ?>
</div>
<div class="rn_<?= $result->type ?>_QuestionResult">
    <h3><a href="<?= $result->url ?>" target="<?= $this->data['attrs']['target'] ?>"><?= $this->helper->highlightTitle($result->text, ($this->data['attrs']['highlight'] && $query) ? $query : $this->data['attrs']['highlight'], $this->data['attrs']['link_truncate_size']) ?></a></h3>

    <? if(!is_null($result->summary)): ?>
    <div class="rn_Summary">
        <?= $this->helper->formatSummary($result->summary, ($this->data['attrs']['highlight'] && $query) ? $query : $this->data['attrs']['highlight'], $this->data['attrs']['truncate_size']) ?>
    </div>
    <? endif; ?>
    
    <div class="rn_AdditionalInfo">
        <? $metdataElements = $this->helper->getMetadataElements($this->data['attrs']['show_metadata']);
           if (!empty($metdataElements)): ?>
            <div class="rn_Counts">
                <? foreach($metdataElements as $metadataElement): ?>
                    <? if($result->SocialSearch->{$metadataElement['elementName']}): ?>
                        <div class="rn_<?= ucfirst($metadataElement['elementName']) ?>">
                            <?= $result->SocialSearch->{$metadataElement['elementName']} ?>
                            <?= $result->SocialSearch->{$metadataElement['elementName']} === 1 ? \RightNow\Utils\Config::getMessage($metadataElement['labelForSingleElement']) : \RightNow\Utils\Config::getMessage($metadataElement['labelForMultipleElements']) ?>
                        </div>
                    <? endif; ?>
                <? endforeach; ?>
            </div>
        <? endif; ?>
        <? if ($this->data['attrs']['show_dates']): ?>
            <div class="rn_Timestamps">
                <?= \RightNow\Utils\Config::getMessage(CREATED_LBL) ?> <time itemprop='dateCreated' datetime=<?= date('Y-m-d', $result->created)?> ><?= $this->helper->formatDate($result->created) ?></time>
                <? if ($result->updated !== $result->created): ?>
                    <?= \RightNow\Utils\Config::getMessage(UPDATED_LBL) ?> <time itemprop='dateUpdated' datetime=<?= date('Y-m-d', $result->updated)?> ><?= $this->helper->formatDate($result->updated) ?></time>
                <? endif; ?> 
            </div>
        <? endif; ?>
    </div>
</div>
</rn:block>
