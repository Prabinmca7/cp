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
        }
    });

    suite.add(new Y.Test.Case({
        name: "Call URL Functionality",

        setUp: function() {
            var self = this;

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

        },

        tearDown: function() {
        },

        "No Opening Window": function() {
            var mockWindow = {
            };
            Y.Assert.areSame(true, widget.env.samePage(mockWindow));
        },

        "Previous page was same window": function() {
            var openingWindow = {
                'property': 'value'
            };
            var mockWindow = {
                'opener': openingWindow,
                'self': openingWindow
            };
            Y.Assert.areSame(true, widget.env.samePage(mockWindow));
        },

        "Should open link in new window": function() {
            var mockWindow = {
                'opener': {
                    'location': {
                        'href': 'http://oracle.com'
                    }
                }
            };
            Y.Assert.areSame(false, widget.env.samePage(mockWindow));
        },

        "Opening window is chat window": function() {
            var mockWindow = {
                'opener': {
                    'RightNow': {
                        'Chat': true
                    },
                    'location': {
                        'href': 'http://oracle.com'
                    }
                }
            };
            Y.Assert.areSame(true, widget.env.samePage(mockWindow));
        },

        "Opening window is Smart Assist window": function() {
            var mockWindow = {
                'opener': {
                    'RightNow': {
                        'Widgets': {
                            'SmartAssistantDialog': true
                        }
                    },
                    'location': {
                        'href': 'http://oracle.com'
                    }
                }
            };
            Y.Assert.areSame(true, widget.env.samePage(mockWindow));
        },

        "Opening window is agent page": function() {
            var mockWindow = {
                'opener': {
                    'location': {
                        'href': 'http://oracle.com/admin/live/agent.php'
                    }
                }
            };
            Y.Assert.areSame(true, widget.env.samePage(mockWindow));
        },

        //@@@ QA 151120-000023 - make sure we get a real value when href is unavailable/not a string
        "Opening window href not available should return true (not undefined)": function() {
            var mockWindow = {
                'opener': {
                    'location': {
                        'href': false
                    }
                }
            };
            Y.Assert.areSame(true, widget.env.samePage(mockWindow));
        }

    }));

    return suite;
}).run();

