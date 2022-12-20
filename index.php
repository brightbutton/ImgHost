<?php session_start();

include "lang/en.php";
$lang = substr($_SERVER["HTTP_ACCEPT_LANGUAGE"], 0, 2);
include "lang/$lang.php";


$mysqli_connect = new mysqli("localhost", "user", "pw", "db");
$mysqli_connect -> set_charset("utf8");


if(!empty($_GET['id']))
{
    include "view.php";
}
else
{
    include "upload.php";
}