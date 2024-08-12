# CP Developer Test Monitor

To use this file simply add a line with the test you added for your story or defect. DTM picks up on the @@@ QA {refno} section, then everything that follows are notes.
eg. @@@ QA: xxxxxx-xxxxxx Libraries/tests/Formatter.test.php - added testFormatThreadEntry

@@@ QA 130827-000144 widgetBuilder/tests/standalone.test.js - updated "Required fields are required before continuing"
@@@ QA 130827-000144 widgetBuilder/tests/standalone.test.js - updated "Continue button is shown with correct data"
@@@ QA 130827-000144 widgetBuilder/tests/standalone.test.js - updated "Can remove an attribute"
@@@ QA 130827-000144 widgetBuilder/tests/extension.test.js - updated "Required fields are required before continuing"
@@@ QA 130827-000144 widgetBuilder/tests/extension.test.js - updated "Step is initially collapsed and continue button stays enabled"
@@@ QA 130827-000144 widgetBuilder/tests/extension.test.js - added "Clicking on an attribute closes step five and re-shows the step four continue button"
@@@ QA 130904-000035 widgetHelpers/tests/SearchFilter.test.js - added 'Report searches with pages specified should add /search/1'
@@@ QA 130904-000035 widgetHelpers/tests/SearchFilter.test.js - added 'Report searches with pages specified, but not included in search event, should add /search/1'
@@@ QA 130904-000035 widgetHelpers/tests/SearchFilter.test.js - added 'Source searches with pages specified should add /search/1'
@@@ QA 130904-000035 widgetHelpers/tests/SearchFilter.test.js - added 'Report searches with pages specified should not add /search/1'
@@@ QA 130904-000035 widgetHelpers/tests/SearchFilter.test.js - added 'Source searches with pages specified should not add /search/1'
@@@ QA 130912-000112 admin/js/deploy/tests/promote.test.js - added 'Promote button and comment entry are enabled to start with'
@@@ QA 130912-000112 admin/js/deploy/tests/promote.test.js - added 'Promote button is disabled then re-enabled when cancelling the confirm dialog'
@@@ QA 130912-000112 admin/js/deploy/tests/stage.test.js - added 'Stage button is disabled then re-enabled when cancelling the confirm dialog'
@@@ QA 130919-000045 admin/js/deploy/tests/stage.test.js - updated 'Stage button stays enabled after cancelling the confirm dialog'
@@@ QA 130919-000045 admin/js/deploy/tests/promote.test.js - updated 'Promote button stays enabled after cancelling the confirm dialog'
@@@ QA 130919-000045 admin/js/deploy/tests/rollback.test.js - added 'Rollback button and comment entry are enabled to start with'
@@@ QA 130919-000045 admin/js/deploy/tests/rollback.test.js - added 'Rollback button stays enabled after cancelling the confirm dialog'
@@@ QA 130920-000060 admin/js/versions/tests/manage.test.js - added '#addThumbnail is a no-op with an image without forceReload = true'
@@@ QA 130920-000060 admin/js/versions/tests/manage.test.js - added '#addThumbnail is a no-op with an error message without forceReload = true'
@@@ QA 130917-000105 admin/js/explorer/tests/dialogs.test.js- added 'Test inspect making sure history is updated'
@@@ QA 130919-000095 widgets/standard/reports/TopAnswers/tests/target.test - tested new attribute for opening url and attachment fields in new window
@@@ QA 130917-000011 widgets/standard/knowledgebase/TopicBrowse/tests/base.test.js - added "Fix to not allow the cluster identified as the 'bestMatch' to override the selected cluster"
@@@ QA 130912-000124 webfiles/core/admin/js/explorer/tests/editor.test.js - added "allow method chaining"
@@@ QA 130112-000001 core/framework/CodeIgniter/system/tests/CoreCodeIgniter.test.php - updated 'Added Strict-Transport-Security header when SEC_END_USER_HTTPS enabled'
@@@ QA 130912-000111 core/framework/Models/tests/Report.test.php - added "testGetReportHeaders"
@@@ QA 130912-000111 core/framework/Models/tests/Report.test.php - added "testSetViewDefinition"
@@@ QA 130912-000111 core/widgets/standard/search/SortList/tests/controller.test.php - added "testGetData"
@@@ QA 130912-000111 core/widgets/standard/search/SortList/tests/controller.test.php - added "testSorting"
@@@ QA 130926-000018 core/framework/Internal/Libraries/tests/ConnectExplorer.test.php - tests to ensure limit parameter is fixed if value is over maximum
@@@ QA 130924-000007 widgets/standard/search/ProductCategoryRelatedItems/ - added base.test
@@@ QA 130924-000007 widgets/standard/search/ProductCategoryRelatedItems/ - added attrs.test
@@@ QA 130924-000007 widgets/standard/search/ProductCategoryRelatedItems/ - added minimum.test
@@@ QA 131009-000093 widgets/standard/search/ProductCategoryRelatedItems/ - added mismatch.test
@@@ QA 130927-000056 core/framework/Internal/Libraries/CodeAssistant/tests/Suggestion.test.php - remove windows line endings from scanned code
@@@ QA 130923-000076 core/framework/Controllers/Admin/tests/Overview.test.php - added "testSetLanguage"
@@@ QA 130923-000076 core/framework/Internal/Utils/tests/Admin.test.php - added "testgetLanguageInterfaceMap"
@@@ QA 130923-000076 core/framework/Internal/Utils/tests/Admin.test.php - added "testGetLanguageLabels"
@@@ QA 130923-000076 core/framework/Controllers/tests/Base.test.php - added "testGetRequestedAdminLangData"
@@@ QA 130717-000070 core/framework/Internal/Libraries/WebDav/tests/PathHandler.test.php added "testGetArtificialPaths"
@@@ QA 130717-000070 core/framework/Internal/Libraries/WebDav/tests/PathHandler.test.php added "testGetInsertedPaths"
@@@ QA 130717-000070 core/framework/Internal/Libraries/WebDav/tests/PathHandler.test.php added "testGetFileSystemPath"
@@@ QA 130717-000070 core/framework/Internal/Libraries/WebDav/tests/PathHandler.test.php added "testTransformToDavPath"
@@@ QA 130717-000070 core/framework/Internal/Libraries/WebDav/tests/PathHandler.test.php added "testGetDirectoryContents"
@@@ QA 130717-000070 core/framework/Internal/Libraries/WebDav/tests/PathHandler.test.php added "testGetDavUrl"
@@@ QA 130717-000070 core/framework/Internal/Libraries/WebDav/tests/PathHandler.test.php added "testGetFileOrFolderName"
@@@ QA 130717-000070 core/framework/Internal/Libraries/WebDav/tests/PathHandler.test.php added "testGetDavPath"
@@@ QA 130717-000070 core/framework/Internal/Libraries/WebDav/tests/PathHandler.test.php added "testGetDavSegments"
@@@ QA 130717-000070 core/framework/Internal/Libraries/WebDav/tests/PathHandler.test.php added "testIsArtificialPath"
@@@ QA 130717-000070 core/framework/Internal/Libraries/WebDav/tests/PathHandler.test.php added "testGetBasePath"
@@@ QA 130717-000070 core/framework/Internal/Libraries/WebDav/tests/PathHandler.test.php added "testInsertWidgetVersion"
@@@ QA 130717-000070 core/framework/Internal/Libraries/WebDav/tests/PathHandler.test.php added "testInsertFrameworkVersion"
@@@ QA 130717-000070 core/framework/Internal/Libraries/WebDav/tests/PathHandler.test.php added "testIsDirectory"
@@@ QA 130717-000070 core/framework/Internal/Libraries/WebDav/tests/PathHandler.test.php added "testGetSize"
@@@ QA 130717-000070 core/framework/Internal/Libraries/WebDav/tests/PathHandler.test.php added "testGetCreationTime"
@@@ QA 130717-000070 core/framework/Internal/Libraries/WebDav/tests/PathHandler.test.php added "testGetModifiedTime"
@@@ QA 130717-000070 core/framework/Internal/Libraries/WebDav/tests/PathHandler.test.php added "testFileExists"
@@@ QA 130717-000070 core/framework/Internal/Libraries/WebDav/tests/PathHandler.test.php added "testIsVisiblePath"
@@@ QA 130717-000070 core/framework/Internal/Libraries/WebDav/tests/PathHandler.test.php added "testIsHiddenLogFile"
@@@ QA 130717-000070 core/framework/Internal/Libraries/WebDav/tests/Server.test.php added "testPROPFIND"
@@@ QA 130717-000070 core/framework/Internal/Libraries/WebDav/tests/Server.test.php added "testGetIndexHtml"
@@@ QA 130717-000070 core/framework/Internal/Libraries/WebDav/tests/Server.test.php added "testGetBreadcrumbHtml"
@@@ QA 130717-000070 core/framework/Internal/Libraries/WebDav/tests/Server.test.php added "testIsIgnoredPutRequest"
@@@ QA 130717-000070 core/framework/Internal/Libraries/WebDav/tests/Server.test.php added "testPUT"
@@@ QA 130717-000070 core/framework/Internal/Libraries/WebDav/tests/Server.test.php added "testIsIgnoredMkColRequest"
@@@ QA 130717-000070 core/framework/Internal/Libraries/WebDav/tests/Server.test.php added "testMKCOL"
@@@ QA 130717-000070 core/framework/Internal/Libraries/WebDav/tests/Server.test.php added "testCOPY"
@@@ QA 130717-000070 core/framework/Internal/Libraries/WebDav/tests/Server.test.php added "testMOVE"
@@@ QA 130717-000070 core/framework/Internal/Libraries/WebDav/tests/Server.test.php added "testDELETE"
@@@ QA 130927-000048 admin/js/tools/tests/widgetBuilder/tooltip.test.js added tooltip test file
@@@ QA 130927-000084 core/framework/Internal/Libraries/WebDav/tests/PathHandler.test.php modify 'testInsertFrameworkVersion' and 'testGetFileSystemPath' to handle versions in non-hosted
@@@ QA 130926-000000 cp/core/framework/Models/tests/Contact.test.php - Add back in Open Login + null password check
@@@ QA 130926-000000 cp/core/framework/Models/tests/Contact.test.php - Add back in Open Login + null password check
@@@ QA 130925-000095 core/framework/Libraries/Cache/tests/PersistentReadThroughCache.test.php modify 'testPersistentReadThroughCache' to test extra long keys
@@@ QA 130925-000095 core/framework/Libraries/Cache/tests/PersistentReadThroughCache.test.php added 'testGetMemcacheKey'
@@@ QA 130925-000095 core/framework/Libraries/Cache/tests/PersistentReadThroughCache.test.php added 'testSetModePrefixKey'
@@@ QA 111028-000071 core/framework/Controllers/tests/Pta.test.php added 'testExternallyGeneratedTokens'
@@@ QA 131003-000035 cp/core/framework/Utils/tests/Framework.test.php - Framework#isOpenLogin should check the right thing
@@@ QA 131003-000035 cp/core/framework/Utils/tests/Framework.test.php - Framework#isOpenLogin should check the right thing
@@@ QA 131003-000035 cp/core/framework/Utils/tests/Framework.test.php - Framework#isOpenLogin should check the right thing
@@@ QA 131003-000035 cp/core/framework/Utils/tests/Framework.test.php - Framework#isOpenLogin should check the right thing
@@@ QA 131003-000035 cp/core/framework/Utils/tests/Framework.test.php - Framework#isOpenLogin should check the right thing
@@@ QA 131003-000035 cp/core/framework/Utils/tests/Framework.test.php - Framework#isOpenLogin should check the right thing
@@@ QA 131003-000035 cp/core/framework/Utils/tests/Framework.test.php - Framework#isOpenLogin should check the right thing
@@@ QA 131002-000041 webfiles/core/admin/js/tests/widgetBuilder/tooltip.test.js - updated 'Test adding nodes to DOM'
@@@ QA 131002-000041 webfiles/core/admin/js/tests/widgetBuilder/tooltip.test.js - updated 'Test with node that has data attribute'
@@@ QA 131002-000041 webfiles/core/admin/js/tests/widgetBuilder/tooltip.test.js - added 'Tooltip should be shown on focus event'
@@@ QA 131002-000041 webfiles/core/admin/js/tests/widgetBuilder/extension.test.js - updated 'parentCSS section is shown'
@@@ QA 130429-000038 cp/core/framework/Models/tests/Report.test.php - added "testFormatViewsData"
@@@ QA 130926-000171 Controllers/tests/Openlogin.test.php - updated testFacebookRegistration
@@@ QA 130613-000055 core/framework/Utils/tests/Validation.test.php - added "testGetDateString"
@@@ QA 130613-000055 core/widgets/standard/input/DateInput/tests/controller.test.php - "added testGetData, testgetDateConstraints, testGetOrderParameters"
@@@ QA 130613-000055 core/widgets/standard/input/DateInput/tests/minYearDateTime.test added
@@@ QA 130613-000055 core/widgets/standard/input/DateInput/tests/minYearDateTime.test.js added
@@@ QA 130613-000055 webfiles/core/debug-js/tests/RightNow.Text.test.js added "sprintf testArgumentsWithSpaces"
@@@ QA 130606-000105 webfiles/core/debug-js/modules/widgetHelpers/tests/Field.test.js - updated "The Y.Overlay hint is rendered inside the widget div as the last child and blurs and focuses properly"
@@@ QA 130606-000105 core/widgets/standard/input/ProductCategoryInput/tests/base.test.js - updated testUI
@@@ QA 130606-000105 core/widgets/standard/input/ProductCatalogInput/tests/defaultProductAttribute.test.js - updated testUI
@@@ QA 131003-000066 core/framework/Libraries/Widget/tests/Output.test.php - verified that getData reset this->data['attrs']['name'] from the cache correctly
@@@ QA 131003-000066 core/framework/Libraries/Widget/tests/Input.test.php - verified that getData reset this->data['attrs']['name'] from the cache correctly
@@@ QA 131016-000016 /core/framework/Controllers/Admin/tests/Overview.test.php updated 'testSetLanguage'
@@@ QA 130613-000055 core/widgets/standard/input/DateInput/tests/minYearDateTime.test updated 'testDateValidation'
@@@ QA 131016-000016 /core/framework/Controllers/Admin/tests/Overview.test.php updated 'testSetLanguage'
@@@ QA 130425-000075 core/widgets/standard/feedback/AnswerFeedback/tests/base.test.js Added tests to ensure controls get removed when providing feedback
@@@ QA 130425-000075 core/widgets/standard/chat/VirtualAssistantFeedback/tests/base.test.js Added tests to thanks message is dynamically generated
@@@ QA 130425-000075 core/framework/Models/tests/Incident.test.php Updated tests to expect ResponseObject during hook error
@@@ QA 130906-000057 cp/webfiles/core/debug-js/modules/widgetHelpers/tests/Form.test.js - Give control back to the widget when a bad server response comes back
@@@ QA 130906-000057 cp/webfiles/core/debug-js/modules/widgetHelpers/tests/Form.test.js - Give control back to the widget when a bad server response comes back
@@@ QA 130712-000078 core/framework/Utils/tests/Connect.test.php - updated 'testIsFileAttachmentType'
@@@ QA 131023-000018 core/framework/Internal/Libraries/tests/ConnectExplorer.test.php - updated 'testSelect'
@@@ QA 131010-000113 webfiles/core/debug-js/modules/widgetHelpers/tests/EventProvider.test.js - Add test filter subscribers
@@@ QA 131010-000113 webfiles/core/debug-js/modules/widgetHelpers/tests/Form.test.js - Add test collect event
@@@ QA 131010-000113 webfiles/core/debug-js/modules/widgetHelpers/tests/Field.test.js - Add test hide / show for dynamic forms
@@@ QA 131010-000113 core/widgets/standard/input/TestInput - Add hide_on_load attribute test
@@@ QA 131010-000113 core/widgets/standard/input/DateInput - Add hide_on_load attribute test
@@@ QA 131010-000113 core/widgets/standard/input/SelectionInput - Add hide_on_load attribute test
@@@ QA 131010-000113 core/widgets/standard/input/ProductCategoryInput - Add hide_on_load attribute test
@@@ QA 131010-000113 core/widgets/standard/input/ProductCatalogInput - Add hide_on_load attribute test
@@@ QA 131010-000113 core/widgets/standard/input/PasswordInput - Add hide_on_load attribute test
@@@ QA 131010-000113 core/widgets/standard/input/FileAttachmentUpload - Add hide_on_load attribute test
@@@ QA 121029-000108 core/framework/Internal/Utils/tests/CodeAssistant.test.php - Add testGetFiles
@@@ QA 121029-000108 core/framework/Internal/Utils/tests/CodeAssistant.test.php - Add testGetAbsolutePathAndCheckPermissions
@@@ QA 121029-000108 core/framework/Internal/Utils/tests/CodeAssistant.test.php - Add testBackupFile
@@@ QA 121029-000108 core/framework/Internal/Utils/tests/CodeAssistant.test.php - Add testProcessInstruction
@@@ QA 121029-000108 core/framework/Internal/Utils/tests/CodeAssistant.test.php - Add testProcessInstructions
@@@ QA 130417-000141 core/framework/Internal/Libraries/tests/Changelog.test.php - updated 'testAddEntriesFromReport'
@@@ QA 131018-000065 core/widgets/standard/input/BasicCustomAllInput/tests/contactFields.test - updated
@@@ QA 131018-000065 core/widgets/standard/input/BasicCustomAllInput/tests/incidentFields.test - updated
@@@ QA 131018-000065 core/widgets/standard/input/BasicDateInput/tests/attrs.test - updated
@@@ QA 131018-000065 core/widgets/standard/input/BasicDateInput/tests/hideHint.test - updated
@@@ QA 131018-000065 core/widgets/standard/input/BasicDateInput/tests/post.test - updated
@@@ QA 131018-000065 core/widgets/standard/input/BasicDateInput/tests/postBad.test - updated
@@@ QA 131018-000065 core/widgets/standard/input/BasicDateInput/tests/readOnlyWithOverride.test - updated
@@@ QA 131018-000065 core/widgets/standard/input/BasicDateInput/tests/required.test - updated
@@@ QA 131018-000065 core/widgets/standard/input/DateInput/tests/controller.test.php - updated
@@@ QA 131018-000065 core/widgets/standard/input/DateInput/tests/minYear.test.js - updated
@@@ QA 131018-000065 core/widgets/standard/input/DateInput/tests/minYearDateTime.test.js - updated
@@@ QA 131022-000064 core/framework/Internal/Libraries/tests/Deployer.test.php - Added testGenerateOptimizedWidgetPath and testGetMinifiedJavaScript
@@@ QA 131028-000098 webfiles/core/admin/js/explorer/tests/editor.test.js - Added verification of visibility of error div
@@@ QA 131029-000048 cp/core/framework/Controllers/tests/Openlogin.test.php - Access twitter user ids via the  property
@@@ QA 131029-000048 cp/core/framework/Controllers/tests/Openlogin.test.php - Access twitter user ids via the  property
@@@ QA 131029-000048 cp/core/framework/Controllers/tests/Openlogin.test.php - Access twitter user ids via the  property
@@@ QA 131029-000048 cp/core/framework/Controllers/tests/Openlogin.test.php - Access twitter user ids via the  property
@@@ QA 131029-000048 cp/core/framework/Controllers/tests/Openlogin.test.php - Access twitter user ids via the  property
@@@ QA 131029-000048 cp/core/framework/Controllers/tests/Openlogin.test.php - Access twitter user ids via the  property
@@@ QA 131029-000048 cp/core/framework/Controllers/tests/Openlogin.test.php - Access twitter user ids via the  property
@@@ QA 131029-000048 cp/core/framework/Controllers/tests/Openlogin.test.php - Access twitter user ids via the  property
@@@ QA 130807-000091 core/framework/Utils/tests/Widgets.test.php - Updated testResetContainerID, testPushAttributesOntoStack, and testPopAttributesFromStack
@@@ QA 130808-000103 core/framework/Internal/Utils/tests/WidgetViews.test.php - Allow EJS files to be overridden in extended widgets
@@@ QA 131021-000022 cp/core/widgets/standard/input/FileAttachmentUpload/tests/base.test.js - Fix incorrect preview image showing when user tries to upload a new image when the first image is being uploaded
@@@ QA 131021-000022 cp/core/widgets/standard/input/FileAttachmentUpload/tests/base.test.js - Fix incorrect preview image showing when user tries to upload a new image when the first image is being uploaded
@@@ QA 131021-000022 cp/core/widgets/standard/input/FileAttachmentUpload/tests/base.test.js - Fix incorrect preview image showing when user tries to upload a new image when the first image is being uploaded
@@@ QA 131021-000022 cp/core/widgets/standard/input/FileAttachmentUpload/tests/base.test.js - Fix incorrect preview image showing when user tries to upload a new image when the first image is being uploaded
@@@ QA 131021-000022 cp/core/widgets/standard/input/FileAttachmentUpload/tests/base.test.js - Fix incorrect preview image showing when user tries to upload a new image when the first image is being uploaded
@@@ QA 131021-000022 cp/core/widgets/standard/input/FileAttachmentUpload/tests/base.test.js - Fix incorrect preview image showing when user tries to upload a new image when the first image is being uploaded
@@@ QA 131021-000022 cp/core/widgets/standard/input/FileAttachmentUpload/tests/base.test.js - Fix incorrect preview image showing when user tries to upload a new image when the first image is being uploaded
@@@ QA 131021-000022 cp/core/widgets/standard/input/FileAttachmentUpload/tests/base.test.js - Fix incorrect preview image showing when user tries to upload a new image when the first image is being uploaded
@@@ QA 131021-000022 cp/core/widgets/standard/input/FileAttachmentUpload/tests/base.test.js - Fix incorrect preview image showing when user tries to upload a new image when the first image is being uploaded
@@@ QA 131021-000022 cp/core/widgets/standard/input/FileAttachmentUpload/tests/base.test.js - Fix incorrect preview image showing when user tries to upload a new image when the first image is being uploaded
@@@ QA 131021-000022 cp/core/widgets/standard/input/FileAttachmentUpload/tests/base.test.js - Fix incorrect preview image showing when user tries to upload a new image when the first image is being uploaded
@@@ QA 131021-000022 cp/core/widgets/standard/input/FileAttachmentUpload/tests/base.test.js - Fix incorrect preview image showing when user tries to upload a new image when the first image is being uploaded
@@@ QA 131021-000022 cp/core/widgets/standard/input/FileAttachmentUpload/tests/base.test.js - Fix incorrect preview image showing when user tries to upload a new image when the first image is being uploaded
@@@ QA 131030-000002 core/framework/Internal/Libraries/tests/ConnectExplorer.test.php - verify object name in connect explorer queries
@@@ QA 120924-000006 webfiles/core/admin/js/logs/tests/dataTable.test.js - Added
@@@ QA 120924-000006 cp/core/framework/Internal/Utils/tests/Logs.test.php - Added 'testGetWebdavLogData'
@@@ QA 120924-000006 cp/core/framework/Internal/Utils/tests/Logs.test.php - Added 'testGetDebugLogData'
@@@ QA 120924-000006 cp/core/framework/Internal/Utils/tests/Logs.test.php - Added 'testGetDeployLogData'
@@@ QA 120924-000006 cp/core/framework/Internal/Utils/tests/Logs.test.php - Added 'testGetWebDavLogDescriptionFor'
@@@ QA 130726-000115 core/framework/Internal/Utils/tests/WidgetViews.test.php - Updated 'testGetExtendedWidgetPhpView'
@@@ QA 131021-000197 cp/core/framework/Libraries/tests/AbuseDetection.test.php - Added isAbuse check if isInAbuse cookie is set
@@@ QA 130710-000077 cp/core/framework/Utils/tests/Url.test.php - Updated 'testGetProductOrCategoryFilter'
@@@ QA 130710-000077 cp/webfiles/core/debug-js/tests/RightNow.Url.test.js - Updated 'testProduct'
@@@ QA 130710-000077 cp/webfiles/core/debug-js/tests/RightNow.Url.test.js - Updated 'testCategory'
@@@ QA 130909-000013 cp/webfiles/core/admin/js/deploy/tests/selectConfigs.test.js - Updated option toggling test to verify focus
@@@ QA 131107-000209 cp/core/framework/Libraries/tests/AbuseDetection.test.php - Updated 'testCheck'
@@@ QA 131107-000209 cp/core/framework/Libraries/tests/AbuseDetection.test.php - Added 'testIsAbuse'
@@@ QA 130821-000020 core/framework/Models/tests/CommunityQuestion.test.php - Updated testBestAnswer
@@@ QA 130821-000020 core/framework/Libraries/tests/Formatter.test.php - Added testFormatMultipleFields
@@@ QA 131021-000206 cp/core/framework/Models/tests/Answer.test.php - Refactor models to remove all calls to AbuseDetection::check() and replace them with AbuseDetection::isAbuse()
@@@ QA 131021-000206 cp/core/framework/Models/tests/Base.test.php - Refactor models to remove all calls to AbuseDetection::check() and replace them with AbuseDetection::isAbuse()
@@@ QA 131021-000206 cp/core/framework/Models/tests/Contact.test.php - Refactor models to remove all calls to AbuseDetection::check() and replace them with AbuseDetection::isAbuse()
@@@ QA 131021-000206 cp/core/framework/Models/tests/Incident.test.php - Refactor models to remove all calls to AbuseDetection::check() and replace them with AbuseDetection::isAbuse()
@@@ QA 131021-000206 cp/core/framework/Models/tests/Notification.test.php - Refactor models to remove all calls to AbuseDetection::check() and replace them with AbuseDetection::isAbuse()
@@@ QA 131021-000206 cp/core/framework/Models/tests/PrimaryObjectBase.test.php - Refactor models to remove all calls to AbuseDetection::check() and replace them with AbuseDetection::isAbuse()
@@@ QA 130726-000115 core/framework/Internal/Utils/tests/WidgetViews.test.php - Added 'testIsViewAndLogicOverridden'
@@@ QA 130821-000020 core/framework/Utils/tests/Connect.test.php - Added testGetSupportObjects
@@@ QA 131105-000057 core/widgets/standard/search/BasicDisplaySearchFilters/tests/controller.test.php - Added
@@@ QA 131111-000050 cp/webfiles/core/admin/js/logs/tests/dataTable.test.js - Added 'search tests'
@@@ QA 130906-000065 core/framework/Internal/Libraries/tests/Staging.test.php - Updated 'testStagingControls'
@@@ QA 130906-000065 core/framework/Internal/Libraries/tests/Staging.test.php - Updated 'testEnvironmentFileDifferences'
@@@ QA 131011-000091 core/framework/Models/tests/Report.test.php - Updated 'testGetHeaders' to confirm columnIDs were set correctly, even if hidden columns were not returned
@@@ QA 131203-000136 core/framework/Controllers/Admin/tests/Versions.test.php - Updated testGetViewsUsedOn
@@@ QA 131119-000042 core/framework/CodeIgniter/system/tests/CoreCodeIgniter.test.php - Added 'testRedirectToHttpsIfNeeded'
@@@ QA 130821-000020 core/framework/Utils/tests/Connect.test.php - Added testRetrieveMetaData
@@@ QA 131108-000112 core/framework/Libraries/tests/Formatter.test.php - Updated testFormatBodyEntry
@@@ QA 131119-000053 core/framework/Models/tests/CommunityQuestion.test.php - Updated testGetRecentlyAnsweredQuestionsWithFlags
@@@ QA 131119-000053 core/framework/Models/tests/CommunityQuestion.test.php - Updated testFlagQuestion
@@@ QA 131119-000053 core/framework/Models/tests/CommunityQuestion.test.php - Updated testFlagQuestionWithAllTypes
@@@ QA 131120-000045 rnw/scripts/cp/core/widgets/standard/login/LoginDialog/tests/autocomplete.test - Updated to reflect maxlength
@@@ QA 131120-000045 rnw/scripts/cp/core/widgets/standard/login/LoginDialog/tests/base.test - Updated to reflect maxlength
@@@ QA 131120-000045 rnw/scripts/cp/core/widgets/standard/login/LoginDialog/tests/custom_labels.test - Updated to reflect maxlength
@@@ QA 131120-000045 rnw/scripts/cp/core/widgets/standard/login/LoginDialog/tests/trigger.test - Updated to reflect maxlength
@@@ QA 131120-000045 rnw/scripts/cp/core/widgets/standard/login/LoginDialog/tests/triggerForce.test - Updated to reflect maxlength
@@@ QA 131120-000045 rnw/scripts/cp/core/widgets/standard/login/LoginDialog/tests/withOpenLoginLink.test - Updated to reflect maxlength
@@@ QA 131120-000045 rnw/scripts/cp/core/widgets/standard/login/LoginDialog/tests/withOpenLoginLinkAndRedirectAttr.test - Updated to reflect maxlength
@@@ QA 131120-000045 rnw/scripts/cp/core/widgets/standard/login/LoginDialog/tests/withOpenLoginLinkAndRedirectParam.test - Updated to reflect maxlength
@@@ QA 131120-000045 rnw/scripts/cp/core/widgets/standard/login/LoginDialog/tests/withOpenLoginLinkAndRedirectParamAndAttr.test - Updated to reflect maxlength
@@@ QA 131125-000033 cp/core/widgets/standard/knowledgebase/GuidedAssistant/tests/base.test - Updated with new css
@@@ QA 131125-000033 cp/core/widgets/standard/knowledgebase/GuidedAssistant/tests/formattedQuestion.test - Updated with new css
@@@ QA 131125-000033 cp/core/widgets/standard/knowledgebase/GuidedAssistant/tests/listType.test - Updated with new css
@@@ QA 131125-000033 cp/core/widgets/standard/knowledgebase/GuidedAssistant/tests/nocookies.test - Updated with new css
@@@ QA 131125-000033 cp/core/widgets/standard/knowledgebase/GuidedAssistant/tests/popupWindow.test - Updated with new css
@@@ QA 131125-000033 cp/core/widgets/standard/knowledgebase/GuidedAssistant/tests/selfTarget.test - Updated with new css
@@@ QA 131125-000033 cp/core/widgets/standard/knowledgebase/GuidedAssistant/tests/singleQuestionDisplay.test - Updated with new css
@@@ QA 131125-000033 cp/core/widgets/standard/knowledgebase/GuidedAssistant/tests/staticGuideID.test - Updated with new css
@@@ QA 131125-000033 cp/core/widgets/standard/knowledgebase/GuidedAssistant/tests/yesNoWithImage.test - Updated with new css
@@@ QA 131119-000053 core/framework/Models/tests/CommunityComment.test.php - Updated testFlagComment
@@@ QA 131119-000053 core/framework/Models/tests/CommunityComment.test.php - Updated testFlagCommentWithAllTypes
@@@ QA 131119-000053 core/framework/Models/tests/CommunityComment.test.php - Updated testCheckCachedTabularQuestions
@@@ QA 131119-000053 core/widgets/standard/input/QuestionComments/controller.test.php - Updated testShouldDisplayCommentActions
@@@ QA 131119-000053 core/widgets/standard/input/QuestionComments/controller.test.php - Updated testShouldDisplayBestAnswerActions
@@@ QA 131119-000053 core/widgets/standard/input/QuestionComments/controller.test.php - Updated testCommentIsLatent
@@@ QA 131119-000053 core/widgets/standard/input/QuestionComments/controller.test.php - Updated testCommentIsBestAnswer
@@@ QA 131126-000072 webfiles/core/admin/js/logs/tests/dataTable.test.js - Added test when value is cleared without a blur
@@@ QA 131120-000027 cp/core/CodeIgniter/system/test/CoreCodeIgniter.test.php - Added test to check complex IE11 user agent detection
@@@ QA 131107-000185 core/framework/Internal/Utils/tests/WidgetViews.test.php - Removed 'testIsViewAndLogicOverridden'
@@@ QA 131107-000185 core/framework/Internal/Utils/tests/WidgetViews.test.php - Updated 'testGetExtendedWidgetPhpView'
@@@ QA 131107-000185 core/framework/Internal/Utils/tests/WidgetViews.test.php - Updated 'testGetExtendedWidgetJsViews'
@@@ QA 131107-000185 core/framework/Utils/tests/Widgets.test.php - Updated 'testGetWidgetToExtendFrom'
@@@ QA 131107-000185 core/framework/Utils/tests/Widgets.test.php - Updated 'testGetWidgetToExtendFrom'
@@@ QA 131107-000185 core/framework/Internal/Libraries/tests/Deployer.test.php - Updated 'testCreateWidgetPageCode'
@@@ QA 131119-000041 core/framework/Controllers/tests/Sitemap.test.php - Add slug to sitemap
@@@ QA 131119-000041 core/framework/Libraries/tests/SEO.test.php - Add slug to sitemap
@@@ QA 131203-000034 cp/core/CodeIgniter/system/test/CoreCodeIgniter.test.php - Added test to check browser that appears after complex IE11
@@@ QA 131203-000045 cp/webfiles/core/admin/js/overview/tests/setMode.test.js - Added General functionality tests
@@@ QA 131209-000153 cp/core/framework/Internal/Libraries/tests/ConnectMetaData.test.php - Added testFormatDates
@@@ QA 131209-000153 cp/core/framework/Internal/Libraries/tests/ConnectMetaData.test.php - Added testIsDate
@@@ QA 131213-000040 cp/core/framework/Internal/Libraries/tests/Deployer.test.php - Added testVersionChangeArgs
@@@ QA 140106-000152 cp/core/framework/Controllers/tests/Pta.test.php - Added tests to validate errors when duplicate emails are sent in via PTA
@@@ QA 140104-000000 cp/core/framework/Internal/Utils/CodeAssistant/tests/WidgetConverter.test.php - Updated testExecuteUnit
@@@ QA 131227-000007 cp/core/framework/Internal/Libraries/WebDav/tests/Server.test.php - Add testIsCyberduck
@@@ QA 131227-000007 cp/core/framework/Internal/Libraries/WebDav/tests/Server.test.php - Add testIsCyberduckGuidFile
@@@ QA 140101-000000 core/framework/CodeIgniter/system/tests/CoreCodeIgniter.test.php - Added testComplexBrowserDetectionWithNbsp
@@@ QA 140107-000166 cp/core/framework/Utils/tests/Url.test.php - Add test to validate sort direction
@@@ QA 140113-000020 core/widgets/standard/search/BasicKeywordSearch/tests/postQuotes.test - Added postQuote rendering test
@@@ QA 140104-000000 core/framework/Internal/Libraries/CodeAssistant/tests/Conversion.test.php - Added testMoveDirectory
@@@ QA 140104-000000 core/framework/Internal/Utils/CodeAssistant/tests/WidgetConverter.test.php - Updated testExecuteUnit
@@@ QA 140104-000000 core/framework/Internal/Utils/tests/CodeAssistant.test.php - Updated testProcessInstruction
@@@ QA 140107-000166 cp/core/framework/Utils/tests/Url.test.php - Add test to validate sort direction
@@@ QA 131213-000029 cp/core/widgets/standard/login/LoginDialog/tests/password.test - Added
@@@ QA 140116-000060 cp/core/framework/tests/Environment.test.php - Added tests to ensure we ignore query string parameters
@@@ QA 140114-000152 cp/core/widgets/standard/input/FileAttachmentUpload/tests/base.test.js - Added button disabled test
@@@ QA 140116-000000 cp/core/compatibility/Internal/Sql/tests/Contact.test.php - Avert internal api error for current password checking
@@@ QA 140116-000000 cp/core/framework/Models/tests/Contact.test.php - Avert internal api error for current password checking
@@@ QA 140115-000002 cp/core/framework/Utils/tests/Validation.test.php - Updated testMaxLength
@@@ QA 140115-000002 cp/core/framework/Utils/tests/Validation.test.php - Added testIsPassword
@@@ QA 140122-000065 cp/core/widgets/standard/input/SelectionInput/tests/incidentStatus.test - Added test for changing thread requiredness
@@@ QA 131204-000093 cp/core/widgets/standard/input/TextInput/tests/controller.test.php - Added testGetDataWithMaxBytes
@@@ QA 131204-000093 cp/core/framework/Utils/tests/Validation.test.php - Added testBytes
@@@ QA 131204-000093 cp/core/framework/Utils/tests/Validation.test.php - Updated testValidate
@@@ QA 131203-000136 core/framework/Controllers/Admin/tests/Versions.test.php - Updated testGetViewsUsedOn
@@@ QA 131211-000072 core/framework/Models/tests/Field.test.php - Updated testProcessFields
@@@ QA 131226-000028 core/framework/Models/tests/CommunityQuestion.test.php - Added testMultipleAnsweredQuestions
@@@ QA 140107-000118 core/widgets/standard/input/QuestionComments/tests/controller.test.php - Added testDelete
@@@ QA 140121-000138 cp/core/framework/Controllers/tests/Ajax.test.php - Properly handle clickstream actions and login exceptions per method for ajax handlers
@@@ QA 140121-000138 cp/core/framework/Controllers/tests/Ajax.test.php - Properly handle clickstream actions and login exceptions per method for ajax handlers
@@@ QA 140121-000138 cp/core/framework/Controllers/tests/Ajax.test.php - Properly handle clickstream actions and login exceptions per method for ajax handlers
@@@ QA 140121-000138 cp/core/framework/Controllers/tests/Ajax.test.php - Properly handle clickstream actions and login exceptions per method for ajax handlers
@@@ QA 140107-000118 core/widgets/standard/input/QuestionComments/tests/controller.test.php - Updated testQuantityLabel
@@@ QA 140107-000060 core/framework/Models/tests/CommunityQuestion.test.php - Updated testGetRecentlyAnsweredQuestionsWithBestAnswers
@@@ QA 140115-000012 cp/core/framework/Models/tests/Report.test.php - Only expand variables for answer content
@@@ QA 140129-000119 cp/core/framework/Utils/tests/Widgets.test.php - Updated testGemptyControllerCode and testGetParentWidget.
@@@ QA 140218-000141 cp/webfiles/core/admin/js/explorer/tests/editor.test.js - Updated tests with error message changes
@@@ QA 140123-000076 cp/webfiles/core/admin/js/configurations/tests/pageSet.test.js - Remove error message when changes successfully save
@@@ QA 140123-000076 cp/webfiles/core/admin/js/configurations/tests/pageSet.test.js - Remove error message when changes successfully save
@@@ QA 140123-000076 cp/webfiles/core/admin/js/configurations/tests/pageSet.test.js - Remove error message when changes successfully save
@@@ QA 140123-000076 cp/webfiles/core/admin/js/configurations/tests/pageSet.test.js - Remove error message when changes successfully save
@@@ QA 140123-000076 cp/webfiles/core/admin/js/configurations/tests/pageSet.test.js - Remove error message when changes successfully save
@@@ QA 140123-000076 cp/webfiles/core/admin/js/configurations/tests/pageSet.test.js - Remove error message when changes successfully save
@@@ QA 140123-000076 cp/webfiles/core/admin/js/configurations/tests/pageSet.test.js - Remove error message when changes successfully save
@@@ QA 140123-000076 cp/webfiles/core/admin/js/configurations/tests/pageSet.test.js - Remove error message when changes successfully save
@@@ QA 140123-000076 cp/webfiles/core/admin/js/configurations/tests/pageSet.test.js - Remove error message when changes successfully save
@@@ QA 130728-000001 cp/core/compatibility/Internal/tests/Api.test.php - Added testCertPath
@@@ QA 130728-000001 cp/core/compatibility/Internal/tests/Api.test.php - Added testSiebelEnabled
@@@ QA 130728-000001 cp/core/compatibility/Internal/tests/SiebelApi.test.php - Added testGenerateRequestParts
@@@ QA 130728-000001 cp/core/compatibility/Internal/tests/SiebelApi.test.php - Added testMakeRequest
@@@ QA 130728-000001 cp/core/compatibility/Internal/tests/SiebelApi.test.php - Added testGeneratePostString
@@@ QA 130728-000001 cp/core/compatibility/Internal/tests/SiebelApi.test.php - Added testGetOptions
@@@ QA 130728-000001 cp/core/compatibility/Internal/tests/SiebelApi.test.php - Added testGetSecureOptions
@@@ QA 130728-000001 cp/core/framework/Internal/Libraries/tests/SiebelRequest.test.php - Added testConstructor
@@@ QA 130728-000001 cp/core/framework/Internal/Libraries/tests/SiebelRequest.test.php - Added testMakeRequest
@@@ QA 130728-000001 cp/core/framework/Internal/Libraries/tests/SiebelRequest.test.php - Added testGetErrors
@@@ QA 130728-000001 cp/core/framework/Internal/Libraries/tests/SiebelRequest.test.php - Added testResetData
@@@ QA 130728-000001 cp/core/framework/Internal/Libraries/tests/SiebelRequest.test.php - Added testMakeSiebelRequest
@@@ QA 130728-000001 cp/core/framework/Internal/Libraries/tests/SiebelRequest.test.php - Added testOutputSiebelErrors
@@@ QA 130728-000001 cp/core/framework/Internal/Libraries/tests/SiebelRequest.test.php - Added testCreateErrorMessage
@@@ QA 130728-000001 cp/core/framework/Libraries/tests/Hooks.test.php - Added testAddStandardHooks
@@@ QA 130728-000001 cp/core/framework/Libraries/tests/Hooks.test.php - Added testRunHook
@@@ QA 130728-000001 cp/core/framework/Libraries/tests/Hooks.test.php - Added testGetHookModelPath
@@@ QA 130728-000001 cp/core/framework/Models/tests/Incident.test.php - Modified testIncidentCreateHooks
@@@ QA 130728-000001 cp/core/framework/Models/tests/Siebel.test.php - Added testProcessRequest
@@@ QA 130728-000001 cp/core/framework/Models/tests/Siebel.test.php - Added testRegisterSmartAssistantResolution
@@@ QA 130728-000001 cp/core/framework/Models/tests/Siebel.test.php - Added testGenerateSiebelData
@@@ QA 130728-000001 cp/core/framework/Models/tests/Siebel.test.php - Added testGetSiebelFieldValue
@@@ QA 140221-000038 cp/core/framework/Internal/Libraries/Deployment/tests/OptimizedWidgetWriter.test.php - Fix dependency requires for core widgets
@@@ QA 140221-000038 cp/core/framework/Internal/Libraries/Deployment/tests/OptimizedWidgetWriter.test.php - Fix dependency requires for core widgets
@@@ QA 140207-000036 cp/core/framework/Models/tests/Report.test.php - Only expand variables on answers
@@@ QA 140207-000036 cp/core/framework/Utils/tests/Text.test.php - Codify beginsWith behavior with null and undefined
@@@ QA 140306-000013 cp/core/framework/Internal/Libraries/tests/Deployer.test.php - Updated testGetJavaScriptInfoForWidgets for invalid widget path
@@@ QA 140324-000047 cp/core/widgets/standard/feedback/SiteFeedback/tests/base.test.js - Handle feedback responses correctly and do not ignore errors
@@@ QA 140324-000047 cp/core/widgets/standard/feedback/SiteFeedback/tests/controller.test.php - Handle feedback responses correctly and do not ignore errors
@@@ QA 140327-000195 cp/core/framework/Models/tests/Field.test.php - Check to see that newly created contact has a login before complaining about cookies
@@@ QA 140327-000194 cp/core/framework/Internal/Libraries/tests/Deployer.test.php - Test inclusion of html tags and that no tags are returned if there is no CSS content
@@@ QA 140404-000117 cp/webfiles/core/debug-js/tests/RightNow.test.js - Test new isSocialUser and socialUserID methods off of RightNow.Profile namespace
@@@ QA 140319-000119 cp/core/framework/Utils/tests/Text.test.php - Update URL validation regex
@@@ QA 140319-000119 cp/webfiles/core/debug-js/tests/RightNow.Text.test.js - Update URL validation regex
@@@ QA 140319-000119 cp/webfiles/core/debug-js/tests/RightNow.Text.test.js - Update URL validation regex
@@@ QA 140403-000075 cp/webfiles/core/admin/js/tools/tests/widgetBuilder/extension.test.js
@@@ QA 140403-000075 cp/webfiles/core/admin/js/tools/tests/widgetBuilder/standalone.test.js
@@@ QA 140319-000113 core/framework/Internal/Libraries/Widget/tests/Builder.test.php - modified testGetJSViews to test duplicate rn:blocks
@@@ QA 140319-000113 core/framework/Internal/Libraries/Widget/tests/Builder.test.php - modified testView to test duplicate rn:blocks
@@@ QA 140319-000113 core/framework/Internal/Libraries/Widget/tests/Builder.test.php - added testGetBlockBoilerPlate
@@@ QA 140318-000017 core/widgets/standard/knowledgebase/TopicBrowse/tests/base.test.js - Added empty topics array test.
@@@ QA 140318-000017 core/widgets/standard/reports/ResultInfo/tests/base.test.js - Added empty topics array test.
@@@ QA 130325-000050 core/framework/Internal/Libraries/tests/ConnectMetaData.test.php - Added support for menu custom attributes
@@@ QA 130325-000050 core/framework/Libraries/tests/Formatter.test.php - Added support for menu custom attributes
@@@ QA 130325-000050 core/widgets/standard/input/SelectionInput/tests/controller.test.php - Added support for menu custom attributes
@@@ QA 130325-000050 core/widgets/standard/output/FieldDisplay/tests/controller.test.php - Added support for menu custom attributes
@@@ QA 140421-000076 core/framework/Libraries/Widget/tests/Input.test.php - Added support for menu custom attributes
@@@ QA 140421-000076 core/framework/Libraries/Widget/tests/Input.test.php - core/widgets/standard/input/SelectionInput/tests/controller.test.php
@@@ QA 140417-000062 core/framework/Internal/Libraries/Widget/tests/Locator.test.php - Added test for widget specifying an invalid framework
@@@ QA 140421-000044 core/widgets/standard/input/SelectionInput/tests/attrs.test - Removed unused label_error attribute
@@@ QA 140416-000149 core/widgets/standard/input/DateInput/tests/controller.test.php - modified testGetDateArray
@@@ QA 140416-000149 core/widgets/standard/input/DateInput/tests/controller.test.php - added testGetConstraints
@@@ QA 140416-000149 core/widgets/standard/input/DateInput/tests/controller.test.php - added testGetMetaConstraints
@@@ QA 140130-000040 core/framework/Models/tests/ProdCat.test.php - added limit tests
@@@ QA 140130-000040 core/widgets/standard/search/ProductCategoryList//tests/maxLevels.test - added limit tests
@@@ QA 140130-000040 core/widgets/standard/search/ProductCategoryList//tests/maxLevels.test - added limit tests
@@@ QA 140326-000168 webfiles/core/debug-js/modules/widgetHelpers/tests/SearchFilter.test.js - Added "Consumers should know when the history manager is providing a response" test
@@@ QA 140514-000069 core/framework/Models/tests/ProdCat.test.php - Unit test performance refactoring
@@@ QA 140519-000122 core/framework/Models/tests/Clickstream.test.php - Updated testSetClickstreamEnabled
@@@ QA 140530-000064 core/widgets/standard/search/MobileProductCategorySearchFilter/tests/base.test.js - Added "New search with multiple product levels should maintain currentLevel"
@@@ QA 140304-000179 core/framework/Models/tests/Incident.test.php - Added testCreateWithResponseEmailPriorityPrimaryEmail
@@@ QA 140304-000179 core/framework/Models/tests/Incident.test.php - Added testCreateWithResponseEmailPriorityAltEmail
@@@ QA 140304-000179 core/framework/Models/tests/Incident.test.php - Added testCreateWithResponseEmailPriorityLoggedIn
@@@ QA 140304-000179 core/framework/Models/tests/Incident.test.php - Added testSubmitFeedbackWithResponseEmailPriority
@@@ QA 140304-000179 core/framework/Models/tests/Incident.test.php - Added testLookupEmailPriorityWithBadEmail
@@@ QA 140304-000179 core/framework/Models/tests/Incident.test.php - Added testLookupEmailPriorityWithPrimaryEmail
@@@ QA 140304-000179 core/framework/Models/tests/Incident.test.php - Added testLookupEmailPriorityWithAltEmail
@@@ QA 140821-000245 core/framework/Models/tests/Report.test.php - Updated testCustomFiltersToSearchArgs
@@@ QA 140821-000245 core/framework/Models/tests/Report.test.php - Added testBadFiltersToSearchArgs
@@@ QA 140821-000245 core/framework/Models/tests/Report.test.php - Added testIsFilterIDValid
@@@ QA 120824-000027 core/widgets/standard/notifications/Unsubscribe/tests/base.test - Updates rendering base.test
@@@ QA 131017-000057 core/widgets/standard/utils/CobrowsePremium/tests/base.test - Updated rendering unit test
@@@ QA 131017-000057 core/widgets/standard/utils/CobrowsePremium/tests/noOutputWhenV1Enabled.test - Rendering unit test to verify widget outputs nothing when v1 is turned ON
@@@ QA 131017-000057 core/widgets/standard/utils/CobrowsePremium/tests/noScriptForInvalidUrl.test - Rendering unit test to verify widget outputs nothing when script is not a valid url
@@@ QA 131017-000057 core/widgets/standard/utils/CobrowsePremium/tests/base.test.js - Added testLiveLookApi
@@@ QA 131017-000057 core/widgets/standard/utils/CobrowsePremium/tests/base.test.js - Added testLiveLookEvents
@@@ QA 140818-000100 core/widgets/standard/utils/CobrowsePremium/tests/base.test - Updated rendering unit test for asyn testing
@@@ QA 140723-000152 core/widgets/standard/chat/ChatCobrowsePremium/tests/base.test - Updated rendering unit test
@@@ QA 140723-000152 core/widgets/standard/chat/ChatCobrowsePremium/tests/noOutputWhenV1Enabled.test - Rendering unit test to verify widget outputs nothing when v1 is turned ON
@@@ QA 140723-000152 core/widgets/standard/chat/ChatCobrowsePremium/tests/noScriptForInvalidUrl.test - Rendering unit test to verify widget outputs nothing when script is not a valid url
@@@ QA 140723-000152 core/widgets/standard/chat/ChatCobrowsePremium/tests/base.test.js - Added testLiveLookApi
@@@ QA 140820-000035 core/widgets/standard/chat/ChatCobrowsePremium/tests/base.test.js - Added ACS unit tests
@@@ QA 140131-000130 core/framework/Internal/Utils/tests/Widgets.test.php - added 'testWidgetReplacerPaths'
@@@ QA 140131-000130 core/framework/Internal/Utils/tests/Widgets.test.php - added 'testWidgetReplacerVersions'
@@@ QA 140131-000130 core/framework/Internal/Utils/tests/Widgets.test.php - added 'testWidgetReplacerAttrs'
@@@ QA 140131-000130 core/framework/Internal/Libraries/Widget/tests/Registry.test.php - added 'testContainsPrefix'
@@@ QA 140131-000130 core/framework/Internal/Libraries/tests/Deployer.test.php - added 'testGetRenderCallPathList'
@@@ QA 140131-000130 core/framework/Internal/Utils/tests/Tags.test.php - updated 'testTransformTags'
@@@ QA 160309-000188 core/framework/Models/tests/Report.test.php - Updated testFormatViewsData
@@@ QA 160215-000102 core/framework/Controllers/Admin/tests/Versions.test.php - Added test for widgets no longer in use utility functions
@@@ QA: 160309-000188 core/framework/Models/tests/Report.test.php - Updated testFormatViewsData
@@@ QA: 160601-000189 core/widgets/standard/input/SmartAssistantDialog - Updated rendering tests
@@@ QA: 160601-000189 core/widgets/standard/okcs/OkcsSmartAssistant - Updated rendering tests
@@@ QA: 160601-000189 core/widgets/standard/user/UserActivity - Updated rendering tests
@@@ QA: 160601-000189 core/widgets/standard/user/UserContributions - Updated rendering tests
@@@ QA: 160519-000137 core/framework/Models/tests/CommunityUser.test.php - Added test for bad displayname
@@@ QA: 160906-000088 core/framework/Libraries/tests/Session.test.php - Added test for bad profile authToken
@@@ QA: 160601-000194 core/widgets/standard/navigation/VisualProductCategorySelector/test/controller.test.php - Added tests for controller functions. specifically 'testGetData', 'testLimitItems', 'testGetSubItems'
@@@ QA: 160601-000194 core/widgets/standard/navigation/VisualProductCategorySelector/test/prefetchsubitemsnonajax - Added rendering test for when pre_fetch_sub_items_non_ajax is true and pre_fetch_sub_items is false
