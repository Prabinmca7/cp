UnitTest.addSuite({
    type: UnitTest.Type.Widget,
    instanceID: 'PasswordInput_0'
}, function(Y, widget, baseSelector){
    //Firefox has an odd bug where calling focus and blur on elements doesn't cause all the event handlers
    //to fire immediately. Sometimes. Since this test has a ton of usage of those two methods, this is incredibly
    //difficult to debug, and works just fine in Chrome, we're going to avoid running these tests for now so we can
    //reliable determine when this is actually failing.
    if(Y.UA.gecko) {
        return new Y.Test.Suite({name: "standard/input/PasswordInput"}).add(
            new Y.Test.Case({
                name: "Firefox Hacks",
                "Skip tests for firefox": function() {
                    Y.assert(true);
                }
            })
        );
    }
    var tests = new Y.Test.Suite({
        name: "standard/input/PasswordInput",
        setUp: function(){
            var testExtender = {
                // Provides dependency injection in order to test various password configurations
                instantiate: function(requirements, attributes) {
                    this.instanceID = 'PasswordInput_0';
                    this.selector = "#rn_" + this.instanceID.replace(/\./g, "\\.");

                    var info = RightNow.Widgets.getWidgetInformation(this.instanceID);
                    this.instantiate._origWidget || (this.instantiate._origWidget = {
                        data: info.instance.data,
                        Y: info.instance.Y
                    });
                    this.instantiate._lastInstance || (this.instantiate._lastInstance = info.instance);
                    this.instantiate._clone || (this.instantiate._clone = Y.one(this.selector).ancestor('form').get('innerHTML'));
                    // Remove event listeners from the last test suite's widget instance
                    Y.one(this.selector).ancestor('form').remove();
                    Y.one(document.body).insert('<form id="rn_banana">' + this.instantiate._clone + '</form>', 0);

                    this.widgetData = RightNow.Lang.cloneObject(this.instantiate._origWidget.data);
                    this.widgetData.js.requirements = requirements;
                    if (typeof attributes !== 'undefined') {
                        this.widgetData.attrs = Y.mix(this.widgetData.attrs, attributes, true);
                    }
                    this.instance = new RightNow.Widgets.PasswordInput(this.widgetData, this.instanceID, this.instantiate._origWidget.Y);
                    this.instantiate._lastInstance = this.instance;
                },
                verifyErrorState: function(error) {
                    var assert = (error) ? 'isTrue' : 'isFalse';

                    Y.Assert[assert](Y.one(this.selector + '_Contact\\.NewPassword').hasClass('rn_ErrorField'));
                    Y.Assert[assert](Y.one(this.selector + '_Label').hasClass('rn_ErrorLabel'));
                },
                _verifyChecklistClass: function(validations, className) {
                    Y.Array.each(validations, function(val) {
                        Y.Assert.isTrue(Y.one(this.selector + ' .yui3-overlay li[data-validate="' + val.name + '"]').hasClass(className));
                    }, this);
                },
                verifyValidation: function(validation, incorrect, correct) {
                    var requirement = {};
                    if (!Y.Lang.isArray(validation)) {
                        validation = [validation];
                    }
                    Y.Array.each(validation, function(val) {
                        requirement[val.name] = {count: val.count, bounds: val.bounds, label: 'label'};
                    });
                    this.instantiate(requirement);
                    var input = Y.one(this.selector + '_Contact\\.NewPassword');

                    // Invalid
                    input.focus().set('value', incorrect);
                    this.unfocus();
                    this._verifyChecklistClass(validation, 'rn_Fail');
                    this.verifyErrorState(true);

                    // Valid
                    input.focus().set('value', correct);
                    this.unfocus();
                    this._verifyChecklistClass(validation, 'rn_Pass');
                    this.verifyErrorState();
                },
                unfocus: function() {
                    var field = Y.one('#focusonme');
                    if (field) {
                        field.focus();
                    }
                    else {
                        field = Y.Node.create('<input type="text" id="focusonme"/>');
                        Y.one(this.selector).append(field);
                        Y.one('#focusonme').focus();
                    }
                }
            };
            for(var item in this.items) {
                Y.mix(this.items[item], testExtender);
            }
        }
    });

    tests.add(new Y.Test.Case({
        name: "Unit",
        checkReturn: function(exp, actual, message) {
            if (
                exp.repetitions !== actual.repetitions ||
                exp.occurrences !== actual.occurrences ||
                exp.uppercase !== actual.uppercase ||
                exp.lowercase !== actual.lowercase ||
                exp.special !== actual.special ||
                exp.specialAndDigits !== actual.specialAndDigits ||
                exp.length !== actual.length
                ) {
                Y.Assert.fail(message);
            }
        },
        "Verify password stats computes the right thing": function() {
            this.instantiate();

            var actual,
                expected = {
                    repetitions: 0,
                    occurrences: 0,
                    uppercase: 0,
                    lowercase: 0,
                    special: 0,
                    specialAndDigits: 0,
                    length: 0
            },
            method = this.instance._getPasswordStats;

            actual = method('');
            this.checkReturn(expected, actual);

            actual = method('a');
            expected.occurrences = 1;
            expected.lowercase = 1;
            expected.length = 1;
            this.checkReturn(expected, actual);

            actual = method('abacad');
            expected.occurrences = 3;
            expected.lowercase = 6;
            expected.length = 6;
            this.checkReturn(expected, actual);

            actual = method('aaaa');
            expected.occurrences = 4;
            expected.repetitions = 4;
            expected.lowercase = 4;
            expected.length = 4;
            this.checkReturn(expected, actual);

            actual = method('aaaaA');
            expected.uppercase = 1;
            expected.length = 5;
            this.checkReturn(expected, actual);

            actual = method('@sdf$4');
            expected.length = 6;
            expected.lowercase = 3;
            expected.uppercase = 0;
            expected.special = 2;
            expected.specialAndDigits = 3;
            expected.occurrences = 1;
            expected.repetitions = 0;
            this.checkReturn(expected, actual);

            actual = method('ಠ_ಠ');
            expected.length = 3;
            expected.lowercase = 0;
            expected.uppercase = 0;
            expected.special = 3;
            expected.occurrences = 2;
            expected.specialAndDigits = 3;
            this.checkReturn(expected, actual);
        }
    }));

    tests.add(new Y.Test.Case({
        name: 'No validations',
        "A password without requirements shouldn't show an overlay when focused": function() {
            this.instantiate();
            Y.one(this.selector + '_Contact\\.NewPassword').focus();
            Y.Assert.isNull(Y.one('.yui3-overlay'));
        }
    }));

    tests.add(new Y.Test.Case({
        name: 'Focus',
        "Initial focus should happen on the correct field": function() {
            this.instantiate();
            if (this.widgetData.attrs.initial_focus) {
                if (this.widgetData.attrs.require_current_password)
                    Y.Assert.areSame(Y.one(this.selector + '_Contact\\.NewPassword_CurrentPassword').getDOMNode(), document.activeElement);
                else
                    Y.Assert.areSame(Y.one(this.selector + '_Contact\\.NewPassword').getDOMNode(), document.activeElement);
            }
        }
    }));

    tests.add(new Y.Test.Case({
        name: "Overlay",
        "Password validation behaves correctly": function() {
            this.instantiate({'length': {count: 2, bounds: 'min', label: 'foo'}});
            var input = Y.one(this.selector + '_Contact\\.NewPassword');

            // Appears on focus
            input.focus();
            var overlay = Y.one('.yui3-overlay');
            Y.Assert.isNotNull(overlay);
            Y.assert(!overlay.hasClass('yui3-overlay-hidden'));

            // Remains on blur
            this.unfocus();
            Y.assert(!overlay.hasClass('yui3-overlay-hidden'));

            // Disappears on blur when requirements are met
            input.focus();
            input.set('value', 'bananas');
            this.unfocus();
            Y.assert(overlay.hasClass('yui3-overlay-hidden'));
        },

        "Password verification overlay behaves correctly": function() {
            this.instantiate({'length': {count: 2, bounds: 'min', label: 'foo'}});
            if (!this.widgetData.attrs.require_validation) return;

            var validation = Y.one(this.selector + ' .rn_Validation');
            validation.focus();
            var overlay = Y.one('.yui3-overlay');

            // Appears on focus if password's overlay isn't showing
            Y.Assert.isNotNull(overlay);
            Y.assert(!overlay.hasClass('yui3-overlay-hidden'));

            // Hides on blur
            this.unfocus();
            Y.assert(overlay.hasClass('yui3-overlay-hidden'));

            Y.one(this.selector + '_Contact\\.NewPassword').set('value', '').focus();
            validation.focus();
            Y.assert(overlay.hasClass('yui3-overlay-hidden'));
        }
    }));

    tests.add(new Y.Test.Case({
        name: 'Digit validation',
        "Validation behavior is correct for digits": function() {
            this.verifyValidation({name: 'specialAndDigits', count: 4, bounds: 'min'}, 'abc123', 'ab1c234');
        }
    }));

    tests.add(new Y.Test.Case({
        name: 'Lower validation',
        "Validation behavior is correct for lowercase": function() {
            this.verifyValidation({name: 'lowercase', count: 6, bounds: 'min'}, 'abcde', 'AaBbCcDdEeFf');
        }
    }));


    tests.add(new Y.Test.Case({
        name: 'Upper validation',
        "Validation behavior is correct for uppercase": function() {
            this.verifyValidation({name: 'uppercase', count: 6, bounds: 'min'}, 'ABCDE', 'AaBbCcDdEeFf');
        }
    }));


    tests.add(new Y.Test.Case({
        name: 'Special validation',
        "Validation behavior is correct for special chars": function() {
            this.verifyValidation({name: 'special', count: 2, bounds: 'min'}, 'abcde!', '!AaBbCcDdEeFf!');
        }
    }));

    tests.add(new Y.Test.Case({
        name: 'Length validation',
        "Validation behavior is correct for min length": function() {
            this.verifyValidation({name: 'length', count: 6, bounds: 'min'}, 'abcde', 'AaBbCcDdEeFf');
        }
    }));

    tests.add(new Y.Test.Case({
        name: 'Repetition validation',
        "Validation behavior is correct for repetitions": function() {
            this.verifyValidation({name: 'repetitions', count: 6, bounds: 'max'}, 'abaaaaaaa', 'aaaaaAaBa');
        }
    }));

    tests.add(new Y.Test.Case({
        name: 'occurrences',
        "Validation behavior is correct for occurrences": function() {
            this.verifyValidation({name: 'occurrences', count: 6, bounds: 'max'}, 'ababababababababab', 'abababa');
        }
    }));

    tests.add(new Y.Test.Case({
        name: 'Several validations',
        "Upper and lower validation behavior is correct": function() {
            this.verifyValidation([
                {name: 'uppercase', count: 2, bounds: 'min'},
                {name: 'lowercase', count: 4, bounds: 'min'}
            ], 'aaaB', 'aBaBaBa');
        },

        "Special and Special+digits validation behavior is correct": function() {
            this.verifyValidation([
                {name: 'special', count: 2, bounds: 'min'},
                {name: 'specialAndDigits', count: 4, bounds: 'min'}
            ], 'aaa!B', 'a2BaB!aB@##a');
        },

        "Repetitions and occurrences validation behavior is correct": function() {
              this.verifyValidation([
                {name: 'occurrences', count: 2, bounds: 'max'},
                {name: 'repetitions', count: 4, bounds: 'max'}
            ], 'BBBBB', 'abcd');
        }
    }));

    tests.add(new Y.Test.Case({
        name: 'Verification field',
        "Verification overlay behavior is correct": function() {
            this.instantiate({'length': {count: 2, bounds: 'min', label: 'foo'}});

            if (!this.widgetData.attrs.require_validation) return;

            var input = Y.one(this.selector + '_Contact\\.NewPassword').focus().set('value', 'bananas');
            var validation = Y.one(this.selector + ' .rn_Password.rn_Validation').focus();
            var overlay = Y.all('.yui3-overlay').item(1);

            // Appears on focus
            Y.Assert.isNotNull(overlay);
            Y.assert(!overlay.hasClass('yui3-overlay-hidden'));

            validation.set('value', 'banana');

            // Remains on blur if passwords don't match, only if blur is a TAB press (screen reader users).
            validation.simulate('keydown', { keyCode: RightNow.UI.KeyMap.TAB });
            Y.Assert.isTrue(Y.one(document.activeElement).hasClass('rn_PasswordOverlay'));
            Y.assert(!overlay.hasClass('yui3-overlay-hidden'));

            // Hides on blur if passwords don't match, shift + TAB.
            validation.simulate('keydown', { keyCode: RightNow.UI.KeyMap.TAB, shiftKey: true });
            Y.assert(overlay.hasClass('yui3-overlay-hidden'));

            // Hides on blur if passwords don't match, blur is a click.
            validation.focus();
            this.unfocus();
            Y.assert(overlay.hasClass('yui3-overlay-hidden'));
            Y.Assert.isTrue(validation.hasClass('rn_ErrorField'));

            // Hides on blur when passwords match
            validation.focus().set('value', 'bananas');
            this.unfocus();
            Y.assert(overlay.hasClass('yui3-overlay-hidden'));
            Y.Assert.isFalse(validation.hasClass('rn_ErrorField'));
        },

        "Verification overlay isn't created before the field's focused": function() {
            this.instantiate();

            if (!this.widgetData.attrs.require_validation) return;

            var input = Y.one(this.selector + '_Contact\\.NewPassword').focus().set('value', 'bananas');
            var validation = Y.one(this.selector + ' .rn_Password.rn_Validation').focus();
            var overlay = Y.one('.yui3-overlay');
            Y.Assert.isNull(overlay);
        }
    }));

    tests.add(new Y.Test.Case({
        name: 'The correct error messages need to show up in the form error div',
        setUp: function() {
            this.errorDiv = Y.Node.create('<div id="errors"></div>');
            Y.one(document.body).append(this.errorDiv);
        },

        tearDown: function() {
            this.errorDiv.remove();
            this.errorDiv = null;
        },

        fireSubmit: function() {
            this.instance.onValidate('submit', [new RightNow.Event.EventObject(null, {data: {error_location: this.errorDiv.get('id')}})]);
        },

        'Password field is required': function() {
            this.instantiate({'length': {count: 2, bounds: 'min', label: 'foo'}}, {require_validation: false});
            this.fireSubmit();

            Y.Assert.areSame(1, this.errorDiv.all('a').size());
            var errorLink = this.errorDiv.one('a');
            Y.Assert.isTrue(errorLink.getHTML().indexOf(this.widgetData.attrs.label_input) > -1, errorLink.getHTML());
            errorLink.simulate('click');
            Y.Assert.areSame(Y.Node.getDOMNode(Y.one(this.selector).all('input').item(this.widgetData.attrs.require_current_password ? 1 : 0)), document.activeElement);
        },

        'Honor required attribute if there aren\'t any validations': function() {
            this.instantiate(null, {required: true, require_validation: false});
            this.fireSubmit();

            Y.Assert.areSame(1, this.errorDiv.all('a').size());
            var errorLink = this.errorDiv.one('a');
            Y.Assert.isTrue(errorLink.getHTML().indexOf(this.widgetData.attrs.label_input) > -1, errorLink.getHTML());
            errorLink.simulate('click');
            Y.Assert.areSame(Y.Node.getDOMNode(Y.one(this.selector).all('input').item(this.widgetData.attrs.require_current_password ? 1 : 0)), document.activeElement);
        },

        'Password verify field is not show to be required if there are validation errors': function() {
            // This is a 'no validation' test page. Setting `require_validation` will
            // just trigger a bunch of spurious errors since the widget's state and
            // HTML would be out of sync.
            if (!Y.one('input[name="Contact.NewPassword_Validation"]')) return;

            this.instantiate({'length': {count: 2, bounds: 'min', label: 'foo'}}, {require_validation: true});
            Y.all(this.selector + ' input').set('value', '');
            this.fireSubmit();

            Y.Assert.areSame(1, this.errorDiv.all('a').size());
        },

        'Password verify field does not show required if password is required': function() {
            // This is a 'no validation' test page. Setting `require_validation` will
            // just trigger a bunch of spurious errors since the widget's state and
            // HTML would be out of sync.
            if (!Y.one('input[name="Contact.NewPassword_Validation"]')) return;

            this.instantiate(null, {require_validation: true, required: true});
            Y.all(this.selector + ' input').set('value', '');
            this.fireSubmit();

            Y.Assert.areSame(1, this.errorDiv.all('a').size());
        },

        'Password verify field is not required when Password field is not required': function() {
            // This is a 'no validation' test page. Setting `require_validation` will
            // just trigger a bunch of spurious errors since the widget's state and
            // HTML would be out of sync.
            if (!Y.one('input[name="Contact.NewPassword_Validation"]')) return;

            this.instantiate(null, {require_validation: true, required: false});
            Y.all(this.selector + ' input').set('value', '');
            Y.Assert.areSame('', this.errorDiv.getHTML(), "Nothing set up");
            this.fireSubmit();

            Y.Assert.areSame('', this.errorDiv.getHTML(), "Should not be any error links");
        },

        'Password verify field is not required when Password field doesn\'t have length requirements': function() {
            // This is a 'no validation' test page. Setting `require_validation` will
            // just trigger a bunch of spurious errors since the widget's state and
            // HTML would be out of sync.
            if (!Y.one('input[name="Contact.NewPassword_Validation"]')) return;

            this.instantiate({occurrences: {bounds: 'max', count: 2, label: 'bananas'}, repetitions: {bounds: 'max', count: 3, label: 'moar bananas'}}, {require_validation: true, required: false});
            Y.all(this.selector + ' input').set('value', '');
            Y.Assert.areSame('', this.errorDiv.getHTML(), "Nothing set up");
            this.fireSubmit();

            Y.Assert.areSame('', this.errorDiv.getHTML(), "Should not be any error links");
        }
    }));

    tests.add(new Y.Test.Case({
        name: 'Test constraint change',

        setUp: function() {
            this.errorDiv = Y.Node.create('<div id="hereBeErrors"></div>');
            Y.one(document.body).append(this.errorDiv);
            new RightNow.Form({attrs: {challenge_location: 'test', error_location: 'hereBeErrors'}, js: {f_tok: 'filler'}}, 'banana', Y);
        },

        tearDown: function() {
            this.errorDiv.remove();
            this.errorDiv = this.input = this.validationField = null;
        },

        "Changing the requiredness should update the label and remove old error messages": function() {
            this.instantiate();

            var input = Y.one(baseSelector + '_Contact\\.NewPassword').set('value', '');
            if(this.instance.data.attrs.require_validation) {
                Y.one(baseSelector + '_Contact\\.NewPassword_Validate').set('value', '');
            }

            //Check the labels. They should NOT contain an asterisk or screen reader text.
            var labelContainer = Y.one(baseSelector + '_LabelContainer'),
                validateLabelContainer = Y.one(baseSelector + '_LabelValidateContainer'),
                validationData = [{data: {error_location: 'hereBeErrors'}}];

            Y.Assert.areSame('', this.errorDiv.get('innerHTML'));
            Y.Assert.isTrue(labelContainer.get('text').indexOf(this.instance.data.attrs.label_input) !== -1);
            Y.Assert.isTrue(labelContainer.get('text').indexOf('*') === -1);
            if(this.instance.data.attrs.require_validation) {
                Y.Assert.isTrue(validateLabelContainer.get('text').indexOf('*') === -1);
                Y.Assert.areSame(RightNow.Text.sprintf(this.instance.data.attrs.label_validation, this.instance.data.attrs.label_input), validateLabelContainer.get('text').trim());
            }

            //Alter the requiredness. Labels should be added.
            this.instance.fire('constraintChange', {'required': true});

            Y.Assert.isTrue(labelContainer.get('text').indexOf('Required') !== -1);
            Y.Assert.isTrue(labelContainer.get('text').indexOf('*') !== -1);
            if(this.instance.data.attrs.require_validation) {
                Y.Assert.isTrue(validateLabelContainer.get('text').indexOf('*') !== -1);
                Y.Assert.isTrue(validateLabelContainer.get('text').indexOf('Required') !== -1);
            }
            //Submitting the form should cause the fields to be highlighted and an error message added
            this.instance.onValidate('validate', validationData);

            Y.Assert.isTrue(this.errorDiv.get('childNodes').size() === 1);
            Y.Assert.isTrue(labelContainer.one('label').hasClass('rn_ErrorLabel'));
            Y.Assert.isTrue(input.hasClass('rn_ErrorField'));

            //Altering the requiredness again should remove labels and messages
            this.instance.fire('constraintChange', {'required': false});
            Y.Assert.isTrue(labelContainer.get('text').indexOf('*') === -1);
            if(this.instance.data.attrs.require_validation) {
                Y.Assert.isTrue(validateLabelContainer.get('text').indexOf('*') === -1);
            }
            Y.Assert.isTrue(labelContainer.get('text').indexOf('Required') === -1);
            if(this.instance.data.attrs.require_validation) {
                Y.Assert.isTrue(validateLabelContainer.get('text').indexOf('Required') === -1);
            }

            Y.Assert.isTrue(this.errorDiv.get('childNodes').size() === 0);
            Y.Assert.isFalse(labelContainer.one('label').hasClass('rn_ErrorLabel'));
            Y.Assert.isFalse(input.hasClass('rn_ErrorField'));
        }
    }));

    return tests;
}).run();
