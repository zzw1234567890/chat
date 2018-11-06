<?php
namespace app\admin\controller;
use phpmailer\Mail;

class User{
    //获取邮箱验证码
    public function sendCode(){
        $toMailer = input("email");
        if(!in_array($toMailer,config("allowEmail")))
            return ["result"=>"0","code"=>"002"];
        $mail = new Mail();
        $mail->toMailer = $toMailer;
        $mail->subject = "Amazing验证码";
        $mail->body = rand(100000,999999);
        if(!$mail->send())
            return ["result"=>"0","code"=>"001"];
        session(md5($toMailer),md5($mail->body));
        return ["result"=>"1"];
    }
    //管理员登陆接口
    public function login(){
        $email = md5(input("email"));
        if(session($email) != md5(input("code")))
            return ["result"=>"0","code"=>"003"];
        session("login","1");
        session($email,null);
        return ["result"=>"1"];
    }
    //退出接口
    public function quit(){
        session(null);
    }
}