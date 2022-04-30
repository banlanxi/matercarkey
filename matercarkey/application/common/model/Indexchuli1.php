<?php

namespace app\common\model;

use think\Db;
use think\Model;
use think\Session;

class Indexchuli1 extends Model
{  //times是总数，reaslpasskey是票据，nowtime是当前时间，username是车牌
    public function checkenterkey($times,$realpasskey,$nowtime,$username){
    include 'connect.php';
    mysqli_select_db ($db,"www.jinhong.com");
    mysqli_query($db,"set names utf8");
    include 'redis.php';
    global $realtimess;
    for($time=0;$time<$times;$time++){
    
    //获得当前redis中所有的set名词
    $redisname = $redis->keys('*');
    //统计$redisname中的数据个数
    $redisnum = count($redisname);
    global $redis_value;
    if($redisnum==0){
        $redis_value=0;
        echo $redis_value;
    }
    else{
        for($i=0;$i<$redisnum;$i++){
        //$realpasskey是否在redis中
        // echo $redisname[$i];
        // var_dump($realpasskey);
        //echo $redisname[$i];

        $redis_value = $redis->sismember($redisname[$i],$realpasskey[$time]);
        if($redis_value==1){
            $redis_name=$redisname[$i];
        }
        else{
            $redis_value=0;
        }
        }

        }
        // echo $redis_value;
        // echo "<br>";
        
    
    // //获得当前redis中所有的set名词
    // $redisname = $redis->keys('*');
    // //统计$redisname中的数据个数
    // $redisnum = count($redisname);
    // $redis_value = 0;
    // //循环遍历$redisname中的数据
    // for($i=0;$i<$redisnum;$i++){
    // //redisname中有数据执行
    // if($redisname!=null){
    //  //查询value在哪个set中
    // $redis_value = $redis->SISMEMBER($redisname[$i],$realpasskey[$times]);
    // //存储$resdisname在$redisname中的位置
    // $redis_name = $redisname[$i];
    // }
    // //redisname中没有数据执行
    // else{
    // $redis_value = 0;
    // }
    // }

    //如果在redis中，则查询是否是当前车主
    if($redis_value==1){
            echo "<script> alert('请检查此编号$realpasskey[$time]已使用,车牌是$redis_name'); </script>";
            continue;
    }
    //如果没有继续完成查询
    if($redis_value==0){
    $sql = "select valid from `www.jinhong.com`.`fa_car_key` where keyword ='$realpasskey[$time]';";
    $rst=mysqli_query($db,$sql);
    while($arr=mysqli_fetch_assoc($rst))
	{   
		$valid[$time]=$arr['valid'];
		}
    //如果有效就继续操作 更改数据库 并存入redis
    if($valid[$time]=='1'){
        //echo $time;
        echo "$realpasskey[$time]<br>";
        $sql = "UPDATE `www.jinhong.com`.`fa_car_key` SET `jointime`=$nowtime ,`carnumber`='$username',`valid`='2' WHERE `valid`='1' AND `keyword`='$realpasskey[$time]';";
        $rst=mysqli_query($db,$sql);
        global $realtimess;
        $realtimess=$realtimess+1;
        $redis->SADD("$username", "$realpasskey[$time]");
        //设计过期时间
        $overtime=86400-date('H', $nowtime)*3600- date('i', $nowtime)*60-date('s')+$nowtime;
        $redis->expireAt("$username", $overtime);
        //添加到redis中
        $sql1 = "select `Id`,`key`,`km` from `www.jinhong.com`.`fa_car_key` where keyword ='$realpasskey[$time]';";
        $rst1=mysqli_query($db,$sql1);
        while($arr=mysqli_fetch_assoc($rst1))
        {
            $zId[]=$arr['Id'];
            $kmz[]=$arr['km'];
            $keyz[]=$arr['key'];       
        }   
        //更新redis
        $redis->SADD($username,$kmz[0].$keyz[0]);
        //设计过期时间
        $overtime=86400-date('H', $nowtime)*3600- date('i', $nowtime)*60-date('s')+$nowtime;
        $redis->expireAt("$username", $overtime);
    }
    //已经被兑现,写出兑换的车友是谁
    if($valid[$time]=='2'){
        //echo $time;
        $sql = "select `carnumber`,`key`,`km` from `www.jinhong.com`.`fa_car_key` where keyword ='$realpasskey[$time]' and valid = '$valid[0]';";
        $rst=mysqli_query($db,$sql);
        while($arr=mysqli_fetch_assoc($rst))
	    {   
        $km[0]=$arr['km'];
        $key[0]=$arr['key'];
		$carnumber[0]=$arr['carnumber'];
        if($username==$carnumber[0]){
            global $realyou;
            $realyou=$realyou+1;
            echo null;
        }
        if($username!=$carnumber[0]){
            //将查询到的车主还有序号上传到redis中
            $redis->SADD("$carnumber[0]", "$km[0].$key[0]");  
            //设计过期时间
            $overtime=86400-date('H', $nowtime)*3600- date('i', $nowtime)*60-date('s')+$nowtime;
            $redis->expireAt("$carnumber[0]", $overtime);
            echo "<script> alert('请检查此编号$km[0].$key[0]已使用,车牌是$carnumber[0]'); </script>";
        }
		}
    }
    //无效的票 已经被删除了
    if($valid[$time]=='0'){
        $sql = "select carnumber from `www.jinhong.com`.`fa_car_key` where keyword ='$realpasskey[$time]' and valid = '0';";
        $rst=mysqli_query($db,$sql);
        echo '此票无效';
    }
    }
  }
  return $realtimess;
 }
   
}

?>