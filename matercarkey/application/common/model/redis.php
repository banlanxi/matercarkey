<?php
   //连接本地的 Redis 服务
   $redis = new Redis();
   $redis->connect('127.0.0.1', 6379);
   //echo "Connection to server successfully";
   //设置 redis 字符串数据
   //$redis->set("tutorial-name", "Redis tutorial");
   //$redis->SADD("remenber", "redis");
   //$redis->set($key,$value);
   
   //$expireTime = mktime(23, 59, 59, date("m"), date("d"), date("Y"));
   //设置键的过期时间
   //$redis->expireAt("remenber", $expireTime);
   //$redis->expireAt("remenber", 1993840000);
   
   // 获取存储的数据并输出
   //echo "Stored string in redis:: " . $redis->get("tutorial-name");
?>

