if(!RightNow.Chat || !RightNow.Chat.Model) {
RightNow.namespace("RightNow.Chat.Model");
/**
 * Contains chat model operations
 * @namespace
 */
RightNow.Chat.Model = {
    /** all the various operations an end user can perform. */
    ChatEndUserAction: {
        LOGON               : 'LOGON',
        LOGOFF              : 'LOGOFF',
        GETUPDATE           : 'GETUPDATE',
        SEND_TEXT           : 'SEND_TEXT',
        ACTIVITY_STATUS     : 'ACTIVITY_STATUS',
        PROACTIVE_QUERY     : 'PROACTIVE_QUERY',
        COBROWSE            : 'COBROWSE',
        NOTIFY_FATTACH      : 'NOTIFY_FATTACH',
        FATTACH_UPLOAD      : 'FATTACH_UPLOAD',
        OUT_OF_BAND_DATA    : 'OUT_OF_BAND_DATA',
        VIDEOCHAT           : 'VIDEOCHAT'
    }, 
    /** all the various states a Chat can possibly be in. */
    ChatState: {
        UNDEFINED    : 0,
        SEARCHING    : 1,
        CONNECTED    : 2,
        REQUEUED     : 3,
        CANCELLED    : 4,
        DEQUEUED     : 5,
        DISCONNECTED : 6,
        RECONNECTING : 7
    },
    /** All the states of chat engagement */
    ChatCreateEngagementResumeCode: {
        NONE             : 'NONE',
        RESUME           : 'RESUME',
        DO_NOT_RESUME    : 'DO_NOT_RESUME'
    },
    /** State of chat activity */
    ChatActivityState: {
        LISTENING   : 'LISTENING',
        RESPONDING  : 'RESPONDING',
        ABSENT      : 'ABSENT'
    },
    /** The sneak preview state */
    ChatSneakPreviewState: {
        NONE                : 'NONE',
        ENABLED             : 'ENABLED',
        DISABLED            : 'DISABLED',
        SITE_UNAVAILABLE    : 'SITE_UNAVAILABLE',
        SERVICE_UNAVAILABLE : 'SERVICE_UNAVAILABLE'
    },
    /** State of chat participation */
    ChatParticipantConnectionState: {
        ABSENT          : 'ABSENT',
        ACTIVE          : 'ACTIVE',
        DISCONNECTED    : 'DISCONNECTED'
    },
    /** Reason that chat has concluded */
    ChatConclusionReason: {
        ENDED_USER_CANCEL       : 'ENDED_USER_CANCEL',
        ENDED_USER_DEFLECTED    : 'ENDED_USER_DEFLECTED'
    },
    /** Reason that Chat has disconnected */
    ChatDisconnectReason: {
        AGENT_CONCLUDED         : 'AGENT_CONCLUDED',
        END_USER_CONCLUDED      : 'END_USER_CONCLUDED',
        QUEUE_TIMEOUT           : 'QUEUE_TIMEOUT',
        IDLE_TIMEOUT            : 'IDLE_TIMEOUT',
        EJECTED                 : 'EJECTED',
        TRANSFERRED_TO_QUEUE    : 'TRANSFERRED_TO_QUEUE',
        PARTICIPANT_LEFT        : 'PARTICIPANT_LEFT',
        NO_AGENTS_AVAILABLE     : 'NO_AGENTS_AVAILABLE',
        BROWSER_UNSUPPORTED     : 'BROWSER_UNSUPPORTED',
        ENDED_USER_CANCEL       : 'ENDED_USER_CANCEL',
        ENDED_USER_DEFLECTED    : 'ENDED_USER_DEFLECTED',
        FAIL_NO_AGENTS_AVAIL    : 'FAIL_NO_AGENTS_AVAIL',
        CONFREE_DISCONNECTED    : 'CONFREE_DISCONNECTED'
    },
    /** CoBrowse Types */
    ChatCoBrowseType: {
        SCREEN_POINTER      : 'SCREEN_POINTER',
        SCREEN              : 'SCREEN',
        MOUSE_NAVIGATION    : 'MOUSE_NAVIGATION',
        DESKTOP_CONTROL     : 'DESKTOP_CONTROL'
    },
    /** All CoBrowse Status Codes */
    ChatCoBrowseStatusCode: {
        ACCEPTED    : 'ACCEPTED',
        DECLINED    : 'DECLINED',
        UNAVAILABLE : 'UNAVAILABLE',
        TIMEOUT     : 'TIMEOUT',
        STARTED     : 'STARTED',
        STOPPED     : 'STOPPED',
        ERROR       : 'ERROR'
    },
    /** Set of communication methods */
    CommunicationMethod: {
        AJAX_POST   : 'AJAX_POST',
        YUI_GET     : 'YUI_GET',
        RNW_REDIRECT: 'RNW_REDIRECT',
        XDOMAIN_REQUEST : 'XDOMAIN_REQUEST'
    },
    /** VideoChat Types  */
    ChatVideoChatType: {
        SCREEN_POINTER       : 'SCREEN_POINTER',
        SCREEN               : 'SCREEN',
        MOUSE_NAVIGATION     : 'MOUSE_NAVIGATION',
        DESKTOP_CONTROL      : 'DESKTOP_CONTROL'
    },
    /** All VideoChat Status Codes */
    ChatVideoChatStatusCode: {
        ACCEPTED    : 'ACCEPTED',
        DECLINED    : 'DECLINED',
        UNAVAILABLE : 'UNAVAILABLE',
        TIMEOUT     : 'TIMEOUT',
        STARTED     : 'STARTED',
        STOPPED     : 'STOPPED',
        ERROR       : 'ERROR',
        ABORTED     : 'ABORTED'
    },
    /** Browser navigation types */
    NavigationType: {
        NAVIGATE_AWAY : 'NAVIGATE_AWAY',
        ON_PAGE  : 'ON_PAGE'
    }
};

/**
 * @constructor
 */
RightNow.Chat.Model.Agents = function()
{
    this._agents = {};
};
RightNow.Chat.Model.Agents.prototype = {
    _leadAgentID: null,
    /**
     * Adds an agent
     * @param {number} clientID
     * @param {string} name
     * @param {string} greeting
     * @param {boolean} isLead
     * @return {Object} The agent that was added
     */
    addAgent: function(clientID, name, greeting, isLead)
    {
        var agent = {
            name: name,
            greeting: greeting,
            clientID: clientID
        };

        this._agents[clientID] = agent;

        if(isLead)
            this._leadAgentID = clientID;

        return agent;
    },

    /**
     * Retrieves an agent
     * @param {number} clientID
     * @return {Object} The agent specified by clientID
     */
    getAgent: function(clientID)
    {
        return this._agents[clientID];
    },

    /**
     * Retrieves the lead agent ID
     * @return {number|null} The lead agent
     */
    getLeadAgentID: function()
    {
        return this._leadAgentID;
    },

    /**
     * Sets the lead agent ID
     * @param {number} clientID
     */
    setLeadAgentID: function(clientID)
    {
        this._leadAgentID = clientID;
    },

    /**
     * Sets the activity status
     * @param {number} clientID
     * @param {number} activityStatus
     */
    setActivityStatus: function(clientID, activityStatus)
    {
        this._agents[clientID].activityStatus = activityStatus;
    }
};

(function(){
    var Model = RightNow.Chat.Model;
    Model.Agents = new Model.Agents();
    Model.EndUser = {
        activityStatus: Model.ChatActivityState.LISTENING,
        firstName: "",
        lastName: "",
        servletSessionID: "",
        email: ""
    };
}());
}
