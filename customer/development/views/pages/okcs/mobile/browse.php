<rn:meta title="#rn:msg:BROWSE1_AB_HDG#" template="okcs_mobile.php" clickstream="answer_list"/>
<rn:condition config_check="OKCS_ENABLED == true">
<div id="rn_PageTitle" class="rn_Home">
    <div class="rn_Hero">
        <div class="rn_HeroInner">
            <div class="rn_OkcsMobileBrowseHeader">
                <h1 class="rn_ScreenReaderOnly"><rn:page_title/></h1>
                <rn:container source_id="OKCSBrowse">
                    <h2 id="rn_AccordTriggerContentType" class="rn_Collapsed"><a href="javascript:void(0);" role="button">#rn:msg:CONTENT_TYPE_LBL#<span class="rn_ButtonOff"></span></a></h2>
                    <h2 id="rn_AccordTriggerProduct" class="rn_Collapsed"><a href="javascript:void(0);" role="button">#rn:msg:PRODUCT_LBL#<span class="rn_ButtonOff"></span></a></h2>
                    <h2 id="rn_AccordTriggerCategory" class="rn_Collapsed"><a href="javascript:void(0);" role="button">#rn:msg:CATEGORY_LBL#<span class="rn_ButtonOff"></span></a></h2>
                       <div class="rn_ClearBoth">
                        <div id="rn_ContainerContentType" class="rn_Hidden">
                            <rn:widget path="okcs/ContentType" list_display="vertical" toggle_selection="true" toggle="rn_AccordTriggerContentType" item_to_toggle="rn_ContainerContentType"/>
                        </div>
                        <div id="rn_ContainerProduct" class="rn_Hidden">
                            <rn:widget path="okcs/OkcsProductCategorySearchFilter" filter_type="products" toggle_selection="true" toggle="rn_AccordTriggerProduct" item_to_toggle="rn_ContainerProduct" view_type="explorer"/>
                        </div>
                        <div id="rn_ContainerCategory" class="rn_Hidden">
                            <rn:widget path="okcs/OkcsProductCategorySearchFilter" filter_type="categories" toggle_selection="true" toggle="rn_AccordTriggerCategory" item_to_toggle="rn_ContainerCategory" view_type="explorer"/>
                        </div>
                    </div>
                </div>
           </div>
    </div>  
</div>
<div class="rn_Container">
<section id="rn_PageContent" class="rn_Home">

        <div id="rn_LoadingIndicator" class="rn_Browse">
           <rn:widget path="okcs/LoadingIndicator"/>
        </div>

        <div id="rn_PageContentArticles">
            <div class="rn_ResultPadding">
                <div id="rn_Browse_Loading"></div>
                <div id="rn_OkcsAnswerList">
                    <div class="rn_Report">
                        <rn:widget path="okcs/OkcsRecommendContent"/>
                        <rn:widget path="okcs/AnswerList" view_type="list" show_headers="false" per_page="5" target="_self"/>
                    </div>
                    <div class="rn_FloatRight">
                        <rn:widget path="okcs/OkcsPagination"/>
                    </div>
                </div>
            </div>
        </div>
    </rn:container>
</section>
</div>
</rn:condition>