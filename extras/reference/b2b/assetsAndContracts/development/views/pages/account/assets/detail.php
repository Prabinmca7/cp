<rn:meta title="#rn:php:\RightNow\Libraries\SEO::getDynamicTitle('asset', \RightNow\Utils\Url::getParameter('asset_id'))#" template="standard.php" login_required="true" force_https="true" clickstream="asset_detail"/>
<div id="rn_PageTitle" class="rn_Account">
    <h1><rn:field name="Asset.Name" highlight="true"/></h1>
</div>
<div id="rn_PageContent" class="rn_Overview">
    <h2 class="rn_HeadingBar">#rn:msg:REGISTERED_PRODUCT_LBL#</h2>
    <div id="rn_AdditionalInfo">
        <rn:widget path="output/DataDisplay" name="Asset.Name" label="#rn:msg:NAME_LBL#"/>
        <rn:widget path="output/ProductCatalogDisplay" name="Asset.Product" label="#rn:msg:PRODUCT_LBL#"/> 
        <rn:widget path="output/DataDisplay" name="Asset.SerialNumber" label="#rn:msg:SERIAL_NUMBER_LBL#"/>
        <rn:widget path="output/DataDisplay" name="Asset.PurchasedDate" label="#rn:msg:PURCHASED_DATE_LBL#"/>
        <rn:widget path="output/DataDisplay" name="Asset.InstalledDate" label="#rn:msg:INSTALLED_DATE_LBL#"/> 
        <rn:widget path="output/DataDisplay" name="Asset.RetiredDate" label="#rn:msg:RETIRED_DATE_LBL#"/>
        <rn:widget path="output/DataDisplay" name="Asset.StatusWithType.Status" label="#rn:msg:STATUS_LBL#"/> 
        <rn:widget path="output/DataDisplay" name="Asset.Description" label="#rn:msg:DESCRIPTION_LBL#"/>    
    </div>
    <div class="rn_Questions">   
        <?if(\RightNow\Utils\Framework::isContactAllowedToUpdateAsset()):?>
            <a class="rn_Questions" href="/app/account/assets/update_registered_product/asset_id/#rn:url_param_value:asset_id#/">#rn:msg:EDIT_REGISTERED_PRODUCT_CMD#</a><br/>
        <?endif;?>
    </div>
        
    <h2 class="rn_HeadingBar">#rn:msg:QUESTIONS_LBL#</h2>
    <div class="rn_Questions">
        <rn:container report_id="230" per_page="10">
            <rn:widget path="reports/ResultInfo"/>
            <rn:widget path="reports/Grid" label_caption="<span class='rn_ScreenReaderOnly'>#rn:msg:YOUR_RECENTLY_SUBMITTED_QS_UC_LBL#</span>"/>
            <rn:widget path="reports/Paginator"/>
        </rn:container>
        <a href="/app/ask/Incident.Asset/#rn:url_param_value:asset_id#/Incident.Product/<rn:field name="Asset.Product.ServiceProduct.ID"/>">#rn:msg:ASK_QUESTION_HDG#</a><br/> 
    </div>

    <div id="rn_DetailTools">
        <rn:widget path="utils/PrintPageLink" />
    </div>
</div>
