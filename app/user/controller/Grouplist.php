<?php
namespace app\user\controller;
use app\model\Glist;

class Grouplist{
    //获取群聊列表
    public function get(){
        $user_id = session("user_id");
        if(!$user_id)
            return ['result'=>'0','code'=>'006'];
        $res = Glist::where(['user_id'=>$user_id,'status'=>1])
            ->field("id,name")
            ->select();
        return $res;
    }
    //搜索群聊列表      get     search
    public function search(){
        $user_id = session("user_id");
        if(!$user_id)
            return ['reuslt'=>'0','code'=>'006'];
        $search = input("get.search");
        if(!$search)
            return ['result'=>'0','code'=>'004'];
        $res = Glist::where("name LIKE '%$search%' AND status=1")
            ->field("id,name")
            ->select();
        return $res;
    }
    //添加群聊列表      post    name    member
    public function add(){
        $user_id = session("user_id");
        $name = input("post.name");
        $member = input("post.member");
        if(!$user_id)
            return ['result'=>'0','code'=>'006'];
        if(!$name || !$member)
            return ['result'=>'0','code'=>'004'];
        $glist = new Glist();
        $glist->user_id = $user_id;
        $glist->name = $name;
        $glist->member = $member;
        $glist->save();
        return ['result'=>'1','glist_id'=>$glist->id];
    }
}