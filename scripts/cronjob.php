<?php session_start();

$mysqli_connect = new mysqli("localhost", "user", "pw", "db");
$mysqli_connect -> set_charset("utf8");

$mysqli_connect -> query("DELETE FROM visitors");

$results = $mysqli_connect -> query("SELECT * FROM uploads ORDER BY expiration DESC");
while($row = $results->fetch_assoc()){
	
	$id_public = $row["id_public"];
	$id_private = $row["id_private"];
	$expiration = $row["expiration"];
	
	if($expiration != "0" && $expiration < time()){
	
		$images = array_slice(scandir("../usrimg/$id_public/".substr($id_private, 0, 10)), 2);
	
		foreach($images as $image){
			unlink("../usrimg/$id_public/".substr($id_private, 0, 10)."/".$image);
		}
		rmdir("../usrimg/$id_public/".substr($id_private, 0, 10));
		rmdir("../usrimg/$id_public");
	
		$mysqli_connect -> query("DELETE FROM uploads WHERE id_public = '$id_public' AND id_private = '$id_private'");
	}
}