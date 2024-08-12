UnitTest.addSuite({
    type: UnitTest.Type.Widget,
    instanceID: 'OkcsSmartAssistant_0'
}, function(Y, widget, baseSelector){
    var smartAssistantTests = new Y.Test.Suite({
        name: "standard/okcs/OkcsSmartAssistant - truncate result title functionality"
    });
    smartAssistantTests.add(new Y.Test.Case({
        name: 'Test truncate result title',

        setUp: function() {
            this._origMakeRequest = RightNow.Ajax.makeRequest;
            RightNow.Ajax.makeRequest = Y.bind(function() {
                this.makeRequestCalledWith = Array.prototype.slice.call(arguments);
            }, this);
        },

        tearDown: function() {
            RightNow.Ajax.makeRequest = this._origMakeRequest;
            this._origMakeRequest = null;
            this.makeRequestCalledWith = null;
        },

        suggestionResponse: {
            sessionParm: "",
            status: 1,
            sa: {
                canEscalate: true,
                token: '7',
                suggestions:[{
                    type: 'AnswerSummary',
                    list: [{
                        ID: 1,
                        title: "Test",
                        href: "http://test.com"
                    },
                    {
                        ID: 2,
                        title: "banana",
                        href: "http://banana.com"
                    },
                    {
                        ID: 3,
                        title: "apple",
                        href: "http://apple.com"
                    }]
                }]
            }
        },

        okcsAnswerResponse: {"error":null,"id":"0","contents":{"title":"Test Node1 Test Nod2 Test Node3 Test Document1 test  ","docID":"TE10","answerID":0,"version":"1.0","published":true,"publishedDate":"08\/29\/2014","content":{"0":{"name":"Title","value":"Test Document1","xPath":"TEST_CHANNEL\/TITLE"},"1":{"name":"Title","value":"Test Node1","xPath":"TEST_CHANNEL\/NODE1\/TITLE"},"2":{"name":"checkbox","value":"Y","type":"CHECKBOX","xPath":"TEST_CHANNEL\/CHECKBOX"},"3":{"name":"counter","value":"9","xPath":"TEST_CHANNEL\/COUNTER"},"4":{"name":"Date","value":"08\/28\/2014","xPath":"TEST_CHANNEL\/DATE"},"5":{"name":"DTTM","value":"08\/29\/2014 17:00:00","xPath":"TEST_CHANNEL\/DTTM"},"7":{"name":"Float","value":"12.25","xPath":"TEST_CHANNEL\/FLOAT"},"8":{"name":"Integer","value":"1234","xPath":"TEST_CHANNEL\/INTEGER"},"9":{"name":"RTA","value":"<p>test<\/p>","xPath":"TEST_CHANNEL\/RTA"},"10":{"name":"TA","value":"test","xPath":"TEST_CHANNEL\/TA"},"11":{"name":"TF","value":"test","xPath":"TEST_CHANNEL\/TF"},"12":{"name":"List","value":"Item 1","type":"LIST","xPath":"TEST_CHANNEL\/LIST"}},"metaContent":[{"name":"TF_Meta","value":"","xPath":"META\/TF"},{"name":"TA_Meta","value":"","xPath":"META\/TA"},{"name":"Title","value":"","xPath":"META\/TITLE"}],"contentType":{"recordID":"082020202843a8b50147a0ad1c9a007fbb","referenceKey":"TEST_CHANNEL","name":"Test_channel"},"resourcePath":"http:\/\/slc01fjo.us.oracle.com:8228\/fas\/resources\/14112\/content\/draft\/08202020e3271dd0147ac1c3c1306b2b\/08202020e3271dd0147ac1c3c1306b1a\/","locale":"en_US","error":null}},

        verifyPromptContents: function(prompt) {
            Y.Assert.isTrue(prompt.hasClass('rn_Prompt'), "prompt is messed up!");
            Y.Assert.areSame('DIV', prompt.get('tagName'), "prompt is messed up!");
            Y.Assert.areSame(3, prompt.get('childNodes').size());
            Y.Assert.areSame(0, prompt.get('firstChild').get('textContent').indexOf(widget.data.attrs.label_prompt));
        },

        verifyListContents: function (list) {
            Y.Assert.isTrue(list.hasClass('rn_List'), "answer list is messed up!");
            Y.Assert.isTrue(list.hasClass('rn_InlineAnswers'), "answer list is messed up!");
            Y.Assert.areSame('UL', list.get('tagName'), "answer list is messed up!");

            var answerLinks = list.all('a');
            Y.Assert.areSame(this.suggestionResponse.sa.suggestions[0].list.length, answerLinks.size());

            answerLinks.each(function (item, index) {
                Y.Assert.isFalse(item.get('textContent').length > widget.data.attrs.truncate_size + 3); // add 3 to truncate size for ellipsis '...'
                Y.Assert.areSame('rn_InlineAnswerLink rn_ExpandAnswer', item.get('className'));
                Y.Assert.areEqual(index + 1, item.getAttribute('accesskey'));
                Y.Assert.areSame('rn_' + widget.instanceID + '_Answer' + (index), item.get('id'));
            });
        },

        "The result titles are properly truncated": function() {
            widget._displayResults("response", [ { data: { result: this.suggestionResponse } } ]);

            Y.Assert.areSame(this.suggestionResponse.sa.token, RightNow.UI.Form.smartAssistantToken);

            var dialogContent = Y.one(baseSelector + "_DialogContent"),
                contents = dialogContent.get("children");

            this.verifyPromptContents(contents.item(0));
            this.verifyListContents(contents.item(1));
        }
    }));
    return smartAssistantTests;
}).run();
