<?php

//这个是cli运行的脚本 
/*
  <#日期 = "2017-7-19">
  <#时间 = "00:46:16">
  <#人物 = "buff" >
  <#备注 = "
 * code : 
 *       -1 => 错误消息
 *        1 => 私信
 *        2 => 全局消息
 *        3 => 通知注册用户成功消息
 *        4 => 初次登录显示用户列表
 *        5 => 更新用户列表--添加
 *        6 => 更新用户列表--减少
 *   
 * 
 * ">
 */
require_once '/usr/local/nginx/html/talking/sf_chat/class/WebS.php';
if (php_sapi_name() !== 'cli') {
    exit("使用cli模式");
}

$serv = new buff\WebS("127.0.0.1", 9501);
$serv->set(array(
    'daemonize'       => 0,
    'worker_num'      => 2, //worker process num
    'task_worker_num' => 2
//    'log_file'      => '/home/buff/swoole.log'
));
$redis = null;
$serv->on('WorkerStart', function ($serv, $worker_id) {
    global $redis;
    $redis = new \Redis();
    $redis->connect("127.0.0.1", 6379) || die("redis 连接失败");
    echo "进程{$worker_id}的redis 连接成功!\n";
});

//回调函数 新建一个websocket连接时 触发的事件
$serv->on('Open', function($serv, $req) {
    global $redis;
    $serv->opening($redis, $req);
});
//当收到用户的消息时 触发事件
$serv->on('Message', function($serv, $frame) {
    global $redis;
    $serv->messaging($redis, $frame);
});

//当websocket 断开连接时 触发事件
$serv->on('Close', function($serv, $fd) {
    global $redis;
    $serv->closing($redis, $fd);
});

//当任务结束时触发事件
$serv->on('Finish', function($serv, $task_id, $data) {
    echo $data;
});
$serv->start();
