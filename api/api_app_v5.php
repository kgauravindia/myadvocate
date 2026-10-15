<?php
header("Access-Control-Allow-Origin: *");
require_once(__DIR__ . '/api_function_v5.php');
$token = !empty($_REQUEST['token']) ? xss_clean($_REQUEST['token']) : $api_key;

if($token == $api_key) 
{
	if(isset($_REQUEST['task']))
	{
	$task = xss_clean($_REQUEST['task']);
	switch($task)
		{
		    //==== App Info ==== //
		    case "app_version":
		        $res = array('version'=>5.0,'appname'=>'MyAdv India');
		       	echo json_encode($res);
				break;	
		    case "notification":
			    extract($_POST);
			    $result = array('advocate'=>0, 'exam'=>0, 'member'=>0, 'notice'=>0, 'question'=>0, 'subject'=>0, 'total'=>0);
			    $fromdate = date('Y-m-d H:i:s', strtotime('-30 days'));
			    if (!empty($user_type) && !empty($id) && in_array($user_type, array('advocate', 'member'))) {
			        $user_data = get_data($user_type, $id, 'updated_at');
			        if (!empty($user_data['data'])) {
			            $fromdate = $user_data['data'];
			        }
			    }
				$todate = date('Y-m-d H:i:s');
				$sql = "select * from advocate where created_at between '$fromdate' and '$todate' and status !='BLOCK'";
			    $adv_res = direct_sql($sql);
			    $result['advocate'] = !empty($adv_res['count']) ? $adv_res['count'] : 0;
			    
			    $sql2 = "select * from exam where created_at between '$fromdate' and '$todate' and status='ACTIVE'";
			    $exam_res = direct_sql($sql2);
			    $result['exam'] = !empty($exam_res['count']) ? $exam_res['count'] : 0;
                
                $sql3 = "select * from member where created_at between '$fromdate' and '$todate' and status !='BLOCK'";
			    $mem_res = direct_sql($sql3);
			    $result['member'] = !empty($mem_res['count']) ? $mem_res['count'] : 0;
			    
			    $sql4 = "select * from notice where created_at between '$fromdate' and '$todate' and status='ACTIVE'";
			    $not_res = direct_sql($sql4);
			    $result['notice'] = !empty($not_res['count']) ? $not_res['count'] : 0;
			   
			    $sql5 = "select * from question where created_at between '$fromdate' and '$todate' and status='ACTIVE'";
			    $q_res = direct_sql($sql5);
			    $result['question'] = !empty($q_res['count']) ? $q_res['count'] : 0;
			    
			    $sql6 = "select * from subject where created_at between '$fromdate' and '$todate' and status='ACTIVE'";
			    $sub_res = direct_sql($sql6);
			    $result['subject'] = !empty($sub_res['count']) ? $sub_res['count'] : 0;
			    
			    $result['total'] = $result['advocate'] + $result['exam'] + $result['member'] + $result['notice'] + $result['question'] + $result['subject'];
			    echo json_encode($result);
				break;
			
			case "clear_all" :
		        extract($_POST);
		        $data =array('updated_at'=>date('Y-m-d H:i:s'));
				$result =update_data($user_type, $data, $id);
				echo json_encode($result);
				break;		
			case "notice_info":
		       	$res =get_all('notice','*', array('status'=>'ACTIVE'));
		       	echo json_encode($res);
				break;
			
			// ==== Acts ====//	
			case "acts_list":
		       	$res =dropdownlist('acts','id','name');
				echo $res;
				break;	
			case "acts_info":
		       	$res =get_all('acts','*', array('status'=>'ACTIVE'));
		       	echo json_encode($res);
				break;
				
		    // ==== Advocate Info ====//		
			case "search_advocate":
			    extract($_POST);
			    $name = !empty($name) ? trim($name) : '';
			    $state_clause = !empty($state_code) ? " and advocate.state_code='$state_code' " : "";
			    $dist_clause = !empty($district_code) ? " and advocate.district_code='$district_code' " : "";
			    $sql = "select advocate.*, district.name as district_name, state.name as state_name, bc.name as bc_name 
			            from advocate 
			            left join district on advocate.district_code = district.code 
			            left join state on advocate.state_code = state.code 
			            left join bc on advocate.bc_id = bc.id 
			            where advocate.name like '%$name%' and advocate.type in('ACTIVE','VERIFIED','PREMIUM') $state_clause $dist_clause 
			            order by advocate.id desc limit 100";
			    $res = direct_sql($sql, false);
				echo json_encode($res);
				break;	
			case "search_advocate_eno":
			    extract($_POST);
			    $e_no = !empty($e_no) ? trim($e_no) : '';
			    $state_clause = !empty($state_code) ? " and advocate.state_code='$state_code' " : "";
			    $sql = "select advocate.*, district.name as district_name, state.name as state_name, bc.name as bc_name 
			            from advocate 
			            left join district on advocate.district_code = district.code 
			            left join state on advocate.state_code = state.code 
			            left join bc on advocate.bc_id = bc.id 
			            where advocate.e_no like '%$e_no%' and advocate.type in('ACTIVE','VERIFIED','PREMIUM') $state_clause 
			            order by advocate.id desc limit 100";
			    $res = direct_sql($sql, false);
				echo json_encode($res);
				break;
			case "search_advocate_mobile":
			    extract($_POST);
			    $mobile = !empty($mobile) ? trim($mobile) : '';
			    $sql = "select advocate.*, district.name as district_name, state.name as state_name, bc.name as bc_name 
			            from advocate 
			            left join district on advocate.district_code = district.code 
			            left join state on advocate.state_code = state.code 
			            left join bc on advocate.bc_id = bc.id 
			            where advocate.mobile like '%$mobile%' and advocate.type in('ACTIVE','VERIFIED','PREMIUM') 
			            order by advocate.id desc limit 100";
			    $res = direct_sql($sql, false);
				echo json_encode($res);
				break;
			case "search_advocate_pa":
			    extract($_POST);
			    $pa = !empty($pa) ? trim($pa) : '';
			    $state_clause = !empty($state_code) ? " and advocate.state_code='$state_code' " : "";
			    $dist_clause = !empty($district_code) ? " and advocate.district_code='$district_code' " : "";
			    $sql = "select advocate.*, district.name as district_name, state.name as state_name, bc.name as bc_name 
			            from advocate 
			            left join district on advocate.district_code = district.code 
			            left join state on advocate.state_code = state.code 
			            left join bc on advocate.bc_id = bc.id 
			            where advocate.practice_area like '%$pa%' and advocate.type in('ACTIVE','VERIFIED','PREMIUM') $state_clause $dist_clause 
			            order by advocate.id desc limit 100";
			    $res = direct_sql($sql, false);
				echo json_encode($res);
				break;
			case "search_advocate_year":
			    extract($_POST);
			    $e_year = !empty($e_year) ? trim($e_year) : '';
			    $state_clause = !empty($state_code) ? " and advocate.state_code='$state_code' " : "";
			    $dist_clause = !empty($district_code) ? " and advocate.district_code='$district_code' " : "";
			    $sql = "select advocate.*, district.name as district_name, state.name as state_name, bc.name as bc_name 
			            from advocate 
			            left join district on advocate.district_code = district.code 
			            left join state on advocate.state_code = state.code 
			            left join bc on advocate.bc_id = bc.id 
			            where advocate.e_year like '%$e_year%' and advocate.type in('ACTIVE','VERIFIED','PREMIUM') $state_clause $dist_clause 
			            order by advocate.id desc limit 100";
			    $res = direct_sql($sql, false);
				echo json_encode($res);
				break;	
			case "get_pa":
		        extract($_POST);
		        $apa =direct_sql('select name from practice_area')['data'];
		        $spa = get_data('advocate',$mobile, 'practice_area','mobile')['data'];
		        $res =array('spa'=>$spa,'apa'=>$apa);
		        echo json_encode($res);
				break;	
		    
		    case "upload" :
		        //print_r($_FILES);
				$result =uploadimg('uploadimg');
				echo json_encode($result);
				break;	
			
			
			// ==== Bar Assocaation ====//	
			case "ba_list":
		       	$res =dropdownlist('ba','id','name');
		       	echo $res;
				break;
			case "ba_all_list":
		       	$res =direct_sql("select * from ba where status='ACTIVE' order by name");
		       	echo json_encode($res);
				break;
			case "ba_info":
		       	$res =get_all('ba','*', array('id'=>$_POST['id']));
		       	$state_code =$res['data'][0]['state_code'];
		       	$district_code =$res['data'][0]['district_code'];
		       	$res['data'][0]['district_name'] = get_data('district',$district_code,'name','code')['data'];
		       	$res['data'][0]['state_name'] = get_data('state',$state_code,'name','code')['data'];
		       	echo json_encode($res);
				break;
		    // ==== Bar Council ====//	
			case "bc_list":
		       	$res =dropdownlist('bc','id','name');
		       	echo $res;
				break;
			case "bc_all_list":
		       	$res =direct_sql("select * from bc where status='ACTIVE' order by name");
		       	echo json_encode($res);
				break;		
			case "bc_info":
		       	$res =get_all('bc','*', array('id'=>$_POST['id']));
		       	$state_code =$res['data'][0]['state_code'];
		       	$district_code =$res['data'][0]['district_code'];
		       	$res['data'][0]['district_name'] = get_data('district',$district_code,'name','code')['data'];
		       	$res['data'][0]['state_name'] = get_data('state',$state_code,'name','code')['data'];
		       	echo json_encode($res);
				break;
			case "bc_listdd":
			   	$res =dropdownlist('bc','id','name', $_POST['bc_id']);
				echo $res;
				break;
			// ====College and University ====//	
		    case "college_listdd":
			    extract($_POST);
		       	$res =dropdownwhere('college','code', 'name', array('univ_code'=>$univ_code), $college_code);
				echo $res;
				break;
			case "univ_info":
			    extract($_POST);
		       	$res =direct_sql("select university.name, university.type, university.code, university.year, university.address, university.location, university.speciality, university.affiliated_college, university.constituent_college, university.url, district.name as district_name, state.name as state_name from university,state, district where university.state_code ='$state_code' and university.state_code =state.code and university.district_code = district.code");
		       	echo json_encode($res);
				break;
			case "univ_list":
				//$state =$_REQUEST['state_code'];
				$dist =$_REQUEST['district_code'];
				//$state_id = get_data('state',$state, 'state_id','state_code')['data'];
				$district_id  = get_data('district',$dist, 'district_id','district_code')['data'];
				
				$sql ="select * from university where district_id ='$district_id' and  u_status='ACTIVE' ";
				$res =direct_sql($sql,false);
				echo json_encode($res);
				break;
			case "univ_listdd":
			    extract($_POST);
		       	$res =dropdownwhere('university','code', 'name', array('state_code'=>$state_code), $univ_code);
				echo $res;
				break;
				
			// ==== District, State and UT ====//	
			case "district_listdd":
			    extract($_POST);
		       	//$res =dropdownlist('district','code','name');
		       	$res =dropdownwhere('district','code', 'name', array('state_code'=>$state_code), $district_code);
				echo $res;
				break;
			case "dist_list":
				$state =$_REQUEST['state_code'];
				$sql ="select district_id, state_code, district_code, district_name from district where state_code ='$state_code' ";
				$res =direct_sql($sql,false);
				echo json_encode($res);
				break;	
			case "state_list":
				$sql ="select state_id, state_code, state_name from state ";
				$res =direct_sql($sql,false);
				echo json_encode($res);
				break;
			case "state_listdd":
			   	$res =dropdownlist('state','code','name', $_POST['state_code']);
				echo $res;
				break;
		    
			// ==== Exam ====//
			case "exam_list":
		       	$res =direct_sql("select * from exam where status='ACTIVE' order by name");
		       	echo json_encode($res);
				break;	
			case "question_list":
		       	$res =get_all('question','*', array('subject_id'=>$_POST['subject_id']));
				echo json_encode($res);
				break;	
			case "question_list_exam":
		       	$res =get_all('question','*', array('exam_id'=>$_POST['exam_id']));
				echo json_encode($res);
				break;	
			case "subject_list":
		       	$res =direct_sql("select * from subject where status='ACTIVE' order by name");
		       	echo json_encode($res);
				break;	
			
			// ==== Login ====//	 
		     case "signup" : // Insert Data in Member and Advocate Table 
				extract($_POST);
				
				$sql ="SELECT id, name, mobile,password FROM advocate  where mobile ='$mobile' UNION ALL SELECT id, name, mobile, password  FROM member where mobile ='$mobile'";
				$res = direct_sql($sql);
				if($res['count']==0)
				{
					unset($_POST['user_type']);
					unset($_POST['tc']);
					$password =rnd_str(6);
					$_POST['password'] =md5($password);
					$res = insert_data($user_type,$_POST);
					if($res['id'] !=0)
					{
						//$sms = "Dear ". $name .", thanks for joining MyAdvIndia \nYour Password: $passsword \nRegards \nMyAdvIndia \nwww.myadv.in";
                        //$sms="Dear ".$name.", Your mobile verification code / Password is ". $password ." \nRegards \n https://myadv.in | MyAdv India \nOfferPlant"; 	// From 04 Dec 2024 
                        $sms="Dear ".$name.", \n Your MyAdv India OTP / EVC / Password is: ". $password ." \nVisit https://myadv.in \nRegards \nEMYADV \nOfferPlant";	// From 05 mar 2025 
						//Dear {#var#} , thanks for joining MyAdvIndia Your Password: {#var#} Regards MyAdvIndia www.myadv.in
						bulk_msg($mobile, $sms);
						rtfmail($email, 'Congrats for joining ' .$name , $sms);
					}
				}
				else{
					$res['msg'] ='Number Already Registered';
					$res['status'] ='error';
				}
					echo json_encode($res);
				break;
			case "login_user" :
				extract($_POST);
				$password =md5($user_pass);
				$sql="SELECT id, name, mobile, email, 'advocate' as 'user_type' FROM advocate  where mobile ='$user_name' and password='$password' and status !='BLOCK' UNION ALL SELECT id, name, mobile, email , 'member' as 'user_type' from  member where mobile ='$user_name' and password='$password' and status !='BLOCK'";
				
				$result = direct_sql($sql);
				if($result['count'] ==0)
				{
					$result['id'] =0;
					$result['status'] ='fail';
					$result['msg'] ='Something Went Worng';
				}
			    echo json_encode($result);
				break;
			
			case "get_user" :
				extract($_POST);
				
				//print_r($_POST);
				$result = get_data($user_type, $id, null, 'id');
			    echo json_encode($result);
				break;
			
			case "update_user" :
				extract($_POST);
				if (isset($email)) {
					$ce = get_data($user_type,$id,'email')['data'];
					if($email != $ce)
					{
						$_POST['email_status'] ='PENDING';
					}
				}
				//print_r($_POST);
				unset($_POST['user_type']);
				$result = update_data($user_type, $_POST, $id);
			    $result['url']='main.html';
			    echo json_encode($result);
				break;
			
			case "delete_user" :
				extract($_POST);
				if ($user_type == 'advocate' || $user_type == 'member') {
					// 1. Fetch user data to archive
					$userDataRes = get_data($user_type, $id);
					if ($userDataRes['status'] === 'success' && $userDataRes['data'] !== null) {
						$user = $userDataRes['data'];
						$u_name = mysqli_real_escape_string($con, $user['name']);
						$u_mobile = mysqli_real_escape_string($con, $user['mobile']);
						$u_email = mysqli_real_escape_string($con, $user['email']);
						
						// 2. Insert into deleted_users table
						$archive_sql = "INSERT INTO deleted_users (user_id, user_type, name, mobile, email) VALUES ('$id', '$user_type', '$u_name', '$u_mobile', '$u_email')";
						direct_sql($archive_sql, 'set');
					}
					
					// 3. Delete the original user record
					$result = delete_data($user_type, $id);
				} else {
					$result = array('status' => 'error', 'msg' => 'Invalid User Type');
				}
				echo json_encode($result);
				break;
			
		
			case "verify_mobile" :
			    //print_r($_POST);
				extract($_POST);
				if(get_data($user_type,$mobile,'mobile_status','mobile')['data'] !='VERIFIED')
				{
				$otp = rand(100000,999999);
                $name = get_data($user_type,$mobile,'name','mobile')['data'];
				
				//$sms = "Dear ".$name.", Your mobile verification code is ".$otp."\nRegards \nMyAdv India \nwww.myadv.in";
                //$sms="Dear ".$name.", Your mobile verification code / Password is ". $otp ." \nRegards \n https://myadv.in | MyAdv India \nOfferPlant"; 	// From 04 Dec 2024 
                $sms="Dear ".$name.", \n Your MyAdv India OTP / EVC / Password is: ". $otp ." \nVisit https://myadv.in \nRegards \nEMYADV \nOfferPlant";	// From 05 mar 2025 
				
				bulk_msg($mobile, $sms);
				$res =array('status'=>'success','msg'=>'OTP Sent Successfully','otp'=>$otp);
				}
				else{
				    $res =array('status'=>'fail','msg'=>'Already Verified');
				}
				echo json_encode($res);
				break;
				
			case "verify_email" :
			    //print_r($_POST);
				extract($_POST);
				if(get_data($user_type,$mobile,'email_status','mobile')['data'] !='VERIFIED')
				{
				   $email = get_data($user_type,$mobile,'email','mobile')['data'];
				$link = encode('user_type='.$user_type.'&mobile='.$mobile.'&email='.$email);
				$vlink = $inst_url.'/ev.php?link='.$link;
				rtfmail($email, "Email Verification form ". $inst_name, "Copy the below link and paste in browser or <a href='".$vlink."'> Click to Verify </a> <br><br>".$vlink);
				$res =array('status'=>'success','msg'=>'Email Sent Successfully to '. $email);
				}
				else{
				    $res =array('status'=>'fail','msg'=>'Already Verified');
				}
				echo json_encode($res);
				break;	
					
			case "recover" : // Update password to Member and Advocate Table 
				extract($_POST);
				$sql="SELECT id, name, mobile, email, 'advocate' as 'user_type' FROM advocate  where mobile ='$mobile' and status !='BLOCK' UNION ALL SELECT id, name, mobile, email , 'member' as 'user_type' from  member where mobile ='$mobile' and status !='BLOCK'";
				$res = direct_sql($sql);
				if($res['count']>0)
				{
					$passsword =rnd_str(6);
					$_POST['password'] =md5($passsword);
					$id = $res['data'][0]['id'];
					$user_type = $res['data'][0]['user_type'];
					$name = $res['data'][0]['name'];
					$mobile = $res['data'][0]['mobile'];
					$email = $res['data'][0]['email'];
					$updated_on = date("d-M-Y h:i:s A");
					$res = update_data($user_type,$_POST,$id);
					if($res['status'] =='success')
					{
						// $sms = "Dear ". $name ." Your new \nPassword: $passsword \n\nRegards \n$inst_name \nwww.myadv.in";
                        //$sms="Dear ".$name.", Your mobile verification code / Password is ". $passsword ." \nRegards \n https://myadv.in | MyAdv India \nOfferPlant"; 	// From 04 Dec 2024 
                        $sms="Dear ".$name.", \n Your MyAdv India OTP / EVC / Password is: ". $passsword ." \nVisit https://myadv.in \nRegards \nEMYADV \nOfferPlant";	// From 05 mar 2025 					
						bulk_msg($mobile, $sms);
						rtfmail($email, $name. ' your MyAdv India profile updated on ' .$updated_on , $sms);
						
					}
				}
				else{
					$res['msg'] ='Number Not Registered';
					$res['status'] ='error';
				}
				echo json_encode($res);
				break;
			// ==== Public Service Commission ====//	
			case "psc_list":
		       	$res =direct_sql("select * from psc where status='ACTIVE' order by name");
		       	echo json_encode($res);
				break;	
			case "psc_info":
		       	$res =get_all('psc','*', array('id'=>$_POST['id']));
		       	$state_code =$res['data'][0]['state_code'];
		       	$district_code =$res['data'][0]['district_code'];
		       	$res['data'][0]['district_name'] = get_data('district',$district_code,'name','code')['data'];
		       	$res['data'][0]['state_name'] = get_data('state',$state_code,'name','code')['data'];
		       	echo json_encode($res);
				break;
	    	// ==== Others ==== //
            case "search_ifsc":
			    extract($_POST);
			    $sql = "select  * from bank_ifsc where bank_ifsc.ifsc='$ifsc' and status='ACTIVE' order by ifsc";
			    $res =direct_sql($sql,false);
				echo json_encode($res);
				break;
			case "search_ifsc_by_branch":
			    extract($_POST);
			    $sql = "select  * from bank_ifsc where bank_ifsc.branch like '%$ifsc%' and status='ACTIVE' order by ifsc";
			    $res =direct_sql($sql,false);
				echo json_encode($res);
				break;
			case "search_pincode_code":
			    extract($_POST);
			    $sql = "select  * from pincode where pincode.pincode='$pincode' and status='ACTIVE' order by locality_name";
			    $res =direct_sql($sql,false);
				echo json_encode($res);
				break;	
			case "search_pincode_by_name":
			    extract($_POST);
			    $sql = "select  * from pincode where pincode.locality like '#$pincode' and status='ACTIVE' order by pincode";
			    $res =direct_sql($sql,false);
				echo json_encode($res);
				break;		
			case "search_hsnsac":
			    extract($_POST);
			    $sql = "select  * from hsnsac where hsnsac.code='$hsnsaccode' and status='ACTIVE' order by code";
			    $res =direct_sql($sql,false);
				echo json_encode($res);
				break;
			case "search_hsnsac_by_name":
			    extract($_POST);
			    $sql = "select  * from hsnsac where hsnsac.description like '%$hsnsaccode%' and status='ACTIVE' order by code";
			    $res =direct_sql($sql,false);
				echo json_encode($res);
				break;	
		
			default:
			    echo" No Task Selected or Invalid Task";
		}
	}
}
else{
    die("Not Authorised");
}
?>