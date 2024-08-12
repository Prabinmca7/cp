<div class="rn_Container">
    <rn:condition config_check="OKCS_ENABLED == true">
        <rn:meta title="#rn:php:\RightNow\Libraries\SEO::getDynamicTitle('answer', \RightNow\Utils\Url::getParameter('a_id'))#" template="okcs_standard.php"  clickstream="answer_view"/>
        <div class="rn_OkcsAnswerAction">
           <rn:widget path="okcs/SubscriptionButton"/>
           <rn:widget path="okcs/OkcsRecommendContent"/>
        </div>
        <div class="rn_AnswerView">
            <rn:widget path="okcs/AnswerTitle">
            <rn:widget path="okcs/AnswerStatus">
            <div class="rn_SectionTitle"></div>
            <rn:widget path="okcs/AnswerContent">
        </div>
        <rn:widget path="okcs/DocumentRating"/>
        <rn:widget path="okcs/OkcsRelatedAnswers"/>
    </rn:condition>
</div>