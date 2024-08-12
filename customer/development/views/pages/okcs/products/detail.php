<rn:meta title="#rn:php:\RightNow\Libraries\SEO::getDynamicTitle('product', \RightNow\Utils\Url::getParameter('categoryRecordID'))#" template="okcs_standard.php" clickstream="product"></rn:meta>
<div class="rn_Hero rn_Product">
    <div class="rn_HeroInner">
        <rn:widget path="okcs/OkcsProductCategoryBreadcrumb" display_first_item="false"/>
        <div class="rn_ProductHero">
            <rn:widget path="okcs/OkcsProductCategoryImageDisplay" label_default_image_alt_text="" label_image_alt_text="">
        </div>
    </div>
</div>

<div class="rn_PageContent rn_Product">
    <div class="rn_Container">
        <rn:widget path="okcs/OkcsVisualProductCategorySelector" show_sub_items_for="#rn:url_param_value:categoryRecordID#" numbered_pagination="true"/>
    </div>
    <div class="rn_PopularKB">
        <div class="rn_Container">
            <rn:widget path="okcs/AnswerList" type="popular" product_category="#rn:url_param_value:categoryRecordID#" target="_self" view_type="list" internal_pagination="true" per_page="10"/>
        </div>
    </div>
</div>
