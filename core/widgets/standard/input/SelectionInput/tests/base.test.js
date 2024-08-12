UnitTest.addSuite({
    type: UnitTest.Type.Widget,
    instanceID: 'SelectionInput_0'
}, function(Y, widget, baseSelector){
    var suite = new Y.Test.Suite({
        name: 'standard/input/SelectionInput',

        setUp: function(){
            var testExtender = {
                isBoolean: function() { return widget.data.js.type === 'Boolean'; },
                isCheckBox: function() { return widget.data.js.type === 'Boolean' && widget.data.attrs.display_as_checkbox; },
                isRadio: function() { return widget.data.js.type === 'Boolean' && !widget.data.attrs.display_as_checkbox; }
            };

            for(var item in this.items) {
                Y.mix(this.items[item], testExtender);
            }
        }
    });

    suite.add(new Y.Test.Case({
        name: "Event Handling and Operation",

        testSetup: function() {
            var elementID = 'rn_' + widget.instanceID + '_' + widget.data.js.name,
                input = document.getElementById(elementID);

            if(widget.data.attrs.initial_focus) {
                Y.Assert.areSame(input, document.activeElement, 'input does not have initial focus');
            }

            // test default value
            if(widget.data.attrs.default_value) {
                Y.Assert.areSame(input.options[input.selectedIndex].value, widget.data.attrs.default_value, 'input does have expected default value');
            }

            // test province
            if(widget.data.js.name === 'Contact.Address.StateOrProvince') {
                var eventObject = new RightNow.Event.EventObject(widget, {data: {country_id: '1'}}),
                    response = {Provinces: [
                        {Name: 'alice', ID: '1'},
                        {Name: 'bob', ID: '2'},
                        {Name: 'carol', ID: '3'}
                    ]};
                RightNow.Event.fire("evt_provinceResponse", response, eventObject);
                Y.Assert.areSame(4, input.options.length);
            }

            // test requiredness (nothing set)
            if(this.isRadio()) {
                document.getElementById(elementID + '_0').checked = false;
                document.getElementById(elementID + '_1').checked = false;
            }
            else if(this.isCheckBox()) {
                input.checked = false;
            }
            else if(!widget.data.hideEmptyOption) {
                input.selectedIndex = 0;
            }

            widget.onValidate('submit', [{data: {error_location: 'hereBeErrors'}}]);

            if(widget.data.attrs.required)
                Y.Assert.areSame(1, Y.all('#hereBeErrors a').size());

            // test requiredness (something set)
            document.getElementById('hereBeErrors').innerHTML = '';
            if(this.isRadio()) {
                document.getElementById(elementID + '_1').checked = true;
            }
            else if(this.isCheckBox()) {
                input.checked = true;
            }
            else {
                input.selectedIndex = 1;
            }

            widget.onValidate('submit', [{data: {error_location: 'hereBeErrors'}}]);

            if(widget.data.attrs.required)
                Y.Assert.areSame(0, Y.all('#hereBeErrors a').size());

        },

        testOnChange: function() {
            var eventReceived = false,
                isBoolean = widget.data.js.type === 'Boolean',
                isRadio =  isBoolean && !widget.data.attrs.display_as_checkbox,
                inputID = baseSelector + '_' + widget.data.js.name.replace(/\./g, "\\."),
                input = isRadio? Y.one(inputID + '_1') : Y.one(inputID);

            RightNow.Event.on("evt_formInputDataChanged", function(args) {
                eventReceived = true;
            });
            input.simulate("change");
            Y.Assert.isTrue(eventReceived, "On Change event is not received");
        },

        //@@@ QA 130325-000044
        testHint: function() {
            if (!widget.data.attrs.hint || widget.data.attrs.hide_hint) {
                return;
            }

            var elementID = 'rn_' + widget.instanceID + '_' + widget.data.js.name,
                hintSelector = baseSelector + '_Hint',
                errors = [],
                that = this,
                isBoolean = widget.data.js.type === 'Boolean',
                isRadio =  isBoolean && !widget.data.attrs.display_as_checkbox,
                timeout = 100, input,
                checkVisibility = function(expected, msg, allowMozillaFail) {
                    // Assertions done in an inner function show up as uncaught exceptions, so log to errors list
                    // and process at the end within resume() which executes in the scope of the test.
                    try {
                        Y.Assert.areSame(expected, Y.one(hintSelector).getStyle('visibility'), msg);
                    }
                    catch (err) {
                        if (allowMozillaFail && Y.UA.gecko) {
                            console.log(msg + ' - Mozilla has timing issues when these tests run in parallel.');
                        }
                        else {
                            errors.push(err);
                        }
                    }
                },
                reportErrors = function() {
                    that.resume(function() {
                        for (var i = 0; i < errors.length; i++) {
                            Y.Assert.fail(errors[i].name + ': ' + errors[i].message);
                        }
                    });
                },
                afterBlur = function() {
                    if (widget.data.attrs.always_show_hint) {
                        checkVisibility('visible', 'Hint did not remain visible');
                    }
                    else {
                        checkVisibility('hidden', 'Hint not hidden after blur');
                    }
                    reportErrors();
                },
                afterFocus = function() {
                    checkVisibility('visible', 'Hint not visible after focus', true);
                    Y.one('#clickToBlur').focus();
                    setTimeout(afterBlur, timeout);
                };

            Y.one(baseSelector).append(Y.Node.create('<label for="clickToBlur">Click Me</label><input type="text" id="clickToBlur">'));

            if (isRadio) {
                input = document.getElementById(elementID + '_1');
            }
            else {
                input = document.getElementById(elementID);
            }


            // test initial state
            if (widget.data.attrs.initial_focus) {
                checkVisibility('visible', 'Hint not visible with initial focus');
            }
            else {
                checkVisibility('hidden', 'Hint not initially hidden');
            }

            // show hint
            if (isBoolean && Y.UA.webkit) {
                input.click();
            }
            else {
                input.focus();
            }

            setTimeout(afterFocus, timeout);
            this.wait();
        }
    }));

    suite.add(new Y.Test.Case({
        name: 'Test constraint change',

        setUp: function() {
            Y.one(document.body).append('<div id="hereBeErrors">');
            new RightNow.Form({attrs: {challenge_location: 'test', error_location: 'hereBeErrors'}, js: {f_tok: 'filler'}}, 'form', Y);
        },

        "Changing the requiredness should update the label and remove old error messages": function() {

            widget.input.set('value', '');
            if(this.isCheckBox() && Y.UA.gecko) {
                widget.input.getDOMNode().click();
            }

            //Check the labels. They should NOT contain an asterisk or screen reader text.
            var errorDiv = Y.one('#hereBeErrors'),
                isRadio = this.isRadio(),
                labelContainer = (isRadio) ? Y.one(baseSelector + '_Label') : Y.one(baseSelector + '_LabelContainer'),
                validationData = [{data: {error_location: 'hereBeErrors'}}],
                requiredAssertion = widget.data.attrs.required ? 'isFalse' : 'isTrue';

            Y.Assert.areSame('', errorDiv.get('innerHTML'));
            Y.Assert.isTrue(labelContainer.get('text').indexOf(widget.data.attrs.label_input) !== -1);
            if(!isRadio) {
                Y.Assert.isTrue(labelContainer.get('text').indexOf(widget.data.attrs.hint) !== -1);
            }
            Y.Assert[requiredAssertion](labelContainer.get('text').indexOf('*') === -1);

            //Alter the requiredness. Labels should be added.
            widget.fire('constraintChange', {'required': true});

            if (widget.data.attrs.label_input) {
                Y.Assert.isTrue(labelContainer.one('.rn_Required').getAttribute('aria-label').indexOf('Required') !== -1);
                if(!isRadio) {
                    Y.Assert.isTrue(labelContainer.get('text').indexOf(widget.data.attrs.hint) !== -1);
                }
                Y.Assert.isTrue(labelContainer.get('text').indexOf('*') !== -1);
            }

            //Submitting the form should cause the fields to be highlighted and an error message added
            widget.onValidate('validate', validationData);

            Y.Assert.isTrue(errorDiv.get('childNodes').size() === 1);
            Y.Assert.isTrue((isRadio) ? labelContainer.hasClass('rn_ErrorLabel') : labelContainer.one('label').hasClass('rn_ErrorLabel'));

            if(isRadio) {
                Y.Assert.isTrue(widget.input.hasClass('rn_ErrorField')[0]);
                Y.Assert.isTrue(widget.input.hasClass('rn_ErrorField')[1]);
            }
            else {
                Y.Assert.isTrue(widget.input.hasClass('rn_ErrorField'));
            }

            //Altering the requiredness again should remove labels and messages
            widget.fire('constraintChange', {'required': false});
            Y.Assert.isTrue(labelContainer.get('text').indexOf('*') === -1);
            Y.Assert.isNull(labelContainer.one('.rn_Required'));

            Y.Assert.isTrue(errorDiv.get('childNodes').size() === 0);
            Y.Assert.isFalse((isRadio) ? labelContainer.hasClass('rn_ErrorLabel') : labelContainer.one('label').hasClass('rn_ErrorLabel'));

            if(isRadio) {
                Y.Assert.isFalse(widget.input.hasClass('rn_ErrorField')[0]);
                Y.Assert.isFalse(widget.input.hasClass('rn_ErrorField')[1]);
            }
            else {
                Y.Assert.isFalse(widget.input.hasClass('rn_ErrorField'));
            }
        }
    }));

    return suite;
}).run();
