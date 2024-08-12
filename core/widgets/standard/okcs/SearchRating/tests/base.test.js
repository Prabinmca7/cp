UnitTest.addSuite({
    type: UnitTest.Type.Widget,
    instanceID: 'SearchRating_0'
}, function(Y, widget, baseSelector) {
    var suite = new Y.Test.Suite({
        name: "standard/okcs/SearchRating",
        
        setUp: function(){
            var testExtender = {
                initValues : function() {
                    this.instanceID = 'SearchRating_0';
                    this.instance = RightNow.Widgets.getWidgetInstance(this.instanceID);
                    this.widgetData = this.instance.data;
                    this.searchComment = Y.one("#rn_" + this.instanceID + "_SearchComment");
                    this.searchComment.addClass('rn_Hidden');   //User Feedback control should be hidden.
                    this.submitMessage = Y.one("#rn_" + this.instanceID + "_ThanksMessage");
                    this.starRating = Y.all(".rn_Rating");
                    this.submitButton = Y.one("#rn_" + this.instanceID + "_SubmitButton");
                    this.ratingComment = Y.one("#rn_" + this.instanceID + "_FeedbackMessage");
                    this.form = Y.one("form");
                    this.labels = Y.all("label");
                    widget._rating = "rn_SearchRating_0_1";
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
            Y.Assert.isTrue(this.submitMessage.hasClass("rn_Hidden"));
        },

        "Clicking the rating star button should display comment box": function() {
            this.initValues();
            this.starRating.item(0).simulate('click');
            Y.Assert.isFalse(this.ratingComment.hasClass("rn_Hidden")); 
        },
        
        "Rating should not be allowed once search is rated ": function() {
            this.initValues();
            Y.Assert.isTrue(this.searchComment.hasClass("rn_Hidden"));
            
            this.starRating.item(0).simulate('click');
            Y.Assert.isFalse(this.searchComment.hasClass("rn_Hidden"));
            Y.Assert.isTrue(this.submitMessage.hasClass("rn_Hidden"));
            
            this.submitButton.simulate('click');
            Y.Assert.isTrue(this.submitMessage.hasClass("rn_Hidden"));
        },

        "Clicking the rating star button should display submit button": function() {
            this.initValues();
            this.starRating.item(0).simulate('click');
            Y.Assert.isFalse(this.submitButton.hasClass("rn_Hidden")); 
        },
        
        "Search Rating widget should not be displayed when no results are fetched": function() {
            this.initValues();
            var searchResult = {};
             searchResult.data = {};
             searchResult.data.searchResults = {};
             searchResult.data.searchResults.results = {};
             searchResult.data.searchResults.results.results = {};
             searchResult.data.searchResults.results.results.length = 0;
            Y.Assert.isTrue(Y.one('#rn_' + this.instanceID + '_Content').hasClass('rn_Hidden'));
        },

        "Mobile View : Search Rating widget should not be displayed when no results are fetched": function() {
            this.initValues();
            widget.data.attrs.toggle_title = true;
            var searchResult = {};
            searchResult.data = {};
            searchResult.data.searchResults = {};
            searchResult.data.searchResults.results = {};
            searchResult.data.searchResults.results.results = {};
            searchResult.data.searchResults.results.results.length = 0;
            if (widget.data.attrs.toggle_title)
                Y.Assert.isNull(Y.one("#rn_SearchRatingHeader"));
        },

        "Accessibility Tests": function() {
            this.initValues();
            
            // Check if buttons for ratings have corresponding text value
            for (var i = 0; i < this.starRating.size(); i++) {
                var textValue = this.starRating.item(i).get('text');
                Y.Assert.areSame(textValue, RightNow.Text.sprintf(widget.data.attrs.label_rating, i + 1, widget.data.attrs.max_rating_count));
            }

            // Ensure that form exists
            Y.Assert.isNotNull(this.form);
            
            // Check that textarea has a corresponding label
            Y.Assert.areSame(this.labels.item(0).get('for'), this.ratingComment.get('id'));

            // Check that submit button has a corresponding label
            Y.Assert.areSame(this.labels.item(1).get('for'), this.submitButton.get('id'));
            Y.Assert.isNotNull(this.submitButton.get('aria-label'));
            Y.Assert.isNotNull(this.ratingComment.get('aria-label'));
        },

        "Thank you message after search rating submission": function() {
            this.initValues();
            this.starRating.item(0).simulate('click');
            this.submitButton.simulate('click');
            Y.Assert.areSame(this.submitMessage.get('textContent'), 'Thank you for your feedback!');
        }
    }));
    return suite;
});
UnitTest.run();
