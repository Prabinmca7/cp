/**
 * Provide a template for requiredLabel, which is used throughout many widgets
 */
RightNow.RequiredLabel = (function() {
    var ejsRequiredObj = new EJS({text:
        '<span class="rn_Required" aria-label="<%= requiredLabel %>"><%= requiredMarkLabel %></span>'
    });

    /**
     * Generates a preformatted required label
     *
     * @param  {String=} requiredLabel       Screenreader text to read. Defaults to 'Required'
     * @param  {String=} requiredMarkLabel   Screen text displayed to end users '*'
     * @return {String}                      Formatted requiredLabel string
     */
    EJS.Helpers.prototype.getRequiredLabel = function(requiredLabel, requiredMarkLabel) {
        return ejsRequiredObj.render({
            'requiredLabel':     requiredLabel || RightNow.Interface.getMessage("REQUIRED_LBL"),
            'requiredMarkLabel': requiredMarkLabel || RightNow.Interface.getMessage("FIELD_REQUIRED_MARK_LBL")});
    };
});