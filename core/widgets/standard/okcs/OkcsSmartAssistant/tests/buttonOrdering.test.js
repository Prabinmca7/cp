UnitTest.addSuite({
    type: UnitTest.Type.Widget,
    instanceID: 'OkcsSmartAssistant_0'
}, function(Y, widget, baseSelector){
    var smartAssistantTests = new Y.Test.Suite({
        name: "standard/okcs/OkcsSmartAssistant"
    });

    smartAssistantTests.add(new Y.Test.Case({
        name: "Test ordering of dialog buttons",
            "Buttons are ordered according to what's specified in the attribute value": function() {
                widget._displayResults("response", [{
                    data: {
                        result: {
                            sessionParm: "",
                            status: 1,
                            sa: {
                                canEscalate: true
                            }
                        }
                    }
                }]);

                var buttons = Y.all('#rnDialog1 .yui3-widget-ft button');
                Y.Assert.isObject(buttons, "buttons don't exist!");
                Y.Assert.areSame(buttons.size(), 3);

                Y.Assert.areSame(buttons.item(0).getHTML(), '<span class="rn_ScreenReaderOnly">Finish submitting your question dialog, please read above text for dialog message </span>' + widget.data.attrs.label_submit_button);
                Y.Assert.areSame(buttons.item(1).getHTML(), widget.data.attrs.label_cancel_button);
                Y.Assert.areSame(buttons.item(2).getHTML(), widget.data.attrs.label_solved_button);

                try{
                    Y.one(buttons.item(1)).simulate('click');
                    Y.assert(Y.one('#rnDialog1').ancestor('.yui3-panel-hidden'), "Dialog wasn't closed!");
                }
                catch(e) {
                    Y.Assert.isTrue(Y.UA.ie > 0);
                }
            }
    }));

    smartAssistantTests.add(new Y.Test.Case({
        name: "Test rebuild of dialog after do not create",
            "Dialog is rebuilt after incident do not create order is received": function() {
                widget._displayResults("response", [{
                    data: {
                        result: {
                            sessionParm: "",
                            status: 1,
                            sa: {
                                canEscalate: false
                            }
                        }
                    }
                }]);

                var buttons = Y.all('#rnDialog2 .yui3-widget-ft button');
                Y.Assert.isObject(buttons, "buttons don't exist!");
                Y.Assert.areSame(buttons.size(), 1);

                Y.Assert.areSame(buttons.item(0).getHTML(), '<span class="rn_ScreenReaderOnly">Finish submitting your question dialog, please read above text for dialog message </span>' + widget.data.attrs.label_cancel_button);

                try{
                    Y.one(buttons.item(0)).simulate('click');
                    Y.assert(Y.one('#rnDialog2').ancestor('.yui3-panel-hidden'), "Dialog wasn't closed!");
                }
                catch(e) {
                    Y.Assert.isTrue(Y.UA.ie > 0);
                }

                widget._displayResults("response", [{
                    data: {
                        result: {
                            sessionParm: "",
                            status: 1,
                            sa: {
                                canEscalate: true,
                                token: '7',
                                    suggestions:[{
                                        type: 'AnswerSummary',
                                        list: [{
                                            ID: 1,
                                            title: "Enabling MMS on iPhone 3G and iPhone 3GS",
                                            href: "http://iphone.com"
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
                        }
                    }
                }]);

                buttons = Y.all('#rnDialog3 .yui3-widget-ft button');
                Y.Assert.isObject(buttons, "buttons don't exist!");
                Y.Assert.areSame(buttons.size(), 3);

                Y.Assert.areSame(buttons.item(0).getHTML(), '<span class="rn_ScreenReaderOnly">Finish submitting your question dialog, please read above text for dialog message </span>' + widget.data.attrs.label_submit_button);
                Y.Assert.areSame(buttons.item(1).getHTML(), widget.data.attrs.label_cancel_button);
                Y.Assert.areSame(buttons.item(2).getHTML(), widget.data.attrs.label_solved_button);

                try{
                    Y.one(buttons.item(1)).simulate('click');
                    Y.assert(Y.one('#rnDialog3').ancestor('.yui3-panel-hidden'), "Dialog wasn't closed!");
                }
                catch(e) {
                    Y.Assert.isTrue(Y.UA.ie > 0);
                }
            }
    }));
    return smartAssistantTests;
});
UnitTest.run();
