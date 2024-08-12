UnitTest.addSuite({
    type: UnitTest.Type.Widget,
    instanceID: 'CommunitySearchResults_0'
}, function(Y, widget, baseSelector){
    var communitySearchTests = new Y.Test.Suite({
        name: "standard/search/CommunitySearchResults",
        setUp: function(){
            var testExtender = {
                initValues: function() {
                    this.instanceID = 'CommunitySearchResults_0';
                    this.instance = RightNow.Widgets.getWidgetInstance(this.instanceID);
                    this.widgetData = this.instance.data;
                    this.selector = "#rn_" + this.instanceID;
                },
                fireFakeResponse: function(){
                    var eo = new RightNow.Event.EventObject(null, {
                            data: {
                                "searchResults": this.getResults(),
                                "totalCount": 20114
                              },
                            filters:   {
                                "keyword": this.widgetData.js.searchTerm
                            }
                        });
                    this.instance.searchSource().fire("response", eo);
                },
                getResults: function() {
                    return [
                        {
                            webUrl: "foo.bar/1",
                            name: "post name 1",
                            preview: "3 wolf moon trust fund keffiyeh four loko next level raw denim culpa cliche. Excepteur irure enim adipisicing laboris ad. Art party you probably haven't heard of them trust fund, craft beer non cliche irony put a bird on it lo-fi thundercats. Next level Austin fap dreamcatcher aute, before they sold out pariatur velit synth four loko scenester nulla odio fixie tattooed. High life duis et, freegan Austin etsy velit lomo mcsweeney's sapiente sustainable reprehenderit. Duis you probably haven't heard of them salvia adipisicing butcher portland. Leggings mlkshk hoodie organic food truck.",
                            createdByName: "cuffy m.",
                            createdByHash: "sdfsdfsdf",
                            createdByAvatar: "http://placekitten.com/32/32",
                            lastActivity: "12.23.2333",
                            ratingTotal: 100,
                            commentCount: 4
                        },
                        {
                            webUrl: "foo.bar/2",
                            name: "post name 2",
                            preview: "3 wolf moon trust fund keffiyeh four loko next level raw denim culpa cliche. Excepteur irure enim adipisicing laboris ad. Art party you probably haven't heard of them trust fund, craft beer non cliche irony put a bird on it lo-fi thundercats. Next level Austin fap dreamcatcher aute, before they sold out pariatur velit synth four loko scenester nulla odio fixie tattooed. High life duis et, freegan Austin etsy velit lomo mcsweeney's sapiente sustainable reprehenderit. Duis you probably haven't heard of them salvia adipisicing butcher portland. Leggings mlkshk hoodie organic food truck.",
                            createdByName: "woody a.",
                            createdByHash: "sdfsdfsdf",
                            createdByAvatar: "http://placekitten.com/32/32",
                            lastActivity: "12.23.2333",
                            ratingTotal: 200,
                            commentCount: 0
                        },
                        {
                            webUrl: "foo.bar/3",
                            name: "post name 3",
                            preview: "3 wolf moon trust fund keffiyeh four loko next level raw denim culpa cliche. Excepteur irure enim adipisicing laboris ad. Art party you probably haven't heard of them trust fund, craft beer non cliche irony put a bird on it lo-fi thundercats. Next level Austin fap dreamcatcher aute, before they sold out pariatur velit synth four loko scenester nulla odio fixie tattooed. High life duis et, freegan Austin etsy velit lomo mcsweeney's sapiente sustainable reprehenderit. Duis you probably haven't heard of them salvia adipisicing butcher portland. Leggings mlkshk hoodie organic food truck.",
                            createdByName: "buzz l.",
                            createdByHash: "sdfsdfsdf",
                            createdByAvatar: "http://placekitten.com/32/32",
                            lastActivity: "12.23.2333",
                            ratingTotal: 0,
                            commentCount: 43
                        },
                        {
                            webUrl: "foo.bar/4",
                            name: "post name 4",
                            preview: "3 wolf moon trust fund keffiyeh four loko next level raw denim culpa cliche. Excepteur irure enim adipisicing laboris ad. Art party you probably haven't heard of them trust fund, craft beer non cliche irony put a bird on it lo-fi thundercats. Next level Austin fap dreamcatcher aute, before they sold out pariatur velit synth four loko scenester nulla odio fixie tattooed. High life duis et, freegan Austin etsy velit lomo mcsweeney's sapiente sustainable reprehenderit. Duis you probably haven't heard of them salvia adipisicing butcher portland. Leggings mlkshk hoodie organic food truck.",
                            createdByName: "feet m.",
                            createdByHash: "sdfsdfsdf",
                            createdByAvatar: "http://placekitten.com/32/32",
                            lastActivity: "12.23.2333",
                            ratingTotal: 400,
                            commentCount: 2
                        },
                        {
                            webUrl: "foo.bar/5",
                            name: "post name 5",
                            preview: "3 wolf moon trust fund keffiyeh four loko next level raw denim culpa cliche. Excepteur irure enim adipisicing laboris ad. Art party you probably haven't heard of them trust fund, craft beer non cliche irony put a bird on it lo-fi thundercats. Next level Austin fap dreamcatcher aute, before they sold out pariatur velit synth four loko scenester nulla odio fixie tattooed. High life duis et, freegan Austin etsy velit lomo mcsweeney's sapiente sustainable reprehenderit. Duis you probably haven't heard of them salvia adipisicing butcher portland. Leggings mlkshk hoodie organic food truck.",
                            createdByName: "silky f.",
                            createdByHash: "sdfsdfsdf",
                            createdByAvatar: "http://placekitten.com/32/32",
                            lastActivity: "12.23.2333",
                            ratingTotal: 1000,
                            commentCount: 2
                        },
                        {
                            webUrl: "foo.bar/6",
                            name: "post name 6",
                            preview: "3 wolf moon trust fund keffiyeh four loko next level raw denim culpa cliche. Excepteur irure enim adipisicing laboris ad. Art party you probably haven't heard of them trust fund, craft beer non cliche irony put a bird on it lo-fi thundercats. Next level Austin fap dreamcatcher aute, before they sold out pariatur velit synth four loko scenester nulla odio fixie tattooed. High life duis et, freegan Austin etsy velit lomo mcsweeney's sapiente sustainable reprehenderit. Duis you probably haven't heard of them salvia adipisicing butcher portland. Leggings mlkshk hoodie organic food truck.",
                            createdByName: "loosy g.",
                            createdByHash: "sdfsdfsdf",
                            createdByAvatar: "http://placekitten.com/32/32",
                            lastActivity: "12.23.2333",
                            ratingTotal: 0,
                            commentCount: 0
                        },
                        {
                            webUrl: "foo.bar/7",
                            name: "post name 7",
                            preview: "3 wolf moon trust fund keffiyeh four loko next level raw denim culpa cliche. Excepteur irure enim adipisicing laboris ad. Art party you probably haven't heard of them trust fund, craft beer non cliche irony put a bird on it lo-fi thundercats. Next level Austin fap dreamcatcher aute, before they sold out pariatur velit synth four loko scenester nulla odio fixie tattooed. High life duis et, freegan Austin etsy velit lomo mcsweeney's sapiente sustainable reprehenderit. Duis you probably haven't heard of them salvia adipisicing butcher portland. Leggings mlkshk hoodie organic food truck.",
                            createdByName: "charlie m.",
                            createdByHash: "sdfsdfsdf",
                            createdByAvatar: "http://placekitten.com/32/32",
                            lastActivity: "12.23.2333",
                            ratingTotal: 600,
                            commentCount: 44
                        },
                        {
                            webUrl: "foo.bar/8",
                            name: "post name 8",
                            preview: "3 wolf moon trust fund keffiyeh four loko next level raw denim culpa cliche. Excepteur irure enim adipisicing laboris ad. Art party you probably haven't heard of them trust fund, craft beer non cliche irony put a bird on it lo-fi thundercats. Next level Austin fap dreamcatcher aute, before they sold out pariatur velit synth four loko scenester nulla odio fixie tattooed. High life duis et, freegan Austin etsy velit lomo mcsweeney's sapiente sustainable reprehenderit. Duis you probably haven't heard of them salvia adipisicing butcher portland. Leggings mlkshk hoodie organic food truck.",
                            createdByName: "john g.",
                            createdByHash: "sdfsdfsdf",
                            createdByAvatar: "http://placekitten.com/32/32",
                            lastActivity: "12.23.2333",
                            ratingTotal: 600,
                            commentCount: 600
                        }
                    ];
                }
            };
            for(var item in this.items) {
                Y.mix(this.items[item], testExtender);
            }
        }
    });

    communitySearchTests.add(new Y.Test.Case({
        name: "Event Handling and Operation",
        
        testNoResults: function() {
            this.initValues();
            var eo = new RightNow.Event.EventObject();
            this.instance.searchSource().fire("response", eo);
            Y.Assert.areSame(0, Y.one(this.selector).all('li').size());
            if (this.widgetData.attrs.hide_when_no_results) {
                Y.Assert.isTrue(Y.one(this.selector).hasClass("rn_Hidden"));
            }
            else {
                Y.Assert.areSame(1, Y.one(this.selector + "_Content").all("*").size());
                Y.Assert.areSame('rn_NoResults', Y.one(this.selector + "_Content").one("*").get("className"));
                Y.Assert.areSame(this.widgetData.attrs.label_no_results, Y.Lang.trim(Y.one(this.selector + "_Content").one("*").get("innerHTML")));
            }
        },
        testReportResponse: function() {
            this.initValues();

            var results = this.getResults();
            this.fireFakeResponse();

            Y.Assert.areSame(results.length, Y.one(this.selector).all('li').size());
            Y.one(this.selector).all('li').each(function(li) {
                if (this.widgetData.attrs.show_profile_picture) {
                    Y.Assert.isTrue(li.hasClass('rn_HasProfilePicture'));
                }
                else {
                    Y.Assert.isFalse(li.hasClass('rn_HasProfilePicture'));
                }
            }, this);
            
            if (this.widgetData.attrs.pagination_enabled) {
                Y.Assert.isFalse(Y.one(this.selector + "_Pagination").hasClass("rn_Hidden"));
                Y.Assert.areSame(this.widgetData.attrs.maximum_page_links - 1, Y.one(this.selector + "_Pages").all("a").size());
                Y.Assert.areSame(this.widgetData.attrs.maximum_page_links, Y.one(this.selector + "_Pages").all("*").size());
            }
            else {
                Y.Assert.isNull(Y.one(this.selector + "_Pages"));
            }
        }
    }));
    
    communitySearchTests.add(new Y.Test.Case({
        name: "UI",
        testClickNext: function() {
            this.initValues();
            if (!this.widgetData.attrs.pagination_enabled) {
                return;
            }
            var hasRun = false;
            this.instance.searchSource().on("send", function(name, resp){
                if(hasRun){
                    return;
                }
                hasRun = true;
                var ajaxParameters = resp[1];
                Y.Assert.areSame(resp[1].page, 2);
                Y.Assert.areSame(resp[1].w_id, 0);

                //Fire a response to the widget so that it will update it's state
                this.fireFakeResponse();

                //Cancel request from actually being made
                return false;
            }, this);
            Y.one(this.selector + "_Forward").simulate('click');
        },

        testClickBack: function() {
            this.initValues();

            if (!this.widgetData.attrs.pagination_enabled) {
                return;
            }
            Y.Assert.isFalse(Y.one(this.selector + "_Pagination").hasClass("rn_Hidden"));
            Y.Assert.areSame(this.widgetData.attrs.maximum_page_links - 1, Y.one(this.selector + "_Pages").all("a").size());
            Y.Assert.areSame(this.widgetData.attrs.maximum_page_links, Y.one(this.selector + "_Pages").all("*").size());
            Y.Assert.areSame(Y.one(".rn_CurrentPage").get('innerHTML'), "2");

            
            var hasRun = false;
            this.instance.searchSource().on("send", function(name, resp){
                if(hasRun){
                    return;
                }
                hasRun = true;
                Y.Assert.areSame(resp[1].page, 1);
                Y.Assert.areSame(resp[1].w_id, 0);

                //Fire a response to the widget so that it will update it's state
                this.fireFakeResponse();

                //Cancel request from actually being made
                return false;
            }, this);
            Y.one(this.selector + "_Back").simulate('click');
        },

        testClickAPage: function() {
            this.initValues();
            var pageToClick = Y.one(this.selector + "_Pages");
            if (!this.widgetData.attrs.pagination_enabled || !pageToClick) {
                return;
            }

            Y.Assert.isFalse(Y.one(this.selector + "_Pagination").hasClass("rn_Hidden"));
            Y.Assert.areSame(this.widgetData.attrs.maximum_page_links - 1, Y.one(this.selector + "_Pages").all("a").size());
            Y.Assert.areSame(this.widgetData.attrs.maximum_page_links, Y.one(this.selector + "_Pages").all("*").size());
            Y.Assert.areSame(Y.one(".rn_CurrentPage").get('innerHTML'), "1");
            
            pageToClick = Y.Node.getDOMNode(pageToClick.all('a').item(this.widgetData.attrs.maximum_page_links - 2));
            var hasRun = false;
            this.instance.searchSource().on("send", function(name, resp){
                if(hasRun){
                    return;
                }
                hasRun = true;
                Y.Assert.areSame(resp[1].page, this.widgetData.attrs.maximum_page_links);
                Y.Assert.areSame(resp[1].w_id, 0);

                //Fire a response to the widget so that it will update it's state
                this.fireFakeResponse();

                //Cancel request from actually being made
                return false;
            }, this);
            Y.one(pageToClick).simulate('click');
        },

        testKeywordChange: function() {
            this.initValues();
            if (!this.widgetData.attrs.pagination_enabled) {
                return;
            }

            Y.Assert.isFalse(Y.one(this.selector + "_Pagination").hasClass("rn_Hidden"));
            Y.Assert.areSame(this.widgetData.attrs.maximum_page_links - 1, Y.one(this.selector + "_Pages").all("a").size());
            Y.Assert.areSame(this.widgetData.attrs.maximum_page_links, Y.one(this.selector + "_Pages").all("*").size());
            Y.Assert.areSame(Y.one(".rn_CurrentPage").get('innerHTML'), this.widgetData.attrs.maximum_page_links.toString());
            var hasRun = false;
            this.instance.searchSource()
            .on("response", function(name, resp) {
                if(hasRun){
                    return;
                }
                hasRun = true;
                Y.Assert.areSame(resp[0].data.searchResults.length, Y.one(this.selector).all('li').size());
                Y.Assert.isTrue(Y.one(this.selector + "_Back").hasClass("rn_Hidden"));
                Y.Assert.areSame("1", Y.Lang.trim(Y.one(this.selector).one(".rn_CurrentPage").get("innerHTML")));
            }, this)
            .on("search", function() {
                return new RightNow.Event.EventObject(this, {data: {
                    keyword: "community"
                }});
            })
            .fire("keywordChanged", new RightNow.Event.EventObject(this, {data: "community1"}))
            .fire("search", new RightNow.Event.EventObject());

            this.fireFakeResponse();
        }
    }));
    return communitySearchTests;
});
UnitTest.run();