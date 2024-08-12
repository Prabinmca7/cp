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
                        Y.one('#' + this.baseID('_Launch')).simulate('click');
                        Y.Assert.isFalse(Y.one("#" + this.baseID('_Level1Input')).hasClass('rn_Hidden'));
                        Y.Assert.areSame("block", Y.one("#" + this.baseID('_Level1Input')).getComputedStyle("display"));
                    };
                    this.validateClose = function(times) {
                        if (typeof times !== "number") {
                            times = 1;
                        }
                        for (var i = 0; i < times; i++) {
                            Y.one("#rn_ActionDialog_Generated1").one("a.rn_Button").simulate('click');
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
                        var preselected = function(preselection, lvl, id) {
                            return preselection && id.indexOf(this.baseID("_Level" + lvl + "Input_" + preselection)) > -1;
                        };
                        selectedItems.each(function(node, i) {
                            var id = node.one('input').get('id');
                            if (Y.Array.indexOf(selectedIDs, id) > -1 || preselected.call(this, this.preselectedVals[i - 1], i + 1, id)) {

                            }
                            else {
                                Y.Assert.fail("Something is selected in the dialog that shouldn't be. Namely, " + id);
                            }
                        }, this);
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
        testPreselection: function() {
            this.initValues();
            for (var i = 0, element, ids = [], preselection = widget._getCommittedSelection(true); i < preselection.length; i++) {
                if (element = Y.one('.rn_MobileProductCategoryInput input[value="' + preselection[i] + '"]')) {
                    ids.push(element.get("id"));
                }
            }
            this.preselections = ids || [];
            this.preselectedVals = preselection || [];
        },

        testOpenAndClose: function() {
            this.initValues();
            this.validateOpen();
            this.validateClose();

            this.validateSelectionOnPage(this.preselectedVals.length);
            this.validateSelectionInDialog(this.preselections);
        },

        testMultiLevelNoSelection: function() {
            this.initValues();
            this.validateOpen();
            var clicked;
            var test = function() {
                RightNow.Event.unsubscribe("evt_menuFilterGetResponse", test);
                this.resume(function() {
                    this.validateClose(2);
                    this.validateSelectionOnPage(this.preselectedVals.length);
                    this.validateSelectionInDialog(this.preselections);
                });
            };
            RightNow.Event.subscribe("evt_menuFilterGetResponse", test, this);
            clicked = this.click(true);
            this.wait();
        },

        testSingleLevelSelection: function() {
            this.initValues();
            this.validateOpen();
            var test = function() {
                Y.Assert.fail("menu get request event was fired when it shouldn't have");
            };
            RightNow.Event.subscribe("evt_menuFilterGetResponse", test, this);
            var clicked = this.click();
            this.validateClose(0);
            this.validateSelectionOnPage(1);
            this.validateSelectionInDialog(clicked);
            RightNow.Event.unsubscribe("evt_menuFilterGetResponse", test);
        },

        testMultiLevelSelection: function() {
            this.initValues();
            this.validateOpen();
            var clicked = [];
            var test = function() {
                RightNow.Event.unsubscribe("evt_menuFilterGetResponse", test);
                this.resume(function() {
                    clicked.push(this.click());
                    this.validateClose(0);
                    this.validateSelectionOnPage(clicked.length);
                    this.validateSelectionInDialog(clicked);
                });
            };
            RightNow.Event.subscribe("evt_menuFilterGetResponse", test, this);
            clicked.push(this.click(true));
            this.wait();
        },

        testRemoveAndCloseFilter: function() {
            this.initValues();
            Y.one('#' + this.baseID('_Launch')).simulate('click');
            Y.one('.rn_Back .rn_Button').simulate('click');
            Y.one('#' + this.baseID('_Input1_1')).simulate('click');
            this.validateClose(0);
            this.validateSelectionOnPage();
            this.validateSelectionInDialog();
        }
    }));
    tests.add(new Y.Test.Case({
        name: "Events",

        testGetNoSelectionResponse: function() {
            this.initValues();
            this.validateResponse(widget._onValidateRequest("submit", [{
                data: {
                    error_location: 'hereBeErrors'
                }
            }]), 0);
        },

        testMultiSelectionResponse: function() {
            this.initValues();
            this.validateOpen();
            var clicked = [];
            var test = function() {
                RightNow.Event.unsubscribe("evt_menuFilterGetResponse", test);
                this.resume(function() {
                    clicked.push(this.click());
                    this.validateClose(0);
                    this.validateSelectionOnPage(clicked.length);
                    this.validateSelectionInDialog(clicked);
                    var expectedValues = [];
                    Y.Array.each(clicked, function(id) {
                        expectedValues.push(parseInt(Y.one("#" + id).get("value"), 10));
                    });
                    this.validateResponse(widget._onValidateRequest("submit", [{
                        data: {
                            error_location: 'hereBeErrors'
                        }
                    }]), expectedValues.pop());
                });
            };
            RightNow.Event.subscribe("evt_menuFilterGetResponse", test, this);
            clicked.push(this.click(true));
            this.wait();
        },

        validateResponse: function(returnVal, value) {
            Y.Assert.isUndefined(returnVal.data.cache);
            Y.Assert.areSame(widget.data.attrs.name, returnVal.data.name);
            Y.Assert.isNumber(returnVal.data.value);
            Y.Assert.areSame(value, returnVal.data.value);
        },

        testRemoveAndCloseFilter: function() {
            this.initValues();
            Y.one('#' + this.baseID('_Launch')).simulate('click');
            Y.one('.rn_Back .rn_Button').simulate('click');
            Y.one('#' + this.baseID('_Input1_1')).simulate('click');
            this.validateClose(0);
            this.validateSelectionOnPage();
            this.validateSelectionInDialog();
        }
    }));


    tests.add(new Y.Test.Case({
        name: 'Test constraint change',
        setUp: function() {
            this.errorDiv = 'hereBeErrors';
            Y.one(document.body).append('<div id="hereBeErrors">');
        },
        tearDown: function() {
            Y.one('#hereBeErrors').remove();
        },

        "Changing the requiredness should update the label and remove old error messages": function() {
            this.initValues();

            //Check the labels. They should NOT contain an asterisk or screen reader text.
            var errorDiv = Y.one('#' + this.errorDiv),
                labelContainer = Y.one('#' + this.baseID('_Label')),
                validationData = [{
                    data: {
                        error_location: this.errorDiv
                    }
                }],
                requiredStart = (widget.data.attrs.required_lvl > 0) ? 'isFalse' : 'isTrue';

            Y.Assert.areSame('', errorDiv.get('innerHTML'));
            Y.Assert.isTrue(labelContainer.get('text').indexOf(widget.data.attrs.label_input) !== -1);
            Y.Assert[requiredStart](labelContainer.get('text').indexOf('*') === -1);

            //Alter the requiredness. Labels should be added.
            widget.fire('constraintChange:required_lvl', {
                constraint: 3
            });

            Y.Assert.isTrue(labelContainer.get('text').indexOf('*') !== -1);
            Y.Assert.isTrue(labelContainer.one('.rn_Required').getAttribute('aria-label').indexOf('Required') !== -1);

            //Submitting the form should cause the fields to be highlighted and an error message added
            widget._onValidateRequest('validate', validationData);

            Y.Assert.isTrue(errorDiv.get('childNodes').size() === 1);
            Y.Assert.isTrue(labelContainer.hasClass('rn_ErrorLabel'));

            //Altering the requiredness again should remove labels and messages
            widget.fire('constraintChange:required_lvl', {
                constraint: 0
            });
            Y.Assert.isTrue(labelContainer.get('text').indexOf('*') === -1);
            Y.Assert.isNull(labelContainer.one('.rn_Required'));

            Y.Assert.isTrue(errorDiv.get('childNodes').size() === 0);
            Y.Assert.isFalse(labelContainer.hasClass('rn_ErrorLabel'));
        }
    }));

    return tests;
}).run();
