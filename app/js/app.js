/*-----app.js --*/
/*-MYADVINDIA APP SCRIPT --*/
	var base_url = 'https://myadv.in/ais/api_v41.php?token=a9dcc62d6078b881d318aca0ead65ab5&';
	var base_url_ed = 'https://ed.olaw.in/app/api_v41.php?token=a9dcc62d6078b881d318aca0ead65ab5&';
	var olaw_api_url = 'https://olaw.in/api.php';
	var olaw_api_key = 'OLAW_D776A66967200383A932';
	var file_url = 'https://myadv.in/upload/';
	var goback ="<img src='icon/back.png' height='25px' onclick='window.history.back()'>";
	var myadv_version =localStorage.getItem('myadv_version'); 
	var myadv_user_type =localStorage.getItem('myadv_user_type'); 
	var myadv_id =localStorage.getItem('myadv_id'); 
	var myadv_name =localStorage.getItem('myadv_name'); 
	var myadv_mobile =localStorage.getItem('myadv_mobile'); 
	function get_disp_contact(val, visibility, type) {
		var masked = (type === 'mobile') ? 'XXXXXX' + String(val).slice(-4) : '****@****.***';
		if (visibility === 'PUBLIC') return val;
		if ((visibility === 'REGISTERED' || !visibility) && myadv_id !== null) return val;
		if (visibility === 'ADVOCATE' && myadv_user_type === 'advocate') return val;
		return masked;
	}
	//document.addEventListener('contextmenu', event => event.preventDefault());
	// Disable right-click
window.addEventListener('contextmenu', function (e) {
    e.preventDefault();
});

// Disable user selection
document.addEventListener('selectstart', function (e) {
    e.preventDefault();
});
	if($("#welcome").length>0 &&localStorage.getItem('myadv_name') !=null)
	{
		$("#welcome").html('Welcome ! ' + localStorage.getItem('myadv_name'));	
	}
	$.ajax({
		type:'POST',
		url:base_url+'task=app_version',
		success:function(data)
		{
			
			obj=JSON.parse(data);
			console.log(obj);
			if( parseFloat(myadv_version) < parseFloat(obj.version))
			{
			$("#updateapp").css('display','block');
			}
		},
		complete:function()
		{
			$("#loader").hide();
		}
	});
	

function sharenow() {
  if (navigator.share) {
    navigator.share({
      title: 'MyAdvocateAI',
      text: 'Hi, MyAdvocateAI helps me solve all types of legal queries & search advocate records. Explore now:',
      url: window.location.origin + '/app/'
    })
    .then(() => console.log('Shared successfully'))
    .catch(error => console.error('Error sharing:', error));
  } else {
    console.log('Sharing not supported on this device');
    // You can provide an alternative sharing method for devices that don't support the Web Share API
  }
}
	
	/*- Function  List --*/
	function randomString() {
	var chars = "0123456789ABCDEFGHIJKLMNOPQRSTUVWXTZabcdefghiklmnopqrstuvwxyz";
	var string_length = 32;
	var randomstring = '';
	for (var i=0; i<string_length; i++) {
		var rnum = Math.floor(Math.random() * chars.length);
		randomstring += chars.substring(rnum,rnum+1);
	}
	localStorage.setItem('auth_key', randomstring );
	return randomstring;
	}

	$("#home").click(function(){
		cordova.getAppVersion.getVersionNumber(function (version) 
		  { 
		  localStorage.setItem('myadv_version',version); 
		  });
		location.reload();
	});
	
	

$("#apparea").on('click',"#logout", function(){
	
	logout();
});

function logout(){
	
	var result = confirm("Do You really want to logout");		
		if(result==true)
		{
			localStorage.removeItem('myadv_id');
			localStorage.removeItem('myadv_mobile');
			localStorage.removeItem('myadv_details');
			localStorage.removeItem('myadv_name');
			localStorage.removeItem('myadv_user_type');
			window.location ='index.html';
		}
}

//========= LOGIN BUTTON ===========//

$("#login_btn").click(function(){
	$("#login_frm").validate();

	if($("#login_frm").valid())
	{
		$(this).attr("disabled", true);
		$(this).html("Please Wait...");
		var data  =$("#login_frm").serialize();
		$.ajax({
			'type':'POST',
			'url':base_url+'&task=login_user',
			'data':data,
			success: function(res){
				
				var obj = JSON.parse(res);
				// console.log(obj);
				if(obj.status.trim() =='success')
				{
					localStorage.setItem('details',res);
					localStorage.setItem('myadv_id',obj.data[0].id);
					localStorage.setItem('myadv_user_type',obj.data[0].user_type);
					localStorage.setItem('myadv_mobile',obj.data[0].mobile);
					localStorage.setItem('myadv_name',obj.data[0].name);
					alert( "Login Success...", obj.status);
					window.location='main.html';
				}
				else{
					alert( "Sorry Some Thing Went Wrong", "error");
					//$("#login_frm")[0].reset();
					$("#login_btn").html("Secure Login");
					$("#login_btn").removeAttr("disabled");
				}
			}

		});
	}
});

//=====SIGNUP BUTTON =========//
$(document).on('click',"#signup_btn", function(){
	$("#insert_frm").validate();
	if($("#insert_frm").valid())
	{
		var task= $("#insert_frm").attr('action');
		$(this).attr("disabled", true);
		$(this).html("Please Wait...");
		var data  =$("#insert_frm").serialize();
		$.ajax({
			'type':'POST',
			'url': base_url+'&task=signup',
			'data':data,
			
			success: function(data){
				// console.log(data);
				var obj = JSON.parse(data);
				if(obj.status =='success')
				{
					alert(obj.status);
					$("#register_area").css("display","none");
					$("#login_area").css("display","block");
				
				}
				else{
					alert(obj.msg);
					$("#register_area").css("display","none");
					$("#login_area").css("display","block");
					$("#signup_btn").removeAttr("disabled");
				}
			}
		});
	}
});
//=====INSERT BUTTON =========//
$(document).on('click',"#insert_btn", function(){
	$("#insert_frm").validate();
	if($("#insert_frm").valid())
	{
		var task= $("#insert_frm").attr('action');
		$(this).attr("disabled", true);
		$(this).html("Please Wait...");
		var data  =$("#insert_frm").serialize();
		$.ajax({
			'type':'POST',
			'url': base_url+'&task='+task,
			'data':data,
			success: function(data){
				// console.log(data);
				var obj = JSON.parse(data);
					alert(obj.msg);
					$("#insert_frm")[0].reset();
					$("#insert_btn").html("Save Details");
					$("#insert_btn").removeAttr("disabled");
			}
		});
	}
});
	
//=====UPDATE BUTTON =========//
$("#apparea").on('click','#update_btn',function(){
	$("#update_frm").validate();

	if($("#update_frm").valid())
	{
		var task= $("#update_frm").attr('action');
		$(this).attr("disabled", true);
		$(this).html("Please Wait...");
		var data  =$("#update_frm").serialize();
		$.ajax({
			'type':'POST',
			'url': base_url+'task='+task,
			'data':data,
			success: function(data){
				//alert(data);
				console.log(data);
				var obj = JSON.parse(data);
				$('#update_frm')[0].reset();
				
				$("#update_btn").html("Save Details");
				$("#update_btn").removeAttr("disabled");
				if(obj.url!= null)
				{
					alert(obj.msg);
					window.location.replace(obj.url);
					
				}
				else{
					alert(obj.msg, obj.status);
				}
			}

		});
	}
});

//=====RECOVER BUTTON =========//
$("#recover_btn").click(function(){
	$("#recover_frm").validate();

	if($("#recover_frm").valid())
	{
		var task= $("#recover_frm").attr('action');
		$(this).attr("disabled", true);
		$(this).html("Please Wait...");
		var data  =$("#recover_frm").serialize();
		$.ajax({
			'type':'POST',
			'url': base_url+'task=recover',
			'data':data,
			success: function(data){
				//alert(data);
				// console.log(data);
				var obj = JSON.parse(data);
				//$('#update_frm')[0].reset();
				
				$("#recover_btn").html("Recover Password");
				$("#recover_btn").removeAttr("disabled");
				alert(obj.msg, obj.status); 
				location.reload();
			}
		});
	}
});

/* ------Online Display Board -----*/
var obd ="<ul class='list-group'><li class='inapp-link list-group-item' data-url='https://main.sci.gov.in/display-board'><i class='fa fa-star color-yellow1-dark'></i> <span> Supreme Court of India </span> <em class='bg-green2-dark'>SCI</em> <i class='fa fa-angle-right'></i></li><li class='list-group-item'> <a href='http://courtview.allahabadhighcourt.in/courtview/CourtViewAllahabad.do'><span>Allahabad High Court- Allahabad</span> <i class='fa fa-angle-right'></a></i></li><li class=' list-group-item'><a href='http://courtview.allahabadhighcourt.in/courtview/CourtViewLucknow.do'> <span> Allahabad High Court- Lucknow </span> <i class='fa fa-angle-right'></a></i></li><li class='list-group-item'> <a href='http://hc.ap.nic.in/Hcdbs/displayboard.jsp'> <span> Andhra Pradesh High Court</span> </a><i class='fa fa-angle-right'></i></li><li class='inapp-link list-group-item' data-url='https://bombayhighcourt.nic.in/mobileindex.html'> <span> Bombay High Court, Mumbai</span> <i class='fa fa-angle-right'></i></li></li><li class='inapp-link list-group-item' data-url='https://www.calcuttahighcourt.gov.in/Display-Board'> <span> Calcutta High Court</span> <i class='fa fa-angle-right'></i></li><li class='list-group-item'> <a href='http://cg.nic.in/hcbspcourtview/query/court1.php'> <span> Chhattisgarh High Court </span><i class='fa fa-angle-right'></i></a></li><li class='inapp-link list-group-item' data-url='https://delhihighcourt.nic.in/displayboard.asp'> <span> Delhi High Court </span> <i class='fa fa-angle-right'></i></li><li class='list-group-item' data-url='#'> <span> Gauhati High Court</span> <span class='badge badge-danger'>NA</span> </li><li class='inapp-link list-group-item' data-url='https://gujarathighcourt.nic.in/boarddisplay'> <span> Gujarat High Court</span> <i class='fa fa-angle-right'></i></li><li class='list-group-item' data-url='#'> <span> Himachal Pradesh High Court </span><span class='badge badge-danger'>NA</span><i class='fa fa-angle-right'></i></li><li class='list-group-item'><a href='http://jkhighcourt.nic.in/displayboard.php'><span> Jammu & Kashmir High Court </span> <i class='fa fa-angle-right'></i></a></li><li class='inapp-link list-group-item' data-url='https://jharkhandhighcourt.nic.in/dpboard.php'> <span> Jharkhand High Court </span> <i class='fa fa-angle-right'></i></li><li class='list-group-item'> <a href='http://karnatakajudiciary.kar.nic.in/websitenew/casedetails/display_board.php'> <span> Karnataka High Court - Bengaluru </span><i class='fa fa-angle-right'></i></a></li><li class='list-group-item'> <a href='http://karnatakajudiciary.kar.nic.in/dwdonlinedisplayboard.aspx'> <span> Karnataka High Court - Dharwad </span> <i class='fa fa-angle-right'></i></a></li><li class='list-group-item'> <a href='http://karnatakajudiciary.kar.nic.in/glbonlinedisplayboard.aspx'> <span> Karnataka High Court - Kalaburagi </span> <i class='fa fa-angle-right'></i></a></li><li class='list-group-item' data-url='#'> <span> Kerala High Court </span><span class='badge badge-danger'>NA</span></li><li class='inapp-link list-group-item' data-url='https://mphc.gov.in/online-display-board'> <span>Madhya Pradesh High Court </span> <i class='fa fa-angle-right'></i></li><li class='inapp-link list-group-item' data-url='https://www.mhc.tn.gov.in/masdisplay/'> <span> Madras High Court - Madras </span> <i class='fa fa-angle-right'></i></li><li class='inapp-link list-group-item' data-url='https://www.mhc.tn.gov.in/mdudisplay/'> <span> Madras High Court - Madurai </span><i class='fa fa-angle-right'></i></li><li class='list-group-item' data-url='#'> <span> Meghalaya High Court</span> <span class='badge badge-danger'>NA</span><i class='fa fa-angle-right'></i></li><li class='list-group-item' data-url='#'> <span> Orissa High Court </span> <span class='badge badge-danger'>NA</span></li><li class='list-group-item' data-url='#'> <span> Manipur High Court </span> <span class='badge badge-danger'>NA</span></li><li class='list-group-item'><a href='http://patnahighcourt.gov.in/odb/'> <span> Patna High Court </span><i class='fa fa-angle-right'></i></a></li><li class='inapp-link list-group-item' data-url='https://phhc.gov.in/display_board_full_width.php'> <span> Punjab & Haryana High Court </span> <i class='fa fa-angle-right'></i></li><li class='inapp-link list-group-item' data-url='https://hcraj.nic.in/displayboard/jodhpur.php'> <span> Rajasthan High Court - Jodhpur </span> <i class='fa fa-angle-right'></i></li><li class='inapp-link list-group-item' data-url='https://hcraj.nic.in/displayboard/jaipur.php'> <span> Rajasthan High Court - Jaipur </span><i class='fa fa-angle-right'></i></li><li class='list-group-item' data-url='#'> <span> Sikkim High Court</span> <span class='badge badge-danger'>NA</span><i class='fa fa-angle-right'></i></li><li class='inapp-link list-group-item' data-url='https://tshc.gov.in/Hcdbs/displayboard.jsp'> <span> Telangana High Court</span><i class='fa fa-angle-right'></i></li><li class='list-group-item' data-url='#'> <span> Tripura High Court</span> <span class='badge badge-danger'>NA</span><i class='fa fa-angle-right'></i></li><li class='inapp-link list-group-item' data-url='https://highcourtofuttarakhand.gov.in/files/dispboard.php'> <span> Uttarakhand High Court </span> <i class='fa fa-angle-right'></i></li></ul>";


$("#apparea").on('click',"#obd",function(){
	$("#appbody").html('');
	$("#apphead").html("<img src='img/back.png' align='left' height='25px' onclick='location.reload()'> <div style='float:right'> <img src='img/obd.png' height='25px' style='filter: brightness(0.5) saturate(100%);'> Online Display-Board </div>");
	$("#appbody").append(obd);
	
});

