<?php

require "../../class/ValidateCode.php";
session_start();
if (!isset($_SESSION['public']) && !isset($_SESSION['user_name'])) {
    header("location:../../404.html");
}
$newimg = new ValidateCode();
$newimg->Doimg_yan();
$_SESSION['yzm'] = $newimg->Get_img_code();
