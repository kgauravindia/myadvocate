var app = {
  // Application Constructor
  initialize: function() {
    this.bindEvents();
  },
 
  // Bind Event Listeners
  bindEvents: function() {
    document.addEventListener('deviceready', this.onDeviceReady, false);
  },
 
  // deviceready Event Handler
  onDeviceReady: function() {
    //console.log('Device is ready for work');
    universalLinks.subscribe('openNewsListPage', app.onNewsListPageRequested);
    universalLinks.subscribe('openNewsDetailedPage', app.onNewsDetailedPageRequested);
  },
 
  // openNewsListPage Event Handler
  onNewsListPageRequested: function(eventData) {
    //console.log('Showing list of awesome news.');
	
    // do some work to show list of news
  },
 
  // openNewsDetailedPage Event Handler
  onNewsDetailedPageRequested: function(eventData) {
    //console.log('Showing to user details page: ' + eventData.path);
	var news_id = eventData.path.substring(6);
	localStorage.setItem('news_id',news_id);
	window.location ='single.html';
	//alert(eventData.params.id);
    // do some work to show detailed page
  }
};
 
app.initialize();


function shareContent(text, title, imageBlob) {
    if (navigator.share) {
        navigator.share({
            title: title,
            text: text,
            files: [new File([imageBlob], "image.jpeg", { type: "image/jpeg" })],
        })
        .then(() => {
            console.log("Content shared successfully");
        })
        .catch((error) => {
            console.error("Error sharing content:", error);
        });
    } else {
        console.log("Web Share API not supported in this browser");
    }
}

$(document).on('click',".news_share",function(){
    
  const file = new File(data, "some.png", { type: "image/png" });
  try {
    navigator.share({
      title: "Example File",
      files: [file]
    });
  } catch (err) {
    console.error("Share failed:", err.message);
  }
  
});


$(document).on('click',".news_share2",function(){
      //alert("Please wait while we are collecting information for your", 'long');
	  var news_id = $(this).data('id');
      var title = $(this).data('title');
	  var msg = $(this).data('msg');
	  var msg = title + "\n" +msg; 
	  //var url = 'For Latest News Download *NewsPlant App* bit.ly/npdlink'; // $(this).data('url');
	  var url = 'https://olaw.in/news/'+news_id; // $(this).data('url');
	  var imageUrl = $(this).data('img');
	     $.ajax({
            type: "GET",
            url: "https://app.myadv.in/share.php", // Replace with the path to your PHP file
            data: { imageUrl: imageUrl },
            success: function(data) {
                if (data !== null) {
                    
                    imgdata = "data:image/jpeg;base64," + data; 
                    //console.log(imgdata);
                    //$("#image").attr("src", "data:image/jpeg;base64," + data);
                    
                    shareContent(msg, title, imgdata);
                    
                } 
            },
            error: function() {
                alert("Image retrieval failed.");
            }
        });
});	

	function shot()
	{
	  var msg ="Hi, I am using MyAdv India App very easy smooth and entertaining you can also try. *Download Now*";
	  var url ="bit.ly/myadvindiaapp";
	  
		navigator.screenshot.save(function(error,res){
	  if(error){
		//console.error(error);
	  }else{
		//console.log('ok',res.filePath); //should be path/to/myScreenshot.jpg
		imageLink = res.filePath;
		window.plugins.socialsharing.share(msg, null, 'file://'+imageLink, url );
		}
	  },'jpg',50,'myScreenShot');
	}

function randomString( string_length =15) {
	var chars = "0123456789ABCDEFGHIJKLMNOPQRSTUVWXTZabcdefghiklmnopqrstuvwxyz";
	var randomstring = '';
	for (var i=0; i<string_length; i++) {
		var rnum = Math.floor(Math.random() * chars.length);
		randomstring += chars.substring(rnum,rnum+1);
	}
	return randomstring;
	}
	// function openBrowser(url) {
	   // var target = '_blank';
	   // var options = "hideurlbar=yes,zoom=no,location=yes";
	   // var ref = cordova.InAppBrowser.open(url, target, options);	
	// }

// $("#apparea").on('click','.inapp-link',function(){
		// if (navigator.connection.type == Connection.NONE) {
			// navigator.notification.alert('No Internet Connection', ref.close(), 'Offline', 'Close');
		// }
		// else{
			// var url = this.getAttribute('data-url'); 
			// var target = '_blank';
			// var ref = cordova.InAppBrowser.open( url, '_blank', 'location=yes,zoom=no,closebuttoncolor=#ececfb,toolbarcolor=#313140,hideurlbar=yes');
			// ref.show();
			// // ref.addEventListener('exit', exitCallback);
			// // function exitCallback() {
			  // // window.location='index.html';
			// // }
		// }
	// });
		
 $("#apparea").on('click',function()
	{
		var networkState = navigator.connection.type;
		if (networkState == Connection.NONE) {
			alert("No Internet Connection");
		}
	});

// function alert(msg) {
//   window.plugins.toast.showWithOptions(
//     {
//       message: msg,
//       duration: "short", // which is 2000 ms. "long" is 4000. Or specify the nr of ms yourself.
//       position: "bottom",
//       addPixelsY: -40  // added a negative value to move it up a bit (default 0)
//     }
//     //onSuccess, // optional
//     //onError    // optional
//   );
// }

function pay()
{
	var amount = $("#total_amt").text();
	var txn =randomString(15);
	var ref =randomString(10);
	let config = {
			"payeeVPA": "9431426600@upi", // VPA no from UPI payment acc
			"payeeName": "PhonePeMerchant", // Merchant Name registered in UPI payment acc
			"payeeMerchantCode": "me", // Merchant Code from UPI payment acc
			"transactionId":txn, // Unique transaction id for merchant's reference
			"transactionRef": ref, // Unique transaction id for merchant's reference
			"transactionNote": "Smart Basket Bill", // Note that will displayed in payment app during transaction
			"amount": amount, // Amount 
			"minimumAmount": "1", // its optional. Minimum amount that has to be transferred 
			"currency": "INR", // Currency of amount
			"transactionRefUrl": "https://smartbasket.info" // URL for the order
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

function callme(no)
{
    window.plugins.CallNumber.callNumber(null, null, no, true);
}


$(document).on('click','.speak',function(event){
	var x = $(this);
	if(x.attr('isspeaking') =="false")
	{
	//var ctext = $(this).closest('.card').find('.card-text').text();
	var ctext = $('#content p').text();
    //console.log(ctext);
		TTS.speak({text:ctext, locale:"hi-IN",rate:1}, 
		function () {
            //console.error("success");
        }, function (reason) {
            //console.error(reason);
        });
	
	 x.attr('isspeaking',"true");
	 x.html('<i class="fa fa-volume-off"></i>');
	}
	else{
		TTS.speak({text:"", locale:"hi-IN",rate:1}, 
		function () {
            //console.error("success");
        }, function (reason) {
            //console.error(reason);
        });
		x.attr('isspeaking',"false");
		x.html('<i class="fa fa-volume-up"></i>');
	}
	event.stopPropagation();
});


function shownews()
{
	alert('Hello' + eventData.url);
}