<rn:meta title="#rn:php:\RightNow\Libraries\SEO::getDynamicTitle('category', \RightNow\Utils\Url::getParameter('c'))#" template="mobile.php" clickstream="category_view"/>
<div class="rn_Hero rn_Product">
    <div class="rn_HeroInner">
        <rn:widget path="navigation/ProductCategoryBreadcrumb" type="category" link_url="/app/#rn:config:CP_CATEGORIES_DETAIL_URL#" display_first_item="false"/>
        <div class="rn_ProductHero">
            <h1>
                <rn:condition url_parameter_check="c != null">
                    <rn:field name="ServiceCategory.Name"/>
                <rn:condition_else/>
                    #rn:msg:CATEGORY_NOT_FOUND_LBL#
                </rn:condition>
            </h1>
            <rn:widget path="output/ProductCategoryImageDisplay" type="category" label_default_image_alt_text="" label_image_alt_text="">
        </div>
        <div class="rn_ProductSubscription">
            <rn:widget path="notifications/DiscussionSubscriptionIcon" subscription_type="Category" label_subscribe="#rn:msg:SUBSCRIBE_TO_DISCUSSIONS_LBL#" label_unsubscribe="#rn:msg:SUBSCRIBED_TO_DISCUSSIONS_LBL#" label_subscribe_title="#rn:msg:RECEIVE_NOTIF_DISC_ADD_DATE_TH_CATEGORY_MSG#" label_unsubscribe_title="#rn:msg:ALREADY_SUB_RECV_NOTIF_CAT_MSG#"/>
        </div>
    </div>
</div>

<div class="rn_PageContent rn_Product">
    <div class="rn_Container">
        <rn:widget path="navigation/VisualProductCategorySelector" type="category" landing_page_url="/app/#rn:config:CP_CATEGORIES_DETAIL_URL#" show_sub_items_for="#rn:url_param_value:c#" label_back_navigation="" layout="none" numbered_pagination="true"/>
    </div>

    <div class="rn_PopularKB">
        <div class="rn_Container">
            <h2>#rn:msg:POPULAR_PUBLISHED_ANSWERS_LBL#</h2>
            <rn:widget path="reports/TopAnswers" show_excerpt="false" per_page="10" category_filter_id="#rn:url_param_value:c#"/>
            <a class="rn_AnswersLink" href="/app/social/questions/list/kw/*/c/#rn:url_param_value:c#/#rn:session#">#rn:msg:SHOW_MORE_COMMUNITY_DISCUSSIONS_FOR_LBL# <rn:field name="ServiceCategory.Name"/></a>
        </div>
    </div>

    <div class="rn_RelatedSocial">
        <div class="rn_Container">
            <h2>#rn:msg:COMMUNITY_QUESTIONS_LBL#</h2>
            <rn:widget path="discussion/QuestionList" type="category" show_sub_items_for="#rn:url_param_value:c#"/>
        </div>
    </div>

    <div class="rn_PopularSocial">
        <div class="rn_Container">
            <h2>#rn:msg:RECENTLY_ANSWERED_COMMUNITY_QUESTIONS_LBL#</h2>
            <rn:widget path="discussion/RecentlyAnsweredQuestions" category_filter="#rn:url_param_value:c#" avatar_size="small" display_answers="false" maximum_questions="5"/>
            <a class="rn_DiscussionsLink" href="/app/social/questions/list/kw/*/c/#rn:url_param_value:c#/#rn:session#">#rn:msg:SHOW_MORE_COMMUNITY_DISCUSSIONS_FOR_LBL# <rn:field name="ServiceCategory.Name"/></a>
        </div>
    </div>
</div>
