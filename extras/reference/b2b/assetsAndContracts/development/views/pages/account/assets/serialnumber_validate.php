<rn:meta title="#rn:msg:REGISTER_A_PRODUCT_LBL#" template="standard.php" clickstream="asset_validate" login_required="true" force_https="true"/>
<div id="rn_PageTitle" class="rn_AskQuestion">
    <h1>#rn:msg:REGISTER_A_PRODUCT_LBL#</h1>
    <div id="rn_PageContent" class="rn_AskQuestion">
        <div class="rn_Padding">
            <div id="rn_ErrorLocation"></div>
            <rn:widget path="utils/ProgressBar" step_descriptions="#rn:msg:PRODUCT_VALIDATION_LBL#, #rn:msg:PRODUCT_REGISTRATION_LBL#"/>
            <rn:widget path="input/AssetCheck" initial_focus="true" redirect_register_asset="/app/account/assets/register_asset/step/2" hint="#rn:msg:SELECT_PRODUCT_FROM_CATALOG_LBL#" label_all_values="#rn:msg:PRODUCT_SELECTION_LBL#" error_location="rn_ErrorLocation"/>
        </div>
    </div>
</div>
