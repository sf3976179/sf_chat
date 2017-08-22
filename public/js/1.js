/* global localGroup, isLogin, localName, localToken */

"use strict";
/**
 * 返回当前时间 如 10:46:14
 * @returns {String} 字符串
 */
function bf_get_time() {
    var date = new Date(), h = date.getHours(), m = date.getMinutes(), s = date.getSeconds();
    if (h < 10) {
        h = '0' + h;
    }
    if (m < 10) {
        m = '0' + m;
    }
    if (s < 10) {
        s = '0' + s;
    }
    return h + ':' + m + ':' + s;
}
$(function () {
    var $isUserNameFormat = false;
    var $isYZMFormat = false;
    $(".gui_input button").click(function () {
        if (isWebWClose) {
            alert("您已断开链接!");
            return;
        }
        var $mes = $("#user_input").val();
        if ($.trim($mes) === '') {
            return false;
        }
        if (localGroup === 'groupJ') {
            websocket.send("quesTo:" + $mes + ":user_name:" + selfName);
        } else {
            websocket.send($mes);
        }
        $("#user_input").val("");
    });
    $("#public_name").change(function () {
        var $val = $(this).val();
        $val = $val.replace(/[\"|\'|\:|\'|\<|\>|\/|\\|\||\s]/g, "");
        $(this).val($val);
        if ($val.length > 3 && $val.length < 16) {
            $isUserNameFormat = true;
            $(this).parent().addClass("has-success");
            $(this).siblings(".glyphicon").addClass("glyphicon-ok");
        } else {
            if ($(this).parent().hasClass("has-success")) {
                $(this).parent().removeClass("has-success");
            }
            if ($(this).siblings(".glyphicon").hasClass("glyphicon")) {
                $(this).siblings(".glyphicon").removeClass("glyphicon-ok");
            }
        }
    });
    $("#yzm").change(function () {
        var $val = $(this).val();
        var reg = /[A-Za-z0-9]{4}/;
        if (!reg.test($val)) {
            $isYZMFormat = false;
            alert("验证码格式不正确 请输入4位数字或字母");
            $(this).val("");
            if ($(this).parent().hasClass("has-success")) {
                $(this).parent().removeClass("has-success");
            }
            if ($(this).siblings(".glyphicon").hasClass("glyphicon")) {
                $(this).siblings(".glyphicon").removeClass("glyphicon-ok");
            }

        } else {
            $(this).parent().addClass("has-success");
            $(this).siblings(".glyphicon").addClass("glyphicon-ok");
            $isYZMFormat = true;
        }
    });
    $("#public_login").click(function () {
        var $user_name = $.trim($("#public_name").val());
        var $yzm = $("#yzm").val();
        if (!$isYZMFormat || !$isUserNameFormat) {
            alert("请输入正确的格式");
            return;
        }
        $.ajax({
            type: 'post',
            url: './process/publicLogin.php',
            data: {yzm: $yzm, user_name: $user_name},
            cache: false,
            dataType: 'json',
            crossDomain: false, //跨域请求 默认为false
            headers: {Accept: 'charset=utf-8'
            },
            complete: function (XMLHttpRequest, textStatus) {
                if (textStatus === 'error') {
                    alert('请求数据失败');
                }
                $("#public_name").val("");
                $("#public_name").siblings(".glyphicon").removeClass("glyphicon-ok");
                $("#public_name").parent().removeClass("has-success");
                $("#yzm").val("");
                $("#yzm").siblings(".glyphicon").removeClass("glyphicon-ok");
                $("#yzm").parent().removeClass("has-success");
            },
            success: function (data, textStatus, XMLHttpRequest) {
                if (data.code === '-1') {
                    alert("验证码不正确");
                } else if (data.code === '-2') {
                    alert("用户名格式不正确");
                }
                if (data.code === "200") {
                    websocket.send("tokenR:" + data.token + ":user_name:" + data.user_name + ":no");
                }
            },
            statusCode: {200: function () {
                }, 304: function () {
                    console.log('这是缓存的数据');
                }},
            error: function (XMLHttpRequest, textStatus, errorThrown) {
                console.log(textStatus);
            }

        });
    });
    $(".user_list").on("mouseenter", "li", function () {
        if ($(this).attr("data-userName") === selfName) {
            return;
        }
        var $sixin = "<p style=\"position:absolute;right:20px;top:0;background-color:#ccc;color:#0600ff;padding:0 15px;\">私信</p>";
        $(this).append($sixin);
    });
    $(".user_list").on("mouseleave", "li", function () {
        $(".user_list li p").remove();
    });
    $(".user_list").on("click", "p", function () {
        sendTo = $(this).parent().attr("data-userName");
        $("#sendTo").text(sendTo);
        $("#sixindiv").css("display", "block");
    });

    $("#sixindiv button").click(function () {
        $("#sendTo").text(sendTo);
        var $mes = $("#sixindiv textarea").val();
        if ($.trim($mes) === '') {
            return false;
        }
        $mes = $mes.replace(/[\:]/g, "Ø");
        websocket.send("sendTo:" + sendTo + ":mes:" + $mes + ":user_name:" + selfName);
        $("#sixindiv textarea").val("");
    });
    $("#sixindiv span").click(function () {
        $("#sixindiv").css("display", "none");
        $("#sendTo").text("");
    });
    $("#reqsixindiv span").click(function () {
        $("#reqsixindiv").css("display", "none");
        $("#sendFrom").text("");
    });
    $("#reqsixindiv button").click(function () {
        $("#reqsixindiv").css("display", "none");
        $("#sendFrom").text("");
    });
});

var selfName = '';
var isWebWClose;
var sendTo = '';
var wsServer = 'ws://127.0.0.1:9501?group=' + localGroup;
var websocket = new WebSocket(wsServer);
websocket.onopen = function (evt) {
    console.log("已连接上websocket服务器.");
    //alert(isLogin);
    if (isLogin) {
	console.log("初始发送数据：");
	console.log(localToken);
	console.log(localName);        
	websocket.send("tokenR:" + localToken + ":user_name:" + localName + ":yes:group:" + localGroup);
    }
};
websocket.onclose = function (evt) {
    console.log("已断开连接");
    isWebWClose = true;
    setTimeout(function () {
        $("#isClose").css("display", "block");
    }, 1000);

};
websocket.onmessage = function (evt) {
    console.log(evt.data);
    var data = JSON.parse(evt.data);
    switch (data.code) {
        //错误信息
        case '-1':
            alert(data.mes);
            break;
            //接受私信
        case '1':
            $("#reqsixindiv").css("display", "block");
            $("#reqsixintext").val(data.mes);
            $("#sendFrom").text(data.form);
            console.log("收到来自自" + data.form + "的信息" + data.mes);
            break;
            //全局消息
        case '2':
            var $time = bf_get_time();
            var $mes = data.mes;
            var $user_name = data.user_name;
            var $who = $user_name === selfName ? "self" : "other";
            var $append = "<div class=\"mes_item\"><p><span class=\"user_name\">" + $user_name + " </span><time>" + $time + "</time></p><p class=\"message " + $who + "\">" + $mes + "</p></div>"
            $(".gui_content").append($append);
            var $cont_scrTop = $(".gui_content").scrollTop() + 10;
            var $list_height = $(".mes_item:last-of-type").height();
            $(".gui_content").animate({'scrollTop': $cont_scrTop + $list_height}, 100);
            break;
            //通知注册用户成功消息
        case '3':
            $(".gui_user").html("<p>欢迎 " + data.user_name + "</p>");
            selfName = data.user_name;
            break;
            //初次登录更新在线用户
        case '4':
            var $users = data.users;
            var $append = '';
            for (var i = 0; i < $users.length; i++) {
                $append += '<li data-userName="' + $users[i] + '">';
                $append += $users[i];
                $append += '</li>';
            }
            $(".user_list ul").append($append);
            break;
            //有新用户登录 添加用户列表
        case '5':
            var $append = '';
            $append += '<li data-userName="' + data.user + '">';
            $append += data.user;
            $append += '</li>';
            $(".user_list ul").append($append);
            break;
            //有用户离线 减少用户列表
        case '6':
            var $li = $(".user_list ul li").each(function () {
                if ($(this).text() === data.user) {
                    $(this).remove();
                    return;
                }
            });
            break;
    }
    console.log('收到来自服务器的消息: ' + data.code);
};
websocket.onerror = function (evt, e) {
    console.log('发生了错误: ' + evt.data);
};
