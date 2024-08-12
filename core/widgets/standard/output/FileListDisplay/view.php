<div id="rn_<?=$this->instanceID;?>" class="<?= $this->classList ?>">
    <? if (count($this->data['value'])): ?>
        <rn:block id="top"/>
        <? if ($this->data['attrs']['label']): ?>
            <rn:block id="label">
            <span class="rn_DataLabel"><?=$this->data['attrs']['label'];?></span>
            </rn:block>
        <? endif; ?>
        <div class="rn_DataValue<?=$this->data['wrapClass']?>">
            <rn:block id="preList"/>
            <ul>
            <? foreach ($this->data['attachments'] as $attachment): ?>
                <rn:block id="listItem">
                <li>
                    <rn:block id="topListItem"/>
                    <? $rel = $this->data['displayingSocialAttachments'] ? 'rel=nofollow ' : ''; ?>
                    <a href="<?=$attachment->AttachmentUrl;?>" <?=$rel;?>target="<?= $attachment->Target ?>">
                        <rn:block id="icon">
                        <?if($attachment->ThumbnailUrl && $attachment->ThumbnailScreenReaderText):?>
                            <img src='<?=$attachment->ThumbnailUrl;?>' class='rn_FileTypeImageThumbnail' alt='<?=\RightNow\Utils\Config::getMessage(THUMBNAIL_FOR_ATTACHED_IMAGE_MSG);?>' />
                            <span class='rn_ScreenReaderOnly'><?=$attachment->ThumbnailScreenReaderText;?></span>
                        <?else:?>
                            <?=$attachment->Icon;?>
                        <?endif;?>
                        </rn:block>
                        <rn:block id="fileName">
                        <?= \RightNow\Utils\Text::escapeHtml($attachment->Name ?: $attachment->FileName) ?>
                        </rn:block>
                    </a>
                   <? if ($this->data['attrs']['display_file_size']): ?>
                    <rn:block id="fileSize">
                    <span class="rn_FileSize">(<?=$attachment->ReadableSize;?>)</span>
                    </rn:block>
                   <? endif; ?>
                    <rn:block id="bottomListItem"/>
                </li>
                </rn:block>
            <? endforeach; ?>
            </ul>
            <rn:block id="postList"/>
       </div>
       <rn:block id="bottom"/>
    <? endif; ?>
</div>
