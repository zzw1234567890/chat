<?php
namespace app\index\controller;
use GatewayClient\Gateway;

class Index{
    public function index(){
        if(session("user_id"))
            return ['result'=>'1'];
        return ['result'=>'0'];
    }
    public function test(){
        return Gateway::getAllUidList();
    }
}