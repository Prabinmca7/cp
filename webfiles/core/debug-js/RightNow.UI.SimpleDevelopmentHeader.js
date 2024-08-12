/**#nocode+*/
//Create boilerplate namespace for when javascript_module="none"
typeof RightNow !== 'undefined' || (RightNow = {});
'UI' in RightNow || (RightNow.UI = {});

if(RightNow.UI.DevelopmentHeader)
    throw new Error("The RightNow.UI.DevelopmentHeader namespace variable has already defined somewhere.");

RightNow.UI.DevelopmentHeader = (function(){
    var _errorCount = 0,
        _warningCount = 0,
        one = function(id) {
            return document.getElementById(id);
        },
        _updateWarningOrError = function(message, titleMessage, imageID, labelID, containerID, parentElementID) {
            one(labelID).innerHTML = titleMessage;
            var image = one(imageID);
            image.style.display = "inline";
            image.setAttribute("title", titleMessage);
            image.setAttribute("alt", titleMessage);

            var newMessage = document.createElement("li"),
                ul = one(containerID);
            newMessage.innerHTML = message;

            if (!ul) {
                ul = document.createElement("ul");
                ul.id = containerID;
                one(parentElementID).appendChild(ul);
            }
            ul.appendChild(newMessage);
            one("rn_ErrorsAndWarnings").style.display = "block";
        };
    one('rn_DevelopmentHeader').style.display = '';
    return {
        addJavascriptError: function(errorMessage) {
            var errorTitle = (++_errorCount === 1) ? RightNow.Interface.getMessage("YOU_HAVE_ONE_ERROR_PAGE_LBL") : RightNow.Text.sprintf(RightNow.Interface.getMessage("YOU_HAVE_PCT_D_ERRORS_PAGE_LBL"), _errorCount);
            _updateWarningOrError(errorMessage, errorTitle, "rn_PanelTitleErrorImage", "rn_ErrorCountLabel", "rn_ErrorInformationList", "rn_DevHeaderErrors");
            one("rn_DevHeaderErrors").className += " rn_Highlight";
        },
    
        addJavascriptWarning: function(warningMessage) {
            var warningTitle = (++_warningCount === 1) ? RightNow.Interface.getMessage("YOU_HAVE_ONE_WARNING_PAGE_LBL") : RightNow.Text.sprintf(RightNow.Interface.getMessage("YOU_HAVE_PCT_D_WARNINGS_PAGE_LBL"), _warningCount);
            _updateWarningOrError(warningMessage, warningTitle, "rn_PanelTitleWarningImage", "rn_WarningCountLabel", "rn_WarningInformationList", "rn_DevHeaderWarnings");
            one("rn_DevHeaderWarnings").className += " rn_Highlight";
        },
    
        toggleDevelopmentHeaderArea: function(divID) {
            var header = one(divID);
            if(header) {
                header.style.display = ((header.style.display === "none") ? "block" : "none");
            }
        },
    
        toggleDevelopmentHeaderText: function(divID, linkElement, linkShowContent, linkHideContent) {
            var header = one(divID), style, html;
            
            if (header.style.display === "none") {
                style = "block";
                html = linkHideContent;
            }
            else {
                style = "none";
                html = linkShowContent;
            }
            header.style.display = style;
            one(linkElement).innerHTML = html;
        }
    };
}());
/**#nocode-*/