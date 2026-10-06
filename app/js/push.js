var addedObservers = false;
var app = {
    // Application Constructor
    initialize: function() {
        document.addEventListener('deviceready', this.onDeviceReady.bind(this), false);
    },
    onDeviceReady: function() {
        this.receivedEvent('deviceready');
    },
    // Update DOM on a Received Event
    receivedEvent: function(id) {
        window.plugins.OneSignal
          .startInit("8a48f21d-c71b-4292-8a36-2d1f113e4361")
          .handleNotificationReceived(function(jsonData) {
            alert("Notification received: \n" + JSON.stringify(jsonData));
            console.log('Did I receive a notification: ' + JSON.stringify(jsonData));
          })
          .handleNotificationOpened(notificationOpenedCallback)
          .inFocusDisplaying(window.plugins.OneSignal.OSInFocusDisplayOption.Notification)
          .iOSSettings(iosSettings)
          .endInit();
            window.plugins.OneSignal.promptForPushNotificationsWithUserResponse(function(accepted) {
            console.log("User accepted notifications: " + accepted);
        });
    }
};

function postNotification() {
    window.plugins.OneSignal.getIds(function(ids) {
        var notificationObj = { contents: {en: "message body"},
        data: {"foo": "bar"},
                          include_player_ids: [ids.userId]};
        window.plugins.OneSignal.postNotification(notificationObj,
            function(successResponse) {
                console.log("Notification Post Success:", successResponse);
            },
            function (failedResponse) {
                console.log("Notification Post Failed: ", failedResponse);
                alert("Notification Post Failed:\n" + JSON.stringify(failedResponse, null, 2));
            }
        );
    });
}
app.initialize();