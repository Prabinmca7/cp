UnitTest.addSuite({
    type: UnitTest.Type.Widget,
    instanceID: 'OkcsRecommendContent_0'
}, function(Y, widget, baseSelector) {
    var suite = new Y.Test.Suite({
        name: "standard/okcs/OkcsRecommendContent",

        setUp: function() {
            var testExtender = {
                initValues: function() {
                    this.instanceID = 'OkcsRecommendContent_0';
                    this.instance = RightNow.Widgets.getWidgetInstance(this.instanceID);
                    this.widgetData = this.instance.data;
                    this.descriptionField = Y.one("#rn_" + this.instanceID + "_Description");
                    this.characterRemainingDiv = Y.one("#rn_" + this.instanceID + "_CharacterRemaining");
                }
            };

            for (var item in this.items) {
                Y.mix(this.items[item], testExtender);
            }
        }
    });

    suite.add(new Y.Test.Case({
        name: "Event Handling and Operation",

         "Validating description field character count": function() {
            this.initValues();
            this.descriptionField.set('value', 'This enhancement is added under the existing div to count the number of characters entered in the text area, this will help the user experience.Checking the number of values entered and on keyup click the value should be updated.');
            var descriptionLength = this.descriptionField.get('value').length;
            widget._updateCharacterCount()
            Y.Assert.areSame(this.characterRemainingDiv.get('innerHTML'), (widget.data.attrs.default_maxlength_value - descriptionLength) + " " + widget.data.attrs.label_characters_remaining);
         }
    }));

    return suite;
});
UnitTest.run();