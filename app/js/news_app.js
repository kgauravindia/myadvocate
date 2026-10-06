var file_url ='https://news.olaw.in/app/upload/';
var media_url ='https://news.olaw.in/app/media/';
var base_url ='https://news.olaw.in/app/api_v5.php?token=fc1d98b592af19df7533c3def295471a&';
var getfile =""; 
//document.addEventListener('contextmenu', event => event.preventDefault());
function req_url(task)
{
	var token = 'token=fc1d98b592af19df7533c3def295471a'; //OfferPlant
	var req_type = localStorage.getItem('user_type');
	var req_by = localStorage.getItem('user_id');
	var req_link = base_url +token + '&req_type='+req_type+'&req_by='+req_by+'&task='+task;
	return req_link;
}

function ytid(url) {
    const regExp = /^.*(youtu.be\/|v\/|u\/\w\/|embed\/|watch\?v=|&v=)([^#&?]*).*/;
    const match = url.match(regExp);
    const videoId = (match && match[2].length === 11)
      ? match[2]
      : null;

    return videoId;
}

function ytview(url) {
    const videoId = ytid(url)
    const youtube = '<iframe width="40%" height="80px" src="https://youtube.com/embed/' 
    + videoId + '" frameborder="0" class="ytview"></iframe>';
    return youtube;
}

function removeTags(str) {
      if ((str===null) || (str===''))
      return false;
      else
      str = str.toString();
      return str.replace( /(<([^>]+)>)/ig, '');
   }
   
function truncate(str, n){
  return (str.length > n) ? str.substr(0, n-1) + '&hellip;' : str;
};

$("#get_customer").on('click',function(){
	var mobile  =$("#mobile").val();
	var link = req_url('get_customer');
	if(mobile.length==10)
	{
	$.ajax({
			'type':'POST',
			'url':link,
			'data':{'mobile':mobile},
			'dataType':"json",
			beforeSend:function()
			{
				//// console.log(link);
				$("#loader").show();
			},
			success: function(data){
				$("#sotp").val(data.otp);
				localStorage.setItem('customer_id',data.id);				
				localStorage.setItem('customer_name',data.name);								
				localStorage.setItem('customer_mobile',mobile);								
				localStorage.setItem('customer_address',data.address);								
				localStorage.setItem('customer_pincode',data.pincode);								
				$("#signup").css('display','none');
				$("#otp").css('display','block');
			},
			complete:function()
			{
				
				$("#loader").hide();
			},
		});
	}
	else{
		alert("Invalid Mobile No.");
	}
});

$("#verify_otp").on('click',function(){
	var sotp  =$("#sotp").val();	
	var uotp  =$("#uotp").val();	
	
	if(sotp ==uotp)
	{
		localStorage.setItem('verification',true);
		alert("Verification Success");
		window.location.href='index.html';
	}
	else{
		$("#uotp").val('');
		alert("Invalid OTP");
	}
});


$("#apparea").on('click', '#logout', function(){
	localStorage.removeItem('customer_id');				
	localStorage.removeItem('customer_mobile');
	localStorage.removeItem('customer_name');
	localStorage.removeItem('customer_address');
	localStorage.removeItem('customer_pincode');
	localStorage.removeItem('invoice_id');
	localStorage.removeItem('verification');
	window.location='user.html';	
});

if($("#profile").length != 0) {

  $("#student_name").html(localStorage.getItem('student_name'));
  $("#student_roll").html(localStorage.getItem('student_roll'));
  $("#trade").html(localStorage.getItem('trade'));
}

$("#apparea").on("keyup","#search_text",function()
{
	var value = $(this).val().toLowerCase();
    $("#appbody .media").filter(function() {
      $(this).toggle($(this).text().toLowerCase().indexOf(value) > -1)
    });
});



function classtime(dt1){
	
	 var dt1 = new Date(dt1);
	 var dt2 = new Date();
	// var h1 = time1.slice(0,2);
	// var m1 = time1.slice(3,5);
	// var s1 = time1.slice(6,8);
		
	// var dt1 = new Date();
	// dt1.setHours(h1);
	// dt1.setMinutes(m1);
	// dt1.setSeconds(s1);
	
	// var total1 =  (dt2- dt1)/1000;
	 
  const total1 = Date.parse(new Date()) - Date.parse(dt1);
  
  const seconds1 = Math.floor( (total1/1000) % 60 );
  const minutes1 = Math.floor( (total1/1000/60) % 60 );
  const hours1 = Math.floor( (total1/(1000*60*60)) % 24 );
  const days1 = Math.floor( total1/(1000*60*60*24) );
   
  //return '<span class="badge badge-info float-right">' + hours1 +"h :" +minutes1 +"m :"+seconds1 +'s ago</span>';
  if(days1 >0) { 
  	var showd = days1 +"d ";
	} 
	else {
	var showd = '';	
	}
  return showd + hours1 +"h : " + minutes1 +'m ago';
}

/*---------------NEWS BOT API  DATA -----------------*/

function get_cat( news_url, id)
{
  var api_url = 'https://'+news_url+'/wp-json/wp/v2/categories/'+id+'?_fields=id,name,count';	
  var data = $.parseJSON($.ajax({
        url: api_url,
        dataType: "json", 
        async: false
    }).responseText); 
	return data; // .name .id . count	
}

function get_img(news_id)
{
  var api_url = base_url+'task=get_img&news_id='+news_id;
  var data = $.parseJSON($.ajax({
        url: api_url,
        dataType: "json", 
		//data:{'news_id':news_id},
        async: false
    }).responseText); 
	return data; // .staus .url .id	
}

function site_info(site_id)
{
  var api_url = base_url+'task=site_info&site_id='+site_id;
  var data = $.parseJSON($.ajax({
        url: api_url,
        dataType: "json", 
		//data:{'news_id':news_id},
        async: false
    }).responseText); 
	return data; // .staus .url .id	
}


function show_ad()
{
  var api_url = base_url+'task=show_ad';
  var res = $.parseJSON($.ajax({
        url: api_url,
        dataType: "json", 
		//data:{'news_id':news_id},
        async: false
    }).responseText); 
	//return res; // .staus .url .id	
	var single  = res.data[0];
	var ad = "<div class='adtitle'> #AD "+ single.type+" </div> <a href='"+single.link+ "' target ='_blank'><img src='" +file_url+single.photo+ "' alt='Ad here' height='60px' ></a>";
	return ad;
}


function single_news(news_url, $id)
{
	var api_url = 'https://'+news_url+'/wp-json/wp/v2/posts/'+$id;	
	var data = $.parseJSON($.ajax({
        url: api_url,
        dataType: "json", 
        async: false
    }).responseText); 
	//return data; // id, title, categories, excerpt, content, media,
	
	var news_data ={}
	
	var cat ='';
	for(var i=0; i<data.categories.length; i++)
	{
		cat  = cat + ", "+ get_cat(news_url,data.categories[i]).name;
	}
	var img =get_img(data.id);;
	var content = removeTags(data.content.rendered);
	var brief = data.excerpt.rendered;
	news_data['id'] = data.id;
	news_data['title'] = data.title.rendered;
	news_data['image'] = img.url;
	news_data['categories'] = cat;
	news_data['content'] = data.content.rendered;
	news_data['brief'] = data.excerpt.rendered;
	news_data['date'] = data.date;
	//'content':content,
	//var news_data ={'id':data.id,'title':data.title.rendered,'image':image,'categories':cat,'brief':brief,'date':data.date};
	return news_data;	
}


function get_news( news_url, limit=10)
{
  var api_url = 'https://'+news_url+'/wp-json/wp/v2/posts?per_page='+limit;	
  var data = $.parseJSON($.ajax({
        url: api_url,
        dataType: "json", 
        async: false,
		beforeSend:function(){
			$("#loader").show();
		}
    }).responseText); 
	//return data; // id, title, categories, excerpt, content, media,
	$("#appbody").html('');
	$("#loader").show();
	for (let i=0; i<data.length; i++)
	{
		y= single_news(news_url , data[i].id);
		var block ='<div class="section mt-3 mb-3">'+
            '<div class="card">'+
                '<img src="'+y.image+'" class="card-img-top" alt="image">'+
                '<div class="card-body">'+
                    '<h6 class="card-subtitle"><span class="badge badge-primary"> '+y.categories+'</span></h6>'+
                    '<h5 class="card-title">'+y.title+'</h5>'+
                    '<p class="card-text">'+
                        y.brief+
                   '</p>'+                    
                '</div>'+
            '</div>'+
        '</div>';
		
		// var block = '<div class="newsarea card">'+
			// '<h2>' 
			// '<img src="'+y.image+'" class="card-img-top" alt="image">'+
			// '<div class="card-body pt-2 text-center">'+
				// '<h4 class="mb-0">'+y.content+'</h4>'+
			// '</div>'+
		// '</div>';
					
		$("#appbody").append(block);
		
	}
	$("#loader").hide();
}


$(document).on('touchmove',".newsview",function(event){
	var nid = $(this).data('id');
	var uid = localStorage.getItem('user_id');
	event.stopPropagation();
 	var link  = base_url+'task=update_viewer';
 $.ajax({
		url: link,
		type: 'post',
		dataType: 'json',
		data: {'news_id':nid,'user_id':uid},
		beforeSend:function()
		{
			//// console.log(link +nid);
		},
		success:function(res) {
		}
	});
});

$("#content").on('click',"p",function(){
	var x = $(this);
	if(x.attr('isfullnews')=='false')
	{
	x.html(x.data('fullnews'));
	x.attr('isfullnews','true');
	}
	else{
	x.html (x.data('summary'));	
	x.attr('isfullnews','false');
	}
});


function get_cat()
{
	$.ajax({
			'type':'POST',
			'url':base_url +'task=get_category',
			dataType:'JSON',
			beforeSend:function()
			{
				$("#loader").show();
			},
			success: function(obj){
				
				
				for(var i=0; i<obj.count; i++)
				{
					var single = obj.data[i];
				// //var category = '<li><a href="#" class="cat item" data-id='+single.id+'>'+
                    // '<img src="'+file_url+single.photo+'" alt="'+ single.name+'" class="image">'+
					// '<div class="in">'+
                        // '<div>'+single.name+'</div>'+
                        // '<span class="text-muted"></span>'+
                    // '</div>'+
                // '</a></li>';
				var category =`<div class="card catagory-card"><a href=""><img src="`+file_url+single.photo+`" alt="" width ="500" height="671">
                <h6>`+single.name+`</h6></a></div>`;
				
           		$("#catarea").append(category);
				}
			},
			complete:function()
			{
				$("#pageTitle").html("News Category");
				$("#loader").hide();
			},
		});	
}

function trending(limit=5)
{
	$.ajax({
			'type':'POST',
			'url':base_url +'task=get_latest_news',
			dataType:'JSON',
			data:{'limit': limit, 'start':0},
			cache:false,
			success: function(objs){
				//// console.log(objs);
				$("#trending").html('');
				for(var i=0; i<objs.length; i++)
				{
					var single = objs[i];
					var x = classtime(single.pdate);
					var img = get_img(single.id);
				 var news =`<div class="newsview single-trending-post d-flex" data-id="`+single.id+`" data-url="`+single.news_url+`" >
					<div class="post-thumbnail"><img src="`+img.url+`"></div>
					<div class="post-content"><a class="post-title" href="single.html">`+truncate(single.title,50)+`</a>
					  <div class="post-meta d-flex align-items-center"><a href="">`+truncate(single.tags,18)+`</a><a href="#">`+x+` </a></div>
					</div>
				  </div>`;
				
           		$("#trending").append(news);
				}
			}
		});	
}

function newsbytag(tag)
{
	$.ajax({
			'type':'POST',
			'url':base_url +'task=get_news_by_tag',
			dataType:'JSON',
			data:{'param1':tag},
			cache:false,
			beforeSend:function()
			{
				$("#loader").show();
			},
			success: function(objs){
				$("#pageTitle").html(tag);
				$("#trending").html('');
				for(var i=0; i<objs.length; i++)
				{					
					var single = objs[i];
					var x = classtime(single.pdate);
					var img = get_img(single.id);
				   var news =`<div class="element-wrapper">    
					<div class="container">
					  <!-- Single Card-->
					  <div class="newsview card mb-3"><img class="card-img-top" src="`+img.url+`" data-id="`+single.id+`" data-url="`+single.news_url+`" alt="">
						<div class="card-body">
						  <h6 class="card-title">`+single.title+`</h6>
						  <p class="card-text">`+single.excerpt+`</p>
						  <p class="card-text"><small class="text-muted">`+x+`</small></p>
						</div>
					  </div>
					</div>
				  </div>`;
				//// console.log(single.tags);
           		$("#trending").append(news);
				}
			},
			 complete:function()
			 {
				 $("#pageTitle").html(tag);
				 $("#loader").hide();
			 }
		});	
}

function newsfromsamesite(url,news_id)
{
	$.ajax({
			'type':'POST',
			'url':base_url +'task=other_news_from_site',
			dataType:'JSON',
			data:{'url':url,'news_id':news_id},
			cache:false,
			beforeSend:function()
			{
				$("#newsfromsamesite").html('<center> <img src="img/loader.gif" width="100px"><br> Please Wait.... </center>');
				
			},
			success: function(objs){
				$("#newsfromsamesite").html('');
				//// console.log(objs);
				for(var i=0; i<objs.length; i++)
				{
					var single = objs[i];
					var x = classtime(single.pdate);
					var img =get_img(single.id);
				var news =`<div class="newsview single-trending-post d-flex" data-id="`+single.id+`" data-url="`+single.news_url+`">
            <div class="post-thumbnail"><img src="`+img.url+`" alt=""></div>
            <div class="post-content"><span class="post-title" >`+truncate(single.title,60)+`</span>`+
			taggen(single.tags) +
            `</div>
          </div>`;
				$("#newsfromsamesite").append(news);
				}
			},
			complete:function()
			{
				//$("#loader").hide();
			}
		});	
}

function tags(limit=10){
	//// console.log('loading Trending');
	$.ajax({
			'type':'POST',
			'url':base_url +'task=get_tags',
			dataType:'JSON',
			data:{'limit': limit},
			cache:false,
			success: function(objs){
				$("#tags").html('');
				//// console.log(objs);
				for(var i=0; i<objs.count; i++)
				{
					var single = objs.data[i];
					var colorArray = [
						'btn-primary',
						'btn-success',
						'btn-info',
						'btn-danger',
						'btn-secondary',
						'btn-dark',
						'btn-warning'
					];
				var randombutton = Math.floor(Math.random()*colorArray.length);
				 var tag ='<a class="tagview btn '+ colorArray[randombutton]+ ' btn-sm m-1" href="index.html" data-text ='+single.name+'>#'
				 +single.name+'</a>';
				
           		$("#tags").append(tag);
				}
			}
			
		});	
}

$(document).on('click','.newsview' ,function(){
	var news_id  = $(this).data('id');
	var news_url  = $(this).data('url');
	//// console.log('clicked' + news_id);
	localStorage.setItem('news_id',news_id);
	localStorage.setItem('news_url',news_url);
	window.location ='single.html';	
});


// $(document).on('click','.tagview' ,function(){
	// var tag  = $(this).data('text');
	// localStorage.setItem('task','get_news_by_tag');
	// localStorage.setItem('tag',tag);
	// window.location ='tag.html';	
// });


function taggen(str) {
  var tag1 ='';
  var res = str.split(",");
  for(let i=0; i<res.length; i++ )
  {
	let x = '<small class="tagview badge badge-success text-light" data-text="'+res[i]+'">'+res[i]+'</small> &nbsp;'; 
	
	tag1 = tag1 +x;
  }
  return tag1;
}

function imageExists(url, callback) {
  var img = new Image();
  img.src = url;
  img.onload = function() { callback(true); };
  img.onerror = function() { callback(false); };
  
}


function newslist()
{
	$.ajax({
			'type':'POST',
			'url':base_url +'task=get_sites',
			dataType:'JSON',
			data:null,
			cache:false,
			beforeSend:function()
			{
				$("#loader").show();
				$("#pageTitle").html("News Portal List");
				$("#appbody").html('<div class="element-wrapper mt-2 container" id="newslist"></div>');
				
			},
			success: function(objs){
				// console.log(objs);
				for(var i=0; i<objs.length; i++)
				{
					var single = objs[i];
					var news =`<div class="newsname single-trending-post d-flex" data-id="`+single.id+`" data-url="`+single.url+`" data-name ="`+ single.name+`">`+
            `<div class="post-thumbnail"><img src="`+file_url +single.logo+`" alt=""></div>
            <div class="post-content"><span class="post-title">`+single.name+ "<br>"+ single.description+`</span>`+
            `</div>
          </div>`;
		  
				$("#newslist").append(news);
				}
			},
			complete:function()
			{
				$("#appright").html('<a href="index.html"><i class="lni lni-chevron-left"></i></a>');
				$("#loader").hide();
			}
		});	
}

$(document).on('click','.newsname' ,function(){
	var news_url  = $(this).data('url');
	var news_name = $(this).data('name');
	$.ajax({
			'type':'POST',
			'url':base_url +'task=news_from_site',
			dataType:'JSON',
			data:{'url':news_url},
			cache:false,
			beforeSend:function()
			{
				$("#loader").show();
				
			},
			success: function(objs){
				// console.log(objs);
				$("#appbody").html('<div class="element-wrapper mt-2 container" id="newslist"></div>');
				
				for(var i=0; i<objs.length; i++)
				{
					var single = objs[i];
					var x = classtime(single.pdate);
					var img =get_img(single.id);
				var news =`<div class="newsview single-trending-post d-flex" data-id="`+single.id+`" data-url="`+single.news_url+`">
            <div class="post-thumbnail"><img src="`+img.url+`" alt=""></div>
            <div class="post-content"><span class="post-title">`+single.title+`</span>`+
			taggen(single.tags) +
            `</div>
          </div>`;
				$("#newslist").append(news);
				}
			},
			complete:function()
			{
				$("#loader").hide();
				$("#pageTitle").html(truncate(news_name,25));
				$("#btnback").html('<div class="back-button" id="newsbox"><i class="lni lni-chevron-left"></i></div>');
			}
		});	
});

$(document).on('click','#newsbox' ,function(){
 newslist();
});

function addtolocal( ls, new_item)
{
	if (localStorage.getItem(ls) === null) {
		localStorage.setItem(ls,'');
	}
	
	var list  =localStorage.getItem(ls);
	
	index = list.indexOf(new_item) // find Exist or Not
	//// console.log(index);
	if(index ==-1 && new_item !='')
	{
		var arr = list.split(",");
		arr.push(new_item);
		var arr = arr.filter(function(el) { return el; });
		var new_list = arr.toString();
		localStorage.setItem(ls,new_list);
		alert("Added Successfully to Favourite");
	}
	else{
		var x = confirm("Already added do want to remove?");
		if(x==true)
		{
			removefromlocal(ls,new_item);
		}
	}
	
}


function removefromlocal( ls, delete_item)
{
	if (localStorage.getItem(ls) === null) {
		alert("Favourite List Not Exist ");
	}
	else{
	var list  =localStorage.getItem(ls);
	var arr = list.split(",");
	
	index = arr.indexOf(delete_item) // find Exist or Not
	if (index > -1) {
		  arr.splice(index, 1);
		  var new_list = arr.toString();
		  localStorage.setItem(ls,new_list);
		  alert("Removed Successfully from Favourites");
		}
		else{
			alert("No Item Exist");
		}
		
	}
}

$(document).on('click', "#btn_search", function(){  // Create Previous Date List 
	
	$.ajax({
			'type':'POST',
			'url':base_url +'task=last_7day_news',
			dataType:'JSON',
			data:{},
			cache:false,
			beforeSend:function()
			{
				$("#loader").show();
			},
			success: function(objs){
				//// console.log(objs);
				$("#appbody").html(`<div class="element-wrapper pt-2" ><div class="container" id='news_by_date'></div>     </div>`);
				
				for(var i=0; i<objs.count; i++)
				{					
				   var single = objs.data[i];
				   var date_list =`<div class="pdate single-trending-post d-flex " data-pdate="`+single.prev_date+`">
            <div class="post-thumbnail"><img src="img/calendar.png" width='20px'></div>
            <div class="post-content">`+single.prev_date+`<span class="float-right badge badge-danger" >`+single.counter+`</span>`+
            `</div>
          </div>`;
				
           		$("#news_by_date").append(date_list);
				}
			},
			 complete:function()
			 {
				$(".pdate:nth-child(4n)").after("<div class='ad_area my-2' ></div>");
				$("#pageTitle").html('Previous News');
				$("#appright").html('');
				$("#appleft").html('<a href="index.html"><i class="lni lni-chevron-left"></i></a>');
				$("#loader").hide();
			 }
		});	
});


$(document).on('click', ".pdate", function(){  //  Single date News 
			var pdate  = $(this).attr('data-pdate');
			
	$.ajax({
			'type':'POST',
			'url':base_url +'task=single_date_news',
			dataType:'JSON',
			data:{'pdate':pdate},
			cache:false,
			beforeSend:function()
			{
			$("#loader").show();
			$("#appbody").html(`<div class="element-wrapper pt-2" ><div class="container" id='single_date_news'></div>     </div>`);
				
			},
			success: function(objs){
				
				for(var i=0; i<objs.count; i++)
				{
					var single = objs.data[i];
					//var x = classtime(single.pdate);
					//var img =get_img(single.id);
			
			var news =`<div class="newsview single-trending-post" data-id="`+single.id+`" data-url="`+single.url+`">`+"<b>"+ single.name + ": </b> " + truncate(single.title,100) +" "+ 
            //<div class="post-thumbnail"><img src="`+img.url+`" alt=""></div>
            //`<div class="post-content"><span class="post-title" >`+truncate(single.title,100)+`</span></div>`+
			taggen(single.tags) + `</div>`;
			
				$("#single_date_news").append(news);
				}
			},
			complete:function()
			{
				$(".newsview:nth-child(6n)").html("<div class='ad_area my-2' ></div>");
				$("#pageTitle").html(pdate);
				$("#appright").html('');
				$("#appleft").html('<i class="lni lni-chevron-left" id="btn_search" ></i>');
				$("#loader").hide();
			}
		});	
});


$(document).on('click', "#bookmark_list", function(){  //  Saveed News
			var news_list  = localStorage.getItem('news_list');
			
	$.ajax({
			'type':'POST',
			'url':base_url +'task=show_save_news',
			dataType:'JSON',
			data:{'news_list':news_list},
			cache:false,
			beforeSend:function()
			{
			$("#loader").show();
			$("#appbody").html(`<div class="element-wrapper pt-2" ><div class="container" id='bookmark_news'></div>     </div>`);
				
			},
			success: function(objs){
				console.log(objs);
				if(objs.count>0)
				{
				for(var i=0; i<objs.count; i++)
				{
					var single = objs.data[i];
					//var x = classtime(single.pdate);
					//var img =get_img(single.id);
			
			var news =`<div class="newsview single-trending-post" data-id="`+single.id+`" data-url="`+single.url+`">`+"<b>"+ single.name + ": </b> " + truncate(single.title,100) +" <span class='badge badge-success'>"+ single.pdate +"</span></div>";
            
				$("#bookmark_news").append(news);
				}
				}
				else{
				$("#bookmark_news").html("<center><h2 class='pt-5 text-muted'>No any favourite found, Add news for future.</h2></centre>");	
				}
			},
			complete:function()
			{
				$(".newsview:nth-child(6n)").html("<div class='ad_area my-2' ></div>");
				$("#pageTitle").html("My Favourites");
				$("#appright").html('');
				$("#appleft").html('<a href="index.html"><i class="lni lni-chevron-left" ></i></a>');
				$("#loader").hide();
			}
		});	
});
setInterval(function(){ 
 		var x =show_ad();
		//// console.log(x);
		$(".ad_area").html(x);
}, 5000);