/*--------Terms and Conditions ---*/
var terms ="<h5 class='text-dark'>Terms and Conditions</h5><p class='text-muted'>Last updated: 01 April 2020</p><p>1. <b>Introduction</b></p><p>Welcome to MyAdv India | A initiative by <b>OfferPlant Technologies Private Limited</b> ('Company', 'we', 'our', 'us', 'MyAdv', 'MyAdv India')!</p><p>These Terms of Service ('Terms', 'Terms of Service') govern your use of our website located at <b>https://myadv.in</b> (together or individually 'Service') operated by <b>OfferPlant Technologies Private Limited, </b> Registered Office 2B, Kumar Bhawan, Umanagar, Chapra, District - Saran, State - Bihar, India 841301 </b>.</p><p>Our Privacy Policy also governs your use of our Service and explains how we collect, safeguard and disclose information that results from your use of our web pages.</p><p>Your agreement with us includes these Terms and our Privacy Policy ('Agreements'). You acknowledge that you have read and understood Agreements, and agree to be bound of them.</p><p>If you do not agree with (or cannot comply with) Agreements, then you may not use the Service, but please let us know by emailing at <b>help@myadv.in</b> so we can try to find a solution. These Terms apply to all visitors, users and others who wish to access or use Service.</p><p>2. <b>Communications</b></p><p>By using our Service, you agree to subscribe to newsletters, marketing or promotional materials and other information we may send. However, you may opt out of receiving any, or all, of these communications from us by following the unsubscribe link or by emailing at help@myadv.in.</p><p>3. <b>Purchases</b></p><p>If you wish to purchase any product or service made available through Service ('Purchase'), you may be asked to supply certain information relevant to your Purchase including but not limited to, your credit or debit card number, the expiration date of your card, your billing address, and your shipping information.</p><p>You represent and warrant that: (i) you have the legal right to use any card(s) or other payment method(s) in connection with any Purchase; and that (ii) the information you supply to us is true, correct and complete.</p><p>We may employ the use of third party services for the purpose of facilitating payment and the completion of Purchases. By submitting your information, you grant us the right to provide the information to these third parties subject to our Privacy Policy.</p><p>We reserve the right to refuse or cancel your order at any time for reasons including but not limited to: product or service availability, errors in the description or price of the product or service, error in your order or other reasons.</p><p>We reserve the right to refuse or cancel your order if fraud or an unauthorized or illegal transaction is suspected.</p><p>4. <b>Contests, Sweepstakes and Promotions</b></p><p>Any contests, sweepstakes or other promotions (collectively, 'Promotions') made available through Service may be governed by rules that are separate from these Terms of Service. If you participate in any Promotions, please review the applicable rules as well as our Privacy Policy. If the rules for a Promotion conflict with these Terms of Service, Promotion rules will apply.</p><p>5. <b>Subscriptions</b></p><p>Some parts of Service are billed on a subscription basis ('Subscription(s)'). You will be billed in advance on a recurring and periodic basis ('Billing Cycle'). Billing cycles will be set depending on the type of subscription plan you select when purchasing a Subscription.</p><p>At the end of each Billing Cycle, your Subscription will automatically renew under the exact same conditions unless you cancel it or OfferPlant Technologies Private Limited cancels it. You may cancel your Subscription renewal either through your online account management page or by contacting help@myadv.in customer support team.</p><p>A valid payment method is required to process the payment for your subscription. You shall provide OfferPlant Technologies Private Limited with accurate and complete billing information that may include but not limited to full name, address, state, postal or zip code, telephone number, and a valid payment method information. By submitting such payment information, you automatically authorize OfferPlant Technologies Private Limited to charge all Subscription fees incurred through your account to any such payment instruments.</p><p>Should automatic billing fail to occur for any reason, OfferPlant Technologies Private Limited reserves the right to terminate your access to the Service with immediate effect.</p><p>6. <b>Free Trial</b></p><p>OfferPlant Technologies Private Limited may, at its sole discretion, offer a Subscription with a free trial for a limited period of time ('Free Trial').</p><p>You may be required to enter your billing information in order to sign up for Free Trial.</p><p>If you do enter your billing information when signing up for Free Trial, you will not be charged by OfferPlant Technologies Private Limited until Free Trial has expired. On the last day of Free Trial period, unless you cancelled your Subscription, you will be automatically charged the applicable Subscription fees for the type of Subscription you have selected.</p><p>At any time and without notice, OfferPlant Technologies Private Limited reserves the right to (i) modify Terms of Service of Free Trial offer, or (ii) cancel such Free Trial offer.</p><p>7. <b>Fee Changes</b></p><p>OfferPlant Technologies Private Limited, in its sole discretion and at any time, may modify Subscription fees for the Subscriptions. Any Subscription fee change will become effective at the end of the then-current Billing Cycle.</p><p>OfferPlant Technologies Private Limited will provide you with a reasonable prior notice of any change in Subscription fees to give you an opportunity to terminate your Subscription before such change becomes effective.</p><p>Your continued use of Service after Subscription fee change comes into effect constitutes your agreement to pay the modified Subscription fee amount.</p><p>8. <b>Refunds</b></p><p>We issue refunds for Contracts within <b>7 days</b> of the original purchase of the Contract.</p><p>9. <b>Content</b></p><p>Our Service allows you to post, link, store, share and otherwise make available certain information, text, graphics, videos, or other material ('Content'). You are responsible for Content that you post on or through Service, including its legality, reliability, and appropriateness.</p><p>By posting Content on or through Service, You represent and warrant that: (i) Content is yours (you own it) and/or you have the right to use it and the right to grant us the rights and license as provided in these Terms, and (ii) that the posting of your Content on or through Service does not violate the privacy rights, publicity rights, copyrights, contract rights or any other rights of any person or entity. We reserve the right to terminate the account of anyone found to be infringing on a copyright.</p><p>You retain any and all of your rights to any Content you submit, post or display on or through Service and you are responsible for protecting those rights. We take no responsibility and assume no liability for Content you or any third party posts on or through Service. However, by posting Content using Service you grant us the right and license to use, modify, publicly perform, publicly display, reproduce, and distribute such Content on and through Service. You agree that this license includes the right for us to make your Content available to other users of Service, who may also use your Content subject to these Terms.</p><p>OfferPlant Technologies Private Limited has the right but not the obligation to monitor and edit all Content provided by users.</p><p>In addition, Content found on or through this Service are the property of OfferPlant Technologies Private Limited or used with permission. You may not distribute, modify, transmit, reuse, download, repost, copy, or use said Content, whether in whole or in part, for commercial purposes or for personal gain, without express advance written permission from us.</p><p>10. <b>Prohibited Uses</b></p><p>You may use Service only for lawful purposes and in accordance with Terms. You agree not to use Service:</p><p>0.1. In any way that violates any applicable national or international law or regulation.</p><p>0.2. For the purpose of exploiting, harming, or attempting to exploit or harm minors in any way by exposing them to inappropriate content or otherwise.</p><p>0.3. To transmit, or procure the sending of, any advertising or promotional material, including any 'junk mail', 'chain letter,' 'spam,' or any other similar solicitation.</p><p>0.4. To impersonate or attempt to impersonate Company, a Company employee, another user, or any other person or entity.</p><p>0.5. In any way that infringes upon the rights of others, or in any way is illegal, threatening, fraudulent, or harmful, or in connection with any unlawful, illegal, fraudulent, or harmful purpose or activity.</p><p>0.6. To engage in any other conduct that restricts or inhibits anyone’s use or enjoyment of Service, or which, as determined by us, ma   y harm or offend Company or users of Service or expose them to liability.</p><p>Additionally, you agree not to:</p><p>0.1. Use Service in any manner that could disable, overburden, damage, or impair Service or interfere with any other party’s use of Service, including their ability to engage in real time activities through Service.</p><p>0.2. Use any robot, spider, or other automatic device, process, or means to access Service for any purpose, including monitoring or copying any of the material on Service.</p><p>0.3. Use any manual process to monitor or copy any of the material on Service or for any other unauthorized purpose without our prior written consent.</p><p>0.4. Use any device, software, or routine that interferes with the proper working of Service.</p><p>0.5. Introduce any viruses, trojan horses, worms, logic bombs, or other material which is malicious or technologically harmful.</p><p>0.6. Attempt to gain unauthorized access to, interfere with, damage, or disrupt any parts of Service, the server on which Service is stored, or any server, computer, or database connected to Service.</p><p>0.7. Attack Service via a denial-of-service attack or a distributed denial-of-service attack.</p><p>0.8. Take any action that may damage or falsify Company rating.</p><p>0.9. Otherwise attempt to interfere with the proper working of Service.</p><p>11. <b>Analytics</b></p><p>We may use third-party Service Providers to monitor and analyze the use of our Service.</p><p>12. <b>Use By Minors</b></p><p>Age requirements If you’re under the age required to manage your own Account, you must have your parent or legal guardian’s permission to use a MyAdv India Account. Please have your parent or legal guardian read these terms with you. If you’re a parent or legal guardian, and you allow your child to use the services, then these terms apply to you and you’re responsible for your child’s activity on the services.</p><p>13. <b>Accounts</b></p><p>When you create an account with us, you guarantee that you are above the age of 18, and that the information you provide us is accurate, complete, and current at all times. Inaccurate, incomplete, or obsolete information may result in the immediate termination of your account on Service.</p><p>You are responsible for maintaining the confidentiality of your account and password, including but not limited to the restriction of access to your computer and/or account. You agree to accept responsibility for any and all activities or actions that occur under your account and/or password, whether your password is with our Service or a third-party service. You must notify us immediately upon becoming aware of any breach of security or unauthorized use of your account.</p><p>You may not use as a username the name of another person or entity or that is not lawfully available for use, a name or trademark that is subject to any rights of another person or entity other than you, without appropriate authorization. You may not use as a username any name that is offensive, vulgar or obscene.</p><p>We reserve the right to refuse service, terminate accounts, remove or edit content, or cancel orders in our sole discretion.</p><p>14. <b>Intellectual Property</b></p><p>Service and its original content (excluding Content provided by users), features and functionality are and will remain the exclusive property of OfferPlant Technologies Private Limited and its licensors. Service is protected by copyright, trademark, and other laws of and foreign countries. Our trademarks may not be used in connection with any product or service without the prior written consent of OfferPlant Technologies Private Limited.</p><p>15. <b>Copyright Policy</b></p><p>We respect the intellectual property rights of others. It is our policy to respond to any claim that Content posted on Service infringes on the copyright or other intellectual property rights ('Infringement') of any person or entity.</p><p>If you are a copyright owner, or authorized on behalf of one, and you believe that the copyrighted work has been copied in a way that constitutes copyright infringement, please submit your claim via email to help@myadv.in, with the subject line: 'Copyright Infringement' and include in your claim a detailed description of the alleged Infringement as detailed below, under 'DMCA Notice and Procedure for Copyright Infringement Claims'</p><p>You may be held accountable for damages (including costs and attorneys’ fees) for misrepresentation or bad-faith claims on the infringement of any Content found on and/or through Service on your copyright.</p><p>16. <b>Notice and Procedure for Copyright Claims</b></p><p>You may submit a notification pursuant to us for further detail):</p><p>0.1. an electronic or physical signature of the person authorized to act on behalf of the owner of the copyright’s interest;</p><p>0.2. a description of the copyrighted work that you claim has been infringed, including the URL (i.e., web page address) of the location where the copyrighted work exists or a copy of the copyrighted work;</p><p>0.3. identification of the URL or other specific location on Service where the material that you claim is infringing is located;</p><p>0.4. your address, telephone number, and email address;</p><p>0.5. a statement by you that you have a good faith belief that the disputed use is not authorized by the copyright owner, its agent, or the law;</p><p>0.6. a statement by you, made under penalty of perjury, that the above information in your notice is accurate and that you are the copyright owner or authorized to act on the copyright owner’s behalf.</p><p>You can contact our Copyright Agent via email at help@myadv.in.</p><p>17. <b>Error Reporting and Feedback</b></p><p>You may provide us either directly at help@myadv.in or via third party sites and tools with information and feedback concerning errors, suggestions for improvements, ideas, problems, complaints, and other matters related to our Service ('Feedback'). You acknowledge and agree that: (i) you shall not retain, acquire or assert any intellectual property right or other right, title or interest in or to the Feedback; (ii) Company may have development ideas similar to the Feedback; (iii) Feedback does not contain confidential information or proprietary information from you or any third party; and (iv) Company is not under any obligation of confidentiality with respect to the Feedback. In the event the transfer of the ownership to the Feedback is not possible due to applicable mandatory laws, you grant Company and its affiliates an exclusive, transferable, irrevocable, free-of-charge, sub-licensable, unlimited and perpetual right to use (including copy, modify, create derivative works, publish, distribute and commercialize) Feedback in any manner and for any purpose.</p><p>18. <b>Links To Other Web Sites</b></p><p>Our Service may contain links to third party web sites or services that are not owned or controlled by OfferPlant Technologies Private Limited.</p><p>OfferPlant Technologies Private Limited has no control over, and assumes no responsibility for the content, privacy policies, or practices of any third party web sites or services. We do not warrant the offerings of any of these entities/individuals or their websites.</p><p>For example, the other Terms of Service are available on <a href='https://offerplant.com/'>OfferPlant</a>, website.</p><p>YOU ACKNOWLEDGE AND AGREE THAT COMPANY SHALL NOT BE RESPONSIBLE OR LIABLE, DIRECTLY OR INDIRECTLY, FOR ANY DAMAGE OR LOSS CAUSED OR ALLEGED TO BE CAUSED BY OR IN CONNECTION WITH USE OF OR RELIANCE ON ANY SUCH CONTENT, GOODS OR SERVICES AVAILABLE ON OR THROUGH ANY SUCH THIRD PARTY WEB SITES OR SERVICES.</p><p>WE STRONGLY ADVISE YOU TO READ THE TERMS OF SERVICE AND PRIVACY POLICIES OF ANY THIRD PARTY WEB SITES OR SERVICES THAT YOU VISIT.</p><p>19. <b>Disclaimer Of Warranty</b></p><p>THESE SERVICES ARE PROVIDED BY COMPANY ON AN 'AS IS' AND 'AS AVAILABLE' BASIS. COMPANY MAKES NO REPRESENTATIONS OR WARRANTIES OF ANY KIND, EXPRESS OR IMPLIED, AS TO THE OPERATION OF THEIR SERVICES, OR THE INFORMATION, CONTENT OR MATERIALS INCLUDED THEREIN. YOU EXPRESSLY AGREE THAT YOUR USE OF THESE SERVICES, THEIR CONTENT, AND ANY SERVICES OR ITEMS OBTAINED FROM US IS AT YOUR SOLE RISK.</p><p>NEITHER COMPANY NOR ANY PERSON ASSOCIATED WITH COMPANY MAKES ANY WARRANTY OR REPRESENTATION WITH RESPECT TO THE COMPLETENESS, SECURITY, RELIABILITY, QUALITY, ACCURACY, OR AVAILABILITY OF THE SERVICES. WITHOUT LIMITING THE FOREGOING, NEITHER COMPANY NOR ANYONE ASSOCIATED WITH COMPANY REPRESENTS OR WARRANTS THAT THE SERVICES, THEIR CONTENT, OR ANY SERVICES OR ITEMS OBTAINED THROUGH THE SERVICES WILL BE ACCURATE, RELIABLE, ERROR-FREE, OR UNINTERRUPTED, THAT DEFECTS WILL BE CORRECTED, THAT THE SERVICES OR THE SERVER THAT MAKES IT AVAILABLE ARE FREE OF VIRUSES OR OTHER HARMFUL COMPONENTS OR THAT THE SERVICES OR ANY SERVICES OR ITEMS OBTAINED THROUGH THE SERVICES WILL OTHERWISE MEET YOUR NEEDS OR EXPECTATIONS.</p><p>COMPANY HEREBY DISCLAIMS ALL WARRANTIES OF ANY KIND, WHETHER EXPRESS OR IMPLIED, STATUTORY, OR OTHERWISE, INCLUDING BUT NOT LIMITED TO ANY WARRANTIES OF MERCHANTABILITY, NON-INFRINGEMENT, AND FITNESS FOR PARTICULAR PURPOSE.</p><p>THE FOREGOING DOES NOT AFFECT ANY WARRANTIES WHICH CANNOT BE EXCLUDED OR LIMITED UNDER APPLICABLE LAW.</p><p>20. <b>Limitation Of Liability</b></p><p>EXCEPT AS PROHIBITED BY LAW, YOU WILL HOLD US AND OUR OFFICERS, DIRECTORS, EMPLOYEES, AND AGENTS HARMLESS FOR ANY INDIRECT, PUNITIVE, SPECIAL, INCIDENTAL, OR CONSEQUENTIAL DAMAGE, HOWEVER IT ARISES (INCLUDING ATTORNEYS’ FEES AND ALL RELATED COSTS AND EXPENSES OF LITIGATION AND ARBITRATION, OR AT TRIAL OR ON APPEAL, IF ANY, WHETHER OR NOT LITIGATION OR ARBITRATION IS INSTITUTED), WHETHER IN AN ACTION OF CONTRACT, NEGLIGENCE, OR OTHER TORTIOUS ACTION, OR ARISING OUT OF OR IN CONNECTION WITH THIS AGREEMENT, INCLUDING WITHOUT LIMITATION ANY CLAIM FOR PERSONAL INJURY OR PROPERTY DAMAGE, ARISING FROM THIS AGREEMENT AND ANY VIOLATION BY YOU OF ANY FEDERAL, STATE, OR LOCAL LAWS, STATUTES, RULES, OR REGULATIONS, EVEN IF COMPANY HAS BEEN PREVIOUSLY ADVISED OF THE POSSIBILITY OF SUCH DAMAGE. EXCEPT AS PROHIBITED BY LAW, IF THERE IS LIABILITY FOUND ON THE PART OF COMPANY, IT WILL BE LIMITED TO THE AMOUNT PAID FOR THE PRODUCTS AND/OR SERVICES, AND UNDER NO CIRCUMSTANCES WILL THERE BE CONSEQUENTIAL OR PUNITIVE DAMAGES. SOME STATES DO NOT ALLOW THE EXCLUSION OR LIMITATION OF PUNITIVE, INCIDENTAL OR CONSEQUENTIAL DAMAGES, SO THE PRIOR LIMITATION OR EXCLUSION MAY NOT APPLY TO YOU.</p><p>21. <b>Termination</b></p><p>We may terminate or suspend your account and bar access to Service immediately, without prior notice or liability, under our sole discretion, for any reason whatsoever and without limitation, including but not limited to a breach of Terms.</p><p>If you wish to terminate your account, you may simply discontinue using Service.</p><p>All provisions of Terms which by their nature should survive termination shall survive termination, including, without limitation, ownership provisions, warranty disclaimers, indemnity and limitations of liability.</p><p>22. <b>Governing Law</b></p><p>These Terms shall be governed and construed in accordance with the laws of India (legal jurisdiction Chapra, Bihar), which governing law applies to agreement without regard to its conflict of law provisions.</p><p>Our failure to enforce any right or provision of these Terms will not be considered a waiver of those rights. If any provision of these Terms is held to be invalid or unenforceable by a court, the remaining provisions of these Terms will remain in effect. These Terms constitute the entire agreement between us regarding our Service and supersede and replace any prior agreements we might have had between us regarding Service.</p><p>23. <b>Changes To Service</b></p><p>We reserve the right to withdraw or amend our Service, and any service or material we provide via Service, in our sole discretion without notice. We will not be liable if for any reason all or any part of Service is unavailable at any time or for any period. From time to time, we may restrict access to some parts of Service, or the entire Service, to users, including registered users.</p><p>24. <b>Amendments To Terms</b></p><p>We may amend Terms at any time by posting the amended terms on this site. It is your responsibility to review these Terms periodically.</p><p>Your continued use of the Platform following the posting of revised Terms means that you accept and agree to the changes. You are expected to check this page frequently so you are aware of any changes, as they are binding on you.</p><p>By continuing to access or use our Service after any revisions become effective, you agree to be bound by the revised terms. If you do not agree to the new terms, you are no longer authorized to use Service.</p><p>25. <b>Waiver And Sever ability</b></p><p>No waiver by Company of any term or condition set forth in Terms shall be deemed a further or continuing waiver of such term or condition or a waiver of any other term or condition, and any failure of Company to assert a right or provision under Terms shall not constitute a waiver of such right or provision.</p><p>If any provision of Terms is held by a court or other tribunal of competent jurisdiction to be invalid, illegal or unenforceable for any reason, such provision shall be eliminated or limited to the minimum extent such that the remaining provisions of Terms will continue in full force and effect.</p><p>26. <b>Acknowledgement</b></p><p>BY USING SERVICE OR OTHER SERVICES PROVIDED BY US, YOU ACKNOWLEDGE THAT YOU HAVE READ THESE TERMS OF SERVICE AND AGREE TO BE BOUND BY THEM.</p><p>27. <b>Contact Us</b></p><p>Please send your feedback, comments, requests for technical support by email: <b>help@myadv.in</b>.</p></p><p class='text-muted'>I have <b>read, understand and accept </b> all the contents of terms and conditions made on this website and app.</p></div>";


$("#apparea").on('click',"#terms",function(){
	$("#appbody").html("<nav class='navbar fixed-bottom navbar-expand-lg navbar-light ' id='appfoot'> <a href='index.html'><img src='img/home.png' id='home' data-id='1' align='left' height='25px'></a> <img src='img/share.png' id='qprev' onclick='shot()' align='left' height='25px'> </nav>");
	$("#apphead").html("<img src='img/back.png' align='left' height='25px' onclick='location.reload()'> <div style='float:center'> <img src='img/logo.png' height='25px' > Terms and Conditions </div>");
	$("#appbody").append(terms);
	
	
});

