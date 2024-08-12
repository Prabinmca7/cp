UnitTest.addSuite({
    type:       UnitTest.Type.Widget,
    instanceID: 'FavoritesButton_0'
}, function (Y, widget, baseSelector) {
    var suite = new Y.Test.Suite({
        name: "standard/okcs/FavoritesButton",

        setUp: function(){
            var testExtender = {
                initValues : function() {
                    this.instanceID = 'FavoritesButton_0';
                    this.instance = RightNow.Widgets.getWidgetInstance(this.instanceID);
                    this.widgetData = this.instance.data;
                    this.favoriteDiv = Y.one("#rn_" + this.instanceID + "_Favorite");
                    this.favoriteButton = Y.one("#rn_" + this.instanceID + "_FavoritesButton");
                    this.Alert = Y.one("#rn_" + this.instanceID + "_Alert");
                }
            };

            for (var item in this.items) {
                Y.mix(this.items[item], testExtender);
            }
        }
    });

    suite.add(new Y.Test.Case({
        name: "Favorite Button test cases",

        "Verify default button label before adding favorite": function() {
            this.initValues();
            Y.Assert.areSame(this.favoriteButton.getHTML().trim(), widget.data.attrs.label_add_favorite_button, "title is correct!");
        },

        "Verify the response after add favorite": function() {
            this.initValues();
            var responseData = {"recordId":"501CEB19532344858EB3B0FADE31E930","key":"favorite_document","value":"1000026,1000000,1000003,1000002,1000001,1000004"};
            var eo = new RightNow.Event.EventObject(null, {
                data: responseData
            });
            widget._displayAddFavoriteMessage(responseData);
            Y.Assert.areSame(this.favoriteButton.getHTML().trim(), widget.data.attrs.label_remove_favorite_button, "label is correct!");
        },
        
        "Verify the button label after duplicate add favorite": function() {
            this.initValues();
            var responseData = {"result":[{'errorCode':'OKDOM-USRFAV01','externalMessage':'Favorite already added.','extraDetails':'501CEB19532344858EB3B0FADE31E930'}]};
            var eo = new RightNow.Event.EventObject(null, {
                data: responseData
            });
            widget._displayAddFavoriteMessage(responseData);
            Y.Assert.areSame(this.favoriteButton.getHTML().trim(), widget.data.attrs.label_remove_favorite_button, "label is correct!");
        },
        
        "Verify the response after remove favorite": function() {
            this.initValues();
            var responseData = {"recordId":"501CEB19532344858EB3B0FADE31E930","key":"favorite_document","value":"1000000,1000003,1000002,1000001,1000004"};
            var eo = new RightNow.Event.EventObject(null, {
                data: responseData
            });
            widget._displayRemoveFavoriteMessage(responseData);
            Y.Assert.areSame(this.favoriteButton.getHTML().trim(), widget.data.attrs.label_add_favorite_button, "label is correct!");
        },
        
        "Verify the button label after duplicate remove favorite": function() {
            this.initValues();
            var responseData = {"result":[{'errorCode':'OKDOM-USRFAV02','externalMessage':'Favorite already deleted.'}]};
            var eo = new RightNow.Event.EventObject(null, {
                data: responseData
            });
            widget._displayRemoveFavoriteMessage(responseData);
            Y.Assert.areSame(this.favoriteButton.getHTML().trim(), widget.data.attrs.label_add_favorite_button, "label is correct!");
        }
    }));

    return suite;
}).run();
