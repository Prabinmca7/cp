/**
 * Provides functionality of rendering avatar in ejs templates
 */
RightNow.Avatar = RightNow.Widgets.extend({

    constructor: function(){
        var ejsAvatarObj = new EJS({text:
            '<span class="rn_UserAvatar" title="<%= displayName %>">' +
                '<span class="rn_Avatar <%= (className ? className : "rn_Medium") %> <%= (avatarUrl) ? "rn_Image" : "rn_Placeholder" %>">' +
                    '<% if(avatarUrl){%>' +
                        '<img itemprop="image" src="<%= avatarUrl %>" height="<%= size %>" width="<%= size %>" alt=""/>' +
                    '<%}else{%>' +
                        '<span class="rn_Default rn_DefaultColor<%= color %>" aria-hidden="true" role="presentation">' +
                            '<span class="rn_Liner"><%= text %></span>' +
                        '</span>' +
                    '<%}%>' +
                '</span>' +
            '</span>'
           });
        /**
         * Generates the avatar for users
         *
         * @param {Object} avatarParams Object constituting of various properties used for avatar generation:
         *                              'avatarUrl' {string} url of avatar provided by user
         *                              'size' {string} height and width of the avatar provided by the avatarUrl
         *                              'displayName' {string} display name of user, it is used when avatarUrl is empty
         *                              'isActive' {boolean} for non active users '!' is displayed as avatar
         *                              'classname' {string} determines the size of avatar,
         *                                                  valid options are 'rn_Small', 'rn_Medium', 'rn_Large' and 'rn_XLarge'
         * @return {String} returns the avatar html string
         */
        EJS.Helpers.prototype.getDefaultAvatar = function(avatarParams) {
            var data = avatarParams,
                text = '?',
                length = 0;
            avatarParams.displayName = RightNow.Text.stripTags(avatarParams.displayName);
            if(avatarParams.isActive) {
                if (avatarParams.displayName) {
                    text = avatarParams.displayName.charAt(0).toUpperCase();
                    length = avatarParams.displayName.length;
                }
                data.color = length % 5;
            }
            else {
                text = '!';
                data.color = 5;
                data.displayName = RightNow.Interface.getMessage("INACTIVE_LC_LBL");
                data.avatarUrl = '';
            }
            data.text = text;
            if(!avatarParams.userID) {
                data.displayName = RightNow.Interface.getMessage("UNKNOWN_LWR_LBL");
            } 
            return ejsAvatarObj.render(data);
        }
    }
});