UnitTest.addSuite({
    type: UnitTest.Type.Widget,
    instanceID: 'MobileProductCategoryInput_0',
    jsFiles: ['/euf/core/debug-js/RightNow.UI.Mobile.js']
}, function(Y, widget, baseSelector) {
    var tests = new Y.Test.Suite({
        name: "standard/input/MobileProductCategoryInput",

        setUp: function() {
            var testExtender = {
                initValues: function() {
                    this.baseID = function(post) {
                        return 'rn_' + widget.instanceID + ((widget.data.attrs.name === 'Incident.Product') ? '_Product' : '_Category') + post;
                    };
                    this.validateOpen = function() {
                        widget._openDialog();
                        Y.Assert.isFalse(Y.one("#" + this.baseID('_Level1Input')).hasClass('rn_Hidden'));
                        Y.Assert.areSame("block", Y.one("#" + this.baseID('_Level1Input')).getComputedStyle("display"));
                    };
                    this.validateClose = function(times) {
                        if (typeof times !== "number") {
                            times = 1;
                        }
                        for (var i = 0; i < times; i++) {
                            // Copying what RightNow.UI.ActionDialog does to avoid simulating clicks
                            if(widget._dialog.hasPreviousContent()) {
                                widget._dialog.previousScreen();
                            }
                            else {
                                widget._dialog.hide();
                            }
                        }
                        Y.Assert.areSame("none", Y.one("#rn_ActionDialog_Generated1").getComputedStyle("display"));
                    };
                    this.validateSelectionOnPage = function(filters) {
                        if (typeof filters !== "number") {
                            filters = 0;
                        }
                        var assertion = (filters) ? 'isFalse' : 'isTrue',
                            text = Y.one("#" + this.baseID("_Launch")).get("innerHTML");
                        if (filters === 0) {
                            Y.Assert.areSame(widget.data.attrs.label_prompt, text);
                        }
                        else {
                            Y.Assert.areSame(filters, text.split("<br>").length - 1);
                        }
                    };
                    this.validateSelectionInDialog = function(selectedIDs) {
                        var selectedItems = Y.one("#rn_ActionDialog_Generated1").all(".rn_Selected");
                        if (!selectedIDs) {
                            selectedItems.each(function(node) {
                                if (node.one("input").get("id").indexOf("_Level") === -1) {
                                    Y.Assert.fail("A wrong top-level item remains selected in the dialog");
                                }
                            }, this);
                            return;
                        }
                        if (typeof selectedIDs === "string") {
                            selectedIDs = [selectedIDs];
                        }
                        selectedItems.each(function(node, i) {
                            var id = node.one('input').get('id');
                            Y.Assert.areNotSame(-1, Y.Array.indexOf(selectedIDs, id), "Something is selected in the dialog that shouldn't be. Namely, " + id);
                        }, this);
                        Y.Assert.areSame(selectedIDs.length, selectedItems.size(), 'Unexpected number of selected elements');
                    };
                    this.click = function(subLevel) {
                        var toClick = Y.all('.rn_PanelContent.rn_MobileProductCategoryInput > div');
                        for (var i = toClick.size() - 1; i > -1; i--) {
                            if (toClick.item(i).getComputedStyle("display") === "block") {
                                toClick = toClick.item(i);
                                break;
                            }
                        }
                        if (subLevel) {
                            toClick = toClick.one('label.rn_HasChildren');
                            toClick = Y.one('#' + toClick.getAttribute('for'));
                        }
                        else {
                            Y.all('label').some(function(label) {
                                if (!label.hasClass('rn_HasChildren')) {
                                    var input = Y.one('#' + label.getAttribute('for'));
                                    if (input.get('value') !== '0') {
                                        toClick = input;
                                        return true;
                                    }
                                }
                            });
                        }
                        toClick.simulate('click');
                        return toClick.get('id');
                    };
                }
            };
            for (var item in this.items) {
                Y.mix(this.items[item], testExtender);
            }
        }
    });

    tests.add(new Y.Test.Case({
        name: "UI functional tests",
        preselections: [],
        setUp: function() {
            this.origMakeRequest = RightNow.Ajax.makeRequest;
            RightNow.Ajax.makeRequest = RightNow.Event.createDelegate(this, this.makeRequest);
            this.madeRequest = false;
        },

        tearDown: function() {
            RightNow.Ajax.makeRequest = this.origMakeRequest;
        },

        makeRequest: function(url, data, options) {
            this.successCallback = options.successHandler;
            this.failureCallback = options.failureHandler;
            this.madeRequest = true;
            this.originalEventObject = options.data;
            this.callbackContext = options.scope;
        },

        //Go down path, exit. Go back to same path, verify path is correctly highlighted
        'Verify going down the same path twice correcly highlights selections': function() {
            this.initValues();
            this.validateSelectionOnPage(0);
            this.validateOpen();
            Y.one('#' + this.baseID('_Input1_2')).simulate('click');
            Y.Assert.isTrue(this.madeRequest, 'Ajax was not set up properly');
            this.successCallback.call(this.callbackContext, {
                "result": [
                    [{
                        "id": 2,
                        "label": "Android",
                        "hasChildren": true
                    }, {
                        "id": 3,
                        "label": "Blackberry",
                        "hasChildren": false
                    }, {
                        "id": 4,
                        "label": "iPhone",
                        "hasChildren": true
                    }]
                ]
            }, this.originalEventObject);
            Y.one('#' + this.baseID('_Level2Input_1_3')).simulate('click');
            this.validateClose(2);
            this.validateOpen();
            Y.one('#' + this.baseID('_Input1_2')).simulate('click');
            this.validateSelectionInDialog([this.baseID('_Input1_2'), this.baseID('_Level2Input_1_3')]);
            this.validateClose(2);
            this.validateOpen();
            Y.one('#' + this.baseID('_Input1_1')).simulate('click');
            this.validateClose(1);
        },

        //Go down path, exit, go down different path, exit, go back to first path, verify first path isn't highlighted from original trip down.
        'Verify going down different paths correctly unhighlights old selections': function() {
            this.validateSelectionOnPage(0);
            this.validateOpen();
            Y.one('#' + this.baseID('_Input1_2')).simulate('click');
            Y.one('#' + this.baseID('_Level2Input_1_3')).simulate('click');
            this.validateSelectionOnPage(2);
            this.validateClose(2);
            this.validateOpen();
            Y.one('#' + this.baseID('_Input1_7')).simulate('click');
            Y.Assert.isTrue(this.madeRequest, 'Ajax was not set up properly');
            this.successCallback.call(this.callbackContext, {
                "result": [
                    [{
                        "id": 132,
                        "label": "p1a",
                        "hasChildren": false
                    }, {
                        "id": 133,
                        "label": "p1b",
                        "hasChildren": false
                    }, {
                        "id": 129,
                        "label": "p2",
                        "hasChildren": true
                    }]
                ]
            }, this.originalEventObject);
            Y.one('#' + this.baseID('_Level2Input_128_3')).simulate('click');
            this.validateSelectionOnPage(2);
            this.validateClose(2);
            this.validateOpen();
            Y.one('#' + this.baseID('_Input1_7')).simulate('click');
            this.validateClose(2);
            this.validateOpen();
            Y.one('#' + this.baseID('_Input1_2')).simulate('click');
            this.validateSelectionInDialog([this.baseID('_Input1_7'), this.baseID('_Level2Input_128_3')]);
            this.validateClose(2);
            this.validateOpen();
            Y.one('#' + this.baseID('_Input1_1')).simulate('click');
            this.validateClose(1);
        },

        'Verify making a selection adds screen reader text': function() {
            this.initValues();
            this.validateSelectionOnPage(0);
            this.validateOpen();
            Y.one('#' + this.baseID('_Input1_2')).simulate('click');
            Y.one('#' + this.baseID('_Level2Input_1_3')).simulate('click');
            this.validateClose(2);
            Y.Assert.areSame(Y.one('.rn_Label .rn_ScreenReaderOnly').get('text'), RightNow.Text.sprintf(widget.data.attrs.label_current_selection_screenreader,
                Y.one("#" + this.baseID("_Launch")).get('innerHTML').replace(/<br>/g, " ")));
        }
    }));

    return tests;
}).run();
