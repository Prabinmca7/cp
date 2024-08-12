UnitTest.addSuite({
    type: UnitTest.Type.Widget,
    instanceID: 'DocumentRating_0'
}, function(Y, widget, baseSelector) {
    var suite = new Y.Test.Suite({
        name: "standard/okcs/DocumentRating",
        
        setUp: function(){
            var testExtender = {
                initValues : function() {
                    this.instanceID = 'DocumentRating_0';
                    this.instance = RightNow.Widgets.getWidgetInstance(this.instanceID);
                    this.widgetData = this.instance.data;
                    this.feedbackMessage = Y.one("#rn_" + this.instanceID + "_ThanksMessage");
                    this.submitButton = Y.one("#rn_" + this.instanceID + "_SubmitButton");
                    this.rating = Y.all(".rn_Rating");
                    this.documentComment = Y.one("#rn_" + this.instanceID + "_DocumentComment");
                    this.ratingComment = Y.one("#rn_" + this.instanceID + "_FeedbackMessage");
                }
            };

            for (var item in this.items) {
                Y.mix(this.items[item], testExtender);
            }
        }
    });

    suite.add(new Y.Test.Case({
        name: "Event Handling and Operation",

        "Thank you message should be hidden by default": function() {
            this.initValues();
            Y.Assert.isTrue(this.documentComment.hasClass("rn_Hidden"));
            Y.Assert.isTrue(this.feedbackMessage.hasClass("rn_Hidden"));
        },

        "Verify if rating is retained after star click and mouse move ( for all rating values )": function() {
            this.initValues();
            
            this.rating.item(0).simulate('click');// One star rating chosen
            this.rating.item(3).simulate("mouseover");
            this.rating.item(4).simulate("mouseout");
            Y.Assert.areSame('1',this.instance._ratingIndex);
            
            this.rating.item(1).simulate('click');// Two star rating chosen
            this.rating.item(3).simulate("mouseover");
            this.rating.item(4).simulate("mouseout");
            Y.Assert.areSame('2',this.instance._ratingIndex);
            
            this.rating.item(2).simulate('click');// Three star rating chosen
            this.rating.item(3).simulate("mouseover");
            this.rating.item(4).simulate("mouseout");
            Y.Assert.areSame('3',this.instance._ratingIndex);
            
            this.rating.item(3).simulate('click');// Four star rating chosen
            this.rating.item(3).simulate("mouseover");
            this.rating.item(4).simulate("mouseout");
            Y.Assert.areSame('4',this.instance._ratingIndex);
            
            this.rating.item(4).simulate('click');//Five star rating chosen
            this.rating.item(3).simulate("mouseover");
            this.rating.item(4).simulate("mouseout");
            Y.Assert.areSame('5',this.instance._ratingIndex);
        },

        "Successful submission of rating should display feedback message and rate button should be disabled": function() {
            this.initValues();
            var responseData = {"_isParsed":true,"validationFunction":"is_bool"};
            var eo = new RightNow.Event.EventObject(null, {
                data: responseData
            });
            widget._displayRatingSubmissionMessage(responseData);
            var feedbackMessage = Y.one("#rn_" + this.instanceID + "_ThanksMessage");
            Y.Assert.isFalse(feedbackMessage.hasClass("rn_Hidden"));
            Y.Assert.isTrue(this.submitButton.get('disabled'));
        },

        "Accessibility Tests": function() {
            this.initValues();
            
            // If buttons, check for ratings have corresponding text value
            if (this.rating.item(0).get('tagName').toLowerCase() === 'button') {
                for (var i=0; i < this.rating.size(); i++) {
                    var textValue = this.rating.item(i).get('text'),
                        expectedValue = i + 1;
                    Y.Assert.isTrue(textValue.indexOf(expectedValue) > -1);
                }
            }

            // If input (i.e. radio button), check for has a corresponding label
            if (this.rating.item(0).get('tagName').toLowerCase() === 'input') {
                for (var i=0; i < this.rating.size(); i++) {
                    var labelFor = this.rating.item(i).get('nextSibling').get('for');
                    Y.Assert.areSame(labelFor, this.rating.item(i).get('id'));
                }
            }
        },
        
        "Clicking the rating star button should display comment box": function() {
            this.initValues();
            this.rating.item(0).simulate('click');
            Y.Assert.isFalse(this.ratingComment.hasClass("rn_Hidden")); 
        },
        
        "Test Concatenation of Long feedback message": function() {
            this.initValues();
            this.rating.item(0).simulate('click');
            Y.Assert.isFalse(this.ratingComment.hasClass("rn_Hidden"));
            this.ratingComment.value = 'To position windows server 2003 more competitively against other web servers, Microsoft has released a stripped-down-yet-impressive edition of windows server 2003 designed specially for web services. the feature set and licensing allows customers easy deployment of web pages, web sites, web applications and web services. Web Edition supports 2GB of RAM and a two-way symmetricTo position windows server 2003 more competitively against other web servers, Microsoft has released a stripped-down-yet-impressive edition of windows server 2003 designed specially for web services. the feature set and licensing allows customers easy deployment of web pages, web sites, web applications and web services. Web Edition supports 2GB of RAM and a two-way symmetricTo position windows server 2003 more competitively against other web servers, Microsoft has released a stripped-down-yet-impressive edition of windows server 2003 designed specially for web services. the feature set and licensing allows customers easy deployment of web pages, web sites, web applications and web services. Web Edition supports 2GB of RAM and a two-way symmetricTo position windows server 2003 more competitively against other web servers, Microsoft has released a stripped-down-yet-impressive edition of windows server 2003 designed specially for web services. the feature set and licensing allows customers easy deployment of web pages, web sites, web applications and web services. Web Edition supports 2GB of RAM and a two-way symmetricTo position windows server 2003 more competitively against other web servers, Microsoft has released a stripped-down-yet-impressive edition of windows server 2003 designed specially for web services. the feature set and licensing allows customers easy deployment of web pages, web sites, web applications and web services. Web Edition supports 2GB of RAM and a two-way symmetricTo position windows server 2003 more competitively against other web servers, Microsoft has released a stripped-down-yet-impressive edition of windows server 2003 designed specially for web services. the feature set and licensing allows customers easy deployment of web pages, web sites, web applications and web services. Web Edition supports 2GB of RAM and a two-way symmetricTo position windows server 2003 more competitively against other web servers, Microsoft has released a stripped-down-yet-impressive edition of windows server 2003 designed specially for web services. the feature set and licensing allows customers easy deployment of web pages, web sites, web applications and web services. Web Edition supports 2GB of RAM and a two-way symmetricTo position windows server 2003 more competitively against other web servers, Microsoft has released a stripped-down-yet-impressive edition of windows server 2003 designed specially for web services. the feature set and licensing allows customers easy deployment of web pages, web sites, web applications and web services. Web Edition supports 2GB of RAM and a two-way symmetricTo position windows server 2003 more competitively against other web servers, Microsoft has released a stripped-down-yet-impressive edition of windows server 2003 designed specially for web services. the feature set and licensing.asy deployment of web pages, web sites, web applications and web services. Web Edition supports 2GB of RAM and a two-way symmetricTo position windows server 2003 more competitively against other web servers, Microsoft has released a stripped-down-yet-impressive edition of windows server 2003 designed specially for web services. the feature set and licensing.asy deployment of web pages, web sites, web applications and web services. Web Edition supports 2GB of RAM and a two-way symmetricTo position windows server 2003 more competitively against other web servers, Microsoft has released a stripped-down-yet-impressive edition of windows server 2003 designed specially for web services. the feature set and licensing.the feature set and 40000 and stareted now 4000 sereies please check'.slice(0,this.ratingComment.getAttribute("maxLength"));           
            var maxlength  = this.ratingComment.getAttribute("maxLength");
            this.submitButton.simulate('click');
            this.wait(function(){
                Y.Assert.areSame(parseInt(maxlength),this.ratingComment.value.length);
            }, 1100);
        }
    }));

    return suite;
});
UnitTest.run();