/*--------Privacy Policy ---*/
var privacy ="<p>Effective date: 01 April 2020</p><p>1. <b>Introduction</b></p><p>Welcome to <b> MyAdv India </b> A initiative by <b>OfferPlant Technologies Private Limited</b>.</p><p><b>MyAdv India </b> A initiative by <b> OfferPlant Technologies Private Limited</b> ('us', 'we', or 'our') operates <b>https://myadv.in</b> (hereinafter referred to as <b>'Service'</b>).</p><p>Our Privacy Policy governs your visit to <b>https://myadv.in</b>, and explains how we collect, safeguard and disclose information that results from your use of our Service.</p><p>We use your data to provide and improve Service. By using Service, you agree to the collection and use of information in accordance with this policy. Unless otherwise defined in this Privacy Policy, the terms used in this Privacy Policy have the same meanings as in our Terms and Conditions.</p><p>Our Terms and Conditions (<b>'Terms'</b>) govern all use of our Service and together with the Privacy Policy constitutes your agreement with us (<b>'agreement'</b>).</p><p>2. <b>Definitions</b></p><p><b>SERVICE</b> means the https://myadv.in website operated by MyAdv India | A initiative by OfferPlant Technologies Private Limited.</p><p><b>PERSONAL DATA</b> means data about a living individual who can be identified from those data (or from those and other information either in our possession or likely to come into our possession).</p><p><b>USAGE DATA</b> is data collected automatically either generated by the use of Service or from Service infrastructure itself (for example, the duration of a page visit).</p><p><b>COOKIES</b> are small files stored on your device (computer or mobile device).</p><p><b>DATA CONTROLLER</b> means a natural or legal person who (either alone or jointly or in common with other persons) determines the purposes for which and the manner in which any personal data are, or are to be, processed. For the purpose of this Privacy Policy, we are a Data Controller of your data.</p><p><b>DATA PROCESSORS (OR SERVICE PROVIDERS)</b> means any natural or legal person who processes the data on behalf of the Data Controller. We may use the services of various Service Providers in order to process your data more effectively.</p><p><b>DATA SUBJECT</b> is any living individual who is the subject of Personal Data.</p><p><b>THE USER</b> is the individual using our Service. The User corresponds to the Data Subject, who is the subject of Personal Data.</p><p>3. <b>Information Collection and Use</b></p><p>We collect several different types of information for various purposes to provide and improve our Service to you.</p><p>4. <b>Types of Data Collected</b></p><p><b>Personal Data</b></p><p>While using our Service, we may ask you to provide us with certain personally identifiable information that can be used to contact or identify you (<b>'Personal Data'</b>). Personally identifiable information may include, but is not limited to:</p><p>0.1. Email address</p><p>0.2. First name and last name</p><p>0.3. Phone / Mobile number</p><p>0.4. Address, Country, State, Province, ZIP/Postal code, City</p><p>0.5. Social Links, Official used identification number.</p><p>0.6. Age, Gender, Marital Status, Qualification, Membership</p><p>0.7. Photographs, Videos, Signatures(physical, digital)</p><p>0.8. Cookies and Usage Data</p><p>We may use your Personal Data to contact you with newsletters, marketing or promotional materials and other information that may be of interest to you. You may opt out of receiving any, or all, of these communications from us by following the unsubscribe link.</p><p><b>Usage Data</b></p><p>We may also collect information that your browser sends whenever you visit our Service or when you access Service by or through any device (<b>'Usage Data'</b>).</p><p>This Usage Data may include information such as your computer’s Internet Protocol address (e.g. IP address), browser type, browser version, the pages of our Service that you visit, the time and date of your visit, the time spent on those pages, unique device identifiers and other diagnostic data.</p><p>When you access Service with a device, this Usage Data may include information such as the type of device you use, your device unique ID, the IP address of your device, your device operating system, the type of Internet browser you use, unique device identifiers and other diagnostic data.</p><p><b>Location Data</b></p><p>We may use and store information about your location if you give us permission to do so (<b>'Location Data'</b>). We use this data to provide features of our Service, to improve and customize our Service.</p><p>You can enable or disable location services when you use our Service at any time by way of your device settings.</p><p><b>Tracking Cookies Data</b></p><p>We use cookies and similar tracking technologies to track the activity on our Service and we hold certain information.</p><p>Cookies are files with a small amount of data which may include an anonymous unique identifier. Cookies are sent to your browser from a website and stored on your device. Other tracking technologies are also used such as beacons, tags and scripts to collect and track information and to improve and analyze our Service.</p><p>You can instruct your browser to refuse all cookies or to indicate when a cookie is being sent. However, if you do not accept cookies, you may not be able to use some portions of our Service.</p><p>Examples of Cookies we use:</p><p>0.1. <b>Session Cookies:</b> We use Session Cookies to operate our Service.</p><p>0.2. <b>Preference Cookies:</b> We use Preference Cookies to remember your preferences and various settings.</p><p>0.3. <b>Security Cookies:</b> We use Security Cookies for security purposes.</p><p>0.4. <b>Advertising Cookies:</b> Advertising Cookies are used to serve you with advertisements that may be relevant to you and your interests.</p><p><b>Other Data</b></p><p>While using our Service, we may also collect the following information: sex, age, date of birth, place of birth, passport details, citizenship, registration at place of residence and actual address, telephone number (work, mobile), details of documents on education, qualification, professional training, employment agreements, non-disclosure agreements, information on bonuses and compensation, information on marital status, family members, social security (or other taxpayer identification) number, office location and other data.</p><p>5. <b>Use of Data</b></p><p>MyAdv India | A initiative by OfferPlant Technologies Private Limited uses the collected data for various purposes:</p><p>0.1. to provide and maintain our Service;</p><p>0.2. to notify you about changes to our Service;</p><p>0.3. to allow you to participate in interactive features of our Service when you choose to do so;</p><p>0.4. to provide customer support;</p><p>0.5. to gather analysis or valuable information so that we can improve our Service;</p><p>0.6. to monitor the usage of our Service;</p><p>0.7. to detect, prevent and address technical issues;</p><p>0.8. to fulfil any other purpose for which you provide it;</p><p>0.9. to carry out our obligations and enforce our rights arising from any contracts entered into between you and us, including for billing and collection;</p><p>0.10. to provide you with notices about your account and/or subscription, including expiration and renewal notices, email-instructions, etc.;</p><p>0.11. to provide you with news, special offers and general information about other goods, services and events which we offer that are similar to those that you have already purchased or enquired about unless you have opted not to receive such information;</p><p>0.12. in any other way we may describe when you provide the information;</p><p>0.13. for any other purpose with your consent.</p><p>6. <b>Retention of Data</b></p><p>We will retain your Personal Data only for as long as is necessary for the purposes set out in this Privacy Policy. We will retain and use your Personal Data to the extent necessary to comply with our legal obligations (for example, if we are required to retain your data to comply with applicable laws), resolve disputes, and enforce our legal agreements and policies.</p><p>We will also retain Usage Data for internal analysis purposes. Usage Data is generally retained for a shorter period, except when this data is used to strengthen the security or to improve the functionality of our Service, or we are legally obligated to retain this data for longer time periods.</p><p>7. <b>Transfer of Data</b></p><p>Your information, including Personal Data, may be transferred to – and maintained on – computers located outside of your state, province, country or other governmental jurisdiction where the data protection laws may differ from those of your jurisdiction.</p><p>If you are located outside Bihar, India and choose to provide information to us, please note that we transfer the data, including Personal Data, to Bihar, India and process it there.</p><p>Your consent to this Privacy Policy followed by your submission of such information represents your agreement to that transfer.</p><p>MyAdv India | A initiative by OfferPlant Technologies Private Limited will take all the steps reasonably necessary to ensure that your data is treated securely and in accordance with this Privacy Policy and no transfer of your Personal Data will take place to an organisation or a country unless there are adequate controls in place including the security of your data and other personal information.</p><p>8. <b>Disclosure of Data</b></p><p>We may disclose personal information that we collect, or you provide:</p><p>0.1. <b>Disclosure for Law Enforcement.</b></p><p>Under certain circumstances, we may be required to disclose your Personal Data if required to do so by law or in response to valid requests by public authorities.</p><p>0.2. <b>Business Transaction.</b></p><p>If we or our subsidiaries are involved in a merger, acquisition or asset sale, your Personal Data may be transferred.</p><p>0.3. <b>Other cases. We may disclose your information also:</b></p><p>0.3.1. to our subsidiaries and affiliates;</p><p>0.3.2. to contractors, service providers, and other third parties we use to support our business;</p><p>0.3.3. to fulfill the purpose for which you provide it;</p><p>0.3.4. for the purpose of including your company’s logo on our website;</p><p>0.3.5. for any other purpose disclosed by us when you provide the information;</p><p>0.3.6. with your consent in any other cases;</p><p>0.3.7. if we believe disclosure is necessary or appropriate to protect the rights, property, or safety of the Company, our customers, or others.</p><p>9. <b>Security of Data</b></p><p>The security of your data is important to us but remember that no method of transmission over the Internet or method of electronic storage is 100% secure. While we strive to use commercially acceptable means to protect your Personal Data, we cannot guarantee its absolute security.</p><p>10. <b>Service Providers</b></p><p>We may employ third party companies and individuals to facilitate our Service (<b>'Service Providers'</b>), provide Service on our behalf, perform Service-related services or assist us in analysing how our Service is used.</p><p>These third parties have access to your Personal Data only to perform these tasks on our behalf and are obligated not to disclose or use it for any other purpose.</p><p>11. <b>Analytics</b></p><p>We may use third-party Service Providers to monitor and analyze the use of our Service.</p><p>12. <b>CI/CD tools</b></p><p>We may use third-party Service Providers to automate the development process of our Service.</p><p>13. <b>Advertising</b></p><p>We may use third-party Service Providers to show advertisements to you to help support and maintain our Service.</p><p>14. <b>Behavioural Re-marketing</b></p><p>We may use re marketing services to advertise on third party websites to you after you visited our Service. We and our third-party vendors use cookies to inform, optimise and serve ads based on your past visits to our Service.</p><p>15. <b>Payments</b></p><p>We may provide paid products and/or services within Service. In that case, we use third-party services for payment processing (e.g. payment processors).</p><p>We will not store or collect your payment card details. That information is provided directly to our third-party payment processors whose use of your personal information is governed by their Privacy Policy. These payment processors adhere to the standards set by PCI-DSS as managed by the PCI Security Standards Council, which is a joint effort of brands like Visa, Mastercard, American Express and Discover. PCI-DSS requirements help ensure the secure handling of payment information.</p><p>16. <b>Links to Other Sites</b></p><p>Our Service may contain links to other sites that are not operated by us. If you click a third party link, you will be directed to that third party’s site. We strongly advise you to review the Privacy Policy of every site you visit.</p><p>We have no control over and assume no responsibility for the content, privacy policies or practices of any third party sites or services.</p><p>For example, the outlined Privacy Policy has been changed by offerplant using <a href='https://offerplant.com/'>OfferPlant.Com</a>, for generating high-quality legal documents for a website, blog, online store or app.</p><p>17. <b><b>Children’s Privacy</b></b></p><p>Our Services are not intended for use by children under the age of 18 (<b>'Child'</b> or <b>'Children'</b>).</p><p>We do not knowingly collect personally identifiable information from Children under 18. If you become aware that a Child has provided us with Personal Data, please contact us. If we become aware that we have collected Personal Data from Children without verification of parental consent, we take steps to remove that information from our servers.</p><p>18. <b>Changes to This Privacy Policy</b></p><p>We may update our Privacy Policy from time to time. We will notify you of any changes by posting the new Privacy Policy on this page.</p><p>We will let you know via email and/or a prominent notice on our Service, prior to the change becoming effective and update 'effective date' at the top of this Privacy Policy.</p><p>You are advised to review this Privacy Policy periodically for any changes. Changes to this Privacy Policy are effective when they are posted on this page.</p><p>19. <b>Contact Us</b></p><p>If you have any questions about this Privacy Policy, please contact us by email: <b>help@myadv.in</b>.</p><p class='text-muted'>I have <b>read, understand and accept </b> all the contents of privacy policy made on this website and app.</p></div>";


$("#apparea").on('click',"#privacy",function(){
	$("#appbody").html("<nav class='navbar fixed-bottom navbar-expand-lg navbar-light ' id='appfoot'> <a href='index.html'><img src='img/home.png' id='home' data-id='1' align='left' height='25px'></a> <img src='img/share.png' id='qprev' onclick='shot()' align='left' height='25px'> </nav>");
	$("#apphead").html("<img src='img/back.png' align='left' height='25px' onclick='location.reload()'> <div style='float:right'> <img src='img/logo.png' height='25px' > Privacy Policy </div>");
	$("#appbody").append(privacy);
	
});

/*--------About App ---*/
var about ="<div class='card mb-3'><div class='card-body'><h5 class='text-dark'>About MyAdv India</h5><p class='text-muted'>Version 5.0 (Premium Workspace) <br>Last Updated: 6 March 2026</p><hr><p><b>MyAdv India</b> is a state-of-the-art, secure digital workspace and comprehensive network hub designed explicitly for advocates, legal professionals, and the common citizens of India.</p><p>Our mission is to bridge the digital gap in the Indian legal ecosystem by delivering immediate accessibility, verification, and transparency.</p><h6>Key Features:</h6><ul style='padding-left:20px; list-style-type:disc;'><li><b>Unified Directory Search:</b> Seamlessly search and locate verified advocates across India by Name, Enrollment Year, Enrollment Number, Practice Area, or Mobile.</li><li><b>Online Display Board (ODB):</b> View real-time live display boards of the Supreme Court of India and various High Courts instantly.</li><li><b>eCourts Integration & Case Status:</b> Track live status and case situations directly from your mobile device.</li><li><b>Advanced Profile Controls:</b> Empowering advocates with privacy visibility levels (<code>PRIVATE</code>, <code>PUBLIC</code>, <code>ADVOCATE</code>, <code>REGISTERED</code>) to secure and control their public emails and contact numbers dynamically.</li></ul><hr><p class='mb-1'><b>A Initiative By:</b> OfferPlant Technologies Private Limited</p><p class='mb-1'><b>Official Website:</b> <a href='https://myadv.in' target='_blank'>https://myadv.in</a></p><p class='mb-0'><b>Support Email:</b> <a href='mailto:help@myadv.in'>help@myadv.in</a></p></div></div>";

$("#apparea").on('click',"#about",function(){
	$("#appbody").html("<nav class='navbar fixed-bottom navbar-expand-lg navbar-light ' id='appfoot'> <a href='index.html'><img src='img/home.png' id='home' data-id='1' align='left' height='25px'></a> <img src='img/share.png' id='qprev' onclick='shot()' align='left' height='25px'> </nav>");
	$("#apphead").html("<img src='img/back.png' align='left' height='25px' onclick='location.reload()'> <div style='float:right'> <img src='img/about_info.png' height='25px' style='filter: brightness(0.5) saturate(100%);'> About App </div>");
	$("#appbody").append(about);
});

/*--------FAQ ---*/

var faq ="<div id='accordion'><div class='card'><div class='card-header'> What is MyAdv India? <a class='card-link' data-toggle='collapse' href='#q1' style='float:right'><img src='img/plus_red.png' width='20px'></a></div><div id='q1' class='collapse' data-parent='#accordion'><div class='card-body'> MyAdv India is an online platform accessed through android application and website to help advocates and common people of India.</div></div></div><div class='card'><div class='card-header'> How to register?<a class='card-link' data-toggle='collapse' href='#q2' style='float:right'><img src='img/plus_red.png' width='20px'></a></div><div id='q2' class='collapse' data-parent='#accordion'><div class='card-body'> You may register via application or official website using mobile and email.</div></div></div><div class='card'><div class='card-header'> What is benefits of Registration?<a class='card-link' data-toggle='collapse' href='#q3' style='float:right'><img src='img/plus_red.png' width='20px'></a></div><div id='q3' class='collapse' data-parent='#accordion'><div class='card-body'> You may access about all features of the applications after registration.</div></div></div><div class='card'><div class='card-header'> Who is advocate? <a class='card-link' data-toggle='collapse' href='#q4' style='float:right'><img src='img/plus_red.png' width='20px'></a></div><div id='q4' class='collapse' data-parent='#accordion'><div class='card-body'> Advocate (defined in section 2 (a) of Advocates Act 1961) is a person who registered there name in any State Bar Council of India.</div></div></div><div class='card'><div class='card-header'> Who is Member? <a class='card-link' data-toggle='collapse' href='#q5' style='float:right'><img src='img/plus_red.png' width='20px'></a></div><div id='q5' class='collapse' data-parent='#accordion'><div class='card-body'> Every person who access the MyAdv India application, website, social platforms related to this MyAdv India in any manner is Member.</div></div></div><div class='card'><div class='card-header'> Who is registered member? <a class='card-link' data-toggle='collapse' href='#q6' style='float:right'><img src='img/plus_red.png' width='20px'></a></div><div id='q6' class='collapse' data-parent='#accordion'><div class='card-body'> Any person who is registered via any mode in this application using mobile number / email and other informations is registered member.</div></div></div><div class='card'><div class='card-header'> Who is verified advocate? <a class='card-link' data-toggle='collapse' href='#q6' style='float:right'><img src='img/plus_red.png' width='20px'></a></div><div id='q6' class='collapse' data-parent='#accordion'><div class='card-body'> Any advocate who is registered in this application / website verified by MyAdv India team is called verified advocate.</div></div></div><div class='card'><div class='card-header'> What is case status? <a class='card-link' data-toggle='collapse' href='#q7' style='float:right'><img src='img/plus_red.png' width='20px'></a></div><div id='q7' class='collapse' data-parent='#accordion'><div class='card-body'> Case status is the present situation of the case registered in any police station, court, tribunal.</div></div></div><div class='card'><div class='card-header'> What is ODB? <a class='card-link' data-toggle='collapse' href='#q8' style='float:right'><img src='img/plus_red.png' width='20px'></a></div><div id='q8' class='collapse' data-parent='#accordion'><div class='card-body'> ODB (Online Display Board) is used in court to display the cases in court hours.</div></div></div><div class='card'><div class='card-header'> What is profile? <a class='card-link' data-toggle='collapse' href='#q9' style='float:right'><img src='img/plus_red.png' width='20px'></a></div><div id='q9' class='collapse' data-parent='#accordion'><div class='card-body'> Any registered person may give information's about their own or others which are accessible to MyAdv India, user, public is called profile.</div></div></div><div class='card'><div class='card-header'> Who managed MyAdv India? <a class='card-link' data-toggle='collapse' href='#q10' style='float:right'><img src='img/plus_red.png' width='20px'></a></div><div id='q10' class='collapse' data-parent='#accordion'><div class='card-body'> Advocates of MyAdv India and OfferPlant Technologies Private Limited (legal jurisdiction district Saran, Bihar) manages informations online and offline.</div></div></div><div class='card'><div class='card-header'> How MyAdv India helps people? <a class='card-link' data-toggle='collapse' href='#q11' style='float:right'><img src='img/plus_red.png' width='20px'></a></div><div id='q11' class='collapse' data-parent='#accordion'><div class='card-body'> MyAdv India share information to the users (unregistered people, registered members, advocates, bar councils, bar associations, bar council of India, state and central government of India and others) to help each others for legal , technical and other support.</div></div></div><div class='card'><div class='card-header'> What is Terms and Conditions and Privacy Policy? <a class='card-link' data-toggle='collapse' href='#q12' style='float:right'><img src='img/plus_red.png' width='20px'></a></div><div id='q12' class='collapse' data-parent='#accordion'><div class='card-body'>  <p> <b id='terms' >Terms and Conditions </b> and <b id='privacy'> Privacy Policy </b> are set off principles as well as conditions to use any services. </p> </div></div></div></div>";

