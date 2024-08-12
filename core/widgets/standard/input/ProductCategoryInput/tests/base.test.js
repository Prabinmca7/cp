UnitTest.addSuite({
    type: UnitTest.Type.Widget,
    instanceID: 'ProductCategoryInput_0'
}, function(Y, widget, baseSelector){
    var tests = new Y.Test.Suite({
        name: "standard/input/ProductCategoryInput",

        setUp: function(){
            var testExtender = {
                initValues : function() {
                    this.button = Y.one("#rn_" + widget.instanceID + "_" + widget.data.js.data_type + "_Button");
                    this.treeContainer = Y.one("#rn_" + widget.instanceID + "_TreeContainer");
                    this.tree = Y.one("#rn_" + widget.instanceID + "_Tree");
                    if(widget.data.attrs.show_confirm_button_in_dialog) {
                        this.confirmButton = Y.one('#rn_' + widget.instanceID + '_' + widget.data.js.data_type +  '_ConfirmButton');
                        this.cancelButton = Y.one('#rn_' + widget.instanceID + '_' + widget.data.js.data_type + '_CancelButton');
                    }
                }
            };

            for(var item in this.items) {
                Y.mix(this.items[item], testExtender);
            }
        }
    });

    tests.add(new Y.Test.Case({
        name: "Events",

        testGetNoSelectionResponse: function() {
            this.initValues();
            this.validateResponse(widget._onValidate("submit", [{data: {error_location: true}}]),
                (widget.data.attrs.required_lvl) ? false : null
            );
        },

        testMultiSelectionResponse: function() {
            var nodes = {
                Product: (widget.data.attrs.show_confirm_button_in_dialog)
                    ? ['#ygtvlabelel2', 'Blackberry']
                    : ['#ygtvt2 a', 'Blackberry'],
                Category: (widget.data.attrs.show_confirm_button_in_dialog)
                    ? ['#ygtvlabelel7', 'Call Quality']
                    : ['#ygtvt7 a', 'Call Quality']
            };
            var values = {
                Product: 3,
                Category: 77
            };
            this.initValues();
            this.button.simulate('click');
            var test = function() {
                RightNow.Event.unsubscribe("evt_menuFilterGetResponse", test);
                this.resume(function() {
                    var toClick;
                    Y.all('#ygtvc0 a').some(function(node) {
                        if (node.getContent() === nodes[widget.data.js.data_type][1]) {
                            toClick = node;
                            return true;
                        }
                    }, this);
                    toClick.simulate('click');
                    widget.data.attrs.show_confirm_button_in_dialog && this.button.simulate('click');
                    this.validateResponse(widget._onValidate("submit", [{data: {error_location: 'hereBeErrors'}}]),
                        values[widget.data.js.data_type]
                    );
                    Y.Assert.isTrue(toClick.ancestor('td').hasClass('ygtvfocus'));
                });
            };
            RightNow.Event.subscribe("evt_menuFilterGetResponse", test, this);
            Y.one(nodes[widget.data.js.data_type][0]).simulate('click');
            this.wait();
        },

        validateResponse: function(returnVal, selected) {
            if (selected === false) {
                Y.Assert.isFalse(returnVal);
            }
            else {
                if (selected === null) {
                    // Validating against no-selection.
                    // But if an item was previously set via url param or attribute,
                    // get that.
                    var defaultVal = widget.data.attrs.default_value ||
                        RightNow.Url.getParameter((widget.data.js.data_type) === 'Product' ? 'p' : 'c');
                    if (defaultVal) {
                        defaultVal = defaultVal.split(',');
                        selected = parseInt(defaultVal[defaultVal.length - 1], 10);
                    }
                }
                Y.Assert.isInstanceOf(RightNow.Event.EventObject, returnVal);
                Y.Assert.areSame(widget.data.attrs.name, returnVal.data.name);
                Y.Assert.areSame(selected, returnVal.data.value);
            }
        }
    }));

    tests.add(new Y.Test.Case({
        name: "UI Functional Tests",

        setUp: function() {
            this.iterations = 3;
        },

        isHidden: function() {
            return this.treeContainer.get('parentNode').hasClass('yui3-panel-hidden');
        },

        isVisible: function() {
            return !this.isHidden();
        },

        /**
         * Test of the ProductCategorySearchFilter's basic UI functionality to ensure
         * that it is working properly. Test the opening and closing of the menu and
         * whether the selection of a product/category will correctly show up as the
         * button's text.
         */
        testUI: function() {
            this.initValues();
            for (var i = 1; i < this.iterations; ++i) {
                this.button.simulate('click');
                Y.assert(this.isVisible(), 'panel is hidden when it shouldn\'t be');
                Y.one('#ygtvlabelel3').simulate('click');
                if(widget.data.attrs.show_confirm_button_in_dialog) {
                    //Ensure the panel is still visible until we click OK
                    Y.assert(this.isVisible(), 'panel is hidden when it shouldn\'t be');
                    Y.Assert.areSame(1000, parseInt(Y.one('.yui3-panel').getStyle('zIndex'), 10));
                    this.confirmButton.simulate('click');
                }
                Y.assert(this.isHidden(), 'panel is visible when it shouldn\'t be');
            }
        },

        testCancelButton: function() {
            this.initValues();
            if(widget.data.attrs.show_confirm_button_in_dialog) {
                //Open and close clicking cancel button
                Y.assert(this.isHidden(), 'panel is visible when it shouldn\'t be');
                this.button.simulate('click'); // To halt/cancel the event propagation of the previous this.button.simulate('click') event.
                this.button.simulate('click');
                Y.assert(this.isVisible(), 'panel is hidden when it shouldn\'t be');
                Y.one('#ygtvlabelel3').simulate('click');
                this.cancelButton.simulate('click');
                Y.assert(this.isHidden(), 'panel is visible when it shouldn\'t be');
            }
        },

        "The selected node should be updated when clicked": function() {
            this.initValues();

            //Reset the widget
            this.button.simulate('click');
            Y.one('#ygtvlabelel1').simulate('click');
            if(this.confirmButton) {
                this.confirmButton.simulate('click');
            }

            //Click around and check the selection
            this.button.simulate('click');
            Y.Assert.areSame(0, widget.tree.get('value'));

            Y.one('#ygtvcontentel3').simulate('click');
            if(this.confirmButton) {
                this.confirmButton.simulate('click');
            }
            Y.Assert.areSame(widget.data.js.data_type === 'Product' ? 6 : 153, widget.tree.get('value'));
        }
    }));

    tests.add(new Y.Test.Case({
        name: 'Required level functionality',

        _should: {
            error: {
                "Hint is re-aligned when a requirement message displays": !widget.data.attrs.always_show_hint
            }
        },

        "Hint is re-aligned when a requirement message displays": function () {
            if (!widget.data.attrs.always_show_hint && !widget.data.attrs.required_lvl) throw new Error("Test doesn't apply");

            this.initValues();

            //Reset the widget
            this.button.simulate('click');
            Y.one('#ygtvlabelel1').simulate('click');

            var eo = new RightNow.Event.EventObject(this, {data: {
                form: null,
                f_tok: 'token',
                error_location: Y.Node.create('<div>').appendTo(Y.one('form')).generateID(),
                timeout: 100000
            }});
             widget.parentForm().fire('collect', eo);

             Y.Assert.areSame(this.button.getY(), Y.one(baseSelector + ' .yui3-overlay').getY());
        }
    }));

    tests.add(new Y.Test.Case({
        name: 'Test constraint change',
        setUp: function() {
            this.errorDiv = 'hereBeErrors';
            Y.one(document.body).append('<div id="hereBeErrors">');
        },

        "Changing the requiredness should update the label and remove old error messages": function() {
            this.initValues();

            this.button.simulate('click');
            Y.one('#ygtvlabelel1').simulate('click');
            if(this.confirmButton) {
                this.confirmButton.simulate('click');
            }

            //Check the labels. They should NOT contain an asterisk or screen reader text.
            var errorDiv = Y.one('#' + this.errorDiv),
                labelContainer = Y.one(baseSelector + '_Label'),
                errorLabel = Y.one(baseSelector + "_ErrorLabel");
                requiredLabel = Y.one(baseSelector + "_RequiredLabel");
                validationData = [{data: {error_location: this.errorDiv}}],
                requiredStart = (widget.data.attrs.required_lvl > 0) ? 'isFalse' : 'isTrue';

            Y.Assert.areSame('', errorDiv.get('innerHTML'));
            Y.Assert.isTrue(labelContainer.get('text').indexOf(widget.data.attrs.label_input) !== -1);
            Y.Assert[requiredStart](requiredLabel.hasClass('rn_Hidden'));

            //Alter the requiredness. Labels should be added.
            widget.fire('constraintChange:required_lvl', { constraint: 3});

            Y.Assert.isTrue(errorLabel.get('text').indexOf(widget.data.attrs.label_nothing_selected) !== -1);
            Y.Assert.isFalse(requiredLabel.hasClass('rn_Hidden'));

            //Submitting the form should cause the fields to be highlighted and an error message added
            widget._onValidate('validate', validationData);

            Y.Assert.isTrue(errorDiv.get('childNodes').size() === 1);
            Y.Assert.isTrue(this.button.hasClass('rn_ErrorField'));

            //Altering the requiredness again should remove labels and messages
            widget.fire('constraintChange:required_lvl', { constraint: 0});
            Y.Assert.isTrue(labelContainer.get('text').indexOf('*') === -1);
            Y.Assert.isTrue(labelContainer.get('text').indexOf('Required') === -1);

            Y.Assert.isTrue(errorDiv.get('childNodes').size() === 0);
            Y.Assert.isFalse(this.button.hasClass('rn_ErrorField'));
        }
    }));

    return tests;
}).run();
