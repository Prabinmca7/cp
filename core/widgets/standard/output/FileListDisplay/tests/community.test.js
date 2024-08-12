UnitTest.addSuite({
    type: UnitTest.Type.Widget,
    instanceID: 'FileListDisplay_0'
}, function(Y, widget, baseSelector){
    var widgetNode = Y.one('#rn_FileListDisplay_0'),
        socialFileDisplayTests = new Y.Test.Suite({
            name: "standard/output/FileListDisplay"
        });

    socialFileDisplayTests.add(new Y.Test.Case({
        name: "Test social file attachment display",

        "Test that the widget renders updated file list upon successful edit": function() {
            widget._currentAttachments = [];
            var jsonData = '[{"ID":32,"FileName":"Something.txt","FileSize":"1.33 KB","AttachmentUrl":"\/ci\/fattach\/get\/32\/14900000\/cc\/14\/filename\/Something.txt?token=e1d6980d5","ContentType":"text/plain"}]',
                eo = new RightNow.Event.EventObject(widget, {data: {
                    instanceID: "FileListDisplay_0",
                    fileAttachments: jsonData
                }});
            RightNow.Event.fire('evt_editSuccessFileRefresh', eo);

            Y.Assert.areEqual(widget._currentAttachments.length, 1, "Attachments array is not updated.");
            Y.Assert.isNotNull(widgetNode.one('.rn_DataValue'), "The added file is not getting rendered");

            var fileAttachment = widgetNode.one('.rn_DataValue li'),
                setUrl = '/ci/fattach/get/32/14900000/cc/14/filename/Something.txt?token=e1d6980d5';
            Y.Assert.isTrue(fileAttachment.one('a').get('href').indexOf(setUrl) !== -1, "File URL is not getting set properly.");
            Y.Assert.areEqual("   Something.txt  ", fileAttachment.one('a').get('text'), "File name is not right.");
            Y.Assert.areEqual("(1.33 KB)", fileAttachment.one('.rn_FileSize').get('text'), "File Size isn't displayed correctly.");
            Y.Assert.areEqual("nofollow", fileAttachment.one('a').get('rel'), "No follow for rel attribute is not applied.");
        },
    }));
    return socialFileDisplayTests;
});
UnitTest.run();
