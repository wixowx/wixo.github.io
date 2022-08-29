<?php
if(isset($_GET['code'])) {
$code    = $_GET['code'];
$title   = $_GET['title'];
$file   = base64_decode($code); 
$name = 'iegybest.in--'.$title.'.mp4';
header("Content-Description: File Transfer"); 
header("Content-Type: application/octet-stream"); 
header("Content-Disposition: attachment; filename=\"". basename($name) ."\""); 
readfile ($file);
exit(); 
}
?>
