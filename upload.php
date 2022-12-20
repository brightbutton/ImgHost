<?php

$html_output = "";

function countUploads($file_amount){
	global $mysqli_connect;
	
	$user_ip = $_SERVER['REMOTE_ADDR'];
	
	$request = $mysqli_connect -> query("SELECT * FROM visitors WHERE ip='$user_ip'");
	$result = $request->fetch_assoc();
	
	$total_upload_amount = $file_amount + $result["uploads"];
	
	if(empty($result["ip"])){
		
		$mysqli_connect -> query("INSERT INTO visitors VALUES ('$user_ip', '$total_upload_amount')");
	}
	elseif($total_upload_amount<=100)
	{
		$mysqli_connect -> query("UPDATE visitors SET uploads='$total_upload_amount' WHERE ip='$user_ip'");
	}
	else{
		header("Location: /?error=uploadlimit");
		die();
	}
}


function generate_id($id_length){
    
    $id = "";
    $id_chars = array("A", "B", "C", "D", "E", "F", "G", "H", "I", "J", "K", "L", "M", "N", "O", "P", "Q", "R", "S", "T", "U", "V", "W", "X", "Y", "Z", "0", "1", "2", "3", "4", "5", "6", "7", "8", "9", "-","_");
    $id_chars_amount = count($id_chars)-1;
    
    for($i = 0; $i < $id_length; $i++){
        
        $rand_char = rand(0, $id_chars_amount);
        $id = $id . $id_chars[$rand_char];
        
    }
    return $id;
}


function check_id_availability($id_name){
    global $mysqli_connect;
    
    
    $id_available = true;
    while($id_available){
        
        $id_public = generate_id(10);
        $id_private = generate_id(30);
        
        if(!empty($id_name)){
            $id_public = $id_name;
        }
        
        $request = $mysqli_connect->query("SELECT id_public, id_private FROM uploads WHERE id_public='$id_public' OR id_private='$id_private' LIMIT 1");
        $result = $request->fetch_assoc();
        
        if(empty($result["id_public"]) && empty($result["id_private"])){
            $id_available = false;
        }
        elseif(!empty($id_name))
        {
            $id_name = $id_name . rand(1,9);
        }
    }
    return array($id_public, $id_private);
}


function save_to_db(){
    global $mysqli_connect, $_POST;
    
    if(empty($_POST["customlink"])){
        $id_name = "";
    }
    else
    {
        $id_name = $_POST["customlink"];
    }
    
    $ids = check_id_availability($id_name);
    $id_public = $ids["0"];
    $id_private = $ids["1"];
    $expiration = 0;
    $password = 0;
    
    if($_POST["expiration"] != "-1"){
        $expiration = time()+$_POST["expiration"];
    }
    if(!empty($_POST["password"])){
        $password = password_hash($_POST["password"], PASSWORD_BCRYPT);
    }
    
    $mysqli_connect->query("INSERT INTO uploads VALUES('$id_public', '$id_private', '$expiration', '$password')");
    
    return array($id_public, $id_private);
}


function save_files(){
    global $html_output, $langvar;
    
    $file_amount = count($_FILES["images"]["tmp_name"]);
    countUploads($file_amount);
	
    if($file_amount > 0){
        
        $ids = save_to_db();
        $folder = $ids['0'] . "/" . substr($ids['1'], 0, 10);
        mkdir("./usrimg/$folder", 0777, true);
    
        for($i=0; $i < $file_amount; $i++){
			
			$file_type = $_FILES["images"]["type"][$i];
            $file_tmp = $_FILES["images"]["tmp_name"][$i];
            $file_name = $_FILES["images"]["name"][$i];
        
            if(str_contains($file_type, "image") {
				
                move_uploaded_file($file_tmp, "./usrimg/$folder/$file_name");
            }
        }
        
		$link_view = "https://" . $_SERVER['SERVER_NAME'] . "/?id=" . $ids['0'];
		$link_delete = "https://" . $_SERVER['SERVER_NAME'] . "/?id=" . $ids['1'];
		$html_output = "
		<div class='message_success'>
			<h1>". $langvar['message_success_uploaded_h1'] ."</h1>
			<p><b>". $langvar['message_success_uploaded_p_link_view'] ."</b></br><a href='$link_view' target='_blank'>$link_view</a></br><b>". $langvar['message_success_uploaded_p_link_delete'] ."</b></br><a href='$link_delete' target='_blank'>$link_delete</a></p>
		</div>
		";
    }
}

if($_GET["error"] == "uploadlimit"){
		$html_output = "
		<div class='message_error'>
			<h1>". $langvar['message_error_uploadlimit_h1'] ."</h1>
			<p>". $langvar['message_error_uploadlimit_p'] ."</p>
		</div>
		";
}


if(!empty($_POST['expiration'])){
	save_files();
}
?>
<!DOCTYPE html>
<html>
<head>
	<title>ImgHost - Image Uploader</title>
	<link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Secular+One&display=swap">
	<link rel="stylesheet" href="/assets/css/style.css">
</head>

<body>

	<div class="form_wrapper">
	
<?php echo$html_output;?>
		
		<form class="form_upload" enctype="multipart/form-data" method="POST">
			<div class="input_file form_element">
				<label class="input_file_label">
					<input type="file" name="images[]" accept="image/*" multiple required />
				</label>
			</div>
			
			<div class="form_group form_group_list">
				<div class="form_element input_customlink">
					<span><?php echo$langvar["customlink"];?></span>
					<input type="text" name="customlink" placeholder="<?php echo$langvar['customlink'];?>" />
				</div>
	
				<div class="form_element input_password">
					<span><?php echo$langvar["password"];?></span>
					<input type="password" name="password" placeholder="<?php echo$langvar['password'];?>" />
				</div>
			</div>
			
			
			<div class="form_group form_group_list_2">
				<div class="form_element input_expiration">
					<span><?php echo$langvar["expire"];?></span>
					<select name="expiration">
						<option value="86400"><?php echo$langvar["expire-one-day"];?></option>
						<option value="604800"><?php echo$langvar["expire-one-week"];?></option>
						<option value="2629746"><?php echo$langvar["expire-one-month"];?></option>
						<option value="31556952"><?php echo$langvar["expire-one-year"];?></option>
						<option value="-1" selected><?php echo$langvar["expire-never"];?></option>
					</select>
				</div>
				<div class="form_element"> 
					<span class="placeholder">-</span>
					<button class="form_element button_upload" type="submit"><?php echo$langvar["upload"];?></button>
				</div>
			</div>
		</form>
	</div>

</body>
</html>