UnitTest.addSuite({
    type: UnitTest.Type.Widget,
    jsFiles: ['/cgi-bin/{cfg}/php/cp/core/widgets/standard/knowledgebase/GuidedAssistant/logic.js'],
    yuiModules: ['node-event-delegate', 'history']
}, function(Y, widget, baseSelector){
    var suite = new Y.Test.Suite({
        name: "standard/knowledgebase/GuidedAssistant",
        setUp: function(){
            baseSelector = 'GuidedAssistant_0';

            Y.one(document.body).insert('<div id="rn_' + baseSelector + '"></div>', 0);

            widget = new RightNow.Widgets.GuidedAssistant({js: {
                guidedAssistant: {
                    questions: [
                        { questionID: 1 }
                    ]
                },
                types: {
                    URL_GET: 1,
                    URL_POST: 2
                }
            }, attrs: {}}, baseSelector, Y);
        }
    });

    suite.add(new Y.Test.Case({
        name: "Call URL Functionality",

        setUp: function() {
            var self = this;

            function appendChildHandler(expected) {
                self.mockWindow.calledWith = [].slice.call(arguments);
                if (expected) {
                    return {
                        submit: function() { self.mockWindow.submitCalled = true; }
                    };
                }
            }

            this.mockWindow = {
                'self': {
                    'opener': {
                        'document': {
                            'body': {
                                'appendChild': function() {
                                    return appendChildHandler.apply(null, arguments);
                                }
                            }
                        }
                    }
                },
                'close': function() {
                    this.closeCalled = true;
                },
                'open': function() {
                    this.openCalledWith = arguments;
                }
            };
        },

        tearDown: function() {
            this.mockWindow = null;
        },

        "GET: Normal scenario changes window location": function() {
            widget.env.samePage = function() { return true; };
            widget.env.console = false;
            widget._navigate('bananas', {
                'no': 'YES&NO',
                'empty': '',
                'pair': { value: 'something' }
            }, this.mockWindow);

            Y.assert(!this.mockWindow.closeCalled);
            Y.assert(!this.mockWindow.openCalledWith);
            Y.Assert.areSame("bananas?no=YES%26NO&empty=&pair=something", this.mockWindow.location);
        },

        "GET: If there's an opener, its location is changed and the current window is closed": function() {
            widget.env.samePage = function() { return false; };
            widget.env.console = false;
            widget._navigate('bananas', {
                'no': 'YES&NO',
                'empty': '',
                'pair': { value: 'something' }
            }, this.mockWindow);

            Y.assert(this.mockWindow.closeCalled);
            Y.assert(!this.mockWindow.openCalledWith);
            Y.Assert.areSame("bananas?no=YES%26NO&empty=&pair=something", this.mockWindow.self.opener.location);
        },

        "GET: Console mode (but not enduser preview mode) and new_window attribute causes a new window to open": function() {
            widget.env.samePage = function() { return true; };
            widget.env.console = true;
            widget.env.previewEnduser = false;
            widget.data.attrs.call_url_new_window = true;
            widget._navigate('bananas', {
                'no': 'YES&NO',
                'empty': '',
                'pair': { value: 'something' }
            }, this.mockWindow);

            Y.assert(!this.mockWindow.closeCalled);
            Y.Assert.areSame(1, this.mockWindow.openCalledWith.length);
            Y.Assert.areSame("bananas?no=YES%26NO&empty=&pair=something", this.mockWindow.openCalledWith[0]);
            Y.Assert.isUndefined(this.mockWindow.self.opener.location);
            Y.Assert.isUndefined(this.mockWindow.location);
        },

        "POST: When this window was opened by another adds the form to the opener and closes this window": function() {
            widget.env.samePage = function() { return false; };
            widget.Y.UA.ie = false; // Ain't nobody got time for IE unit test meshuga.
            widget.env.console = false;
            widget._post("bananas", {
                hey: 'no',
                foo: { value: 'bar' },
                empty: ''
            }, this.mockWindow);

            Y.assert(this.mockWindow.closeCalled);
            Y.assert(this.mockWindow.submitCalled);
            Y.Assert.areSame(1, this.mockWindow.calledWith.length);
            var form = Y.one(this.mockWindow.calledWith[0]);
            Y.Assert.areSame('form', form.get('tagName').toLowerCase());
            Y.Assert.areSame('bananas', form.getAttribute('action'));
            Y.assert(form.hasClass('rn_Hidden'));
            Y.assert(form.one('[name="hey"]'));
            Y.Assert.areSame('no', form.one('[name="hey"]').get('value'));
            Y.assert(form.one('[name="foo"]'));
            Y.Assert.areSame('bar', form.one('[name="foo"]').get('value'));
            Y.assert(form.one('[name="empty"]'));
            Y.Assert.areSame('', form.one('[name="empty"]').get('value'));
        }
    }));

    return suite;
}).run();
