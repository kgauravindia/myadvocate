var addedObservers = false;

var app = {
    // Application Constructor
    initialize: function() {
        document.addEventListener('deviceready', this.onDeviceReady.bind(this), false);
    },

    // deviceready Event Handler
    //
    // Bind any cordova events here. Common events are:
    // 'pause', 'resume', etc.
    onDeviceReady: function() {
        this.receivedEvent('deviceready');
    },

    // Update DOM on a Received Event
    receivedEvent: function(id) {
        var parentElement = document.getElementById(id);
        var listeningElement = parentElement.querySelector('.listening');
        var receivedElement = parentElement.querySelector('.received');

        listeningElement.setAttribute('style', 'display:none;');
        receivedElement.setAttribute('style', 'display:block;');

        console.log('Received Event: ' + id);

        //START ONESIGNAL CODE
        //Remove this method to stop OneSignal Debugging
        window.plugins.OneSignal.setLogLevel({logLevel: 6, visualLevel: 0});
  
        var notificationOpenedCallback = function(jsonData) {
            var notificationData = JSON.stringify(jsonData)
            console.log('notificationOpenedCallback: ' + notificationData);
            var notificationID = jsonData.notification.payload.notificationID;
            console.log('notificationID: ' + notificationID);
            var notificationData = jsonData.notification.payload.additionalData.foo;
            console.log('notificationData: ' + notificationData);
        };
        // Set your iOS Settings
        var iosSettings = {};
        iosSettings["kOSSettingsKeyAutoPrompt"] = false;
        iosSettings["kOSSettingsKeyInAppLaunchURL"] = false;
               
        window.plugins.OneSignal
          .startInit("3beb3078-e0f1-4629-af17-fde833b9f716")
          .handleNotificationReceived(function(jsonData) {
            alert("Notification received: \n" + JSON.stringify(jsonData));
            console.log('Did I receive a notification: ' + JSON.stringify(jsonData));
          })
          .handleNotificationOpened(notificationOpenedCallback)
          .inFocusDisplaying(window.plugins.OneSignal.OSInFocusDisplayOption.Notification)
          .iOSSettings(iosSettings)
          .endInit();

        if (addedObservers == false) {
            addedObservers = true;

            window.plugins.OneSignal.addEmailSubscriptionObserver(function(stateChanges) {
                console.log("Email subscription state changed: \n" + JSON.stringify(stateChanges, null, 2));
            });

            window.plugins.OneSignal.addSubscriptionObserver(function(stateChanges) {
                console.log("Push subscription state changed: " + JSON.stringify(stateChanges, null, 2));
            });

            window.plugins.OneSignal.addPermissionObserver(function(stateChanges) {
                console.log("Push permission state changed: " + JSON.stringify(stateChanges, null, 2));
            });
        }
        // The promptForPushNotificationsWithUserResponse function will show the iOS push notification prompt. 
        // We recommend removing the following code and instead using an In-App Message to prompt for notification permission (See step 6)
        window.plugins.OneSignal.promptForPushNotificationsWithUserResponse(function(accepted) {
            console.log("User accepted notifications: " + accepted);
        });
    }
};

function triggerOutcome() {
    window.plugins.OneSignal.sendOutcomeWithValue("cordova", 10, function () {
        console.log("outcomes sent log");
    });
}

function triggerIAM() {
    console.log("Triggering any active IAM with Trigger value birthday is true");
    window.plugins.OneSignal.addTrigger("birthday", "true");
}

function getIds() {
    window.plugins.OneSignal.getPermissionSubscriptionState(function(status) {
        document.getElementById("OneSignalUserId").innerHTML = "UserId: " + status.subscriptionStatus.userId;
        document.getElementById("OneSignalPushToken").innerHTML = "PushToken: " + status.subscriptionStatus.pushToken;
        console.log('Player ID: ' + status.subscriptionStatus.userId);
        alert('Player ID: ' + status.subscriptionStatus.userId + "\npushToken = " + status.subscriptionStatus.pushToken);
    });
}

function sendTags() {
    window.plugins.OneSignal.sendTags({PhoneGapKey: "PhoneGapValue", key2: "value2"});
    alert("Tags Sent");
}

function getTags() {
    window.plugins.OneSignal.getTags(function(tags) {
        alert('Tags Received: ' + JSON.stringify(tags));
    });
}

function deleteTags() {
    window.plugins.OneSignal.deleteTags(["PhoneGapKey", "key2"]);
    alert("Tags deleted");
}

function promptLocation() {
    window.plugins.OneSignal.promptLocation();
    // iOS - add CoreLocation.framework and add to plist: NSLocationUsageDescription and NSLocationWhenInUseUsageDescription
    // android - add one of the following Android Permissions:
    // <uses-permission android:name="android.permission.ACCESS_FINE_LOCATION"/>
    // <uses-permission android:name="android.permission.ACCESS_COARSE_LOCATION"/>
}

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

function setEmail() {
    console.log("Setting email: " + document.getElementById("email").value);
    window.plugins.OneSignal.setEmail(document.getElementById("email").value, function() {
        console.log("Successfully set email");
    }, function(error) {
        alert("Encountered an error setting email: \n" + JSON.stringify(error, null, 2));
    });
}

function logoutEmail() {
    console.log("Logging out of email");
    window.plugins.OneSignal.logoutEmail(function(successResponse) {
        console.log("Successfully logged out of email");
    }, function(error) {
        alert("Failed to log out of email with error: \n" + JSON.stringify(error, null, 2));
    });
}

function setExternalId() {
   let externalId = document.getElementById("externalId").value;
   console.log("Setting external ID to " + externalId);

   window.plugins.OneSignal.setExternalUserId(externalId);
}

function removeExternalId() {
   console.log("Removing external ID");

   window.plugins.OneSignal.removeExternalUserId();
}

app.initialize();

/*----------------------NOTICE -----------------*/