RightNow.Widgets.QuestionDetail = RightNow.Widgets.extend({
    constructor: function() {
        var questionDetails = this.Y.one(this.baseSelector);
        if (questionDetails) {
            questionDetails.delegate("click", this._onEditClick, '.rn_EditQuestionLink', this, true);
            questionDetails.delegate("click", this._toggleEditForm, '.rn_CancelEdit', this, false);
            questionDetails.delegate("click", this._deleteQuestionConfirm, '.rn_DeleteQuestion', this);
        }

        this._deleteDialog = null;
    },

    /**
     * Calls a method to show the edit question area. 
     * Fires an event to fetch existing files attached to the question in the edit mode.
     * @param {Object} e The event details
     * @param {Boolean} showForm Whether to show
     *                           the form (T) or hide it (F)
     */
    _onEditClick: function(e, showForm) {
        editors = {};
        counter = 0;
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
                        CKEditor5.upload.Base64UploadAdapter
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
                        'redo'
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
                getEditorVal = "editors."+elementId+".getData()";
                setEditorVal = "editors."+elementId+".setData('')";
                editorVal = eval(getEditorVal);
                if( eval("document.getElementById('"+elementId+"').nextSibling.nextSibling.getAttribute('role')") == "application")
                eval("document.getElementById('"+elementId+"').nextSibling.nextSibling.remove()");
                if(!edit || !content || content=="replyToComment")    
                eval(setEditorVal);
                else
                {
                   // if (content.indexOf('"') >= 0)
                    editVal = "editors."+elementId+".setData('"+content.replace(/(["'])/g, "\\$1")+"')";            
                    eval(editVal);
                }
                } )
                .catch( err => console.error( err ) );
               }
               createEditor( questionbodyId, edit=true, questionbodydata );
        if(this.Y.one(this.baseSelector + ' .rn_FileListDisplay')) {
            if(this.Y.one(this.baseSelector + ' .rn_ExistingFileAttachments')) {
                this.Y.one(this.baseSelector + ' .rn_ExistingFileAttachments').remove();
            }

            var fileDisplayInstanceID = RightNow.Text.getSubstringAfter(this.Y.one(this.baseSelector + ' .rn_FileListDisplay').get('id'), 'rn_'),
                fileAttachments = JSON.stringify(RightNow.Widgets.getWidgetInstance(fileDisplayInstanceID)._currentAttachments);

            var eo = new RightNow.Event.EventObject(this, {data: {
                instanceID: RightNow.Text.getSubstringAfter(this.Y.one(this.baseSelector + ' .rn_SocialFileAttachmentUpload').get('id'), 'rn_'),
                fileAttachments: fileAttachments
            }});

            RightNow.Event.fire('evt_editRefreshFileAttachments', eo);
        }

        this._toggleEditForm(e, showForm);
    },

    /**
     * Shows / hides the edit question area and
     * the content display area. Focuses on
     * the first focusable element in the
     * newly-displayed area.
     * @param {Object} e The event details
     * @param {Boolean} showForm Whether to show
     *                           the form (T) or hide it (F)
     */
    _toggleEditForm: function(e, showForm) {
        var toggleElements = this.Y.one(this.baseSelector).all('.rn_QuestionEdit,.rn_QuestionInfoOptions,.rn_QuestionHeader,.rn_QuestionBody,.rn_QuestionToolbar');
        toggleElements.toggleClass('rn_Hidden');
        this.Y.one(this.baseSelector + ((showForm) ? '_QuestionEdit input' : ' .rn_QuestionActions a')).focus();
        var questionEditDiv = this.Y.one(this.baseSelector + '_QuestionEdit');
        if(showForm && !this.data.attrs.use_rich_text_input && questionEditDiv.getAttribute('data-contentType') === 'text/html') {
            questionEditDiv.one('textarea.rn_TextArea').setAttribute('readonly', 'readonly');
        }
    },

    /**
     * Event handler to submit question delete action.
     * @param {Object} e Event
     */
    _deleteQuestionConfirm: function(event) {
        var confirmElement = this.Y.Node.create('<p id="rn_' + this.instanceID + '_QuestionDetailDeleteDialogText">')
                             .addClass('rn_QuestionDetailDeleteDialog')
                             .set('innerHTML', this.data.attrs.label_delete_confirm),
            buttons = [
                        { text: this.data.attrs.label_confirm_delete_button, handler: {fn: function(){
                            this._deleteQuestion(parseInt(event.currentTarget.getAttribute('data-questionID'), 10), event.currentTarget);
                        }, scope: this}, isDefault: true},
                        { text: this.data.attrs.label_cancel_delete_button, handler: {fn: function(){
                            this._deleteDialog.hide();
                        }, scope: this}, isDefault: false}
                      ];

        this._deleteDialog = RightNow.UI.Dialog.actionDialog(
            this.data.attrs.label_delete_confirm_title,
            confirmElement,
            {
                buttons: buttons,
                // Below attribute is required to make screen reader read dialog text
                dialogDescription: 'rn_' + this.instanceID + '_QuestionDetailDeleteDialogText'
            }
        );

        this._deleteDialog.show();
    },

    /**
     * Event handler to capture edit question delete click
     * @param {Object} event The event details
     */
    _deleteQuestion: function(questionID, target) {
        if (isNaN(questionID)) return;

        var eventObj = new RightNow.Event.EventObject(this, {data: {
            w_id:       this.data.info.w_id,
            questionID: questionID
        }});
    
        if(this.data.js.f_tok){
            RightNow.Event.subscribe("evt_formTokenUpdate", RightNow.Widgets.onFormTokenUpdate, this);
            // Get a new f_tok value on each ajax request
            RightNow.Event.fire("evt_formTokenRequest", new RightNow.Event.EventObject(this, {data:{formToken:this.data.js.f_tok}}));

            eventObj.data.f_tok = this.data.js.f_tok;
        }

        if (RightNow.Event.fire('evt_deleteQuestionRequest', eventObj)) {
            this._deleteDialog.destroy();
            target.setHTML(this.data.attrs.label_deleting)
                .toggleClass('rn_DeleteQuestion', false)
                .toggleClass('rn_DeletingQuestion', true)
                .set('disabled', true);

            RightNow.Ajax.makeRequest(this.data.attrs.delete_question_ajax, eventObj.data, {
                successHandler: this._deleteQuestionResponse,
                scope:          this,
                data:           [eventObj],
                json:           true
            });
        }
    },

    /**
     * Callback after the question's been deleted.
     * @param {Object} response Response from server
     * @param {Array} args `data` passed in the options to makeRequest
     */
    _deleteQuestionResponse: function(response, args) {
        var message;
        if(!response.errors && RightNow.Event.fire('evt_deleteQuestionResponse', response, args[0])) {
            RightNow.UI.displayBanner(this.data.attrs.successfully_deleted_question_banner, { type: 'SUCCESS' }).on('close', function() {
                (args[1] || window.location).href = this.data.attrs.deleted_question_redirect_url;
            }, this);
        }
        else if(!RightNow.Ajax.indicatesSocialUserError(response)) {
            var deleteButton = this.Y.one('.rn_DeletingQuestion');

            if (response.errors) {
                message = response.errors[0].externalMessage;
            }
            else {
                RightNow.UI.displayBanner(RightNow.Interface.getMessage("THERE_WAS_PROBLEM_IN_DELETING_QUESTION_MSG"), { type: 'ERROR', focus: true });
            }
            RightNow.UI.displayBanner(message , {
                type: 'ERROR',
                focusElement: deleteButton
            });

            if (deleteButton) {
                deleteButton.setHTML(this.data.attrs.label_delete_button)
                        .toggleClass('rn_DeletingQuestion', false)
                        .toggleClass('rn_DeleteQuestion', true)
                        .set('disabled', false);
            }
        }
    }
});
