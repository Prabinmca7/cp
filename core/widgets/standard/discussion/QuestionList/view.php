<div id="rn_<?= $this->instanceID ?>" class="<?= $this->classList ?>">
    <rn:block id="top"/>
        <? if(empty($this->data['result'])): ?>
            <?= $this->data['attrs']['label_no_recent_questions']; ?>
        <? else: ?>
            <table id="rn_<?=$this->instanceID;?>_QuestionListTable">
                <caption><?=$this->data['attrs']['label_caption']?></caption>
                <thead>
                    <rn:block id="topHeader"/>
                    <tr>
                        <rn:block id="headerData">
                        <th scope="col"><?=$this->data['questionHeader'];?></th>
                        <? foreach($this->data['attrs']['show_columns'] as $header):?>
                            <th scope="col"><?=$this->data['tableHeaders'][$header];?></th>
                        <? endforeach;?>
                        </rn:block>
                    </tr>
                    <rn:block id="bottomHeader"/>
                </thead>
                <tbody>
                    <? if(count($this->data['result']) > 0): ?>
                        <rn:block id="topBody"/>
                        <? foreach($this->data['result'] as $data): ?>
                            <rn:block id="preBodyRow"/>
                            <tr>
                                <th scope="row">
                                    <a href="/app/social/questions/detail/qid/<?= $data['id'] ?>"><?= $data['subject'] ?></a>
                                </th>
                                <? foreach($this->data['attrs']['show_columns'] as $metadata):
                                    $noActivity = ($metadata === "last_activity") ? $this->data['attrs']['label_no_activity'] : 0; ?>
                                    <td>
                                        <rn:block id="columnData">
                                            <?= $data[$metadata] ? (($metadata === 'last_activity') ? \RightNow\Utils\Date::formatTimestamp($data[$metadata], \RightNow\Utils\Date::getDateFormat($this->data['attrs']['last_activity_date_format'])) : $data[$metadata]) : $noActivity ?>
                                        </rn:block>
                                    </td>
                                <? endforeach;?>
                            </tr>
                            <rn:block id="postBodyRow"/>
                        <? endforeach; ?>
                        <rn:block id="bottomBody"/>
                    <? endif;?>
                </tbody>
            </table>
        <? endif; ?>
    <rn:block id="bottom"/>
</div>