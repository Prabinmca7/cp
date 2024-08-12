UnitTest.addSuite({
    type: UnitTest.Type.Widget,
    instanceID: 'CombinedSearchResults_0',
}, function(Y, widget, baseSelector){
    var tests = new Y.Test.Suite({
        name: "standard/reports/CombinedSearchResults",
        setUp: function(){
            var testExtender = {
                initValues : function() {
                    this.id = 'CombinedSearchResults_0';
                    this.instance = RightNow.Widgets.getWidgetInstance(this.id);
                    this.widgetData = this.instance.data;
                    this.id = 'rn_' + this.id;
                    this.testResults = function(noMoreLink) {
                        var contents = Y.one('#' + this.id + "_Content"),
                            list = contents.one('*');
                        Y.Assert.isTrue(contents.hasClass('rn_Content'));
                        Y.Assert.areSame(contents.get('children').size(), 0);
                        if (list !== null) {
                            Y.Assert.areSame(list.get('children').size(), 1);
                            Y.Assert.areSame(list.get('tagName').toUpperCase(), "UL");
                            var listItem = list.one('*');
                            Y.Assert.areSame(listItem.get('children').size(), 1);
                            Y.Assert.areSame(listItem.get('id'), this.id + "_Social");
                            Y.Assert.areSame(listItem.get('className'), "rn_Social");
                            list = listItem.one('*');
                            Y.Assert.areSame(list.get('tagName').toUpperCase(), "UL");
                            Y.Assert.areSame(list.get('className'), "rn_SocialList");
                            Y.Assert.areSame(list.get('children').size(), 1);
                            listItem = list.one('*');
                            // Heading link + list of results + More link (if there are more results)
                            Y.Assert.areSame((noMoreLink) ? 2 : 3, listItem.get('children').size());
                            var heading = listItem.one('*');
                            Y.Assert.areSame(heading.get('tagName').toUpperCase(), "A");
                            Y.Assert.isTrue(heading.hasClass('rn_Link') && heading.hasClass('rn_Heading'));
                            Y.Assert.areSame(heading.get('innerHTML'), this.widgetData.attrs.label_social_results_heading);
                            list = listItem.get('children').item(1);
                            Y.Assert.isTrue(list.get('children').size() < 20 /* hard coded limit*/);
                            var checkToggle = function(parentNode, className) {
                                for(var i = this.widgetData.attrs.maximum_social_results; i < parentNode.get('children').size(); i++) {
                                    Y.Assert.isTrue(parentNode.get('children').item(i).hasClass(className));
                                }
                            };
                            checkToggle.call(this, list, 'rn_Hidden');
                            var a = listItem.get('children').item(2);
                            Y.Assert.areSame(a.get('tagName').toUpperCase(), "A");
                            Y.Assert.areSame(a.get('className'), "rn_More");
                            a.simulate('click');
                            checkToggle.call(this, list, 'rn_Shown');
                            Y.Assert.isTrue(a.hasClass('rn_Hidden'));
                            heading.simulate('click');
                            checkToggle.call(this, list, 'rn_Hidden');
                            Y.Assert.areSame(a.get('className'), "rn_More");
                        }
                    };
                },
                testToggle: function() {
                    // Test show / hide toggle
                    var more = Y.one('#' + this.id + ' a.rn_More')
                    if (more !== null) {
                        more.simulate('click');
                        Y.Assert.isTrue(Y.one('#' + this.id + ' a.rn_More').hasClass('rn_Hidden'));
                        Y.one('.rn_SocialList').all('li').each(function(li) {
                            Y.Assert.isFalse(li.hasClass('rn_Hidden'), "An li is hidden when it shouldn't be");
                        });

                        Y.one('#' + this.id + ' a.rn_Heading').simulate('click');
                        Y.Assert.isFalse(Y.one('#' + this.id + ' a.rn_More').hasClass('rn_Hidden'));
                        var count = 0,
                            cutoff = this.instance.data.attrs.maximum_social_results;
                        Y.all('.rn_SocialList ul.rn_Links > li').each(function(li) {
                            var assert = (++count === cutoff) ? 'isFalse' : 'isTrue';
                            Y.Assert[assert](li.hasClass('rn_Hidden'), "li " + count + " wasn't toggled correctly");
                        });
                    }
                }
            };
            for(var item in this.items) {
                Y.mix(this.items[item], testExtender);
            }
        }
    });
    tests.add(new Y.Test.Case({
        name: "Test initial results",
        testResponse: function() {
            this.initValues();
            this.testResults();
        }
    }));
    tests.add(new Y.Test.Case({
        name: "Test new results coming back",
        testResponse: function() {
            this.initValues();

            var singleResult = false;
            function test() {
                this.testResults();
                Y.Assert.areSame(Y.one('#' + this.id + '_Loading').get('className'), "");
                this.testToggle(singleResult);
            }
            this.instance.searchSource(this.widgetData.attrs.source_id).on("response", test, this)
                .fire("response", new RightNow.Event.EventObject({}, {
                data: {
                    social: {
                        data: {
                            results: [
                                {createdByAvatar: 'sdf', webUrl: 'sdfsdf', name: 'title', preview: 'snippet', createdByName: 'cuffy', createdByHash: 'sdfsdf', lastActivity: 'sdf'},
                                {createdByAvatar: 'sdf', webUrl: 'sdfsdf', name: 'title', preview: 'snippet', createdByName: 'cuffy', createdByHash: 'sdfsdf', lastActivity: 'sdf'}
                            ]
                        }
                    }
            }}));
            singleResult = true;
            this.instance.searchSource(this.widgetData.attrs.source_id)
                .fire("response", new RightNow.Event.EventObject({}, {
                data: {
                    social: {
                        data: {
                            results: [
                                {createdByAvatar: 'sdf', webUrl: 'sdfsdf', name: 'title', preview: 'snippet', createdByName: 'cuffy', createdByHash: 'sdfsdf', lastActivity: 'sdf'}
                            ]
                        }
                    }
            }}));
        }
    }));
    return tests;
});
UnitTest.run();
