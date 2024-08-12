UnitTest.addSuite({
    type: UnitTest.Type.Widget,
    instanceID: 'SocialFileAttachmentUpload_0'
}, function(Y, widget, baseSelector){
    var parentForm = Y.one('form #rn_SocialFileAttachmentUpload_0') !== null,
        socialFileUploadTests = new Y.Test.Suite({
            name: "standard/input/SocialFileAttachmentUpload"
        });

    socialFileUploadTests.add(new Y.Test.Case({
        name: "Test social file attachment upload",

        "Test attachment list and details reset on evt_newCommentRefresh": function() {
            widget._attachmentCount = 1;
            widget._attachments = [{contentType: "text/plain", localName: "B9vqy3sitename.marias.us.oracle.com", userName: "Sample File.txt"}];
            widget._renderNewAttachmentItem("Sample File.txt", widget._attachmentCount);

            // check if node exists
            Y.Assert.isNotNull(Y.one("#rn_SocialFileAttachmentUpload_0 ul[role='alert'] li"), "No file attached.");

            RightNow.Event.fire('evt_newCommentRefresh', new RightNow.Event.EventObject(widget, {data: {instanceID: "SocialFileAttachmentUpload_0"}}));
            Y.Assert.isTrue((widget._attachmentCount === 0) ? true : false, "Attachment count is not reset.");
            Y.Assert.isNull(widget._attachmentList, "Attachment list is not reset.");
            Y.Assert.isTrue((widget._attachments.length === 0) ? true : false, "Attachment array is not reset.");
            Y.Assert.isNull(Y.one("#rn_SocialFileAttachmentUpload_0 ul[role='alert'] li"), "Attachment list is still getting displayed.");
        },

        "Test attachment list and details refresh on evt_editRefreshFileAttachments": function() {
            widget._attachmentCount = 1;
            widget._attachments = [{contentType: "text/plain", localName: "B9vqy3sitename.marias.us.oracle.com", userName: "Sample File.txt"}];
            widget._renderNewAttachmentItem("Sample File.txt", widget._attachmentCount);

            RightNow.Event.fire('evt_editRefreshFileAttachments', new RightNow.Event.EventObject(widget, {
                data: {
                    instanceID: "SocialFileAttachmentUpload_0",
                    fileAttachments: '[{"FileName":"sample_file.txt","ID":33,"ContentType":"text/plain"}]'
                }
            }));

            Y.Assert.isTrue((widget._attachmentCount === 1) ? true : false, "File attachment count is not 1");
            Y.Assert.isTrue((widget._attachments.length === 0) ? true : false, "Attachment array is not reset.");
            Y.Assert.isNull(Y.one("#rn_SocialFileAttachmentUpload_0 > ul[role='alert'] li"), "Attachment list is still getting displayed.");
            // cleanup
            Y.Node.one("#rn_SocialFileAttachmentUpload_0 .rn_ExistingFileAttachments").remove(true);
        },

        "Test max attachment list refresh on evt_editRefreshFileAttachments": function() {
            widget._attachmentCount = 1;
            widget._attachments = [{contentType: "text/plain", localName: "B9vqy3sitename.marias.us.oracle.com", userName: "Sample File.txt"}];
            widget._renderNewAttachmentItem("Sample File.txt", widget._attachmentCount);

            RightNow.Event.fire('evt_editRefreshFileAttachments', new RightNow.Event.EventObject(widget, {
                data: {
                    instanceID: "SocialFileAttachmentUpload_0",
                    fileAttachments: '[{"FileName":"sample_file.txt","ID":33,"ContentType":"text/plain"},{"FileName":"sample_file_1.txt","ID":34,"ContentType":"text/plain"},{"FileName":"sample_file_2.txt","ID":35,"ContentType":"text/plain"}]'
                }
            }));

            // attachmentCount is now reflecting data.fileAttachments array count
            Y.Assert.isTrue((widget._attachmentCount === 3) ? true : false, "File attachment count is not correct");
            Y.Assert.isTrue((widget._attachments.length === 0) ? true : false, "Attachment array is not reset.");
            Y.Assert.isNull(Y.one("#rn_SocialFileAttachmentUpload_0 > ul[role='alert'] li"), "Attachment list is still getting displayed.");
            Y.Assert.isTrue(Y.one("#rn_SocialFileAttachmentUpload_0_FileInput").hasAttribute("disabled"), "File input is not disabled when max count got reached.");
        }
    }));
    return socialFileUploadTests;
});
UnitTest.run();
