<rn:meta title="#rn:msg:UPDATE_PRODUCT_CMD#" template="standard.php" clickstream="asset_update" login_required="true" force_https="true"/>
<div id="rn_PageTitle" class="rn_AskQuestion">
    <h1>#rn:msg:UPDATE_REGISTERED_PRODUCT_CMD#</h1>
</div>
<div id="rn_PageContent" class="rn_QuestionDetail">
    <div class="rn_Padding">
        <form id="rn_UpdateProductSubmit" method="post" action="/ci/ajaxRequest/sendForm">
            <div id="rn_ErrorLocation"></div>
            <div id="rn_AdditionalInfo">
                <rn:widget path="output/DataDisplay" name="Asset.SerialNumber" label="#rn:msg:SERIAL_NUMBER_LBL#" />
                <rn:widget path="output/ProductCatalogDisplay" name="Asset.Product" label="#rn:msg:PRODUCT_LBL#"/>
                <rn:widget path="input/FormInput" name="Asset.PurchasedDate" required="true" label_input="#rn:msg:DATE_PURCHASED_LBL#"/>
            </div>
            <rn:widget path="input/FormInput" name="Asset.Name" required="true" label_input="#rn:msg:NAME_LBL#"/>
            <rn:widget path="input/FormInput" name="Asset.StatusWithType.Status" label_input="#rn:msg:STATUS_LBL#"/>
            <rn:widget path="input/FormInput" name="Asset.InstalledDate" label_input="#rn:msg:DATE_INSTALLED_LBL#"/> 
            <rn:widget path="input/FormInput" name="Asset.RetiredDate" label_input="#rn:msg:DATE_RETIRED_LBL#"/>
            <rn:widget path="input/FormInput" name="Asset.Description" label_input="#rn:msg:DESCRIPTION_LBL#"/>
            <rn:widget path="input/FormSubmit" label_button="#rn:msg:CONTINUE_ELLIPSIS_CMD#" on_success_url="/app/account/overview" error_location="rn_ErrorLocation"/>
        </form>
    </div>
</div>
