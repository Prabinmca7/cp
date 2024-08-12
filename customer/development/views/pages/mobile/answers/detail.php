<rn:meta title="#rn:php:\RightNow\Libraries\SEO::getDynamicTitle('answer', \RightNow\Utils\Url::getParameter('a_id'))#" template="mobile.php" answer_details="true" clickstream="answer_view"/>

<article itemscope itemtype="http://schema.org/Article" class="rn_Container">
    <div class="rn_ContentDetail">
        <div class="rn_PageTitle rn_RecordDetail">
            <rn:widget path="navigation/ProductCategoryBreadcrumb"/>
            <h1 class="rn_Summary" itemprop="name"><rn:field name="Answer.Summary" highlight="true"/></h1>
            <div class="rn_RecordInfo rn_AnswerInfo">
                #rn:msg:PUBLISHED_LBL# <span itemprop="dateCreated"><rn:field name="Answer.CreatedTime" /></span><br />
                #rn:msg:UPDATED_LBL# <span itemprop="dateModified"><rn:field name="Answer.UpdatedTime" /></span>
            </div>
            <rn:field name="Answer.Question" highlight="true"/>
        </div>

        <div class="rn_PageContent rn_RecordDetail">
            <div class="rn_RecordText rn_AnswerText" itemprop="articleBody">
                <rn:field name="Answer.Solution" highlight="true"/>
            </div>
            <rn:widget path="knowledgebase/GuidedAssistant" popup_window_url="/app/utils/guided_assistant" label_text_result="#rn:msg:PLEASE_READ_THIS_RESPONSE_MSG#"/>
            <div class="rn_FileAttach">
                <rn:widget path="output/DataDisplay" name="Answer.FileAttachments" label="#rn:msg:ATTACHMENTS_LBL#"/>
            </div>
        </div>
    </div>

    <aside class="rn_SideRail" role="complementary">
        <rn:widget path="feedback/AnswerFeedback" label_title="#rn:msg:IS_THIS_ANSWER_HELPFUL_LBL#"/>
    </aside>
</article>
