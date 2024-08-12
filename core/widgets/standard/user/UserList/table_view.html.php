<rn:block id="preTableView"/>
<div class="rn_UsersTableView">
    <table id="rn_<?=$this->instanceID;?>_UserListTable">
        <caption><?=$this->data['attrs']['label_caption']?></caption>
        <thead>
            <rn:block id="topHeader"/>
            <tr>
                <rn:block id="headerData">
                <th class="rn_DisplayName" scope="col"><?=$this->data['attrs']['label_user'];?></th>
                <th class="rn_Count" scope="col"><?= $this->data['attrs']['content_type'] === "questions" ? $this->data['attrs']['label_question_count'] :
                    ($this->data['attrs']['content_type'] === "comments" ? $this->data['attrs']['label_comment_count'] : $this->data['attrs']['label_best_answer_count']);?></th>
                </rn:block>
            </tr>
            <rn:block id="bottomHeader"/>
        </thead>
        <? if(count($this->data['user_data']) > 0): ?>
            <tbody>
                <rn:block id="topBody"/>
                <? foreach($this->data['user_data'] as $userDetails): ?>
                    <rn:block id="preBodyRow"/>
                    <tr>
                        <td class="rn_DisplayNameDetails">
                            <? if($this->data['attrs']['show_avatar']): ?>
                                <div class="rn_ProfileAvatar">
                                    <rn:block id="avatarData">
                                        <? if($this->data['attrs']['avatar_size'] !== 'none'): ?>
                                            <?= $this->render('Partials.Social.Avatar', $this->helper('Social')->defaultAvatarArgs($userDetails['user'], array(
                                                'size' => $this->data['attrs']['avatar_size'],
                                            ), true)) ?>
                                        <? endif;?>
                                    </rn:block>
                                </div>
                            <? endif;?>
                            <div class="rn_DisplayName">
                                <rn:block id="displayNameData">
                                    <a href="<?= '/app/' . \RightNow\Utils\Config::getConfig(CP_PUBLIC_PROFILE_URL) . '/user/' . $userDetails['user']->ID ?>"><?= $userDetails['user']->DisplayName?></a>
                                </rn:block>
                            </div>
                        </td>
                        <td class="rn_Count">
                            <rn:block id="countData">
                                <?= $userDetails['count'] ?>
                            </rn:block>
                        </td>
                    </tr>
                    <rn:block id="postBodyRow"/>
                <? endforeach; ?>
                <rn:block id="bottomBody"/>
            </tbody>
        <? endif;?>
    </table>
</div>
<rn:block id="postTableView"/>
