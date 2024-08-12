RightNow.Widgets.OkcsSmartAssistant = RightNow.Widgets.SmartAssistantDialog.extend({
    overrides: {
        constructor: function() {
            this.data.attrs.get_answer_content = this.data.attrs.get_okcs_data_ajax;
            this._isExplorerView = this.data.attrs.view_type.toLowerCase() === 'explorer';
            this._openNewTabText = this.data.attrs.label_new_tab;
            try {
                this.parentForm().on("response", this._displayOkcsResults, this);
                //We need to store this ID off since when the dialog is popped, this widget is no longer within the form element and therefore
                //can't call the form (re)submit event. We'll use this later to tell the parentForm() function which ID to use
                this._parentFormID = this.getParentFormID();
            }
            catch (e) {
                // Widget appears outside of a form tag
                RightNow.Event.on('evt_formButtonSubmitResponse', this._displayOkcsResults, this);
            }
            RightNow.UI.Form.smartAssistant = true;
            this.Y.augment(this, RightNow.Avatar);
        },

        /**
         * Build up data array for the EJS view.
         * @param {Object} result SmartAssistant result data
         * @param {Object} viewData Data needed for EJS view
         */
        _generateViewData: function(result, viewData) {
            var viewDisplay = this.data.attrs.label_no_results;

            result = result[0].list;
            if (result.length > 0) {
                viewData.openNewTabText = this._openNewTabText;
                viewData.truncateSize = this.data.attrs.truncate_size;
                viewData.ellipsis = RightNow.Interface.getMessage('ELLIPSIS_MSG');
                viewData.answerViewUrl = this.data.attrs.answer_detail_url !== undefined && this.data.attrs.answer_detail_url !== '' ? this.data.attrs.answer_detail_url : RightNow.Interface.getConfig('CP_ANSWERS_DETAIL_URL');
                viewDisplay = new EJS({text: this.getStatic().templates.displayOkcsResults}).render(viewData);
            }
            this.Y.one(this.baseSelector).delegate('click', this._openNewTab, '.rn_NewTab', this);
            return viewDisplay;
        },

        _submitButtonAction: function() {
            //notify FormSubmit widget to re-submit
            this._dialog.hide();
            var eventObject = new RightNow.Event.EventObject(this, {data: { priorTransactionID: this._priorTransactionID, deflected: 'false', okcsSearchSession: this._okcsSearchSession }});
            RightNow.Ajax.makeRequest(this.data.attrs.get_okcs_data_ajax, eventObject.data, {ignoreFailure: true, scope: this, data: eventObject, json: true});
            this.parentForm(this._parentFormID).fire("submitRequest");
        },

        _solvedButtonAction: function()
        {
            RightNow.ActionCapture.record('incident', 'deflect');
            RightNow.ActionCapture.flush(function(){
                var redirectUrl = this.data.attrs.solved_url;
                if(RightNow.UI.Form.smartAssistantToken && RightNow.Text.beginsWith(redirectUrl, '/app')){
                    redirectUrl = RightNow.Url.addParameter(redirectUrl, 'saResultToken', RightNow.UI.Form.smartAssistantToken);
                }
                var eventObject = new RightNow.Event.EventObject(this, {data: { priorTransactionID: this._priorTransactionID, deflected: 'true', okcsSearchSession: this._okcsSearchSession }});
                RightNow.Ajax.makeRequest(this.data.attrs.get_okcs_data_ajax, eventObject.data, {ignoreFailure: true, scope: this, data: eventObject, json: true});
                RightNow.Url.navigate(redirectUrl);
            }, this);
        },

        /**
         * Sets target attribute of all links to "_blank" to force links to a new window.
         * @param {Object} clickedLink Clicked DOM node
         * @param {Object} eventObject Event
         */
        _getContent: function(clickedLink, eventObject, contentEndpoint) {
            if(clickedLink.getAttribute('data-object-type') === 'discussion'){
                RightNow.Ajax.makeRequest(contentEndpoint, eventObject.data, {successHandler: this._displayContent, scope: this, data: eventObject, json: true, isResponseObject: true});
                return;
            }
            var highlightUrl = clickedLink.getAttribute('data-highlightUrl'),
                answerDetails = clickedLink.getAttribute('data-docId').split(':'),
                answerType = clickedLink.getAttribute('data-answerType'),
                answerID = answerDetails[1],
                imDocID = answerDetails[3],
                clickthruEventObject = new RightNow.Event.EventObject(this, {data: {
                    docID: answerDetails[0],
                    answerID: answerID,
                    clickThruLink: clickedLink.getAttribute('data-clickThroughUrl')
                }}),
                isAggRat = this.data.attrs.custom_metadata.indexOf('aggregate_rating') !== -1;
            if(highlightUrl !== undefined && highlightUrl !== '' ) {
                eventObject = new RightNow.Event.EventObject(this, {data: {
                    highlightedLink: highlightUrl,
                    doc_id: answerType === 'HTML' ? answerID : imDocID,
                    answerType: answerType,
                    isAggRat: isAggRat
                }});
            }
            else {
                eventObject = new RightNow.Event.EventObject(this, {data: {doc_id: answerType !== 'CMS-XML' ? answerID : imDocID, highlightedLink: clickedLink.getAttribute('data-clickThroughUrl'), answerType: answerType}});
            }
            var answerData = {'answerID' : answerID, 'answerUrl': clickedLink.getAttribute('data-url'), 'title' : clickedLink.getAttribute('title')};
            this._displayOKCSAnswerContent(answerData, eventObject, clickthruEventObject);
        },

        /**
        * Common method to insert content
        * @param {Object} contentWrapper Container of the content
        */
        _insertContent: function(contentWrapper) {
            if (this._isExplorerView) {
                this._dialogContent.insert(contentWrapper, "after");
            }
            else {
                this.Y.one("#" + this.baseDomID + "_Answer" + this._showingAnswerDiscussion.getAttribute('data-id')).insert(contentWrapper, "after");
            }
        },

        /**
        * Handles expanded answer details of toggling display.
        * @param id string id of the DOM node
        * @param answerID string answer id
        * @param answer object The container of the answer content
        * @param toggle object The toggled element
        * @param alt object The container of the screenreader text
        */
        _expandAnswerContent: function(id, answerID, answer, toggle, alt) {
            for(var i in this._answersLoaded) {
                if(this._answersLoaded.hasOwnProperty(i) && i !== answerID && this._answersLoaded[i] === true) {
                    //hide any currently-expanded answers
                    if (this._isExplorerView) {
                        this.Y.one(id + "Content" + i).replaceClass("rn_ExpandedAnswerContentExplorer", "rn_Hidden");
                    }
                    this.Y.one(id + i).replaceClass("rn_ExpandedAnswer", "rn_ExpandAnswer");
                    this.Y.one(id + "Content" + i).replaceClass("rn_ExpandedAnswerContent", "rn_Hidden");
                    this.Y.one(id + i).removeClass("rn_ExpandedAnswerSolution");
                    this.Y.one(id + i + "_Alternative").set("innerHTML", this.data.attrs.label_collapsed);
                    this._answersLoaded[i] = false;
                }
            }

            if (this._isExplorerView) {
                this._explorerViewToggleAnswerContent(answerID, this._expand, toggle, answer);
            }
            else {
                answer.replaceClass("rn_Hidden", "rn_ExpandedAnswerContent");
                toggle.replaceClass("rn_ExpandAnswer", "rn_ExpandedAnswerSolution");
            }

            alt.set("innerHTML", this.data.attrs.label_expanded);

            if(!this.Y.DOM.contains(window, this.Y.Node.getDOMNode(toggle))) {
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
        * @param answerID string answer id
        * @param answer object The container of the answer content
        * @param toggle object The toggled element
        * @param alt object The container of the screenreader text
        */
        _collapseAnswerContent: function(answerID, answer, toggle, alt) {
            if (this._isExplorerView) {
                this._explorerViewToggleAnswerContent(answerID, this._expand, toggle, answer);
            }
            else {
                answer.replaceClass("rn_ExpandedAnswerContent", "rn_Hidden");
                toggle.replaceClass("rn_ExpandedAnswerSolution", "rn_ExpandAnswer");
            }
            alt.set("innerHTML", this.data.attrs.label_collapsed);
        }
    },

    /**
     * Event handler for for opening a result in a new browser tab.
     * @param evt string Event name
     * @return {Boolean} false to avoid browser default call
     */
    _openNewTab: function(evt) {
        var url = evt.target.getAttribute('data-url');
        if (url.indexOf('a_id') > -1)
            url = '/app/' + url;
        window.open(url);
        return false;
    },

    /**
     * Event handler for when form submission returns from the server.
     * This function only handles a server response that contains
     * OKCS SmartAssistant result data.
     * @param evt string Event name
     * @param args object Event arguments
     */
    _displayOkcsResults: function(evt, args) {
        var result = args[0];
        if(this.Y.all('.rn_Answer.rn_AnswerDetail')) {
            this.Y.all('.rn_Answer.rn_AnswerDetail').remove();
        }
        if(result && result.data && result.data.result && result.data.result.sa) {
            if (!this._parentFormID && result.data.form)
                this._parentFormID = result.data.form;

            result = result.data.result.sa;
            this._transactionID = result.transactionID;
            this._priorTransactionID = result.priorTransactionID;
            this._okcsSearchSession = result.okcsSearchSession;

            this._displayResults(evt, args);
        }
    },

    /**
    * Method to get the content of OKCS document.
    * @param answerID object Answer data object
    * @param eventObject object Event
    * @param clickThruEventObject object ClickThruEventObject
    */
    _displayOKCSAnswerContent: function(answerData, eventObject, clickThruEventObject) {
        var answerID = answerData.answerID,
            answerUrl = answerData.answerUrl,
            answerTitle = answerData.title,
            isAggRat = this.data.attrs.custom_metadata.indexOf('aggregate_rating') !== -1;    
        if(RightNow.Event.fire("evt_getAnswerResponse")){
            if(this._showingAnswerDiscussion && this._showingAnswerDiscussion.getAttribute("data-id").indexOf(answerID) > -1 && answerID) {
                this._answersLoaded[answerID] = true;
                var answerType = eventObject.data.answerType;
                //check for external HTML documents
                if(answerType === 'HTML' && !(answerUrl.indexOf("okcsFattach") > 0)) {
                    RightNow.Ajax.makeRequest(this.data.attrs.get_okcs_data_ajax, eventObject.data, {
                        successHandler: this._displayExternalResults, scope: this, data: {answerID : answerID, type : answerType, answerUrl : answerUrl}, json: true
                    });
                }
                
                //check for IM document or external document
                else if (answerType === 'CMS-XML') {
                    RightNow.Ajax.makeRequest(this.data.attrs.get_okcs_data_ajax, eventObject.data, {
                        successHandler: this._onIMContentResponse, scope: this, data: {answerID : answerID, isAggRat : isAggRat}, json: true
                    });
                }
                //check for downloadable documents like word,ppt and txt documents
                else if (answerType !== 'CMS-XML' && answerType !== 'PDF' && answerType !== 'HTML' && answerType !== 'TEXT' && !(answerUrl.indexOf("okcsFattach") > 0)){
                    RightNow.Ajax.makeRequest(this.data.attrs.get_okcs_data_ajax, clickThruEventObject.data, {
                        successHandler: this._downloadContent, scope: this, data:{answerID : answerID , answerTitle : answerTitle},ignoreFailure: true
                    });
                }
                else {
                    var content = this.Y.Node.create("<iframe id='" + this.baseDomID + "_Iframe" + answerID + "' title='" + answerTitle + "'></iframe>");
                    content.addClass("rn_AnswerPDFSolution");
                    content.set('src', answerUrl);
                    this._showOkcsContent(answerID, content);
                    RightNow.Ajax.makeRequest(this.data.attrs.get_okcs_data_ajax, clickThruEventObject.data, {
                        ignoreFailure: true, data: clickThruEventObject, scope: this
                    });
                }
            }
        }
    },

    /**
     * Method to generate content of an OKCS document.
     * @param {Object} response Response Object
     * @param {Object} data external document meta data
     */
    _displayExternalResults: function(response, data) {
        var frameID = this.baseDomID + "_iframe" + data.answerID,
            content = this.Y.Node.create("<iframe id='" + frameID + "'></iframe>");
        content.addClass("rn_AnswerPDFSolution");
        if(response.contents.html === null) {
            content.set('src', response.contents.url);
        }
        this._showOkcsContent(data.answerID, content);
        if(response.contents.html !== null) {
            frameID = '#' + frameID;
            YUI().use('event-hover', function (Y) {
                function setHTMLData() {
                    var iframeDocument = this.getDOMNode().contentWindow.document;
                    iframeDocument.head.innerHTML += "<style type='text/css'>.ok-highlight-title {background-color: #FF0;font-weight: bold;}.ok-highlight-sentence {background-color: #EBEFF5;}</style>"
                    iframeDocument.body.innerHTML = response.contents.html;
                }
                Y.on('available', setHTMLData, frameID);
            });
        }
    },

    /**
     * Method to generate content of an OKCS document.
     * @param {Object} response Response Object
     * @param {Object} originalEventObj Event
     */
    _onIMContentResponse: function(response, originalEventObj) {
        var contentDisplay = "";
        //Error scenarios flow
        if(response.contents.error && response.contents.error.errorCode){
            var title = message = '';
            switch(response.contents.error.errorCode){
                case 'HTTP 400':
                case 'HTTP 404':
                case 'HTTP 409':
                case 'HTTP 500':
                case 'HTTP 503':
                    title = this.data.attrs.label_not_available;
                    message = this.data.attrs.label_answer_not_available_message;
                    break;
                case 'HTTP 403':
                    title = this.data.attrs.label_permission_denied;
                    message = this.data.attrs.label_no_access_message;
                    break;
            }
            contentDisplay = '<div class="rn_Hero"><div class="rn_Container"><h1>' + title + '</h1></div></div><div class="rn_PageContent rn_ErrorPage rn_Container"><p>' + message + '</p></div>';
        }
        else if(response.error !== null) {
            contentDisplay = '<div id="' + this.baseDomID + '_Error" class="rn_ErrorMessage">' + response.error + '</div>';
        }
        else {
            //Build up data array for the EJS view.
            var answerId = response.contents.answerID ? response.contents.answerID : response.id,
                metaData = response.contents.data[1];
            var data = {
                'channelView' : this.getOkcsAnswerView(response.contents.data[0].content, answerId),
                'metaView' : (metaData !== undefined ? this.getOkcsAnswerView(metaData.content, answerId) : ''),
                'attrs' : this.data.attrs
            };
            var metaArray = [],
                metaContentArray = [],
                isAggRat = this.data.attrs.custom_metadata.indexOf('aggregate_rating') !== -1,
                isAggRatAvailbale = false;
                
                if(isAggRat) {
                    if(response.contents.aggregateRating !== null && response.contents.questionsCount > 0 && response.contents.answersCount === 5) {
                        isAggRatAvailbale = true;
                    }
                }
            
            if (this.data.attrs.display_metadata) {
                metaArray = this.data.attrs.custom_metadata.split('|');
                for(var i = 0; i < metaArray.length; i++) {
                    if(isAggRatAvailbale || (!isAggRatAvailbale && metaArray[i].toLowerCase().trim() !== 'aggregate_rating')) {
                        var tempObj = {
                            'value' : this._getValueAttribute(metaArray[i],response),
                            'label' : this._getAttributeLabels(metaArray[i],response) 
                        };
                        metaContentArray.push(tempObj);
                    }
                }
            }
            
            data.metaContentArray = metaContentArray;
            contentDisplay = new EJS({text: this.getStatic().templates.displayContent}).render(data);
        }
        this._showOkcsContent(originalEventObj.answerID, contentDisplay);
    },

    /**
    * Method to display the OKCS content.
    * @param {Object} answerContent Answer details
    * @param {int} answerID Answer Id
    * @return {object} Answer view contents
    */
    getOkcsAnswerView: function(answerContent, answerID) {
        var answerView = "", currentIndex = previousDepth = 1;
        if(answerContent !== null && answerContent !== undefined) {
            for (var index in answerContent) {
                var contentAttribute = answerContent[index],
                    className = "rn_" + this.data.info.name + this.convertXpathToClassName(answerContent[index].xPath);
                if(contentAttribute.type === 'CHECKBOX') {
                    index = index.split('-')[1];
                }
                if(contentAttribute.type === 'FILE') {
                    contentAttribute.fileType = this._getAttachmentType(contentAttribute.value, answerID);
                }
                if(contentAttribute.depth !== 0) {
                    //Build up data array for the EJS view.
                    var data = {
                        'contentAttribute' : contentAttribute,
                        'className' : className,
                        'answerID' : answerID,
                        'index' : index
                    };
                    if(contentAttribute.depth > previousDepth) {
                        answerView += '<div class="rn_Indent">';
                    }
                    if(contentAttribute.depth < previousDepth) {
                        var diff = previousDepth - contentAttribute.depth;
                        for(var count = diff; count > 0; count--) {
                            answerView += '</div>';
                        }
                    }
                    answerView += new EJS({text: this.getStatic().templates.displayChannelAttribute}).render(data);
                    if(Object.keys(answerContent).length === currentIndex) {
                        for(var count = contentAttribute.depth; count > 1; count--) {
                            answerView += '</div>';
                        }
                    }
                    previousDepth = contentAttribute.depth;
                }
                currentIndex++;
            }
        }
        return answerView;
    },

    /**
    * Method to convert xPath to class name.
    * @param {String} xPath XML path value
    * @return {String} class name
    */
    convertXpathToClassName: function(xPath) {
        if(xPath === undefined)
            return false;
        var className = xPath.toLowerCase().replace(/\//g, "_").split("_");
        for (var i = 0; i < className.length; i++) {
            className[i] = "_" + className[i].charAt(0).toUpperCase() + className[i].slice(1);
        }
        return className.toString().replace(/\,/g, "");
    },

    /**
    * Method to display the OKCS content.
    * @param {int} answerID clicked ID
    * @param {Object} answerContent container of the answer content
    */
    _showOkcsContent: function(answerID, answerContent) {
        var answerWrapper = this.Y.Node.create("<span id='" + this.baseDomID + "_AnswerContent" + answerID + "' class='rn_Answer rn_AnswerDetail'></span>"),
            answerDetail = this.Y.Node.create("<span id='" + this.baseDomID + "_AnswerDetail" + answerID + "' class='rn_AnswerSolution'></span>"),
            openInNewTab = false;

        if (this._isExplorerView)
            this._generateHideButton(answerID, answerWrapper);

        if (this.data.attrs.display_inline && !this._isExplorerView)
            openInNewTab = this._generateNewTabLink(answerID, answerWrapper);

        if (!openInNewTab)
            answerWrapper.append(answerDetail.append(answerContent));

        this._showContent(answerID, 'answer', answerWrapper);
    },

    /**
    * Generate a button to hide content. This is used when the didplay view type is set to "explorer"
    * @param answerID int The answer id of the answer to toggle
    * @param answerWrapper {Object} container of the answer content
    * @return {Boolean} True if the document is to be opened in new tab else false
    */
    _generateNewTabLink: function(answerID, answerWrapper) {
        var newTabLink,
            url = this.Y.one(this.baseSelector + "_Answer" + answerID).getAttribute('data-url');
        url = RightNow.Url.addParameter(url, "session", RightNow.Url.getSession());
        if (url !== "") {
            //Build up data array for the EJS view.
            var data = {
                'url' : url,
                'text' : this.data.attrs.label_new_tab
            };
            newTabLink = new EJS({text: this.getStatic().templates.newTabLink}).render(data);
            answerWrapper.append(newTabLink);
            return true;
        }
        return false;
    },

    /**
    * Generate a button to hide content. This is used when the didplay view type is set to "explorer"
    * @param answerID int The answer id of the answer to toggle
    * @param answerWrapper {Object} container of the answer content
    */
    _generateHideButton: function(answerID, answerWrapper) {
        var buttonContainer = this.Y.Node.create("<div class='rn_ButtonContainer'></div>"),
            hideContentButton = this.Y.Node.create("<button id='" + this.baseDomID + "_HideContent" + answerID + "' class='rn_HideButton'>" + this.data.attrs.label_close_answer + "</button>"),
            url = this.Y.one(this.baseSelector + "_Answer" + answerID).getAttribute('data-href');
        url = RightNow.Url.addParameter(url, "session", RightNow.Url.getSession());
        if(this._type === 'discussion'){
            discussionNewTab = this.baseSelector + "_AnswerContent" + answerID + " .rn_OpenDiscussionLinkText a";
            if(this.Y.one(discussionNewTab).getAttribute('hidden')){
                return;
            }
            url = this.Y.one(discussionNewTab).getAttribute("href");
            this.Y.all(discussionNewTab).hide();
        }
        var newTabButton = this.Y.Node.create("<button id='" + this.baseDomID + "_NewTabButton" + answerID + "' class='rn_NewTabButton' data-url='" + url + "'>" + this._openNewTabText + "</button>");
        if(this._type === 'discussion'){
            answerWrapper.prepend(buttonContainer.append(newTabButton).append(hideContentButton));
        }
        else{
            answerWrapper.append(buttonContainer.append(newTabButton).append(hideContentButton));
        }
    },

    /**
    * Handles the toggling display of expanded answer details for view_type "explorer".
    * @param answerID int The answer id of the answer to toggle
    * @param expand boolean T to expand the answer F to hide the answer
    * @param link object The link node of the answer to toggle
    * @param answerContent object The node containing the content of the answer
    */
    _explorerViewToggleAnswerContent: function(answerID, expand, link, answerContent) {
        var list = this.Y.all(".rn_List"),
            listItems = expand ? this.Y.all(".rn_ExpandAnswer") : this.Y.all(".rn_InlineAnswerLink"),
            buttons = this.Y.all(".rn_NewTab"),
            previousSelectedAnswer = this.Y.one('a.rn_ExpandedAnswerExplorer');

        listItems.toggleClass('rn_ExpandAnswer');
        listItems.toggleClass('rn_InlineAnswersLimitedText');

        if (expand) {
            if (previousSelectedAnswer)
                previousSelectedAnswer.removeClass("rn_ExpandedAnswerExplorer");

            answerContent.removeClass('rn_Hidden');
            link.addClass("rn_ExpandedAnswerExplorer");
            answerContent.addClass("rn_ExpandedAnswerContentExplorer");
            buttons.addClass('rn_Hidden');
            list.addClass("rn_InlineAnswersExplorer");
            var answerType = link.getAttribute('data-answertype'),
                answerFrame = this.Y.one("iframe#" + this.baseDomID + "_Iframe" + answerID),
                isImHtml = (answerFrame !== null && answerType === 'HTML' && answerFrame.getDOMNode().src.indexOf("okcsFattach")) > 0 ? true : false;
            if((this._type !== 'discussion' && answerType !== 'CMS-XML' && answerType !== 'PDF' && answerType !== 'HTML') || isImHtml) {
                this.Y.one("iframe#" + this.baseDomID + "_Iframe" + answerID).getDOMNode().src = this.Y.one("iframe#" + this.baseDomID + "_Iframe" + answerID).getDOMNode().src;
            }

            if(this._type === 'discussion'){
                this._generateHideButton(answerID, this.Y.one(this.baseSelector + "_AnswerContent" + answerID))
            }
                this._hideButton = this.Y.one(this.baseSelector + "_HideContent" + answerID);
                this._hideButton.once("click", function(){
                    this._toggleContent(answerID, false);
                }, this);
                this._newTabButton = this.Y.one(this.baseSelector + "_NewTabButton" + answerID);
                this._newTabButton.on("click", this._openNewTab, this);
                this._newTabButton.focus();
        }
        else {
            link.removeClass("rn_ExpandedAnswerExplorer");
            answerContent.removeClass("rn_ExpandedAnswerContentExplorer");
            buttons.removeClass('rn_Hidden');
            list.removeClass("rn_InlineAnswersExplorer");
            answerContent.addClass("rn_AnswerHide");
            buttons.addClass('rn_AnswerHide');
        }
    },

    /**
     * Method to make the document downloadable.
     * @param {Object} response Response Object
     * @param {Object} originalEventObj Event
     */
    _downloadContent: function(response, originalEventObj) {
        var navigationUrl = JSON.parse(response.responseText).url;
        var content = this.Y.Node.create("<iframe id='" + this.baseDomID + "_Iframe" + originalEventObj.answerID + "' title='" + originalEventObj.answerTitle + "'></iframe>");
        content.addClass("rn_AnswerPDFSolution");
        content.set('src', navigationUrl);
        this._showOkcsContent(originalEventObj.answerID, content);
        content.contentWindow.open(navigationUrl, '_blank');
    },

    /**
     * Method to get the type of attachment.
     * @param {fileName} Name of the file
     */
    _getAttachmentType: function(fileName, answerID) {
        var fileType = fileName.split('.').pop().toString().toLowerCase(),
            mediaArray = [],
            mediaTypeArray = [],
            mediaArrayCount = this.data.attrs.supported_formats_media_inline.split('|').length - 1;
        if (this.data.attrs.display_media_inline === false) {
            return 'NONE';
        }
        else {
            if(((this.data.attrs.supported_formats_media_inline.split('=').length - 1) - (mediaArrayCount)) > 0){
                if(mediaArrayCount > 0) {
                    mediaArray = this.data.attrs.supported_formats_media_inline.split('|');
                    for(var i = 0; i < mediaArray.length; i++) {
                        mediaTypeArray = mediaArray[i].split("=");
                        if((mediaTypeArray[1].toLowerCase()).trim().indexOf((fileType.toLowerCase()).trim()) >= 0) {
                            return mediaTypeArray[0].trim();
                       }
                    }
                }
                else {
                    if(this.data.attrs.supported_formats_media_inline.split('=').length - 1 > 0){
                        mediaTypeArray = this.data.attrs.supported_formats_media_inline.split('=');
                        if((mediaTypeArray[1].toLowerCase()).trim().indexOf((fileType.toLowerCase()).trim()) >= 0) {
                            return mediaTypeArray[0].trim();
                        }
                    }
                    else {
                        if(this.data.js.showError) {
                            RightNow.UI.Dialog.messageDialog('Format of supported_formats_media_inline attribute value is invalid', {"icon": "WARN"});
                        }
                    }
                }
            }
            else {
                if(this.data.js.showError) {
                    RightNow.UI.Dialog.messageDialog('Format of supported_formats_media_inline attribute value is invalid', {"icon": "WARN"});
                }
            }
        }
    },
    _getValueAttribute: function(value,response) {
         var returnValue;
        switch (value.toLowerCase().trim()) {
            case "document_id":
                returnValue = response.contents.docID;
                break;
            case "version":
                returnValue = response.contents.version;
                break;
            case "status":
                returnValue = response.contents.published;
                break;
            case "display_date":
                returnValue = response.contents.publishedDate;
                break;
            case "aggregate_rating":
                returnValue = response.contents.aggregateRating;
                break;    
            case "owner":
                returnValue = response.contents.owner;
                break;   
            case "answer_id":
                returnValue = response.contents.answerId;
                break; 
            case "last_modifier":
                returnValue = response.contents.lastModifier;
                break; 
            case "last_modified":
                returnValue = response.contents.lastModifiedDate;
                break; 
            case "creator":
                returnValue = response.contents.creator;
                break;                
            default:
                returnValue = "";
        }
       
    return returnValue;
    },
    _getAttributeLabels: function(value,response) {
        switch (value.toLowerCase().trim()) {
            case "document_id":
                returnValue = this.data.attrs.label_doc_id;
                break;
            case "version":
                returnValue = this.data.attrs.label_version;
                break;
            case "status":
                 returnValue = this.data.attrs.label_status;
                break;
            case "display_date":
                 if(response.contents.published === this.data.js.published) {
                   returnValue = this.data.attrs.label_published;
                 } 
                 else {
                   returnValue = this.data.attrs.label_draft;
                 }
                break;
            case "aggregate_rating":
                 returnValue = this.data.attrs.label_aggregate_rating;
                break;
            case "owner":
                returnValue = this.data.attrs.label_owner;
                break;
            case "answer_id":
                returnValue = this.data.attrs.label_answer_id;
                break;
            case "last_modifier":
               returnValue = this.data.attrs.label_last_modifier;
                break;
            case "last_modified":
                returnValue = this.data.attrs.label_last_modified;
                break;
            case "creator":
                returnValue = this.data.attrs.label_creator;
                break;                
            default:
                returnValue = "";
        }
    return returnValue;
    }
    
});