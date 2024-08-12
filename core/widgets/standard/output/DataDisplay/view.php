<rn:block id="top"/>
<?if(\RightNow\Utils\Connect::isFileAttachmentType($this->data['value'])):?>
    <rn:widget path="output/FileListDisplay" sub_id="file"/>
<?elseif(\RightNow\Utils\Connect::getProductCategoryType($this->data['value'])):?>
    <rn:widget path="output/ProductCategoryDisplay" sub_id="prodCat"/>
<?elseif(\RightNow\Utils\Connect::isIncidentThreadType($this->data['value'])):?>
    <rn:widget path="output/IncidentThreadDisplay" sub_id="incident"/>
<?else:?>
    <rn:widget path="output/FieldDisplay" sub_id="genericField"/>
<?endif;?>
<rn:block id="bottom"/>
