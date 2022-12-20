<?php

function load_images($ids){
    $id_public = $ids["0"];
    $id_private = $ids["1"];
    $path = "usrimg/$id_public/" . substr($id_private, 0, 10);
    
    $images = array_slice(scandir($path), 2);
    $html_snippet = "";
    $quotation = '"';
	
    foreach($images as $image){
        $html_snippet = $html_snippet . "
        <div class='img_wrapper'>
            <a href='$path/$image' target='_blank'>
                <div class='img_element' style='background-image:url($quotation$path/$image$quotation), url(assets/img/img.png)'></div>
            </a>
        </div>";
    }
    
    return $html_snippet;
}


function check_password($info){
    global $_POST, $html_snippet, $langvar;
    
    $id_public = $info["0"];
    $id_private = $info["1"];
    $password = $info["2"];
    $entered_password = $_POST['password'];
    
    if($password == 0 || password_verify($entered_password, $password)){
        $html_snippet = load_images(array($id_public, $id_private));
    }
    else
    {
        $html_snippet = "
        <form method='post' class='form_upload'>
            <div class='form_element input_password'>
                <input type='password' name='password' placeholder='".$langvar['password']."' />
            </div>
            <div class='form_element'> 
                <button type='submit' class='button_upload'>".$langvar['enter']."</button>
            </div>
        </form>
        ";
    }
}


function delete_images($ids){
    global $mysqli_connect;
    
	$id_public = $ids["0"];
	$id_private = $ids["1"];
	
	$images = array_slice(scandir("usrimg/$id_public/".substr($id_private, 0, 10)), 2);
	
	foreach($images as $image){
		unlink("usrimg/$id_public/".substr($id_private, 0, 10)."/".$image);
	}
	rmdir("usrimg/$id_public/".substr($id_private, 0, 10));
	rmdir("usrimg/$id_public");
	
	$mysqli_connect->query("DELETE FROM uploads WHERE id_public = '$id_public' AND id_private = '$id_private'");
	
	header("Location: ./");
}


function id_lookup($id){
    global $mysqli_connect;
    
    $request = $mysqli_connect->query("SELECT * FROM uploads WHERE id_public='$id' OR id_private='$id' LIMIT 1");
    $result = $request->fetch_assoc();
    
    if(!empty($result["id_public"]) && !empty($result["id_private"])){
        
        if($result["id_public"] == $id){
           
			if($result["expiration"] != "0" && $result["expiration"] < time()){
				
				delete_images(array($result['id_public'], $result['id_private']));
			}
			else
			{
				check_password(array($result['id_public'], $result['id_private'], $result['password']));
			}
            
		}
		else
		{
			delete_images(array($result['id_public'], $result['id_private']));
		}
	}
	else
	{
		header("Location: ./");
	}
}

id_lookup($_GET["id"]);
?>
<!DOCTYPE html>
<html>
<head>
	<title>ImgHost - Image Viewer</title>
	<link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Secular+One&display=swap">
	<link rel="stylesheet" href="/assets/css/style.css">
</head>

<body>
	<div class="top_placeholder">
		<div class="form_wrapper">
			<div class="img_overview">
				<?php echo $html_snippet;?>
			</div>
		</div>
	</div>
</body>
</html>