<?php

/*
| -------------------------------------------------------------------------
| Hooks
| -------------------------------------------------------------------------
| This file lets you define hooks to extend Customer Portal functionality. Hooks allow
| you to specify custom code that you wish to execute before and after many
| important events that occur within Customer Portal. This custom code can modify data,
| perform custom validation, and return customized error messages to display to your users.
|
| Hooks are defined by specifying the location where you wish the hook to run as the array index
| and setting that index to an array of 3 items, class, function, and filepath. The 'class' index
| is the case-sensitive name of the custom model you wish to use. The 'function' index is the name
| of the function within the 'class' you wish to call. Finally, the 'filepath' is the location to
| your model, which will automatically be prefixed by models/custom/. The 'filepath' index only
| needs a value if your model is contained within a subfolder
|
|-----------------
| Hook Locations
|-----------------
|
|     pre_allow_contact                 - Called before allowing a contact to access content.
|     pre_login                         - Called immediately before user becomes logged in.
|     post_login                        - Called immediately after user has been logged in.
|     pre_logout                        - Called immediately before user logs out.
|     post_logout                       - Called immediately after user logs out.
|     pre_contact_create                - Called before Customer Portal validation and contact is created.
|     post_contact_create               - Called immediately after contact has been created.
|     pre_contact_update                - Called before Customer Portal validation and contact is updated.
|     post_contact_update               - Called immediately after contact is updated.
|     pre_incident_create               - Called before Customer Portal validation and incident is created.
|     pre_incident_create_save          - Called before save is called on the Incident Connect object. Returning a string will prevent the save function from being called and will set the $incident object to the 'incident' key in the hook data.
|     post_incident_create              - Called immediately after incident has been created.
|     pre_register_smart_assistant_resolution - Called before the KnowledgeFoundation\Knowledge::RegisterSmartAssistantResolution is called. Returning a string will prevent the KnowledgeFoundation\Knowledge::RegisterSmartAssistantResolution function from being called.
|     pre_incident_update               - Called before Customer Portal validation and incident is updated.
|     post_incident_update              - Called immediately after incident is updated.
|     pre_siebel_incident_submit        - Called before Customer Portal submits a Service Request to a configured Siebel instance.
|     post_siebel_incident_error        - Called after Customer Portal submits a Service Request to a configured Siebel instance if an error occurs.
|     pre_asset_create                  - Called before Customer Portal validation and asset is created.
|     post_asset_create                 - Called immediately after asset has been created.
|     pre_asset_update                  - Called before Customer Portal validation and asset is updated.
|     post_asset_update                 - Called immediately after asset is updated.
|     pre_feedback_submit               - Called before both site and answer feedback. This hook will not be utilized when config OKCS_ENABLED is set to true.
|     post_feedback_submit              - Called after both site and answer feedback is submitted. This hook will not be utilized when config OKCS_ENABLED is set to true.
|     pre_login_redirect                - Called before user is redirected to a new page because they are not logged in.
|     pre_pta_decode                    - Called before PTA string is decoded and converted to pairdata.
|     pre_pta_convert                   - Called after PTA string has been decoded and converted into key/value pairs.
|     pre_page_render                   - Called before page content is sent to the browser.
|     pre_report_get                    - Called before a report is retrieved.
|     pre_report_get_data               - Called before submitting the report and allows for modification of the query parameters.
|     post_report_get_data              - Called after the report data has been retrieved and allows for modification of the report data.
|     pre_page_set_selection            - Called before the user is redirected to a specific page set.
|     pre_attachment_upload             - Called before an attachment is uploaded to the server. Return a string error message to prevent the file from being uploaded.
|     pre_attachment_download           - Called before an attachment is downloaded from the server. Set `preventBrowserDisplay` to true to prevent the attachment from being rendered in the client's browser. This hook will not be utilized when config OKCS_ENABLED is set to true.
|     pre_retrieve_smart_assistant_answers - Called before results are passed to SmartAssistantDialog widget when config OKCS_ENABLED is set to true.
|     pre_sub_report_check              - Called before the sub report check is performed and allows to modify the sub report mapping.
|     pre_sitemap_priority_data         - Called before calculating priority for sitemap and allows for modification of priority scale range.
|     pre_report_filter_clean           - Called before cleaning the filter values and allows for modification of clean callback closures.
|     pre_socialquestion_create         - Called immediately before Customer Portal validation and before the social question is created.
|     post_socialquestion_create        - Called immediately after the social question has been created.
|     pre_socialquestion_update         - Called immediately before Customer Portal validation and before the social question is updated.
|     post_socialquestion_update        - Called immediately after the social question has been updated.
|     pre_socialuser_create             - Called immediately before Customer Portal validation and before the social user is created.
|     post_socialuser_create            - Called immediately after the social user has been created.
|     pre_socialuser_update             - Called immediately before Customer Portal validation and before the social user is updated.
|     post_socialuser_update            - Called immediately after the social user has been updated.
|     pre_socialquestioncomment_create  - Called immediately before Customer Portal validation and before the social question comment is created.
|     post_socialquestioncomment_create - Called immediately after the social question comment has been created.
|     pre_socialquestioncomment_update  - Called immediately before Customer Portal validation and before the social question comment is updated.
|     post_socialquestioncomment_update - Called immediately after the social question comment has been updated.
|     okcs_site_map_answers             - Called before siteMap links are generated when OKCS_ENABLED is set to true.
|     pre_openlogin_lookup_contact      - Called before logging in an open id user.
|     pre_openlogin_decode              - Called before getting the open id user info.
|     post_openlogin_authurl            - Called immediately after the authentication URL has been generated.
|     openlogin_build_url               - Called before calling the build url in the discover method of LightOpenID.
|
|
| Please refer to the documentation for further information
|
|------------------
|Examples
|------------------
|
| Example 1 - Call the sendFeedback function immediately after an incident is created
|             using the Immediateincidentfeedback_model
|             (located at /models/custom/immediateincidentfeedback_model.php).
|
| $rnHooks['post_incident_create'] = array(
|        'class' => 'Immediateincidentfeedback_model',
|        'function' => 'sendFeedback',
|        'filepath' => ''
|    );

|=========================================================================================================

| Example 2 - Call the copyLogin function immediately before a contact is created using
|             the Customcontact_model (located at /models/custom/contact/customcontact_model.php)
|
| $rnHooks['pre_contact_create'] = array(
|        'class' => 'Customcontact_model',
|        'function' => 'copyLogin',
|        'filepath' => 'contact'
|    );
|=========================================================================================================

| Example 3 - First call the customValidation function from the Myfeedback_model
|             (located at /models/custom/feedback/myfeedback_model.php) then call
|             the sendFeedback function from Immediateincidentfeedback_model (located at
|             /models/custom/immediateincidentfeedback_model.php). The first function will be called
|             before the feedback is submitted. The second will be called after.
|
| $rnHooks['pre_feedback_submit'][] = array(
|        'class' => 'Myfeedback_model',
|        'function' => 'customValidation',
|        'filepath' => 'feedback'
|    );
| $rnHooks['post_feedback_submit'][] = array(
|        'class' => 'Immediateincidentfeedback_model',
|        'function' => 'sendFeedback',
|        'filepath' => ''
|    );
|=========================================================================================================
*/