$("#apparea").on('click',"#faq",function(){
	$("#appbody").html('');
	$("#apphead").html("<img src='img/back.png' align='left' height='25px' onclick='location.reload()'> <div style='float:right'> <img src='img/faq.png' height='25px' style='filter: brightness(0.5) saturate(100%);'> Frequently Ask Questions </div>");
	$("#appbody").append(faq);
	
});



var case_status = "<ul class='list-group'><li class='inapp-link list-group-item' data-url='https://main.sci.gov.in/case-status'><i class='fa fa-star color-yellow1-dark'></i> <span> Supreme Court of India </span> <em class='bg-green2-dark'>SCI</em> <i class='fa fa-angle-right'></i></li><li class='list-group-item'> <a href='http://www.allahabadhighcourt.in/apps/status/'><span>Allahabad High Court- Allahabad </span><i class='fa fa-angle-right'></i></a></li><li class='list-group-item'> <a href='http://tshcstatus.nic.in/csis_ap/'> <span> Andhra Pradesh High Court</span> <i class='fa fa-angle-right'></i></a></li><li class='list-group-item'><a href='http://bombayhighcourt.nic.in/'> <span> Bombay High Court - Mumbai</span> <i class='fa fa-angle-right'></i></a></li><li class='list-group-item'><a href='http://www.hcbombayatgoa.nic.in/'> <span> Bombay High Court - Goa </span> <i class='fa fa-angle-right'></i></a></li><li class='inapp-link list-group-item' data-url='https://www.calcuttahighcourt.gov.in/Case-Status'> <span> Calcutta High Court</span> <i class='fa fa-angle-right'></i></li><li class='inapp-link list-group-item' data-url='https://services.ecourts.gov.in/ecourtindiaHC/index_highcourt.php?state_cd=18&dist_cd=1&stateNm=Chhattisgarh'> <span> Chhattisgarh High Court </span><i class='fa fa-angle-right'></i></li><li class='inapp-link list-group-item' data-url='https://delhihighcourt.nic.in/case.asp'> <span> Delhi High Court </span> <i class='fa fa-angle-right'></i></li><li class='inapp-link list-group-item' data-url='https://services.ecourts.gov.in/ecourtindiaHC/index_highcourt.php?state_cd=6&dist_cd=1&stateNm=Assam'> <span> Gauhati High Court</span></li><li class='inapp-link list-group-item' data-url='https://gujarathc-casestatus.nic.in/gujarathc/'> <span> Gujarat High Court</span> <i class='fa fa-angle-right'></i></li><li class='list-group-item'> <a href='http://hphighcourt.nic.in/'> <span> Himachal Pradesh High Court </span><i class='fa fa-angle-right'></i></a></li><li class='inapp-link list-group-item' data-url='https://services.ecourts.gov.in/ecourtindiaHC/index_highcourt.php?state_cd=12&dist_cd=1&stateNm=Jammu%20and%20Kashmir'><span> Jammu & Kashmir High Court - Jammu </span> <i class='fa fa-angle-right'></i></li><li class='inapp-link list-group-item' data-url='https://services.ecourts.gov.in/ecourtindiaHC/index_highcourt.php?state_cd=12&dist_cd=1&court_code=2&stateNm=Jammu%20and%20Kashmir'><span> Jammu & Kashmir High Court - Srinagar </span> <i class='fa fa-angle-right'></i></li><li class='inapp-link list-group-item' data-url='https://services.ecourts.gov.in/ecourtindiaHC/index_highcourt.php?state_cd=7&dist_cd=1&stateNm=Jharkhand'> <span> Jharkhand High Court </span> <i class='fa fa-angle-right'></i></li><li class='inapp-link list-group-item' data-url='https://karnatakajudiciary.kar.nic.in/case_status.asp'> <span> Karnataka High Court </span> <i class='fa fa-angle-right'></i></li><li class='inapp-link list-group-item' data-url='https://services.ecourts.gov.in/ecourtindiaHC/index_highcourt.php?state_cd=4&dist_cd=1&stateNm=Kerala'> <span> Kerala High Court <br> <i class='fa fa-angle-right'></i></li><li class='inapp-link list-group-item' data-url='https://mphc.gov.in/case-status'> <span>Madhya Pradesh High Court </span> <i class='fa fa-angle-right'></i></li><li class='list-group-item'><a href='https://www.hcmadras.tn.nic.in/casestatus.html#casest'> <span> Madras High Court </span> <i class='fa fa-angle-right'></i></a></li><li class='inapp-link list-group-item' data-url='https://services.ecourts.gov.in/ecourtindiaHC/index_highcourt.php?state_cd=21&dist_cd=1&stateNm=Meghalaya'> <span> Meghalaya High Court</span></li><li class='inapp-link list-group-item' data-url='https://services.ecourts.gov.in/ecourtindiaHC/index_highcourt.php?state_cd=11&dist_cd=1&stateNm=Odisha'> <span> Orissa High Court </span></li><li class='inapp-link list-group-item' data-url='https://services.ecourts.gov.in/ecourtindiaHC/index_highcourt.php?state_cd=25&dist_cd=1&stateNm=Manipur'> <span> Manipur High Court </span></li><li class='list-group-item'> <a href='http://patnahighcourt.gov.in/'> <span> Patna High Court </span><i class='fa fa-angle-right'></i></a></li><li class='inapp-link list-group-item' data-url='https://phhc.gov.in'> <span> Punjab & Haryana High Court </span> <i class='fa fa-angle-right'></i></li><li class='inapp-link list-group-item' data-url='https://hcraj.nic.in/cishcraj-jdp/'> <span> Rajasthan High Court - Jodhpur </span> <i class='fa fa-angle-right'></i></li><li class='inapp-link list-group-item' data-url='https://hcraj.nic.in/cishcraj-jp/'> <span> Rajasthan High Court - Jaipur </span><i class='fa fa-angle-right'></i></li><li class='inapp-link list-group-item' data-url='https://highcourtofsikkim.nic.in/hcs/hcourt/hg_case_search'> <span> Sikkim High Court</span></li><li class='list-group-item'> <a href='http://tshcstatus.nic.in/'> <span> Telangana High Court</span><i class='fa fa-angle-right'></i></a></li><li class='list-group-item'><a href='http://thc.nic.in/cstatus.html'> <span> Tripura High Court</span> </a></li><li class='inapp-link list-group-item' data-url='https://services.ecourts.gov.in/ecourtindiaHC/index_highcourt.php?state_cd=15&dist_cd=1&stateNm=Uttarakhand'> <span> Uttarakhand High Court </span> <i class='fa fa-angle-right'></i></li><li class='inapp-link list-group-item' data-url='https://hcservices.ecourts.gov.in/hcservices/'> <span> All High Court - eCourts </span> <i class='fa fa-angle-right'></i></li><li class='inapp-link list-group-item' data-url='https://services.ecourts.gov.in/ecourtindia_v6/'> <span> All District Court - eCourts </span> <i class='fa fa-angle-right'></i></li><li class='inapp-link list-group-item' data-url='https://advocate.morg.in/case_status.php'> <span> Case Status - MyAdv India </span> <i class='fa fa-angle-right'></i></li></ul>";

$("#apparea").on('click',"#case",function(){
	$("#appbody").html('');
	$("#apphead").html("<img src='img/back.png' align='left' height='25px' onclick='location.reload()'> <div style='float:right'> <img src='img/case.png' height='25px' style='filter: brightness(0.5) saturate(100%);'> Case Status </div>");
	$("#appbody").append(case_status);
	
});


$("#apparea").on("click", '#subject_list, #subject_back',function(){

	$.ajax({
		type:'POST',
		url:base_url+'task=subject_list',
		beforeSend:function(){
			$("#loader").show();
		},
		success:function(data)
		{
			$("#apphead").html("<img src='img/back.png' align='left' height='25px' onclick='location.reload()'> <div style='float:right'> <img src='img/question.png' height='25px' style='filter: brightness(0.5) saturate(100%);'> Subject List </div>");
			$("#appbody").html("");
			obj = JSON.parse(data);
			// console.log(data);
			for(var i =0; i<obj.count; i++)
			{
			var list ="<div class='subject_list col-12 my-1 btn btn-border border-danger' data-subject='"+obj.data[i].id+"' data-subjectname ='"+obj.data[i].name+"'>"+obj.data[i].name+"</div>";
			$("#appbody").append(list);
			}
		},
		complete:function()
		{
			$("#loader").hide();
		}
	});
});

$("#apparea").on("click",".subject_list", function(){
	
	var sub_id = $(this).data('subject');
	var subjectname = $(this).data('subjectname');
	$.ajax({
		type:'POST',
		url:base_url+'task=question_list',
		data:{'subject_id':sub_id},
		beforeSend:function(){
			$("#loader").show();
		},
		success:function(res)
		{
			var obj = JSON.parse(res);
			$("#apphead").html("<img src='img/back.png' align='left' height='25px' id='subject_back'> <div style='float:right'> <img src='img/question.png' height='25px' style='filter: brightness(0.5) saturate(100%);'> "+ subjectname+" </div>");
			
			$("#appbody").html('');	
			if(obj.count>0)
			{
								
				for(var i=0;  i<obj.count; i++)
				{
					var qno=i+1;
					var x = obj['data'][i];
					var oe ='';
					if(x.option_e !='')
					{
						var oe = "E. <input type='radio' class='optR E' name='ans_"+x.id+"' value='E'>&nbsp;"+x.option_e+"<br>";
					}
					var data = "<div class='card m-2'><div class='card-body'><b>"+qno+ ". " +x.details+"</b></span><br>A. <input type='radio' class='optR A' name='ans_"+x.id+"' value='A'>&nbsp;"+x.option_a+"<br>B. <input type='radio' name='ans_"+x.id+"' class='optR B' value='B'>&nbsp;"+x.option_b+"<br>C. <input type='radio' name='ans_"+x.id+"' class='optR C' value='C'>&nbsp;"+x.option_c+"<br>D. <input type='radio' name='ans_"+x.id+"' class='optR D' value='D'>&nbsp;"+x.option_d+"<br>"+oe+"<span class='ans' style='display:none'>"+x.answer+"</span></div></div>";
					// console.log(data);
					$("#appbody").append(data);	
				}
			}
			else{
				$("#appbody").append("<div class='text-danger text-center'> No question found <div class='btn btn-info btn-block' id='subject_back'>Go Back </div></div>");
			}
				 
			
			
		},
		complete:function()
		{
			$("#loader").hide();
		}
	});
});

$("#appbody").on("click", ".optR", function(){
	var ya = $(this).val();
	var oa = $(this).parent('.card-body').find('.ans').text();
	if(ya==oa)
	{
		$(this).parent('.card-body').find('.ans').css({'display':'block'});
		$(this).parent('.card-body').find('.ans').addClass('bg-success text-center text-light p-2');
	}
	else{
		$(this).parent('.card-body').find('.ans').css({'display':'block'});
		$(this).parent('.card-body').find('.ans').addClass('bg-danger p-2 text-center text-light');
	
	}
});
$("#apparea").on("click", '#exam_list, #exam_back',function(){

	$.ajax({
		type:'POST',
		url:base_url+'task=exam_list',
		beforeSend:function(){
			$("#loader").show();
		},
		success:function(data)
		{
			$("#apphead").html("<img src='img/back.png' align='left' height='25px' onclick='location.reload()'> <div style='float:right'> <img src='img/exam.png' height='25px' style='filter: brightness(0.5) saturate(100%);'> Previous Examination </div>");
			$("#appbody").html("");
			obj = JSON.parse(data);
			// console.log(data);
			for(var i =0; i<obj.count; i++)
			{
			var list ="<div class='exam_list col-12 my-1 btn btn-border border-danger' data-exam='"+obj.data[i].id+"' data-examname ='"+obj.data[i].name+"'>"+obj.data[i].name+"</div>";
			$("#appbody").append(list);
			}
		},
		complete:function()
		{
			$("#loader").hide();
		}
	});
});


// My Case 
$("#apparea").on("click", '#case_list, #case_back',function(){
	var mobile =localStorage.getItem('myadv_mobile');
	$.ajax({
		type:'POST',
		url:base_url_ed+'task=case_list&myadv_mobile='+mobile,
		beforeSend:function(){
			$("#loader").show();
		},
		success:function(data)
		{
			$("#apphead").html("<img src='img/back.png' align='left' height='25px' onclick='location.reload()'> <div style='float:right'> <img src='img/mycase.png' height='25px' style='filter: brightness(0.5) saturate(100%);'> My Case </div>");
			$("#appbody").html("");
			obj = JSON.parse(data);
			 //console.log(data);
			if (obj.count>0){
			for(var i =0; i<obj.count; i++)
			{
			var list ="<div class='case_list col-12 my-1 btn btn-border border-danger' data-caseid='"+obj.data[i].case_id+"' data-casenumber ='"+obj.data[i].case_number+" '> In the court of <b>"+obj.data[i].court_name+ "</b><br>"+obj.data[i].ctype_name+ " : " +obj.data[i].case_number +"/"+ obj.data[i].case_year+ "(" +obj.data[i].case_reg_number +"/"+ obj.data[i].case_reg_year+ ")</b> <br>" +obj.data[i].cfp_name+" Vs " +obj.data[i].csp_name +" <br> Date: <b>"  +obj.data[i].next_date+ "</b> ("+obj.data[i].case_status + ")<br> CNR: <b>"  +obj.data[i].cnr+ "</b> Party : <b>"+obj.data[i].our_party + "</b></div>";
			$("#appbody").append(list);
			}
			}
			else { 
			$("#appbody").append("<div class='text-danger text-center'> No case found <div class='btn btn-danger btn-block' onclick='location.reload()' > Go Back </div></div>");
			alert ("No case found");
			}
			
		},
		complete:function()
		{
			$("#loader").hide();
		}
	});
});

$("#apparea").on("click", '#ref_case_list, #ref_case_back',function(){
	var mobile =localStorage.getItem('myadv_mobile');
	$.ajax({
		type:'POST',
		url:base_url_ed+'task=ref_case_list&myadv_mobile='+mobile,
		beforeSend:function(){
			$("#loader").show();
		},
		success:function(data)
		{
			$("#apphead").html("<img src='img/back.png' align='left' height='25px' onclick='location.reload()'> <div style='float:right'> <img src='img/myref.png' height='25px' style='filter: brightness(0.5) saturate(100%);'> My Ref Case </div>");
			$("#appbody").html("");
			obj = JSON.parse(data);
			// console.log(data);
			if (obj.count>0){
			for(var i =0; i<obj.count; i++)
			{
			var list ="<div class='case_list col-12 my-1 btn btn-border border-danger' data-caseid='"+obj.data[i].case_id+"' data-casenumber ='"+obj.data[i].case_number+" '> In the court of <b>"+obj.data[i].court_name+ "</b><br>"+obj.data[i].ctype_name+ " : " +obj.data[i].case_number +"/"+ obj.data[i].case_year+ "</b> (" +obj.data[i].case_reg_number +"/"+ obj.data[i].case_reg_year+ ") <br>" +obj.data[i].cfp_name+" Vs " +obj.data[i].csp_name +" <br> Date: <b>"  +obj.data[i].next_date+ "</b> ("+obj.data[i].case_status + ")<br> CNR:  <b>"  +obj.data[i].cnr+ "</b> Party : <b>"+obj.data[i].our_party + "</b></div>";
			$("#appbody").append(list);
			}
			}
			else { 
			$("#appbody").append("<div class='text-danger text-center'> No case found <div class='btn btn-danger btn-block' onclick='location.reload()' > Go Back </div></div>");
			alert ("No case found");
			}
			
		},
		complete:function()
		{
			$("#loader").hide();
		}
	});
});


