<?php

$con = mysqli_connect('remotemysql.com', 'tjwuFq57pY', 'O3stac5WDq');
mysqli_query($con,"SET NAMES 'utf8'");
mysqli_select_db($con, 'tjwuFq57pY');


$Query_tag   = "SELECT * FROM `star` WHERE star_id";
$Execute_tag = mysqli_query($con,$Query_tag);
$row_tag     = mysqli_fetch_assoc($Execute_tag); 

$name = $row_tag['star_name'];

echo $name ;
