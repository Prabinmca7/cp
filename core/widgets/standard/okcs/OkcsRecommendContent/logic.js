RightNow.Widgets.OkcsRecommendContent = RightNow.SearchConsumer.extend({
    overrides: {
        constructor: function() {
            this.parent();
            this.messageBox = this.Y.one(this.data.attrs.message_element ? this.Y.one('#' + this.data.attrs.message_element) : null);
            this._contentDiv = this.Y.one(this.baseSelector + '_RecommendContent');
            this._contentTypeList = this.data.js.contentTypes;
            this._selectedContentType = this.data.js.selectedContentType;
            this._isRecommendChange = this.data.js.isRecommendChange;
            this._recommendationButton = this.Y.one(this.baseSelector + '_RecommendContentButton');
            var recommendationSubmit = this.Y.one(this.baseSelector + '_RecommendationSubmit');
            var recommendationCancel = this.Y.one(this.baseSelector + '_RecommendationCancel');
            this._contentType = this.Y.one(this.baseSelector + '_ContentType');
            this._priority = this.Y.one(this.baseSelector + '_Priority');
            this._recommendationForm = this.Y.one(this.baseSelector + '_RecommendForm');
            this._charactersRemainingDiv = this.Y.one(this.baseSelector + '_CharacterRemaining');
            this._charactersRemainingDiv.set('innerHTML', this.data.attrs.default_maxlength_value + " " + this.data.attrs.label_characters_remaining);
            this._contentDescriptionValue = '';
            this._charactersRemaining = '';
            this._isDropDownPopulated = false;
            this._eo = new RightNow.Event.EventObject(this);
            this._statusMessage = this.Y.one(this.baseSelector + "_StatusMessage");
            this.fileAttachmentUpload = this.Y.one(this.baseSelector + "_FileInput");
            this.fileRemoveLink = this.Y.one(this.baseSelector + "_Remove");
            
            if(this._recommendationButton)
                this._recommendationButton.on('click', this._onRecommendationButtonClick, this);

            if(recommendationSubmit)
                recommendationSubmit.on('click', this._onRecommendationSubmit, this);

            if(recommendationCancel)
                recommendationCancel.on('click', this._onRecommendationCancel, this);

            if(this.fileAttachmentUpload)
                this.fileAttachmentUpload.on('change', this._fileExtension, this);

            if(this.fileRemoveLink)
                this.fileRemoveLink.on('click', this._hideRemoveLink, this);
            
            this._contentDescription = this.Y.one(this.baseSelector + '_Description');
            if(this._contentDescription)
                this._contentDescription.on('keyup', this._updateCharacterCount, this);

            if(this._priority)
                this._priority.on('change', this._onPriorityChange, this);

            if(this.data.js.sources) {
                this.searchSource().setOptions(this.data.js.sources);
                this.searchSource().on('response', this._onContentTypeChanged, this);
            }
        }
    },

    /**
    * Event handler executed when recommendation button is clicked
    */
    _onRecommendationButtonClick: function() {
        this._recommendationButton.set("disabled", "true");
        this._errorDisplay = this.Y.one(this.baseSelector + '_ErrorLocation');
        var simpleText = this.Y.one(this.baseSelector + '_Description');

        if(!this._statusMessage.hasClass("rn_Hidden")) {
            this._statusMessage.addClass("rn_Hidden");
        }
        if(this._errorDisplay) {
            this._errorDisplay.set("innerHTML", '').removeClass('rn_MessageBox rn_ErrorMessage');
        }
        var caseNumber = this.Y.one(this.baseSelector + '_CaseNumber');
        if(caseNumber !== null ){caseNumber.set('value', '');}
        this.Y.one(this.baseSelector + '_Title').set('value', this.data.js.title);
        var priority = this.Y.one(this.baseSelector + '_Priority');
        if(priority !== null ) {
             priority.set('value', 'None');
        }
        if(this.Y.one(this.baseSelector + "_FileInput") !== null){
             this.Y.one(this.baseSelector + "_FileInput").set('value', '');
             this.fileRemoveLink.addClass("rn_Hidden");
        }
        this.Y.one(this.baseSelector + '_RecommendationSubmit').set("disabled", false);
        this._charactersRemainingDiv.set('innerHTML', this.data.attrs.default_maxlength_value + " " + this.data.attrs.label_characters_remaining);

        if(simpleText)
            simpleText.set('value', '');

        if(this._contentTypeList === undefined && !this._isRecommendChange) {
            var eventObject = new RightNow.Event.EventObject(this, {data: {contentTypeList: this.data.attrs.content_type_list}});
            RightNow.Ajax.makeRequest(this.data.attrs.retrieve_content_type_ajax, eventObject.data, {
                successHandler: this._showContentTypes,
                json: true, scope: this
            });
            this._isDropDownPopulated = true;
        }
        else {
            this._recommendationButton.addClass('rn_Hidden');
            this._recommendationForm.removeClass('rn_Hidden');
        }
        if(this.Y.one('.rn_DropDown')) {
            if(this._selectedContentType !== '')
                this.Y.one('.rn_DropDown').addClass('rn_Hidden');
            else {
                if(this._contentTypeList !== undefined) {
                    if((this._contentType !== undefined && this._contentType.getDOMNode().children.length < 1 && 
                            this._contentTypeList.length !== 0 && !this._isDropDownPopulated)) {
                        this._showContentTypes(this._contentTypeList);
                        this._isDropDownPopulated = true;
                    }
                    else if(this._contentTypeList.length === 0 && !this._isDropDownPopulated) {
                        RightNow.UI.displayBanner(this.data.attrs.label_failure_msg, { type: 'ERROR' });
                        this._recommendationForm.addClass('rn_Hidden');
                        this._recommendationButton.removeClass('rn_Hidden');
                        this._recommendationButton.removeAttribute('disabled');
                        return;
                    }
                }
                if(this._contentType !== undefined){
                    this._contentType.set('selectedIndex', 0);
                }
                this.Y.one('.rn_DropDown').removeClass('rn_Hidden');
            }
        }
    },
    
    /**
     * Content Type AJAX call success handler.
     * @param {Object} response Response Object
     */
    _showContentTypes: function(response){
        if(response.length === 0) {
            RightNow.UI.displayBanner(this.data.attrs.label_failure_msg, { type: 'ERROR' })
        }
        else if(!response.failure && this._contentType !== null) {
            this._contentTypeList = response;
            this.Y.Array.each(this._contentTypeList, function(currentNode){
                this._contentType.appendChild(this.Y.Node.create('<option value="' + currentNode.referenceKey + '" >' + currentNode.name + '</option>'));
            }, this);
            this._recommendationButton.addClass('rn_Hidden');
            this._recommendationForm.removeClass('rn_Hidden');
        }
        this._recommendationButton.removeAttribute('disabled');
    },
    
    /**
     * This method is called to create content recommendation in info manager
     * @param {array} contentData Array containg content recommendation data
     */
    createRecommendation: function(contentData) {
        var caseNumberRef = this.Y.one(this.baseSelector + '_CaseNumber');
        var priorityRef = this.Y.one(this.baseSelector + '_Priority');
        var hasAttachment = false;
        if(this.Y.one(this.baseSelector + "_FileInput").get('value') !== ''){
           var uploadedAttachment = this.Y.one(this.baseSelector + "_FileInput");
           var fileName = uploadedAttachment.get('value');
           var file = uploadedAttachment._node.files[0];
           var formElement = uploadedAttachment.ancestor('form');
           var origEncType = formElement.get('enctype');
           formElement.set('enctype', 'multipart/form-data').set('encoding', 'multipart/form-data');
           this._eo.data.filename = uploadedAttachment.get('value');
           hasAttachment = true;
        }

        var contentType,
            recordId,
            referenceKey,
            name,
            selectedContentType = this._selectedContentType,
            isRecommendChange = this._isRecommendChange,
            caseNumber = caseNumberRef != null ? caseNumberRef.get('value') : '',
            title = this.Y.one(this.baseSelector + '_Title').get('value'),
            priority = priorityRef != null ? priorityRef.get('value') : 'None',
            errorHtml = '';
        if(selectedContentType === '' && !isRecommendChange)
            selectedContentType = this.Y.one(this.baseSelector + '_ContentType').get('value');
        this.Y.Array.each(this._contentTypeList, function(currentNode){
            if (currentNode.referenceKey === selectedContentType) {
                contentType = currentNode;
                recordId = contentType.recordId;
                referenceKey = contentType.referenceKey;
                name = contentType.name;
            }
        }, this);
        
        var eventObject = new RightNow.Event.EventObject(this, {data: {
            caseNumber: caseNumber,
            title: title,
            priority: priority,
            comments: contentData.comments,
            isRecommendChange: isRecommendChange,
            contentTypeRecordId: recordId,
            contentTypeReferenceKey: referenceKey,
            contentTypeName: name,
            answerId: this.data.js.answerId,
            contentRecordId: this.data.js.contentRecordID,
            documentId: this.data.js.docID,
            versionID: this.data.js.versionID,
            r_tok: this.data.js.r_tok
        }});

        if(hasAttachment){
            eventObject.data.fileName = fileName;
            RightNow.Ajax.makeRequest("/ci/okcsAjaxRequest/createRecommendationWithAttachment", eventObject.data, {
                successHandler: this._displayRecommendationSubmissionMessage,
                data: eventObject,
                json: true,
                scope: this,
                type : "POST",
                timeout: (RightNow.Interface.getConfig("CP_FILE_UPLOAD_MAX_TIME") || 300) * 1000,
                upload: formElement.get('id')
            });
            formElement.set('enctype', origEncType).set('encoding', origEncType);
       }else{
            RightNow.Ajax.makeRequest(this.data.attrs.create_recommendation_ajax, eventObject.data, {
                successHandler: this._displayRecommendationSubmissionMessage,
                json: true, scope: this
        });
       }
    },

    /**
     * Validates file extensions
     * @param filename
     */
    _validateFileExtension: function(fileName) {
        this._validExtensions = this.data.js.valid_file_extensions.toLowerCase().replace(' ', '').split(',');

        var index = fileName.lastIndexOf('.'),
            fileExtension = (index !== -1 && index !== (fileName.length - 1))
                ? fileName.substring(index + 1).toLowerCase()
                : null,
            extensionIsValid = fileExtension && this.Y.Array.indexOf(this._validExtensions, fileExtension) > -1;

        if (extensionIsValid) {
            return true;
        }
        this._setStatusMessage(RightNow.Text.sprintf(this.data.attrs.label_invalid_extension, '.' + this._validExtensions.join(", .")), "alert", null);

        return false;
    },

    /**
     * Sets the content and attributes of the Status Message span element
     * @param {String}  message           Message to set
     * @param {String}  role              The value to be set for "role" attribute
     * @param {number}  tabIndex          The value to be set for "tabIndex" attribute
     */
    _setStatusMessage: function(message, role, tabIndex) {
        this._statusMessage.set('innerHTML', message);
        if(this._statusMessage.hasClass("rn_Hidden")){
           this._statusMessage.removeClass("rn_Hidden");
        }
        if(!this.fileRemoveLink.hasClass("rn_Hidden")){
           this.fileRemoveLink.addClass("rn_Hidden");
        }
        role ? this._statusMessage.setAttribute("role", role) : this._statusMessage.removeAttribute("role");
        tabIndex ? this._statusMessage.setAttribute("tabIndex", tabIndex) : this._statusMessage.removeAttribute("tabIndex");
    },

    /**
     * It is invoked when file is uploaded in the attachment field
     */
    _fileExtension: function(){
       var uploadedAttachment = this.Y.one(this.baseSelector + "_FileInput");
       var fileName = uploadedAttachment.get('value');
       var file = uploadedAttachment._node.files[0];
       var isFileVaild = true;
       if(fileName !== ""){
           isFileVaild = this._validateFileExtension(file.name);
       }
       if(!isFileVaild) {
           if(this.Y.one(this.baseSelector + "_FileInput") !== null){
               this.Y.one(this.baseSelector + "_FileInput").set('value', '');
           }
          return;
       }
       if(isFileVaild){
           if(!this._statusMessage.hasClass("rn_Hidden")){
               this._statusMessage.addClass("rn_Hidden");
           }
           if(this.fileRemoveLink.hasClass("rn_Hidden")){
               this.fileRemoveLink.removeClass("rn_Hidden");
           }
       }
       if(fileName.length === 0){
           this.fileRemoveLink.addClass("rn_Hidden");
       }
    },

    /**
     * This method is invoked when remove link is clicked to remove the file from the attachment field
     */
    _hideRemoveLink: function(){
        if(this.Y.one(this.baseSelector + "_FileInput") !== null){
             this.Y.one(this.baseSelector + "_FileInput").set('value', '');
         }
         if(!this.fileRemoveLink.hasClass("rn_Hidden"))
             this.fileRemoveLink.addClass("rn_Hidden");

         if(!this._statusMessage.hasClass("rn_Hidden"))
             this._statusMessage.addClass("rn_Hidden");
    },
    
    /**
     * Displays Recommendation submission message.
     * @param {Object} response Response from the recommendation submit AJAX request.
     */
    _displayRecommendationSubmissionMessage: function(response){
        this._toggleLoadingIndicators(false);
        if(response.failure) {
            this.Y.one(this.baseSelector + '_RecommendationSubmit').set("disabled", false);
        }
        else if(response.validationError) {
            var error = '<div><b>' + this.data.attrs.label_recommend_description + " : " + this.data.attrs.label_maxlength_error + '</b></div>';
            if(this._errorDisplay) {
                this._errorDisplay.set("innerHTML", error);
                this._errorDisplay.addClass('rn_MessageBox rn_ErrorMessage');
                this.Y.one(this.baseSelector + '_RecommendationSubmit').set("disabled", false);
            }
        }
        else if(response.error){
            var error = '<div><b>' + response.error + '</b></div>';
            if(this._errorDisplay) {
                this._errorDisplay.set("innerHTML", error);
                this._errorDisplay.addClass('rn_MessageBox rn_ErrorMessage');
                this.Y.one(this.baseSelector + '_RecommendationSubmit').set("disabled", false);
            }
        }
        else {
            this.displayMessage(this.data.attrs.label_success_msg);
            this._recommendationForm.addClass('rn_Hidden');
            this._recommendationButton.removeClass('rn_Hidden');
        }
        this._recommendationButton.removeAttribute('disabled');
    },

    /**
     * Displays success message in message box above widget or as user specified div.
     * @param message String Message to display.
     */
    displayMessage: function(message) {
        if(this.messageBox) {
            this.messageBox.setStyle("opacity", 0).addClass("rn_MessageBox");
            this.messageBox.transition({
                opacity: 1,
                duration: 0.4
            });
            this.messageBox.set('innerHTML', message);
            RightNow.UI.updateVirtualBuffer();
            this.messageBox.set('tabIndex', 0);
            this.messageBox.focus();
        }
        else {
            RightNow.UI.displayBanner(message, {focus: true});
        }
        RightNow.Event.fire("evt_clearRichTextInput");
    },

    /**
     * Event handler executed when recommendation is submitted
     * @param {Object} e Event
     */
    _onRecommendationSubmit: function(e) {
        e.halt();

        if(this._validateFormData()) {
            this._toggleLoadingIndicators();
            this.Y.one(this.baseSelector + '_RecommendationSubmit').set("disabled", "true");
            this.createRecommendation({'comments' : this._contentDescriptionValue});
        }
    },

    /**
     * Validates form data.
     * @return Boolean if the form validated successfully
     */
    _validateFormData: function() {
        var contentTypeIsNotEmpty = true;
        if(this._errorDisplay) {
            this._errorDisplay.set("innerHTML", "").removeClass('rn_MessageBox rn_ErrorMessage');
        }
        if(this._selectedContentType === '' && !this._isRecommendChange){
            var contentTypeNode = this.Y.one(this.baseSelector + '_ContentType');
            contentTypeIsNotEmpty = this._validateInputFields(contentTypeNode, this.data.attrs.label_recommend_content_type);
        }
        var titleNode = this.Y.one(this.baseSelector + '_Title'),
            descriptionNode = this.Y.one(this.baseSelector + '_Description'),
            titleIsNotEmpty = this._validateInputFields(titleNode, this.data.attrs.label_recommend_title),
            descriptionIsNotEmpty = this._validateInputFields(descriptionNode, this.data.attrs.label_recommend_description);
        this._contentDescriptionValue = descriptionNode.get('value');

        return contentTypeIsNotEmpty && titleIsNotEmpty && descriptionIsNotEmpty;
    },

    /**
     * Utility function to validate the given input fields.
     * @param inputField HTMLElement the recommendation field to validate
     * @param label String the field's label that is used within error messages.
     * @return Boolean if the input field validated successfully
     */
    _validateInputFields: function(inputField, label) {
        var inputFieldValue = inputField.get('value'),
            id = inputField.get('id');
        if (inputFieldValue === ''){
            this._addErrorMessage(label + this.data.js.fieldIsRequired, id);
            return false;
        }
        return true;
    },

    /**
     * Adds an error message to the page and adds the correct CSS classes
     * @param message String The error message to display
     * @param focusElement HTMLElement The HTML element to focus on when the error message link is clicked
     */
    _addErrorMessage: function(message, focusElement) {
        if(this._errorDisplay !== '') {
            this._errorDisplay.addClass('rn_MessageBox rn_ErrorMessage');
            //add link to message so that it can receive focus for accessibility reasons
            var newMessage = '<a href="javascript:void(0);" onclick="document.getElementById(\'' + focusElement + '\').focus(); return false;">' + message + '</a>';
            var oldMessage = this._errorDisplay.get("innerHTML");
            if (oldMessage !== "")
                newMessage = oldMessage + '<br/>' + newMessage;
            this._errorDisplay.set("innerHTML", newMessage);
            this._errorDisplay.one('a').focus();
            this._errorDisplay.one("h2") ? this._errorDisplay.one("h2").setHTML(RightNow.Interface.getMessage("ERRORS_LBL")) : this._errorDisplay.prepend("<h2>" + RightNow.Interface.getMessage("ERROR_LBL") + "</h2>");
            this._errorDisplay.one("h2").setAttribute('role', 'alert');
        }
    },

    /**
     * Event handler executed when recommendation is cancelled
     * @param {Object} e Event
     */
    _onRecommendationCancel: function(e) {
        this._recommendationForm.addClass('rn_Hidden');
        this._recommendationButton.removeAttribute('disabled');
        this._recommendationButton.removeClass('rn_Hidden');
        this._charactersRemainingDiv.set('innerHTML', this.data.attrs.default_maxlength_value + " " + this.data.attrs.label_characters_remaining);
    },

    /**
     * Event handler executed on click of content type
     * @param {String} type Event type
     * @param {Object} args Arguments passed with event
     */
    _onContentTypeChanged: function(type, args) {
        this._recommendationButton.removeClass('rn_Hidden');
        this._recommendationButton.removeAttribute('disabled');
        this._recommendationForm.addClass('rn_Hidden');
        this._selectedContentType = args[0].data && args[0].data.selectedChannel;
        var isRecommendationAllowed = false;
        if(args[0].data && args[0].data.isRecommendationAllowed) {
            isRecommendationAllowed = true;
            //Check for exclusion list based on the value of content_type_list attribute
            if(this.data.attrs.content_type_list && this.data.attrs.content_type_list.indexOf(this._selectedContentType) === -1)
                isRecommendationAllowed = false;
        }
        if (this._contentTypeList !== undefined) {
            for (var i = 0; this._contentTypeList[i]; i++) {
                if (this._contentTypeList[i].referenceKey === this._selectedContentType) {
                    isRecommendationAllowed = true;
                }
            }
        }
        if(this._selectedContentType === '') {
            isRecommendationAllowed = true;
        }
        isRecommendationAllowed ? this._contentDiv.removeClass('rn_Hidden') : this._contentDiv.addClass('rn_Hidden');
    },

    /**
     * Updates the text for the ARIA alert div that appears above search rating
     * @param {String} text The text to update the div with
     */
    _updateAriaAlert: function(text) {
        this._ariaAlert = this._ariaAlert || this.Y.one(this.baseSelector + '_Alert');
        if(this._ariaAlert) {
            this._ariaAlert.set('innerHTML', text);
        }
    },

    /**
     * Hides / shows the status message.
     * @param {Boolean} turnOn Whether to turn on the loading indicators (T),
     * remove the loading indicators (F), or toggle their current state (default) (optional)
     */
    _toggleLoadingIndicators: function(turnOn) {
        var classFunc = ((typeof turnOn === "undefined") ? "toggleClass" : ((turnOn === true) ? "removeClass" : "addClass")),
            message = this.Y.one(this.baseSelector + "_StatusMessage");
        if (message) {
            message[classFunc]("rn_Hidden").setAttribute("aria-live", (message.hasClass("rn_Hidden")) ? "off" : "assertive");
        }
        this.Y.one(this.baseSelector + "_StatusMessage").addClass('rn_OkcsSubmit');
    },
    
    /**
     * Updates the number of characters entered in description field
     */
    _updateCharacterCount: function() {
        this._charactersRemaining = this.data.attrs.default_maxlength_value - this._contentDescription.get('value').length;
        this._charactersRemainingDiv.set('innerHTML', this._charactersRemaining + " " + this.data.attrs.label_characters_remaining)
    },

    /**
     * Method to be called on priority selection
     */
    _onPriorityChange: function() {
        if(this._priority.get('selectedIndex') === 1) {
            this._priority.set('selectedIndex', 0);
        }
    }
});
