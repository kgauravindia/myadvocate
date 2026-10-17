<?php
session_start(); 
$token = session_id();
ini_set('max_execution_time', 300);
set_time_limit(300);
date_default_timezone_set('Asia/Kolkata');
/*-------Some Basic Details (Global Variables) ---------*/
if(isset($_SESSION['user_id']))
{
	$user_id = $_SESSION['user_id'];
}

$inst_name ="My Advocate";
$inst_tag ="Accurate Advice Authentic Advocate ";
$inst_address1 ="OfferPlant Technologies Private Limited ";
$inst_address2 ="Chapra, Bihar 841301";
$inst_contact ="+91 9430 55 2117";
$inst_support ="8102930609";
$inst_email ="help@myadv.in";
$inst_logo ="/assets/images/logo_circle.png";
$white_logo ="/assets/images/logo_circle-bw.png";
$inst_url ="http://myadv.in";
$inst_domain ="myadv.in";
$inst_type ="Organization";

$inst_facebook ='http://facebook.com/myadvocateai';
$inst_twitter ='http://twitter.com/myadvocateai';
$inst_instagram ='http://instagram.com/myadvocateai';
$inst_youtube ='https://www.youtube.com/myadvocateai';
$inst_blog ='https://myadvocateai.blogspot.in';
$inst_app='https://play.google.com/store/apps/details?id=com.offerplant.myadvindia'; 
$sender_id ="EMYADV"; #EMYADV
$noreply_email ="noreply@myadv.in";
$auth_key_msg ="b0e99bea1fa7d15e27e1c5fd8e3c868"; // MSG CLUB myadvindia 12 1 23
$auth_key_sms ="180367At8cchpCRSTV59ed9c10"; // MSG 91
$base_url ='';
$docs_fee =200;
$google_map = '<iframe src="https://www.google.com/maps/embed?pb=!1m14!1m8!1m3!1d14369.20898696091!2d84.7476853!3d25.7936009!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x0%3A0xe2538a3cca0942e!2sMyAdvIndia!5e0!3m2!1sen!2sin!4v1585984990732!5m2!1sen!2sin" width="100%" height="500" frameborder="0" style="border:0;" allowfullscreen="" tabindex="0"></iframe>';
/*---------Social Link ----------*/

$facebook ='http://facebook.com/offerplant';
$twitter ='http://twitter.com/offerplant';
$linkedin ='http://linkedin.com/company/offerplant';
$youtube ='https://www.youtube.com/OfferPlantTechnologiesPrivateLimited';
$pinterest ='http://pinterest.com/offerplant';
$instagram ='http://instagram.com/offerplant';

$app_name ='AIS 1.0';
$dev_company ="OfferPlant Technologies Private Limited";
$dev_by ="OfferPlant";
$dev_url ="http://offerplant.com";
$dev_email ="ask@offerplant.com";
$dev_contact ="9431426600";
$api_key ='a9dcc62d6078b881d318aca0ead65ab5'; //MyAdvIndia@2020

$host_name ='localhost';
$db_user ='u305984835_myadvocate';
$db_password ='Adv@@2026!';
$db_name ='u305984835_myadvocate';

$gender_list =array('','MALE','FEMALE','OTHER');
$duration_list =array('',1,2,3,4,6,9,12,15,18,24);

$status_list =array('','ACTIVE','BLOCK');
$option_list =array('','A','B','C','D','E');
$unit_list =array('Days','Months','Hours','Years');
	
$month = array('April','May','June','July','August','September','October','November','December','January','February','March');
/* ---- MyAdv List ------- */ 
$blood_list =array('A+'=>'A+', 'B+'=>'B+','AB+'=>'AB+','O+'=>'O+','A-'=>'A-', 'B-'=>'B-','AB-'=>'AB-','O-'=>'O-');
$case_list =array('1'=>'civil case', '2'=>'criminal case');
$category_list =array('GEN'=>'General', 'OBC'=>'OBC','SC'=>'SC', 'ST'=>'ST');
$court_list =array(''=>'Select Court','CC'=>'Civil Court', 'HC'=>'High Court','SC'=>'Supreme Court of India', 'EC'=>'Executive Court','OC'=>'Other Court');
$gender_list =array(''=>'Select Gender','M'=>'Male', 'F'=>'Female','O'=>'Other');
$marital_list =array('U'=>'Unmarried', 'M'=>'Married','D'=>'Divorced', 'W'=>'Widow');
$edu_list =array(''=>'Select Education','1'=>'Illiterate', '2'=>'Literate','10'=>'10th','12'=>'12th', '15'=>'Graduate','17'=>'Post Graduate', '18'=>'Doctorate', '19'=> 'Professional');
$adv_type_list = array('PENDING', 'ACTIVE', 'VERIFIED','PREMIUM' ,'BLOCK','DIED');
$parea_list = array('Admirality Suit','Anticipatory Bail','Arbitration Petition','Bail Petition','Banking','Certificate Appeal','Civil Appeal','Civil Miscellaneous','Civil Reference','Civil Review','Civil Revision','Civil Writ','Commercial Appeal','Company Appeal','Company Petition','Compensation Appeal','Complaint','Consumer Case','Contempt Petition','Criminal Appeal','Criminal Case','Criminal Miscellaneous','Criminal Reference','Criminal Revision','Criminal Trail','Criminal Writ','Curative Petition','Death Reference Case','Debt Recovery','Divorce','Election Petition','Enquiry','Eviction Suit','Excise Case','Execution','Executive Appeal','Executive Case','Final Decree','First Appeal','Govt. Appeal','Guardianship','Juvenile Appeal','Juvenile Enquiry','Juvenile Trial','Land Acquisition','Land Dispute','Letters Patent Appeal','Maintenance','Matrimonial Ref','Matrimonial Suit','Miscellaneous Appeal','Miscellaneous Application','Miscellaneous Case','Money Suit','Motor Vehicle Claim','Original Suit','Other Cases','Other Civil Cases','Other Criminal Cases','Partition Suit','PASCO','Patent Case','Probate','Recovery Case','Registration','Request Case','Restitution','Review Petition','RTI','SC And ST','Second Appeal','Session Trial','Special Leave Petition','Special Reference Case','Stamp Case','Succession','Suo Moto Petition','Tax Cases','Title Appeal','Title Suit','Transfer Petition','Transferred Case','Under Trial');
$college_type_list = array('Affiliated College', 'Constituent / University College','PG Center / Off-Campus Center', 'Recognized Center');
$university_type_list = array('Central Open University', 'Central University', 'Deemed University-Government','Deemed University-Private' ,'Institute of National Importance','Institute under State Legislature Act','State Open University', 'State Private Open University', 'State Private University','State Public University');
$admin_role =array('add_center' , 'add_course' , 'add_paper' , 'add_student' , 'pay_fee');
$client_role = array('config','conn','function','client_index','add_student','txn_history');
/*-------End of Basic Details ---------*/
?>