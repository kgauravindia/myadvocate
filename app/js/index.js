var app = {
    init: function() {
		//document.addEventListener("deviceready", initAd, false);
		
    },
	onDeviceReady: function() {
		StatusBar.backgroundColorByHexString('#330000');
		alert(StatusBar);
    }
};
app.init();

	
function randomString( string_length =15) {
	var chars = "0123456789ABCDEFGHIJKLMNOPQRSTUVWXTZabcdefghiklmnopqrstuvwxyz";
	var randomstring = '';
	for (var i=0; i<string_length; i++) {
		var rnum = Math.floor(Math.random() * chars.length);
		randomstring += chars.substring(rnum,rnum+1);
	}
	return randomstring;
	}
	
var base_url = 'https://myadv.in';	
// $.get(base_url+'&task=get_version', function(data){
	// localStorage.setItem('myadv_version',data);
// });
		
function shot()
{
	alert("Sharing")
  var msg ="Hi, *MyAdvocateAI* helps me solve all types of legal queries. *Download Now*";
  var url ="https://myadv.in/app/";
   var imageLink = 'https://myadv.in/images/app-download-img.png';
  window.plugins.socialsharing.share(msg, imageLink, null, url);
}

function sharenow()
{
	alert("Sharing")
  var msg ="Hi, *MyAdvocateAI* helps me solve all types of legal queries. *Download Now*";
  var url ="https://myadv.in/app/";
    var imageLink = 'https://myadv.in/images/app-download-img.png';
    window.plugins.socialsharing.share(msg, null, imageLink, url);
 }

function openBrowser(url) {
   var target = '_blank';
   var options = "hideurlbar=yes,zoom=no,location=yes";
   var ref = cordova.InAppBrowser.open(url, target, options);	
}

function applaunch( app )
{
	window.plugins.launcher.launch({packageName:app}, alert('success'), alert('fail'));
	//window.plugins.launcher.launch({packageName:'com.facebook.katana'}, successCallback, errorCallback)
}

$("#apparea").on('click','.inapp-link',function(){
		if (navigator.connection.type == Connection.NONE) {
			navigator.notification.alert('No Internet Connection', ref.close(), 'Offline', 'Close');
		}
		else{
			var url = this.getAttribute('data-url'); 
			var target = '_blank';
			var ref = cordova.InAppBrowser.open( url, '_blank', 'location=yes,zoom=no,closebuttoncolor=#ececfb,toolbarcolor=#313140,hideurlbar=yes');
			ref.show();
			// ref.addEventListener('exit', exitCallback);
			// function exitCallback() {
			  // window.location='index.html';
			// }
		}
	});
	
 $("#apparea").on('click',function()
	{
		var networkState = navigator.connection.type;
   		if (networkState == Connection.NONE) {
			alert("No Internet Connection");
		}
	});
	
function pay()
{
	var txn =randomString(15);
	var ref =randomString(10);
	let config = {
			"payeeVPA": "offerplant@upi", // VPA no from UPI payment acc
			"payeeName": "PhonePeMerchant", // Merchant Name registered in UPI payment acc
			"payeeMerchantCode": "me", // Merchant Code from UPI payment acc
			"transactionId":txn, // Unique transaction id for merchant's reference
			"transactionRef": ref, // Unique transaction id for merchant's reference
			"transactionNote": "MyAdv Membership Fee", // Note that will displayed in payment app during transaction
			"amount": "99", // Amount 
			"minimumAmount": "1", // its optional. Minimum amount that has to be transferred 
			"currency": "INR", // Currency of amount
			"transactionRefUrl": "https://myadv.in" // URL for the order
	};
	let successCallback = function (result) { 
		/* success and failure of payment will be given in this method, this is because each payment uses different name to represent the status of transaction under "Status" field.*/
		//alert("result of success interaction of payment app" + result);
		var res = JSON.stringify(result);
		alert("Success" + res);
	}
	let failureCallback = function (err) {
	   // alert("Issue in interaction and completion of payment with UPI" + err);
	   var error = JSON.stringify(err);
		alert("error" +error );
	}
	 
	window["UPI"].acceptPayment(config, successCallback, failureCallback);
}
function alert(msg) {
  window.plugins.toast.showWithOptions(
    {
      message: msg,
      duration: "short", // which is 2000 ms. "long" is 4000. Or specify the nr of ms yourself.
      position: "bottom",
      addPixelsY: -40  // added a negative value to move it up a bit (default 0)
    }
    //onSuccess, // optional
    //onError    // optional
  );
}