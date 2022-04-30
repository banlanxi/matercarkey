<?php
namespace app\common\model;

use think\Db;
use think\Model;
use think\Session;


class Userpass extends Model
{

    public function index()
    {
        //获得传过来的数据

        $username = $_POST['username'];
        //连接数据库用来验证是否有相对应的车主
        include 'connect.php';
        //防止sq注入
        check_param($username);
        mysqli_select_db ($db,"www.jinhong.com");
        mysqli_query($db,"set names utf8");
        if(!$db){
            die('数据库连接失败');
        }
        $sql="select * from `www.jinhong.com`.`fa_user` where username = '$username' ";
        $rst=mysqli_query($db,$sql);
        //var_dump($rst);
        //print_r($rst);
        //转换接收到的信息代入相对应的数据名称
        while($rs=mysqli_fetch_assoc($rst))
        {
        $rows=$rs['username'];
        $nickname=$rs['nickname'];
        }
        //登陆信息的确定
        if($rows!== null){
            echo "<script> alert(' 请再次确定当前登录的账号是：$rows  车主姓名是：$nickname ');</script>"; 
            // 赋值（当前作用域）
            Session::set('name',$nickname);
            Session::set('username',$rows);
            // $_SESSION['name']=$nickname;
            // $_SESSION['username']=$rows;
            header("refresh:1;memberindex");
        }else if($rows== null){
            echo "请再次确定，当前车辆信息车牌不存在!";
            header("refresh:5;memberindex");//输入错误跳转登录页面
            die;
            
        }
    }

}

