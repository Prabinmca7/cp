UnitTest.addSuite({
    type: UnitTest.Type.Widget,
    instanceID: 'CommunityPostDisplay_0'
}, function(Y, widget, baseSelector) {
    var communityPostDisplayTests = new Y.Test.Suite({
        name: "standard/social/CommunityPostDisplay",

        setUp: function() {
            var testExtender = {
                initValues : function() {
                    this.instanceID = 'CommunityPostDisplay_0';
                }
            };

            for(var item in this.items) {
                Y.mix(this.items[item], testExtender);
            }
        }
    });

    communityPostDisplayTests.add(new Y.Test.Case({
        name: "Focus/Blur tests",

        "Comment box should add and remove placeholder on focus and blur": function() {
            if (Y.UA.gecko) return; // escaping test case for Firefox since it doesn't handle the onFocus event very well. Bugzilla bug #53579

            var textBox = Y.one(baseSelector + '_Comment'),
                submitSpan = Y.one(baseSelector + '_PostCommentSubmit'),
                hasTested = false;

            //Remove placeholder on focus
            Y.Assert.areSame(widget.data.attrs.label_comment_placeholder, textBox.get('value'));
            Y.Assert.isTrue(submitSpan.hasClass('rn_Hidden'));

            textBox.once('focus', function() {
                Y.Assert.areSame('', textBox.get('value'));
                Y.Assert.isFalse(submitSpan.hasClass('rn_Hidden'));
                hasTested = true;
            });
            textBox.focus();
            Y.Assert.isTrue(hasTested);

            //Add placeholder on blur
            hasTested = false;
            Y.Assert.areSame('', textBox.get('value'));
            Y.Assert.isFalse(submitSpan.hasClass('rn_Hidden'));

            textBox.once('blur', function() {
                Y.Assert.areSame(widget.data.attrs.label_comment_placeholder, textBox.get('value'));
                Y.Assert.isTrue(submitSpan.hasClass('rn_Hidden'));
                hasTested = true;
            });
            textBox.blur();
            Y.Assert.isTrue(hasTested);

            //Don't add placeholder when the box has content
            var testValue = 'my value that should not be discarded';
            hasTested = false;
            textBox.set('value', testValue);
            textBox.focus();
            textBox.once('blur', function() {
                Y.Assert.areSame(testValue, textBox.get('value'));
                Y.Assert.isFalse(submitSpan.hasClass('rn_Hidden'));
                hasTested = true;
            });
            textBox.blur();
            Y.Assert.isTrue(hasTested);

        }
    }));

    communityPostDisplayTests.add(new Y.Test.Case(
    {
        name: "Event Handling and Operation",

        "Clicking view comments should fire a request": function() {
            this.initValues();

            var hasCalledRequest = false;
            RightNow.Event.subscribe('evt_getCommentListRequest', function(name, args){
                hasCalledRequest = true;
                Y.Assert.areSame(name, "evt_getCommentListRequest");
                args = args[0];
                Y.Assert.areSame(args.w_id, this.instanceID);
                Y.Assert.areSame(args.data.postID, widget.data.attrs.post_hash);
                Y.Assert.areSame(args.data.w_id, 0);
                //Cancel request from actually going to server
                return false;
            }, this);
            Y.one(baseSelector + '_ShowComments').simulate('click');
            Y.Assert.isTrue(hasCalledRequest);
        },

        "Comments should be added to the widget on response": function() {
            this.initValues();
            var originalEventObject = new RightNow.Event.EventObject(widget, {data: {
                    w_id: widget.data.info.w_id,
                    postID: widget.data.js.postHash
                }}),
                response = {
                    comments:[
                        {
                            createdBy: {
                                avatar: "http://placekitten.com/32/32",
                                name: "Mr. Splashypants",
                                guid: 12
                            },
                            value: "Est odio master cleanse, quis art party fugiat keytar. You probably haven't heard of them art party photo booth authentic.",
                            created: "12.23.2333",
                            id: "asdf",
                            ratingCount: 12,
                            ratingValueTotal: 600
                        },
                        {
                            createdBy: {
                                avatar: "http://placekitten.com/24/24",
                                name: "Bobo PC",
                                guid: 13
                            },
                            value: "Pour-over officia keytar esse dolore do dolor laborum bushwick. Typewriter mollit street art fanny pack reprehenderit, voluptate ethnic direct trade leggings iphone brunch exercitation velit wes anderson salvia.",
                            created: "12.24.2333",
                            id: "fdsa",
                            ratingCount: 2,
                            ratingValueTotal: 0
                        },
                        {
                            createdBy: {
                                avatar: "http://placekitten.com/36/36",
                                name: "C Diddy",
                                guid: 13
                            },
                            value: "Stumptown etsy kale chips +1 scenester 8-bit. Ut kale chips sunt, sed street art jean shorts yr mcsweeney's food truck consequat. Keytar nostrud hoodie mumblecore, velit cred proident fanny pack VHS squid laboris.",
                            created: "12.24.2333",
                            id: "qwerty",
                            ratingCount: 5,
                            ratingValueTotal: 400,
                            ratedByRequestingUser:{
                                ratingValue: 100
                            }
                        }
                    ]
                };
            widget._readyToShowComments(response, originalEventObject);
            var commentList = Y.one(baseSelector + "_Comments").all(".rn_Comment");
            Y.Assert.areSame(commentList.size(), 3);
        },

        "Clicking rating up should fire a request": function(){
            this.initValues();

            var hasCalledRequest = false;
            RightNow.Event.subscribe('evt_postCommentActionRequest', function(name, args){
                if(hasCalledRequest) return;
                hasCalledRequest = true;
                Y.Assert.areSame(name, "evt_postCommentActionRequest");
                args = args[0];
                Y.Assert.areSame(args.w_id, this.instanceID);
                Y.Assert.areSame(args.data.postID, widget.data.attrs.post_hash);
                Y.Assert.areSame(args.data.action, "rate");
                Y.Assert.areSame(args.data.content, 100);
                Y.Assert.areSame(args.data.w_id, 0);
                //Cancel request from actually going to server
                return false;
            }, this);
            Y.one(baseSelector + '_RateUp').simulate('click');
            Y.Assert.isTrue(hasCalledRequest);
        },

        "Clicking comment submit should fire a request": function(){
            this.initValues();
            var textBox = Y.one(baseSelector + '_Comment'),
                submitButton = Y.one(baseSelector + '_Submit'),
                hasCalledRequest = false;

            textBox.focus();
            textBox.set('value', 'My test comment');
            RightNow.Event.subscribe('evt_postCommentActionRequest', function(name, args){
                if(hasCalledRequest) return;
                hasCalledRequest = true;
                Y.Assert.areSame(name, "evt_postCommentActionRequest");
                args = args[0];
                Y.Assert.areSame(args.w_id, this.instanceID);
                Y.Assert.areSame(args.data.postID, widget.data.attrs.post_hash);
                Y.Assert.areSame(args.data.action, "reply");
                Y.Assert.areSame(args.data.content, "My test comment");
                Y.Assert.areSame(args.data.w_id, 0);
                //Cancel request from actually going to server
                return false;
            }, this);
            submitButton.simulate('click');
            Y.Assert.isTrue(hasCalledRequest);
        }
    }));

    return communityPostDisplayTests;
}).run();