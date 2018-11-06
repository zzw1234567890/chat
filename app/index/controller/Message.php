<?php
namespace app\index\controller;
// use \GatewayWorker\Lib\Gateway;
use GatewayClient\Gateway;
use think\cache\driver\Redis;

class Message{
	public function index(){
		// $redis = new Redis();
		// $message = $redis->get("message");
        // $message = json_encode($message,JSON_UNESCAPED_UNICODE);
		// Gateway::sendToAll("$client_id said $message\r\n");
		// 向任意uid的网站页面发送数据
		$message = '{"type":"ok","client_id":"7f0000010b5700000005"}';
		// $message = ['result'=>1];
        Gateway::sendToAll($message);
		// return $uid;
		// 向任意群组的网站页面发送数据
		// Gateway::sendToGroup($group, $message);
	}
}