<rn:meta title="#rn:php:\RightNow\Libraries\SEO::getDynamicTitle('question', \RightNow\Utils\Url::getParameter('qid'))#" template="mobile.php" clickstream="question_view"/>
<div class="rn_Container">
    <div class="rn_ContentDetail">
        <div class="rn_PageContent rn_RecordDetail" itemscope itemtype="http://schema.org/Question">
            <div>
                <rn:condition flashdata_value_for="info">
                    <div class="rn_MessageBox rn_InfoMessage">
                        #rn:flashdata:info#
                    </div>
                </rn:condition>
            </div>

            <rn:widget path="navigation/ProductCategoryBreadcrumb" display_first_item="true"/>

            <div class="rn_Container rn_PrimaryQuestionContent">
                <rn:widget path="discussion/QuestionStatus"/>
                <rn:widget path="discussion/QuestionDetail" mobile_enabled="true" sub:prodcat:verify_permissions="Create" />
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
</div>
