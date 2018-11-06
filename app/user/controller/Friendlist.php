<?php
namespace app\user\controller;
use think\Db;
use app\model\Flist;
use app\model\Request;
use GatewayClient\Gateway;

class Friendlist{
    //获取好友列表
    public function get(){
        $user_id = session("user_id");
        //$user_id = 5;
        if(!$user_id)
            return ['result'=>'0','code'=>'006'];
        $member = Db::table('flist')->where("user_id",$user_id)->field("member")->find()['member'].'0';
        $sql = "SELECT id,name,status,
                    (SELECT content FROM pmessage WHERE (user_id=$user_id AND goal_id=a.id) OR (user_id=a.id AND goal_id=$user_id) 
                        ORDER BY create_time desc LIMIT 0,1) AS last_msg,
                    (SELECT create_time FROM pmessage WHERE (user_id=$user_id AND goal_id=a.id) OR (user_id=a.id AND goal_id=$user_id) 
                        ORDER BY create_time desc LIMIT 0,1) AS last_time,
                    (SELECT count(id) FROM pmessage WHERE status=1 AND (user_id=a.id AND goal_id=$user_id))
                        AS `count`
        		FROM `user` a
        		WHERE a.id 
                IN ($member)";
        $flist = Db::query($sql);
        $sql = "SELECT id,name,status,
                    (SELECT IFNULL ((SELECT title FROM request WHERE goal_id=$user_id ORDER BY create_time desc LIMIT 0,1),'暂无通知')) AS last_msg, 
                    (SELECT IFNULL ((SELECT create_time FROM request WHERE goal_id=$user_id ORDER BY create_time desc LIMIT 0,1),' ')) AS last_time,
                    (SELECT count(id) FROM request WHERE goal_id=$user_id AND status=1) AS `count` 
                FROM user 
                WHERE id=1";
        array_unshift($flist,Db::query($sql)[0]);
        return $flist;
    }
    //搜索好友  get   search
    public function search(){
        $user_id = session("user_id");
        if(!$user_id)
            return ['result'=>'0','code'=>'006'];
        $search = input("get.search");
        if(!$search)
            return ['result'=>'0','code'=>'004'];
        $member = Db::table('flist')->where(['user_id'=>$user_id,'status'=>1])->field('member')->find()['member'].'0';
        $sql = "SELECT id,name,account,sex,status
                FROM user 
                WHERE (name like '%$search%' or account like '%$search%') AND id IN ($member)";
        $res = Db::query($sql);
        return $res;
    }
    //发送添加好友的信息    post    friend_id
    public function add(){
        $user_id = session("user_id");
        // $user_id = 5;
        $friend_id = input("post.friend_id");
        if(!$user_id)
            return ['result'=>'0','code'=>'006'];
        if(!$friend_id)
            return ['result'=>'0','code'=>'004'];
        //判断好友数量是否达到上限
        $num = 200;
        $sql = "SELECT sum(count) AS count,GROUP_CONCAT(member,'') AS member
                FROM flist 
                WHERE user_id=$user_id OR user_id=$friend_id
                GROUP BY user_id";
        $flist = Db::query($sql);
        if($flist[0]['count'] >= $num || $flist[1]['count'] >= $num)
            return ['result'=>'0','code'=>'003'];
        $flist = explode(',',$flist[0]['member']);
        if(in_array($friend_id,$flist))
            return ['result'=>'0','code'=>'005'];
        $res = Request::where(['from_id'=>$user_id,'goal_id'=>$friend_id,'type'=>1])->field("id")->find();
        if($res)
            return ['result'=>'0','code'=>'105'];
        $request = new Request();
        $request->from_id = $user_id;
        $request->goal_id = $friend_id;
        $request->title = config("friendTitle");
        $request->content = config("addContent");
        $request->type = 1;
        $request->save();
        $user = Db::table('user')->where("id",$user_id)->field("id,name,account")->find();
        if(Gateway::isUidOnline($request->goal_id)){
            $res = ['command'=>'106','goal_id'=>'1','content'=>$request->content,'type'=>'1','status'=>'0','last_time'=>date("H:i"),
                    'title'=>$request->title,'name'=>$user['name'],'account'=>$user['account'],'friend_id'=>$user['id']];
            Gateway::sendToUid($request->goal_id,json_encode($res));
        }
        return ['result'=>'1'];
    }
    //同意添加好友   post   friend_id      type     name
    public function agree(){
        $user_id = session("user_id");
        // $user_id = 5;
        $friend_id = input("post.friend_id",4);
        $name = input("post.name","我的好友");
        if(!$user_id)
            return ['result'=>'0','code'=>'006'];
        if(!$friend_id || !$name)
            return ['result'=>'0','code'=>'004'];
        Db::startTrans();
        try{
            Db::table('request')->where(['from_id'=>$friend_id,'goal_id'=>$user_id,'type'=>1])->update(['type'=>2]);
            $flist = Db::table('flist')->where(['user_id'=>$user_id,'name'=>$name,'status'=>1])->field("id,member,count")->find();
            $flist['member'] = $flist['member'].$friend_id.',';
            Db::table('flist')->where("id",$flist['id'])->update(['member'=>$flist['member'],'count'=>$flist['count'] + 1]);
            $flist = Db::table('flist')->where(['user_id'=>$friend_id,'name'=>$name,'status'=>1])->field("id,member,count")->find();
            $flist['member'] = $flist['member'].$user_id.',';
            Db::table('flist')->where("id",$flist['id'])->update(['member'=>$flist['member'],'count'=>$flist['count'] + 1]);
            Db::table('pmessage')->insert(['user_id'=>$friend_id,'goal_id'=>$user_id,'content'=>config('hello')]);
            Db::commit();
        }catch (\Exception $e) {
            Db::rollback();
			return ['result'=>'0','code'=>'007'];
        }
        //向双方发送通知
        $sql = "SELECT id,name,status,
                    (SELECT content FROM pmessage WHERE user_id=$friend_id AND goal_id=$user_id
                        ORDER BY create_time desc LIMIT 0,1) AS last_msg,
                    (SELECT create_time FROM pmessage WHERE user_id=$friend_id AND goal_id=$user_id
                        ORDER BY create_time desc LIMIT 0,1) AS last_time,
                    (SELECT count(id) FROM pmessage WHERE status=1 AND user_id=$friend_id AND goal_id=$user_id)
                        AS `count`
                FROM `user`
                WHERE id=$friend_id";
        $res = Db::query($sql)[0];
        $res['command'] = '107';
        Gateway::sendToUid($user_id,json_encode($res));
        if(Gateway::isUidOnline($friend_id)){
            $sql = "SELECT id,name,status
                    FROM `user`
                    WHERE id=$user_id";
            $temp = Db::query($sql)[0];
            $res['id'] = $temp['id'];
            $res['name'] = $temp['name'];
            $res['status'] = $temp['status'];
            Gateway::sendToUid($friend_id,json_encode($res));
        }
        return ['result'=>'1'];
    }
    //拒绝 post     friend_id
    public function refuse(){
        $user_id = session("user_id");
        $friend_id = input("post.friend_id");
        $name = input("post.name","我的好友");
        if(!$user_id)
            return ['result'=>'0','code'=>'006'];
        if(!$friend_id || !$name)
            return ['result'=>'0','code'=>'004'];
        Db::startTrans();
        try{
            Db::table('request')->where(['from_id'=>$friend_id,'goal_id'=>$user_id,'type'=>'1'])->update(['type'=>3]);
            Db::table('request')->insert(['from_id'=>$user_id,'goal_id'=>$friend_id,'type'=>'3','title'=>config('friendTitle'),'content'=>config('refuseContent')]);
            Db::commit();
        }catch (\Exception $e){
            Db::rollback();
			return ['result'=>'0','code'=>'007'];
        }
        $user = Db::table('user')->where('id',$user_id)->field("name,account")->find();
        $sql = "SELECT a.id,a.name AS from_user,a.status,b.*,
                    (SELECT count(id) FROM request WHERE status=1 AND from_id=$user_id AND goal_id=$friend_id AND type=3) AS `count`
                FROM user a,
                    (SELECT `type`,title,content,create_time AS last_time FROM request 
                    WHERE from_id=$user_id AND goal_id=$friend_id AND type=3 ORDER BY create_time DESC LIMIT 0,1) b
                WHERE id=1";
        $res = Db::query($sql)[0];
        if(Gateway::isUidOnline($friend_id)){
            $res['command'] = '108';
            $res['name'] = $user['name'];
            $res['account'] = $user['account'];
            Gateway::sendToUid($friend_id,json_encode($res));
        }
        return ['result'=>1];
    }
    //删除好友  post    friend_id   name
    public function del(){
        $user_id = session("user_id");
        $friend_id = input("post.friend_id");
        $name = input("post.name","我的好友");
        if(!$user_id)
            return ['result'=>'0','code'=>'006'];
        if(!$friend_id || !$name)
            return ['result'=>'0','code'=>'004'];
        //在自己的列表中删除
        $t = ','.$friend_id.',';
        $sql = "UPDATE flist SET member=REPLACE(member,'$t',','),count=count-1 WHERE user_id=$user_id";
        Db::execute($sql);
        // //在对方的列表中删除
        $t = ','.$user_id.',';
        $sql = "UPDATE flist SET member=REPLACE(member,'$t',','),count=count-1 WHERE user_id=$friend_id";
        Db::execute($sql);
        return ['result'=>'1'];
    }
}