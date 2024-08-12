<rn:meta title="#rn:php:\RightNow\Libraries\SEO::getDynamicTitle('incident', \RightNow\Utils\Url::getParameter('i_id'))#" template="basic.php" login_required="true" clickstream="incident_view" force_https="true"/>

<div>
    <h1><rn:field name="Incident.Subject" highlight="true"/></h1>
</div>

<div>
    <rn:condition incident_reopen_deadline_hours="168">
    <rn:widget path="input/BasicFormStatusDisplay"/>
    <rn:form action="/app/account/questions/detail/i_id/#rn:url_param_value:i_id#" post_handler="postRequest/sendForm">
        <rn:widget path="input/BasicFormInput" name="Incident.StatusWithType.Status" label_input="#rn:msg:DO_YOU_WANT_A_RESPONSE_MSG#"/>
        <rn:widget path="input/BasicFormInput" name="Incident.Threads" label_input="#rn:msg:ADD_ADDTL_INFORMATION_QUESTION_CMD#"/>
        <rn:widget path="input/BasicFormSubmit" label_button="#rn:msg:SUBMIT_CMD#" on_success_url="/app/account/questions/list"/>
    </rn:form>
    <rn:condition_else/>
        <h2>#rn:msg:INC_REOPNED_UPD_FURTHER_ASST_PLS_MSG#</h2>
    </rn:condition>
</div>

<div>
    <h2>#rn:msg:COMMUNICATION_HISTORY_LBL#</h2>
    <div>
        <rn:widget path="output/DataDisplay" name="Incident.Threads" label=""/>
    </div>
</div>

<div>
    <h2>#rn:msg:ADDITIONAL_DETAILS_LBL#</h2>
    <div>
        <rn:widget path="output/DataDisplay" name="Incident.PrimaryContact.Emails.PRIMARY.Address" left_justify="true" label="#rn:msg:EMAIL_ADDR_LBL#"/>
        <rn:widget path="output/DataDisplay" name="Incident.ReferenceNumber" left_justify="true" label="#rn:msg:REFERENCE_NUMBER_LBL#"/>
        <rn:widget path="output/DataDisplay" name="Incident.StatusWithType.Status" left_justify="true" label="#rn:msg:STATUS_LBL#"/>
        <rn:widget path="output/DataDisplay" name="Incident.CreatedTime" left_justify="true" label="#rn:msg:CREATED_LBL#"/>
        <rn:widget path="output/DataDisplay" name="Incident.UpdatedTime" left_justify="true" label="#rn:msg:UPDATED_LBL#"/>
        <rn:widget path="output/DataDisplay" name="Incident.Product" left_justify="true" label="#rn:msg:PRODUCT_LBL#"/>
        <rn:widget path="output/DataDisplay" name="Incident.Category" left_justify="true" label="#rn:msg:CATEGORY_LBL#"/>
        <rn:widget path="output/DataDisplay" name="Incident.FileAttachments" left_justify="true" label="#rn:msg:FILE_ATTACHMENTS_LBL#"/>
        <rn:widget path="output/CustomAllDisplay" table="Incident" left_justify="true"/>
    </div>
</div>