$("#apparea").on("click",".exam_list", function(){
	
	var exam_id = $(this).data('exam');
	var examname = $(this).data('examname');
	$.ajax({
		type:'POST',
		url:base_url+'task=question_list_exam',
		data:{'exam_id':exam_id},
		beforeSend:function(){
			$("#loader").show();
		},
		success:function(res)
		{
			var obj = JSON.parse(res);
			$("#apphead").html("<img src='img/back.png' align='left' height='25px' id='exam_back'> <div style='float:right'> <img src='img/exam.png' height='25px' style='filter: brightness(0.5) saturate(100%);'> "+ examname+" </div>");
			
			$("#appbody").html('');	
			if(obj.count>0)
			{
				
				for(var i=0;  i<obj.count; i++)
				{
					var qno=i+1;
					var x = obj['data'][i];
					var oe ='';
					if(x.option_e !='')
					{
						var oe = "E. <input type='radio' class='optR E' name='ans_"+x.id+"' value='E'>&nbsp;"+x.option_e+"<br>";
					}
					var data = "<div class='card m-2'><div class='card-body'><b>"+qno+ ". " +x.details+"</b></span><br>A. <input type='radio' class='optR A' name='ans_"+x.id+"' value='A'>&nbsp;"+x.option_a+"<br>B. <input type='radio' name='ans_"+x.id+"' class='optR B' value='B'>&nbsp;"+x.option_b+"<br>C. <input type='radio' name='ans_"+x.id+"' class='optR C' value='C'>&nbsp;"+x.option_c+"<br>D. <input type='radio' name='ans_"+x.id+"' class='optR D' value='D'>&nbsp;"+x.option_d+"<br>"+oe+"<span class='ans' style='display:none'>"+x.answer+"</span></div></div>";
					// console.log(data);
					$("#appbody").append(data);	
				}
			}
			else{
				$("#appbody").append("<div class='text-danger text-center'> No question found <div class='btn btn-info btn-block' id='exam_back'>Go Back </div></div>");
			}
			
		},
		complete:function()
		{
			$("#loader").hide();
		}
	});
});


$("#appbody").on("click", ".optR", function(){
	var ya = $(this).val();
	var oa = $(this).parent('.card-body').find('.ans').text();
	if(ya==oa)
	{
		$(this).parent('.card-body').find('.ans').css({'display':'block'});
		$(this).parent('.card-body').find('.ans').addClass('badge badge-success text-center text-light');
	}
	else{
		$(this).parent('.card-body').find('.ans').css({'display':'block'});
		$(this).parent('.card-body').find('.ans').addClass('badge badge-danger text-center text-light');
	
	}
});
 

$("#bc_list").on("click", function(){

	$.ajax({
		type:'POST',
		url:base_url+'task=bc_list',
		beforeSend:function(){
			$("#loader").show();
		},
		success:function(data)
		{
			$("#apphead").html("<img src='img/back.png' align='left' height='25px' onclick='location.reload()'> <div style='float:right'> <img src='img/bar.png' height='25px' style='filter: brightness(0.5) saturate(100%);'> Bar Council </div>");
			$("#appbody").html("<select name='bc' class='form-control mb-2' id='bc_list'></select><div id='bc_info'></div>"); 
				$("#bc_list").append(data);
		},
		complete:function()
		{
			$("#loader").hide();
		}
	});
});

$("#apparea").on("change","#bc_list", function(){
	
	var id = $(this).val();
	$.ajax({
		type:'POST',
		url:base_url+'task=bc_info',
		data:{'id':id},
		beforeSend:function(){
			$("#loader").show();
		},
		success:function(res)
		{
			var obj = JSON.parse(res);
			
			
			if(obj.count>0)
			{
				
				for(var i=0;  i<obj.count; i++)
				{
					var x = obj['data'][i];
					// console.log(x);
					var data = "<div class='card mb-3'> <div class='card-body'> <span class='text-info'><h6>"+x.name+"</h6></span><hr> <p>Address :&nbsp; "+x.address+"</p> <p>District : &nbsp; "+x.district_name+"</p><p>State : &nbsp;"+x.state_name+" &nbsp; PIN Code : "+x.pincode+"</p><p> Establishment Year : &nbsp;"+x.year+"</p> <p>Working Area (State & UT) :&nbsp; "+x.state_for+"</p><p> Telephone : +91 "+x.tel+"</p><p>Email - "+x.email+"</p><p>Website :&nbsp; <a href="+x.url+">"+x.url+"</a></p> ";
					// console.log(data);
					$("#bc_info").html(data);	
				}
			}
			else{
				$("#appbody").append("<div class='text-danger text-center'> No Information Available </div>");
			}
				 
			
			
		},
		complete:function()
		{
			$("#loader").hide();
		}
	});
});

//============ New in 4.1 ============= //
//----------- Public service Commission Start----------- //
$("#apparea").on("click", '#psc_list, #psc_back',function(){

	$.ajax({
		type:'POST',
		url:base_url+'task=psc_list',
		beforeSend:function(){
			$("#loader").show();
		},
		success:function(data)
		{
			$("#apphead").html("<img src='img/back.png' align='left' height='25px' onclick='location.reload()'> <div style='float:right'> <img src='img/list_line.png' height='25px' style='filter: brightness(0.5) saturate(100%);'> &nbsp; Public Service Commission List</div>");
			$("#appbody").html("");
			obj = JSON.parse(data);
			for(var i =0; i<obj.count; i++)
			{
			var list ="<div class='psc_list col-12 my-1 btn btn-border border-danger' data-psc='"+obj.data[i].id+"' data-pscname ='"+obj.data[i].name+"'>"+obj.data[i].name+"</div>";
			$("#appbody").append(list);
			}
		},
		complete:function()
		{
			$("#loader").hide();
		}
	});
});

$("#apparea").on("click",".psc_list", function(){
	var psc_id = $(this).data('psc');
	var pscname = $(this).data('pscname');
	$.ajax({
		type:'POST',
		url:base_url+'task=psc_info',
		data:{'id':psc_id},
		beforeSend:function(){
			$("#loader").show();
		},
		success:function(res)
		{
			var obj = JSON.parse(res);
			$("#apphead").html("<img src='img/back.png' align='left' height='25px' id='psc_back'> <div style='float:right'> <img src='img/list_line.png' height='25px' style='filter: brightness(0.5) saturate(100%);'>&nbsp; Public Service Commission Info </div>");
			$("#appbody").html('');	
			if(obj.count>0)
			{
				for(var i=0;  i<obj.count; i++)
				{
					var x = obj['data'][i];
					var data = "<div class='card m-2'><div class='card-body'><b>" +x.name+"</b></span><br> <b> Address:</b> "+x.address+"<br> <b>Pincode:</b> "+x.pincode+"<br> <b>Telephone: </b>"+x.tel+"<br> <b>Establishment Year: </b>"+x.year+"<br><b>Email: </b><a href=mailto:"+x.email+">"+x.email+" </a><b></br>Website:</b> <a href="+x.url+">"+x.url+" </a><i><br>Last Update On: "+x.updated_at+" </i><br></div></div>";
					$("#appbody").append(data);	
				}
			}
			else{
				$("#appbody").append("<div class='text-danger text-center'> No information found <div class='btn btn-info btn-block' id='psc_back'>Go Back </div></div>");
			}
		},
		complete:function()
		{
			$("#loader").hide();
		}
	});
});


//========== BC ========//
$("#apparea").on("click", '#bc_all_list, #bc_all_back',function(){

	$.ajax({
		type:'POST',
		url:base_url+'task=bc_all_list',
		beforeSend:function(){
			$("#loader").show();
		},
		success:function(data)
		{
			$("#apphead").html("<img src='img/back.png' align='left' height='25px' onclick='location.reload()'> <div style='float:right'> <img src='img/bar.png' height='25px' style='filter: brightness(0.5) saturate(100%);'> &nbsp;State Bar Council List</div>");
			$("#appbody").html("");
			obj = JSON.parse(data);
			// console.log(data);
			for(var i =0; i<obj.count; i++)
			{
			var list ="<div class='bc_all_list col-12 my-1 btn btn-border border-danger' data-bc='"+obj.data[i].id+"' data-bcname ='"+obj.data[i].name+"'>"+obj.data[i].name+"</div>";
			$("#appbody").append(list);
			}
		},
		complete:function()
		{
			$("#loader").hide();
		}
	});
});

$("#apparea").on("click",".bc_all_list", function(){
	
	var bc_id = $(this).data('bc');
	var bcname = $(this).data('bcname');
	$.ajax({
		type:'POST',
		url:base_url+'task=bc_info',
		data:{'id':bc_id},
		beforeSend:function(){
			$("#loader").show();
		},
		success:function(res)
		{
			var obj = JSON.parse(res);
			$("#apphead").html("<img src='img/back.png' align='left' height='25px' id='bc_all_back'> <div style='float:right'> <img src='img/bar.png' height='25px' style='filter: brightness(0.5) saturate(100%);'> &nbsp; State Bar Council Info </div>");
			
			$("#appbody").html('');	
			if(obj.count>0)
			{
				
				for(var i=0;  i<obj.count; i++)
				{
					var x = obj['data'][i];
					var data = "<div class='card m-2'><div class='card-body'><b>" +x.name+"</b></span><br> <b> Address:</b> "+x.address+"<br> <b>Pincode:</b> "+x.pincode+"<br> <b>Telephone: </b>"+x.tel+"<br> <b>Establishment Year: </b>"+x.year+"<br><b>Email: </b><a href=mailto:"+x.email+">"+x.email+" </a><b></br>Website:</b> <a href="+x.url+">"+x.url+" </a><i><br>Last Update On: "+x.updated_at+" </i><br></div></div>";
					$("#appbody").append(data);	
				}
			}
			else{
				$("#appbody").append("<div class='text-danger text-center'> No information found <div class='btn btn-info btn-block' id='bc_all_back'>Go Back </div></div>");
			}
		},
		complete:function()
		{
			$("#loader").hide();
		}
	});
});

//========== BA ========//

$("#apparea").on("click", '#ba_all_list, #ba_all_back',function(){

	$.ajax({
		type:'POST',
		url:base_url+'task=ba_all_list',
		beforeSend:function(){
			$("#loader").show();
		},
		success:function(data)
		{
			$("#apphead").html("<img src='img/back.png' align='left' height='25px' onclick='location.reload()'> <div style='float:right'> <img src='img/bar.png' height='25px' style='filter: brightness(0.5) saturate(100%);'> &nbsp;Bar Association List</div>");
			$("#appbody").html("");
			obj = JSON.parse(data);
			// console.log(data);
			for(var i =0; i<obj.count; i++)
			{
			var list ="<div class='ba_all_list col-12 my-1 btn btn-border border-danger' data-ba='"+obj.data[i].id+"' data-baname ='"+obj.data[i].name+"'>"+obj.data[i].name+"</div>";
			$("#appbody").append(list);
			}
		},
		complete:function()
		{
			$("#loader").hide();
		}
	});
});

$("#apparea").on("click",".ba_all_list", function(){
	
	var ba_id = $(this).data('ba');
	var baname = $(this).data('baname');
	$.ajax({
		type:'POST',
		url:base_url+'task=ba_info',
		data:{'id':ba_id},
		beforeSend:function(){
			$("#loader").show();
		},
		success:function(res)
		{
			var obj = JSON.parse(res);
			$("#apphead").html("<img src='img/back.png' align='left' height='25px' id='ba_all_back'> <div style='float:right'> <img src='img/bar.png' height='25px' style='filter: brightness(0.5) saturate(100%);'> &nbsp;Bar Association Info </div>");
			
			$("#appbody").html('');	
			if(obj.count>0)
			{
				
				for(var i=0;  i<obj.count; i++)
				{
					var x = obj['data'][i];
					var data = "<div class='card m-2'><div class='card-body'><b>" +x.name+"</b></span><br> <b> Address:</b> "+x.address+" <br> <b> District:</b> "+x.district_name+"<br> <b> State / UT:</b> "+x.state_name+"<br> <b>Pincode:</b> "+x.pincode+"<br> <b>Telephone: </b>"+x.tel+"<br> <b>Establishment Year: </b>"+x.year+"<br><b>Email: </b><a href=mailto:"+x.email+">"+x.email+" </a><b></br>Website:</b> <a href="+x.url+">"+x.url+" </a><i><br>Last Update On: "+x.updated_at+" </i><br></div></div>";
					$("#appbody").append(data);	
				}
			}
			else{
				$("#appbody").append("<div class='text-danger text-center'> No BA information found <div class='btn btn-info btn-block' id='ba_all_back'>Go Back </div></div>");
			}
		},
		complete:function()
		{
			$("#loader").hide();
		}
	});
});


$("#univ_list").on("click", function(){

	$.ajax({
		type:'POST',
		url:base_url+'task=state_listdd',
		beforeSend:function(){
			$("#loader").show();
		},
		success:function(data)
		{
			$("#apphead").html("<img src='img/back.png' align='left' height='25px' onclick='location.reload()'> <div style='float:right'> <img src='img/college.png' height='25px' style='filter: brightness(0.5) saturate(100%);'> University List </div>");
			$("#appbody").html("<p> Select a state or UT for university details </p> <select name='state' class='form-control mb-2' id='univ_state_listdd'></select><div id='univ_info'></div>");
				$("#univ_state_listdd").append(data);
				
		},
		complete:function()
		{
			$("#loader").hide();
		}
	});
});

$("#apparea").on("change","#univ_state_listdd", function(){
	
	var state_code = $(this).val();
	// console.log(state_code);
	$.ajax({
		type:'POST',
		url:base_url+'task=univ_info',
		data:{'state_code':state_code},
		beforeSend:function(){
			$("#loader").show();
		},
		success:function(res)
		{
			var obj = JSON.parse(res);
			
			
			if(obj.count>0)
			{
				
				for(var i=0;  i<obj.count; i++)
				{
					var x = obj['data'][i];
					
					var data = " <div class='card mt-2'><div class='card-body'> <span class='text-danger'><h6>"+x.name+"</h6></span><hr> <p>Type :&nbsp; "+x.type+"</p> <p>Code : &nbsp; "+x.code+"</p><p> Address : &nbsp; "+x.address+"</p><p> District : &nbsp; "+x.district_name+"</p><p>State : &nbsp;"+x.state_name+" &nbsp; <p>Establishment Year : &nbsp;"+x.year+" &nbsp;</p><p> Location : &nbsp;"+x.location+"</p><p> Speciality : "+x.speciality+"</p><p> Affiliated College : "+x.affiliated_college+"</p><p> Constitute College : "+x.constituent_college+"</p><p>Details visit :&nbsp; <a href=+x.url+>"+x.url+"</a></p></div></div>"
					
					$("#univ_info").append(data);
					
					
				}
			}
			else{
				$("#appbody").append("<div class='text-danger text-center'> No Information Available </div>");
			}
				 
			
			
		},
		complete:function()
		{
			$("#loader").hide();
		}
	});
});
//================ Search Advocate ================== //
$('#apparea').on("click", "#search_advocate", function(){
	$.ajax({
		type:'POST',
		url:base_url+'task=state_listdd',
		beforeSend:function(){
			$("#loader").show();
		},
		success:function(data)
		{
			$("#apphead").html("<img src='img/back.png' align='left' height='25px' onclick='location.reload()'> <div style='float:right'> <img src='img/search.png' height='25px' > Search Advocate By Name </div>");
			$("#appbody").html("<select name='state' class='form-control mb-2' id='search_state_list'></select><select name='district' class='form-control mb-2' id='search_district_list'></select><input type='text' id='search_advocate_name' class='form-control' placeholder='At least 5 character' > <button class='btn btn-danger btn-block mt-2' id='search_advocate_btn' >Search Advocate </button>");
				$("#search_state_list").append(data);
				
		},
		complete:function()
		{
			$("#loader").hide();
		}
	});
});



$("#apparea").on("click","#search_advocate_btn", function(){
	
	var state = $("#search_state_list").val();
	var district = $("#search_district_list").val();
	var name = $("#search_advocate_name").val();
	if(name.length <5 || district =='' )
	{
		alert("Check all Field ")
	}
	else{
		$.ajax({
			type:'POST',
			url:base_url+'task=search_advocate',
			data:{'state_code':state,'district_code':district,'name':name},
			beforeSend:function(){
				$("#loader").show();
			},
			success:function(data)
			{
				obj = JSON.parse(data);
				// console.log(obj);
				if(obj.count>0)
			{
				$("#appbody").html("<div class='bg-gray text-danger'>" + obj.count +" Advocate Available </div>");	
				if (obj.count<20)
					{
						var ct=obj.count;
					}
				else{
						ct=20;
					}
				for(var i=0;  i<ct; i++)
				{
					var x = obj['data'][i];
					var disp_mobile = get_disp_contact(x.mobile, x.mobile_visibility, 'mobile');
					var disp_email  = get_disp_contact(x.email, x.email_visibility, 'email');
					var data = " <div class='card mt-1'><div class='card-body'> <span class='text-danger'><b>"+x.name+" &nbsp; <img src='img/"+x.status+".png'></b></span><hr>Mobile :&nbsp; "+disp_mobile+" &nbsp; <img src='img/"+x.mobile_status+".png'><br>Email : &nbsp; "+disp_email+"<br>Member : &nbsp; "+x.bc_name+"<br>Practicing Since : &nbsp; "+x.e_year+"<br>From : &nbsp;"+x.district_name+",&nbsp;"+x.state_name+"  </div></div>";
					
					$("#appbody").append(data);	
					
				}
			}
			else{
				$("#appbody").append("<div class='text-danger text-center'> No Information Available </div>");
			}
			},
			complete:function()
			{
				$("#loader").hide();
				
			}
		});
	}
});

$('#apparea').on("click", "#search_advocate_year", function(){
	$.ajax({
		type:'POST',
		url:base_url+'task=state_listdd',
		beforeSend:function(){
			$("#loader").show();
		},
		success:function(data)
		{
			$("#apphead").html("<img src='img/back.png' align='left' height='25px' id='search_back'> <div style='float:right'> <img src='img/search.png' height='25px' > Search Advocate By Year </div>");
			$("#appbody").html("<select name='state' class='form-control mb-2' id='search_state_list'></select><select name='district' class='form-control mb-2' id='search_district_list'></select><input type='tel' id='search_advocate_name_year' class='form-control' placeholder='Year' > <button class='btn btn-danger btn-block mt-2' id='search_advocate_btn_year' >Search Advocate By Year </button>");
				$("#search_state_list").append(data);
				
		},
		complete:function()
		{
			$("#loader").hide();
		}
	});
});



