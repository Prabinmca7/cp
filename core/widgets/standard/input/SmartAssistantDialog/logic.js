RightNow.Widgets.SmartAssistantDialog = RightNow.Field.extend({
    overrides: {
        constructor: function() {
            this.parent();

            try {
                this.parentForm().on("response", this._displayResults, this);
                //We need to store this ID off since when the dialog is popped, this widget is no longer within the form element and therefore
                //can't call the form (re)submit event. We'll use this later to tell the parentForm() function which ID to use
                this._parentFormID = this.getParentFormID();
            }
            catch (e) {
                // Widget appears outside of a form tag
                RightNow.Event.on('evt_formButtonSubmitResponse', this._displayResults, this);
            }
            RightNow.UI.Form.smartAssistant = true;
            this.sessionParameter = '';
            this.Y.augment(this, RightNow.Avatar);
        }
    },
    /**
     * Function to navigate to links within same page
     */
    _navigateToTag: function(evt) {
        evt.preventDefault();
        var elem = document.getElementById(evt.currentTarget.getData('tagId'));
        elem.scrollIntoView();
    },
    /**
     * Event handler for when form submission returns from the server.
     * This function only handles a server response that contains
     * SmartAssistant result data.
     * @param evt string Event name
     * @param args object Event arguments
     */
    _displayResults: function(evt, args)
    {
        var result = args[0];
        if(result && result.data && result.data.result && result.data.result.sa)
        {
            if (result.data.result.newFormToken)
            {
                // Check if a new form token was passed back and use it the next time the the form is submitted
                RightNow.Event.fire("evt_formTokenUpdate", new RightNow.Event.EventObject(null, {data: {newToken: result.data.result.newFormToken}}));
            }

            if (!this._parentFormID && result.data.form)
            {
                this._parentFormID = result.data.form;
            }
            this.sessionParameter = result.data.result.sessionParam || '';
            result = result.data.result.sa;

            if(result.token){
                RightNow.UI.Form.smartAssistantToken = result.token;
            }
            RightNow.UI.Form.smartAssistant = false;
            if(typeof result.canEscalate !== 'undefined' )
                this._doNotCreateIncidentOrSocialQuestion = !result.canEscalate;
            else if (typeof result.canCreate !== 'undefined' )
                this._doNotCreateIncidentOrSocialQuestion = !result.canCreate;

            var dialogHeading = this.Y.one("#rn_" + this.instanceID + "_DialogHeading");
            if(dialogHeading)
                dialogHeading.set("innerHTML", this.data.attrs.label_banner);

            var saDisplay,
                accessKeyPrompt = "",
                displayButton = true,
                inlineContent = false,
                inlineAnswerDiscussions = false,
                suggestions = result.suggestions,
                i,
                accessKeyNumber = 0;

            if(suggestions && suggestions.length > 0)
            {
                var suggestion;
                for(i = 0; i < suggestions.length; i++)
                {
                    suggestion = suggestions[i];
                    if(suggestion.type === 'AnswerSummary' || suggestion.type === 'QuestionSummary')
                    {
                        if(this.data.attrs.accesskeys_enabled && this.data.attrs.label_accesskey && this.data.attrs.display_inline)
                        {
                            accessKeyNumber += suggestion.list.length;
                        }
                        if(this.data.attrs.display_inline)
                            inlineAnswerDiscussions = true;
                    }
                    else
                    {
                        inlineContent = true;
                    }
                }

                if (accessKeyNumber > 0) {
                    var keyComboString = RightNow.Interface.getMessage("ACCESSKEY_LBL"),
                        UA = this.Y.UA,
                        OS = UA.os;

                    if(UA.ie)
                        keyComboString = RightNow.Interface.getMessage("ALT_LBL");
                    else if (UA.gecko)
                        keyComboString = (OS === "windows" || !OS) ? RightNow.Interface.getMessage("ALT_PLUS_SHIFT_LBL") : RightNow.Interface.getMessage("CTRL_LBL");
                    else if (UA.webkit)
                        keyComboString = (OS === "windows" || !OS) ? RightNow.Interface.getMessage("ALT_LBL") : RightNow.Interface.getMessage("CTRL_PLUS_OPT_LBL");

                    accessKeyPrompt = RightNow.Text.sprintf("<span class='rn_ScreenReaderOnly'>" + this.data.attrs.label_accesskey + "</span>", keyComboString, accessKeyNumber);
                }

                if (suggestions.length > 0) {
                    var data = {
                        'suggestions' : suggestions,
                        'accessKeyPrompt' : accessKeyPrompt,
                        'sessionParam' : this.sessionParameter,
                        'baseDomID' : this.baseDomID,
                        'attrs' : {
                            'label_prompt' : this.data.attrs.label_prompt,
                            'accesskeys_enabled' : this.data.attrs.accesskeys_enabled,
                            'label_accesskey' : this.data.attrs.label_accesskey,
                            'display_inline' : this.data.attrs.display_inline,
                            'label_collapsed' : this.data.attrs.label_collapsed
                        },
                        'answerUrl' : RightNow.Interface.getConfig('CP_ANSWERS_DETAIL_URL')
                    };

                    saDisplay = this._generateViewData(suggestions, data);
                }
            }
            else
            {
                saDisplay = this.data.attrs.label_no_results;
            }
            if(this._doNotCreateIncidentOrSocialQuestion)
            {
                RightNow.ActionCapture.record('incident', 'doNotCreateState');
                //Reset the SA to always display if they are in the DNC case
                RightNow.UI.Form.smartAssistant = true;
                if(this.data.attrs.dnc_label_banner && dialogHeading)
                    dialogHeading.set("innerHTML", this.data.attrs.dnc_label_banner);
                displayButton = false;
            }

            //Create the dialog box, set up the event handlers, and populate it with the generated HTML.
            this._dialogBody = this.Y.one(this.baseSelector);
            this._dialogContent = this.Y.one(this.baseSelector + "_DialogContent");

            if(this._dialogBody)
            {
                var handlers = {
                        label_submit_button: {
                            fn: this._submitButtonAction,
                            scope: this
                        },
                        label_cancel_button:{
                            fn: this._cancelButtonAction,
                            scope: this
                        },
                        label_solved_button: {
                            fn: this._solvedButtonAction,
                            scope: this
                        }
                    },
                    dialogContent = this._dialogContent,
                    buttons = [],
                    links = [],
                    buttonOrder = this.data.attrs.button_ordering,
                    index = 0,
                    button;

                if(displayButton)
                {
                    for(i = 0; i < buttonOrder.length; i++)
                    {
                        button = buttonOrder[i];
                        buttons.push({text: button.label, handler: handlers[button.name], isDefault: (buttons.length === 0)});
                        if(button.displayAsLink)
                        {
                            // keep track of the button's index and click handler for the button's replacement later
                            links.push({index: index, handler: handlers[button.name], label: button.label});
                        }
                        index++;
                    }
                }
                else if(this.data.attrs.label_cancel_button)
                {
                    buttons.push({text: (this._doNotCreateIncidentOrSocialQuestion && this.data.attrs.dnc_label_cancel_button)
                        ? this.data.attrs.dnc_label_cancel_button
                        : this.data.attrs.label_cancel_button,
                        handler: handlers.label_cancel_button, isDefault: true});
                }

                if(dialogContent)
                {
                    dialogContent.set("innerHTML", saDisplay);
                    if(inlineAnswerDiscussions && saDisplay !== this.data.attrs.label_no_results)
                        this._enableInlineAnswerDiscussions();
                    if(inlineContent || !inlineAnswerDiscussions)
                        this._modifyLinkTargets(dialogContent);
                }

                this._dialog = RightNow.UI.Dialog.actionDialog((this._doNotCreateIncidentOrSocialQuestion && this.data.attrs.dnc_label_dialog_title)
                    ? this.data.attrs.dnc_label_dialog_title
                    : this.data.attrs.label_dialog_title,
                    this._dialogBody, {"buttons": buttons});
                var panel = this.Y.one('#' + this._dialog.id).ancestor('.yui3-panel');
                if (panel)
                    panel.addClass("rn_SmartAssistantDialogContainer");
                RightNow.UI.show(this._dialogBody);
                if(links.length)
                {
                    // replace buttons with equivalent links
                    var dialogButtons = this._dialog.getButtons(),
                        link, handler, buttonContainer;
                    for(i = 0; i < links.length; i++)
                    {
                        button = dialogButtons.item ? dialogButtons.item(links[i].index) : dialogButtons[links[i].index];
                        handler = links[i].handler;
                        link = this.Y.Node.create("<a href='javascript:void(0);'>" + links[i].label + "</a>");
                        link.on('click', (typeof handler === "function") ? handler : handler.fn, links[i].handler.scope || this._dialog);
                        buttonContainer = button.get('parentNode') || null;
                        if(buttonContainer)
                        {
                            button.insert(link, 'after');
                            button.remove();
                        }
                    }
                }
                this._dialog.show();
            }
        }
    },

    /**
     * Build up data array for the EJS view.
     * @param {Object} result SmartAssistant result data
     * @param {Object} viewData Data needed for EJS view
     */
    _generateViewData: function(result, viewData)
    {
        var viewDisplay = this.data.attrs.label_no_results;
        if (result.length > 0)
            viewDisplay = new EJS({text: this.getStatic().templates.displayResults}).render(viewData);

        return viewDisplay;
    },

    _submitButtonAction: function()
    {
        //notify FormSubmit widget to re-submit
        this._dialog.hide();
        this.parentForm(this._parentFormID).fire("submitRequest");
    },

    _cancelButtonAction: function()
    {
        if(!this._doNotCreateIncident || !this.data.attrs.dnc_redirect_url)
            this._dialog.hide();
        else
            RightNow.Url.navigate(this.data.attrs.dnc_redirect_url);
    },

    _solvedButtonAction: function()
    {
        RightNow.ActionCapture.record('incident', 'deflect');
        RightNow.ActionCapture.flush(function(){
            var redirectUrl = this.data.attrs.solved_url;
            if(RightNow.UI.Form.smartAssistantToken && RightNow.Text.beginsWith(redirectUrl, '/app')){
                redirectUrl = RightNow.Url.addParameter(redirectUrl, 'saResultToken', RightNow.UI.Form.smartAssistantToken);
            }
            RightNow.Url.navigate(redirectUrl);
        }, this);
    },

    /**
     * Sets the short anchor links to the current window and all
     * other links add in the target attribute to "_blank" to force
     * those links to a new window.
     * @param {object} rootElement - YUI3 Node for which you want the links modified
     */
    _modifyAnchorLinks: function(rootElement)
    {
        var baseHrefFragment = this.Y.one('base');
        var href = "";
        baseHrefFragment = (baseHrefFragment) ? baseHrefFragment.get("href") : "";
        //Some browsers automatically append a slash to the end of domain only URLs. We need to
        //strip them off so that the comparisons later can be accurate.
        if(baseHrefFragment.substr(-1) === '/')
            baseHrefFragment = baseHrefFragment.slice(0, -1);

        rootElement.all('a').each(function(node) {
            if((href = node.get("href")) !== "") {
                var hashLocation = href.split("#");
                //Some browsers automatically append a slash to the end of domain only URLs. We need to
                //strip them off so that the comparisons later can be accurate.
                if(hashLocation[0].substr(-1) === '/')
                    hashLocation[0] = hashLocation[0].slice(0, -1);

                if((hashLocation[0] === undefined || hashLocation[0] === baseHrefFragment) && hashLocation[1] !== undefined) {
                    node.set("href", window.location.pathname + "#" + hashLocation[1]);
                }
                else {
                    node.setAttribute("target", "_blank");
                }
            }
        });
    },

    /**
     * Sets target attribute of all links to "_blank" to force links to a new window.
     * @param {object} rootElement - YUI3 Node for which you want the links modified
     */
    _modifyLinkTargets: function(rootElement)
    {
        if(this.data.attrs.display_answers_inline)
            this._modifyAnchorLinks(rootElement);
        else {
            rootElement.all('a').setAttribute("target", "_blank");
            rootElement.all('a.internalLink').on('click', this._navigateToTag);
        }
    },

    /**
     * Declares the event listener for when the links specified are clicked.
     */
    _enableInlineAnswerDiscussions: function()
    {
        this._answersLoaded = {};
        this._discussionsLoaded = {};

        this.Y.one(this.baseSelector).delegate('click', function(evt)
        {
            var clicked = evt.target,
                objectID = clicked.getAttribute('data-id');
            this._type = clicked.getAttribute('data-object-type');

            //Non-answer expander was clicked, let it work like normal
            if(objectID === ""){
                return;
            }
            evt.halt();
            if(this._showingAnswerDiscussion === clicked)
                return;

            //If the answer isn't already loaded, make an AJAX request to get it.
            if((typeof this._answersLoaded[objectID] === "undefined" && this._type === 'answer')
                    || (typeof this._discussionsLoaded[objectID] === "undefined" && this._type === 'discussion'))
            {
                clicked.append("<span class='rn_Loading' aria-live='assertive'><span class='rn_ScreenReaderOnly'>" + RightNow.Interface.getMessage("LOADING_ELLIPSES_LBL") + "</span></span>");
                this._showingAnswerDiscussion = clicked;
                if(this._dialogBody)
                    this._dialogBody.setAttribute("aria-busy", "true");

                var eventObject = new RightNow.Event.EventObject(this, {data: {objectID: objectID}}),
                    contentEndpoint;

                if(this._type === 'answer' && RightNow.Event.fire('evt_getAnswerRequest', eventObject)){
                    contentEndpoint = this.data.attrs.get_answer_content;
                }
                else if(this._type === 'discussion' && RightNow.Event.fire('evt_getDiscussionRequest', eventObject)){
                    contentEndpoint = this.data.attrs.get_discussion_content;
                }

                if(contentEndpoint)
                    this._getContent(clicked, eventObject, contentEndpoint);
            }
            //If it's loaded, toggle the display
            else
            {
                this._toggleContent(objectID, !this._answersLoaded[objectID]);
            }
        }, 'ul', this);
    },

    /**
     * Retrieves content
     * @param {Object} clickedLink Event target
     * @param {Object} eventObject Event object
     * @param {Object} contentEndpoint Endpoint to use to get content
     */
    _getContent: function(clickedLink, eventObject, contentEndpoint)
    {
        RightNow.Ajax.makeRequest(contentEndpoint, eventObject.data, {successHandler: this._displayContent, scope: this, data: eventObject, json: true, isResponseObject: true});
    },

    /**
    * Common method related to showing content
    * @param {int} objectID Clicked ID
    * @param {string} type Type of content
    * @param {Object} contentWrapper Container of the content
    */
    _showContent: function(objectID, type, contentWrapper)
    {
        this._modifyLinkTargets(contentWrapper);
        this._insertContent(contentWrapper);
        this._showingAnswerDiscussion.removeChild(this._showingAnswerDiscussion.get('lastChild'));
        this._showingAnswerDiscussion = null;
        this._toggleContent(objectID, true);

        if(this._dialog.resizeToWindow)
            this._dialog.resizeToWindow();
        if(this._dialogBody)
            this._dialogBody.setAttribute('aria-busy', 'false');

        RightNow.ActionCapture.record(type, 'view', objectID);
    },

    /**
    * Common method to insert content
    * @param {Object} contentWrapper Container of the content
    */
    _insertContent: function(contentWrapper)
    {
        this._showingAnswerDiscussion.insert(contentWrapper, 'after');
    },

    /**
    * Event subscriber for when an answer is returned from the server.
    * @param {Object} response Response object from the server containing answer data for the clicked ID.
    */
    _displayContent: function(response, originalEventObject)
    {
        var objectID = response.ID,
            answerDiscussionWrapper;

        if(this._type === 'answer' && RightNow.Event.fire("evt_getAnswerResponse", {data: originalEventObject, response: response})) {
            //Make sure the response is hitting the correct widget, is expected, matches the showing answer and has legitimate answer data.
            if(this._showingAnswerDiscussion && this._showingAnswerDiscussion.get('id').indexOf(response.ID) > -1 && response.ID) {
                answerDiscussionWrapper = this.Y.Node.create(new EJS({text: this.getStatic().templates.answerContent}).render({
                    spanID: this.baseDomID + '_AnswerContent' + objectID,
                    question: (response.Question && !response.GuidedAssistance) ? response.Question : null,
                    contents: this._getContents(objectID, response)
                }));

                this._answersLoaded[objectID] = true;
            }
        }
        else if(this._type === 'discussion' && RightNow.Event.fire("evt_getDiscussionResponse", {data: originalEventObject, response: response})) {
            if(this._showingAnswerDiscussion && this._showingAnswerDiscussion.get('id').indexOf(response.ID) > -1 && response.ID) {
                answerDiscussionWrapper = this.Y.Node.create(new EJS({text: this.getStatic().templates.discussionContent}).render({
                    spanID: this.baseDomID + '_AnswerContent' + objectID,
                    objectID: objectID,
                    discussion: response.Body,
                    solution: response.BestCommunityQuestionAnswers,
                    avatarURL: response.CreatedByCommunityUser.AvatarURL,
                    displayName: response.CreatedByCommunityUser.DisplayName,
                    sessionParam : this.sessionParameter,
                    bestAnswerExists: this._bestAnswerExists(response.BestCommunityQuestionAnswers),
                    userStatus: response.CreatedByCommunityUser.StatusWithType.Status.ID,
                    js: this.data.js
                }));

                this._discussionsLoaded[objectID] = true;
            }
        }

        this._showContent(objectID, this._type, answerDiscussionWrapper);
    },
    /**
     * Returns whether to diplay best answer or not
     * @param {Object} bestAnswers The best answer details from response object
     * @return {boolean} true or false
     */
    _bestAnswerExists: function(bestAnswers)
    {
        for(var i in bestAnswers) {
            if (bestAnswers[i].CommunityComment.Body !== null) {
               return true;
            }
        }
        return false;
    },

    /**
     * Retrieves the Answer content to display
     * @param {int} answerID The answer ID
     * @param {Object} response Response object from the server containing answer data
     * @return {string} The answer content
     */
    _getContents: function(answerID, response)
    {
        if (response.FileAttachments) {
            var attachmentLinks = '';
            this.Y.Object.each(response.FileAttachments, function(attachment) {
                attachmentLinks += this._getAnswerLink(answerID, '/ci/fattach/get/' + attachment.ID + this.sessionParameter, attachment.FileName || this.data.attrs.label_download_attachment) + '<br />';
            }, this);
            return (response.Solution ? response.Solution : '') + attachmentLinks;
        }

        if(response.URL) {
            return this._getAnswerLink(answerID, response.URL, response.URL);
        }

        if (response.GuidedAssistance) {
            return this._getAnswerLink(answerID, '/app/' + RightNow.Interface.getConfig('CP_ANSWERS_DETAIL_URL') + '/a_id/' + answerID + this.sessionParameter, this.data.attrs.label_view_guide);
        }

        return response.Solution;
    },

    /**
     * Returns the answer link HTML
     * @param {int} answerID The answer ID
     * @param {string} href The URL to link to
     * @param {string} text The text to display
     * @return {string} The answer link HTML
     */
    _getAnswerLink: function(answerID, href, text)
    {
        return new EJS({text: this.getStatic().templates.answerLink}).render({
            answerID : answerID,
            href : href,
            text : text
        });
    },

    /**
    * Handles the accordion-link toggling display of expanded answer details.
    * @param answerID int The answer id of the answer to toggle
    * @param expand boolean T to expand the answer F to hide the answer
    */
    _toggleContent: function(answerID, expand)
    {
        var id = this.baseSelector + "_Answer",
            toggle = this.Y.one(id + answerID),
            answer = this.Y.one(id + "Content" + answerID),
            alt = this.Y.one(id + answerID + "_Alternative");

        this._expand = expand;

        if(expand)
        {
            this._expandAnswerContent(id, answerID, answer, toggle, alt);
        }
        else //collapse
        {
            this._collapseAnswerContent(answerID, answer, toggle, alt);
        }
        this._answersLoaded[answerID] = expand;
    },

    /**
    * Handles expanded answer details of toggling display.
    * @param answer object The container of the answer content
    * @param toggle object The toggled element
    * @param alt object The container of the screenreader text
    */
    _expandAnswerContent: function(id, answerID, answer, toggle, alt)
    {
        for(var i in this._answersLoaded)
        {
            if(this._answersLoaded.hasOwnProperty(i) && i !== answerID && this._answersLoaded[i] === true)
            {
                this.Y.one(id + i).replaceClass("rn_ExpandedAnswer", "rn_ExpandAnswer");
                this.Y.one(id + "Content" + i).replaceClass("rn_ExpandedAnswerContent", "rn_Hidden");
                this.Y.one(id + i + "_Alternative").set("innerHTML", this.data.attrs.label_collapsed);
                this._answersLoaded[i] = false;
            }
        }

        answer.replaceClass("rn_Hidden", "rn_ExpandedAnswerContent");
        toggle.replaceClass("rn_ExpandAnswer", "rn_ExpandedAnswer");
        alt.set("innerHTML", this.data.attrs.label_expanded);

        if(!this.Y.DOM.contains(window, this.Y.Node.getDOMNode(toggle)))
        {
            //mobile: scroll to the top of the expanded item
            //iOS: doesn't properly implement view properties but does properly implement auto-scrolling
            if(toggle.getY() <= 0 && toggle.scrollIntoView)
                toggle.scrollIntoView();
            //Android: doesn't properly implement auto-scrolling but does properly implement view properties
            else
                window.scrollTo(0, toggle.getY() - 20); //20px buffer above
            //WebOS: doesn't properly implement anything...
        }
    },

    /**
    * Handles collapsed answer details of toggling display.
    * @param answer object The container of the answer content
    * @param toggle object The toggled element
    * @param alt object The container of the screenreader text
    */
    _collapseAnswerContent: function(answerID, answer, toggle, alt)
    {
        answer.replaceClass("rn_ExpandedAnswerContent", "rn_Hidden");
        toggle.replaceClass("rn_ExpandedAnswer", "rn_ExpandAnswer");
        alt.set("innerHTML", this.data.attrs.label_collapsed);
    }
});
