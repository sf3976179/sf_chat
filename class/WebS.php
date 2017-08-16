<?php

namespace buff;

/**
 * 继承swoole_websocket_server 实现一些逻辑方法
 *
 * @author buff
 */
class WebS extends \Swoole\Websocket\Server
{
//发送用户列表
    const
            SENDUSERSLISTS = 1;
//普通信息
    const
            COMMONMESSAGE = 2;
//警告用户名已存在信息
    const
            WARMINGSAMENAME = 3;
//警告错误的token
    const
            WRONGTOKEN = 4;
//服务器异常 通常为插入数据失败等
    const
            SERVERERROR = 500;
    //添加用户列表
    const
            ADDUSER = 5;
    //减少用户列表
    const
            DELUSER = 8;
    //通知用户注册成功
    const
            REGISTERSUCCESS = 6;
    //通知用户当前尚未登录
    const
            UNLOGIN = 7;
    //发送信息给个人
    const
            SENDTOPERSON = 10;
    //问答
    const
            QUESTION = 11;
    /**
     *  检查用户名是否已存在
     * @param \Redis $redis redis连接对象
     * @param string $user_name 用户注册名
     * @return bool 用户名是否已存在 true:是 false:否
     */
    public
            function checkRegisterName(\Redis $redis, string $user_name, string $reload, string $user_ip): bool {
        foreach ($this->connections as $fd) {
            $result = $redis->hGetAll($fd);
            if ($result["user_name"] === $user_name) {
                //此处只验证了是否为同一个ip,如果想更精确判断可以在写入一个sessionid到redis中,
                if ($reload === "yes" && $result['ip'] === $user_ip) {
                    $this->close($fd);
                    return false;
                }
                else {
                    return true;
                }
            }
        }
        return false;

    }

    /**
     * 发送消息到一个群组中
     * @param int $frameFd  socketid
     * @param \Redis $redis redis连接对象
     * @param string $user_name 发送消息的用户名
     * @param int $type 发送消息的类型
     * @param string $mes 要发送的信息
     * @param string $group 要发送到的组
     */
    public
            function sendToGroup(int $frameFd, \Redis $redis, string $user_name, int $type, string $mes = "", string $group = "public") {
        if ($type === self::COMMONMESSAGE) {
            $mes = htmlspecialchars($mes, ENT_NOQUOTES);
            //将换行转换为br
            $mes = nl2br($mes);
            $mes = str_replace(["\n", "\""], ["", "\\\""], $mes);
        }
        foreach ($this->connections as $fd) {
            $result = $redis->hGetAll($fd);
            if (self::DELUSER === $type && $frameFd === $fd || $result["group"] !== $group) {
                continue;
            }
            switch ($type) {
                //有新用户连接通知客户增加用户
                case self::ADDUSER:
                    $this->push($fd, "{\"code\":\"5\",\"user\":\"{$user_name}\"}");
                    break;
                //普通消息
                case self::COMMONMESSAGE:
                    $this->push($fd, "{\"code\":\"2\",\"mes\":\"{$mes}\",\"user_name\":\"{$user_name}\"}");
                    break;
                //有用户退出时删除用户列表
                case self::DELUSER:
                    $this->push($fd, "{\"code\":\"6\",\"user\":\"{$user_name}\"}");
                    break;
                //有用户退出时删除用户列表
                case self::QUESTION:
                    $this->push($fd, "{\"code\":\"2\",\"mes\":\"{$mes}\",\"user_name\":\"{$user_name}\"}");
                    break;
            }
        }

    }

