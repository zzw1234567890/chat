<?php
//加载GatewayClient。关于GatewayClient参见本页面底部介绍
// require_once '../extend/GatewayClient/Gateway.php';
// GatewayClient 3.0.0版本开始要使用命名空间
namespace app\index\controller;
use GatewayClient\Gateway;
use app\model\User;
use think\Db;
// 设置GatewayWorker服务的Register服务ip和端口，请根据实际情况改成实际值(ip不能是0.0.0.0)
class Build{
    public function index(){
        // 假设用户已经登录，用户uid和群组id在session中
        // client_id与uid绑定
        $client_id = input("post.client_id");
        $user_id = session("user_id");
        if(!$client_id)
            return ['result'=>'0','code'=>'004'];
        if(!$user_id)
            return ['result'=>'0','code'=>'006'];
        Gateway::bindUid($client_id, $user_id);
        // 加入某个群组（可调用多次加入多个群组）
        Gateway::joinGroup($client_id, "chat");
        $user = User::get($user_id);
        $user->status = 2;
        $user->client_id = $client_id;
        $user->save();
        //用户发送上线通知
        Gateway::sendToGroup("chat",json_encode(['command'=>'101','user_id'=>$user_id]));
        return ['result'=>'1'];
    }
}
