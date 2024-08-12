UnitTest.addSuite({
    type: UnitTest.Type.Framework,
    jsFiles: [
        '/euf/core/debug-js/RightNow.UI.js',
        '/euf/core/debug-js/RightNow.Text.js',
        '/euf/core/debug-js/RightNow.Url.js',
        '/euf/core/debug-js/RightNow.Ajax.js',
        '/euf/core/debug-js/RightNow.UI.AbuseDetection.js',
        '/euf/core/debug-js/RightNow.Event.js',
        '/euf/core/debug-js/modules/widgetHelpers/EventProvider.js',
        '/euf/core/debug-js/modules/widgetHelpers/Form.js',
        '/euf/core/debug-js/modules/widgetHelpers/Field.js'
    ],
    namespaces: [
        'RightNow.UI.findParentForm'
    ]
}, function(Y){
    var Location = window.location.href;
    RightNow.Interface.Constants = {"ACTION_ADD":1,"ANY_FILTER_VALUE":"~any~","EUF_DT_CHECK":12,"EUF_DT_DATE":1,"EUF_DT_DATETIME":2,"EUF_DT_FATTACH":11,"EUF_DT_HIERMENU":9,"EUF_DT_INT":5,"EUF_DT_MEMO":6,"EUF_DT_PASSWORD":7,"EUF_DT_RADIO":3,"EUF_DT_SELECT":4,"EUF_DT_THREAD":10,"EUF_DT_VARCHAR":8,"INT_NULL":-2147483647};
    function createForm(id, action) {
        var form = Y.Node.create("<form id='" + id + "'><div id='rn_widget_" + id + "'><input type='submit' id='rn_widget_" + id + "_Button'></div></form>");
        if (action) {
            form.set('action', action);
        }
        Y.one(document.body).appendChild(form);

        Y.one(document.body).append('<div id="test">');
        new RightNow.Form({attrs: {challenge_location: 'test', error_location: 'test'}, js: { f_tok: 'bananas' }}, 'test_form', Y);
    }

    var tests = new Y.Test.Suite({
        name: "Field",
        setUp: function() {
            createForm('rn_test_form');
        }
    });
    tests.add(new Y.Test.Case({
        name: "trimField",

        testTrimField: function() {
            var widget = RightNow.Field.extend({
                input: Y.Node.create('<input type="text"/>'),
                set: function(v) { this.input.set('value', v); },
                get: function() { return this.input.get('value'); },
                test: function() {
                    this.set("   ");
                    this._trimField();
                    Y.Assert.areSame("", this.get());
                    this.set("1   ");
                    this._trimField();
                    Y.Assert.areSame("1", this.get());
                    this.set("  a b ");
                    this._trimField();
                    Y.Assert.areSame("a b", this.get());
                    this.set("abc");
                    this._trimField();
                    Y.Assert.areSame("abc", this.get());
                    this.set("12,34 a                ");
                    this._trimField();
                    Y.Assert.areSame("12,34 a", this.get());
                }
            });
            new widget({
                attrs: {},
                js: {
                    name: 'fieldName',
                    type: 'String'
                }
            }, 'test_form', Y).test();
            new widget({
                attrs: {},
                js: {
                    name: 'fieldName',
                    type: 'Thread'
                }
            }, 'test_form', Y).test();
            new widget({
                attrs: {},
                js: {
                    name: 'fieldName',
                    type: 'Integer'
                }
            }, 'test_form', Y).test();
        },

        testPasswordField: function() {
            var widget = RightNow.Field.extend({
                test: function() {
                    this._value = "   ";
                    this._trimField();
                    Y.Assert.areSame("   ", this._value);
                    this._value = "1   ";
                    this._trimField();
                    Y.Assert.areSame("1   ", this._value);
                    this._value = "  a b ";
                    this._trimField();
                    Y.Assert.areSame("  a b ", this._value);
                    this._value = "abc";
                    this._trimField();
                    Y.Assert.areSame("abc", this._value);
                    this._value = "12,34 a                ";
                    this._trimField();
                    Y.Assert.areSame("12,34 a                ", this._value);
                }
            });
            new widget({
                attrs: {},
                js: {
                    name: 'fieldName',
                    type: 'Password'
                }
            }, 'test_form', Y).test();
        },

        testDateField: function() {
            var widget = RightNow.Field.extend({
                test: function() {
                    this._value = "   ";
                    this._trimField();
                    Y.Assert.areSame("   ", this._value);
                    this._value = "1   ";
                    this._trimField();
                    Y.Assert.areSame("1   ", this._value);
                    this._value = "  a b ";
                    this._trimField();
                    Y.Assert.areSame("  a b ", this._value);
                    this._value = "abc";
                    this._trimField();
                    Y.Assert.areSame("abc", this._value);
                    this._value = "12,34 a                ";
                    this._trimField();
                    Y.Assert.areSame("12,34 a                ", this._value);
                }
            });
            new widget({
                attrs: {},
                js: {
                    name: 'fieldName',
                    type: 'Date'
                }
            }, 'test_form', Y).test();
        }
    }));

    tests.add(new Y.Test.Case({
        name: "initializeHint",

        "A span is inserted as the overlay when Y.Overlay isn't provided": function() {
            var widget = RightNow.Field.extend({
                test: function() {
                    var wrapper = this.Y.Node.create("<div><input type='text'></div>");
                    this.input = wrapper.one('input');
                    this._initializeHint();
                    Y.Assert.areSame(2, wrapper.get('children').size());
                    Y.Assert.areSame('rn_HintText', wrapper.get('children').item(1).get('className'));
                    Y.Assert.areSame("banana", wrapper.get('children').item(1).get('innerHTML'));
                }
            });
            new widget({
                attrs: {hint: "banana"},
                js: {
                    name: 'fieldName',
                    type: 'Date'
                }
            }, 'test_form', Y).test();
        },

        "The Y.Overlay hint is rendered inside the widget div as the last child and blurs and focuses properly": function() {
            var test = this;

            YUI().use('overlay', function(y) {
                test.resume(function() {
                    var wrapper = y.Node.create("<div id='rn_overlaytest'><input type='text'></div>");
                    Y.one('#rn_test_form').append(wrapper);

                    var widget = RightNow.Field.extend({
                        testCreation: function() {
                            this.input = wrapper.one('input');

                            this._initializeHint();

                            this.overlay = wrapper.one(this.baseSelector + '_Hint');
                            var yuiOverlayNode = this.overlay.ancestor('.yui3-overlay');
                            Y.Assert.areSame(this.input.next(), yuiOverlayNode);
                            Y.Assert.isNull(this.input.next().next());
                            Y.Assert.areSame('rn_HintBox', this.overlay.get('className'));
                            Y.Assert.areSame('banana', this.overlay.get('innerHTML'));
                            Y.Assert.isNotNull(yuiOverlayNode);
                            Y.Assert.isNotNull(this.overlay.ancestor('.yui3-overlay-hidden')); // Initially hidden
                            Y.Assert.areSame(3, parseInt(yuiOverlayNode.getStyle('zIndex'), 10));

                            return this;
                        },
                        testCreationRightHint: function() {
                            this.input = wrapper.one('input');

                            this.data.info.class_name = 'RightNow.Widgets.SelectionInput';
                            this.data.js.type = 'not boolean';
                            this._initializeHint();

                            Y.Assert.areSame('rn_HintBoxRight', this.overlay.get('className'));

                            return this;
                        },
                        testFocus: function() {
                            if (!Y.UA.gecko){ // escaping test case for Firefox since it doesn't handle the onFocus event very well. Bugzilla bug #53579
                                y.one(this.baseSelector).one('input').focus();
                                Y.Assert.isFalse(this.overlay.ancestor('.yui3-overlay').hasClass('yui3-overlay-hidden'));
                            }
                            return this;
                        },
                        testBlur: function() {
                            var a = this.Y.Node.create('<a href="#">FocusOnMe</a>');
                            this.Y.one(document.body).insert(a);
                            if (!Y.UA.gecko){ // escaping test case for Firefox since it doesn't handle the onFocus event very well. Bugzilla bug #53579
                                a.focus();
                                Y.Assert.isTrue(this.overlay.ancestor('.yui3-overlay').hasClass('yui3-overlay-hidden'));
                            }
                            return this;
                        }
                    });
                    new widget({
                        attrs: {hint: "banana"},
                        js: {
                            name: 'fieldName',
                            type: 'Date'
                        }
                    }, 'overlaytest', y)
                        .testCreation()
                        .testFocus()
                        .testBlur();

                    var wrapper = y.Node.create("<div id='rn_overlaytest2'><input type='text'><span id='testSpan'>extra info</span></div>");
                    Y.one('#rn_test_form').append(wrapper);
                    var widget2 = RightNow.Field.extend({
                        testCreation: function() {
                            this.input = wrapper.one('input');

                            this._initializeHint();

                            this.overlay = wrapper.one(this.baseSelector + '_Hint');
                            Y.Assert.areSame(this.input.next().next(), this.overlay.ancestor().ancestor().ancestor());
                            Y.Assert.isNull(this.input.next().next().next());
                            Y.Assert.areSame('rn_HintBox', this.overlay.get('className'));
                            Y.Assert.areSame("banana", this.overlay.get('innerHTML'));
                            Y.Assert.isNotNull(this.overlay.ancestor('.yui3-overlay'));
                            Y.Assert.isNotNull(this.overlay.ancestor('.yui3-overlay-hidden')); // Initially hidden

                            return this;
                        }
                    });
                    new widget2({
                        attrs: {hint: "banana"},
                        js: {
                            name: 'fieldName',
                            type: 'Date'
                        }
                    }, 'overlaytest2', y)
                        .testCreation();
                });
            });
            this.wait();
        },

        "A hint that's always showing shouldn't get inserted via JS": function(){
            var widget = RightNow.Field.extend({
                test: function() {
                    var wrapper = this.Y.Node.create("<div><input type='text'></div>");
                    this.input = wrapper.one('input');
                    this._initializeHint();
                    Y.Assert.areSame(1, wrapper.get('children').size()); //Since we're always showing the hint, it shouldn't get added by the init call
                }
            });
            new widget({
                attrs: {hint: "banana", always_show_hint: true},
                js: {
                    name: 'fieldName',
                    type: 'Date'
                }
            }, 'test_form', Y).test();
        }
    }));
    tests.add(new Y.Test.Case({
        name: "getValue",

        testGetValue: function() {
            var widget = RightNow.Field.extend({
                test: function() {
                    var value;
                    this.input = this.Y.Node.create("<input type='text'>");
                    this.input.set("value", "foo");
                    value = this.getValue();
                    Y.Assert.areSame("foo", value);
                    // Empty string is converted to null
                    this.input.set("value", "");
                    value = this.getValue();
                    Y.Assert.isNull(value);

                    this._fieldName = "Contact.NewPassword";
                    this.input.set("value", "");
                    value = this.getValue();
                    Y.Assert.areSame("", value);
                    this.input.set("value", "    ");
                    value = this.getValue();
                    Y.Assert.areSame("    ", value);
                    this.input.set("value", "    a    ");
                    value = this.getValue();
                    Y.Assert.areSame("    a    ", value);

                    this._fieldName = "Contact.NewPassword_Validate";
                    this.input.set("value", "");
                    value = this.getValue();
                    Y.Assert.areSame("", value);
                    this.input.set("value", "    ");
                    value = this.getValue();
                    Y.Assert.areSame("    ", value);
                    this.input.set("value", "    a    ");
                    value = this.getValue();
                    Y.Assert.areSame("    a    ", value);
                    this._fieldName = null;

                    this.data.js.type = 'Integer';
                    this.input.set("value", "123");
                    value = this.getValue();
                    Y.Assert.areSame(123, value);
                    this.input.set("value", " 123");
                    value = this.getValue();
                    Y.Assert.areSame(123, value);
                    this.input.set("value", "123 ");
                    value = this.getValue();
                    Y.Assert.areSame(123, value);
                    this.input.set("value", " 123 ");
                    value = this.getValue();
                    Y.Assert.areSame(123, value);

                    this.input.set("value", "");
                    value = this.getValue();
                    Y.Assert.areSame("", value);
                    this.input.set("value", null);
                    value = this.getValue();
                    Y.Assert.areSame("", value);

                    this.input.set("value", "123df34");
                    value = this.getValue();
                    Y.Assert.areSame("123df34", value);
                    this.input.set("value", "123.34");
                    value = this.getValue();
                    Y.Assert.areSame("123.34", value);
                    this.input.set("value", "123,df34");
                    value = this.getValue();
                    Y.Assert.areSame("123,df34", value);
                    this.input.set("value", "123 df34");
                    value = this.getValue();
                    Y.Assert.areSame("123 df34", value);
                    this.input.set("value", ",123 df34");
                    value = this.getValue();
                    Y.Assert.areSame(",123 df34", value);
                    this.input.set("value", " 123 df34");
                    value = this.getValue();
                    Y.Assert.areSame("123 df34", value);
                    this.input.set("value", "a123 df34");
                    value = this.getValue();
                    Y.Assert.areSame("a123 df34", value);

                    this.data.js.type = 'NamedIDLabel';
                    this.input = this.Y.Node.create("<select><option value='1'>Foo</option></select>");
                    value = this.getValue();
                    Y.Assert.areSame("1", value);
                    this.input = this.Y.Node.create("<select><option value='1'>Foo</option><option value='2'>Foo</option><option value='3' selected>Foo</option></select>");
                    value = this.getValue();
                    Y.Assert.areSame("3", value);

                    this.data.js.type = 'Boolean';
                    this.input = this.Y.Node.create("<input type='checkbox'>");
                    value = this.getValue();
                    Y.Assert.areSame(false, value);
                    this.input.set("checked", "true");
                    value = this.getValue();
                    Y.Assert.areSame(true, value);

                    this.data.js.type = 'Boolean';
                    this.input = this.Y.Node.create("<div><input type='radio' value='1' name='foo'><input type='radio' value='0' name='foo'></div>").all('input');
                    value = this.getValue();
                    Y.Assert.areSame("", value);
                    this.input.item(0).set("checked", true);
                    value = this.getValue();
                    Y.Assert.areSame("1", value);
                    this.input.item(0).set("checked", false);
                    this.input.item(1).set("checked", true);
                    value = this.getValue();
                    Y.Assert.areSame("0", value);

                    this.data.js.type = 'Date';
                    this.input = this.Y.Node.create("<div><select><option value='1'>Foo</option></select><select><option value='1'>Foo</option></select><select><option value='1'>Foo</option></select></div>").all('select');
                    value = this.getValue();
                    Y.Assert.areSame("", value);

                    this.data.js.type = 'Date';
                    this.input = this.Y.Node.create("<div><select name='day'><option value='1' selected='selected'>Foo</option></select><select name='month'><option value='12' selected='selected'>Foo</option></select><select name='year'><option selected='selected' value='2011'>Foo</option></select></div>").all('select');
                    value = this.getValue();
                    Y.Assert.areSame("2011-12-1 0:0:00", value);

                    this.data.js.type = 'Date';
                    this.input = this.Y.Node.create("<div><select name='day'><option value selected='selected'>Foo</option></select><select name='month'><option value selected='selected'>Foo</option></select><select name='year'><option selected='selected' value>Foo</option></select></div>").all('select');
                    value = this.getValue();
                    Y.Assert.areSame("", value);

                    this.data.js.type = 'DateTime';
                    this.input = this.Y.Node.create("<div>" +
                        "<select name='day'><option value='1'>Foo</option></select><select name='hour'><option value='1'>Foo</option></select><select name='month'><option value='1'>Foo</option></select>" +
                        "<select name='year'><option value='1'>Foo</option></select><select name='minute'><option value='1'>Foo</option></select>" +
                        "</div>").all('select');
                    value = this.getValue();
                    Y.Assert.areSame("", value);

                    this.data.js.type = 'Date';
                    this.input = this.Y.Node.create("<div>" +
                        "<select><option value='1'>Foo</option></select><select><option value='1'>Foo</option></select><select><option value='1'>Foo</option></select>" +
                        "<select><option value='1'>Foo</option></select><select><option value='1'>Foo</option></select>" +
                        "</div>").all('select');
                    value = this.getValue();
                    Y.Assert.areSame("", value);

                    this.data.js.type = 'DateTime';
                    this.input = this.Y.Node.create("<div>" +
                        "<select name='day'><option value='5' selected='selected'>Foo</option></select><select name='hour'><option value='5' selected='selected'>Foo</option></select><select name='month'><option value='5' selected='selected'>Foo</option></select>" +
                        "<select name='year'><option value='2005' selected='selected'>Foo</option></select><select name='minute'><option value='55' selected='selected'>Foo</option></select>" +
                        "</div>").all('select');
                    value = this.getValue();
                    Y.Assert.areSame("2005-5-5 5:55:00", value);

                    // @@@ QA 120927-000089 - make sure date fields are properly matched (ie, Birthday_Month should not match when looking for the "day")
                    this.data.js.type = 'Date';
                    this.input = this.Y.Node.create("<div>" +
                        "<select name='rn_DateInput_51_Contact.CustomFields.CO.Birthday_Day'><option value='5' selected='selected'>Foo</option></select>" +
                        "<select name='rn_DateInput_51_Contact.CustomFields.CO.Birthday_Month'><option value='6' selected='selected'>Foo</option></select>" +
                        "<select name='rn_DateInput_51_Contact.CustomFields.CO.Birthday_Year'><option value='2005' selected='selected'>Foo</option></select>" +
                        "</div>").all('select');
                    value = this.getValue();
                    Y.Assert.areSame("2005-6-5 0:0:00", value);

                    this.data.js.type = 'FileAttachmentIncident';
                    Y.Assert.isUndefined(this.getValue());

                    this.data.js.type = 'ServiceProduct';
                    this._selectedNode = {hierValue: 150};
                    Y.Assert.areSame(150, this.getValue());

                    this.data.js.type = 'ServiceCategory';
                    this._selectedNode = {hierValue: 160};
                    Y.Assert.areSame(160, this.getValue());

                }
            });
            new widget({
                attrs: {},
                js: {
                    name: 'fieldName',
                    type: 'String'
                }
            }, 'test_form', Y).test();
        }
    }));
    tests.add(new Y.Test.Case({
        name: "checkIsRequired",

        testCheckIsRequired: function() {
            var widget = RightNow.Field.extend({
                test: function() {
                    var value;
                    this.data.attrs.required = false;
                    Y.Assert.isFalse(this._isRequired(), '1');
                    this.data.attrs.required = true;
                    Y.Assert.isTrue(this._isRequired(), '2');

                    this.data.js.type = 'ServiceCategory';
                    Y.Assert.isFalse(this._isRequired(), '3');
                    this.data.attrs.required = true;
                    Y.Assert.isFalse(this._isRequired(), '4');
                    this.data.attrs.required_lvl = 0;
                    Y.Assert.isFalse(this._isRequired(), '5');
                    this.data.attrs.required_lvl = 1;
                    Y.Assert.isTrue(this._isRequired(), '6');
                    this.data.attrs.required_lvl = 6;
                    Y.Assert.isTrue(this._isRequired(), '7');
                    this.data.attrs.required_lvl = -1;
                    Y.Assert.isFalse(this._isRequired(), '8');

                    this.data.js.type = 'ServiceProduct';
                    Y.Assert.isFalse(this._isRequired());
                    this.data.attrs.required = true;
                    Y.Assert.isFalse(this._isRequired());
                    this.data.attrs.required_lvl = 0;
                    Y.Assert.isFalse(this._isRequired());
                    this.data.attrs.required_lvl = 1;
                    Y.Assert.isTrue(this._isRequired());
                    this.data.attrs.required_lvl = 6;
                    Y.Assert.isTrue(this._isRequired());
                    this.data.attrs.required_lvl = -1;
                    Y.Assert.isFalse(this._isRequired());

                    this.data.js.type = 'FileAttachmentIncident';
                    Y.Assert.isFalse(this._isRequired());
                    this.data.attrs.required = true;
                    Y.Assert.isFalse(this._isRequired());
                    this.data.attrs.required_lvl = 0;
                    Y.Assert.isFalse(this._isRequired());
                    this.data.attrs.required_lvl = 1;
                    Y.Assert.isFalse(this._isRequired());
                    this.data.attrs.min_required_attachments = 0;
                    Y.Assert.isFalse(this._isRequired());
                    this.data.attrs.min_required_attachments = 1;
                    Y.Assert.isTrue(this._isRequired());
                    this.data.attrs.min_required_attachments = 6;
                    Y.Assert.isTrue(this._isRequired());
                    this.data.attrs.min_required_attachments = -1;
                    Y.Assert.isFalse(this._isRequired());
                }
            });
            new widget({
                attrs: {hint:"banana"},
                js: {
                    name: 'fieldName',
                    type: 'Integer'
                }
            }, 'test_form', Y).test();
        }
    }));
    tests.add(new Y.Test.Case({
        name: "checkRequired",

        testCheckRequired: function() {
            var widget = RightNow.Field.extend({
                test: function() {
                    var expectedErrors = 0;
                    var value;
                    this.data.attrs.required = false;
                    this._value = null;
                    value = this._checkRequired();
                    Y.Assert.isFalse(value);
                    this._value = "";
                    value = this._checkRequired();
                    Y.Assert.isFalse(value);
                    this._value = "fsdfsdf";
                    value = this._checkRequired();
                    Y.Assert.isFalse(value);

                    this.data.attrs.required = true;
                    value = this._checkRequired();
                    Y.Assert.isFalse(value);
                    this._value = "";
                    value = this._checkRequired();
                    Y.Assert.isTrue(value);
                    Y.Assert.areSame(++expectedErrors, this._errors.length);
                    this._value = null;
                    value = this._checkRequired();
                    Y.Assert.isTrue(value);
                    Y.Assert.areSame(++expectedErrors, this._errors.length);
                    this._value = false;
                    value = this._checkRequired();
                    Y.Assert.isTrue(value);
                    Y.Assert.areSame(++expectedErrors, this._errors.length);

                    this.data.js.type = 'Date';
                    this._value = "";
                    value = this._checkRequired();
                    Y.Assert.isTrue(value);
                    Y.Assert.areSame(++expectedErrors, this._errors.length);
                    this._value = "1-2-1";
                    value = this._checkRequired();
                    Y.Assert.isFalse(value);
                    Y.Assert.areSame(expectedErrors, this._errors.length);

                    this.data.js.type = 'DateTime';
                    this._value = "";
                    value = this._checkRequired();
                    Y.Assert.isTrue(value);
                    Y.Assert.areSame(++expectedErrors, this._errors.length);
                    this._value = "1-2-1 12:";
                    value = this._checkRequired();
                    Y.Assert.isFalse(value);
                    Y.Assert.areSame(expectedErrors, this._errors.length);
                }
            });
            new widget({
                attrs: {hint:"banana"},
                js: {
                    name: 'fieldName',
                    type: 'Integer'
                }
            }, 'test_form', Y).test();
        }
    }));
    tests.add(new Y.Test.Case({
        name: "checkUrl",

        testCheckUrl: function() {
            var widget = RightNow.Field.extend({
                test: function() {
                    this._errors = [];
                    this._value = null;
                    this._checkUrl();
                    Y.Assert.areSame(0, this._errors.length);
                    this._value = "";
                    this._checkUrl();
                    Y.Assert.areSame(0, this._errors.length);
                    this._value = "sdfsf";
                    this._checkUrl();
                    Y.Assert.areSame(1, this._errors.length);
                    this._value = "httP:/ww.go.o";
                    this._checkUrl();
                    Y.Assert.areSame(2, this._errors.length);
                    this._value = "http://www.google.com";
                    this._checkUrl();
                    Y.Assert.areSame(2, this._errors.length);
                }
            });
            new widget({
                attrs: {hint:"banana"},
                js: {
                    name: 'fieldName',
                    type: 'Date',
                    // The value of these two properties shouldn't
                    // matter; it's not up to #checkUrl to conditionally
                    // verify based on their state.
                    custom: false,
                    url: false
                }
            }, 'test_form', Y).test();
        }
    }));
    tests.add(new Y.Test.Case({
        name: "politeFocus",

        testPoliteFocus: function() {
            Y.one('body').append(Y.Node.create('<button class="button1">button1</button><div class="alert"></div><button class="button2">button2</button>'));
            var button1 = Y.one('.button1'),
                button2 = Y.one('.button2');

            var widget = RightNow.Field.extend({
                test: function() {
                    // No alerts on page
                    this.politeFocus(button1);
                    Y.Assert.areSame(document.activeElement.className, 'button1');

                    // Alert is set, but div has no content, so focus
                    Y.one('.alert').set('role', 'alert');
                    this.politeFocus(button2);
                    Y.Assert.areSame(document.activeElement.className, 'button2');

                    // Alert is set, and div has content, so don't focus
                    Y.one('.alert').set('innerText', 'spongebob');
                    this.politeFocus(button1);
                    Y.Assert.areSame(document.activeElement.className, 'button2');
                    Y.one('.alert').set('role', null);
                    Y.one('.alert').set('innerText', '');

                    // aria-live=assertive is set, but div has no content, so focus
                    Y.one('.alert').set('aria-live', 'assertive');
                    this.politeFocus(button1);
                    Y.Assert.areSame(document.activeElement.className, 'button1');

                    // aria-live=assertive is set, and div has content, so don't focus
                    Y.one('.alert').set('innerText', 'spongebob');
                    this.politeFocus(button2);
                    Y.Assert.areSame(document.activeElement.className, 'button1');
                }
            });
            new widget({attrs: {},js: {}}, '', Y).test();
        },
    }));
    tests.add(new Y.Test.Case({
        name: "checkEmail",

        testCheckEmail: function() {
            // DE_VALID_EMAIL_PATTERN doesn't exist in this context.
            var widget = RightNow.Field.extend({
                test: function() {
                    this._errors = [];
                    this._fieldName = 'email';
                    this._value = "";
                    this._checkEmail();
                    Y.Assert.areSame(0, this._errors.length);
                    this._value = null;
                    this._checkEmail();
                    Y.Assert.areSame(0, this._errors.length);
                    this._value = "sdfsf";

                    return this;
                },
                testAlternateEmail: function() {
                    this._errors = [];
                    this._fieldName = 'Incident.CustomFields.c.alternateemail';

                    this._value = null;
                    this._checkEmail();
                    Y.Assert.areSame(0, this._errors.length);

                    this._value = '';
                    this._checkEmail();
                    Y.Assert.areSame(0, this._errors.length);

                    this._value = 'banana@banana.io;cuffy.meigs@gmail.foo;diego.de.la.vega@aol.co.uk.sp';
                    this._checkEmail();
                    Y.Assert.areSame(3, this._errors.length);
                    // @@@ QA 130509-000120 alternateemail validation fix.
                    this._value = 'banana@banana.io,bananas';
                    this._checkEmail();
                    Y.Assert.areSame(5, this._errors.length);

                    this._value = 'asfd';
                    this._checkEmail();
                    Y.Assert.areSame(6, this._errors.length);
                }
            });
            new widget({
                attrs: {hint:"banana"},
                js: {
                    name: 'fieldName',
                    type: 'String',
                    customID: true,
                    url: true
                }
            }, 'test_form', Y)
                .test()
                .testAlternateEmail();
        }
    }));
    tests.add(new Y.Test.Case({
        name: "checkIsCommonEmailType",

        testIsCommonEmailType: function() {
            var widget = RightNow.Field.extend({
                test: function() {
                    Y.Assert.isTrue(this.isCommonEmailType());

                    this._fieldName = 'Contact.Emails.ALT1.Address';
                    Y.Assert.isTrue(this.isCommonEmailType());

                    this._fieldName = 'Contact.Emails.ALT2.Address';
                    Y.Assert.isTrue(this.isCommonEmailType());

                    this._fieldName = 'Contact.Emails.ALT3.Address';
                    Y.Assert.isFalse(this.isCommonEmailType());

                    this._fieldName = 'Contact.Emails.PRIMARY';
                    Y.Assert.isFalse(this.isCommonEmailType());

                    this._fieldName = 'Incident.CustomFields.c.alternateemail';
                    Y.Assert.isTrue(this.isCommonEmailType());

                }
            });
            new widget({
                attrs: {},
                js: {
                    name: 'Contact.Emails.PRIMARY.Address'
                }
            }, 'test_form', Y).test();
        }
    }));
    tests.add(new Y.Test.Case({
        name: "checkIsRadio",

        // @@@ QA 130325-000044
        testIsRadio: function() {
            var widget = RightNow.Field.extend({
                test: function() {
                    this.input = Y.Node.create("<div><input type='radio' value='1' name='foo'><input type='radio' value='0' name='foo'></div>").all('input');
                    Y.Assert.isTrue(this._isRadio());

                    this.input = Y.Node.create("<div><input type='text' name='foo'></div>");
                    Y.Assert.isFalse(this._isRadio());
                }
            });
            new widget({
                attrs: {},
                js: {
                    name: 'Incident.CustomFields.c.yesno1'
                }
            }, 'test_form', Y).test();
        }
    }));
    tests.add(new Y.Test.Case({
        name: "checkData",

        newWidget: function(name, data) {
            var widget = RightNow.Field.extend({
                error: function(val) {
                    this._value = val;
                    this._checkData();
                    return ++widget.errors === this._errors.length;
                },
                noError: function(val) {
                    this._value = val;
                    this._checkData();
                    return !this._errors || widget.errors === this._errors.length;

                }
            }, {
                errors: 0
            });

            return new widget({
                attrs: {hint:"banana"},
                js: Y.mix({
                    name: name,
                    type: 'String',
                    customID: false,
                    url: false
                }, data)
            }, 'test_form', Y);
        },

        "Null and empty string aren't checked for any field": function() {
            var widget = this.newWidget('Anyfield', {constraints: {regex: '^[^ <>"]*$'}});
            Y.assert(widget.noError(''));
            Y.assert(widget.noError(null));
            Y.assert(widget.noError('sdfsf'));
            Y.assert(widget.error('sd fsf'));
        },


        "Contact.Login does not allow GT, LT, dubquotes, spaces. And says as much.": function() {
            var widget = this.newWidget('Contact.Login', {constraints: {regex: '^[^ <>"]*$'}});
            Y.assert(widget.noError(''));
            Y.assert(widget.noError('sdfsf'));
            Y.assert(widget.error('sd fsf'));
            Y.assert(widget.error('sdfsf>'));
            Y.assert(widget.error('sd<fsf'));
            Y.assert(widget.noError("sdfs'f"));
            Y.assert(widget.error("\"sdfsf"));

            for (var i = 0, errorMessage; i < widget._errors.length; i++) {
                errorMessage = widget._errors[i];
                Y.assert(errorMessage.indexOf('<'));
                Y.assert(errorMessage.indexOf('>'));
                Y.assert(errorMessage.indexOf('spaces'));
                Y.assert(errorMessage.indexOf('double quotes'));
            }
        },


        "Contact channel fields do not allow spaces": function() {
            var widget = this.newWidget('Contact.ChannelUsernames.FACEBOOK.Username', {channelID: true});
            Y.assert(widget.noError(''));
            Y.assert(widget.noError('sdfsf'));
            Y.assert(widget.error('sd fsf'));
        },

        "Contact phone numbers allow certain characters": function() {
            var widget = this.newWidget('Contact.Phones.OFFICE.Number');
            Y.assert(widget.error("∂"));
            Y.assert(widget.error("&653345asdf"));
            Y.assert(widget.noError("14#2342-24234(a)b ,+."));

            widget = this.newWidget('Contact.Phones.FAX.Number');
            Y.assert(widget.error("∂"));
            Y.assert(widget.error("&653345asdf"));
            Y.assert(widget.noError("14#2342-24234(a)b ,+."));

            widget = this.newWidget('Contact.Phones.HOME.Number');
            Y.assert(widget.error("∂"));
            Y.assert(widget.error("&653345asdf"));
            Y.assert(widget.noError("14#2342-24234(a)b ,+."));

            widget = this.newWidget('Contact.Phones.ASST.Number');
            Y.assert(widget.error("∂"));
            Y.assert(widget.error("&653345asdf"));
            Y.assert(widget.noError("14#2342-24234(a)b ,+."));

            widget = this.newWidget('Contact.Phones.MOBILE.Number');
            Y.assert(widget.error("∂"));
            Y.assert(widget.error("&653345asdf"));
            Y.assert(widget.noError("14#2342-24234(a)b ,+."));

            for (var i = 0, errorMessage; i < widget._errors.length; i++) {
                errorMessage = widget._errors[i];
                Y.assert(errorMessage.indexOf('phone number'));
            }
        },

        "Contact postal codes allow certain characters": function() {
            var widget = this.newWidget('Contact.Address.PostalCode');

            Y.assert(widget.error("∂"));
            Y.assert(widget.error("&653345asdf"));
            Y.assert(widget.noError("14#2342-24234(a)b ,+."));

            for (var i = 0, errorMessage; i < widget._errors.length; i++) {
                errorMessage = widget._errors[i];
                Y.assert(errorMessage.indexOf('postal code'));
            }
        }
    }));
    tests.add(new Y.Test.Case({
        name: "checkValueTests",
        newWidget: function(name, data) {
            var widget = RightNow.Field.extend({
                error: function(val) {
                    this._value = val;
                    this._checkValue();
                    this._errors = this._errors || [];
                    return ++widget.errors === this._errors.length;
                },
                noError: function(val) {
                    this._value = val;
                    this._checkValue();
                    return !this._errors || widget.errors === this._errors.length;
                },
                getLastError: function() {
                    return this._errors.slice(-1)[0];
                }
            }, {
                errors: 0
            });

            return new widget({
                attrs: {hint:"banana"},
                js: Y.mix({
                    name: name,
                    type: 'String',
                    customID: false,
                    url: false,
                    constraints: {}
                }, data, true)
            }, 'test_form', Y);
        },
        "Constraint checking for integer fields works properly when no constraints are specified": function() {
            var widget = this.newWidget('Incident.CustomFields.c.int1', {type:'Integer'});

            Y.assert(widget.noError(""));
            Y.assert(widget.noError(1));
            Y.assert(widget.noError(123456));
            Y.assert(widget.error("1"));
            Y.assert(widget.error("abc"));
            Y.assert(widget.error(" "));
        },
        "Min value constraint check for integer fields works properly": function(){
            var widget = this.newWidget('Incident.CustomFields.c.int1', {type:'Integer', constraints: {minValue: 5}});

            Y.assert(widget.noError(5));
            Y.assert(widget.noError(6));
            Y.assert(widget.noError(08));
            Y.assert(widget.noError(123456));
            Y.assert(widget.error(4));
            Y.assert(widget.error(04));
            Y.assert(widget.error(0));
            Y.assert(widget.error(-1));
        },
        "Min value constraint check of zero for integer fields works properly": function(){
            var widget = this.newWidget('Incident.CustomFields.c.int1', {type:'Integer', constraints: {minValue: 0}});

            Y.assert(widget.noError(5));
            Y.assert(widget.noError(0));
            Y.assert(widget.error(-5));
        },
        "Max value constraint check for integer fields works properly": function(){
            var widget = this.newWidget('Incident.CustomFields.c.int1', {type:'Integer', constraints: {maxValue: 5}});

            Y.assert(widget.error(6));
            Y.assert(widget.error(7));
            Y.assert(widget.error(08));
            Y.assert(widget.error(123456));
            Y.assert(widget.noError(4));
            Y.assert(widget.noError(04));
            Y.assert(widget.noError(0));
            Y.assert(widget.noError(-1));
        },
        "Max value constraint check of zero for integer fields works properly": function(){
            var widget = this.newWidget('Incident.CustomFields.c.int1', {type:'Integer', constraints: {minValue: -10, maxValue: 0}});

            Y.assert(widget.noError(-5));
            Y.assert(widget.noError(0));
            Y.assert(widget.error(5));
        },
        "Contact password length checking works properly": function(){
            var widget = this.newWidget('Contact.NewPassword', {passwordLength: 5});

            Y.assert(widget.noError("abcde"));
            Y.assert(widget.noError("abcdef"));
            Y.assert(widget.noError("abcdefghijklmnopqrstuvwxyz"));
            Y.assert(widget.error("abcd"));
            Y.assert(widget.error(""));
            Y.assert(widget.error("    "));
        },
        "Max length constraint checking for password works properly": function(){
            var widget = this.newWidget('Contact.NewPassword', {constraints: {maxLength: 10}});

            Y.assert(widget.noError(""));
            Y.assert(widget.noError("1234567890"));
            Y.assert(widget.noError("          "));
            Y.assert(widget.noError("力力力力力力力力力力"));
            Y.assert(widget.error("12345678901"));
            Y.assert(widget.error("abcdefghijklmnop"));
            Y.assert(widget.error("力力力力力力力力力力力"));
        },
        "Min length constraint checking for password works properly": function(){
            var widget = this.newWidget('Contact.NewPassword', {constraints: {minLength: 11}});

            Y.assert(widget.error(""));
            Y.assert(widget.error("1234567890"));
            Y.assert(widget.error("          "));
            Y.assert(widget.error("力力力力力力力力力力"));
            Y.assert(widget.noError("12345678901"));
            Y.assert(widget.noError("abcdefghijklmnop"));
            Y.assert(widget.noError("力力力力力力力力力力力"));
        },
        "When min length constraint isn't met, error message is of the correct grammatical number": function() {
            var widget = this.newWidget('Contact.banana', {constraints: {minLength: 3}});

            Y.assert(widget.noError(null));
            Y.assert(widget.noError('foo'));

            Y.assert(widget.error('f'));
            // TK - when message base is in place, mock & verify...

            Y.assert(widget.error('fo'));
            // Y.assert(widget.getLastError().indexOf('1 character') > -1);
            // Y.assert(widget.getLastError().indexOf('characters') === -1);

        },
        "When max length constraint isn't met, error message is of the correct grammatical number": function() {
            var widget = this.newWidget('Contact.banana', {constraints: {maxLength: 2}});

            Y.assert(widget.noError(null));
            Y.assert(widget.noError('fo'));

            Y.assert(widget.error('foo'));
            // TK - when message base is in place, mock & verify...

            // Y.assert(widget.getLastError().indexOf('1 character') > -1);
            // Y.assert(widget.getLastError().indexOf('2 characters') > -1);

            Y.assert(widget.error('foooo'));
            // Y.assert(widget.getLastError().indexOf('2 characters') > -1);
            // Y.assert(widget.getLastError().indexOf('3 characters') > -1);
        }
    }));

    tests.add(new Y.Test.Case({
        name: "test hide/show",

        "field should be visible and hidden when show/hide is toggled": function() {
            var field = new RightNow.Field({
                attrs: {hide_on_load: false},
                js: {}
            }, 'test_form', Y);
            Y.Assert.isTrue(field.isVisible());

            var field = new RightNow.Field({
                attrs: {hide_on_load: true},
                js: {}
            }, 'test_form', Y);
            Y.Assert.isFalse(field.isVisible());

            field.show();
            Y.Assert.isTrue(field.isVisible());

            field.hide();
            Y.Assert.isFalse(field.isVisible());
        }
    }));

    tests.add(new Y.Test.Case({
        name: "isEmailField",

        testIsEmailField: function() {
            var widget = RightNow.Field.extend({
                test: function() {
                    this._fieldName = 'Email';
                    Y.Assert.isFalse(this.is("email"));
                    this._fieldName = 'emails';
                    Y.Assert.isFalse(this.is("email"));
                    this._fieldName = 'Email_alt1';
                    Y.Assert.isFalse(this.is("email"));
                    this._fieldName = 'Email_alt2';
                    Y.Assert.isFalse(this.is("email"));
                    this._fieldName = 'Email_alt3';
                    Y.Assert.isFalse(this.is("email"));
                    this._fieldName = 'alternateemail';
                    Y.Assert.isFalse(this.is("email"));

                    this._fieldName = 'Contact.Emails.PRIMARY.Address';
                    Y.Assert.isTrue(this.is("email"));

                    this._fieldName = 'Contact.Emails.ALT1.Address';
                    Y.Assert.isTrue(this.is("email"));

                    this._fieldName = 'Contact.Emails.ALT2.Address';
                    Y.Assert.isTrue(this.is("email"));

                    this.data.js.email = true;
                    Y.Assert.isTrue(this.is("email"));
                }
            });
            new widget({
                attrs: {},
                js: {
                    name: 'fieldName',
                    type: 'String'
                }
            }, 'test_form', Y).test();
        }
    }));
    tests.add(new Y.Test.Case({
        name: "parentFormFunctions",

        testParentFormFunctions: function() {
            form = Y.Node.create('<form id="testForm"><div id="rn_testInstance"></div></form>');
            Y.one(document.body).append(form);

            var widget = RightNow.Field.extend({
                test: function() {
                    var formConductor = this.parentForm();
                    Y.Assert.isObject(formConductor);
                    Y.Assert.isFunction(formConductor.on);
                    var forcedFormConductor = this.parentForm('testForm');
                    Y.Assert.isObject(forcedFormConductor);
                    Y.Assert.isFunction(forcedFormConductor.on);

                    Y.Assert.areSame('testForm', this.getParentFormID());

                    Y.one('#testForm').remove();
                }
            });
            new widget({
                attrs: {},
                js: {
                    name: 'fieldName',
                    type: 'String'
                }
            }, 'testInstance', Y).test();
        }
    }));

    tests.add(new Y.Test.Case({
        name: "createEventObject",
        testCreateEventObject: function() {
            this.wait(function() {
                var widget = RightNow.Field.extend({
                    test: function() {
                        this.input = this.Y.Node.create('<input type="text" value="banana">');
                        this._parentForm = "someFormID";
                        var eo = this.createEventObject();
                        Y.Assert.areSame(this.data.js.name, eo.data.name);
                        Y.Assert.areSame(this.data.attrs.required, eo.data.required);
                    }
                });
                new widget({
                    attrs: {
                        required: false
                    },
                    js: {
                        name: 'Contact.Login'
                    }
                }, 'test_form', Y).test();

                widget = RightNow.Field.extend({
                    test: function() {
                        this.input = this.Y.Node.create('<input type="text" value="banana">');
                        this._parentForm = "someFormID";
                        var eo = this.createEventObject();
                        Y.Assert.areSame(this.data.js.name, eo.data.name);
                        Y.Assert.areSame(this.data.attrs.required, eo.data.required);
                    }
                });
                new widget({
                    attrs: {
                        required: true
                    },
                    js: {
                        name: 'fieldName',
                        table: 'set',
                        prev: 'no',
                        profile: false,
                        type: 'String'
                    }
                }, 'test_form', Y).test();
            }, 1000);
        }
    }));

    tests.add(new Y.Test.Case({
        name: "Test setConstraint events",

        "Events should fire with all values and those values should be cast to the correct type": function() {
            var changeFireCount = 0, requiredFireCount = 0, minAttachmentsFireCount = 0, requiredLevelFirecount = 0;

            var widget = RightNow.Field.extend({
                overrides: {
                    constructor: function() {
                        this.parent();
                        this.on('constraintChange', this.constraintChange, this);
                        this.on('constraintChange:required', this.constraintChangeRequired, this);
                        this.on('constraintChange:min_required_attachments', this.constraintChangeMinAttachments, this);
                        this.on('constraintChange:required_lvl', this.constraintChangeRequiredLevel, this);
                    }
                },

                constraintChange: function(evt, constraint) {
                    Y.Assert.areSame('constraintChange', evt);
                    Y.Assert.isTrue(Y.Lang.isObject(constraint[0]));
                    changeFireCount++;
                },
                constraintChangeRequired: function(evt, constraint) {
                    Y.Assert.areSame('constraintChange:required', evt);
                    Y.Assert.isTrue(constraint[0].constraint === false);
                    requiredFireCount++;
                },
                constraintChangeMinAttachments: function(evt, constraint) {
                    Y.Assert.areSame('constraintChange:min_required_attachments', evt);
                    Y.Assert.isTrue(constraint[0].constraint === 0);
                    minAttachmentsFireCount++;
                },
                constraintChangeRequiredLevel: function(evt, constraint) {
                    Y.Assert.areSame('constraintChange:required_lvl', evt);
                    Y.Assert.isTrue(constraint[0].constraint === 6);
                    requiredLevelFirecount++;
                }
            });

            var eventWidget = new widget({attrs: {hide_on_load: false}, js: {name: 'fieldName'}}, 'test_form', Y);

            eventWidget.setConstraints({required: "false"});
            eventWidget.setConstraints({required_lvl: "6"});
            eventWidget.setConstraints({min_required_attachments: "0"});

            Y.Assert.areSame(3, changeFireCount);
            Y.Assert.areSame(1, requiredFireCount);
            Y.Assert.areSame(1, minAttachmentsFireCount);
            Y.Assert.areSame(1, requiredLevelFirecount);
        }
    }));

    return tests;
}).run();
