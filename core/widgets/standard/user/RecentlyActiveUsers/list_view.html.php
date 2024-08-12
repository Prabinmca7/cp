<rn:block id="preListView"/>
<div class="rn_RecentUsersList">
    <ul class="rn_RecentUsers">
        <? for ($i = 0; $i < count($this->data['users']); $i++): ?>
            <li class="rn_RecentUser">
                <? if(in_array('user_avatar', $this->data['attrs']['specify_list_view_metadata'])) { ?>
                    <div class="rn_RecentUserAvatar">
                        <? if($this->data['attrs']['avatar_size'] !== 'none') { ?>
                            <?= $this->render('Partials.Social.Avatar', $this->helper('Social')->defaultAvatarArgs($this->data['users'][$i]['user'], array(
                                'size' => $this->data['attrs']['avatar_size'],
                            ), true)) ?>
                        <? } ?>
                    </div>
                <? } ?>
                <? if(in_array('display_name', $this->data['attrs']['specify_list_view_metadata']) || in_array('last_active', $this->data['attrs']['specify_list_view_metadata'])) { ?>
                    <div class="rn_RecentUserDetails">
                        <? if(in_array('display_name', $this->data['attrs']['specify_list_view_metadata'])) { ?>
                            <span><a href="<?= '/app/' . \RightNow\Utils\Config::getConfig(CP_PUBLIC_PROFILE_URL) . '/user/' . $this->data['users'][$i]['user']->ID ?>"><?= $this->data['users'][$i]['user']->DisplayName ?></a></span>
                        <? } ?>
                        <? if(in_array('last_active', $this->data['attrs']['specify_list_view_metadata'])) { ?>
                            <span><?= $this->data['attrs']['last_active_label'] . " " . \RightNow\Utils\Date::formatTimestamp($this->data['users'][$i]['createdTime'], \RightNow\Utils\Date::getDateFormat($this->data['attrs']['last_active_date_format'])) ?></span>
                        <? } ?>
                    </div>
                <? } ?>
            </li>
        <? endfor; ?>
    </ul>
</div>
<rn:block id="postListView"/>
