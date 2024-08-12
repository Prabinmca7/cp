<rn:meta title="#rn:msg:COMMUNITY_HOME_PAGE_LBL#" template="mobile.php" clickstream="community_home_page"/>

<div class="rn_Hero">
    <div class="rn_HeroInner">
        <div class="rn_HeroCopy">
            <h1>#rn:msg:SEARCH_FORUMS_LBL#</h1>
        </div>
        <div class="rn_SearchControls">
            <h1 class="rn_ScreenReaderOnly">#rn:msg:SEARCH_CMD#</h1>
            <form method="get" action="app/social/questions/list">
                <rn:container source_id="SocialSearch,KFSearch">
                    <div class="rn_SearchInput">
                        <rn:widget path="searchsource/SourceSearchField" allow_empty_search="true" initial_focus="true" filter_label="Keyword" filter_type="query"/>
                    </div>
                    <rn:widget path="searchsource/SourceSearchButton" endpoint="/ci/ajaxRequest/search" history_source_id="SocialSearch" search_results_url="/app/social/questions/list"/>
                </rn:container>
            </form>
        </div>
    </div>
</div>

<div class="rn_PageContent rn_CommunityHome">
    <div class="rn_Container">
        <div class="rn_ContentDetail">
            <rn:widget path="standard/utils/AnnouncementText" label_heading="#rn:msg:WELCOME_TO_OUR_NEW_COMMUNITY_LBL#" file_path="/euf/assets/others/community-announcement.txt"/>
            <div class="rn_Forums">
                <div class="rn_ForumListIcon"></div>
                <div class="rn_ForumListTitle">
                    <h2>#rn:msg:FORUMS_LBL#</h2>
                </div>
                <rn:widget path="discussion/ForumList" show_columns="question_count, last_activity" last_activity_format="short_textual"/>
            </div>
            <div class="rn_MostComments">
                <div class="rn_UserListIcon"></div>
                <div class="rn_UserListTitle">
                    <h2>#rn:msg:MOST_COMMENTS_LBL#</h2>
                </div>
                <rn:widget path="user/UserList" content_type="comments" content_display_type="table_view" avatar_size="small"/>
            </div>
            <div class="rn_RecentUsers">
                <div class="rn_StackedIcons"></div>
                <div class="rn_RecentUsersTitle">
                    <h2>#rn:msg:RECENTLY_ACTIVE_USERS_LBL#</h2>
                </div>
                <rn:widget path="user/RecentlyActiveUsers" last_active_format="short_textual" content_display_type="list_view" max_user_count="5"/>
            </div>
            <div class="rn_RecentQuestions">
                <div class="rn_StackedIcons"></div>
                <div class="rn_RecentQuestionsTitle">
                    <h2>#rn:msg:RECENTLY_ASKED_QUESTIONS_LBL#</h2>
                </div>
                <rn:widget path="discussion/RecentlyAskedQuestions" show_excerpt="true" maximum_questions="5" questions_with_comments="no_comments"/>
            </div>
        </div>
    </div>
</div>
