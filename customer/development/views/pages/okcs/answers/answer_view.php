<div class="rn_Container">
    <rn:condition config_check="OKCS_ENABLED == true">
        <div class="rn_ContentDetail">
            <rn:meta title="#rn:php:\RightNow\Libraries\SEO::getDynamicTitle('answer', \RightNow\Utils\Url::getParameter('a_id'))#" template="okcs_standard.php"  clickstream="answer_view"/>
            <div id="rn_LoadingIndicator" class="rn_Browse">
               <rn:widget path="okcs/LoadingIndicator" show_loading_indicator="true"/>
            </div>
			<div class="rn_PageTitle rn_RecordDetail">
                <rn:widget path="okcs/OkcsProductCategoryBreadcrumb" display_first_item="true"/>
                <div class="rn_OkcsAnswerAction">
                   <rn:widget path="okcs/SubscriptionButton"/>
                   <rn:widget path="okcs/FavoritesButton"/>
                   <rn:widget path="okcs/OkcsRecommendContent"/>

                </div>
                <rn:widget path="okcs/AnswerTitle">
            </div>

            <div class="rn_AnswerView">
                <rn:widget path="okcs/AnswerStatus">
                <div class="rn_SectionTitle"></div>
                <rn:widget path="okcs/AnswerContent">
            </div>
            <div class="rn_DetailTools rn_HideInPrint">
                <div class="rn_Links">
                    <rn:widget path="okcs/OkcsEmailAnswerLink" use_database_template="true"/>
                </div>
            </div>
            <rn:widget path="okcs/DocumentRating"/>
            <rn:widget path="okcs/OkcsRelatedAnswers"/>
        </div>
        <aside class="rn_SideRail" role="complementary">
            <rn:widget path="okcs/OkcsRecentlyViewedContent"/>
        </aside>
    </rn:condition>
</div>