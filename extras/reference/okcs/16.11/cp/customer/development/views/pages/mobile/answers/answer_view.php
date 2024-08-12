<div class="rn_Container">
    <rn:condition config_check="OKCS_ENABLED == true">
        <rn:meta title="#rn:php:\RightNow\Libraries\SEO::getDynamicTitle('answer', \RightNow\Utils\Url::getParameter('a_id'))#" template="okcs_mobile.php"  clickstream="answer_view"/>
        <rn:widget path="okcs/OkcsRecommendContent"/>
        <rn:widget path="okcs/SubscriptionButton"/>
        <section id="rn_PageContent" class="rn_AnswerDetail">
            <div id="rn_AnswerText">
                <rn:widget path="okcs/AnswerTitle">
                <rn:widget path="okcs/AnswerStatus">
                <div class="rn_SectionTitle"></div>
                <rn:widget path="okcs/AnswerContent">
            </div>
            <div class="rn_DetailTools rn_HideInPrint">
                <div class="rn_Links">
                    <rn:widget path="okcs/OkcsEmailAnswerLink" />
                </div>
            </div>
            <rn:widget path="okcs/DocumentRating"/>
            <rn:widget path="okcs/OkcsRelatedAnswers"/>
        </section>
    </rn:condition>
</div>