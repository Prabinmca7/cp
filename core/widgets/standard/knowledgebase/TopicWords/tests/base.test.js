UnitTest.addSuite({
    type: UnitTest.Type.Widget,
    instanceID: 'TopicWords_0',
}, function(Y, widget, baseSelector){
    var topicWordsTests = new Y.Test.Suite({
        name: "standard/knowledgebase/TopicWords",
        
        setUp: function(){
            var testExtender = {
                initValues : function() {
                    this.instanceID = 'TopicWords_0';
                    this.instance = RightNow.Widgets.getWidgetInstance(this.instanceID);
                    this.widgetData = this.instance.data;
                    this.labelTitle = this.widgetData.attrs.label_title;
                    this.target = this.widgetData.attrs.target;
                    this.displayIcon = this.widgetData.attrs.display_icon;
                }
            };
            
            for(var item in this.items)
            {
                Y.mix(this.items[item], testExtender);
            }            
        }
    });
        
    topicWordsTests.add(new Y.Test.Case(
    {
        name: "Event Handling and Operation",
        
        /**
         * Tests the widget's response to the evt_reportResponse event. Tests whether the provided
         * topic is displayed with the given icon (if display_icon attribute is true) and whether the
         * provided topic is correctly displayed.
         * 
         * It then tests the response in the event that no topic is provided.
         */
        testReportResponse: function() {
            this.initValues();
        
            var eo = new RightNow.Event.EventObject(this.instance, {data: this.mockData, 
                filters: {allFilters: {format:{parmList:null}, filters:{}}, report_id: this.widgetData.attrs.report_id}});
            
            this.instance.searchSource().fire("response", eo);
            
            Y.Assert.isFalse(Y.one("#rn_" + this.instanceID).hasClass("rn_Hidden"));
            

            Y.Assert.isNotNull(document.getElementById("rn_" + this.instanceID + "_List"));
            var topicList = document.getElementById("rn_" + this.instanceID + "_List");
            Y.Assert.areNotSame("", topicList.innerHTML);
            
            var instanceElement = Y.one('#rn_' + this.instanceID);
            Y.Assert.areSame(this.labelTitle, instanceElement.one('.rn_Title').getHTML());
            
            var dtElement = instanceElement.one('dt');
            Y.Assert.isNotNull(dtElement);

            if (this.displayIcon) {
                Y.Assert.areSame(this.widgetData.attrs.icon_path || 'images/icons/www.gif', instanceElement.one('img').getAttribute('src'));
            } 
            var a = instanceElement.one('a');
            Y.Assert.areSame(this.target, a.getAttribute('target'));
            Y.Assert.areSame("Test", a.getHTML());
            
            var ddElement = instanceElement.one('dd');
            Y.Assert.isNotNull(ddElement);
            Y.Assert.areSame("Test Topic Word", Y.Lang.trim(ddElement.getHTML()));
            
            eo = new RightNow.Event.EventObject(this.instance, {
                filters: {allFilters: {format:{parmList:null}, filters:{}}, report_id: this.widgetData.attrs.report_id}
            });
            this.instance.searchSource().fire("response", eo);
            
            Y.Assert.isTrue(Y.one("#rn_" + this.instanceID).hasClass("rn_Hidden"));
            Y.Assert.isNotNull(document.getElementById("rn_" + this.instanceID + "_List"));
            Y.Assert.areSame("", topicList.innerHTML);
        },

        "Adding params to generated links": function() {
            var origAttr = this.instance.data.attrs.add_params_to_url;

            this.instance.data.attrs.add_params_to_url = 'p,kw';
            this.instance.searchSource().fire('response', new RightNow.Event.EventObject(null, {data: this.mockData, filters: {
                p: {
                    filters: {
                        data: ['200']
                    }
                },
                keyword: { filters: { data: 'yeah'} }
            }}));

            var links = Y.all('#rn_' + this.instanceID + ' a');
            Y.Assert.areSame(1, links.size());
            links.each(function(a) {
                Y.Assert.isTrue(/\/p\/200\/kw\/yeah/.test(a.get('href')));
            });

            this.instance.data.attrs.add_params_to_url = origAttr;
        },

        mockData: {
                topic_words: [{"url" : window.location.host + "/app/foo/bar",
                                 "title" : "Test",
                                 "text" : "Test Topic Word",
                                 "icon" : "<img src='images/icons/www.gif' />"}]
            }
    }));
    return topicWordsTests;
});
UnitTest.run();
