<?php
namespace app\message\controller;
use think\Db;
use app\model\Pmessage;
use GatewayClient\Gateway;

class Pchat{
    //获取私聊信息  get     goal_id
    public function getMsg(){
        $user_id = session("user_id");
        $goal_id = input("get.goal_id");
        if($goal_id == 1)
            return $this->getRequest();
        $msg_id = input("get.msg_id",0);
        $msg_id = $msg_id ? 0 : session("msg_id");
        if(!$user_id)
            return ['result'=>'0','code'=>'006'];
        if(!$goal_id)
            return ['result'=>'0','code'=>'004'];
        Db::startTrans();
        try{
            $sql = "UPDATE pmessage 
                    SET status=2
                    WHERE ((user_id=$user_id AND goal_id=$goal_id) OR (goal_id=$user_id AND user_id=$goal_id)) AND status=1";
            Db::execute($sql);
            $sql = "SELECT count(1) AS count FROM pmessage WHERE (goal_id=$user_id AND user_id=$goal_id) AND status=1";
            $count = Db::query($sql)[0];
            $count = $count['count'] > 10 ? $count['count'] : 10;
            $sql = "SELECT (IF(user_id=$user_id,1,0)) AS status,create_time,content,id,0 AS `type`
                    FROM pmessage 
                    WHERE ((user_id=$user_id AND goal_id=$goal_id) OR (goal_id=$user_id AND user_id=$goal_id)) AND ($msg_id=0 OR id<$msg_id)
                    ORDER BY create_time DESC
                    LIMIT 0,$count";
            $res = Db::query($sql);
            $user = Db::table('user')->where('id',$goal_id)->field('name')->find();
            Db::commit();
        }catch (\Exception $e) {
            Db::rollback();
            return ['result'=>'0','code'=>'007'];
        }
        if(!count($res)){
            session('msg_id',0);
        }else session('msg_id',$res[count($res) - 1]['id']);
        return ['friend_name'=>$user['name'],'msg'=>$res];
    }
    //重置消息状态  post    goal_id
    public function setMsgStatus(){
        $user_id = session("user_id");
        $goal_id = input("post.goal_id");
        Db::table('pmessage')->where("goal_id=$user_id AND user_id=$goal_id AND status=1")->update(['status'=>2]);
        return ['result'=>1];
    }
    //发送私聊信息  post    goal_id     content
    public function send(){
        $user_id = session("user_id");
        $goal_id = input("post.goal_id");
        $content = input("post.content");
        if(!$user_id)
            return ['result'=>'0','code'=>'006'];
        if(!$goal_id || !$content)
            return ['result'=>'0','code'=>'004'];
        $pmsg = new Pmessage();
        $pmsg->user_id = $user_id;
        $pmsg->goal_id = $goal_id;
        $pmsg->content = $content;
        $pmsg->save();
        $name = Db::table('user')->where(['id'=>$user_id])->field('name')->find()['name'];
        $res = ['command'=>'103','status'=>1,'goal_id'=>$goal_id,'content'=>$content,'last_time'=>date("H:i"),'name'=>$name];
        GateWay::sendToUid($user_id,json_encode($res));
        if(Gateway::isUidOnline($goal_id)){
            $res['status'] = 0;
            $res['goal_id'] = $user_id;
            Gateway::sendToUid($goal_id,json_encode($res));
        }
        return ['result'=>'1'];
    }
    //获取请求(区分普通消息)
    public function getRequest(){
        $user_id = session("user_id");
        $msg_id = input("get.msg_id",0);
        $msg_id = $msg_id ? 0 : session("msg_id");
        if(!$user_id)
            return ['result'=>'0','code'=>'006'];
        $sql = "UPDATE request 
                SET status=2
                WHERE goal_id=$user_id AND status=1";
        Db::execute($sql);
        $sql = "SELECT a.id AS msg_id,a.content,a.type,0 AS status,a.title,b.name,b.account,b.id AS friend_id 
                FROM request a,user b 
                WHERE a.goal_id=$user_id AND a.from_id=b.id";
        $res = Db::query($sql);
        $user = Db::table('user')->where('id',1)->field('name')->find();
        if(!count($res))
            session('msg_id',0);
        else session('msg_id',$res[count($res) - 1]['msg_id']);
        return ['friend_name'=>$user['name'],'msg'=>$res];
    }
}