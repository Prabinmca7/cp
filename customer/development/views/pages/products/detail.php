<rn:meta title="#rn:php:\RightNow\Libraries\SEO::getDynamicTitle('product', \RightNow\Utils\Url::getParameter('p'))#" template="standard.php" clickstream="product"/>
<div class="rn_Hero rn_Product">
    <div class="rn_HeroInner">
        <rn:widget path="navigation/ProductCategoryBreadcrumb" display_first_item="false"/>
        <div class="rn_ProductHero">
            <h1>
                <rn:condition url_parameter_check="p != null">
                    <rn:field name="ServiceProduct.Name"/>
                <rn:condition_else/>
                    #rn:msg:PRODUCT_NOT_FOUND_LBL#
                </rn:condition>
            </h1>
            <rn:widget path="output/ProductCategoryImageDisplay" label_default_image_alt_text="" label_image_alt_text="">
        </div>
        <div class="rn_ProductSubscription">
            <rn:widget path="notifications/DiscussionSubscriptionIcon" subscription_type="Product" label_subscribe="#rn:msg:SUBSCRIBE_TO_DISCUSSIONS_LBL#" label_unsubscribe="#rn:msg:SUBSCRIBED_TO_DISCUSSIONS_LBL#" label_subscribe_title="#rn:msg:RECEIVE_NOTIF_DISC_ADD_DATE_TH_PRODUCT_MSG#" label_unsubscribe_title="#rn:msg:ALREADY_SUB_RECV_NOTIF_SSS_UNSUBSCRIBE_MSG#"/>
        </div>
    </div>
</div>

<div class="rn_PageContent rn_Product">
    <div class="rn_Container">
        <rn:widget path="navigation/VisualProductCategorySelector" show_sub_items_for="#rn:url_param_value:p#" numbered_pagination="true"/>
    </div>

    <div class="rn_PopularKB">
        <div class="rn_Container">
            <div class="rn_HeaderContainer">
                <h2>#rn:msg:POPULAR_PUBLISHED_ANSWERS_LBL#</h2>
                <rn:widget path="knowledgebase/RssIcon" />
            </div>
            <rn:widget path="reports/TopAnswers" show_excerpt="true" per_page="10" product_filter_id="#rn:url_param_value:p#"/>
            <a class="rn_AnswersLink" href="/app/answers/list/p/#rn:url_param_value:p#/#rn:session#">#rn:msg:SHOW_MORE_PUBLISHED_ANSWERS_FOR_LBL# <rn:field name="ServiceProduct.Name"/></a>
        </div>
    </div>

    <div class="rn_RelatedSocial">
        <div class="rn_Container">
            <h2>#rn:msg:COMMUNITY_QUESTIONS_LBL#</h2>
            <rn:widget path="discussion/QuestionList" show_sub_items_for="#rn:url_param_value:p#"/>
        </div>
    </div>

    <div class="rn_PopularSocial">
        <div class="rn_Container">
            <div class="rn_HeaderContainer">
                <h2>#rn:msg:RECENTLY_ANSWERED_COMMUNITY_QUESTIONS_LBL#</h2>
                <rn:widget path="knowledgebase/RssIcon" feed_title="#rn:msg:COMMUNITY_RSS_FEED_LBL#" object_type="CommunityQuestion" prodcat_type="Product"/>
            </div>
            <rn:widget path="discussion/RecentlyAnsweredQuestions" product_filter="#rn:url_param_value:p#"/>
            <a class="rn_DiscussionsLink" href="/app/social/questions/list/kw/*/p/#rn:url_param_value:p#/#rn:session#">#rn:msg:SHOW_MORE_COMMUNITY_DISCUSSIONS_FOR_LBL# <rn:field name="ServiceProduct.Name"/></a>
        </div>
    </div>
</div>
