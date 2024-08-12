UnitTest.addSuite({
    type:       UnitTest.Type.Widget,
    instanceID: 'OkcsSuggestions_0'
}, function (Y, widget, baseSelector) {
    var suite = new Y.Test.Suite({
        name: "standard/okcs/OkcsSuggestions",

        setUp: function() {
            var testExtender = {
                initValues: function() {
                    this.instanceID = 'OkcsSuggestions_0';
                    widget.inputNodes = Y.one(".rn_" + this.instanceID + " input");
                    this._response = '{"items":[{"highlightedTitle":"<span class=\"rn_KASuggestTitle\">Ora</span>cle Knowledge Service Cloud - Knowledge Management","title":"Oracle Knowledge Service Cloud - Knowledge Management","answerId":1004000},{"highlightedTitle":"Using Office 2007 To Upload Documents  <span class=\"rn_KASuggestTitle\">Ora</span>cle Menu Is Not Available In Excel","title":"Using Office 2007 To Upload Documents  Oracle Menu Is Not Available In Excel","answerId":1000015},{"highlightedTitle":"Using Office 2007 To Upload Documents  <span class=\"rn_KASuggestTitle\">Ora</span>cle Menu Is Not Available In Excel","title":"Using Office 2007 To Upload Documents  Oracle Menu Is Not Available In Excel","answerId":1000019},{"highlightedTitle":"How To Clean Up Your Search Directory After Switching from Verity to <span class=\"rn_KASuggestTitle\">Ora</span>cle Database Fulltext Searching","title":"How To Clean Up Your Search Directory After Switching from Verity to Oracle Database Fulltext Searching","answerId":1000044},{"highlightedTitle":"How To Configure Anti-Virus On Windows Server Running <span class=\"rn_KASuggestTitle\">Ora</span>cle Database","title":"How To Configure Anti-Virus On Windows Server Running Oracle Database","answerId":1000060},{"highlightedTitle":"How To Perform a Silent Installation Of <span class=\"rn_KASuggestTitle\">Ora</span>cle Data Integrator 11g On Unix Systems","title":"How To Perform a Silent Installation Of Oracle Data Integrator 11g On Unix Systems","answerId":1000086},{"highlightedTitle":"How to Configure <span class=\"rn_KASuggestTitle\">Ora</span>cle BPM 10gR3 with <span class=\"rn_KASuggestTitle\">Ora</span>cle Internet Directory ","title":"How to Configure Oracle BPM 10gR3 with Oracle Internet Directory ","answerId":1000089},{"highlightedTitle":"Example of replicating <span class=\"rn_KASuggestTitle\">Ora</span>cle Spatial with <span class=\"rn_KASuggestTitle\">Ora</span>cle GoldenGate ","title":"Example of replicating Oracle Spatial with Oracle GoldenGate ","answerId":1000138},{"highlightedTitle":"Allow Bind Variables in <span class=\"rn_KASuggestTitle\">ora</span> query-database Function","title":"Allow Bind Variables in ora query-database Function","answerId":1000140},{"highlightedTitle":"How to Change the Source File Path That <span class=\"rn_KASuggestTitle\">Ora</span>cle Legal Whitehill Enterprise Converter is Pointing to ","title":"How to Change the Source File Path That Oracle Legal Whitehill Enterprise Converter is Pointing to ","answerId":1000139}],"hasMore":false,"links":[{"rel":"canonical","href":"https://day124-18800-sql-84h-irs.dq.lan:443/kmrest1/api/v1/suggestedArticles?limit=20&offset=0&contentState=PUBLISHED&q=ora","mediaType":"application/json, application/xml","method":"GET"}],"count":10}';
                }
            };

            for (var item in this.items) {
                Y.mix(this.items[item], testExtender);
            }
        }
    });

    suite.add(new Y.Test.Case({
        name: "Event Handling and Operation",

        setUp: function() {
            widget.searchSource().on('search', function() {
                // Prevent the search from happening.
                return false;
            });
        },

        "Validate data and DOM": function() {
            this.initValues();
            var query = "oracle";
            
            widget._getSuggestions(query);
            var content = Y.one(".rn_" + this.instanceID + " div");
            Y.Assert.isNotNull(content);
        },

        "Verify the title for suggestions(search) loaded": function() {
            this.initValues();

            widget._showSuggestions(this._response);
            var title = Y.one(".yui3-aclist-list");
            Y.Assert.areSame(title.get('aria-label'), widget.data.attrs.label_suggested_search);
        },
        
        "Verify the title for suggestions(answer) loaded": function() {
            this.initValues();            
            widget.data.attrs.suggestions_as = 'Answer';
            
            widget._showSuggestions(this._response);
            var title = Y.one(".yui3-aclist-list");
            Y.Assert.areSame(title.get('aria-label'), widget.data.attrs.label_suggested_answer);
        }
    }));

    return suite;
}).run();
