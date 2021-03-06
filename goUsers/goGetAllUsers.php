<?php
    /******************************************************
    ### Name: goGetAllUserLists.php 					###
    ### Description: API to get all User Lists 			###
    ### Version: 4.0 									###
    ### Copyright: GOAutoDial Ltd. (c) 2011-2016 		###
    ### Written by: Jeremiah Sebastian V. Samatra 		###
    ### License: AGPLv2 								###
    ******************************************************/
    include_once ("goAPI.php");
    include_once ("../licensed-conf.php");
	
	$user = $session_user;
	
	if(!empty($session_user)){
		// get user_level
		$astDB->where("user", $user);
		$query_userlevel = $astDB->getOne("vicidial_users", "user, user_group");
		//$query_userlevel_sql = "SELECT user_level,user_group FROM vicidial_users WHERE user = '$user' LIMIT 1";
		$user_level = $query_userlevel["user_level"];
		$groupId = $query_userlevel["user_group"];
		
		if (!checkIfTenant($groupId)) {
			$ul='';
			if (strtoupper($groupId) != 'ADMIN') {
				if ($user_level > 8) {
					$uQuery = "SELECT tenant_id FROM go_multi_tenant;";
					$uRslt = mysqli_query($linkgo, $uQuery);
					if (mysqli_num_rows($uRslt) > 0) {
						$ul = "AND user_group NOT IN (";
						$uListGroups = "";
						while($uResults = mysqli_fetch_array($uRslt, MYSQLI_ASSOC)) {
							$uListGroups = "'{$uResults['tenant_id']}',";
						}
						$ul .= rtrim($uListGroups, ',');
						$ul .= ")";
					}
				} else {
					$ul = "AND user_group='$groupId'";
				}
			}
		} else { 
			$ul = "AND user_group='$groupId'";  
		}
		if ($groupId != 'ADMIN') {
			$notAdminSQL = "AND user_group != ?";
			$arrLastPhoneLogin = array("VDAD", "VDCL", "goautodial", "goAPI", 4, "", "ADMIN");
		}else{
			$arrLastPhoneLogin = array("VDAD", "VDCL", "goautodial", "goAPI", 4, "");
		}
		
		// getting agent count
		$userslist = array();
		$userslist = array_merge($userslist, $default_users);
		array_push($userslist, $user, 4);
		$getLastCount = $astDB->rawQuery("SELECT user FROM vicidial_users WHERE user NOT IN (?,?,?,?,?) AND user_level != ? ORDER BY user ASC", $userslist);
		$max = $astDB->count;
		//$getLastCount = "SELECT user FROM vicidial_users WHERE user NOT IN ('VDAD','VDCL', 'goAPI', 'goautodial', '$user') AND user_level != '4' ORDER BY user ASC";
		//$queryCount = mysqli_query($link, $getLastCount);
		//$max = mysqli_num_rows($queryCount);
			
			// condition
			for($i=0; $i < $max; $i++){
				$userRow = $getLastCount[$i];
				if(preg_match("/^agent/i", $userRow['user'])){
					$get_last = preg_replace("/^agent/i", "", $userRow['user']);
					$last_num[] = intval($get_last);
				}
			}

			// return data
			$get_last = max($last_num);
			$agent_num = $get_last + 1;
			
		// getting phone login count
		
		$queryLastPhoneLogin = $astDB->rawQuery("SELECT phone_login FROM vicidial_users WHERE user NOT IN (?,?,?,?) AND user_level != ? AND phone_login != ? $notAdminSQL ORDER BY phone_login DESC", $arrLastPhoneLogin);
		//$queryPhoneLoginCount = mysqli_query($link, $getLastPhoneLogin);
		//$max_phonelogins = mysqli_num_rows($queryPhoneLoginCount);
		
			// condition
			if($astDB->count > 0){
				for($i=0; $i < count($queryLastPhoneLogin);$i++){
					$get_last_phonelogin = $queryLastPhoneLogin[$i];
					if(preg_match("/^Agent/i", $get_last_phonelogin['phone_login'])){
						$get_last_count = preg_replace("/^Agent/i", "", $get_last_phonelogin['phone_login']);
						$last_pl[] = intval($get_last_count);
					}else{
						$get_last_count = $get_last_phonelogin['phone_login'];
						$last_pl[] = intval($get_last_count);
					}
				}
				// return data
				$phonelogin_num = max($last_pl);
				$phonelogin_num = $phonelogin_num + 1;
				
			}else{
				// return data
				$phonelogin_num = "0000001";
			}
	
		// getting all users
		//	$query = "SELECT user_id, user, full_name, user_level, user_group, active FROM vicidial_users WHERE user NOT IN ('VDAD','VDCL') AND user_level != '4' $ul $notAdminSQL ORDER BY user ASC;";
		$getting_users = array("VDAD", "VDCL", "goAPI", "goautodial", 4, $user);
		$query = $astDB->rawQuery("SELECT user_id, user, full_name, user_level, user_group, phone_login, active FROM vicidial_users WHERE user NOT IN (?,?,?,?) AND (user_level != ?) $ul ORDER BY user != ?, user_id DESC", $getting_users);	
		$countResult = $astDB->count;

		$querygo = $goDB->query("SELECT userid, avatar FROM users ORDER BY userid DESC");
		$countResultgo = $goDB->count;
		
		// condition	
		if($countResultgo > 0) {
			$datago = array();
			for($i=0; $i < $countResultgo; $i++){
				$fresultsgo = $querygo[$i];					
				array_push($datago, $fresultsgo);
				$dataUserIDgo[] = $fresultsgo['userid'];
				$dataAvatar[] = $fresultsgo['avatar'];
			}
		}
		
		// condition
		if($countResult > 0) {
			$data = array();
			for($i=0; $i < $countResult; $i++){
				$fresults = $query[$i];
				array_push($data, $fresults);
				$dataUserID[] = $fresults['user_id'];
				$dataUser[] = $fresults['user'];
				$dataFullName[] = $fresults['full_name'];
				$dataUserLevel[] = $fresults['user_level'];
				$dataUserGroup[] = $fresults['user_group'];
				$dataPhone[] = $fresults['phone_login'];
				$dataActive[]	= $fresults['active'];
				//$apiresults = array("result" => "success", "data" => $data);
			}
			
			$apiresults = array("result" => "success", "user_id" => $dataUserID,"user_group" => $dataUserGroup, "user" => $dataUser, "full_name" => $dataFullName, "user_level" => $dataUserLevel, "phone_login" => $dataPhone, "active" => $dataActive, "last_count" => $agent_num, "last_phone_login" => $phonelogin_num, "avatar" => $dataAvatar, "useridgo" => $dataUserIDgo, "licensedSeats" => $config["licensedSeats"]);
			
		} else {
			$err_msg = error_handle("10010");
			$apiresults = array("code" => "10010", "result" => $err_msg); 
		}
	}else{
		$err_msg = error_handle("40001");
		$apiresults = array("code" => "40001", "result" => $err_msg); 
	}
?>
