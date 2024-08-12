<rn:meta title="#rn:msg:REGISTER_A_PRODUCT_LBL#" template="standard.php" clickstream="asset_create" login_required="true" force_https="true"/>
<div id="rn_PageTitle" class="rn_AskQuestion">
    <h1>#rn:msg:REGISTER_A_PRODUCT_LBL#</h1>
</div>
<div id="rn_PageContent" class="rn_QuestionDetail">
    <div class="rn_Padding">
        <form id="rn_AssetRegisterSubmit" method="post" action="/ci/ajaxRequest/sendForm">
            <div id="rn_ErrorLocation"></div>            
            <rn:widget path="utils/ProgressBar" current_step="#rn:url_param_value:step#" step_descriptions="#rn:msg:PRODUCT_VALIDATION_LBL#, #rn:msg:PRODUCT_REGISTRATION_LBL#"/>
            <div id="rn_AdditionalInfo">
                <rn:widget path="output/DataDisplay" name="Asset.SerialNumber" label="#rn:msg:SERIAL_NUMBER_LBL#" />
                <rn:widget path="output/ProductCatalogDisplay" name="Asset.Product" label="#rn:msg:PRODUCT_LBL#"/>
            </div>

            <rn:widget path="input/FormInput" name="Asset.Name" required="true" label_input="#rn:msg:NAME_LBL#"/>
            <rn:widget path="input/FormInput" name="Asset.StatusWithType.Status" label_input="#rn:msg:STATUS_LBL#" default_value="#rn:php:ASSET_ACTIVE#"/>
            <rn:widget path="input/FormInput" name="Asset.PurchasedDate" required="true" label_input="#rn:msg:DATE_PURCHASED_LBL#"/>
            <rn:widget path="input/FormInput" name="Asset.InstalledDate" label_input="#rn:msg:DATE_INSTALLED_LBL#"/> 
            <rn:widget path="input/FormInput" name="Asset.RetiredDate" label_input="#rn:msg:DATE_RETIRED_LBL#"/>
            <rn:widget path="input/FormInput" name="Asset.Description" label_input="#rn:msg:DESCRIPTION_LBL#" readonly="yes"/>
            <rn:widget path="input/FormSubmit" label_button="#rn:msg:CONTINUE_ELLIPSIS_CMD#" on_success_url="/app/account/assets/asset_registration_confirmation/step/3" error_location="rn_ErrorLocation"/>
        </form>
    </div>
</div>
