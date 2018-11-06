<?php
namespace app\user\controller;
use app\model\User;
use think\Db;
use GatewayClient\Gateway;

class Online{
	//获取在线用户
    public function get(){
		$user_id = session("user_id");
    	if(!$user_id)
			return ['result'=>'0','code'=>'006'];
    	$res = User::where("id<>1 AND id<>$user_id AND status=2")
			->field("id,account,name,sex,describe,status")
    		->select();
    	return $res;
	}
	//搜索在线用户		get 	search
    public function search(){
		$user_id = session("user_id");
		$user_id = 5;
    	if(!$user_id)
    		return ['result'=>'0','code'=>'006'];
		$search = input("get.search");
		if(!$search)
			return ['result'=>'0','code'=>'004'];
		$res = User::where("(name LIKE '%$search%' OR account LIKE '%$search%') AND id<>$user_id")
			->field("id,account,name,status")
			->Order("status DESC")
			->select();
		return $res;
    }
}