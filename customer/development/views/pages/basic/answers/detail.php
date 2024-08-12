<rn:meta title="#rn:php:\RightNow\Libraries\SEO::getDynamicTitle('answer', \RightNow\Utils\Url::getParameter('a_id'))#" template="basic.php" answer_details="true" clickstream="answer_view"/>

<rn:widget path="input/BasicFormStatusDisplay"/>

<div>
    <h1><rn:field name="Answer.Summary" highlight="true"/></h1>
    <rn:field name="Answer.Question" highlight="true"/>
</div>

<div>
    <div>
        <rn:field name="Answer.Solution" highlight="true"/>
    </div><br />
    <div>
        <rn:widget path="output/DataDisplay" name="Answer.FileAttachments" left_justify="true" label="#rn:msg:ATTACHMENTS_LBL#"/>
    </div><br />
    <div>
        <b>#rn:msg:PUBLISHED_LBL#</b> <rn:field name="Answer.CreatedTime" /><br />
        <b>#rn:msg:UPDATED_LBL#</b> <rn:field name="Answer.UpdatedTime" /><br />
    </div>
</div>

<rn:widget path="feedback/BasicAnswerFeedback" on_success_url="/app/answers/submit_feedback"/>
<rn:widget path="knowledgebase/RelatedAnswers" />
