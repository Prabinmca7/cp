<div id="rn_<?=$this->instanceID;?>" class="<?= $this->classList ?>">
    <rn:block id="top"/>
<? if ($this->data['attrs']['label']): ?>
    <rn:block id="label">
    <span class="rn_DataLabel"><?=$this->data['attrs']['label'];?> </span>
    </rn:block>
<? endif; ?>
<? if($this->data['value']): ?>
    <rn:block id="preList"/>
<? foreach($this->data['value'] as $thread): ?>
    <?
        \RightNow\Libraries\Decorator::add($thread, 'Present/IncidentThreadPresenter');
        // This is not a public note, so it should not be displayed.
        if ($thread->IncidentThreadPresenter->isPrivate()) continue;

        $subclass = $thread->IncidentThreadPresenter->isCustomerEntry() ? 'rn_Customer' : '';
    ?>
    <rn:block id="preListItem"/>
    <div class="rn_ThreadHeader <?=$subclass?>">
        <rn:block id="preThreadHeader"/>
        <span class="rn_ThreadAuthor">
            <rn:block id="threadAuthor">
            <?= $this->helper->getThreadAuthorInfo($thread) ?>
            </rn:block>
        </span>
        <span class="rn_ThreadTime">
            <rn:block id="threadTime">
            <?= $thread->IncidentThreadPresenter->formattedCreationTime($this->data['attrs']['highlight']) ?>
            </rn:block>
        </span>
        <rn:block id="postThreadHeader"/>
    </div>
    <div class="rn_ThreadContent">
        <rn:block id="threadContent">
        <?= $thread->IncidentThreadPresenter->formattedEntry($this->data['attrs']['highlight']) ?>
        </rn:block>
    </div>
    <rn:block id="postListItem"/>
<? endforeach; ?>
    <rn:block id="postList"/>
<? endif; ?>
    <rn:block id="bottom"/>
</div>
