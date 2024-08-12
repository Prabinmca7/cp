UnitTest.addSuite({
    type: UnitTest.Type.Widget,
    instanceID: 'FileAttachmentUpload_0'
}, function(Y, widget, baseSelector){
    var tests = new Y.Test.Suite({
        name: 'standard/input/FileAttachmentUpload'
    });

    tests.add(new Y.Test.Case({
        name: 'UI behavior',

        setUp: function() {
            this.fakeInput = Y.Node.create('<input type="text" id="fakeInput"/>');
            this.fakeFileInput = Y.Node.create('<input type="file" id="fakeFileInput"/>');
            Y.one('form').append(this.fakeInput);
            this.origMakeRequest = RightNow.Ajax.makeRequest;
            RightNow.Ajax.makeRequest = RightNow.Event.createDelegate(this, this.makeRequest);
            this.madeRequest = false;
        },

        tearDown: function() {
            Y.one('#fakeInput').remove();
            Y.one('#fakeFileInput').remove();
            RightNow.Ajax.makeRequest = this.origMakeRequest;
        },

        makeRequest: function(url, data, options) {
            this.successCallback = options.successHandler;
            this.failureCallback = options.failureHandler;
            this.madeRequest = true;
            this.originalEventObject = options.data;
            this.callbackContext = options.scope;
        },

        'Loading indicator appears when uploading': function() {
            widget._onFileAdded({target: this.fakeFileInput.set('files', this.getFileList())});
            Y.assert(this.madeRequest);
            Y.assert(!Y.one(baseSelector + '_LoadingIcon').hasClass('rn_Hidden'));
            Y.assert(!Y.one(baseSelector + '_FileInput').disabled == true);
            Y.assert(!Y.one(baseSelector + '_StatusMessage').hasClass('rn_ScreenReaderOnly'));
        },

        "'Choose File' button is disabled when max attachments reached": function() {
            var originalDisabled = widget.input.get('disabled'),
                originalMaxAttachments = widget.data.attrs.max_attachments;

            widget.input.set('disabled', false);
            Y.Assert.isFalse(widget.input.get('disabled'));
            widget.data.attrs.max_attachments = 1;
            this.successCallback.call(this.callbackContext, {name: 'cantaloupe.txt', tmp_name: 'cantaloupe', type: 'text/plain', size: 100}, this.originalEventObject);
            Y.Assert.isTrue(widget.input.get('disabled'));

            // Restore
            Y.one(baseSelector + ' a').simulate('click');
            widget.input.set('disabled', originalDisabled);
            widget.data.attrs.max_attachments = originalMaxAttachments;
        },

        'A thumbnail image is displayed after upload': function() {
            var imageFile = {
                    imgSrc: "data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///ywAAAAAAQABAAACAUwAOw==",
                    name: "altName"
                },
                fakeReader = { readAsDataURL: function(){ }, result: imageFile.imgSrc },
                self = this;

            widget._loadThumbnail(imageFile, fakeReader, function (img) {
                self.resume(function () {
                    Y.Assert.areSame("1", img.getAttribute("height"));
                    Y.Assert.areSame("1", img.getAttribute("width"));
                    Y.Assert.areSame(imageFile.name, img.getAttribute("alt"));
                });
            });
            fakeReader.onload({ target: { result: imageFile.imgSrc } });
            this.wait();
        },

        'Loading indicator is removed and file names are added upon upload': function() {
            this.successCallback.call(this.callbackContext, {name: 'bananas.txt', tmp_name: 'bananas', type: 'text/plain', size: 1000}, this.originalEventObject);
            Y.assert(Y.one(baseSelector + '_LoadingIcon').hasClass('rn_Hidden'));
            Y.assert(Y.one(baseSelector + '_StatusMessage').hasClass('rn_ScreenReaderOnly'));
            Y.Assert.areSame(1, Y.one(baseSelector).one('ul').all('li').size());
            Y.assert(Y.one(baseSelector + ' ul li').getHTML().indexOf('bananas.txt') > -1);
            Y.assert(Y.one(baseSelector + ' a').getHTML().indexOf(widget.data.attrs.label_remove) > -1);
        },

        'File is removed from list upon click of removal link': function() {
            Y.one(baseSelector + ' a').simulate('click');
            Y.Assert.areSame(0, Y.one(baseSelector).one('ul').all('li').size());
        },

        'Error messages display when an error is returned from the server': function() {
            widget._onFileAdded({target: this.fakeFileInput.set('files', this.getFileList())});
            widget._uploading = true;
            Y.assert(!Y.one(baseSelector + '_LoadingIcon').hasClass('rn_Hidden'));
            Y.assert(!Y.one(baseSelector + '_StatusMessage').hasClass('rn_ScreenReaderOnly'));
            this.successCallback.call(this.callbackContext, {error: 10}, this.originalEventObject);
            Y.assert(Y.one('#rnDialog1 #rn_Dialog_1_Message'));
            Y.assert(Y.one(baseSelector + '_LoadingIcon').hasClass('rn_Hidden'));
            Y.assert(Y.one(baseSelector + '_StatusMessage').hasClass('rn_ScreenReaderOnly'));
            Y.Assert.isFalse(widget._uploading);
            Y.one('#rnDialog1 .yui3-widget-ft button').simulate('click');
        },

        'The loading indicator is removed when there is a request failure': function() {
            widget._onFileAdded({target: this.fakeFileInput.set('files', this.getFileList())});
            Y.assert(!Y.one(baseSelector + '_LoadingIcon').hasClass('rn_Hidden'));
            Y.assert(!Y.one(baseSelector + '_StatusMessage').hasClass('rn_ScreenReaderOnly'));
            this.failureCallback.call(this.callbackContext,{'suggestedErrorMessage': 'errorMessage'});
        },

        'A masked filename has the real filename displayed - short path': function() {
            this.validateMaskBehavior('bananas.txt', 'bananas.txt', this);
        },

        'A masked filename has the real filename displayed - full path Windows': function() {
            this.validateMaskBehavior('c:\\path\\bananas.txt', 'bananas.txt', this);
        },

        'A masked filename has the real filename displayed - full path non-Windows': function() {
            this.validateMaskBehavior('/nfs/users/jwatson/bananas.txt', 'bananas.txt', this);
        },

        getFileList: function(content = 'This is test content', fileName = 'test.txt', type = 'text/plain') {
            var list = new DataTransfer();
            var file = new File([content], fileName, {type: type});
            list.items.add(file);
            return list.files;
        },

        validateMaskBehavior: function(fullPath, filename) {
            this.originalEventObject.data.filename = fullPath;
            this.successCallback.call(this.callbackContext, {
                name: '*******.txt',
                tmp_name: 'bananas',
                type: 'txt',
                size: 1000
            }, this.originalEventObject);
            Y.Assert.areSame(1, Y.one(baseSelector).one('ul').all('li').size());
            Y.assert(Y.one(baseSelector + ' ul li').getHTML().indexOf(filename) > -1);
            Y.assert(Y.one(baseSelector + ' a').getHTML().indexOf(widget.data.attrs.label_remove) > -1);
            Y.one(baseSelector + ' a').simulate('click');
            Y.Assert.areSame(0, Y.one(baseSelector).one('ul').all('li').size());
        },

        cancelEvent: function() {
            return false;
        },

        'Input element is cleared if upload request event is cancelled': function() {
            var originalInput = widget.input;
            widget.input = this.fakeInput;
            this.fakeInput.set('value', 'someFile');
            widget._uploading = false;
            widget._loading.addClass('rn_Hidden');
            var eventHandle = RightNow.Event.subscribe("evt_fileUploadRequest", this.cancelEvent);
            widget._sendUploadRequest();
            eventHandle.detach();
            Y.Assert.areSame('', this.fakeInput.get('value'));
            Y.Assert.isFalse(widget._uploading);
            Y.Assert.isTrue(widget._loading.hasClass('rn_Hidden'));
            widget.input = originalInput;
        },

        'Input element is cleared if upload response event is cancelled': function() {
            var originalInput = widget.input;
            widget.input = this.fakeInput;
            this.fakeInput.set('value', 'someFile');
            widget._uploading = true;
            widget._loading.removeClass('rn_Hidden');
            var eventHandle = RightNow.Event.subscribe("evt_fileUploadResponse", this.cancelEvent);
            widget._fileUploadReturn({}, { data: { filename: "" } });
            eventHandle.detach();
            Y.Assert.areSame('', this.fakeInput.get('value'));
            Y.Assert.isFalse(widget._uploading);
            Y.Assert.isTrue(widget._loading.hasClass('rn_Hidden'));
            widget.input = originalInput;
        },

        'Error message is displayed if upload is still going': function() {
            // Insert error div
            var errorLocation = Y.Node.create('<div id="hereBeErrors" />'),
                originalInput = widget.input;
            Y.one(document.body).prepend(errorLocation);
            widget.input = this.fakeInput;
            this.fakeInput.set('value', 'someFile');

            widget._uploading = true;
            var result = widget._onValidateUpdate('type', [{data: {error_location: 'hereBeErrors'}}]);
            Y.Assert.isFalse(result);
            Y.Assert.isTrue(widget._uploading);
            Y.Assert.isTrue(Y.one(baseSelector + '_Label').hasClass('rn_ErrorLabel'));
            Y.Assert.isTrue(widget.input.hasClass('rn_ErrorField'));
            Y.Assert.isTrue(errorLocation.getHTML().indexOf(widget.data.attrs.label_still_uploading_error) !== -1);

            widget._uploading = false;
            widget.input = originalInput;
            widget.toggleErrorIndicator(false);
            errorLocation.remove(true);
        }
    }));

    tests.add(new Y.Test.Case({
        name: 'required validation',

        'An error message displays when a required attachment has not been submitted': function() {
            // Insert error div
            var errorLocation = Y.Node.create('<div id="hereBeErrors" />');
            Y.one(document.body).prepend(errorLocation);
            // Instantiate the form. When the form is created all of the deferred events and added fields will be re-added to the new form
            new RightNow.Form({ attrs: { error_location: 'hereBeErrors' }, js: {f_tok: 'filler'}}, widget.instanceID, Y);

            var eventFired = false;
            RightNow.Event.on('evt_formFieldValidateFailure', function() {
                eventFired = true;
            });

            RightNow.Form.find(baseSelector, widget.instanceID).fire('collect', new RightNow.Event.EventObject(this, {data: {
                form: 'banana',
                error_location: 'hereBeErrors'
            }}));

            Y.Assert.isTrue(eventFired);
            Y.Assert.isTrue(Y.one(baseSelector).one('label').hasClass('rn_ErrorLabel'));
            Y.Assert.isTrue(Y.one(baseSelector).one('input').hasClass('rn_ErrorField'));
            Y.Assert.areSame(1, errorLocation.get('childNodes').size());
            Y.Assert.areSame(-1, errorLocation.one('a').getHTML().indexOf('%s'));

            errorLocation.remove(true);
        }
    }));

//    tests.add(new Y.Test.Case({
//        name: 'Duplicated file name handling',
//
//        'Counter is added': function() {
//            widget._attachments = [{userName: 'fileName.txt' },
//                                   {userName: '13.5_fileName.txt' }];
//
//            var output = widget._renameDuplicateFilename('fileName.txt');
//            Y.Assert.areSame("fileName1.txt", output);
//        },
//
//        'Counter keeps incrementing': function() {
//            widget._attachments = [
//                {userName: 'a.bfileName.txt' },
//                {userName: 'a.bfileName1.txt' },
//                {userName: 'a.bfileName2.txt' },
//                {userName: 'a.bfileName3.txt' }
//            ];
//
//            var output = widget._renameDuplicateFilename('a.bfileName.txt');
//            Y.Assert.areSame("a.bfileName4.txt", output);
//        },
//
//        "Counter is tacked onto end of files without extensions": function() {
//            widget._attachments = [
//                {userName: 'file' },
//                {userName: 'file1' }
//            ];
//
//            var output = widget._renameDuplicateFilename('file');
//            Y.Assert.areSame("file2", output);
//        }
//    }));

//    tests.add(new Y.Test.Case({
//        name: 'Test constraint change',
//        setUp: function() {
//            this.errorDiv = 'hereBeErrors';
//            Y.one(document.body).append('<div id="hereBeErrors">');
//        },
//
//        "Changing the requiredness should update the label and remove old error messages": function() {
//            //Check the labels. They should NOT contain an asterisk or screen reader text.
//            var errorDiv = Y.one('#' + this.errorDiv),
//                labelContainer = Y.one(baseSelector + '_LabelContainer'),
//                validationData = [{data: {error_location: 'hereBeErrors'}}],
//                requiredFunction = (widget.data.attrs.min_required_attachments > 0) ? 'isFalse' : 'isTrue';
//
//            Y.Assert.areSame('', errorDiv.get('innerHTML'));
//            Y.Assert.isTrue(labelContainer.get('text').indexOf(widget.data.attrs.label_input) !== -1);
//            Y.Assert[requiredFunction](labelContainer.get('text').indexOf('*') === -1);
//
//            //Alter the requiredness. Labels should be added.
//            widget.fire('constraintChange:min_required_attachments', { constraint : 6});
//
//            Y.Assert.isTrue(labelContainer.one('.rn_Required').getAttribute('aria-label').indexOf('Required') !== -1);
//            Y.Assert.isTrue(labelContainer.get('text').indexOf('*') !== -1);
//
//            //Submitting the form should cause the fields to be highlighted and an error message added
//            widget._onValidateUpdate('validate', validationData);
//
//            Y.Assert.isTrue(errorDiv.get('childNodes').size() === 1);
//            Y.Assert.isTrue(labelContainer.one('label').hasClass('rn_ErrorLabel'));
//            Y.Assert.isTrue(widget.input.hasClass('rn_ErrorField'));
//
//            //Altering the requiredness again should remove labels and messages
//            widget.fire('constraintChange:min_required_attachments', { constraint: 0});
//            Y.Assert.isTrue(labelContainer.get('text').indexOf('*') === -1);
//            Y.Assert.isNull(labelContainer.one('.rn_Required'));
//
//            Y.Assert.isTrue(errorDiv.get('childNodes').size() === 0);
//            Y.Assert.isFalse(labelContainer.one('label').hasClass('rn_ErrorLabel'));
//            Y.Assert.isFalse(widget.input.hasClass('rn_ErrorField'));
//        }
//    }));


    return tests;
});
UnitTest.run();
