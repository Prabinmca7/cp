UnitTest.addSuite({
    type:       UnitTest.Type.Widget,
    instanceID: 'OkcsRecentlyViewedContent_0'
}, function (Y, widget, baseSelector) {
    var suite = new Y.Test.Suite({
        name: "standard/okcs/OkcsRecentlyViewedContent",

        setUp: function(){
            var testExtender = {
                initValues : function() {
                    this.instanceID = 'OkcsRecentlyViewedContent_0';
                    this.instance = RightNow.Widgets.getWidgetInstance(this.instanceID);
                    this.instance.data.js.previousContent = null;                    
                }
            };

            for (var item in this.items) {
                Y.mix(this.items[item], testExtender);
            }
        }
    });

    suite.add(new Y.Test.Case({
        name: "Event Handling and Operation",

        "Verify widget header label when no recent answers": function() {
            this.initValues();
            this.recentTitle = Y.one('#rn_' + this.instanceID + ' h2');
            Y.Assert.isNull(this.recentTitle);
        },
        
        "Verify widget renders all recent answers": function() {
            this.initValues();
            this.instance.data.js.previousContent = '[1000000, 1000002, 1000004, 1000003]';
            this.instance.data.attrs.content_count = 5;
            localStorage.setItem("okcsRecentAnswers",'[{"answerId":"1000004","title":" Bring Your Network Topology to the Cloud."},{"answerId":"1000002","title":"Infrastructure as a Service"},{"answerId":"1000000","title":"Software as a Service"},{"answerId":"1000003","title":" Cloud Marketplace"}]');
            this.instance.constructor();
            this.recentTitle = Y.one('#rn_' + this.instanceID + ' h2');
            Y.Assert.isNotNull(this.recentTitle);
            Y.Assert.areSame(Y.all('.rn_AnswerContentItem')._nodes.length, 4);
        },

        "Verify widget renders all recent answers and questions": function() {
            this.initValues();
            this.instance.data.js.previousContent = '[1000000, 1000002]';
            this.instance.data.js.previousQuestions = '[1020, 1021]';
            this.instance.data.attrs.content_count = 5;
            localStorage.setItem("okcsRecentAnswers",'[{"questionId":"1021","title":" Bring Your Network Topology to the Cloud."},{"answerId":"1000002","title":"Infrastructure as a Service"},{"answerId":"1000000","title":"Software as a Service"},{"questionId":"1020","title":" Cloud Marketplace"}]');
            this.instance.constructor();
            this.recentTitle = Y.one('#rn_' + this.instanceID + ' h2');
            Y.Assert.isNotNull(this.recentTitle);
            Y.Assert.areSame(Y.all('.rn_AnswerContentItem')._nodes.length, 2);
            Y.Assert.areSame(Y.all('.rn_SocialQuestionItem')._nodes.length, 2);
        },
        
        "Verify widget renders all recent answers except current answer": function() {
            this.initValues();
            this.instance.data.js.currentAnswerId = 1000002;
            this.instance.data.js.previousContent = '[1000000, 1000002, 1000004, 1000003]';
            localStorage.setItem("okcsRecentAnswers",'[{"answerId":"1000004","title":" Bring Your Network Topology to the Cloud."},{"answerId":"1000002","title":"Infrastructure as a Service"},{"answerId":"1000000","title":"Software as a Service"},{"answerId":"1000003","title":" Cloud Marketplace"}]');
            this.instance.constructor();
            this.recentTitle = Y.one('#rn_' + this.instanceID + ' h2');
            Y.Assert.isNotNull(this.recentTitle);
            Y.Assert.areSame(Y.all('.rn_AnswerContentItem')._nodes.length, 3);
        },
        
        "Verify widget renders default 5 recent answers": function() {
            this.initValues();
            this.instance.data.js.currentAnswerId = null;
            this.instance.data.attrs.content_count = 5;
            this.instance.data.js.previousContent = '[1000000, 1000002, 1000004, 1000003, 1000005, 1000006, 1000007, 1000008]';
            localStorage.setItem("okcsRecentAnswers",'[{"answerId":"1000004","title":" Bring Your Network Topology to the Cloud."},{"answerId":"1000002","title":"Infrastructure as a Service"},{"answerId":"1000000","title":"Software as a Service"},{"answerId":"1000003","title":" Cloud Marketplace"},{"answerId":"1000005","title":"Microsoft windows 10"},{"answerId":"1000006","title":"Apple iphone 7s"},{"answerId":"1000007","title":"Apple iphone 7"},{"answerId":"1000008","title":"Android phones"}]');
            this.instance.constructor();
            this.recentTitle = Y.one('#rn_' + this.instanceID + ' h2');
            Y.Assert.isNotNull(this.recentTitle);
            Y.Assert.areSame(Y.all('.rn_AnswerContentItem')._nodes.length, 5);
        },
        
        "Verify local storage is updated for recent answers": function() {
            this.initValues();
            this.instance.data.js.currentAnswerId = null;
            this.instance.data.attrs.content_count = 5;
            this.instance.data.js.previousContent = '[1000000, 1000002, 1000004]';
            localStorage.setItem("okcsRecentAnswers",'[{"answerId":"1000004","title":" Bring Your Network Topology to the Cloud."},{"answerId":"1000002","title":"Infrastructure as a Service"},{"answerId":"1000000","title":"Software as a Service"},{"answerId":"1000003","title":" Cloud Marketplace"},{"answerId":"1000005","title":"Microsoft windows 10"},{"answerId":"1000006","title":"Apple iphone 7s"},{"answerId":"1000007","title":"Apple iphone 7"},{"answerId":"1000008","title":"Android phones"}]');
            this.instance.constructor();
            this.recentTitle = Y.one('#rn_' + this.instanceID + ' h2');
            Y.Assert.isNotNull(this.recentTitle);
            Y.Assert.areSame(JSON.parse(localStorage.getItem("okcsRecentAnswers")).length, 3);
        },

        "Verify widget renders all recent answers only": function() {
            this.initValues();
            this.instance.data.js.previousContent = '[1000000, 1000002]';
            this.instance.data.js.previousQuestions = '[1020, 1021]';
            this.instance.data.attrs.content_count = 5;
            this.instance.data.attrs.content_type = ["answers"];
            localStorage.setItem("okcsRecentAnswers",'[{"questionId":"1021","title":" Bring Your Network Topology to the Cloud."},{"answerId":"1000002","title":"Infrastructure as a Service"},{"answerId":"1000000","title":"Software as a Service"},{"questionId":"1020","title":" Cloud Marketplace"}]');
            this.instance.constructor();
            this.recentTitle = Y.one('#rn_' + this.instanceID + ' h2');
            Y.Assert.isNotNull(this.recentTitle);
            Y.Assert.areSame(Y.all('.rn_AnswerContentItem')._nodes.length, 2);
            Y.Assert.areSame(Y.all('.rn_SocialQuestionItem')._nodes.length, 0);
        },

        "Verify widget renders all recent questions only": function() {
            this.initValues();
            this.instance.data.js.previousContent = '[1000000, 1000002]';
            this.instance.data.js.previousQuestions = '[1020, 1021]';
            this.instance.data.attrs.content_count = 5;
            this.instance.data.attrs.content_type = ["questions"];
            this.instance.data.attrs.content_type = "questions";
            localStorage.setItem("okcsRecentAnswers",'[{"questionId":"1021","title":" Bring Your Network Topology to the Cloud."},{"answerId":"1000002","title":"Infrastructure as a Service"},{"answerId":"1000000","title":"Software as a Service"},{"questionId":"1020","title":" Cloud Marketplace"}]');
            this.instance.constructor();
            this.recentTitle = Y.one('#rn_' + this.instanceID + ' h2');
            Y.Assert.isNotNull(this.recentTitle);
            Y.Assert.areSame(Y.all('.rn_SocialQuestionItem')._nodes.length, 2);
            Y.Assert.areSame(Y.all('.rn_AnswerContentItem')._nodes.length, 0);
        }
    }));

    return suite;
}).run();