    /**
     *  发消息给个人
     * @param int $fd scoket连接号
     * @param string $mes 发送的信息
     * @param int $type 发送消息的类型
     * @param string $sendTo 发送给谁
     * @param string $user_name 发送信息的人的名称
     * @param \Redis $redis redis连接对象
     */
    public
            function sendToPerson(int $fd, string $mes, int $type, string $sendTo = "", string $user_name = "", \Redis $redis = null) {
        $mes = htmlspecialchars($mes, ENT_NOQUOTES);
        $mes = str_replace("Ø", ":", $mes);
        switch ($type) {
            case self::SENDUSERSLISTS:
                //通知用户 当前在线用户列表
                $this->push($fd, "{\"code\":\"4\",\"users\":[{$mes}]}");
                break;
            //通知用户 你注册成功了
            case self::REGISTERSUCCESS:
                $this->push($fd, "{\"code\":\"3\",\"user_name\":\"{$mes}\"}");
                break;
            //通知用户 注册名已存在
            case self::WARMINGSAMENAME:
                $this->push($fd, "{\"code\":\"-1\",\"mes\":\"{$mes}\"}");
                break;
            //通知用户 服务器错误
            case self::SERVERERROR:
                $this->push($fd, "{\"code\":\"-1\",\"mes\":\"{$mes}\"}");
                break;
            //通知用户当前未登录
            case self::UNLOGIN:
                $this->push($fd, "{\"code\":\"-1\",\"mes\":\"{$mes}\"}");
                break;
            //通知用户令牌错误
            case self::WRONGTOKEN:
                $this->push($fd, "{\"code\":\"-1\",\"mes\":\"{$mes}\"}");
                break;
            //发私信
            case self::SENDTOPERSON:
                $mes = htmlspecialchars_decode($mes, ENT_NOQUOTES);
                foreach ($this->connections as $tempfd) {
                    $user_info = $redis->hGetAll($tempfd);
                    if ($user_info['user_name'] === $sendTo) {
                        $mes = str_replace(["\n", "\""], ['\n', "\\\""], $mes);
                        $this->push($tempfd, "{\"code\":\"1\",\"mes\":\"{$mes}\",\"form\":\"{$user_name}\"}");
                        break;
                    }
                }
                break;
        }

    }

    /**
     * 获取在线用户列表
     * @param \Redis $redis redis连接对象
     * @param string $group
     * @return string
     */
    public
            function getOnlineUsersList(\Redis $redis, string $group = "public"): string {
        $userlist = "";
        $is_have_user = false;
        foreach ($this->connections as $fd) {
            $result = $redis->hGetAll($fd);
            if ($result["user_name"] == "" || $result["group"] !== $group) {
                continue;
            }
            $is_have_user = true;
            $userlist .= ('"' . $result["user_name"] . '",');
        }
        if (!$is_have_user) {
            return "";
        }
        $userlist = substr($userlist, 0, strlen($userlist) - 1);
        return $userlist;

    }

    /**
     * 检查token是否正确
     * @param string $frameData 用户发送的信息内容
     * @return bool token是否正确
     */
    public
            function checkToken(string $frameData): bool {
        $userData = explode(':', $frameData);
        if (count($userData) !== 4) {
            return self::COMMONMESSAGE;
        }
        $NowDatep = [date("Y-m-d H:i", time()), date("Y-m-d H:i", time() - 60)];
        $timeStamp = [strtotime($NowDatep[0]), strtotime($NowDatep[1])];
        $hash = [hash('sha256', $timeStamp[0] . 'daimin' . $userData[3]),
            hash('sha256', $timeStamp[1] . 'daimin' . $userData[3])
        ];
        if ($userData[1] == $hash[0] || $userData[1] == $hash[1]) {
            return true;
        }
        else {
            return false;
        }

    }

    public
            function opening(\Redis $redis, $req) {
        $redis->incr('users_num');
        $group = $req->get['group'] ?? 'public';
        $redis->hMset($req->fd, ["token" => "", "user_name" => "", "group" => $group, "ip" => $req->server['remote_addr']]);
        echo "新客户端连接: " . $req->fd . "时间:" . date("Y-n-j H:i:s") . "\n";
        $userlist = $this->getOnlineUsersList($redis, $req->get['group']);
        $this->sendToPerson($req->fd, $userlist, self::SENDUSERSLISTS);

    }

    /**
     * 判断用户发送的信息 是什么行为
     * @param \Redis $redis redis 连接对象
     * @param type $frame swoole 存储的用户信息
     * @param array $userInfo 当前redis中用户存储的信息
     * @return type int
     */
    public
            function judgeMesEventType(\Redis $redis, $frame, array $userInfo): int {
        $preSixChar = mb_substr($frame->data, 0, 6);
        //如果发送消息小于6个字符那么就是普通消息
        if (mb_strlen($preSixChar) < 6) {
            return self::COMMONMESSAGE;
        }
        switch ($preSixChar) {
            case "tokenR":
                //如果当前用户未注册
                if ($userInfo['token'] === "") {
                    //如果验证token正确
                    if ($this->checkToken($frame->data)) {
                        $userData = explode(':', $frame->data);
                        //验证用户名是否存在
                        if ($this->checkRegisterName($redis, $userData[3], $userData[4], $userInfo['ip'])) {
                            //警告信息,用户名已存在
                            return self::WARMINGSAMENAME;
                        }
                        if (count($userData) === 7) {
                            $res = $redis->hMset($frame->fd, ["token" => $userData[1], "user_name" => $userData[3], "group" => $userData[6], "ip" => $userInfo['ip']]);
                        }
                        else {
                            $res = $redis->hMset($frame->fd, ["token" => $userData[1], "user_name" => $userData[3]]);
                        }
                        if (!$res) {
                            echo "更新用户信息失败 user_name=={$userData[3]} token = {$userData[1]}\n";
                            //如果插入用户信息失败那么返回服务器错误
                            return self::SERVERERROR;
                        }
                        echo "新注册用户 {$userData[3]}({$frame->fd})\n";
                        //返回注册成功
                        return self::REGISTERSUCCESS;
                    }
                    //如果token不正确 返回错误token
                    else {
                        return self::WRONGTOKEN;
                    }
                }
                //如果只是发送包含token这个字符串的语句 群发
                else {
                    return self::COMMONMESSAGE;
                }
                break;

            case "sendTo":
                if ($userInfo['token'] === "") {
                    return self::UNLOGIN;
                }
                $userData = explode(':', $frame->data);
                if (count($userData) !== 6) {
                    return self::COMMONMESSAGE;
                }
                return self::SENDTOPERSON;

            case "quesTo":
                if ($userInfo['token'] === "") {
                    return self::UNLOGIN;
                }
                $userData = explode(':', $frame->data);
                return self::QUESTION;
            default:
                return self::COMMONMESSAGE;
        }

    }

