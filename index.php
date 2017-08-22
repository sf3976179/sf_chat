<?php
/*
  <#日期 = "2017-8-10">
  <#人物 = "sf" >
  <#备注 = " ">
 */
session_start();
$_SESSION['user_name'] = '';
if ($_SESSION['user_name'] != '') {
    $islogin = true;
    $NowDatep = date("Y-m-d H:i", time());
    $timeStamp = strtotime($NowDatep);
    $token = hash("sha256", $timeStamp . 'daimin' . $_SESSION['user_name']);
}
else {
    $islogin = false;
    $_SESSION['public'] = true;
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
    <head>
        <meta charset="UTF-8"/>
        <meta http-equiv="X-UA-Compatible" content="IE=Edge">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <link rel="stylesheet" href="./public/css/bootstrap.min.css">
        <link rel="stylesheet" href="./public/css/style.css">
        <title>聊天室</title>
        <script>var localGroup = "public";
<?php
if ($islogin) {
    echo "var isLogin=true;var localToken=\"{$token}\";var localName=\"{$_SESSION['user_name']}\";";
}
else {
    echo "var isLogin=false;";
}
?>
        </script>
    </head>
    <body>
        <div id='isClose' style="">
            <div class='cont' style="">
                <p style=''>已断开连接!</p>
            </div>
        </div>
        <div id='sixindiv'>
            <p>发送私信给<span id='sendTo'></span></p>
            <textarea name="sixintext" id="sixintext" style=""></textarea>
            <button class="btn btn-primary">Enter</button>
            <span class=' close glyphicon glyphicon-remove'></span>
        </div>
        <div id='reqsixindiv'>
            <p>收到来自<span id='sendFrom'></span>的私信</p>
            <textarea name="sixintext" id="reqsixintext" style=""></textarea>
            <button class="btn btn-primary">Enter</button>
            <span class=' close glyphicon glyphicon-remove'></span>
        </div>
        <div id="main" class="container">
            <nav style="margin-top: 30px;" class="navbar navbar-default" role="navigation"><!--这里的role是为了增加语意-->
                <div class="container-fluid">
                    <div class="navbar-header">
                        <a class="navbar-brand" href="javascript:;">聊天室</a>
                    </div>
                    <ul class="nav navbar-nav">
                        <li class="active"><a href="/talking/sf_chat/index.php">公共聊天室</a></li>
                    </ul>
                </div>
            </nav>
            <div class="content clearfix">
                <div class="user_list pull-left">
                    <h2>当前在线</h2>
                    <ul>
                    </ul>
                </div>
                <div class="gui pull-left clearfix">
                    <div class="gui_content pull-left">
                    </div>
                    <div class="gui_user pull-left">
                        <div class="form-group  has-feedback">
                            <label class="control-label " for="public_name">临时用户:</label>
                            <input id="public_name" name="public_name" class="form-control" type="text" maxlength="16" placeholder="请输入您的用户名">
                            <span class="glyphicon  form-control-feedback"></span>
                        </div>
                        <div class="public_yzm form-group has-feedback clearfix">
                            <label class="control-label " for="public_name">请输入验证码:</label>
                            <input id="yzm" name="yzm" class="form-control" type="text" maxlength="4" placeholder="">
                            <span class="glyphicon  form-control-feedback "></span>
                            <img style='cursor: pointer;' onclick="this.src = './public/images/yanZhengMa.php?rand=' + Math.random()" src="./public/images/yanZhengMa.php" alt="">
                        </div>
                        <button id="public_login" class="btn btn-block btn-primary">确定</button>
                    </div>
                    <div class="gui_input" style="clear:both;">
                        <textarea id="user_input"  name="user_input" cols="30" rows="5" spellcheck="false" placeholder="Shift+Enter 换行"></textarea>
                        <button class="btn btn-primary">Enter</button>
                    </div>
                </div>
            </div> 
        </div>
        <!--[if lte IE 8]>
            <script src="./public/js/html5.js"></script>
           <script src="./public/js/jquery.min.1.9.1.js"></script>
           <script src="./public/js/bootstrap.min.js"></script>
         <![endif]-->
        <!--[if (gt IE 8)|!(IE)]><!-->
        <script src="./public/js/jquery.min.3.2.1.js"></script>
        <script src="./public/js/bootstrap.min.js"></script>
        <!--<![endif]-->
        <script src="./public/js/1.js" type="text/javascript"></script>   
    </body>
</html>
