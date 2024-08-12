<rn:meta title="#rn:msg:RECOMMENDATIONS_LBL#" template="okcs_standard.php" login_required="true" force_https="true"/>
<div class="rn_Hero">
    <div class="rn_Container">
        <h1>#rn:msg:RECOMMENDATIONS_LBL#</h1>
    </div>
</div>
<div class="rn_PageContent rn_AccountOverview rn_Container">
    <rn:container source_id="OKCSBrowse">
        <h2><a class="rn_Profile">#rn:msg:MY_RECOMMENDATIONS_LBL#</a></h2>
        <div id="rn_OkcsManageRecommendations">
            <rn:widget path="okcs/OkcsManageRecommendations" per_page="10"/>
            <div class="rn_FloatRight">
                <rn:widget path="okcs/OkcsPagination" data_source="authoring_recommendations"/>
            </div>
        </div>
    </rn:container>
</div>
