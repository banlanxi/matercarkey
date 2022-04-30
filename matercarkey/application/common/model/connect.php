<?php

// 过程化 对象化
// $host = '127.0.0.1';
// $dbuser = 'root';
// $pwd = '111111';
// $dbname = 'liuyan';
// $db = new mysqli($host,$dbuser,$pwd);
// $db->query("CREATE DATABASE IF NOT EXISTS $dbname");
// $db->query("USE $dbname");


$db=mysqli_connect("localhost","www.jinhong.com","2827792","www.jinhong.com","3306");
if(!$db){
	die('数据库连接失败');
}
//2.mysqli_select_db($db,"liuyan");操作liuyan数据库
//指定数据库编码为UTF8
$db->query('SET NAMES UTF8');
//3.mysqli_query($db,"set names utf8");

//4.查询操作数据库
// $sql="select *from msg";
// $rst=mysqli_query($db,$sql);//$rst是返回得到的结果

//5.各类方式转化抽象的$rst结果集进行处理,
//mysqli_fetch_assoc()输出关联的字段名数组    用echo $arr['nicheng'];
//mysqli_fetch_row()输出索引
//mysqli_fetch_array()输出索引和关联的字段名数组
//mysqli_fetch_object() 要用  $arr->nicheng引用

// while（$arr=mysqli_fetch_assoc($rse)）{
// 	echo "<br/>";
// 	echo $arr['nicheng'];

	
// }

// // 3.3操作数据库（发送sql处理结果）
// $sql="select count(id) as c from msg";
// $res=$mysqli->query($sql);
// // 方式一
// //cho mysqli_num_rows($rse);
// // 方式二 推荐
// $row =$res->fetch_array();
// echo $row（'c'）;


//6.关闭数据库，释放资源
//mysqli_close($db);
//用来防止sql注入
function check_param($value) { 
    #  select|insert|update|delete|\'|\/\*|\*|\.\.\/|\.\/|union|into|load_file|outfile
$str = 'select|insert|and|or|update|delete|\'|\/\*|\*|\.\.\/|\.\/|union|into|load_file|outfile';
if(!$value) {
    exit('没有参数！'); 
	
}elseif(@preg_match_all($str, $value)) { 
	//清除所有session；
    session_destroy();
	echo "请再次确定，输入正确的的车牌";
    header("refresh:5;url=./user.php");//输入错误跳转登录页面
	die;
}
return true; 
} 
?>