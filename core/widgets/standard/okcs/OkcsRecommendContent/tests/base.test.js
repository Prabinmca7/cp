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
                    this.submitButton = Y.one("#rn_" + this.instanceID + "_RecommendationSubmit");
                    this.priorityField = Y.one("#rn_" + this.instanceID + "_Priority");
                    this.contentTypeField = Y.one("#rn_" + this.instanceID + "_ContentType");
                    this.descriptionField = Y.one("#rn_" + this.instanceID + "_Description");
                    this.characterRemainingDiv = Y.one("#rn_" + this.instanceID + "_CharacterRemaining");
                    this.fieldText = this.characterRemainingDiv.get('innerHTML');
                    this.instance._errorDisplay = Y.one("#rn_" + this.instanceID + "_ErrorLocation");
                }
            };

            for (var item in this.items) {
                Y.mix(this.items[item], testExtender);
            }
        }
    });

    suite.add(new Y.Test.Case({
        name: "Event Handling and Operation",

        "Verify if Content Type field is throwing error message when empty": function() {
            this.initValues();
            this.submitButton.simulate('click');
            if (Y.one("#rn_" + this.instanceID + "_ContentType").get('value') === '') {
                var errorMessage = Y.one("#rn_" + this.instanceID + "_ErrorLocation a").get("innerHTML");
                Y.Assert.areSame(errorMessage, 'Content Type field is required');
            }
        },

        "Verify if Title field is throwing error message when empty": function() {
            this.initValues();
            var optionNode = Y.Node.create('<option value="TEST" selected>TEST</option>');
            this.contentTypeField.append(optionNode);
            this.contentTypeField.set('selectedIndex', 1);
            this.submitButton.simulate('click');
            if (Y.one("#rn_" + this.instanceID + "_Title").get('value') === '') {
                var errorMessage = Y.one("#rn_" + this.instanceID + "_ErrorLocation a").get("innerHTML");
                Y.Assert.areSame(errorMessage, 'Title field is required');
            }
        },

        "Verify if Description field is throwing error message when empty": function() {
            this.initValues();
            var optionNode = Y.Node.create('<option value="TEST" selected>TEST</option>');
            this.contentTypeField.append(optionNode);
            this.contentTypeField.set('selectedIndex', 1);
            Y.one("#rn_" + this.instanceID + "_Title").set('value', 'test');
            this.submitButton.simulate('click');
            if (Y.one("#rn_" + this.instanceID + "_Description").get('value') === '') {
                var errorMessage = Y.one("#rn_" + this.instanceID + "_ErrorLocation a").get("innerHTML");
                Y.Assert.areSame(errorMessage, 'Description field is required');
            }
        },
        
        "Verify if Priority field is having a default value while loading": function() {
            this.initValues();
            var fieldValue = this.priorityField.get('value');
            Y.Assert.areSame(fieldValue, 'None');
         },
         
         "Verify all Priority field values while loading": function() {
            this.initValues();
            Y.Assert.isTrue(this.priorityField.hasChildNodes());
            var fieldValue = this.priorityField.get('children');
            Y.Assert.areSame(fieldValue.item(1).get('value').toLowerCase(), 'none');
            Y.Assert.areSame(fieldValue.item(2).get('value').toLowerCase(), 'low');
            Y.Assert.areSame(fieldValue.item(3).get('value').toLowerCase(), 'medium');
            Y.Assert.areSame(fieldValue.item(4).get('value').toLowerCase(), 'high');
         },
         
         "Verify the default display of the number of characters entered": function() {
            this.initValues();
            Y.Assert.areSame(this.fieldText, widget.data.attrs.default_maxlength_value + " " + widget.data.attrs.label_characters_remaining);
         },
         
         "Validating description field character count": function() {
            this.initValues();
            this.descriptionField.set('value', 'This enhancement is added under the existing div to count the number of characters entered in the text area, this will help the user experience');
            var descriptionLength = this.descriptionField.get('value').length;
            widget._updateCharacterCount();
            Y.Assert.areNotSame(widget.data.attrs.default_maxlength_value, descriptionLength);
            Y.Assert.areNotSame(this.fieldText, descriptionLength + " " + widget.data.attrs.label_characters_remaining);
         }
    }));

    return suite;
});
UnitTest.run();