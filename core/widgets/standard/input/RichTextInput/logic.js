RightNow.Widgets.RichTextInput = RightNow.Field.extend({
    overrides: {
        constructor: function() {
            this.parent();
            this.data.readOnly = this.data.attrs.read_only;
            this.data.label = this.data.attrs.label_input;
            this.loadingIcon = this.Y.one(this.baseSelector + '_LoadingIcon');
            this.editor = this.baseDomID + '_ckeditor';
            this.hasChanged = false;
            var editors = {};
            var that  = this;
            if(this.data.attrs.sub_id == "body" || !this.data.attrs.sub_id)
            {   
            this.Y.Get.js(this.data.js.ckeditorPath, function(){
                that.loadCKEditor(false);
            });
            }
            else if(this.data.attrs.sub_id == "newCommentBody")
            {
            if(typeof CKEditor5 !== 'undefined' )    
            that.loadCKEditor(false);
            else{
                this.Y.Get.js(this.data.js.ckeditorPath, function(){
                    that.loadCKEditor(false);
                });
            }
            }
            this._subscribeToFormValidation();
        },

        /**
         * Returns the field's value.
         * @return {Object} HTML Field value
         */
        getValue: function() {
           if(this.data.attrs.sub_id == "newCommentBody")
           {
            activeEditorId = newCommentEditorId;
           }
           else if(this.data.attrs.sub_id == "inlineCommentBody")
           {
            activeEditorId = inlineCommentEditorId;
           }
           else if(this.data.attrs.sub_id == "body")
           {
            activeEditorId = bodyEditorId;
           }
           if(eval("editors." + activeEditorId + ".getData()")) 
            editorVal = "editors." + activeEditorId + ".getData()";
            return { text: eval(editorVal) };
        }
    },

    /**
     * Load CKEditor
     */
    loadCKEditor: function(edit, content){
        this.loadingIcon.removeClass("rn_Hidden");
    editors = {};
    function createEditor( elementId , edit , content ) {
        return CKEditor5.editorClassic.ClassicEditor
            .create( document.getElementById( elementId ) 
            , {
                plugins: [
                    CKEditor5.essentials.Essentials,
                    CKEditor5.autoformat.Autoformat,
                    CKEditor5.blockQuote.BlockQuote,
                    CKEditor5.basicStyles.Bold,
                    CKEditor5.basicStyles.Underline,
                    CKEditor5.basicStyles.Strikethrough,
                    CKEditor5.heading.Heading,
                    CKEditor5.image.Image,
                    CKEditor5.image.ImageCaption,
                    CKEditor5.image.ImageStyle,
                    CKEditor5.image.ImageToolbar,
                    CKEditor5.image.ImageUpload,
                    CKEditor5.indent.Indent,
                    CKEditor5.indent.IndentBlock,
                    CKEditor5.basicStyles.Italic,
                    CKEditor5.link.Link,
                    CKEditor5.list.List,
                    CKEditor5.font.Font,
                    CKEditor5.mediaEmbed.MediaEmbed,
                    CKEditor5.paragraph.Paragraph,
                    CKEditor5.table.Table,
                    CKEditor5.table.TableToolbar,
                    CKEditor5.codeBlock.CodeBlock,
                    CKEditor5.basicStyles.Code,
                    CKEditor5.upload.Base64UploadAdapter,
                    CKEditor5.sourceEditing.SourceEditing
                ],
                toolbar: [
                    '|',
                    'heading',
                    '|',
                    'bold',
                    'italic',
                    'underline',
                    'Strikethrough',
                    'link',
                    'code',
                    'fontSize', 'fontFamily',
                    {
                    label: 'Font color',
                    icon: 'plus',
                    items: [ 'fontColor', 'fontBackgroundColor' ]
                    },
                    'bulletedList',
                    'numberedList',
                    '|',
                    'outdent',
                    'indent',
                    '|',
                    'uploadImage',
                    'blockQuote',
                    'insertTable',
                    'mediaEmbed',
                    'codeBlock',
                    '|',
                    'undo',
                    'redo',
                    'sourceEditing'
                ],
                image: {
                    toolbar: [
                        'imageStyle:inline',
                        'imageStyle:block',
                        'imageStyle:side',
                        '|',
                        'imageTextAlternative'
                    ]
                },
                table: {
                    contentToolbar: [
                        'tableColumn',
                        'tableRow',
                        'mergeTableCells'
                    ]
                }
            }
            )
            .then( editor => {
            editors[ elementId ] = editor;      
            getEditorVal = "editors." + elementId + ".getData()";
            setEditorVal = "editors." + elementId + ".setData('')";
            editorVal = eval(getEditorVal);
            if(!edit || !content || content == "replyToComment")    
            eval(setEditorVal);
            else
            {
                editVal = 'editors.' + elementId + '.setData("' + content.replace(/(["'])/g, "\\$1") + '")';            
                eval(editVal);
            }
            } )
            .catch( err => console.error( err ) );
           }
           if(this.data.attrs.sub_id == "newCommentBody")
           {
             newCommentEditorId = this.editor;
             createEditor( this.editor, edit = false);
           }
           else if(this.data.attrs.sub_id == "inlineCommentBody" && edit && content != "newCommentBody" && content != "replyToComment" )
           {
            inlineCommentEditorId = this.editor;
            createEditor( this.editor, edit = true , content );
           }
           else if(this.data.attrs.sub_id == "body")
           {
            bodyEditorId = this.editor;
            createEditor( this.editor, edit = false );
            questionbodydata = this.data.js.initialValue;
            questionbodyId = this.editor;
           }
           else if(!this.data.attrs.sub_id)
           {
            activeEditorId = this.editor;
            createEditor( this.editor );
           }
           else if (content == "newCommentBody" || content == "replyToComment")
           {
            if(content == "replyToComment")
            {
            inlineCommentEditorId = this.editor;
            activeEditorId = this.editor;
            }
            else 
            activeEditorId = newCommentEditorId;
            createEditor( activeEditorId, edit = false);
           }
           else
           createEditor( newCommentEditorId, edit = false);
           this.loadingIcon.addClass("rn_Hidden");
    },

    /**
     * preventing data uri
     * @param {object} editor loaded event
     */
    preventDataURI : function(ev) {
        this.Y.delegate('input', function(e) {
            var target = e.currentTarget,
                input = target.get('value'),
                uriPattern = new RegExp("data:\\s*image\\s*/", "i");
            if(target.ancestor("table").get("className") === 'cke_dialog_ui_hbox' && uriPattern.test(input)) {
                if (target.get('parentNode').next(".rn_CkeCustomUriValidation") === null) {
                    var errorDiv = this.Y.Node.create('<div>');
                    errorDiv.set('innerHTML', this.data.attrs.label_data_uris_error);
                    errorDiv.addClass('rn_CkeCustomUriValidation');
                    errorDiv.addClass('rn_CkeDialogInputError');
                    target.get('parentNode').insert(errorDiv, 'after');
                }
                target.set('value', '');
                target.focus();
            }
            else {
                if(target.get('parentNode').next(".rn_CkeCustomUriValidation")) {
                    target.get('parentNode').next(".rn_CkeCustomUriValidation").remove();
                }
            }
        }, this.Y.one("body"), "." + ev.editor.id + " " + 'input.cke_dialog_ui_input_text', this);
    },
    /**
     * Sets the label text.
     * @param {String} newLabel label text to set
     */
    setLabel: function(newLabel) {
        if (newLabel) {
            this.data.label = newLabel;
            this.Y.one(this.baseSelector + ' label .rn_LabelInput').set('text', newLabel);
        }
    },

    /**
     * Returns the label text.
     * @return {String} Label's text
     */
    getLabel: function() {
        return this.data.label;
    },

    /**
     * Reloads the editor portion of the widget
     * with the given contents.
     * @param  {String}  content  New HTML content
     * @param  {boolean} readOnly True if editor should be placed in read only mode
     */
    reload: function(content, readOnly, editor) {
        this.data.readOnly = typeof readOnly === 'undefined' ? this.data.attrs.read_only : readOnly;
        if(this.data.attrs.sub_id == "inlineCommentBody" && content)// && !this.data.js.initialValue)
        {
        this.data.js.initialValue = content; 
        editorDestroy = "editors." + newCommentEditorId + ".destroy()"; 
        eval(editorDestroy);   
        edit = true;
        this.loadCKEditor(edit = true,content);
        edit = false;
        }
        else if(this.data.attrs.sub_id == "inlineCommentBody" && !content)
        {
            if(eval("editors." + newCommentEditorId))
            {
            editorDestroy = "editors." + newCommentEditorId + ".destroy()";
            eval(editorDestroy);
            edit = true;
            this.loadCKEditor(edit = true, content = "replyToComment"); 
            }
        }
        else
        {
            editorDestroy = "editors." + activeEditorId + ".destroy()";
            setEditorVal = "editors." + activeEditorId + ".setData('')";
            if(eval("editors." + activeEditorId)) 
            eval(setEditorVal);
        }
        this._subscribeToFormValidation();
        this._toggleErrorIndicator(false);
    },

    /**
     * Destroy ckeditor instance if it exists
     */
    destroy: function(){
        editorDestroy = "editors." + activeEditorId + ".destroy()";
        eval(editorDestroy);
        editorType = "newCommentBody";
        this.loadCKEditor(false,editorType);
    },
    /**
     * Destroy ckeditor instance if it exists when user cancels his request
     */
    reset: function(){
        var ckeditor = this.editor;
        editorDestroy = "editors." + this.editor + ".destroy()";
        eval(editorDestroy);
        editorType = "newCommentBody";
        this.loadCKEditor(false,editorType);
    },
    /**
     * Subscribes to the parent form's 'submit' event
     * in order to do validation. Subscribes only if
     * `this.data.readOnly` is false and the event hasn't
     * already been subscribed to.
     */
    _subscribeToFormValidation: function() {
        if (this._subscribedToFormValidation) return;

        this.parentForm().on('submit', this.onValidate, this);
        this._subscribedToFormValidation = true;
    },

    /**
     * Displays an error message for the field.
     * @param  {String} message       Message to display
     * @param  {String} errorLocation ID in which to inject the message
     */
    _displayError: function(message, errorLocation) {
        var commonErrorDiv = this.Y.one('#' + errorLocation),
            label = this.getLabel();

        if (this.data.attrs.label_error_fieldname) {
            label = this.data.attrs.label_error_fieldname;
        }
        else if (this.data.attrs.name === 'CommunityQuestion.Body') {
            label = RightNow.Interface.getMessage('QUESTION_LBL');
        }
        else if (this.data.attrs.name === 'CommunityComment.Body') {
            label = RightNow.Interface.getMessage('COMMENT_LBL');
        }

        if (commonErrorDiv) {
            if (message.indexOf("%s") > -1) {
                message = RightNow.Text.sprintf(message, label);
            }
            else if (!RightNow.Text.beginsWith(message, label)) {
                message = (label + ' ' + message);
            }

            commonErrorDiv.append("<div><b><a href='javascript:void(0);' onclick='document.getElementById(\"" +
                this.baseDomID + '_Editor' + "\").getElementsByTagName(\"iframe\")[0].contentWindow.document.body.focus(); return false;'>" + message + "</a></b></div>");

        }
        this._toggleErrorIndicator(true);
    },

    /**
     * Turns the error indicators on and off.
     * @param  {Boolean} show T to show; F to hide
     */
    _toggleErrorIndicator: function(show) {
        var method = (show) ? 'addClass' : 'removeClass';
        this.Y.on('available',
                  function(){
                      this[method]('rn_ErrorField');},
                  this.baseSelector + '_Editor iframe');
        this.Y.one(this.baseSelector + '_Label')[method]('rn_ErrorLabel');
    },

    /**
     * Event handler executed when form is being submitted.
     * @param {String} type Event name
     * @param {Array} args Event arguments
     * @return {object|boolean} Event object if validation passes,
     *                                False otherwise
     */
    onValidate: function(type, args) {
        var eventObject = this.createEventObject(),
            globalEvent = 'evt_formFieldValidate',
            result = 'Pass',
            errors = [],
            error_location = args[0].data.error_location,
            label = this.getLabel(),
            value = this.getValue(),
            errorMessage;
        eventObject.data.value = value.text;
        this._toggleErrorIndicator(false);

        var stripValue = RightNow.Text.stripTags(value.text, '<img>');
        stripValue = stripValue.replace(/\&nbsp\;/gi, '');

        if(this.Y.Lang.trim(stripValue) === '') {
            value.text = this.Y.Lang.trim(stripValue);
        }
        if (value.error) {
            this._displayError(value.error, error_location);
            result = 'Failure';
        }
        else if (!this.validate(errors, this.Y.Lang.trim(value.text))) {
            this.Y.Array.each(errors, function(error) {
                this._displayError(error, error_location);
            }, this);
            result = 'Failure';

            if (!(eventObject.data.value = this.Y.Lang.trim(value.text) ? value.text : '') && this.data.contentWasPruned) {
                this._displayError(this.data.attrs.label_content_stripped, error_location);
            }
        }

        RightNow.Event.fire(globalEvent + result, eventObject);

        return result === 'Pass' ? eventObject : false;
    },

    /**
     * Set the value in the field
     * @param {String} content to set in the editor
     */
    setValue: function(content) {
        this.getInstance().setData(content, function()
            { this.getInstance(); });
    },

    /**
     * Returns the editor instance
     * @return {Object}
     */
    getInstance: function() {
        return CKEDITOR.instances[this.editor];
    },

    /**
     * Returns a range for the editor's document object (i.e. a logical representation of a piece of content in the editors's DOM)
     * @return {object}
     */
    getRange: function() {
        return new CKEDITOR.dom.range(this.getInstance().document);
    },

    /**
     * Inserts given content in the editor in the specified mode and range
     * @param {string} content
     * @param {string} mode
     * @param {object} range
     */
    insertHtml: function(content, mode, range) {
        this.getInstance().insertHtml(content, mode, range);
    }
});
