UnitTest.addSuite({
    type: UnitTest.Type.Widget,
    instanceID: 'MobileProductCategoryInput_0',
    jsFiles: ['/euf/core/debug-js/RightNow.UI.Mobile.js']
}, function(Y, widget, baseSelector){
    var tests = new Y.Test.Suite({
        name: "standard/input/MobileProductCategoryInput",

        setUp: function(){
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
            for(var item in this.items) {
                Y.mix(this.items[item], testExtender);
            }
        }
    });

    tests.add(new Y.Test.Case({
        name: "Validation",

        testNoSelectionNoErrorLocation: function() {
            this.initValues();
            var returnVal = widget._onValidateRequest("submit", [{data: {error_location: true}}]);
            Y.Assert.isFalse(returnVal);
            Y.Assert.isTrue(Y.one("#" + this.baseID("_Label")).hasClass('rn_ErrorLabel'));
        },

        testNoSelectionErrorLocation: function() {
            this.initValues();
            this._errorLocation = Y.Node.create("<div id='banana'></div>");
            new Y.Node(document.body).insert(this._errorLocation, 0);
            var returnVal = widget._onValidateRequest("submit", [{data: {error_location: this._errorLocation.get('id')}}]);
            Y.Assert.isFalse(returnVal);
            Y.Assert.isTrue(Y.one("#" + this.baseID("_Label")).hasClass('rn_ErrorLabel'));
            Y.Assert.isTrue(this._errorLocation.get("innerHTML").indexOf(widget.data.attrs.label_prompt) > -1);
            Y.Assert.isTrue(this._errorLocation.get("innerHTML").indexOf(this.baseID("_Launch")) > -1);
            Y.Assert.isNotNull(this._errorLocation.one("a"));
        },

        testSingleLevel: function() {
            this.initValues();
            this.validateOpen();
            var clicked = this.click();
            this.validateClose(0);
            this.validateSelectionOnPage(1);
            this.validateSelectionInDialog(clicked);
            var expectedValue = parseInt(Y.one("#" + clicked).get("value"), 10);
            this.validateResponse(widget._onValidateRequest("submit", [{data: {error_location: this._errorLocation.get('id')}}]), expectedValue);
            Y.Assert.isFalse(Y.one("#" + this.baseID("_Label")).hasClass('rn_ErrorLabel'), 'error label!');
        },

        testMultiLevel: function() {
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
                    Y.Assert.isFalse(Y.one("#" + this.baseID("_Label")).hasClass('rn_ErrorLabel'), 'error label!');
                    var expectedValues = [];
                    Y.Array.each(clicked, function(id) {
                        expectedValues.push(parseInt(Y.one("#" + id).get("value"), 10));
                    });
                    this.validateResponse(widget._onValidateRequest("submit", [{data: {error_location: this._errorLocation.get('id')}}]), expectedValues.pop());
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
        }
    }));
    return tests;
});
UnitTest.run();