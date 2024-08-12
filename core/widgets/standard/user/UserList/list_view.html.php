<rn:block id="preListView"/>
<div class="rn_UsersListView">
    <ul class="rn_Users">
        <? foreach($this->data['user_data'] as $userDetails): ?>
            <li class="rn_UserItem">
                <? if($this->data['attrs']['show_avatar']): ?>
                    <div class="rn_UserListAvatar">
                        <? if($this->data['attrs']['avatar_size'] !== 'none'): ?>
                            <?= $this->render('Partials.Social.Avatar', $this->helper('Social')->defaultAvatarArgs($userDetails['user'], array(
                                'size' => $this->data['attrs']['avatar_size'],
                            ), true)) ?>
                        <? endif;?>
                    </div>
                <? endif;?>
                <div class="rn_UserListDetails">
                    <span><a href="<?= '/app/' . \RightNow\Utils\Config::getConfig(CP_PUBLIC_PROFILE_URL) . '/user/' . $userDetails['user']->ID ?>"><?= $userDetails['user']->DisplayName?></a></span>
                    <span><?= $this->data['attrs']['label_count'] . " " . $userDetails['count'] ?></span>
                </div>
            </li>
        <? endforeach; ?>
    </ul>
</div>
<rn:block id="postListView"/>