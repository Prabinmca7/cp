RightNow.Widgets.GuidedAssistant = RightNow.Widgets.extend({
    constructor: function() {
        if (this.data.attrs.popup_window_url) return;

        this._currentLevel = 1;
        this._guide = [];
        this._map = RightNow.Url.convertToArray(RightNow.Url.getParameterSegment());
        this._currentGuideID = this.data.js.guidedAssistant.guideID;
        this._originalGuideID = this._currentGuideID;
        this._guide[this._currentGuideID] = this.data.js.guidedAssistant;
        this._currentQuestion = this._guide[this._currentGuideID].questions[0].questionID;
        this._previousQuestions = [];
        this._eo = new RightNow.Event.EventObject(this, {data: {guideID: this._currentGuideID}});
        delete this.data.js.guidedAssistant;
        this._hasRecordedInitialInteraction = false;

        this.env = this._getEnvironment();

        var RightNowEvent = RightNow.Event;
        RightNowEvent.subscribe("evt_GuidedAssistanceGoToResponse", this._goToResponse, this);
        RightNowEvent.subscribe("evt_GuidedAssistanceGoToQuestion", this._goToQuestion, this);
        RightNowEvent.subscribe("evt_GuidedAssistanceAnswerViewed", this._answerViewed, this);
        RightNow.Widgets.formTokenRegistration(this);
        this._liveNotificationArea = this.Y.Node.create("<div class='rn_ScreenReaderOnly' role='alert'></div>");
        new this.Y.Node(document.body).insert(this._liveNotificationArea, 0);

        this._attachDomEvents();

        this._initHistoryManager();

        //notify listeners that a guide has been loaded
        RightNowEvent.fire("evt_GuideLoaded", this._eo);
    },

    /**
    * Sets up all DOM event listeners.
    */
    _attachDomEvents: function() {
        this.Y.Event.attach("click", this._goBackHelper, this.baseSelector + "_BackButton", this);
        var widget = this.Y.one(this.baseSelector);
        widget.delegate("click", this._onClick, "input, button, a", this);
        // dropdowns require additional accessibility care
        widget.delegate("change", this._onSelectChange, "select", this);
        widget.delegate("keydown", function(e) {
            if ((e.keyCode === RightNow.UI.KeyMap.TAB && this._changeFired) || e.keyCode === RightNow.UI.KeyMap.ENTER) {
                this._onClick(e);
            }
        }, "select", this);
        widget.delegate("mousedown", function(e) {
            this._mouseTriggered = true;
        }, "select", this);

        if (this.Y.UA.ie && this.Y.UA.ie < 9) {
            // [YUI event delegate doesn't work for select change in old IE](http://bit.ly/J5pJwO)
            // TK remove this condition when YUI fixes the bug
            RightNow.Event.on('evt_GuideQuestionRendered', function(evt, args) {
                args = args[0];
                var question = args.data.question;
                if (args.w_id === this.instanceID && (question.type === this.data.js.types.MENU_QUESTION || question.type === this.data.js.types.LIST_QUESTION)) {
                    this.Y.one(this.baseSelector + '_Response' + args.data.guideID + '_' + question.questionID).one('select').on('change', this._onSelectChange, this);
                }
            }, this);
            // The GuideQuestionRendered event isn't fired for the server-rendered question.
            // If the first question is a list or menu, attach a change listener to it.
            var initialSelect = this.Y.one(this.baseSelector + ' select');
            if (initialSelect) {
                initialSelect.on('change', this._onSelectChange, this);
            }
        }
    },

    /**
     * Event handler for select elements' change DOM event.
     * @param {Object} e EventFacade object
     */
    _onSelectChange: function(e) {
        this._changeFired = true;
        if (this._mouseTriggered || this.Y.UA.ie && e.target.get("size") === "0") {
            this._mouseTriggered = false;
            this._onClick(e);
        }
    },

    /**
    * Click handler for all actionable elements in the widget.
    * @param {Object} evt Click event
    */
    _onClick: function(evt) {
        var target = evt.target,
            tagName = target.get('tagName').toLowerCase(),
            id = target.get('id');

        if (((tagName === 'a' || tagName === 'button') && typeof target.get('onclick') === 'function') ||
            (tagName === 'input' && target.get('type') === 'text') ||
            id.indexOf('BackButton') > 0 || id.indexOf('RestartButton') > 0) {
            // Check if the user is clicking on an anchor tag that has an onclick handler associated with it (this
            // handles link-type responses which manually invoke the click event on the radio input element) OR
            // button with an onclick handler (popup-image button within the response markup) OR
            // input[type='text'], where the submit button for text input questions handles the onclick event OR
            // is the restart or back button.
            return;
        }

        var questionID = parseInt(target.getAttribute("data-question"), 10),
            responseID = parseInt(target.getAttribute("data-response"), 10),
            guideID = parseInt(target.getAttribute("data-guide"), 10),
            answerID = parseInt(target.getAttribute("data-answer"), 10),
            level = parseInt(target.getAttribute("data-level"), 10);

        if (guideID && tagName === 'a') {
            this._linkClicked(evt, {
                guideID: guideID,
                questionID: questionID,
                responseID: responseID,
                answerID: answerID
            });
            return;
        }
        if (guideID)
            this.answerQuestion(target, guideID, questionID, responseID, level);
    },
/********************************
 User-traversal Functions
********************************/
    /**
    * Executed when user responds to a question.
    * @param {Object} element Clicked element
    * @param {Number} guideID ID of the guide the question is in
    * @param {Number} questionID ID of the question
    * @param {Number} responseID ID of the response
    * @param {Number} level level of the question in the guide
    * @param {Boolean=} skipped indicates if this question was skipped over (optional)
    */
    answerQuestion: function(element, guideID, questionID, responseID, level, skipped) {
       if(guideID !== this._currentGuideID) {
            if(!this._guide[guideID])
                throw new Error("Missing guide");
            else
                this._currentGuideID = guideID;
        }
        if(!this._hasRecordedInitialInteraction){
            RightNow.ActionCapture.record('guidedAssistance', 'interact', this._currentGuideID);
            this._hasRecordedInitialInteraction = true;
        }
        this._setAriaLoading(true);

        var question = this._getQuestionByID(questionID),
            response = this._getResponseByID(question, responseID);
        if(question) {
            this._currentLevel = level + 1;
            this._previousQuestions[level] = questionID;

            //make sure there are no questions below this one
            this._removeElements(questionID);
            this._removePairs(questionID);

            if(question.type === this.data.js.types.MENU_QUESTION || question.type === this.data.js.types.LIST_QUESTION) {
                if(element.get("options").item(element.get("selectedIndex")).get("value")) {
                    responseID = parseInt(element.get("options").item(element.get("selectedIndex")).get("value"), 10);
                    response = this._getResponseByID(question, responseID);
                }
                else {
                    this._previousQuestions.pop();
                    this._currentLevel--;
                    this._setAriaLoading(false);
                    return;
                }
            }
            else if(question.type === this.data.js.types.TEXT_QUESTION) {
                //grab the user-entered text and continue onward
                var input = this.Y.one(this.baseSelector + "_Response" + guideID + "_" + questionID + "_" + responseID);
                if(input) {
                    input.set("value", this.Y.Lang.trim(input.get("value")));
                    if(input.get("value") === "") {
                        input.focus();
                        this._setAriaLoading(false);
                        this._currentLevel--;
                        this._previousQuestions.pop();
                        return;
                    }
                    response.value = input.get("value");
                }
                else {
                    this._setAriaLoading(false);
                    return;
                }
            }
            this._addPairs(question, response.value, level);
            this._submitStats(RightNow.Ajax.CT.GA_SESSION_DETAILS, {
                ga_sid: this._guide[guideID].guideSessionID,
                ga_id: guideID,
                q_id: questionID,
                r_id: responseID,
                skipped: skipped
            }, true);

            //notify listeners of selection
            this._eo.data = {guideID: guideID, questionID: questionID, responseID: responseID, responseValue: response.value};
            RightNow.Event.fire("evt_GuideResponseSelected", this._eo);

            if(this.data.attrs.single_question_display) {
                //reset the user's response in case they go back
                if(question.type === this.data.js.types.RADIO_QUESTION || question.type === this.data.js.types.IMAGE_QUESTION)
                    element.set("checked", false);
                else if(question.type === this.data.js.types.MENU_QUESTION || question.type === this.data.js.types.LIST_QUESTION)
                    element.set("selectedIndex", 0);
                else if(question.type === this.data.js.types.TEXT_QUESTION)
                    element.set("value", "");
                if(this._guideAppended) {
                    this._hideFirstGuideQuestion(questionID);
                    this._guideAppended = false;
                }
                else {
                    this._toggleQuestion(question);
                }
                this._toggleBackButton(true);
            }
            else {
                if (question.type === this.data.js.types.LINK_QUESTION || question.type === this.data.js.types.BUTTON_QUESTION) {
                    //add appropriate visual cues as to what's been selected
                    this._highlightResponse(element, question.type);
                }
                else if (element && element.get("type") === "radio" && element.get("checked") === false) {
                    //manually check checkbox-type questions if they haven't been (agent navigation)
                    element.set("checked", true);
                }
            }

            this._buildNextResult(response);
            this._changeFired = false;
            this._setAriaLoading(false);
        }
    },

/********************************
 Intended for designer use only.
 Called via COM Interop & WebBrowser WPF control
********************************/
    /**
    * Navigates to the specified question's result by mocking
    * user-interaction on the guide.
    * @param {String} evt Event name
    * @param {Array} args Event object
    * @throws Error if the specified guide doesn't exist
    * @return {Number|null} the level of the question that the response is for
    */
    _goToResponse: function(evt, args) {
        var levelOfQuestion = this._goToQuestion(evt, args);
        if (typeof levelOfQuestion === 'undefined') {
            return;
        }
        var data = args[0].data,
            guideID = data.guideID,
            questionID = data.questionID,
            responseID = data.responseID,
            goToResponse = function(level) {
                var question = this._getQuestionByID(questionID);
                if(question && question.responses) {
                    for(var i = 0, response; i < question.responses.length; i++) {
                        response = question.responses[i];
                        if(response.responseID === responseID && !(this._restoringState && response.url)) {
                            this._answerQuestion(guideID, question, response, level);
                        }
                    }
                }
            };
        if(levelOfQuestion.guideID) {
            //a sub-guide needs to be asynchronously loaded...
            var levelUpToSubGuide = this._goToResponse(this.instanceID, [{data: levelOfQuestion}]),
                restoringState = this._restoringState;
            //callback to continue onward once the subguide has been loaded
            this._guideLoadedCallback = function(idOfLoadedGuide) {
                if(guideID === idOfLoadedGuide) {
                    //go to the requested question
                    this._guideLoadedCallback = null;
                    this._currentGuideID = guideID;
                    this._restoringState = restoringState;
                    levelOfQuestion = this._goToQuestion(this.instanceID, [{data: {guideID: guideID, questionID: questionID, level: levelUpToSubGuide}}]);
                    //then respond to it
                    goToResponse.call(this, levelOfQuestion + levelUpToSubGuide);
                    this._restoringState = false;
                }
            };
        }
        else {
            goToResponse.call(this, levelOfQuestion);
            return levelOfQuestion;
        }
    },

    /**
    * Navigates to the specified question by mocking
    * user-interaction on the guide.
    * @param {String} evt Event name
    * @param {Array} args Event object
    * @throws Error if the specified guide doesn't exist
    * @return {Number|Object} Either Int the level of the question that is navigated to
    *           Or Object A node that must be navigated thru in order to load a sub-guide.
    */
    _goToQuestion: function(evt, args) {
        var data = args[0].data,
            guideID = data.guideID,
            questionID = data.questionID,
            startingLevel = (data.level || 0) + 1,
            i, response, j;

        if(this._guide[guideID] && this._guide[guideID].questions[0] && (guideID === this._currentGuideID || !this._guide[guideID].parentGuide)) {
            //if the guide is loaded, has questions, is the guide we're currently in or the root guide...
            this._currentGuideID = guideID;
            //get the ordered path of questions
            var nodeList = this._getPathToQuestion([this._guide[guideID].questions[0]], questionID);
            if(nodeList && nodeList.length) {
                if(nodeList.length === 1) {
                    //going back up to root question
                    this._removeElements(nodeList[0].questionID);
                    if(this.data.attrs.single_question_display) {
                        this._toggleQuestion(nodeList[0], true);
                        this._toggleBackButton(false);
                    }
                }
                else {
                    //mock user's responses with response ids...
                    for(i = 0; i <= nodeList.length - 2; i++) {
                        for(j in nodeList[i].responses) {
                            if(nodeList[i].responses.hasOwnProperty(j)) {
                                response = nodeList[i].responses[j];
                                if(response.childQuestionID === nodeList[i + 1].questionID) {
                                    this._answerQuestion(guideID, nodeList[i], response, i + startingLevel);
                                }
                            }
                        }
                    }
                }
                return nodeList.length;
            }
        }
        else {
            //search thru ea. loaded guide to find a response linking to the requested guide
            var parentGuide;
            for(i in this._guide) {
                if(this._guide.hasOwnProperty(i)) {
                    response = this._getGuideParentResponse(this._guide[i].questions, guideID);
                    if(response) {
                        return {guideID: this._guide[i].guideID, questionID: response.questionID, responseID: response.responseID};
                    }
                }
            }
        }
    },

    /**
    * Completes the mock user-interaction by triggering the public answerQuestion function.
    * Provides special setup required to mock dropdown and list menu interaction.
    * @param {Number} guideID Guide id of the question
    * @param {Object} question Internal representation of question object
    * @param {Object} response Internal representation of response object
    * @param {Number} level The level of the question.
    */
    _answerQuestion: function(guideID, question, response, level) {
        var element;
        if (question.type === this.data.js.types.MENU_QUESTION || question.type === this.data.js.types.LIST_QUESTION) {
            var parent = this.Y.one(this.baseSelector + "_Response" + guideID + "_" + question.questionID);
            element = (parent) ? parent.one('select') : null;
            if (element) {
                element.get("options").some(function(option, i) {
                    if (option.get("value") === (response.responseID + "")) {
                        element.set("selectedIndex", i);
                        return true;
                    }
                });
            }
        }
        else {
            this.Y.one(this.baseSelector + "_Response" + guideID + "_" + response.parentQuestionID).all("input, button").some(function(node) {
                if (node.getAttribute("data-response") === (response.responseID + "")) {
                    element = node;
                    return true;
                }
            });
            if (element && question.type === this.data.js.types.TEXT_QUESTION) {
                element.set("value", RightNow.Interface.getMessage("RESPONSE_PLACEHOLDER_LBL"));
            }
        }
        this.answerQuestion(element, guideID, response.parentQuestionID, response.responseID, level, true);
    },
/********************************
 HTML-Building Functions
********************************/
    /**
    * Constructs the next result node.
    * @param {Object} response Response object
    */
    _buildNextResult: function(response) {
        var result = this.Y.Node.create("<div class='rn_Result rn_Node'></div>"),
            deadEnd = true, question;

        if(!response || !response.type) {
            //tree was improperly saved w/o a response...
            result.set("innerHTML", "<div class='rn_ResultText'>" + RightNow.Interface.getMessage("NO_ANSWERS_FOUND_MSG") + "</div>");
        }
        else {
            this._currentResponse = response.responseID; 
            if (response.url) {
                //do a post or get to an external site. Endusers: buh-bye now. Agents: stay with us.
                //opening a new window after an AJAX request returns will be blocked by modern browsers,
                // so we need to call _callUrl separately (since the page is opened in a new window,
                // we know that the click tracking should be recorded)
                RightNow.ActionCapture.record('guidedAssistance', 'finish', this._currentGuideID);
                RightNow.ActionCapture.flush();
                if(response.urlType === this.data.js.types.URL_GET && this.env.samePage() && this.data.attrs.call_url_new_window) {
                    RightNow.Ajax.CT.commitActions();
                    this._callUrl(response);
                }
                else {
                    RightNow.Ajax.CT.commitActions(function(){
                        this._callUrl(response);
                    }, this);
                }
                if(this.env.preview) {
                    //agents don't submit stats so callUrl won't get called above, allow agent preview
                    this._callUrl(response);
                }
                if(!this.env.console || this.env.previewEnduser) {
                    return;
                }
            }
            if(response.type & this.data.js.types.TEXT_RESPONSE) {
                //Text explanation
                RightNow.ActionCapture.record('guidedAssistance', 'finish', this._currentGuideID);
                result.append(this._createTextResultHTML(response) +
                        this._createResolutionChatLink(this._currentGuideID, response.parentQuestionID, response.responseID))
                       .addClass("rn_Text")
                       .set("id", this.baseDomID + "_Result" + this._currentGuideID + "_" + response.responseID);
            }
            if(response.type & this.data.js.types.ANSWER_RESPONSE && (!this.env.console || this.env.previewEnduser)) {
                //Answers
                RightNow.ActionCapture.record('guidedAssistance', 'finish', this._currentGuideID);
                result.append(this._createAnswersHTML(response))
                      .addClass("rn_Answers")
                      .set("id", this.baseDomID + "_Result" + this._currentGuideID + "_" + response.responseID);
            }
            if(response.type & this.data.js.types.QUESTION_RESPONSE) {
                //Question
                question = this._getQuestionByID(response.childQuestionID);
                result.append(this._buildQuestionHTML(question) +
                        this._createQuestionChatLink(this._currentGuideID, response.childQuestionID))
                      .addClass("rn_Question")
                      .set("id", this.baseDomID + "_Question" + this._currentGuideID + "_" + response.childQuestionID);
                this._currentQuestion = response.childQuestionID;

                //Log the question being rendered
                this._submitStats(RightNow.Ajax.CT.GA_SESSION_DETAILS, {
                    ga_sid: this._guide[this._currentGuideID].guideSessionID,
                    ga_id: this._currentGuideID,
                    q_id: this._currentQuestion
                }, true);

                deadEnd = false;
            }
            if(response.type & this.data.js.types.GUIDE_RESPONSE && response.childGuideID) {
                //Another Guide
                if(result.get("innerHTML")) {
                    this._appendElement(result.removeClass("rn_Node"));
                }
                RightNow.Ajax.CT.commitActions();
                this._getGuideByID(response.childGuideID, response.responseID, this._currentQuestion);
                return;
            }
            this._saveState({
                guideID: this._currentGuideID,
                questionID: response.parentQuestionID,
                responseID: response.responseID,
                guideSession: this._guide[this._currentGuideID].guideSessionID,
                sessionID: this.data.js.session
            });
        }
        //Log response and (possibly) newly-rendered question
        RightNow.Ajax.CT.commitActions();
        this._appendElement(result);
        RightNow.Url.transformLinks(result);

        if(this.data.attrs.single_question_display && deadEnd && this._currentLevel > 2) {
            this._displayRestartButton();
        }

        if (question) {
            RightNow.Event.fire('evt_GuideQuestionRendered', new RightNow.Event.EventObject(this, {
                data: { question: question, guideID: this._currentGuideID }
            }));
        }

        this._newContentAdded();
    },

    /**
    * Constructs the contents of the next question.
    * @param {Object} question Question object
    */
    _buildQuestionHTML: function(question) {
        var types = this.data.js.types,
            newQuestion, result, resultClass;

        switch (question.type) {
            case types.BUTTON_QUESTION:
                result = this._createButtonHTML(question);
                resultClass = "rn_ButtonQuestion";
                break;
            case types.MENU_QUESTION:
            case types.LIST_QUESTION:
                result = this._createMenuHTML(question);
                resultClass = (question.type === types.LIST_QUESTION) ? "rn_ListQuestion" : "rn_MenuQuestion";
                break;
            case types.LINK_QUESTION:
                result = this._createLinkHTML(question);
                resultClass = "rn_LinkQuestion";
                break;
            case types.RADIO_QUESTION:
                result = this._createRadioHTML(question);
                resultClass = "rn_RadioQuestion";
                break;
            case types.IMAGE_QUESTION:
                result = this._createImageHTML(question);
                resultClass = "rn_ImageQuestion";
                break;
            case types.TEXT_QUESTION:
                result = this._createTextInputHTML(question);
                resultClass = "rn_TextQuestion";
                break;
        }

        return new EJS({text: this.getStatic().templates.question}).render({
                responseContent: result,
                responseClass: resultClass,
                questionText: question.text,
                responseID: this.baseDomID + "_Response" + this._currentGuideID + "_" + question.questionID
            }) +
            ((this.env.console && !this.env.previewEnduser && question.agentText)
                ? "<pre class='rn_AgentText'><em>" + RightNow.Interface.getMessage("AGT_TEXT_LBL") + "</em>" + question.agentText + "</pre>"
                : ""
            );
    },

    /**
    * Creates the HTML for button response type.
    * @param {Object} question Question object
    * @return {String} the HTML string
    */
    _createButtonHTML: function(question) {
        return new EJS({text: this.getStatic().templates.buttonResponse}).render({
            guideID: this._currentGuideID,
            questionID: question.questionID,
            responses: question.responses,
            level: this._currentLevel
        });
    },

    /**
    * Creates the HTML for menu & list response types.
    * @param {Object} question Question object
    * @return {String} the HTML string
    */
    _createMenuHTML: function(question) {
        var questionID = question.questionID;
        return new EJS({text: this.getStatic().templates.menuResponse}).render({
            guideID: this._currentGuideID,
            questionID: questionID,
            accessibleText: question.taglessText,
            responses: question.responses,
            size: (question.type === this.data.js.types.LIST_QUESTION) ? (question.responses.length + 1) : 0,
            level: this._currentLevel
        });
    },

    /**
    * Creates the HTML for link response type.
    * @param {Object} question Question object
    * @return {String} the HTML string
    */
    _createLinkHTML: function(question) {
        var questionID = question.questionID;
        return new EJS({text: this.getStatic().templates.linkResponse}).render({
            id: this._buildResponseID(questionID),
            guideID: this._currentGuideID,
            questionID: questionID,
            accessibleText: question.taglessText,
            responses: question.responses,
            className: (this.env.mobile) ? "rn_TransparentScreenReaderOnly" : "rn_ScreenReaderOnly",
            level: this._currentLevel
        });
    },

    /**
    * Creates the HTML for radio response type.
    * @param {Object} question Question object
    * @return {String} the HTML string
    */
    _createRadioHTML: function(question) {
        var questionID = question.questionID;
        return new EJS({text: this.getStatic().templates.radioResponse}).render({
            id: this._buildResponseID(questionID),
            guideID: this._currentGuideID,
            questionID: questionID,
            accessibleText: question.taglessText,
            responses: question.responses,
            level: this._currentLevel
        });
    },

    /**
    * Creates the HTML for image response type.
    * @param {Object} question Question object
    * @return {String} the HTML string
    */
    _createImageHTML: function(question) {
        var questionID = question.questionID;
        return new EJS({text: this.getStatic().templates.imageResponse}).render({
            id: this._buildResponseID(questionID),
            guideID: this._currentGuideID,
            questionID: questionID,
            accessibleText: question.taglessText,
            responses: question.responses,
            level: this._currentLevel
        });
    },

    /**
    * Creates the HTML for text response type.
    * @param {Object} question Question object
    * @return {String} the HTML string
    */
    _createTextInputHTML: function(question) {
        this.Y.augment(this, RightNow.RequiredLabel);
        var response = question.responses[0],
            questionID = question.questionID;
        return new EJS({text: this.getStatic().templates.textResponse}).render({
            id: this._buildResponseID(questionID, response.responseID),
            guideID: this._currentGuideID,
            questionID: questionID,
            responseID: response.responseID,
            responseText: response.text,
            label: this.data.attrs.label_text_response_button,
            level: this._currentLevel
        });
    },

    /**
    * Performs URL result action (post/get to an external site).
    * @param {Object} response Response object
    */
    _callUrl: function(response) {
        this._map.session = this.data.js.session;

        if(response.urlType === this.data.js.types.URL_GET) {
            this._navigate(response.url, this._map);
        }
        else if(response.urlType === this.data.js.types.URL_POST) {
            this._post(response.url, this._map);
        }
    },

    /**
     * Navigates the page (or the opener page)
     * with the given querystring parameters.
     * @param {String} url Base url
     * @param {Object} paramMap hash of keys and values
     * @param {Object=} win window object
     */
    _navigate: function(url, paramMap, win) {
        win || (win = window);

        var params = "",
            separator = (url.indexOf("?") > -1) ? "&" : "?";

        this.Y.Object.each(paramMap, function(pair, key) {
            params += (separator + encodeURIComponent(key) + "=" + encodeURIComponent(pair.value || pair));
            if(separator === "?")
                separator = "&";
        });

        url += params;

        if(!this.env.samePage() && (!this.Y.UA.ie || this.Y.UA.ie > 10)) {
            win.self.opener.location = url;
            win.close();
        }
        else if((this.env.console && !this.env.previewEnduser) ||
                this.data.attrs.call_url_new_window){
            win.open(url);
        }
        else {
            win.location = url;
        }
    },

    /**
     * Performs a post via a form submission.
     * @param {String} url Base url
     * @param {Object} paramMap hash of keys and values
     * @param {Object=} win window object
     */
    _post: function(url, paramMap, win) {
        win || (win = window);

        var form = this.Y.Node.create("<form class='rn_Hidden' method='post'></form>")
                            .set("action", url);

        if(this.env.console && !this.env.previewEnduser) {
            form.set("target", "_blank");
        }
        this.Y.Object.each(paramMap, function(pair, name) {
            form.append(this.Y.Node.create("<input type='text'>")
                .set("name", name)
                .set("value", pair.value || pair)
            );
        }, this);

        if(!this.env.samePage() && !this.Y.UA.ie && !this.env.mobile) {
            // IE and some mobile browsers don't allow modification of the opener's DOM
            form = this.Y.Node.getDOMNode(form);
            form = win.self.opener.document.body.appendChild(form);
            win.close();
        }
        else {
            this._appendElement(form, this._currentGuideID);
        }
        form.submit();
    },

    /**
    * Creates the HTML for text results.
    * @param {Object} response Response object
    * @return {String} the HTML string
    */
    _createTextResultHTML: function(response) {
        return new EJS({text: this.getStatic().templates.textResult}).render({
            heading: this.data.attrs.label_text_result,
            resultText: response.responseText || ""
        });
    },

    /**
    * Creates the HTML for answer link results.
    * @param {Object} response Response object
    * @return {String} the HTML string
    */
    _createAnswersHTML: function(response) {
        var answers = "";
        if (response.childAnswers) {
            answers = new EJS({text: this.getStatic().templates.answerResult}).render({
                answers: response.childAnswers,
                guideID: this._currentGuideID,
                questionID: this._currentQuestion,
                responseID: response.responseID,
                target: this.data.attrs.target || ((this.env.samePage() || this.env.mobile) ? "_blank" : "") || "_blank",
                heading: this.data.attrs.label_answer_result
            });
        }
        return answers;
    },

    /**
    * Creates the HTML for a sub-guide and
    * inserts it into the parent guide.
    * @param {Object} guide Guide object
    * @param {Number} parentGuideID Parent guide's ID
    */
    _createNewGuide: function(guide, parentGuideID) {
        var question = guide.questions[0],
            questionID = question.questionID,
            result = this.Y.Node.create("<div class='rn_Guide rn_Result'></div>")
                .set("id", this.baseDomID + "_Guide" + this._currentGuideID)
                .append(this.Y.Node.create("<div class='rn_Question rn_Node'></div>")
                        .set("id", this.baseDomID + "_Question" + guide.guideID + "_" + questionID)
                        .append(
                            this._buildQuestionHTML(question) +
                            this._createQuestionChatLink(this._currentGuideID, questionID)
                        )
                );
        this._currentQuestion = questionID;
        this._appendElement(result, parentGuideID);
        RightNow.Url.transformLinks(result);
        if (this.data.attrs.single_question_display) {
            this._guideAppended = true;
        }

        RightNow.Event.fire('evt_GuideQuestionRendered', new RightNow.Event.EventObject(this, {
            data: { question: question, guideID: this._currentGuideID }
        }));
    },

    /**
    * Creates an 'Add Question to Chat' link. Only used when
    * a chat agent is viewing the guide.
    * @param {Number} guideID Guide ID
    * @param {Number} questionID Question ID
    */
    _createQuestionChatLink: function(guideID, questionID) {
        return  (this.data.js.isChatAgent) ? "<a class='rn_ChatLink' href='javascript:void(0);' data-guide='" + guideID + "' data-question='" + questionID + "'>" +
            RightNow.Interface.getMessage("ADD_TO_CHAT_CMD") + "</a>" : "";
    },

    /**
    * Creates an 'Add Resolution to Chat' link. Only used when
    * a chat agent is viewing the guide.
    * @param {Number} guideID Guide ID
    * @param {Number} questionID Question ID
    * @param {Number} responseID Response ID
    */
    _createResolutionChatLink: function(guideID, questionID, responseID) {
        return (this.data.js.isChatAgent) ? "<a class='rn_ChatLink' href='javascript:void(0);' data-guide='" + guideID + "' data-question='" + questionID + "' data-response='" + responseID + "'>" +
            RightNow.Interface.getMessage("ADD_TO_CHAT_CMD") + "</a>" : "";
    },

    /**
    * Displays a start over button. Creates the button if it hasn't been created.
    * Should only be called when in single_question_display mode and a leaf-node
    * of the guide has been hit.
    */
    _displayRestartButton: function() {
        if (this._restartButton) {
            RightNow.UI.show(this._restartButton);
        }
        else {
            this._restartButton = this.Y.Node.create("<button class='rn_RestartButton'></button>")
                    .set("id", this.baseDomID + "_RestartButton")
                    .set("innerHTML", this.data.attrs.label_start_over);
            this._restartButton.on("click", function() {
               this._setAriaLoading(true);
                while (this._currentLevel !== 1) {
                    this._goBack();
                }
                this._setAriaLoading(false);
                this._focusTopOfGuide();
            }, this);
            this.Y.one(this.baseSelector + "_BackButton").insert(this._restartButton, 'after');
        }
    },

    /**
    * Removes Question DOM nodes.
    * @param {Number} questionID ID of question from which
    *               to remove all subsequent questions
    */
    _removeElements: function(questionID) {
        var question = this._get("Question", questionID),
            next = (question) ? question.next() : null;
        while (next) {
            next.remove();
            next = question.next();
        }
    },

    /**
    * Appends new guide elements to the DOM.
    * Scrolls down to new nodes if they aren't in the current viewport.
    * @param {Object} element HTMLElement to append
    * @param {Number} parentGuideID The guide ID of the guide to append onto
    */
    _appendElement: function(element, parentGuideID) {
        var guide = this.Y.one(this.baseSelector + "_Guide" + (parentGuideID || this._currentGuideID));
        if (guide) {
            guide.appendChild(element).scrollIntoView();
            window.scrollTo(this.Y.DOM.docScrollX() - 20, this.Y.DOM.docScrollY());
        }
    },

    /**
    * Performs accessibility operations to notify of new content.
    */
    _newContentAdded: function() {
        if (this.data.attrs.single_question_display) {
            this._focusTopOfGuide();
        }
        else if (this._liveNotificationArea){
            this._liveNotificationArea.set("innerHTML", RightNow.Interface.getMessage("NEW_CONTENT_ADDED_BELOW_MSG"));
        }
    },

    /**
    * Improves the screen reader experience so that users know when new content has been added
    * when guide is in single_question_display mode. It focuses the top of the guide
    * and announces that fact.
    */
    _focusTopOfGuide: function() {
        var anchor = this.Y.one(this.baseSelector + "_SamePageAnchor");
        if (anchor) {
            anchor.setAttribute("tabindex", -1);
            try {
                anchor.focus();
            }
            catch (ex) {
                //ie throws js errors if it cannot focus for any reason
                //(element is disabled or hidden, etc...)
            }
        }
        RightNow.UI.updateVirtualBuffer();
        this._liveNotificationArea.set("innerHTML", RightNow.Interface.getMessage("TOP_CONTENT_CONTENT_ADDED_MSG"));
        this._setAriaLoading(false);
    },

     /**
     * Returns an object containing various info about the current environment.
     * @return {Object} info about environment
     */
     _getEnvironment: function() {
         return {
             mobile: this.data.js.mobile,
             console: (typeof this.data.js.agentMode !== "undefined"),
             agent: (this.data.js.agentMode === "agent"),
             preview: (this.data.js.agentMode && this.data.js.agentMode.toLowerCase().indexOf("preview") > -1),
             previewEnduser: (this.data.js.agentMode && this.data.js.agentMode === "enduserPreview"),
             samePage: function(win) {
                 // allow for mocking/testing
                 win || (win = window);

                 // During a page view, it is not possible for this.env.samePage() to go from true to false, so we can cache the result
                 // once it is true.
                 if (this._samePageCachedResult) {
                     return this._samePageCachedResult;
                 }
                 try {
                     //Do not use the parent window if chat is loaded in the parent window (more than likely it is an active chat session)
                     //Do not use the parent window if the SmartAssistantDialog is loaded in the parent window (more than likely the user is in the middle of asking a question)
                     //Do not use the parent window if the link is opened from chat transcript(Agent console)
                     this._samePageCachedResult = !win.opener ||
                         win.opener === win.self ||
                         (win.opener.RightNow && win.opener.RightNow.Chat) ||
                         (win.opener.RightNow && win.opener.RightNow.Widgets.SmartAssistantDialog) ||
                         (win.opener.location.href.search(/admin\/live\/agent.php/i) !== -1);
                 }
                 catch (e) {
                     // IE throws an exception when the opening window is closed and window.opener is then checked
                     // some instances, like when the opener is using HTTPS and the current window is not, the
                     // location.href is not accessible and throws a "Permission Denied" exception.
                     // in this case, just set to true, ensuring we don't load page in the chat engagement
                     this._samePageCachedResult = true;
                 }
                 return this._samePageCachedResult;
              }
        };
    },

    /**
    * This helper allows us to set aria-busy around _goBack calls instead of putting the aria-busy
    *  calls directly in go-back which could get called in rapid succession in _restartButton
    */
    _goBackHelper: function(){
        this._setAriaLoading(true);
        this._goBack();
        this._focusTopOfGuide();
        this._setAriaLoading(false);
    },

    /**
    * Navigates one question up the guide to the previously answered question.
    */
    _goBack: function() {
        this._currentLevel--;
        var prevQuestionID = this._previousQuestions[this._currentLevel],
            prevSibling;
        if(this._toggleQuestion(prevQuestionID, true)) {
            //display prev question, hide current node and anything below
            this._removeElements(prevQuestionID);
            //display any responses immediately above current node
            //if the current node is the first question of a sub-guide)
            if(prevQuestionID === 1 && this._guide[this._currentGuideID].parentGuide) {
                prevSibling = this._get("Guide").previous();
                if(prevSibling && prevSibling.get("id").indexOf("Result") > -1) {
                    RightNow.UI.show(prevSibling);
                }
                //Set condition necessary to hide text explanation on previous guide 
                //when navigating forward after pressing back button
                this._guideAppended = true;
            }
        }
        else {
            //Didn't find the prev question. That can only mean one thing: it's in a parent guide.
            this._currentGuideID = this._guide[this._currentGuideID].parentGuide;
            this._toggleQuestion(prevQuestionID, true);
            this._removeElements(prevQuestionID);
        }

        //hide restart button
        RightNow.UI.hide(this._restartButton);
        if (this._currentLevel === 1) {
            this._toggleBackButton();
        }
    },

/********************************
 Utility Functions
********************************/
    /**
    * Adds key-value pairs for the current question.
    * @param {Object} question The current question
    * @param {String=} responseValue optional The question's response value
    * @param {Number} level The question's level in the overall guide structure
    */
    _addPairs: function(question, responseValue, level) {
        for(var name in question.nameValuePairs) {
            if(question.nameValuePairs.hasOwnProperty(name)) {
                this._map[name] = {value: question.nameValuePairs[name], level: level};
            }
        }

       if(question.name) {
            if(typeof responseValue !== "undefined") {
                //a name that has an actual value always wins out...
                this._map[question.name] = {value: responseValue, level: level};
            }
            else if(this._map[question.name] && this._map[question.name].level === level) {
                //...except when it's within the same question (response has a response value; then response changed to one that has no response value)
                delete this._map[question.name];
            }
        }
    },

    /**
    * Removes key-value pairs for guide branches that were backtracked / abandoned.
    * @assert The member variable _currentLevel is correct
    */
    _removePairs: function() {
        for(var i in this._map) {
            if(this._map.hasOwnProperty(i) && this._map[i].level >= this._currentLevel) {
                delete this._map[i];
            }
        }
    },

    /**
    * Retrieves a Question object.
    * @param {Number} domNodeID ID of question to retrieve
    * @return {?Object} Question or null if not found
    */
    _getQuestionByID: function(domNodeID) {
        if(this._guide && this._guide[this._currentGuideID] && this._guide[this._currentGuideID].questions) {
            var questions = this._guide[this._currentGuideID].questions;
            for(var question in questions) {
                if(questions[question].questionID === domNodeID)
                    return questions[question];
            }
        }
    },

    /**
    * Retrieves a Response object.
    * @param {Object} question Response's Question object
    * @param {Number} responseID ID of response to retrieve
    * @return {?Object} Response or null if not found
    */
    _getResponseByID: function(question, responseID) {
        if(question && question.responses) {
            for(var response in question.responses) {
                if(question.responses[response].responseID === responseID)
                    return question.responses[response];
            }
        }
    },

    /**
    * Returns a path from first question to the specified question.
    * @param {Array} nodes List of nodes to consider - should only
    *                       consist of first question when called
    * @param {Number} questionID the ID of the final question
    * @return {?Array} Questions in the path or null if not found
    */
    _getPathToQuestion: function(nodes, questionID) {
        //perform a BFS
        var node, i, found, child;
        if(nodes.length) {
            node = nodes.shift();
            if(node.questionID === questionID) return [node];
            for(i in node.responses) {
                if(node.responses.hasOwnProperty(i) && node.responses[i].childQuestionID){
                    child = this._getQuestionByID(node.responses[i].childQuestionID);
                    child.parent = node.questionID;
                    nodes.push(child);
                }
            }
            found = this._getPathToQuestion(nodes, questionID);
            if(found) {
                return (found[0].parent === node.questionID) ? [node].concat(found) : found;
            }
        }
    },

    /**
    * Searches through all questions and returns the question id
    * and response id of a response that is the sub-guide specified by
    * guideID.
    * @param {Array} questions list of questions in a given guide
    * @param {Number} guideID id of the guide to look for
    * @return {?Object} containing responseID, questionID or null if not found
    */
    _getGuideParentResponse: function(questions, guideID) {
        var i, quesLen, j, respLen;
        for(i = 0, quesLen = questions.length; i < quesLen; i++) {
            for(j = 0, respLen = questions[i].responses.length; j < respLen; j++) {
                if(questions[i].responses[j].childGuideID === guideID){
                    return {responseID: questions[i].responses[j].responseID, questionID: questions[i].questionID};
                }
            }
        }
    },

    /**
    * Returns the YUI Node for the specified type and id.
    * @param {String} type Object type (Question/Response/Guide)
    * @param {String=} id Postfix part of the id (optional when type='Guide'
    *   in which case the current guide element is returned)
    * @return {?Object} YUI node or null if it doesn't exist
    */
    _get: function(type, id) {
        var suffix = id ? ("_" + id) : "";
        return this.Y.one(this.baseSelector + "_" + type + this._currentGuideID + suffix);
    },

    /**
    * Passes the specified clickstreams action and action details object
    * to the clickstreams controller; does nothing in the case that the current
    * mode is an agent or enduser preview mode
    * @param {Number} action clickstreams constant defined for RightNow.Ajax.CT
    * @param {Object} details Contains appropriate properties for the specified action
    * @param {Boolean=} queue (optional) Whether to queue the action (T) or submit
    * the action immediately; defaults to immediate submission.
    * @param {Function=} callback Executes when stats are finished submitting (optional)
    * @param {Object=} scope Scope to apply to callback (optional)
    */
    _submitStats: function(action, details, queue, callback, scope) {
        if(!this.env.preview && !this._restoringState && !this.data.js.isSpider) {
            if(queue) {
                RightNow.Ajax.CT.addAction(action, details);
            }
            else {
                RightNow.Ajax.CT.submitAction(action, details, callback, scope);
            }
        }
    },

    /**
    * Record an answer as viewed. Invoked via an event.
    * @param {String} evt Event name
    * @param {Array} args Event object
    */
    _answerViewed: function(evt, args) {
        var data = args[0].data;
        this._recordAnswerViewed(data.guideID, data.questionID, data.responseID, data.answerID);
    },

    /**
    * Called when any link within the widget is clicked.
    * @param {Object} evt Click event
    * @param {Object} ids Contains all relevant ids found on the link
    */
    _linkClicked: function(evt, ids) {
        var link = evt.target, callback;
        if (link.hasClass("rn_ChatLink")) {
            if (ids.responseID) {
                this._addResolutionToChat(ids.guideID, ids.questionID, ids.responseID);
            }
            else {
                this._addQuestionToChat(ids.guideID, ids.questionID);
            }
        }
        else {
            if (!link.getAttribute("target") || link.getAttribute("target") === "_self") {
                evt.halt();
                callback = function() { RightNow.Url.navigate(link.get("href")); };
            }
            else if (!this.env.samePage()) {
                try {
                    window.self.opener.location = link.get("href");
                    window.self.opener.focus();
                    evt.halt();
                }
                catch (e) {}
            }
            this._recordAnswerViewed(ids.guideID, ids.questionID, ids.responseID, ids.answerID, callback);
        }
    },

    /**
    * Called when add to chat link clicked.
    * Notifies external observer that the click occurred.
    * @param {Number} guideID Guide ID
    * @param {Number} questionID Question ID
    */
    _addQuestionToChat: function(guideID, questionID) {
        this._eo.data = {guideID: guideID, questionID: questionID};
        RightNow.Event.fire("evt_GuideAddQuestionToChat", this._eo);
    },

    /**
    * Called when add to chat link is clicked on a text response.
    * Notifies external observer that the click occured.
    * @param {Number} guideID Guide ID
    * @param {Number} questionID Question ID
    * @param {Number} responseID Response ID
    */
    _addResolutionToChat: function(guideID, questionID, responseID) {
        this._eo.data = {guideID: guideID, questionID: questionID, responseID: responseID};
        RightNow.Event.fire("evt_GuideAddResolutionToChat", this._eo);
    },

    /**
    * Records that an answer was viewed
    * @param {Number} guideID Guide id
    * @param {Number} questionID Question id
    * @param {Number} responseID Response id
    * @param {Number} answerID  Answer id
    * @param {Function=} Callback function (optional)
    */
    _recordAnswerViewed: function(guideID, questionID, responseID, answerID, callback) {
        RightNow.ActionCapture.record('guidedAssistanceAnswer', 'view', guideID);
        if(callback){
            RightNow.ActionCapture.flush();
        }
        this._submitStats(RightNow.Ajax.CT.GA_SESSION_DETAILS, {
            ga_sid: this._guide[guideID].guideSessionID,
            ga_id: guideID,
            q_id: questionID,
            r_id: responseID,
            a_id: answerID
        }, false, callback);
    },

    /**
     * Builds up a dom id to use for a response element.
     * @param {Number} questionID Question of the response
     * @param {Number=} responseID id of the response; if not
     *  supplied then a trailing unscore appears in the result
     * @return {String} Generated id
     */
    _buildResponseID: function(questionID, responseID) {
        return this.baseDomID + '_Response' + this._currentGuideID + '_' + questionID + '_' + (responseID || '');
    },

/********************************
 UI Utility Functions
********************************/
    /**
    * Highlights the chosen response link/button by adding 'rn_HighlightResponse' class.
    * Should only be called with links (from list responses) or buttons.
    * @param {Object} chosenResponse the clicked-on response.
    * @param {Number} questionType type of the question
    */
    _highlightResponse: function(chosenResponse, questionType) {
        var cssClass = "rn_HighlightResponse";
        if (questionType === this.data.js.types.LINK_QUESTION) {
            chosenResponse.get("parentNode").get("parentNode").all("label").removeClass(cssClass);
            chosenResponse.get("parentNode").all("label").addClass(cssClass);
        }
        else {
            cssClass += (this.env.console) ? "" : " rn_SelectedButton";
            chosenResponse.get("parentNode").all("button").removeClass(cssClass);
            chosenResponse.addClass(cssClass);
        }
    },

    /**
    * Toggles the display of the back button by adding/removing 'rn_Hidden' class.
    * @param {Boolean} show T to show the button, F to hide the button
    */
    _toggleBackButton: function(show) {
        this.Y.one(this.baseSelector + "_BackButton")[((show) ? "removeClass" : "addClass")]("rn_Hidden");
    },

    /**
    * Toggles the display of a question node by adding/removing 'rn_Hidden' class.
    * @param {Number|Object} question Integer question id if being called from goBack or object question
    * @param {Boolean} show T to show the question, F to hide the question
    * @return {Boolean} T if the operation was a success (the question was toggled);
    *                     F otherwise (the question wasn't found or was already in the toggled state specified)
    */
    _toggleQuestion: function(question, show) {
        if (!question) return false;

        if (typeof question === "number") {
            var previousQuestion = this._guide[this._currentGuideID].questions[question - 1],
                numberOfPreviousResponses = previousQuestion ? previousQuestion.responses.length : 0;

            if (numberOfPreviousResponses > 0 && previousQuestion.type === this.data.js.types.TEXT_QUESTION) {
                // Clear all the previous question's text boxes.
                for (var i = 0, input; i < numberOfPreviousResponses; i++) {
                     input = this.Y.one(this.baseSelector + "_Response" + this._originalGuideID + "_" + question + "_" + previousQuestion.responses[i].responseID);
                     if(input) {
                        input.set("value", "");
                     }
                }
            }
        }

        var element = this._get("Question", ((typeof question === "number") ? question : question.questionID)),
            elementIsHidden;

        if (element) {
            elementIsHidden = element.hasClass('rn_Hidden');
            if ((show && !elementIsHidden) || (!show && elementIsHidden)) return false;

            element[((show) ? "removeClass" : "addClass")]("rn_Hidden");

            return true;
        }

        return false;
    },

    /**
    * Hides a sub-guide's first question (called while in single_question_display mode);
    * Takes special consideration to hide any preceding response nodes from the parent guide
    * @param {Number} questionID the question ID
    */
    _hideFirstGuideQuestion: function(questionID) {
        //hides the guide's first question
        this._get("Question", questionID).addClass("rn_Hidden");
        //hides any preceding response nodes to the guide
        this._get("Guide").previous('*').addClass("rn_Hidden");
    },

    /**
    * Sets the aria-busy attribute on the outer widget div.
    * @param {Boolean} loadingOrNot Whether aria-busy or not
    */
    _setAriaLoading: function(loadingOrNot) {
        this._outerGuideDiv = this._outerGuideDiv || this.Y.one(this.baseSelector);
        this._outerGuideDiv.setAttribute("aria-busy", ((loadingOrNot) ? "true" : "false"));
    },

/********************************
 GA Tree Retrieval
********************************/
    /**
    * Fires the event to retrieve a sub-guide or create the guide if
    * its already been retrieved.
    * @param {Number} guideID The guide ID
    * @param {Number} responseID the response ID
    * @param {Number} questionID the parent question's ID
    */
    _getGuideByID: function(guideID, responseID, questionID) {
        if(this._guide[guideID]) {
            var parentGuideID = this._currentGuideID;
            this._currentGuideID = guideID;
            this._createNewGuide(this._guide[guideID], parentGuideID);
            this._currentGuideID = parentGuideID;
        }
        else if(!this._retrievingNewGuide) {
            this._retrievingNewGuide = true;
            if(this._restoringState){
                //set flag so that stats aren't recorded when new guide loads asynchronously
                this._restoringNewGuideState = true;
            }
            var eo = new RightNow.Event.EventObject(this, {data: {
                w_id: this.data.info.w_id,
                guideID: guideID,
                responseID: responseID,
                questionID: questionID,
                langID: this.data.js.langID,
                f_tok: this.data.js.f_tok
            }});
            if (RightNow.Event.fire("evt_GuidedAssistanceRequest", eo)) {
                RightNow.Ajax.makeRequest(this.data.attrs.guide_request_ajax, eo.data, {successHandler: this._getGuideResponse, scope: this, json: true, data: eo});
            }
        }
    },

    /**
    * Responds to guide retrieval event.
    * @param {Object} response Server response
    * @param {Object} origEventObject original event object
    */
    _getGuideResponse: function(response, origEventObject) {
        if (RightNow.Event.fire("evt_GuidedAssistanceResponse", {response: response, data: origEventObject})) {
            var newGuide = response,
                prevGuideID = this._currentGuideID;

            newGuide.parentGuide = prevGuideID;
            this._currentGuideID = newGuide.guideID;
            this._guide[this._currentGuideID] = newGuide;

            if(!this._restoringNewGuideState) {
                //Submit stats for new guide session
                this._submitStats(RightNow.Ajax.CT.GA_SESSIONS, {
                    ga_sid: this._guide[this._currentGuideID].guideSessionID,
                    ga_id: this._currentGuideID,
                    sid: this.data.js.session,
                    acct_id: parseInt(this.data.js.accountID, 10),
                    channel: this.data.js.channel
                }, true);
            }

            //output new guides' first question
            this._createNewGuide(newGuide, prevGuideID);

            if(!this._restoringNewGuideState) {
                //submit stats for the guide's initially displayed question
                this._submitStats(RightNow.Ajax.CT.GA_SESSION_DETAILS, {
                    ga_sid: this._guide[this._currentGuideID].guideSessionID,
                    ga_id: this._currentGuideID,
                    q_id: this._currentQuestion
                });
            }
            else{
                this._restoringNewGuideState = false;
            }

            this._retrievingNewGuide = false;
            if(this._guideLoadedCallback){
                this._guideLoadedCallback.call(this, this._currentGuideID);
            }
            this._newContentAdded();
        }
    },

/********************************
 History management Functions
********************************/
    /**
    * Initializes history manager functionality.
    */
    _initHistoryManager: function() {
        if (this._history) return;

        var recordGuideSessionStart = function() {
            //Submit stats for this session
            this._submitStats(RightNow.Ajax.CT.GA_SESSIONS, {
                ga_sid: this._guide[this._currentGuideID].guideSessionID,
                ga_id: this._currentGuideID,
                sid: this.data.js.session,
                acct_id: parseInt(this.data.js.accountID, 10),
                channel: this.data.js.channel
            }, true);
            //Submit stats for the initially displayed question
            this._submitStats(RightNow.Ajax.CT.GA_SESSION_DETAILS, {
                ga_sid: this._guide[this._currentGuideID].guideSessionID,
                ga_id: this._currentGuideID,
                q_id: this._currentQuestion
            });
            RightNow.ActionCapture.record('guidedAssistance', 'load', this._currentGuideID);
        };

        if (top === self && !this.env.console) {
            // History manager setup
            this._stateKey = "gs";
            this._history = new this.Y.History();
            this._history.on("change", function(e){
                var src = e.src,
                    currentState;
                if (src !== this.Y.HistoryHash.SRC_HASH && src !== this.Y.HistoryHTML5.SRC_POPSTATE) {
                    return;
                }
                currentState = e.newVal[this._stateKey] || "";
                if (!this._restoreState(currentState)) {
                    recordGuideSessionStart.call(this);
                }
            }, this, true);

            // Restore an initial state, if one exists (user refreshes the page)
            var initialState;
            if (this.Y.HistoryBase.html5) {
                // HTML5 history module doesn't look for initial state, so pull it out manually
                initialState = window.location.toString().match(new RegExp("(#" + this._stateKey + "=)([A-Za-z0-9_/.]+)"));
                if (initialState && initialState[2]) {
                    initialState = initialState[2];
                }
                else {
                    initialState = null;
                }
            }
            else {
                initialState = this._history.get(this._stateKey);
            }
            if (initialState && this._restoreState(initialState)) {
                return;
            }
        }
        recordGuideSessionStart.call(this);
    },

    /**
    * Restores the passed-in state.
    * @param {String} state The base64 encoded JSON-stringified state
    * @return {Boolean} Whether the current session is still valid
    */
    _restoreState: function(state){
        if(state){
            try{
                state = RightNow.JSON.parse(RightNow.Text.Encoding.base64Decode(state));
                if(state.sessionID !== this.data.js.session){
                    //if we're seeing a new session, then the guide session that's been saved is no longer valid.
                    return false;
                }
                if((this._currentGuideID !== state.guideID || this._currentResponse !== state.responseID || this._previousQuestions[this._currentLevel - 1] !== state.questionID)){
                    this._restoringState = true;
                    this._guide[this._currentGuideID].guideSessionID = state.guideSession;
                    this._goToResponse(this.instanceID, [{data: {guideID: state.guideID, questionID: state.questionID, responseID: state.responseID}}]);
                    this._restoringState = false;
                }
            }
            catch(e){
                //invalid state: someone's been messin' with the URL!
            }
        }
        else if(this._currentLevel > 1){
            //go back to the initial state
            this._currentResponse = null;
            this._goToQuestion(this.instanceID, [{data: {guideID: this._originalGuideID, questionID: this._guide[this._originalGuideID].questions[0].questionID}}]);
        }
        return true;
    },

    /**
    * Stashes off the current state in the YUI history manager.
    * @param {Object} state State to stash. Must contain guideID,
    * questionID, responseID, guideSession, and sessionID members.
    */
    _saveState: function(state) {
        if(this._history && !this._restoringState) {
            //don't attempt to save a state when the state's currently being restored
            var savedOffState = RightNow.Text.Encoding.base64Encode(RightNow.JSON.stringify(state)),
                hash = "#" + this._stateKey + "=" + savedOffState,
                saveThis = {};
            saveThis[this._stateKey] = savedOffState;
            this._history.add(saveThis, {url: ((window.location.hash) ? window.location.toString().replace(window.location.hash, hash) : window.location + hash)});
        }
    }
});
