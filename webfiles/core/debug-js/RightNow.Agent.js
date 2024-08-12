/**#nocode+*/
if(RightNow.Agent) throw new Error("The RightNow.Agent namespace variable has already been defined somewhere.");
/**
 * RightNow.Agent
 *
 * @namespace Contains functions to handle interops between the agent console and the GuidedAssistant widget.
 */
RightNow.Agent = {
    /**
     * RightNow.Agent.GuidedAssistant
     *
     * @namespace Contains functions to handle the GuidedAssistant widget
     */
    GuidedAssistant: (function()
    {
        /**
         * Fires event for a guide to go to the specified response.
         * @param {number} guideID The guide the response is in
         * @param {number} questionID The parent question of the response
         * @param {number} responseID The response to navigate to
         * @inner
         */
        var _goToResponse = function(guideID, questionID, responseID) {
            var eo = new RightNow.Event.EventObject();
            eo.data = {"guideID": guideID, "responseID": responseID, "questionID": questionID};
            RightNow.Event.fire("evt_GuidedAssistanceGoToResponse", eo);
        },

        /**
         * Fires event for a guide to go to the specified question.
         * @param {number} guideID The guide the question is in
         * @param {number} questionID The question to navigate to
         * @inner
         */
        _goToQuestion = function(guideID, questionID) {
            var eo = new RightNow.Event.EventObject();
            eo.data = {"guideID": guideID, "questionID": questionID};
            RightNow.Event.fire("evt_GuidedAssistanceGoToQuestion", eo);
        },

        /**
         * Fires event when an answer is viewed in the agent console.
         * @param {number} guideID The guide the answer is in
         * @param {number} questionID The question the answer is in
         * @param {number} responseID The response the question is in
         * @param {number} The ID of the answer that is viewed
         * @inner
         */
        _answerViewed = function(guideID, questionID, responseID, answerID) {
            var eo = new RightNow.Event.EventObject();
            eo.data = {"guideID": guideID, "responseID": responseID, "questionID": questionID, "answerID": answerID};
            RightNow.Event.fire("evt_GuidedAssistanceAnswerViewed", eo);
        },
    
        /**
         * Interop call to add the specified question to a chat.
         * @param {string} evt Event name
         * @param {Object} args Event data
         * @inner
         */
        _addQuestionToChat = function(evt, args) {
            var questionID = args[0].data.questionID,
                guideID = args[0].data.guideID;
            if(questionID && guideID) {
                window.external.Guide.AddQuestionToChat(guideID, questionID);
            }
        },
    
        /**
         * Interop call to add the specified resolution to a chat.
         * @param {string} evt Event name
         * @param {Object} args Event data
         * @inner
         */
        _addResolutionToChat = function(evt, args) {
            var questionID = args[0].data.questionID,
                guideID = args[0].data.guideID,
                responseID = args[0].data.responseID;
            if(questionID && guideID && responseID){
                window.external.Guide.AddResolutionToChat(guideID, questionID, responseID);
            }
        },
    
        /**
         * Interop call to notify of a response selection.
         * @param {string} evt Event name
         * @param {Object} args Event data
         * @inner
         */
        _responseSelected = function(evt, args) {
            var questionID = args[0].data.questionID,
                responseID = args[0].data.responseID,
                guideID = args[0].data.guideID,
                responseValue = args[0].data.responseValue;
            if(questionID && responseID) {
                window.external.Guide.ResponseSelected(guideID, questionID, responseID, responseValue);
            }
        },
    
        /**
         * Interop call to notify that a guide was loaded.
         * @param {string} evt Event name
         * @param {Object} args Event data
         * @inner
         */
        _guideLoaded = function(evt, args) {
            var guideID = args[0].data.guideID;
            if(guideID) {
                window.external.Guide.GuideLoaded(guideID);
            }
        };

        //Only setup handlers if we have agent navigation
        if(window.external && window.external.Guide) {
            //Guided Assistant handlers coming from the .NET world
            window.external.Guide.GoToResponse = _goToResponse;
            window.external.Guide.GoToQuestion = _goToQuestion;
            window.external.Guide.AnswerViewed = _answerViewed;

             //Guided Assistant events to subscribe to for interaction with the .NET world
            RightNow.Event.subscribe("evt_GuideAddQuestionToChat", _addQuestionToChat, this);
            RightNow.Event.subscribe("evt_GuideAddResolutionToChat", _addResolutionToChat, this);
            RightNow.Event.subscribe("evt_GuideResponseSelected", _responseSelected, this);
            RightNow.Event.subscribe("evt_GuideLoaded", _guideLoaded, this);
        }
    }())
};
/**#nocode-*/
