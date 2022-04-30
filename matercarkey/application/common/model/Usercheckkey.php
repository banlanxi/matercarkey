<?php
namespace app\common\model;

use think\Db;
use think\Model;
use think\Session;

class Usercheckkey extends Model
{

    public function index()
    {
        //获得post传过来的数据
        $username = $_POST['username'];
        $starttime = $_POST['starttime'];
        $endtime = $_POST['endtime'];
        //$starttime转时间戳
        $starttime = strtotime($starttime);
        //$endtime转时间戳
        $endtime = strtotime($endtime);
        //连接数据库用来验证是否有相对应的车主
        include 'connect.php';
        //防止sq注入
        check_param($username);
        mysqli_select_db ($db,"www.jinhong.com");
        mysqli_query($db,"set names utf8");
        if(!$db){
            die('数据库连接失败');
        }
        //查询这个车主在这段时间内的票
        $sql = "SELECT km,`key`,jointime FROM `www.jinhong.com`.`fa_car_key` WHERE carnumber = '$username' AND `jointime` BETWEEN '$starttime' AND '$endtime'" ;
        $rst=mysqli_query($db,$sql);
        //查询结果
        $result = array();
        while($row = mysqli_fetch_assoc($rst)){
            $result[] = $row;
        }
        //从model发送数据$result到controller
        //返回结果
        return $result;
        }
    }