    public
            function messaging(\Redis $redis, $frame) {
        $result = $redis->hGetAll($frame->fd);
        $user_name = $result['user_name'];
        $user_group = $result['group'];
        echo "收到来自 {$user_group}组中 {$user_name} ({$frame->fd})的消息: " . $frame->data . "\n";
        //当用户是第一个注册时候(发送的语句前面5个字是token)
        $eventType = $this->judgeMesEventType($redis, $frame, $result);
        switch ($eventType) {
            //普通消息
            case self::COMMONMESSAGE:
                if ($result['token'] !== "") {
                    $this->sendToGroup($frame->fd, $redis, $user_name, self::COMMONMESSAGE, $frame->data, $user_group);
                }
                else {
                    $this->sendToPerson($frame->fd, "请先登录!", self::UNLOGIN);
                }
                break;
            //注册成功消息
            case self::REGISTERSUCCESS:
                $result = $redis->hGetAll($frame->fd);
                $user_name = $result['user_name'];
                $user_group = $result['group'];
                $this->sendToGroup($frame->fd, $redis, $user_name, self::ADDUSER, "", $user_group);
                $this->sendToPerson($frame->fd, $user_name, self::REGISTERSUCCESS);
                break;
            //服务器错误
            case self::SERVERERROR:
                $this->sendToPerson($frame->fd, "服务器异常 请联系管理员", self::SERVERERROR);
                break;
            //用户名已存在
            case self::WARMINGSAMENAME:
                $this->sendToPerson($frame->fd, "用户名已存在", self::WARMINGSAMENAME);
                break;
            //错误的token
            case self::WRONGTOKEN:
                $this->sendToPerson($frame->fd, "令牌错误!", self::WRONGTOKEN);
                break;
            //用户当前未登录
            case self::UNLOGIN:
                $this->sendToPerson($frame->fd, "当前未登录!", self::UNLOGIN);
                break;
            //发送私信给个人
            case self::SENDTOPERSON:
                $userData = explode(':', $frame->data);
                $sendTo = $userData[1];
                $mes = $userData[3];
                $user_name = $userData[5];
                $this->sendToPerson($frame->fd, $mes, self::SENDTOPERSON, $sendTo, $user_name, $redis);
                break;
            //问答专区
            case self::QUESTION:
                $userData = explode(':', $frame->data);
                $ques = $userData[1];
                $user_name = $userData[3];
                $result = $redis->hGetAll($frame->fd);
                $user_group = $result['group'];
                $data = [$frame->fd, $ques, $user_name, $user_group];
                $this->sendToGroup($frame->fd, $redis, $user_name, self::QUESTION, $ques, $user_group);
                $this->task($data);
                break;
        }

    }

    public
            function closing(\Redis $redis, $fd) {
        $redis->decr('users_num');
        $userData = $redis->hGetAll($fd);    
	$user_name = $userData['user_name'];
        $user_group = $userData['group'];
        $res = $redis->del($fd);
        if ($res === false) {
            echo "删除用户{$user_name}({$fd})失败\n";
        }
        if ($user_name) {
            $this->sendToGroup($fd, $redis, $user_name, self::DELUSER, "", $user_group);
        }
	echo '断开连接数据：';
	var_dump($userData);
	$redis->delete($fd);
	echo "fd:".$fd;
        echo "客户端{$user_name}({$fd})已断开连接\n";

    }

}
