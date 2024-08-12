UnitTest.addSuite({
    type: UnitTest.Type.Widget,
    instanceID: 'SmartAssistantDialog_0'
}, function(Y, widget, baseSelector){
    var parentForm = Y.one('form #rn_SmartAssistantDialog_0') !== null,
        smartAssistantTests = new Y.Test.Suite({
            name: "standard/input/SmartAssistantDialog"
        });

    smartAssistantTests.add(new Y.Test.Case({
        name: "Test dialog display",
        "Event Subscription When the Form Response Doesn't Contain SA Member": function() {
            Y.Assert.isTrue(RightNow.UI.Form.smartAssistant);

            widget._displayResults("response", [{
                data: {
                    result: {
                        sessionParam: "",
                        status: 1
                    }
                }
            }]);

            Y.Assert.isTrue(RightNow.UI.Form.smartAssistant);
            Y.Assert.isNull(Y.one("#rnDialog1"));
        },
        "Response Contains SA, but no SA data": function() {
            Y.Assert.isTrue(RightNow.UI.Form.smartAssistant, "RightNow.UI.Form.smartAssistant is wrong!");

            widget._displayResults("response", [{
                data: {
                    result: {
                        sessionParam: "",
                        status: 1,
                        sa: {
                            canEscalate: true
                        }
                    }
                }
            }]);

            Y.Assert.isFalse(RightNow.UI.Form.smartAssistant, "RightNow.UI.Form.smartAssistant is wrong!");

            var dialog = Y.one('#rnDialog1');
            Y.Assert.isObject(dialog, "dialog is missing!");
            Y.Assert.isTrue(Y.one(dialog).hasClass('rn_Dialog'), "dialog doesn't have rn_Dialog classname!");
            Y.Assert.isTrue(Y.one(dialog).ancestor().hasClass('rn_SmartAssistantDialogContainer'), "dialog doesn't have rn_SmartAssistantDialogContainer classname!");

            var dialogTitle = Y.one("#rn_Dialog_1_Title");
            Y.Assert.isObject(dialogTitle);
            Y.Assert.areSame(dialogTitle.getHTML(), widget.data.attrs.label_dialog_title, "dialog title is incorrect!");

            //dialog's banner attribute has already been verified in the rendering UT...
            var dialogContent = Y.one(baseSelector + "_DialogContent");
            Y.Assert.isObject(dialogContent, "dialog contents not found!");
            Y.Assert.areSame(dialogContent.get('innerHTML'), widget.data.attrs.label_no_results, "dialog contents don't contain label_no_results message!");
            var buttons = dialog.all(".yui3-widget-ft button");
            Y.Assert.areSame(buttons.size(), 3 - widget.data.attrs.display_button_as_link.split(',').length);
            Y.Assert.areSame(buttons.item(0).getHTML(), '<span class="rn_ScreenReaderOnly">Finish submitting your question dialog, please read above text for dialog message </span>' + widget.data.attrs.label_solved_button, "solved button text is incorrect!");
            Y.Assert.areSame(buttons.item(1).getHTML(), widget.data.attrs.label_submit_button, "submit button text is incorrect!");
            Y.Assert.areSame(dialog.one('.yui3-widget-ft a').getHTML(), widget.data.attrs.label_cancel_button);
        },

        "Test the dialog's cancel button": function() {
            widget._displayResults("response", [{
                data: {
                    result: {
                        sessionParam: "",
                        status: 1,
                        sa: {
                            canEscalate: true
                        }
                    }
                }
            }]);

            var buttons = Y.all("#rnDialog2 .yui3-widget-ft button");
            Y.Assert.isObject(buttons, "buttons don't exist!");
            Y.Assert.areSame(buttons.size(), 3 - widget.data.attrs.display_button_as_link.split(',').length);
            try{
                Y.one('#rnDialog2 .yui3-widget-ft a').simulate('click');
                var dialogContainer = Y.one('#rnDialog2');
                Y.assert(dialogContainer.ancestor('.yui3-panel-hidden'), "Dialog wasn't closed!");
            }
            catch(e) {
                Y.Assert.isTrue(Y.UA.ie > 0);
            }
        },
        "Test the dialog's submit button": function() {
            //Pop the smartAssistant dialog
            widget._displayResults("response", [{
                data: {
                    result: {
                        sessionParam: "",
                        status: 1,
                        sa: {
                            canEscalate: true
                        }
                    }
                }
            }]);

            //Find the button
            var buttons = Y.all("#rnDialog3 .yui3-widget-ft button"),
                oldParentForm = widget.parentForm,
                hasFiredEvent = false;

            Y.Assert.isObject(buttons, "buttons don't exist!");
            Y.Assert.areSame(buttons.size(), 3 - widget.data.attrs.display_button_as_link.split(',').length);

            //Make sure the dialog is hidden and the event fired.
            widget.parentForm = function(id) {
                return {
                    fire: function(evtName) {
                        hasFiredEvent = true;
                        Y.Assert.isNull(document.getElementById(this.yuiDialogID + "_c"), "Dialog wasn't closed!");
                        Y.Assert.areSame(evtName, 'submitRequest', "Submit Request was never fired when clicked!");
                    }
                };
            };
            buttons.item(1).simulate('click');
            Y.Assert.isTrue(hasFiredEvent);
        },
        "Test the dialog's content when there's SA Data": function() {
            var answerSummary = "Answer Summary",
                answerSolution = "Answer Solution",
                answerDescription = "Answer Description",
                staticContent = "<a href='foo'>banana</a>",
                fileAttachmentTitle = "File Attachment Title";

            widget._displayResults("response", [{
                    data: {
                        result:{
                            sessionParam: "",
                            status: 1,
                            sa: {
                                canEscalate: true,
                                token: '7',
                                suggestions:[
                                    {
                                        type: 'AnswerSummary',
                                        list: [{
                                            ID: 52,
                                            title: "Enabling MMS on iPhone 3G and iPhone 3GS"
                                        }]
                                    },
                                    {
                                        type: 'Answer',
                                        title: 'Answer Summary',
                                        content: 'Answer Solution'
                                    },
                                    {
                                        type: 'StandardContent',
                                        content: "<a href='foo'>banana</a>"
                                    },
                                    {
                                        type: 'Answer',
                                        title: fileAttachmentTitle,
                                        FileAttachments: '32',
                                        ID: 52
                                    }
                                ]
                            }
                        }
                    }
                }
            ]);
            Y.Assert.areSame('7', RightNow.UI.Form.smartAssistantToken);
            var dialogContent = Y.one(baseSelector + "_DialogContent");
            Y.Assert.isObject(dialogContent, "dialog contents not found!");
            var contents = dialogContent.get("children")._nodes;
            Y.Assert.isTrue(Y.one(contents[0]).hasClass('rn_Prompt') && contents[0].tagName === "DIV", "prompt is messed up!");
            Y.Assert.isTrue(contents[0].innerHTML.indexOf(widget.data.attrs.label_prompt) > -1);

            Y.Assert.isTrue(Y.one(contents[1]).hasClass('rn_List') && contents[1].tagName === "UL", "answer list is messed up!");

            var answerLink = contents[1].getElementsByTagName("a");
            Y.Assert.isObject(answerLink, "couldn't find answer link!");
            Y.Assert.areSame(answerLink.length, 1);
            answerLink = answerLink[0];
            Y.Assert.areSame(answerLink.target, "_blank");

            Y.Assert.isTrue(Y.one(contents[2]).hasClass('rn_Answer') && contents[2].tagName === "DIV", "answer content be bad!");
            Y.Assert.isTrue(contents[2].children.length === 2);
            Y.Assert.areSame(contents[2].children[0].innerHTML, answerSummary);
            Y.Assert.isTrue(Y.one(contents[2].children[0]).hasClass('rn_Summary') && contents[2].children[0].tagName === "DIV");
            Y.Assert.areSame(contents[2].children[1].innerHTML, answerSolution);
            Y.Assert.isTrue(Y.one(contents[2].children[1]).hasClass('rn_Solution') && contents[2].children[1].tagName === "DIV");

            Y.Assert.isTrue(Y.one(contents[3]).hasClass('rn_Response') && contents[3].tagName === "DIV", "static content be horked!");
            Y.Assert.isTrue(contents[3].innerHTML.indexOf("_blank") > -1);
            widget._dialog.hide();

            Y.Assert.isTrue(Y.one(contents[4]).hasClass('rn_Answer') && contents[2].tagName === "DIV", "answer content be bad!");
            Y.Assert.isTrue(contents[4].children.length === 1);
            Y.Assert.areSame(contents[4].children[0].innerHTML, fileAttachmentTitle);
        },
        "Test old event fallback when there's no parent form": function() {
            var formID = 'hereBeErrors';
            if (parentForm) {
                Y.Assert.isNotUndefined(widget._parentFormID);
            }
            else {
                Y.Assert.isUndefined(widget._parentFormID);
                RightNow.Event.fire('evt_formButtonSubmitResponse', new RightNow.Event.EventObject(null, {data: {
                    form: formID,
                    result: {sa: {canEscalate: true}}
                }}));
                Y.Assert.areSame(formID, widget._parentFormID);
                widget._dialog.hide();
            }
        },

        "SA response with new form token triggers the global event to notify subscribers": function() {
            var calledTokenEvent = false;
            RightNow.Event.subscribe("evt_formTokenUpdate", function(evt, args) {
                calledTokenEvent = true;
                Y.Assert.isNull(args[0].w_id);
                Y.Assert.areSame('banana', args[0].data.newToken);
            }, this);
            widget._displayResults("response", [new RightNow.Event.EventObject(this, {data: {result: {sa: 1, newFormToken: 'banana'}}})]);
            Y.Assert.isTrue(calledTokenEvent);
        }
    }));
    return smartAssistantTests;
});
UnitTest.run();
