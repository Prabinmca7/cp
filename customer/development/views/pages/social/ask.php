<rn:meta title="#rn:msg:ASK_THE_COMMUNITY_LBL#" template="standard.php" login_required="true" clickstream="question_create"/>

<div class="rn_Hero">
    <div class="rn_HeroInner">
        <div class="rn_HeroCopy">
            <h1>#rn:msg:SUBMIT_A_QUESTION_TO_SUPPORT_COMMUNITY_MSG#</h1>
        </div>
        <div class="translucent">
            <strong>#rn:msg:TIPS_COLON_LBL#</strong>
            <ul>
                <li><i class="fa fa-thumbs-up"></i> #rn:msg:INCLUDE_AS_MANY_DETAILS_AS_POSSIBLE_LBL#</li>
                <li><i class="fa fa-thumbs-up"></i> #rn:msg:SELECTING_HELP_PEOPLE_RESPOND_QUICKER_LBL#</li>
            </ul>
        </div>
        <br>
        <p>#rn:msg:NEED_HELP_FROM_A_SUPPORT_PROFESSIONAL_LBL# <a href="/app/ask#rn:session#">#rn:msg:ASK_OUR_SUPPORT_STAFF_LBL# →</a></p>
    </div>
</div>

<div class="rn_PageContent rn_AskQuestion rn_Container">
    <rn:condition is_social_user="true">
        <rn:condition is_active_social_user="false" >
            <div class="rn_MessageBox rn_ErrorMessage">#rn:msg:ACT_O_PERM_CONT_OR_CONT_MANAGER_MSG#</div>
        </rn:condition>
    </rn:condition>

    <rn:condition is_social_user="false" is_active_social_user="true">
        <form id="rn_QuestionSubmit" method="post" action="/ci/ajaxRequest/sendForm">
            <div id="rn_ErrorLocation"></div>
            <rn:widget path="input/FormInput" name="CommunityQuestion.Subject" label_input="#rn:msg:SUBJECT_LBL#" required="true" initial_focus="true"/>
            <rn:widget path="input/RichTextInput" name="CommunityQuestion.Body" label_input="#rn:msg:QUESTION_LBL#" required="true"/>
            <rn:widget path="input/ProductCategoryInput" name="CommunityQuestion.product" verify_permissions="Create"/>
            <rn:widget path="input/SocialFileAttachmentUpload"/>
            <br/>
            <rn:widget path="notifications/DiscussionAuthorSubscription"/>
            <br/>
            <rn:widget path="input/FormSubmit" label_button="#rn:msg:POST_YOUR_QUESTION_LBL#" on_success_url="/app/#rn:config:CP_SOCIAL_QUESTIONS_DETAIL_URL#" error_location="rn_ErrorLocation"/>
            <rn:condition content_viewed="2" searches_done="1">
            <rn:condition_else/>
            <rn:widget path="input/SmartAssistantDialog" label_prompt="#rn:msg:OFFICIAL_SSS_MIGHT_L_IMMEDIATELY_MSG#"/>
            </rn:condition>
        </form>

        <rn:condition is_social_user="false">
            <rn:widget path="standard/user/UserInfoDialog" display_on_page_load="true"/>
        </rn:condition>
    </rn:condition>
</div>

