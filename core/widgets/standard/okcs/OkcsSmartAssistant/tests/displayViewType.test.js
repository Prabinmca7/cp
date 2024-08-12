UnitTest.addSuite({
    type: UnitTest.Type.Widget,
    instanceID: 'OkcsSmartAssistant_0'
}, function(Y, widget, baseSelector){
    var smartAssistantTests = new Y.Test.Suite({
        name: "standard/okcs/OkcsSmartAssistant - display view type functionality"
    });
    smartAssistantTests.add(new Y.Test.Case({
        name: 'Test display view type',

        setUp: function() {
            this._origMakeRequest = RightNow.Ajax.makeRequest;
            RightNow.Ajax.makeRequest = Y.bind(function() {
                this.makeRequestCalledWith = Array.prototype.slice.call(arguments);
            }, this);
        },

        tearDown: function() {
            RightNow.Ajax.makeRequest = this._origMakeRequest;
            this._origMakeRequest = null;
            this.makeRequestCalledWith = null;
        },

        suggestionResponse: {
            sessionParm: "",
            status: 1,
            sa: {
                canEscalate: true,
                token: '7',
                suggestions:[{
                    type: 'AnswerSummary',
                    list: [{
                        ID: 1,
                        title: "Test",
                        href: "http://test.com"
                    },
                    {
                        ID: 2,
                        title: "banana",
                        href: "http://banana.com"
                    },
                    {
                        ID: 3,
                        title: "apple",
                        href: "http://apple.com"
                    }]
                }]
            }
        },

        okcsAnswerResponse: {"error":null,"id":"0","contents":{"title":"Test Node1 Test Nod2 Test Node3 Test Document1 test  ","docID":"TE10","answerID":0,"version":"1.0","published":true,"publishedDate":"08\/29\/2014","content":{"0":{"name":"Title","value":"Test Document1","xPath":"TEST_CHANNEL\/TITLE"},"1":{"name":"Title","value":"Test Node1","xPath":"TEST_CHANNEL\/NODE1\/TITLE"},"2":{"name":"checkbox","value":"Y","type":"CHECKBOX","xPath":"TEST_CHANNEL\/CHECKBOX"},"3":{"name":"counter","value":"9","xPath":"TEST_CHANNEL\/COUNTER"},"4":{"name":"Date","value":"08\/28\/2014","xPath":"TEST_CHANNEL\/DATE"},"5":{"name":"DTTM","value":"08\/29\/2014 17:00:00","xPath":"TEST_CHANNEL\/DTTM"},"7":{"name":"Float","value":"12.25","xPath":"TEST_CHANNEL\/FLOAT"},"8":{"name":"Integer","value":"1234","xPath":"TEST_CHANNEL\/INTEGER"},"9":{"name":"RTA","value":"<p>test<\/p>","xPath":"TEST_CHANNEL\/RTA"},"10":{"name":"TA","value":"test","xPath":"TEST_CHANNEL\/TA"},"11":{"name":"TF","value":"test","xPath":"TEST_CHANNEL\/TF"},"12":{"name":"List","value":"Item 1","type":"LIST","xPath":"TEST_CHANNEL\/LIST"}},"metaContent":[{"name":"TF_Meta","value":"","xPath":"META\/TF"},{"name":"TA_Meta","value":"","xPath":"META\/TA"},{"name":"Title","value":"","xPath":"META\/TITLE"}],"contentType":{"recordID":"082020202843a8b50147a0ad1c9a007fbb","referenceKey":"TEST_CHANNEL","name":"Test_channel"},"resourcePath":"http:\/\/slc01fjo.us.oracle.com:8228\/fas\/resources\/14112\/content\/draft\/08202020e3271dd0147ac1c3c1306b2b\/08202020e3271dd0147ac1c3c1306b1a\/","locale":"en_US","error":null}},

        okcsFileAttachmentResponse: {"error":null,"id":"1000009","contents":{"title":"Windows 7 Alert messages","docID":"FI1","answerID":1000009,"version":"1.0","published":true,"publishedDate":"01\/16\/2015","content":{"FILE_ATTACHMENTS":{"name":null,"type":"NODE","xPath":"FILE_ATTACHMENTS","depth":0},"FILE_ATTACHMENTS\/FILE_ATTACHMENTS_TITLE":{"name":"File Attachments Title","type":"DISPLAY","value":"Windows 7 Alert messages","xPath":"FILE_ATTACHMENTS\/FILE_ATTACHMENTS_TITLE","depth":1},"FILE_ATTACHMENTS\/FILE_1":{"name":"File 1","value":"Enabling Alerts in Windows 7.pdf","type":"FILE","filePath":"foo\/fas\/resources\/okcs__ok152b1__t2\/content\/draft\/08202027e01e272014af15a7414007fb7\/08202027e01e272014af15a7414007fb3\/Enabling%20Alerts%20in%20Windows%207.pdf","xPath":"FILE_ATTACHMENTS\/FILE_1","depth":1}},"metaContent":null,"contentType":{"recordID":"08202027e01e272014af15a7414007fcd","referenceKey":"FILE_ATTACHMENTS","name":"File Attachments"},"resourcePath":"foo\/fas\/resources\/okcs__ok152b1__t2\/content\/draft\/08202027e01e272014af15a7414007fb7\/08202027e01e272014af15a7414007fb3\/","locale":"en_US","error":null}},



        verifyPromptContents: function(prompt) {
            Y.Assert.isTrue(prompt.hasClass('rn_Prompt'), "prompt is messed up!");
            Y.Assert.areSame('DIV', prompt.get('tagName'), "prompt is messed up!");
            Y.Assert.areSame(3, prompt.get('childNodes').size());
            Y.Assert.areSame(0, prompt.get('firstChild').get('textContent').indexOf(widget.data.attrs.label_prompt));
        },

        verifyListContents: function (list) {
            Y.Assert.isTrue(list.hasClass('rn_List'), "answer list is messed up!");
            Y.Assert.isTrue(list.hasClass('rn_InlineAnswers'), "answer list is messed up!");
            Y.Assert.areSame('UL', list.get('tagName'), "answer list is messed up!");

            var answerLinks = list.all('a');
            Y.Assert.areSame(this.suggestionResponse.sa.suggestions[0].list.length, answerLinks.size());

            answerLinks.each(function (item, index) {
                Y.Assert.areSame('rn_InlineAnswerLink rn_ExpandAnswer', item.get('className'));
                Y.Assert.areEqual(index + 1, item.getAttribute('accesskey'));
                Y.Assert.areSame('rn_' + widget.instanceID + '_Answer' + (index), item.get('id'));
            });
        },

        verifyInjectedOkcsAnswerContents: function () {
            var answerDetail = Y.one('.rn_AnswerSolution');

            Y.Assert.isNotNull(answerDetail);

            var children = answerDetail.getDOMNode().children;

            var firstChild = children.item(0);
            if (firstChild.nodeName === 'IFRAME') {
                Y.Assert.isNotNull(firstChild.getAttribute('title'));
            }
            else {
                Y.Assert.areSame(2, children.length);
                Y.Assert.areSame('rn_AnswerInfo rn_AnswerInfo_Top', firstChild.className);
                Y.Assert.isTrue(firstChild.firstElementChild.textContent.indexOf('Doc ID') > -1);
                Y.Assert.areSame('SECTION', firstChild.firstElementChild.nodeName);

                var secondChild = children.item(1);
                Y.Assert.areSame('rn_AnswerDetail', secondChild.className);
                Y.Assert.isNotNull(secondChild.firstElementChild.textContent);
                Y.Assert.areSame('rn_AnswerText', secondChild.firstElementChild.className);

                var channelData = Y.one('.rn_AnswerText').getDOMNode().children;
                Y.Assert.isNotNull(channelData.item(0));
                Y.Assert.areSame('rn_OkcsSmartAssistant_Test_Channel_Title', channelData.item(0).className);
                Y.Assert.isNotNull(channelData.item(1));
                Y.Assert.areSame('rn_OkcsSmartAssistant_Test_Channel_Node1_Title', channelData.item(1).className);
                Y.Assert.isNotNull(channelData.item(2));
                Y.Assert.areSame('rn_OkcsSmartAssistant_Test_Channel_Checkbox', channelData.item(2).className);
                Y.Assert.isNotNull(channelData.item(3));
                Y.Assert.areSame('rn_OkcsSmartAssistant_Test_Channel_Counter', channelData.item(3).className);
                Y.Assert.isNotNull(channelData.item(4));
                Y.Assert.areSame('rn_OkcsSmartAssistant_Test_Channel_Date', channelData.item(4).className);
                Y.Assert.isNotNull(channelData.item(5));
                Y.Assert.areSame('rn_OkcsSmartAssistant_Test_Channel_Dttm', channelData.item(5).className);
                Y.Assert.isNotNull(channelData.item(6));
                Y.Assert.areSame('rn_OkcsSmartAssistant_Test_Channel_Float', channelData.item(6).className);
                Y.Assert.isNotNull(channelData.item(7));
                Y.Assert.areSame('rn_OkcsSmartAssistant_Test_Channel_Integer', channelData.item(7).className);
                Y.Assert.isNotNull(channelData.item(8));
                Y.Assert.areSame('rn_OkcsSmartAssistant_Test_Channel_Rta', channelData.item(8).className);
                Y.Assert.isNotNull(channelData.item(9));
                Y.Assert.areSame('rn_OkcsSmartAssistant_Test_Channel_Ta', channelData.item(9).className);
                Y.Assert.isNotNull(channelData.item(10));
                Y.Assert.areSame('rn_OkcsSmartAssistant_Test_Channel_Tf', channelData.item(10).className);
                Y.Assert.isNotNull(channelData.item(11));
                Y.Assert.areSame('rn_OkcsSmartAssistant_Test_Channel_List', channelData.item(11).className);

                var channelCheckbox = Y.one('.rn_OkcsSmartAssistant_Test_Channel_Checkbox .rn_SchemaAttribute');
                Y.Assert.areSame(channelCheckbox.one('label').get('for'), channelCheckbox.one('input').get('id'));
            }
        },

        verifyInjectedOkcsAnswerContentsWithAttachment: function () {
            var answerDetail = Y.one('.rn_AnswerSolution');

            Y.Assert.isNotNull(answerDetail);

            var children = answerDetail.getDOMNode().children;

            var firstChild = children.item(0);
            if (firstChild.nodeName === 'IFRAME') {
                Y.Assert.isNotNull(firstChild.getAttribute('title'));
            }
            else {
                var channelData = Y.one('.rn_AnswerText').getDOMNode().children;
                Y.Assert.isNotNull(channelData.item(0));
                Y.Assert.areSame('rn_OkcsSmartAssistant_File_Attachments_File_Attachments_Title', channelData.item(0).className);
                Y.Assert.isNotNull(channelData.item(1));
                Y.Assert.areSame('rn_OkcsSmartAssistant_File_Attachments_File_1', channelData.item(1).className);
                Y.Assert.isNotNull(channelData.item(2));
            }
        },

        "The dialog's inline answer content is properly constructed": function() {
            widget._displayResults("response", [ { data: { result: this.suggestionResponse } } ]);

            Y.Assert.areSame(this.suggestionResponse.sa.token, RightNow.UI.Form.smartAssistantToken);

            var dialogContent = Y.one(baseSelector + "_DialogContent"),
                contents = dialogContent.get("children");

            this.verifyPromptContents(contents.item(0));
            this.verifyListContents(contents.item(1));
        },

        "Collpased result should have the corresponding accessibility text displayed": function() {
            var toggle = Y.one('#rn_OkcsSmartAssistant_0_Answer0');
            var screenReaderSpan = Y.one('#' + toggle.get('id') + '_Alternative');

            Y.Assert.isNotNull(Y.one(screenReaderSpan));
            Y.Assert.isTrue(screenReaderSpan.hasClass('rn_ScreenReaderOnly'));
            Y.Assert.areSame(screenReaderSpan.getHTML().trim(), widget.data.attrs.label_collapsed.trim(), "accessibility text for collapsed item is incorrect!");
        },

        "Clicking on an inline answer link fires a global request event before making an ajax request": function() {
            var dialogContent = Y.one(baseSelector + "_DialogContent"),
                clickedAnswer = dialogContent.one('a:first-child'),
                hasFiredRequest = false;
            RightNow.Event.subscribe("evt_getAnswerRequest", function(type, args) {
                hasFiredRequest = true;
                args = args[0];
                Y.assert(!this.makeRequestCalledWith);
                Y.Assert.areSame(args.w_id, widget.instanceID);
                Y.Assert.areSame(args.data.objectID, clickedAnswer.getAttribute('data-id'));
            }, this);
            clickedAnswer.simulate('click');
            Y.Assert.isTrue(hasFiredRequest, 'The request for inline content did not fire.');
        },

        "Content from answer response is injected into the list after a global response event is fired": function() {
            var dialogContent = Y.one(baseSelector + "_DialogContent"),
                clickedAnswer = dialogContent.one('a:first-child'),
                hasFiredResponse = false;

            RightNow.Event.subscribe("evt_getAnswerResponse", function() {
                hasFiredResponse = true;
            }, this);

            if (Y.one('.rn_AnswerSolution') !== null ){
                widget._onIMContentResponse(this.okcsAnswerResponse, {});
                this.verifyInjectedOkcsAnswerContents();
            }
        },

        "Content with attachment from answer response is injected into the list after a global response event is fired": function() {
            var dialogContent = Y.one(baseSelector + "_DialogContent"),
                clickedAnswer = dialogContent.one('a:first-child'),
                hasFiredResponse = false;

            RightNow.Event.subscribe("evt_getAnswerResponse", function() {
                hasFiredResponse = true;
            }, this);

            if (Y.one('.rn_AnswerSolution') !== null ){
                widget._onIMContentResponse(this.okcsFileAttachmentResponse, {});
                this.verifyInjectedOkcsAnswerContentsWithAttachment();
            }
        },

        "Buttons to hide result and open a result in new tab are available when display view type is 'explorer'": function() {
            var buttonContainer = Y.one('.rn_ButtonContainer');
            if (buttonContainer !== null) {
                var buttons = buttonContainer.getDOMNode().children,
                    hideButton = buttons.item(1),
                    newTabButton = buttons.item(0);

                Y.Assert.isNotNull(newTabButton);
                Y.Assert.isTrue(newTabButton.hasFocus());
                Y.Assert.isNotNull(hideButton);

                var newTab = Y.one('#' + newTabButton.getAttribute('id'));
                Y.Assert.areSame(widget.data.attrs.label_new_tab, newTab.getHTML());
                Y.Assert.isNotNull(newTab.getAttribute('data-url'));
            }
        },

        "Result should have text truncation from CSS when expanded": function() {
            var toggle = Y.one('a.rn_ExpandedAnswerExplorer');
            if (toggle !== null) {
                Y.Assert.isTrue(toggle.hasClass('rn_InlineAnswersLimitedText'));
            }
        },

        "Expanded result should have the corresponding accessibility text displayed": function() {
            var toggle = Y.one('a.rn_ExpandedAnswerExplorer');

            if (toggle !== null) {
                var screenReaderSpan = Y.one('#' + toggle.get('id') + '_Alternative');

                Y.Assert.isNotNull(Y.one(screenReaderSpan));
                Y.Assert.isTrue(screenReaderSpan.hasClass('rn_ScreenReaderOnly'));
                Y.Assert.areSame(screenReaderSpan.getHTML().trim(), widget.data.attrs.label_expanded.trim(), "accessibility text for expanded item is incorrect!");
            }
        },

        "Clicking the Hide button hides the answer content": function() {
            var expandedAnswer,
                buttonContainer = Y.one('.rn_ButtonContainer');
            if (buttonContainer !== null) {
                var buttons = buttonContainer.all('button'),
                hideButton = buttons.item(1);

                hideButton.simulate('click');
                expandedAnswer = Y.one('a.rn_ExpandedAnswerExplorer');
                Y.Assert.isNull(expandedAnswer);
            }
        },
        "Document unavailable scenario": function() {
            var errorResponse = {"error":null,"id":"1000044","contents":{"title":null,"docID":null,"recordID":null,"owner":null,"answerID":null,"version":null,"published":null,"publishedDate":false,"content":null,"metaContent":null,"contentType":null,"resourcePath":null,"locale":null,"error":{"externalMessage":"Resource Not Found","errorCode":"HTTP 404","source":"","internalMessage":null,"extraDetails":null,"displayToUser":false},"categories":null,"versionID":null},"ajaxTimings":[0,{"key":"KEY1","value":0.227979},0,{"key":"KEY2","value":0.04931},{"key":"DK3","value":0.3044900894165}]},
                hasFiredResponse = false;

            RightNow.Event.subscribe("evt_getAnswerResponse", function() {
                hasFiredResponse = true;
            }, this);

            if (Y.one('.rn_AnswerSolution') !== null ){
                widget._onIMContentResponse(this.errorResponse, {});
                var titleHeader = Y.one('.rn_Hero');
                var messagebody = Y.one('.rn_PageContent.rn_ErrorPage.rn_Container');
                
                Y.Assert.isNotNull(titleHeader);
                Y.Assert.isNotNull(messagebody);
                Y.Assert.areSame('This answer is no longer available.', messagebody.getData());
            }
        }
    }));
    return smartAssistantTests;
}).run();