$("#apparea").on("click","#search_advocate_btn_year", function(){
	
	var state = $("#search_state_list").val();
	var district = $("#search_district_list").val();
	var year = $("#search_advocate_name_year").val();
	if(year.length <4 || district =='' )
	{
		alert("Check all Field ")
	}
	else{
		$.ajax({
			type:'POST',
			url:base_url+'task=search_advocate_year',
			data:{'state_code':state,'district_code':district,'e_year':year},
			beforeSend:function(){
				$("#loader").show();
			},
			success:function(data)
			{
				obj = JSON.parse(data);
				// console.log(obj);
				if(obj.count>0)
			{
				$("#appbody").html("<div class='bg-gray text-danger'>" + obj.count +" Advocate Available </div>");	
				if (obj.count<30)
					{
						var ct=obj.count;
					}
				else{
						ct=30;
					}
				for(var i=0;  i<ct; i++)
				{
					var x = obj['data'][i];
					var disp_mobile = get_disp_contact(x.mobile, x.mobile_visibility, 'mobile');
					var disp_email  = get_disp_contact(x.email, x.email_visibility, 'email');
					var data = " <div class='card mt-1'><div class='card-body'> <span class='text-danger'><b>"+x.name+" &nbsp; <img src='img/"+x.status+".png'></b></span><hr>Mobile :&nbsp; "+disp_mobile+" &nbsp; <img src='img/"+x.mobile_status+".png'><br>Email : &nbsp; "+disp_email+"<br>Member : &nbsp; "+x.bc_name+"<br>Practicing Since : &nbsp; "+x.e_year+"<br>From : &nbsp;"+x.district_name+",&nbsp;"+x.state_name+"  </div></div>";
					
					$("#appbody").append(data);	
					
				}
			}
			else{
				$("#appbody").append("<div class='text-danger text-center'> No Information Available </div>");
			}
			},
			complete:function()
			{
				$("#loader").hide();
				
			}
		});
	}
});

$("#apparea").on("click","#search_advocate_pa", function(){

	$.ajax({
		type:'POST',
		url:base_url+'task=state_listdd',
		beforeSend:function(){
			$("#loader").show();
		},
		success:function(data)
		{
			$("#apphead").html("<img src='img/back.png' align='left' height='25px' id='search_back'> <div style='float:right'> <img src='img/search.png' height='25px' > Search by Practice Area </div>");
			$("#appbody").html("<select name='state' class='form-control mb-2' id='search_state_list'></select><select name='district' class='form-control mb-2' id='search_district_list'></select><input type='text' id='search_advocate_name_pa' class='form-control' placeholder='e.g Bail, Partition Suit' > <button class='btn btn-danger btn-block mt-2' id='search_advocate_btn_pa' >Search by Practice Area </button>");
				$("#search_state_list").append(data);
				
		},
		complete:function()
		{
			$("#loader").hide();
		}
	});
});



$("#apparea").on("click","#search_advocate_btn_pa", function(){
	
	var state = $("#search_state_list").val();
	var district = $("#search_district_list").val();
	var pa = $("#search_advocate_name_pa").val();
	if(pa.length <4 || district =='' )
	{
		alert("Check all Field ")
	}
	else{
		$.ajax({
			type:'POST',
			url:base_url+'task=search_advocate_pa',
			data:{'state_code':state,'district_code':district,'pa':pa},
			beforeSend:function(){
				$("#loader").show();
			},
			success:function(data)
			{
				obj = JSON.parse(data);
				// console.log(obj);
				if(obj.count>0)
			{
				$("#appbody").html("<div class='bg-gray text-danger'>" + obj.count +" Advocate Available </div>");	
				if (obj.count<20)
					{
						var ct=obj.count;
					}
				else{
						ct=20;
					}
				for(var i=0;  i<ct; i++)
				{
					var x = obj['data'][i];
					var disp_mobile = get_disp_contact(x.mobile, x.mobile_visibility, 'mobile');
					var disp_email  = get_disp_contact(x.email, x.email_visibility, 'email');
					var data = " <div class='card mt-1'><div class='card-body'> <span class='text-danger'><b>"+x.name+" &nbsp; <img src='img/"+x.status+".png'></b></span><hr>Mobile :&nbsp; "+disp_mobile+" &nbsp; <img src='img/"+x.mobile_status+".png'><br>Email : &nbsp; "+disp_email+"<br>Member : &nbsp; "+x.bc_name+"<br>Practicing Since : &nbsp; "+x.e_year+"<br>From : &nbsp;"+x.district_name+",&nbsp;"+x.state_name+"  </div></div>";
					
					$("#appbody").append(data);	
					
				}
			}
			else{
				$("#appbody").append("<div class='text-danger text-center'> No Information Available </div>");
			}
			},
			complete:function()
			{
				$("#loader").hide();
			}
		});
	}
});


$("#apparea").on("click","#search_advocate_eno", function(){

	$.ajax({
		type:'POST',
		url:base_url+'task=state_listdd',
		beforeSend:function(){
			$("#loader").show();
		},
		success:function(data)
		{
			$("#apphead").html("<img src='img/back.png' align='left' height='25px' id='search_back'> <div style='float:right'> <img src='img/search.png' height='25px' > Search by Enrollment No </div>");
			$("#appbody").html("<select name='state' class='form-control mb-2' id='search_state_list'></select><input type='text' id='search_advocate_name_eno' class='form-control' placeholder='Enrollment No' > <button class='btn btn-danger btn-block mt-2' id='search_advocate_btn_eno' > Search by Enrollment No </button>");
				$("#search_state_list").append(data);
				
		},
		complete:function()
		{
			$("#loader").hide();
		}
	});
});

$("#apparea").on("click","#search_advocate_btn_eno", function(){
	
	var state = $("#search_state_list").val();
	//var district = $("#search_district_list").val();
	var eno = $("#search_advocate_name_eno").val();
	if(eno.length <1 )
	{
		alert("Check Enrollment Number")
	}
	else{
		$.ajax({
			type:'POST',
			url:base_url+'task=search_advocate_eno',
			data:{'state_code':state,'e_no':eno},
			beforeSend:function(){
				$("#loader").show();
			},
			success:function(data)
			{
				obj = JSON.parse(data);
				// console.log(obj);
				if(obj.count>0)
			{
				$("#appbody").html("<div class='bg-gray text-danger'>" + obj.count +" Advocate Available </div>");	
				if (obj.count<20)
					{
						var ct=obj.count;
					}
				else{
						ct=20;
					}
				for(var i=0;  i<ct; i++)
				{
					var x = obj['data'][i];
					var disp_mobile = get_disp_contact(x.mobile, x.mobile_visibility, 'mobile');
					var disp_email  = get_disp_contact(x.email, x.email_visibility, 'email');
					var data = " <div class='card mt-1'><div class='card-body'> <span class='text-danger'><b>"+x.name+" &nbsp; <img src='img/"+x.status+".png'></b></span><hr>Mobile :&nbsp; "+disp_mobile+" &nbsp; <img src='img/"+x.mobile_status+".png'><br>Email : &nbsp; "+disp_email+"<br>Member : &nbsp; "+x.bc_name+"<br>Practicing Since : &nbsp; "+x.e_year+"<br>From : &nbsp;"+x.district_name+",&nbsp;"+x.state_name+"  </div></div>";
					
					$("#appbody").append(data);	
					
				}
			}
			else{
				$("#appbody").append("<div class='text-danger text-center'> No Information Available </div>");
			}
			},
			complete:function()
			{
				$("#loader").hide();
			}
		});
	}
});

$("#apparea").on("click","#search_advocate_mobile", function(){

	$.ajax({
		type:'POST',
		url:base_url+'task=state_listdd',
		beforeSend:function(){
			$("#loader").show();
		},
		success:function(data)
		{
			$("#apphead").html("<img src='img/back.png' align='left' height='25px' id='search_back'> <div style='float:right'> <img src='img/search.png' height='25px' > Search by Mobile </div>");
			$("#appbody").html("<input type='tel' id='search_advocate_name_mobile' class='form-control' placeholder='Mobile' > <button class='btn btn-danger btn-block mt-2' id='search_advocate_btn_mobile' > Search by Mobile </button>");
				//$("#search_state_list").append(data);
				
		},
		complete:function()
		{
			$("#loader").hide();
		}
	});
});

$("#apparea").on("click","#search_advocate_btn_mobile", function(){
	
	//var state = $("#search_state_list").val();
	//var district = $("#search_district_list").val();
	var mobile = $("#search_advocate_name_mobile").val();
	if(mobile.length <10 )
	{
		alert("Invalid Mobile Number")
	}
	else{
		$.ajax({
			type:'POST',
			url:base_url+'task=search_advocate_mobile',
			data:{'mobile':mobile},
			beforeSend:function(){
				$("#loader").show();
			},
			success:function(data)
			{
				obj = JSON.parse(data);
				// console.log(obj);
				if(obj.count>0)
			{
				$("#appbody").html("<div class='bg-gray text-danger'>" + obj.count +" Advocate Available </div>");	
				if (obj.count<20)
					{
						var ct=obj.count;
					}
				else{
						ct=20;
					}
				for(var i=0;  i<ct; i++)
				{
					var x = obj['data'][i];
					var disp_mobile = get_disp_contact(x.mobile, x.mobile_visibility, 'mobile');
					var disp_email  = get_disp_contact(x.email, x.email_visibility, 'email');
					var data = " <div class='card mt-1'><div class='card-body'> <span class='text-danger'><b>"+x.name+" &nbsp; <img src='img/"+x.status+".png'></b></span><hr>Mobile :&nbsp; "+disp_mobile+" &nbsp; <img src='img/"+x.mobile_status+".png'><br>Email : &nbsp; "+disp_email+"<br>Member : &nbsp; "+x.bc_name+"<br>Practicing Since : &nbsp; "+x.e_year+"<br>From : &nbsp;"+x.district_name+",&nbsp;"+x.state_name+"  </div></div>";
					
					$("#appbody").append(data);	
					
				}
			}
			else{
				$("#appbody").append("<div class='text-danger text-center'> No Information Available </div>");
			}
			},
			complete:function()
			{
				$("#loader").hide();
			}
		});
	}
});
//============ PINCODE ============== //
$("#apparea").on("click","#pincode_list", function(){

	$.ajax({
		type:'POST',
		url:base_url+'task=search_pincode_code',
		beforeSend:function(){
			$("#loader").show();
		},
		success:function(data)
		{
			$("#apphead").html("<img src='img/back.png' align='left' height='25px' onclick='location.reload()'> <div style='float:right'> <img src='img/pincode.png' height='25px' > Search Pincode </div>");
			$("#appbody").html("<input type='tel' id='search_pincode_code' maxlength='6' class='form-control' placeholder='Pincode' > <button class='btn btn-danger btn-block mt-2' id='search_pincode_code_result' > Search Post Office</button>");
				
		},
		complete:function()
		{
			$("#loader").hide();
		}
	});
});

$("#apparea").on("click","#search_pincode_code_result", function(){
	var pincode = $("#search_pincode_code").val().trim();
	if(pincode.length < 6)
	{
		alert("Please enter a valid 6-digit PIN Code");
	}
	else{
		$.ajax({
			type: 'GET',
			url: olaw_api_url + '?api_key=' + encodeURIComponent(olaw_api_key) + '&action=pincode&pincode=' + encodeURIComponent(pincode),
			beforeSend: function(){
				$("#loader").show();
			},
			success: function(res)
			{
				var obj = (typeof res === 'object') ? res : JSON.parse(res);
				if(obj.status === 'success' && obj.data && obj.data.length > 0)
				{
					var list = Array.isArray(obj.data) ? obj.data : [obj.data];
					$("#appbody").html("<div class='bg-gray text-danger mb-2'><b>" + list.length + " Post Offices / Localities in PIN: " + pincode + "</b></div>");
					for(var i = 0; i < list.length; i++)
					{
						var x = list[i];
						var data = "<div class='card mt-1'><div class='card-body'>" +
							"<span class='text-danger'><b>" + (x.locality_name || x.office_name || 'Locality') + "</b></span><hr>" +
							"<b>PIN Code:</b> " + (x.pincode || pincode) + "<br>" +
							"<b>Post Office:</b> " + (x.office_name || '—') + "<br>" +
							"<b>District:</b> " + (x.district_name || '—') + "<br>" +
							"<b>State/UT:</b> " + (x.state_name || '—') +
							"</div></div>";
						$("#appbody").append(data);
					}
				}
				else{
					$("#appbody").html("<div class='text-danger text-center p-3'>No locations found for PIN Code: " + pincode + "</div>");
				}
			},
			error: function(){
				$("#appbody").html("<div class='text-danger text-center p-3'>Unable to connect to PIN Code API. Please try again.</div>");
			},
			complete: function(){
				$("#loader").hide();
			}
		});
	}
});

//============ HSN SAC ============== //
$("#apparea").on("click","#hsnsac_list", function(){

	$.ajax({
		type:'POST',
		url:base_url+'task=search_hsnsac',
		beforeSend:function(){
			$("#loader").show();
		},
		success:function(data)
		{
			$("#apphead").html("<img src='img/back.png' align='left' height='25px' onclick='location.reload()'> <div style='float:right'> <img src='img/monitor.png' height='25px' > Search HSN and SAC </div>");
			$("#appbody").html("<center> <i>Harmonized System of Nomenclature (HSN) and Services Accounting Code (SAC) used in GST </i><br><b>Search by Code </b> </center><input type='tel' id='search_hsnsac' maxlength='8' class='form-control' placeholder='Code' > <button class='btn btn-danger btn-block mt-2' id='search_hsnsac_result' > HSN/SAC by Code</button><center> <b>Search by Name </b> </center><input type='text' id='search_hsnsac_by_name' maxlength='20' class='form-control' placeholder='Name' > <button class='btn btn-danger btn-block mt-2' id='search_hsnsac_result_by_name' > HSN/SAC by Name</button>");
		},
		complete:function()
		{
			$("#loader").hide();
		}
	});
});

$("#apparea").on("click","#search_hsnsac_result", function(){
	var hsnsaccode = $("#search_hsnsac").val().trim();
	if(hsnsaccode.length < 1)
	{
		alert("Please enter an HSN / SAC Code");
	}
	else{
		$("#loader").show();
		// Try olaw API first
		$.ajax({
			type: 'GET',
			url: olaw_api_url + '?api_key=' + encodeURIComponent(olaw_api_key) + '&action=hsn-sac&q=' + encodeURIComponent(hsnsaccode),
			success: function(res)
			{
				var obj = (typeof res === 'object') ? res : null;
				try { if (!obj) obj = JSON.parse(res); } catch(e){}
				if(obj && obj.status === 'success' && obj.data)
				{
					$("#loader").hide();
					var list = Array.isArray(obj.data) ? obj.data : [obj.data];
					$("#appbody").html("<div class='bg-gray text-danger mb-2'><b>" + list.length + " HSN / SAC Records Found</b></div>");
					for(var i = 0; i < list.length; i++)
					{
						var x = list[i];
						var data = "<div class='card mt-1'><div class='card-body'>" +
							"<span class='text-danger'><b>" + (x.code || hsnsaccode) + "</b></span><hr>" +
							"<b>Description:</b> " + (x.description || x.title || '—') + "<br>" +
							"<b>Type:</b> <b>" + (x.type || 'Goods / Services') + "</b>" +
							"</div></div>";
						$("#appbody").append(data);
					}
				}
				else{
					fallbackHsnSearch(hsnsaccode, 'code');
				}
			},
			error: function(){
				fallbackHsnSearch(hsnsaccode, 'code');
			}
		});
	}
});

$("#apparea").on("click","#search_hsnsac_result_by_name", function(){
	var hsnsaccode = $("#search_hsnsac_by_name").val().trim();
	if(hsnsaccode.length < 1)
	{
		alert("Please enter an HSN / SAC keyword");
	}
	else{
		$("#loader").show();
		$.ajax({
			type: 'GET',
			url: olaw_api_url + '?api_key=' + encodeURIComponent(olaw_api_key) + '&action=hsn-sac&q=' + encodeURIComponent(hsnsaccode),
			success: function(res)
			{
				var obj = (typeof res === 'object') ? res : null;
				try { if (!obj) obj = JSON.parse(res); } catch(e){}
				if(obj && obj.status === 'success' && obj.data)
				{
					$("#loader").hide();
					var list = Array.isArray(obj.data) ? obj.data : [obj.data];
					$("#appbody").html("<div class='bg-gray text-danger mb-2'><b>" + list.length + " HSN / SAC Records Found</b></div>");
					for(var i = 0; i < list.length; i++)
					{
						var x = list[i];
						var data = "<div class='card mt-1'><div class='card-body'>" +
							"<span class='text-danger'><b>" + (x.code || '—') + "</b></span><hr>" +
							"<b>Description:</b> " + (x.description || x.title || '—') + "<br>" +
							"<b>Type:</b> <b>" + (x.type || 'Goods / Services') + "</b>" +
							"</div></div>";
						$("#appbody").append(data);
					}
				}
				else{
					fallbackHsnSearch(hsnsaccode, 'name');
				}
			},
			error: function(){
				fallbackHsnSearch(hsnsaccode, 'name');
			}
		});
	}
});

