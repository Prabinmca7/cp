UnitTest.addSuite({
    type: UnitTest.Type.Widget,
    instanceID: 'SmartAssistantDialog_0'
}, function(Y, widget, baseSelector){
    var smartAssistantTests = new Y.Test.Suite({
        name: "standard/input/SmartAssistantDialog - inline answer functionality"
    });
    smartAssistantTests.add(new Y.Test.Case({
        name: 'Test inline answer content',

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
            sessionParam: "",
            status: 1,
            sa: {
                canEscalate: true,
                token: '7',
                suggestions:[{
                    type: 'AnswerSummary',
                    list: [{
                        ID: 1,
                        title: "Enabling MMS on iPhone 3G and iPhone 3GS"
                    },
                    {
                        ID: 2,
                        title: "banana"
                    },
                    {
                        ID: 3,
                        title: "apple"
                    }]
                }]
            }
        },

        answerResponse: {
            ID: 1,
            Question: "ivory coast",
            Solution: "Doldrums"
        },

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
                Y.Assert.areSame('rn_InlineAnswerLink rn_ExpandAnswer', item.get('className'));
                Y.Assert.areEqual(index + 1, item.getAttribute('accesskey'));
                Y.Assert.areSame('rn_' + widget.instanceID + '_Answer' + (index + 1), item.get('id'));
            });
        },

        verifyInjectedAnswerContents: function (container) {
            var children = container.get('children');

            Y.assert(container.hasClass('rn_ExpandedAnswerContent'));

            Y.Assert.areSame(2, children.size());

            var firstChild = children.item(0);
            Y.assert(firstChild.hasClass('rn_AnswerSummary'));
            Y.Assert.areSame(this.answerResponse.Question, firstChild.get('textContent'));

            var secondChild = children.item(1);
            Y.assert(secondChild.hasClass('rn_AnswerSolution'));
            Y.Assert.areSame(this.answerResponse.Solution, secondChild.get('textContent'));
        },

        "The dialog's inline answer content is properly constructed": function() {
            widget._displayResults("response", [ { data: { result: this.suggestionResponse } } ]);

            Y.Assert.areSame(this.suggestionResponse.sa.token, RightNow.UI.Form.smartAssistantToken);

            var dialogContent = Y.one(baseSelector + "_DialogContent"),
                contents = dialogContent.get("children");

            this.verifyPromptContents(contents.item(0));
            this.verifyListContents(contents.item(1));
        },

        "Clicking on an inline answer link fires a global request event before making an ajax request": function() {
            var dialogContent = Y.one(baseSelector + "_DialogContent"),
                clickedAnswer = dialogContent.one('a:first-child'),
                hasFiredRequest = false;

            RightNow.Event.subscribe("evt_getAnswerRequest", function(type, args) {
                hasFiredRequest = true;
                args = args[0];
                Y.assert(!this.makeRequestCalledWith);
                Y.Assert.areSame(args.w_id, widget.instanceID);
                Y.Assert.areSame(args.data.objectID, clickedAnswer.getAttribute('data-id'));
            }, this);


            clickedAnswer.simulate('click');
            Y.Assert.isTrue(hasFiredRequest, 'The request for inline content did not fire.');
            Y.Assert.areSame(widget.data.attrs.get_answer_content, this.makeRequestCalledWith[0]);
        },

        "Content from answer response is injected into the list after a global response event is fired": function() {
            var dialogContent = Y.one(baseSelector + "_DialogContent"),
                clickedAnswer = dialogContent.one('a:first-child'),
                hasFiredResponse = false;

            RightNow.Event.subscribe("evt_getAnswerResponse", function() {
                hasFiredResponse = true;
            }, this);

            widget._displayContent(this.answerResponse);

            Y.Assert.isTrue(hasFiredResponse);
            Y.assert(clickedAnswer.hasClass('rn_ExpandedAnswer'));
            this.verifyInjectedAnswerContents(clickedAnswer.next());
        },

        "Hide button should not be available when display view type is 'inline'": function() {
            var toggle = Y.one('a.rn_ExpandedAnswer'),
                hideButtonContainer = Y.one('.rn_HideButtonContainer');

            if (toggle)
                Y.Assert.isNull(hideButtonContainer);
        },

        "Clicking an expanded answer toggle hides the answer content": function() {
            var toggle = Y.one('a.rn_ExpandedAnswer');
            toggle.simulate('click');

            Y.assert(!toggle.hasClass('rn_ExpandedAnswer'));
        },

        "File attachment type answers display a link to the attachment": function() {
            var fileAttachementID = 456,
                sessionParameter = '/session/abc123',
                expected = 'href="/ci/fattach/get/' + fileAttachementID,
                response = widget._getContents(123, {FileAttachments: [{ID: fileAttachementID}]});

            Y.Assert.isTrue(response.indexOf(expected + '"') > 0);

            // session parameter should be added to the link url
            widget.sessionParameter = sessionParameter;
            response = widget._getContents(123, {FileAttachments: [{ID: fileAttachementID}]});
            Y.Assert.isTrue(response.indexOf(expected + sessionParameter) > 0, 'session parameter not added to URL');
            widget.sessionParameter = '';
        },

        "Multiple file attachments are concatenated": function() {
            var attachments = [
                    {ID: 456, FileName: 'spongebob'},
                    {ID: 457, FileName: 'batman'},
                    {ID: 458, FileName: 'hiphopopotamus'},
                    {ID: 459, FileName: '(╯°□°）╯︵ ┻━┻'},
                    {ID: 460},
                ],
                expectedLink = 'href="/ci/fattach/get/',
                expectedText,
                response = widget._getContents(123, {FileAttachments: attachments});

            for(var i = 0; i < attachments.length - 1; i++) {
                Y.Assert.isTrue(response.indexOf(attachments[i].ID) > 0);
                Y.Assert.isTrue(response.indexOf(attachments[i].FileName) > 0);
            }

            // Do a separate check for the last attachment, since it uses the generic attachment label,
            // and since we're using indexOf, we don't want to just pass all of the checks inadvertently
            Y.Assert.isTrue(response.indexOf(attachments[attachments.length - 1].ID) > 0);
            Y.Assert.isTrue(response.indexOf(widget.data.attrs.label_download_attachment) > 0);
        },

        "URL attachment type answers display a link to the URL": function() {
            var url = 'http://google.com',
                expected = 'href="' + url,
                sessionParameter = '/session/abc123',
                response = widget._getContents(123, {URL: url});

            Y.Assert.isTrue(response.indexOf(expected + '"') > 0);

            // session parameter should _not_ be added to the link url
            widget.sessionParameter = sessionParameter;
            response = widget._getContents(123, {URL: url});
            Y.Assert.isTrue(response.indexOf(expected + '"') > 0, 'session parameter was added to URL');
            widget.sessionParameter = '';
        },

        "Answers with guided assistance display a link to the answer": function() {
            var answerID = 123,
                expected = 'href="/app/answers/detail/a_id/' + answerID,
                sessionParameter = '/session/abc123',
                response = widget._getContents(answerID, {GuidedAssistance: {ID: 456}});

            Y.Assert.isTrue(response.indexOf(expected + '"') > 0);

            // session parameter should be added to the link url
            widget.sessionParameter = sessionParameter;
            response = widget._getContents(answerID, {GuidedAssistance: {ID: 456}});
            Y.Assert.isTrue(response.indexOf(expected + sessionParameter) > 0, 'session parameter not added to URL');
            widget.sessionParameter = '';
        },

        "HTML type answers display the answer solution": function() {
            var expected = 'Here be content',
                response = widget._getContents(123, {Solution: expected});
            Y.Assert.areSame(expected, response);
        },

        "Best answer comment displays comment's author": function() {
            var dialogContent = Y.one(baseSelector + "_DialogContent");

            var suggestionResponse = {
                sessionParam: "",
                status: 1,
                sa: {
                    canEscalate: true,
                    token: '7',
                    suggestions:[{
                        type: 'QuestionSummary',
                        list: [{
                            ID: 82,
                            title: "CommunityQuestion"
                        }]
                    }]
                }
            };
            widget._displayResults("response", [ { data: { result: suggestionResponse } } ]);
            var clickedAnswer = dialogContent.one('a:first-child');
            clickedAnswer.simulate('click');

            var socialQuestionResponse = {
                "ID":82,
                "BestCommunityQuestionAnswer":{
                    "0":{
                        "CommunityComment":{
                            "Body":"SocialQuestionCommentBody",
                            "CreatedByCommunityUser":{
                                "AvatarURL":"http://invalid.com/SocialQuestionCommentAvatar",
                                "DisplayName":"Alexis",
                                "StatusWithType": {"Status":{"ID":38}}
                            }
                        }
                    }
                },
                "Body":"SocialQuestionBody",
                "CreatedByCommunityUser":{
                    "AvatarURL":"http://invalid.com/SocialQuestionAvatar",
                    "DisplayName":"Duncan89",
                    "StatusWithType": {"Status":{"ID":38}}
                },
                "Subject":"SocialQuestionSubject"
            };
            widget._displayContent(socialQuestionResponse);

            Y.Assert.areSame('http://invalid.com/SocialQuestionAvatar', dialogContent.one('.rn_SocialQuestionAuthor img').get('src'));
        },

        "Blank list is not displayed when there isn't a best answer": function() {
            var dialogContent = Y.one(baseSelector + "_DialogContent");

            var suggestionResponse = {
                sessionParam: "",
                status: 1,
                sa: {
                    canEscalate: true,
                    token: '7',
                    suggestions:[{
                        type: 'QuestionSummary',
                        list: [{
                            ID: 82,
                            title: "CommunityQuestion"
                        }]
                    }]
                }
            };
            widget._displayResults("response", [ { data: { result: suggestionResponse } } ]);
            var clickedAnswer = dialogContent.one('a:first-child');
            clickedAnswer.simulate('click');

            var socialQuestionResponse = {
                "ID":82,
                "BestCommunityQuestionAnswers":{
                    "0":{
                        "CommunityComment":{
                            "Body": null,
                            "CreatedByCommunityUser":{
                                "AvatarURL":"http://invalid.com/SocialQuestionCommentAvatar",
                                "DisplayName":"Alexis",
                                "StatusWithType": {"Status":{"ID":38}}
                            }
                        }
                    }
                },
                "Body":"SocialQuestionBody",
                "CreatedByCommunityUser":{
                    "AvatarURL":"http://invalid.com/SocialQuestionAvatar",
                    "DisplayName":"Duncan89",
                    "StatusWithType": {"Status":{"ID":38}}
                },
                "Subject":"SocialQuestionSubject"
            };
            widget._displayContent(socialQuestionResponse);

            Y.Assert.areSame(widget._bestAnswerExists(socialQuestionResponse.BestCommunityQuestionAnswers), false);
        },

        "Changing Anchor tags in an answer for use in smartassistant": function() {
            var expected1 = Y.Node.create('<div><a style="TEXT-DECORATION: underline" href="' + window.location.pathname + '#EMEA">Europe, Middle East and Africa (EMEA)</a></div>'),
                answerWrapper1 = Y.Node.create('<div><a style="TEXT-DECORATION: underline" href="#EMEA">Europe, Middle East and Africa (EMEA)</a></div>'),
                expected2 = Y.Node.create('<div><a style="TEXT-DECORATION: underline" href="http://testsite.us.oracle.com/answerTest.html#EMEA" target="_blank">Europe, Middle East and Africa (EMEA)</a></div>'),
                answerWrapper2 = Y.Node.create('<div><a style="TEXT-DECORATION: underline" href="http://testsite.us.oracle.com/answerTest.html#EMEA">Europe, Middle East and Africa (EMEA)</a></div>');

            widget._modifyAnchorLinks(answerWrapper1);
            Y.Assert.areSame(expected1.one('a').get("href"), answerWrapper1.one('a').get("href"));
            // Make sure that the links don't get the target = _blank so a new window will not open.
            Y.Assert.areSame(expected1.one('a').get("target"), answerWrapper1.one('a').get("target"));
            // Make sure that a full anchor doesn't get changed.
            widget._modifyAnchorLinks(answerWrapper2);
            Y.Assert.areSame(expected2.one('a').get("href"), answerWrapper2.one('a').get("href"));
            // Make sure that full links get the target = _blank so a new window will open.
            Y.Assert.areSame(expected2.one('a').get("target"), answerWrapper2.one('a').get("target"));
        }
    }));
    return smartAssistantTests;
}).run();
