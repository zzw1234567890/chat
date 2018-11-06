<?php
namespace app\user\controller;
use app\model\User;
use \think\Db;
use \think\Session;

class Init{
    //登录      post    account     password
    public function login(){
		$account = input("post.account");
		$password = input("post.password");
		if(!$account || !$password)
			return ['result'=>'0','code'=>'004'];
		$sql = "(account='$account' AND status=1) AND (password='$password' OR encrypt='$password')";
        $res = User::where($sql)
			->field("id")
			->find();
		if($res == NULL)
			return ['result'=>'0','code'=>'001'];
		session("user_id",$res['id']);
		return ['result'=>'1','password'=>md5($password)];
    }
    //注册      post    account     password	name
    public function regist(){
		$account = input("post.account");
		$password = input("post.password");
		$name = input("post.name");
		if(!$account || !$password || !$name)
			return ['result'=>'0','code'=>'004'];
		$res = User::where(['account'=>$account])
			->field("id")
			->find();
		if($res)
			return ['result'=>'0','code'=>'002'];
		// 启动事务
		Db::startTrans();
		try{
			Db::table('user')->insert(['account'=>$account,'password'=>$password,'encrypt'=>md5($password),'name'=>$name,'sex'=>'未知','describe'=>'这家伙很懒，什么都没说~~']);
			$user = Db::table('user')->where('account',$account)->field('id')->find();
			Db::table('flist')->insert(['user_id'=>$user['id'],'name'=>'我的好友','member'=>'0,']);
			Db::commit();    
		} catch (\Exception $e) {
			Db::rollback();
			return ['result'=>'0','code'=>'007'];
		}
		session("user_id",$user['id']);
		return ['result'=>'1'];
	}
	//退出
	public function quite(){
		Session::delete("user_id");
		return ['result'=>'1'];
	}
}