function fallbackHsnSearch(query, searchType) {
	var task = (searchType === 'name') ? 'search_hsnsac_by_name' : 'search_hsnsac';
	$.ajax({
		type: 'POST',
		url: base_url + 'task=' + task,
		data: {'hsnsaccode': query},
		success: function(data)
		{
			try {
				var obj = JSON.parse(data);
				if(obj.count > 0)
				{
					$("#appbody").html("<div class='bg-gray text-danger mb-2'><b>" + obj.count + " HSN / SAC Available</b></div>");
					var ct = (obj.count < 30) ? obj.count : 30;
					for(var i = 0; i < ct; i++)
					{
						var x = obj['data'][i];
						var card = "<div class='card mt-1'><div class='card-body'>" +
							"<span class='text-danger'><b>" + (x.code || '—') + "</b></span><hr>" +
							"<b>Description:</b> " + (x.description || '—') + "<br>" +
							"<b>Type:</b> <b>" + (x.type || '—') + "</b>" +
							"</div></div>";
						$("#appbody").append(card);
					}
				}
				else{
					$("#appbody").html("<div class='text-danger text-center p-3'>No HSN / SAC records found for: " + query + "</div>");
				}
			} catch(e) {
				$("#appbody").html("<div class='text-danger text-center p-3'>No HSN / SAC records found.</div>");
			}
		},
		error: function(){
			$("#appbody").html("<div class='text-danger text-center p-3'>Unable to complete HSN search. Please try again.</div>");
		},
		complete: function(){
			$("#loader").hide();
		}
	});
}

// ==== IFSC ==== //
$("#apparea").on("click","#ifsc", function(){

	$.ajax({
		type:'POST',
		url:base_url+'task=ifsc',
		beforeSend:function(){
			$("#loader").show();
		},
		success:function(data)
		{
			$("#apphead").html("<img src='img/back.png' align='left' height='25px' onclick='location.reload()'> <div style='float:right'> <img src='img/ifsc.png' height='25px' > Search IFSC </div>");
			$("#appbody").html("<center><b> IFSC by Code </b></center><input type='text' id='ifsc_text' maxlength='11' class='form-control' placeholder='11 Digit IFSC' > <button class='btn btn-danger btn-block mt-2' id='ifsc_text_result' > IFSC by Code</button><center> <b>Search by Branch </b> </center><input type='text' id='ifsc_branch' maxlength='20' class='form-control' placeholder='Name' > <button class='btn btn-danger btn-block mt-2' id='ifsc_branch_result' > IFSC by Name</button> Indian Financial System Code (IFSC) is 11 digit code issued by RBI for all Banks");
		},
		complete:function()
		{
			$("#loader").hide();
		}
	});
});

$("#apparea").on("click","#ifsc_text_result", function(){
	var ifsc = $("#ifsc_text").val().trim().toUpperCase();
	if(ifsc.length < 11)
	{
		alert("Please enter a valid 11-digit IFSC code");
	}
	else{
		$.ajax({
			type: 'GET',
			url: olaw_api_url + '?api_key=' + encodeURIComponent(olaw_api_key) + '&action=bank-ifsc&ifsc=' + encodeURIComponent(ifsc),
			beforeSend: function(){
				$("#loader").show();
			},
			success: function(res)
			{
				var obj = (typeof res === 'object') ? res : JSON.parse(res);
				if(obj.status === 'success' && obj.data)
				{
					var x = obj.data;
					$("#appbody").html("<div class='bg-gray text-danger mb-2'><b>Verified RBI Bank Record</b></div>");
					var data = "<div class='card mt-1'><div class='card-body'>" +
						"<span class='text-danger'><b>" + (x.ifsc || ifsc) + "</b></span> &nbsp; <small class='badge badge-success'>ACTIVE</small><hr>" +
						"<b>Bank Name:</b> " + (x.bank || '—') + "<br>" +
						"<b>Branch Name:</b> " + (x.branch || '—') + "<br>" +
						"<b>MICR Code:</b> " + (x.micr || '—') + "<br>" +
						"<b>Address:</b> " + (x.address || '—') + "<br>" +
						"<b>City:</b> " + (x.city1 || x.city2 || '—') + "<br>" +
						"<b>State/UT:</b> " + (x.state || '—') + "<br>" +
						(x.phone ? ("<b>Phone:</b> " + (x.stdcode ? x.stdcode + '-' : '') + x.phone + "<br>") : "") +
						"</div></div>";
					$("#appbody").append(data);
				}
				else{
					$("#appbody").html("<div class='text-danger text-center p-3'>No IFSC details found for: " + ifsc + "</div>");
				}
			},
			error: function(){
				$("#appbody").html("<div class='text-danger text-center p-3'>Unable to connect to IFSC API. Please try again.</div>");
			},
			complete: function(){
				$("#loader").hide();
			}
		});
	}
});

$("#apparea").on("click","#ifsc_branch_result", function(){
	
	var ifsc = $("#ifsc_branch").val();
	if(ifsc.length <1 )
	{
		alert("Invalid Branch")
	}
	else{
		$.ajax({
			type:'POST',
			url:base_url+'task=search_ifsc_by_branch',
			data:{'ifsc':ifsc},
			beforeSend:function(){
				$("#loader").show();
			},
			success:function(data)
			{
				obj = JSON.parse(data);
				//console.log(obj);
				if(obj.count>0)
			{
				
				$("#appbody").html("<div class='bg-gray text-danger'>" + obj.count +" Branch Available </div>");	
				if (obj.count<50)
					{
						var ct=obj.count;
					}
				else{
						ct=50;
					}
				for(var i=0;  i<ct; i++)
				{
					var x = obj['data'][i];
					var data = " <div class='card mt-1'><div class='card-body'> <span class='text-danger'><b>"+x.ifsc+"&nbsp; </b></span><hr> <b>Bank Name:</b> "+x.bank+" <br> <b>Branch Name:</b> "+x.branch+" <br> <b>Address:</b> "+x.address+" <br> <b>City:</b> "+x.city1+", "+x.city2+" <br> <b>State/UT:</b> "+x.state+" <br> <b>Phone:</b> "+x.stdcode+""+x.phone+" <br> <b>Last Update:</b> "+x.updated_at+" <br> </div>";
					
					$("#appbody").append(data);	
					
				}
			}
			else{
				$("#appbody").append("<div class='text-danger text-center'> No Banch Available </div>");
			}
			},
			complete:function()
			{
				$("#loader").hide();
			}
		});
	}
});


// ==== End ==== //

$("#apparea").on("change","#search_state_list", function(){
	
	var state_code = $(this).val();
	// console.log(state_code);
	$.ajax({
		type:'POST',
		url:base_url+'task=district_listdd',
		data:{'state_code':state_code},
		beforeSend:function(){
			$("#loader").show();
		},
		success:function(data)
		{
			// console.log(data);
			$("#search_district_list").html("");
			$("#search_district_list").append(data);
			//$("#search_state_list").css('display','none');
				
		},
		
		complete:function()
		{
			$("#loader").hide();
		}
	});
});



$("#acts_list").on("click", function(){

	$.ajax({
		type:'POST',
		url:base_url+'task=acts_info',
		beforeSend:function(){
			$("#loader").show();
		},
		success:function(data)
		{
			$("#apphead").html("<img src='img/back.png' align='left' height='25px' onclick='location.reload()'> <div style='float:right'> <img src='img/cdownload.png' height='25px' style='filter: brightness(0.5) saturate(100%);'> Acts and Rules </div>");
			$("#appbody").html('');
			obj =JSON.parse(data);
			// console.log(obj);
			for(var i=0; i< obj.count; i++)
			{
				var list = "<div data-toggle='collapse' class='btn btn-block bg-secondary text-light my-1' data-target='#acts_list"+i+"' > "+obj.data[i].name.slice(0,20)+"...<img src='img/plus.png' height='20px' class='float-right'></div>"+"<div id='acts_list"+i+"' class='collapse in p-2'><b>"+ obj.data[i].name+"</b> &nbsp; "+ obj.data[i].year +" <br> Download in PDF <a href='"+ obj.data[i].english+"'> English</a> and <a href='"+ obj.data[i].hindi+"'> Hindi </a>"+ ""+ "<br> More details visit <a href='"+obj.data[i].official+"'> Official Website </a></div>"; 
				$("#appbody").append(list);
			}
		},
		complete:function()
		{
			$("#loader").hide();
		}
	});
});


$("#notice_list").on("click", function(){

	$.ajax({
		type:'POST',
		url:base_url+'task=notice_info',
		beforeSend:function(){
			$("#loader").show();
		},
		success:function(data)
		{
			$("#apphead").html("<img src='img/back.png' align='left' height='25px' onclick='location.reload()'> <div style='float:right'> <img src='img/new.png' height='25px' style='filter: brightness(0.5) saturate(100%);'> New Notifications </div>");
			$("#appbody").html('');
			obj =JSON.parse(data);
			// console.log(obj);
			for(var i=0; i< obj.count; i++)
			{
				var list = "<div data-toggle='collapse' class='btn btn-block bg-secondary text-light my-1' data-target='#notice"+i+"' > "+obj.data[i].name.slice(0,20)+"...<img src='img/plus.png' height='20px' class='float-right'></div>"+"<div id='notice"+i+"' class='collapse in p-2'><b>"+ obj.data[i].name+"</b> &nbsp; "+ obj.data[i].details +" <br><a href='"+ obj.data[i].url_1+"'>Official Link</a><br><a href='"+ obj.data[i].url_2+"'>More Details</a>"+ ""+ "<br>"+obj.data[i].last_date+"</div>"; 
				$("#appbody").append(list);
			}
		},
		complete:function()
		{
			$("#loader").hide();
		}
	});
});

function uplaodok(evt)
{
	$("#display").html("Please Wait..");
	var files = evt.target.files;
	var file = files[0];
	
	let formData = new FormData();
	formData.append("uploadimg", file); 
	$.ajax({
	url: base_url+'&task=upload',
	type: "POST",
	data:  formData,
	contentType: false,
	cache: false,
	processData:false,
	beforeSend: function()
		  {
		  	$("#loader").show();
		  },
	success: function(data){
		// console.log(data);
		var obj = JSON.parse(data);
		$("#targetimg").val(obj.id);
		$("#display").html("<img src='"+file_url+obj.id +"' width='200px' class='img-thumbnail' align='center'>");
		alert(obj.orig_name + obj.error ,obj.status);
		//$("#insert_btn").attr("disabled", false);
	},
	complete: function(){
			$('#loader').hide(200);
	}
	});
	
}
/*--------NOTICE PAGE  --------*/

$("#apparea").on('click',"#closemodel", function(){
	
	$("#myModal").hide();
});


function populate(frm, data) {   
    $.each(data, function(key, value) {  
        var ctrl = $('[name='+key+']', frm);  
        switch(ctrl.prop("type")) { 
            case "radio": case "checkbox":   
                ctrl.each(function() {
                    if($(this).attr('value') == value) $(this).attr("checked",value);
                });   
                break;  
            default:
                ctrl.val(value); 
        }  
    });  
}

/*============================*/

function loadstate(state_code =null)
{
	$.ajax({
		type:'POST',
		url:base_url+'task=state_listdd',
		data:{'state_code':state_code},
		beforeSend:function(){
			$("#loader").show();
		},
		success:function(data)
		{
			// console.log(data);
			$("#state_code").append(data);
				
		},
		complete:function()
		{
			$("#loader").hide();
		}
	});
}

function loadbc(bc_id =null)
{
	$.ajax({
		type:'POST',
		url:base_url+'task=bc_listdd',
		data:{'bc_id':bc_id},
		beforeSend:function(){
			$("#loader").show();
		},
		success:function(data)
		{
			// console.log(data);
			$("#bc_id").append(data);
				
		},
		complete:function()
		{
			$("#loader").hide();
		}
	});
}


function loaddist(state_code, district_code =null)	
{
	$.ajax({
		type:'POST',
		url:base_url+'task=district_listdd',
		data:{'state_code':state_code,'district_code':district_code},
		beforeSend:function(){
			$("#loader").show();
		},
		success:function(data)
		{
			 //console.log(data);
			$("#district_code").empty();
			$("#district_code").append(data);
				
		},
		complete:function()
		{
			$("#loader").hide();
		}
	});
}

function loaduniv(state_code, univ_code =null)	
{
	$.ajax({
		type:'POST',
		url:base_url+'task=univ_listdd',
		data:{'state_code':state_code,'univ_code':univ_code},
		beforeSend:function(){
			$("#loader").show();
		},
		success:function(data)
		{
			 //console.log(data);
			$("#univ_code").empty();
			$("#univ_code").append(data);
				
		},
		complete:function()
		{
			$("#loader").hide();
		}
	});
}

function loadcollege(univ_code, college_code =null)	
{
	$.ajax({
		type:'POST',
		url:base_url+'task=college_listdd',
		data:{'univ_code':univ_code,'college_code':college_code},
		beforeSend:function(){
			$("#loader").show();
		},
		success:function(data)
		{
			 //console.log(data);
			$("#college_code").empty();
			$("#college_code").append(data);
				
		},
		complete:function()
		{
			$("#loader").hide();
		}
	});
}


function getuser()
	{		
		$.ajax({
			type :'post',
			url: base_url+'task=get_user',
			data: {'id':myadv_id,'user_type':myadv_user_type},
			beforeSend: function(){
				$("#loader").show();
			},
			success: function(res){
				myobj =JSON.parse(res);
				populate("#update_frm", myobj.data);	
				$("#user_type").val(myadv_user_type);
				loadstate(myobj.data.state_code);
				loaddist(myobj.data.state_code, myobj.data.district_code);
			},
			complete: function()
			{
				$("#loader").hide();
			}
		});	
	}		
function getadvocate()
	{
		$.ajax({
			type :'post',
			url: base_url+'task=get_user',
			data: {'id':myadv_id,'user_type':myadv_user_type},
			beforeSend: function(){
				$("#loader").show();
			},
			success: function(res){
				
				myobj =JSON.parse(res);
				var practice_area = myobj.data.practice_area.split(",");
				//console.log(practice_area);
				practice_area.forEach( function(pa){
					var x = "<div class='custom-control custom-checkbox'> <input type='checkbox' class='custom-control-input' id='"+pa+"' name='example1'> <label class='custom-control-label' for='"+pa+"'>"+pa+"</label> </div>";
					$("#parea").append(x);
				});
				populate("#update_frm", myobj.data);	
				$("#user_type").val(myadv_user_type);
				loaduniv(myobj.data.state_code,myobj.data.univ_code);
				loadbc(myobj.data.bc_id);
				loadstate(myobj.data.state_code);
				loaddist(myobj.data.state_code, myobj.data.district_code);
				loadcollege(myobj.data.univ_code, myobj.data.college_code);
			},
			complete: function()
			{
				$("#loader").hide();
			}
		});	
	}		

$("#apparea").on("click", "#update_user_p", function(){
		
		var update_frm = "<div id='cross'>X</div><form id='update_frm' action='update_user'><input type='hidden' class='form-control mb-2' id='user_type' name='user_type' ><input type='hidden' class='form-control mb-2' placeholder='id' name='id'><select name='gender' id='gender' class='form-control mb-2'><option value='F'>Female</option><option value='M'>Male</option></select><select name='education' id='education' class='form-control mb-2'><option value=''>Select Education</option><option value='1'>Illiterate</option><option value='2'>Literate </option><option value='10'>10th</option><option value='12'>12th</option> <option value='15'>Graduate</option><option value='17'>Post Graduate</option><option value='18'>Doctorate</option><option value='19'> Professional</option></select><select name='state_code' onChange='loaddist(this.value)' class='form-control mb-2' id='state_code'></select><select name='district_code' class='form-control mb-2' id='district_code'></select><input type='text' name='address' id='address' placeholder='Address' class='form-control mb-2'><input type='tel' name='pincode' id='pincode' placeholder='Pin Code' class='form-control mb-2'><input type='email' name='email' id='email' placeholder='Email' class='form-control mb-2'><input type='tel' name='whatsapp' id='whatsapp' maxlength='10' placeholder='Whatsapp No' class='form-control mb-2'><label>Date of Birth</label> <input type='date' name='dob' id='dob' placeholder='Date of Birth' class='form-control mb-2'> </form><button class='btn btn-danger btn-block mt-2' id='update_btn' > Update Personal Details </button>";
		
		$("#updatemodal").show();
		$("#updatemodal .modal-body").html(update_frm);
		getuser();
		
});

$("#apparea").on("click", "#update_user_a_status", function(){
		
		var update_frm = "<div id='cross'>X</div><form id='update_frm' action='update_user'><input type='hidden' class='form-control mb-2' id='user_type' name='user_type' ><input type='hidden' class='form-control mb-2' placeholder='id' name='id'><input type='text' name='type' id='type' placeholder='Advocate Status' class='form-control mb-2' readonly> <p> MyAdv India has two types for profile: <br><b> ACTIVE </b> Lifetime free profile available to search.<br><b> VERIFIED </b> Profile photo and about section extra with ACTIVE (other people can see photo and about).  <br> <p> If your profile is <b> PENDING </b> it means your profile is not complete / not approved by MyAdv India Team. Complete profile and upload ID Proof.</p>";
		
		$("#updatemodal").show();
		$("#updatemodal .modal-body").html(update_frm);
		getadvocate();
		
});



$("#apparea").on("click", "#new_payment1", function(){
		
		var update_frm = "<div id='cross'>X</div><form id='update_frm' action='update_user'><input type='hidden' class='form-control mb-2' id='user_type' name='user_type' ><input type='hidden' class='form-control mb-2' placeholder='id' name='id'><input type='text' name='type' id='type' placeholder='Advocate Status' class='form-control mb-2' readonly> <p> MyAdv India has two types for profile: <br><b> ACTIVE </b> Lifetime free profile available to search.<br><b> VERIFIED </b> Profile photo and about section extra with ACTIVE (other people can see photo and about). <p> If you want to verify your profile <a href = 'https://rzp.io/l/myadv99'> Pay Now </a></p>";
		
		$("#updatemodal").show();
		$("#updatemodal .modal-body").html(update_frm);
		getadvocate();
		
});

