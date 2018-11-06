<?php
namespace app\admin\controller;
use app\model\Report;

class Index
{
    //检查登陆状态
    public function init(){
        if(session("login"))
            return true;
        return false;
    }
    //获取报名表信息
    public function getReport()
    {
        if(!$this->init())
            return ["result"=>"0","code"=>"006"];
        $result = Report::where("status",1)
                ->field("name,number,sex,qq,phone,grade,major,speciality,describe,direction,plan")
                ->select();
        if(!$result)
            return "NULL";
        for($i = 0;$i < count($result);$i ++){
            $result[$i] = $result[$i]->toArray();
        }
        return $result;
    }
}

?>