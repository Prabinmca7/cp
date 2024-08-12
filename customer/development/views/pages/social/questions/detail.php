<rn:meta title="#rn:php:\RightNow\Libraries\SEO::getDynamicTitle('question', \RightNow\Utils\Url::getParameter('qid'))#" template="standard.php" clickstream="question_view"/>
<div class="rn_Container">
    <div class="rn_ContentDetail">
        <div class="rn_PageContent rn_RecordDetail" itemscope itemtype="http://schema.org/Question">
                <rn:condition flashdata_value_for="info">
                    <div class="rn_MessageBox rn_InfoMessage">
                        #rn:flashdata:info#
                    </div>
                </rn:condition>

            <rn:widget path="navigation/ProductCategoryBreadcrumb"/>

            <div class="rn_Container rn_PrimaryQuestionContent">
                <rn:widget path="discussion/QuestionStatus"/>
                <rn:widget path="discussion/QuestionDetail" sub:prodcat:verify_permissions="Create"/>
                <div class="rn_DetailTools rn_AdditionalInfo rn_HideInPrint">
                    <rn:widget path="utils/EmailAnswerLink" object_type="question" label_dialog_title="#rn:msg:EMAIL_DISCUSSION_LBL#" label_tooltip="#rn:msg:EMAIL_A_LINK_TO_THIS_DISCUSSION_LBL#"/>
                    <rn:widget path="notifications/DiscussionSubscriptionIcon"/>
                    <rn:widget path="utils/PrintPageLink"/>
                    <rn:widget path="utils/SocialBookmarkLink" object_type="question"/>
                </div>
            </div>

            <div class="rn_SecondaryQuestionContent">
                <div class="rn_Container">
                    <rn:widget path="discussion/BestAnswerDisplay"/>
                </div>
            </div>

            <div class="rn_Container">
                <rn:widget path="discussion/QuestionComments"/>
            </div>
            <rn:widget path="navigation/DiscussionPagination"/>
        </div>
    </div>

    <div class="rn_SideRail">
        <rn:widget path="utils/ContactUs" />
        <rn:widget path="discussion/RecentlyViewedContent" />
        <rn:widget path="discussion/RelatedKnowledgebaseAnswers" limit="4" />
    </div>
</div>