$("#apparea").on("click", "#new_payment", function(){
		
		var update_frm = "<div id='cross'>X</div><form id='update_frm' action='new_payment'><input type='hidden' class='form-control mb-2' id='user_type' name='user_type' ><input type='hidden' class='form-control mb-2' id='id' name='id' ><input type='hidden' class='form-control mb-2' placeholder='mobile' name='mobile'> <input type='text' name='type' id='type' placeholder='Type' class='form-control mb-2' readonly><b>Enter INR, Transaction Type, No, Date etc. </b><select name='applied_for' id='applied_for' class='form-control mb-2'><option value='VERIFIED'>VERIFIED</option> <option value='PREMIUM'>PREMIUM</option></select> <input type='tel' name='inr' id='inr' placeholder='e.g 99.00' maxlength='4' class='form-control mb-2' required> <select name='txnType' id='txnType' class='form-control mb-2'><option value='IMPS'>IMPS</option><option value='NEFT'>NEFT</option><option value='UPI'>UPI</option> <option value='OTHER'>OTHER</option></select> <input type='tel' name='txnNo' id='txnNo' placeholder='Transaction No' class='form-control mb-2' required><input type='date' name='txnDate' id='txnDate' placeholder='Txn Date' class='form-control mb-2' required> <textarea id='payment_message' rows='3' name='payment_message' class='form-control mb-2'> </textarea><p> I have gone through <a href='https://www.myadv.in/pricing' > Profile Plans </a>  </p></form><button class='btn btn-danger btn-block mt-2' id='update_btn' > Update Payment Details </button>";
		
		$("#updatemodal").show();
		$("#updatemodal .modal-body").html(update_frm);
		getadvocate();
		
});

$("#apparea").on("click", "#update_user_a", function(){
		
		var update_frm = "<div id='cross'>X</div><form id='update_frm' action='update_user'><input type='hidden' class='form-control mb-2' id='user_type' name='user_type' ><input type='hidden' class='form-control mb-2' placeholder='id' name='id'><select name='bc_id' class='form-control mb-2' id='bc_id'></select><input type='text' name='e_no' id='e_no' placeholder='Enrollment No' class='form-control mb-2'><input type='tel' name='e_year' id='e_year' placeholder='Enrollment Year' maxlength='4' class='form-control mb-2'><label>Select University State</label><select  onChange='loaduniv(this.value)' class='form-control mb-2' id='state_code'></select><select onChange='loadcollege(this.value)' name='univ_code' class='form-control mb-2' id='univ_code'></select><select name='college_code' class='form-control mb-2' id='college_code'></select><select name='court' id='court' class='form-control mb-2'><option value=''>Select Court</option><option value='CC'>Civil Court</option><option value='HC'>High Court</option><option value='SC'>Supreme Court</option><option value='EC'>Executive Court</option><option value='OC'>Other Court</option></select> </form><button class='btn btn-danger btn-block mt-2' id='update_btn' > Update Professional Details </button>";
		
		$("#updatemodal").show();
		$("#updatemodal .modal-body").html(update_frm);
		getadvocate();
		
});

$("#apparea").on("click", "#update_user_a_about", function(){
		
		var update_frm = "<div id='cross'>X</div><form id='update_frm' action='update_user'><input type='hidden' class='form-control mb-2' id='user_type' name='user_type' ><input type='hidden' class='form-control mb-2' placeholder='id' name='id'><p>About Me </p><textarea id='about' rows='6' name='about' class='form-control mb-2'> </textarea> </form><button class='btn btn-danger btn-block mt-2' id='update_btn' > Update About Me </button>";
		
		$("#updatemodal").show();
		$("#updatemodal .modal-body").html(update_frm);
		getadvocate();
		
});


$("#apparea").on("click", "#update_user_photo", function(){
		
		var update_frm = "<div id='cross'>X</div><form id='update_frm' action='update_user'><input type='hidden' class='form-control mb-2' id='user_type' name='user_type' ><input type='hidden' class='form-control mb-2' placeholder='id' name='id'><input type='hidden' name='photo' id='targetimg' ></form><form id='uploadForm' enctype='multipart/form-data'><div id='display'></div><div class='form-group'> <label>Upload / Change Photograph</label> <input type='file' name='uploadimg' id='uploadimg' accept='image'> <br>(<small>Clear and colorful photograph in JPG format in 50KB only.</small>)</div></form> <button class='btn btn-danger btn-block mt-2' id='update_btn' > Update Photo</button>";
		
		$("#updatemodal").show();
		$("#updatemodal .modal-body").html(update_frm);
		getadvocate();
		
});

$("#apparea").on("click", "#update_user_id_proof", function(){
		
		var update_frm = "<div id='cross'>X</div><form id='update_frm' action='update_user'><input type='hidden' class='form-control mb-2' id='user_type' name='user_type' ><input type='hidden' class='form-control mb-2' placeholder='id' name='id'><input type='hidden' name='id_proof' id='targetimg' ></form><form id='uploadForm' enctype='multipart/form-data'><div id='display'></div><div class='form-group'> <label>Upload Identity Card</label> <input type='file' name='uploadimg' id='uploadimg' accept='image'> <br>(<small>Scan copy of Bar Association ID Card / AIBE Certificate / Bar Council Certificate or Id card in 100KB.</small>)</div></form> <button class='btn btn-danger btn-block mt-2' id='update_btn' > Update ID Proof </button>";
		
		$("#updatemodal").show();
		$("#updatemodal .modal-body").html(update_frm);
		getadvocate();
		
});


$("#apparea").on("click", "#update_visibility", function(){
		
		var update_frm = "<div id='cross'>X</div><form id='update_frm' action='update_user'><input type='hidden' class='form-control mb-2' id='user_type' name='user_type' ><input type='hidden' class='form-control mb-2' placeholder='id' name='id'><label><b>Mobile Number Visibility</b></label><select name='mobile_visibility' id='mobile_visibility' class='form-control mb-2'><option value='PRIVATE'>PRIVATE (Hidden from everyone)</option><option value='PUBLIC'>PUBLIC (Visible to all visitors)</option><option value='ADVOCATE'>ADVOCATE (Visible only to Advocates)</option><option value='REGISTERED'>REGISTERED (Visible only to logged-in users)</option></select><label><b>Email Address Visibility</b></label><select name='email_visibility' id='email_visibility' class='form-control mb-2'><option value='PRIVATE'>PRIVATE (Hidden from everyone)</option><option value='PUBLIC'>PUBLIC (Visible to all visitors)</option><option value='ADVOCATE'>ADVOCATE (Visible only to Advocates)</option><option value='REGISTERED'>REGISTERED (Visible only to logged-in users)</option></select><label><b>Address Visibility</b></label><select name='address_visibility' id='address_visibility' class='form-control mb-2'><option value='PRIVATE'>PRIVATE (Hidden from everyone)</option><option value='PUBLIC'>PUBLIC (Visible to all visitors)</option><option value='ADVOCATE'>ADVOCATE (Visible only to Advocates)</option><option value='REGISTERED'>REGISTERED (Visible only to logged-in users)</option></select></form><button class='btn btn-danger btn-block mt-2' id='update_btn' > Update Visibility Settings </button>";
		
		$("#updatemodal").show();
		$("#updatemodal .modal-body").html(update_frm);
		getuser();
		
});

$("#apparea").on("click", "#verify_mobile" , function(){
		// var mobile  = localStorage.getItem('myadv_mobile');
		// var user_type  = localStorage.getItem('myadv_user_type');
		$.ajax({
		type:'POST',
		url:base_url+'task=verify_mobile',
		data:{'mobile':myadv_mobile,'user_type':myadv_user_type},
		beforeSend:function(){
			$("#loader").show();
			//console.log(mobile +user_type);
		},
		success:function(data)
		{
			obj = JSON.parse(data);
			//console.log(data);
			if(obj.status =='success')
			{
			var update_frm = "<div id='cross'>X</div><input type='hidden' id='sotp' class='form-control mb-2'> <input type='text' id='uotp' placeholder='Enter OTP' class='form-control mb-2'><button class='btn btn-danger btn-block mt-2' id='verify_btn' > Verify Mobile No. </button>";
			$("#updatemodal").show();
			$("#updatemodal .modal-body").html(update_frm);
			$("#sotp").val(obj.otp);
			}
			else{
				alert(obj.msg);
			}
				
		},
		complete:function()
		{
			$("#loader").hide();
		}
	});
		
		
});

$("#apparea").on('click',"#cross",function(){
	$("#updatemodal").hide();
});

$("#apparea").on('click',"#verify_btn",function(){
	var sotp  = $("#sotp").val();
	var uotp  = $("#uotp").val();
	if(sotp ==uotp)
	{
		// var id  = localStorage.getItem('myadv_id');
		// var mobile  = localStorage.getItem('myadv_mobile');
		// var user_type  = localStorage.getItem('myadv_user_type');
		
		$.ajax({
			type :'post',
			url: base_url+'task=update_user',
			data: {'id':myadv_id,'user_type':myadv_user_type,'mobile_status':'VERIFIED'},
			success: function(){
				alert("Mobile Verified");
				$("#updatemodal").hide();
			}
		});
	}
	else{
		alert("Invalid OTP");
	}
	
});


$("#apparea").on("click", "#verify_email" , function(){
		$.ajax({
		type:'POST',
		url:base_url+'task=verify_email',
		data:{'mobile':myadv_mobile,'user_type':myadv_user_type},
		beforeSend:function(){
			$("#loader").show();
			//console.log(mobile +user_type);
		},
		success:function(data)
		{
			//console.log(data);
			obj = JSON.parse(data);
			alert(obj.msg);	
		},
		complete:function()
		{
			$("#loader").hide();
		}
	});		
});

$("#apparea").on('click',"#profile",function(){
	var profile = "<div class='jumbotron'><h5>Welcome ! "+myadv_name+" </h5> Mobile:<b> "+myadv_mobile+" </b> <br> Update profile to get notification.</div><div class='row mx-1' ><div class='col qicon'> <img src='img/badge.png' id='update_user_p' data-id='1' height='40px'> <br> Personal Details</div><div class='col qicon'> <img src='img/mobile.png' id='verify_mobile' height='40px'> <br>Verify Mobile</div><div class='col qicon'> <img src='img/email.png' id='verify_email' height='40px'> <br>Verify Email</div></div> <div id='adv_display' style='display:none'> <div class='row mx-1' > <div class='col qicon' id='update_user_a_status'> <h6> Know Profile Status </h6></div></div> <div class='row mx-1' ><div class='col qicon'> <img src='img/bar.png' id='update_user_a' height='40px'> <br>Update Bar Council</div><div class='col qicon'> <img src='img/upload_docs.png' id='update_user_id_proof' height='40px'>  <br>Upload Docs</div><div class='col qicon'> <img src='img/visibility_settings.png' id='update_visibility' height='40px'>  <br>Visibility Settings</div></div><div class='row mx-1' ><div class='col qicon'> <img src='img/pay.png' id='new_payment1' height='40px'> <br>Pay Now</div><div class='col qicon'> <img src='img/upload.png' id='update_user_photo' height='40px'><br>Upload Photo</div> <div class='col qicon'> <img src='img/about_info.png' id='update_user_a_about' height='40px'>  <br>About Me</div></div></div><div class='row mx-1 mt-3'><div class='col qicon text-danger' id='delete_profile_btn' style='cursor:pointer;'><img src='img/delete_danger.png' height='40px'><br>Delete Profile</div></div>";
	
	$("#apphead").html("My Profile <div style='float:left'> </div> <img src='img/logout.png' id='logout' height='25px'>");
	$("#appbody").html(profile);
	if(myadv_user_type =='advocate')
	{
		$("#adv_display").css("display","block");
	}
	else{
		$("#adv_display").hide();
	}
	
});

$("#apparea").on("click", "#delete_profile_btn", function(){
	var confirmDelete = confirm("Warning: Deleting your profile will permanently erase your data and details from the platform. This action cannot be undone. Are you sure you want to delete your profile?");
	if(confirmDelete) {
		var confirmationText = prompt("Please type 'DELETE' to confirm profile deletion:");
		if(confirmationText === "DELETE") {
			$.ajax({
				type: 'POST',
				url: base_url + 'task=delete_user',
				data: {'id': myadv_id, 'user_type': myadv_user_type},
				beforeSend: function(){
					$("#loader").show();
				},
				success: function(res){
					var obj = JSON.parse(res);
					if(obj.status === 'success') {
						alert("Your profile has been deleted successfully.");
						localStorage.removeItem('myadv_id');
						localStorage.removeItem('myadv_mobile');
						localStorage.removeItem('myadv_details');
						localStorage.removeItem('myadv_name');
						localStorage.removeItem('myadv_user_type');
						window.location = 'index.html';
					} else {
						alert(obj.msg || "Error deleting profile. Please try again.");
					}
				},
				error: function() {
					alert("Network error. Please try again.");
				},
				complete: function(){
					$("#loader").hide();
				}
			});
		} else {
			alert("Confirmation mismatch. Profile deletion cancelled.");
		}
	}
});

$("#apparea").on('click','#wa', function(){
	var num1 =	prompt("Enter WhatsApp Number");
	if(num1.length !=10)
	{
		alert("Invalid Number Try Again");
	}
	else{
		window.location ='https://wa.me/+91'+num1;
	}
});


$("#apparea").on('click',"#search, #search2, #search_back",function(){
	var search = "<div class='jumbotron'><h5>Welcome ! "+myadv_name+" </h5><p> Search advocates with name, enrollment number, year, mobile and practice area.  </p></div><div class='row mx-1' ><div class='col qicon'> <img src='img/browser.png' id='search_advocate' data-id='1' height='40px'> <br>By Name</div><div class='col qicon'> <img src='img/badge.png' id='search_advocate_eno' data-id='1' height='40px'> <br>By Enrollment No</div><div class='col qicon'> <img src='img/mobile.png' id='search_advocate_mobile' data-id='1' height='40px'> <br>By Mobile</div></div><div class='row mx-1' ><div class='col qicon'> <img src='img/page.png' id='search_advocate_pa' data-id='1' height='40px'> <br>By Practice Area</div><div class='col qicon'> <img src='img/calendar.png' id='search_advocate_year' data-id='1' height='40px'> <br>By Practice Year</div></div> <div class='row mx-1' ><div class='col qicon'> <img src='img/book.png' class='inapp-link' data-url='https://indiankanoon.org/' height='40px'> <br>Search Law</div><div class='col qicon'> <img src='img/indiacode.png' class='inapp-link' data-url='https://indiacode.nic.in/' height='40px'> <br>India Code</div></div>";
	$("#apphead").html("<img src='img/logo.png' align='left' height='25px' id=''> <div style='float:center'> <img src='img/search.png' height='25px'> Search Advocate</div>");
	$("#appmenu").html("<img src='img/logout.png' id='logout' height='25px'>");
	$("#appbody").html(search);
	//$("#updatemodal").show();
	
});


//===========UPLOAD IMAGES ==============//
$('#apparea').on('change','#uploadimg',function (){
		$("#uploadForm").submit();
});

$("#apparea").on('submit', '#uploadForm',function(e){
	e.preventDefault();
	if($('#update_btn').length!=0)
			{
				$("#update_btn").attr("disabled", true);
			}
	$.ajax({
	url:base_url+'task=upload',
	type: "POST",
	data:  new FormData(this),
	contentType: false,
	cache: false,
	processData:false,
	success: function(data){
		console.log(data);
		//alert(data);
		var obj = JSON.parse(data);
		$("#targetimg").val(obj.id);
		$("#display").html( "<img src='"+file_url+obj.id +"' width='100px' height='100px' class='img-thumbnail'/>");
		alert(obj.msg,obj.status);
		$("#update_btn").attr("disabled", false);
	},
	error: function(){} 	        
	});
});

//===========UPLOAD ID PROOF ==============//
$('#upload_id_proof').change(function (){
		$("#id_proof").submit();
});

$("#id_proof").on('submit',(function(e){
	e.preventDefault();
	$.ajax({
	//url: "master_process?task=upload",
	url:base_url+'task=upload',
	type: "POST",
	data:  new FormData(this),
	contentType: false,
	cache: false,
	processData:false,
	success: function(data){
		var obj = JSON.parse(data);
		//alert(data);
		$("#target_id_proof").val(obj.id);
		$("#student_id_display").html("file_url"+obj.id +"' width='100px' height='100px' class='img-thumbnail'>");
		$.notify(obj.msg,obj.status);
	},
	error: function(){} 	        
	});
}));

function notice_box(){
	$.ajax({
		type:'POST',
		url:base_url+'task=notification',
		data:{'user_type':myadv_user_type,'id':myadv_id},
		beforeSend:function(){
			//$("#loader").show();
		},
		success:function(data)
		{
			//console.log(data);
			myobj =JSON.parse(data);
			if(myobj.advocate>0)
			{
				$("#advocate_count").html("<b>"+myobj.advocate +"</b> Advocate");
			}
			if(myobj.exam>0)
			{
				$("#exam_count").html("<b>"+myobj.exam +"</b> Exam Set");
			}
			if(myobj.member>0)
			{
				$("#member_count").html("<b>"+myobj.member +"</b> Member");
			}
			if(myobj.notice>0)
			{
				$("#notice_count").html("<b>"+myobj.notice +"</b> Notification");
			}
			if(myobj.question>0)
			{
				$("#question_count").html("<b>"+myobj.question +"</b> Question");
			}
			if(myobj.subject>0)
			{
				$("#subject_count").html("<b>"+myobj.subject +"</b> Subject");
			}
			if(myobj.total>0)
			{
				$("#total_count").html(myobj.total);
				$("#clear_all").css('display','block');
			}
			else{
				$("#notice_box").html("<center> No Update Found </center>");
			}
				
				
		},
		complete:function()
		{
			$("#loader").hide();
		}
	});
}
$("#apparea").on('click', "#show_notice", function(){
		$("#notice_box").slideToggle(500);
});

$("#apparea").on("click","#clear_all", function(){
	$.ajax({
		type:'POST',
		url:base_url+'task=clear_all',
		data:{'user_type':myadv_user_type,'id':myadv_id},
		beforeSend:function(){
			//$("#loader").show();
		},
		success:function(data)
		{
			//console.log(data);
		},
		complete:function()
		{
			$("#loader").hide();
			$("#notice_box").slideToggle(500);
		}
	});
});