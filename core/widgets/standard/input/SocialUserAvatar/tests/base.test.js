UnitTest.addSuite({
    type:       UnitTest.Type.Widget,
    instanceID: 'SocialUserAvatar_0'
}, function (Y, widget, baseSelector) {
    var suite = new Y.Test.Suite({
        name: "standard/input/SocialUserAvatar - Gravatar & Social Options Behavior"
    });

    suite.add(new Y.Test.Case({
        name: "Functional",

        setUp: function() {
            widget.mockResponse = {target: {'src': null, 'data-fallback': null, getAttribute: widget.overrideGetAttribute}};
            widget.displaySuccess = true;

            this._copyElementAttribute = widget._copyElementAttribute;
            this.expectedValues = {
                folder: 'everyone',
                w_id: widget.data.info.w_id
            };
            widget._copyElementAttribute = function(){return;};
        },

        tearDown: function() {
            widget._copyElementAttribute = this._copyElementAttribute;
        },

        "Redirection takes place when save changes button is clicked": function() {
            var responseInfo = {"success":true,"archivedAvatar":false},
                expectedValue = {socialUser: 109, value: null, w_id: widget.data.info.w_id},
                responseObject = new RightNow.Event.EventObject(widget, {data: expectedValue});
            widget.avatarSelectionType = "default";
            widget.data.attrs.success_url_for_own_avatar = window.location.href + "#";
            UnitTest.overrideMakeRequest(widget.data.attrs.save_profile_picture_ajax, expectedValue, '_onSaveSuccess', widget, responseInfo, responseObject);
            UnitTest.overrideMakeRequest('/ci/ajaxRequest/getNewFormToken');
            widget._saveProfilePicture();
            Y.Assert.isTrue(window.location.href.indexOf("#") > -1);
        },

        "When no avatar is set a default is generated and shown": function() {
            var defaultAvatar = Y.one(baseSelector + ' .rn_PreviewImage .rn_Default');
            Y.Assert.isFalse(defaultAvatar.getComputedStyle('display') === 'none');
            Y.Assert.isTrue(Y.one(baseSelector + ' .rn_PreviewImage img').getComputedStyle('display') === 'none');
            Y.Assert.areSame('S', defaultAvatar.one('.rn_Liner').getHTML().trim());
        },

        "When the page loads the current avatar is highlighted": function() {
            Y.Assert.isTrue(Y.one(baseSelector + ' .rn_DefaultOption').hasClass('rn_ChosenAvatar'));
        },

        "When Gravatar image fails an error is displayed": function() {
            widget.displaySuccess = false;

            //When you click Apply next to Gravatar, an error symbol comes up
            widget.urlToVerify = 'https://www.gravatar.com/avatar/ca52a4de6c9903d35723d6da597e3b9e?d=404&s=256';
            var gravatarApply = Y.one(baseSelector + ' .rn_Gravatar button');
            widget._displayStatusForInput('loading', gravatarApply);
            widget._onPreviewImageError({target: 'widget.urlToVerify'});
            Y.Assert.isTrue(gravatarApply.next().hasClass('rn_Error'));
            Y.Assert.isTrue(gravatarApply.next().one('*').hasClass('rn_ExclamationCircle'));

            //After the gravatar image has failed to apply, clicking Apply for default avatar is a success with check square symbol
            var defaultApply = Y.one(baseSelector + ' .rn_DefaultOption button');
            defaultApply.simulate('click');
            Y.Assert.isTrue(defaultApply.next().hasClass('rn_Success'));
            Y.Assert.isTrue(defaultApply.next().one('*').hasClass('rn_CheckSquare'));
            widget.displaySuccess = true;
        },

    "The previous good image is used when a source fails": function() {
            widget._onPreviewImageError({target: '/bananas.png'});

            Y.Assert.areSame("#", Y.one(baseSelector).one('img').getAttribute('src'));
        },

        "Submit button is disabled during ajax request": function() {
            Y.one(baseSelector).insert(Y.Node.create('<div class="rn_FormSubmit"><button type="submit">Submit</button></div>'), 'after');
            widget.submitButton = Y.one('.rn_SaveButton');
            widget.urlToVerify = 'https://www.gravatar.com/avatar/ca52a4de6c9903d35723d6da597e3b9e?d=404&s=256';
            widget._saveProfilePicture();
            Y.Assert.isTrue(widget.submitButton.hasAttribute('disabled'));
        },


	"Text is displayed when there are no images to display": function() {
            var responseInfo = {"numberOfPages":1,"files":[]},
                responseObject = new RightNow.Event.EventObject(widget, {data: this.expectedValues});
            UnitTest.overrideMakeRequest(widget.data.attrs.submit_avatar_library_action_ajax, this.expectedValues, '_onSubmitSuccess', widget, responseInfo, responseObject);
            widget._onButtonClick();
            Y.Assert.isFalse(Y.one(baseSelector + ' .rn_AvatarLibraryForm').hasClass('rn_Hidden'));
            Y.Assert.isNotNull(Y.one(baseSelector + ' .rn_NoImages'));
        },

        "Tabs are not present when tab count is one": function() {
            var responseInfo = {"numberOfPages":1,"files":["everyone\/mountains.jpg","everyone\/kingfisher.jpg","everyone\/flower3.jpg"]},
                responseObject = new RightNow.Event.EventObject(widget, {data: this.expectedValues});
            UnitTest.overrideMakeRequest(widget.data.attrs.submit_avatar_library_action_ajax, this.expectedValues, '_onSubmitSuccess', widget, responseInfo, responseObject);
            widget._onButtonClick();
            Y.Assert.isFalse(Y.one(baseSelector + ' .rn_AvatarLibraryForm').hasClass('rn_Hidden'));
            Y.Assert.isNull(Y.one(baseSelector + ' .rn_RoleSetTabs'));
        },

        "The Avatar Library is displayed on clicking Choose button": function() {
            widget.data.attrs.avatar_library_page_size = 1;
            widget.lastPage = 5;
            widget.currentPage = 1;
            var responseInfo = {"numberOfPages":6,"files":["everyone\/mountains.jpg","everyone\/kingfisher.jpg","everyone\/flower3.jpg"]},
                responseObject = new RightNow.Event.EventObject(widget, {data: {w_id: widget.data.info.w_id}});
            widget._onSubmitSuccess(responseInfo, responseObject);
            Y.Assert.isFalse(Y.one(baseSelector + ' .rn_AvatarLibraryForm').hasClass('rn_Hidden'));
            Y.Assert.isNull(Y.one(baseSelector + ' .rn_NoImages'));
            var div = Y.one(baseSelector + " .rn_ProfilePictures .rn_UserAvatar");
            var ImageTitle = div.get('children').item(0).getAttribute('title');
            Y.Assert.areEqual(ImageTitle, "mountains");
            var PrevTitle = Y.one(baseSelector + " .rn_ImagePaginator").get('children').item(0).getAttribute('title');
            Y.Assert.areEqual(PrevTitle, "Previous");
            var title = Y.one(baseSelector + " .rn_CurrentPages").get('children').item(0).getAttribute('title');
            Y.Assert.areEqual(title, "Page 1 of 6");
        },

        "Clicking on avatar library image disables all the buttons": function() {
            widget.data.attrs.avatar_library_page_size = 1;
            widget.lastPage = 5;
            widget.currentPage = 1;
            var responseInfo = {"numberOfPages":6,"files":["everyone\/mountain_peak.jpg","everyone\/kingfisher.jpg","everyone\/flower3.jpg"]},
                responseObject = new RightNow.Event.EventObject(widget, {data: {w_id: widget.data.info.w_id}});
            widget._onSubmitSuccess(responseInfo, responseObject);
            var div = Y.one(baseSelector + " .rn_ProfilePictures").get('children').item(0);
            div.get('children').item(0).simulate('click');
            Y.Assert.isTrue(widget.submitButton.hasAttribute('disabled'));
        },

        "Close gallery button should be accessible after the last image of current page or pagination link and before save changes button": function() {
            var closeGalleryButton = Y.one(baseSelector + " .rn_ImageDisplay .rn_CloseGallery");
            var paginationDiv = Y.one(baseSelector + " .rn_ImageDisplay .rn_ImagePaginator");
            var nextPageLink = paginationDiv.get('children').item(2);
            var lastElementOfGallery = nextPageLink;
            if(widget.submitButton.hasAttribute('disabled'))
                widget.submitButton.removeAttribute('disabled');
            responseObject = new RightNow.Event.EventObject(widget);
            responseObject.keyCode = 9;
            responseObject.shiftKey = false;
            responseObject.preventDefault = function(){};
            lastElementOfGallery.focus();
            widget._onTabKeyDown(responseObject);
            // focus should shift to close gallery button from next page link when Tab key is pressed
            Y.Assert.areSame(Y.one(document.activeElement),closeGalleryButton);
            closeGalleryButton.focus();
            widget._onTabKeyDown(responseObject);
            // focus should shift to save changes button from close gallery link when Tab key is pressed
            Y.Assert.areSame(Y.one(document.activeElement),widget.submitButton);
            responseObject.shiftKey = true;
            widget.submitButton.focus();
            widget._onTabKeyDown(responseObject);
            // focus should shift to close gallery button from save changes button when Shift + Tab key is pressed
            Y.Assert.areSame(Y.one(document.activeElement),closeGalleryButton);
            closeGalleryButton.focus();
            widget._onTabKeyDown(responseObject);
            // focus should shift to next page link from close gallery button when Shift + Tab key is pressed
            Y.Assert.areSame(Y.one(document.activeElement),lastElementOfGallery);
        },

        "Updating Archived Avatar brings up the dialog box": function() {
            var responseInfo = {"archivedAvatar":true},
                expectedValue = {socialUser: 109, value: null, w_id: widget.data.info.w_id},
                responseObject = new RightNow.Event.EventObject(widget, {data: expectedValue});
            widget.avatarSelectionType = "default";
            UnitTest.overrideMakeRequest(widget.data.attrs.save_profile_picture_ajax, expectedValue, '_onSaveSuccess', widget, responseInfo, responseObject);
            UnitTest.overrideMakeRequest('/ci/ajaxRequest/getNewFormToken');
            widget._saveProfilePicture();
            Y.assert(!Y.one('#rnDialog1').ancestor('.yui3-panel-hidden'));
        },

        "Clicking cancel checks for facebook token presence": function () {
            // calling cancel when facebook token check is false
            RightNow.Url.navigate = RightNow.Event.createDelegate(this, function(url) {
                Y.Assert.isTrue(url.indexOf('app/home') > -1, "Cancel redirection url is incorrect. Actual: " + url);
            });
            widget._cancelProfilePicture();

            // calling cancel when facebook token check is true
            var responseObject = new RightNow.Event.EventObject(widget, {data: {w_id: widget.data.info.w_id}});
            widget.data.js.ftokenPresent = true;
            UnitTest.overrideMakeRequest(widget.data.attrs.cancel_profile_picture_ajax,
                null, '_onCancel', widget, {"success" : true}, responseObject);
            widget._cancelProfilePicture();
        }
    }));

    return suite;
}).run();
