<div class="rn_Container">
    <rn:condition config_check="OKCS_ENABLED == true">
        <rn:meta title="#rn:php:\RightNow\Libraries\SEO::getDynamicTitle('answer', \RightNow\Utils\Url::getParameter('a_id'))#" template="mobile.php"  clickstream="answer_view"/>
        <rn:widget path="okcs/SubscriptionButton"/>
        <section id="rn_PageContent" class="rn_AnswerDetail">
            <div id="rn_AnswerText">
                <rn:widget path="okcs/AnswerTitle">
                <rn:widget path="okcs/AnswerStatus">
                <div class="rn_SectionTitle"></div>
                <rn:widget path="okcs/AnswerContent">
            </div>
            <rn:widget path="okcs/DocumentRating"/>
        </section>
    </rn:condition>
</div>