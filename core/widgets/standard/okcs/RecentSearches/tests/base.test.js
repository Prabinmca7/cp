UnitTest.addSuite({
    type: UnitTest.Type.Widget,
    instanceID: 'RecentSearches_0'
}, function(Y, widget, baseSelector) {
    var suite = new Y.Test.Suite({
        name: "standard/okcs/RecentSearches",

        setUp: function() {
            var testExtender = {
                initValues: function() {
                    this.instanceID = 'RecentSearches_0';
                    widget.inputNodes = Y.one(".rn_" + this.instanceID + " input");
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
            var responseData = ["content", "test", "windows", "loan"];
            widget.data.js.recentSearches = responseData;
            widget._showRecentSearches();
            var content = Y.one(".rn_" + this.instanceID + " div");
            Y.Assert.isNotNull(content);
        },

        "Verify the title and number of recent searches loaded": function() {
            this.initValues();
            var title = Y.one(".rn_" + this.instanceID + " ul"),
                list = Y.all(".yui3-aclist-item");
            widget.data.attrs.no_of_suggestions = 4;
            widget._showRecentSearches();
            Y.Assert.areSame(title.get('aria-label'), widget.data.attrs.label_recent_search);
            Y.Assert.areSame(list.size(), widget.data.attrs.no_of_suggestions);
        },

        "Validate recent search with multiple special characters": function() {
            this.initValues();
            widget.data.attrs.no_of_suggestions = 10;
            //Here the language related results are encrypted and decrypted while displaying
            var responseData = ["#phone(0001)-[701_{9898}_@=+*1800%^]$", "html & css", "javascript &gt; html", "window\"s", "&lt;div&gt;window's&lt;/div&gt;", "chinese = \u9019\u662f\u9019\u88e1\u7684\u7a97\u53e3\u6587\u672c",
                "Hebrew - \u05d6\u05d4\u05d5 \u05d8\u05e7\u05e1\u05d8 \u05d7\u05dc\u05d5\u05e0\u05d5\u05ea \u05db\u05d0\u05df\u00ae\u2122 \u20ac",
                "arabic | \u0647\u0630\u0627 \u0647\u0648 \u0648\u064a\u0646\u062f\u0648\u0632 \u0627\u0644\u0646\u0635 \u0647\u0646\u0627",
                "french ~` Ceci est un texte de fen\u00eatres ici", "english&lt;b&gt;test&lt;/b&gt;windows"
            ];
            widget.data.js.recentSearches = responseData;
            widget._showRecentSearches();
            this.recentSearchList = Y.all(".yui3-aclist-item");
            Y.Assert.isNotNull(this.recentSearchList.size());
            for (var i = 0; i < this.recentSearchList.size(); i++) {
                Y.Assert.isNotNull(this.recentSearchList.item(i).get('text'));
            }
            Y.Assert.areSame("#phone(0001)-[701_{9898}_@=+*1800%^]$", this.recentSearchList.item(0).get('text'));
            Y.Assert.areSame("html & css", this.recentSearchList.item(1).get('text'));
            Y.Assert.areSame("javascript > html", this.recentSearchList.item(2).get('text'));
            Y.Assert.areSame("window\"s", this.recentSearchList.item(3).get('text'));
            Y.Assert.areSame("<div>window\'s</div>", this.recentSearchList.item(4).get('text'));
            Y.Assert.areSame("chinese = 這是這裡的窗口文本", this.recentSearchList.item(5).get('text'));
            Y.Assert.areSame("Hebrew - זהו טקסט חלונות כאן®™ €", this.recentSearchList.item(6).get('text'));
            Y.Assert.areSame("arabic | هذا هو ويندوز النص هنا", this.recentSearchList.item(7).get('text'));
            Y.Assert.areSame("french ~` Ceci est un texte de fenêtres ici", this.recentSearchList.item(8).get('text'));
            Y.Assert.areSame("english<b>test</b>windows", this.recentSearchList.item(9).get('text'));
        }
    }));

    return suite;
}).run();