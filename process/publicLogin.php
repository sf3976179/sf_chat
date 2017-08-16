<?php

/*
  <#日期 = "2017-7-19">
  <#时间 = "00:27:47">
  <#人物 = "buff" >
  <#备注 = " ">
 */
header('Content-type: application/json');
session_start();
if ($_SESSION['public'] !== true) {
    header("location:../404.html");
    exit;
}
$true_yzm = $_SESSION['yzm'];
$user_yzm = strtolower($_POST['yzm']);
$user_name = trim($_POST['user_name']);
/*
 * code 
 *     -1:验证码不正确
 *     -2:用户名不符合规范
 *    200:ok
 */
if ($true_yzm != $user_yzm) {
    exit("{\"code\":\"-1\",\"mes\":\"验证码不正确\",\"trueyzm\":\"{$true_yzm}\"}");
}
$pattern = '@[\\\/\:\*\?\"\'\<\>\|\s]@';
$regRes = preg_match($pattern, $user_name);
if (strlen($user_name) > 24 || $regRes !== 0) {
    exit('{"code":"-2","mes":"用户名不符合规范"}');
}
$NowDatep = date("Y-m-d H:i", time());
$timeStamp = strtotime($NowDatep);
$token = hash("sha256", $timeStamp . 'daimin' . $user_name);
//$_SESSION['user_name']=$user_name;

echo "{\"code\":\"200\",\"mes\":\"公共用户注册成功\",\"user_name\":\"{$user_name}\",\"token\":\"{$token}\"}";









