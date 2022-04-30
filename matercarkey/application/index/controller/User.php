<?php

namespace app\index\controller;

use addons\wechat\model\WechatCaptcha;
use app\common\controller\Frontend;
use app\common\library\Ems;
use app\common\library\Sms;
use app\common\model\Attachment;
use think\Config;
use think\Cookie;
use think\Hook;
use think\Session;
use think\Validate;
use app\common\model\Userpass;
use app\common\model\Usercheckkey;

/**
 * 会员中心
 */
class User extends Frontend
{
    protected $layout = 'default';
    protected $noNeedLogin = ['login', 'register', 'third'];
    protected $noNeedRight = ['*'];

    public function _initialize()
    {
        parent::_initialize();
        $auth = $this->auth;

        if (!Config::get('fastadmin.usercenter')) {
            $this->error(__('User center already closed'), '/');
        }

        //监听注册登录退出的事件
        Hook::add('user_login_successed', function ($user) use ($auth) {
            $expire = input('post.keeplogin') ? 30 * 86400 : 0;
            Cookie::set('uid', $user->id, $expire);
            Cookie::set('token', $auth->getToken(), $expire);
        });
        Hook::add('user_register_successed', function ($user) use ($auth) {
            Cookie::set('uid', $user->id);
            Cookie::set('token', $auth->getToken());
        });
        Hook::add('user_delete_successed', function ($user) use ($auth) {
            Cookie::delete('uid');
            Cookie::delete('token');
        });
        Hook::add('user_logout_successed', function ($user) use ($auth) {
            Cookie::delete('uid');
            Cookie::delete('token');
        });
    }

    /**
     * 会员中心
     */
    public function index()
    {
        $this->view->assign('title', __('User center'));
        return $this->view->fetch();
    }

    /**
     * 注册会员
     */
    public function register()
    {
        $url = $this->request->request('url', '', 'trim');
        if ($this->auth->id) {
            $this->success(__('You\'ve logged in, do not login again'), $url ? $url : url('user/index'));
        }
        if ($this->request->isPost()) {
            $username = $this->request->post('username');
            $password = $this->request->post('password');
            $email = $this->request->post('email');
            $mobile = $this->request->post('mobile', '');
            $captcha = $this->request->post('captcha');
            $token = $this->request->post('__token__');
            $rule = [
                'username'  => 'require|length:3,30',
                'password'  => 'require|length:6,30',
                'email'     => 'require|email',
                'mobile'    => 'regex:/^1\d{10}$/',
                '__token__' => 'require|token',
            ];

            $msg = [
                'username.require' => 'Username can not be empty',
                'username.length'  => 'Username must be 3 to 30 characters',
                'password.require' => 'Password can not be empty',
                'password.length'  => 'Password must be 6 to 30 characters',
                'email'            => 'Email is incorrect',
                'mobile'           => 'Mobile is incorrect',
            ];
            $data = [
                'username'  => $username,
                'password'  => $password,
                'email'     => $email,
                'mobile'    => $mobile,
                '__token__' => $token,
            ];
            //验证码
            $captchaResult = true;
            $captchaType = config("fastadmin.user_register_captcha");
            if ($captchaType) {
                if ($captchaType == 'mobile') {
                    $captchaResult = Sms::check($mobile, $captcha, 'register');
                } elseif ($captchaType == 'email') {
                    $captchaResult = Ems::check($email, $captcha, 'register');
                } elseif ($captchaType == 'wechat') {
                    $captchaResult = WechatCaptcha::check($captcha, 'register');
                } elseif ($captchaType == 'text') {
                    $captchaResult = \think\Validate::is($captcha, 'captcha');
                }
            }
            if (!$captchaResult) {
                $this->error(__('Captcha is incorrect'));
            }
            $validate = new Validate($rule, $msg);
            $result = $validate->check($data);
            if (!$result) {
                $this->error(__($validate->getError()), null, ['token' => $this->request->token()]);
            }
            if ($this->auth->register($username, $password, $email, $mobile)) {
                $this->success(__('Sign up successful'), $url ? $url : url('user/index'));
            } else {
                $this->error($this->auth->getError(), null, ['token' => $this->request->token()]);
            }
        }
        //判断来源
        $referer = $this->request->server('HTTP_REFERER');
        if (!$url && (strtolower(parse_url($referer, PHP_URL_HOST)) == strtolower($this->request->host()))
            && !preg_match("/(user\/login|user\/register|user\/logout)/i", $referer)) {
            $url = $referer;
        }
        $this->view->assign('captchaType', config('fastadmin.user_register_captcha'));
        $this->view->assign('url', $url);
        $this->view->assign('title', __('Register'));
        return $this->view->fetch();
    }

    /**
     * 会员登录
     */
    public function login()
    {
        $url = $this->request->request('url', '', 'trim');
        if ($this->auth->id) {
            $this->success(__('You\'ve logged in, do not login again'), $url ? $url : url('user/index'));
        }
        if ($this->request->isPost()) {
            $account = $this->request->post('account');
            $password = $this->request->post('password');
            $keeplogin = (int)$this->request->post('keeplogin');
            $token = $this->request->post('__token__');
            $rule = [
                'account'   => 'require|length:3,50',
                'password'  => 'require|length:6,30',
                '__token__' => 'require|token',
            ];

            $msg = [
                'account.require'  => 'Account can not be empty',
                'account.length'   => 'Account must be 3 to 50 characters',
                'password.require' => 'Password can not be empty',
                'password.length'  => 'Password must be 6 to 30 characters',
            ];
            $data = [
                'account'   => $account,
                'password'  => $password,
                '__token__' => $token,
            ];
            $validate = new Validate($rule, $msg);
            $result = $validate->check($data);
            if (!$result) {
                $this->error(__($validate->getError()), null, ['token' => $this->request->token()]);
                return false;
            }
            if ($this->auth->login($account, $password)) {
                $this->success(__('Logged in successful'), $url ? $url : url('user/index'));
            } else {
                $this->error($this->auth->getError(), null, ['token' => $this->request->token()]);
            }
        }
        //判断来源
        $referer = $this->request->server('HTTP_REFERER');
        if (!$url && (strtolower(parse_url($referer, PHP_URL_HOST)) == strtolower($this->request->host()))
            && !preg_match("/(user\/login|user\/register|user\/logout)/i", $referer)) {
            $url = $referer;
        }
        $this->view->assign('url', $url);
        $this->view->assign('title', __('Login'));
        return $this->view->fetch();
    }

    /**
     * 退出登录
     */
    public function logout()
    {
        if ($this->request->isPost()) {
            $this->token();
            //退出本站
            $this->auth->logout();
            $this->success(__('Logout successful'), url('user/index'));
        }
        $html = "<form id='logout_submit' name='logout_submit' action='' method='post'>" . token() . "<input type='submit' value='ok' style='display:none;'></form>";
        $html .= "<script>document.forms['logout_submit'].submit();</script>";

        return $html;
    }

    /**
     * 个人信息
     */
    public function profile()
    {
        $this->view->assign('title', __('Profile'));
        return $this->view->fetch();
    }

    /**
     * 修改密码
     */
    public function changepwd()
    {
        if ($this->request->isPost()) {
            $oldpassword = $this->request->post("oldpassword");
            $newpassword = $this->request->post("newpassword");
            $renewpassword = $this->request->post("renewpassword");
            $token = $this->request->post('__token__');
            $rule = [
                'oldpassword'   => 'require|length:6,30',
                'newpassword'   => 'require|length:6,30',
                'renewpassword' => 'require|length:6,30|confirm:newpassword',
                '__token__'     => 'token',
            ];

            $msg = [
                'renewpassword.confirm' => __('Password and confirm password don\'t match')
            ];
            $data = [
                'oldpassword'   => $oldpassword,
                'newpassword'   => $newpassword,
                'renewpassword' => $renewpassword,
                '__token__'     => $token,
            ];
            $field = [
                'oldpassword'   => __('Old password'),
                'newpassword'   => __('New password'),
                'renewpassword' => __('Renew password')
            ];
            $validate = new Validate($rule, $msg, $field);
            $result = $validate->check($data);
            if (!$result) {
                $this->error(__($validate->getError()), null, ['token' => $this->request->token()]);
                return false;
            }

            $ret = $this->auth->changepwd($newpassword, $oldpassword);
            if ($ret) {
                $this->success(__('Reset password successful'), url('user/login'));
            } else {
                $this->error($this->auth->getError(), null, ['token' => $this->request->token()]);
            }
        }
        $this->view->assign('title', __('Change password'));
        return $this->view->fetch();
    }

    public function attachment()
    {
        //设置过滤方法
        $this->request->filter(['strip_tags']);
        if ($this->request->isAjax()) {
            $mimetypeQuery = [];
            $where = [];
            $filter = $this->request->request('filter');
            $filterArr = (array)json_decode($filter, true);
            if (isset($filterArr['mimetype']) && preg_match("/[]\,|\*]/", $filterArr['mimetype'])) {
                $this->request->get(['filter' => json_encode(array_diff_key($filterArr, ['mimetype' => '']))]);
                $mimetypeQuery = function ($query) use ($filterArr) {
                    $mimetypeArr = explode(',', $filterArr['mimetype']);
                    foreach ($mimetypeArr as $index => $item) {
                        if (stripos($item, "/*") !== false) {
                            $query->whereOr('mimetype', 'like', str_replace("/*", "/", $item) . '%');
                        } else {
                            $query->whereOr('mimetype', 'like', '%' . $item . '%');
                        }
                    }
                };
            } elseif (isset($filterArr['mimetype'])) {
                $where['mimetype'] = ['like', '%' . $filterArr['mimetype'] . '%'];
            }

            if (isset($filterArr['filename'])) {
                $where['filename'] = ['like', '%' . $filterArr['filename'] . '%'];
            }

            if (isset($filterArr['createtime'])) {
                $timeArr = explode(' - ', $filterArr['createtime']);
                $where['createtime'] = ['between', [strtotime($timeArr[0]), strtotime($timeArr[1])]];
            }

            $model = new Attachment();
            $offset = $this->request->get("offset", 0);
            $limit = $this->request->get("limit", 0);
            $total = $model
                ->where($where)
                ->where($mimetypeQuery)
                ->where('user_id', $this->auth->id)
                ->order("id", "DESC")
                ->count();

            $list = $model
                ->where($where)
                ->where($mimetypeQuery)
                ->where('user_id', $this->auth->id)
                ->order("id", "DESC")
                ->limit($offset, $limit)
                ->select();
            $cdnurl = preg_replace("/\/(\w+)\.php$/i", '', $this->request->root());
            foreach ($list as $k => &$v) {
                $v['fullurl'] = ($v['storage'] == 'local' ? $cdnurl : $this->view->config['upload']['cdnurl']) . $v['url'];
            }
            unset($v);
            $result = array("total" => $total, "rows" => $list);

            return json($result);
        }
        $this->view->assign("mimetypeList", \app\common\model\Attachment::getMimetypeList());
        return $this->view->fetch();
    }
    public function memberuser()
    {
        return $this->view->fetch();

    }

    public function pop(){
       //接收form提交的数据

        $data = $this->request->post();
        model('Userpass')->index($data);
    }
    public function chuli(){
        //接收form提交的数据
 
         $data = $this->request->post();
         //session值的读取:
        $username = Session::get('username');
        $nickname = Session::get('name');
        $time=time();
        //预处理传过来的票据

        //获得传过来多少票据,并把票据插入到$passkey数组中，并预处理$passkey数组
        $times=0;
        //记录此次执行多少成功上传
        $realtimess=0;
        $passkey=array();
        //传过来的数量
        global $times;
        //暂存key
        global $passkey;
        //有效地key
        global $realpasskeynull;
        //有效地key数量
        global $realtimes;
        function lastkey(){
        //传过来的数量
        global $times;
        //暂存key
        global $passkey;
        //有效地key
        global $realpasskeynull;
        //有效地key数量
        global $realtimes;
            $frequency=1;
            for($frequency=1;$frequency<33;$frequency++){
                //将$_POST[$frequency]存入数组
                $passkey[$frequency]=$_POST[$frequency];
                //判断不为空
                if(!empty($_POST[$frequency])){
                    $times++;
                }   
            //去除重复的key并获得处理后的
            $realpasskey=array_unique($passkey);
            
            //去除为空的key
            $realpasskeynull=array_filter($realpasskey);
            //重新排序
            $realpasskeynull=array_merge($realpasskeynull);
            //获得不重复且不为空的数量
            $realtimes=count($realpasskeynull);
        }
        }
        //echo $now;
        lastkey();
        // echo $realtimes;
        // var_dump($realpasskeynull);

        
        $realtimess=model('Indexchuli1')->checkenterkey($realtimes,$realpasskeynull,$time,$username);
         echo "<script> alert('输入的票据有$times 个，其中不重复有 $realtimes,成功登记的有$realtimess ');history.go(-1); </script>";
     }
    public function memberindex()
    {   
            //session_start();
            //session值的读取:
            // 取值（当前作用域）
            $username = Session::get('username');
            $nickname = Session::get('name');
            // $username = $_SESSION['username'];
            // $nickname = $_SESSION['name'];
            $key=array();
                //连接数据库
            $db=mysqli_connect("localhost","www.jinhong.com","2827792","www.jinhong.com","3306");
            mysqli_select_db ($db,"www.jinhong.com");
            mysqli_query($db,"set names utf8");
            //获得今日时间戳
            $today=strtotime(date("Y-m-d"));
            //统计今日当前用户上传的票据数量
            $sql="select * from `www.jinhong.com`.`fa_car_key` where `carnumber`='$username' and `jointime`>'$today'";
            $result=mysqli_query($db,$sql);
            //输出总的票据数量
            echo "<br><br><br>";
            echo "今日当前用户上传的票据数量为：".mysqli_num_rows($result);
            echo "<br>其中";
            //接收到的票据根据不同种类的km分类
            while($row=mysqli_fetch_assoc($result)){
                $key[$row['km']][]=$row;
            }
            //输出不同种类票据数量
            foreach($key as $k=>$v){
                if($k=="a"){
                    echo "1里票据数量为：".count($v)."<br>";
                }elseif($k=="b"){
                    echo "1.5公里票据数量为：".count($v)."<br>";
                }elseif($k=="c"){
                    echo "2公里票据数量为：".count($v)."<br>";
                }elseif($k=="d"){
                    echo "2.5公里票据数量为：".count($v)."<br>";
                }elseif($k=="e"){
                    echo "3公里票据数量为：".count($v)."<br>";
                }elseif($k=="f"){
                    echo "3.5公里票据数量为：".count($v)."<br>";
                }elseif($k=="g"){
                    echo "4公里票据数量为：".count($v)."<br>";
                }elseif($k=="h"){
                    echo "4.5公里票据数量为：".count($v)."<br>";
                }elseif($k=="i"){
                    echo "5公里票据数量为：".count($v)."<br>";
                }elseif($k=="j"){
                    echo "5.5公里票据数量为：".count($v)."<br>";
                }elseif($k=="k"){
                    echo "6公里票据数量为：".count($v)."<br>";
                }elseif($k=="l"){
                    echo "6.5公里票据数量为：".count($v)."<br>";
                }elseif($k=="m"){
                    echo "7公里票据数量为：".count($v)."<br>";
                }
            }
        return $this->view->fetch();
    }


    public function check()
    {
        return $this->view->fetch();
    }

    public function checkkey()
    {
        $data = $this->request->post();
        //获取调用model后得到的数据
        $result =model('Usercheckkey')->index($data);
        //根据km输出不同种类票据数量
        $key=array();
        foreach($result as $k=>$v){
            $key[$v['km']][]=$v;
        }
        //输出不同种类票据数量
        foreach($key as $k=>$v){
            if($k=="a"){
                echo "<div style='text-align:center;'>1里票据数量为：".count($v)."<br>";
            }elseif($k=="b"){
                echo "<div style='text-align:center;'>1.5公里票据数量为：".count($v)."<br>";
            }elseif($k=="c"){
                echo "<div style='text-align:center;'>2公里票据数量为：".count($v)."<br>";
            }elseif($k=="d"){
                echo "<div style='text-align:center;'>2.5公里票据数量为：".count($v)."<br>";
            }elseif($k=="e"){
                echo "<div style='text-align:center;'>3公里票据数量为：".count($v)."<br>";
            }elseif($k=="f"){
                echo "<div style='text-align:center;'>3.5公里票据数量为：".count($v)."<br>";
            }elseif($k=="g"){
                echo "<div style='text-align:center;'>4公里票据数量为：".count($v)."<br>";
            }elseif($k=="h"){
                echo "<div style='text-align:center;'>4.5公里票据数量为：".count($v)."<br>";
            }elseif($k=="i"){
                echo "<div style='text-align:center;'>5公里票据数量为：".count($v)."<br>";
            }elseif($k=="j"){
                echo "<div style='text-align:center;'>5.5公里票据数量为：".count($v)."<br>";
            }elseif($k=="k"){
                echo "<div style='text-align:center;'>6公里票据数量为：".count($v)."<br>";
            }elseif($k=="l"){
                echo "<div style='text-align:center;'>6.5公里票据数量为：".count($v)."<br>";
            }elseif($k=="m"){
                echo "<div style='text-align:center;'>7公里票据数量为：".count($v)."<br>";
            }
        }
        //将$key中的jointime时间戳转换成日期
        foreach($key as $k=>$v){
            foreach($v as $k1=>$v1){
                $key[$k][$k1]['jointime']=date('Y-m-d H:i:s',$v1['jointime']);
            }
        }
        //输出$key中的km.key.jointime
        foreach($key as $k=>$v){
            //五个数据为一组换行
            $i=0;
            foreach($v as $k1=>$v1){
                if($i%5==0){
                    echo "<div style='text-align:center;'>";
                }
                echo $v1['km'].".".$v1['key'].".".$v1['jointime']."<br>";
                if($i%5==4){
                    echo "</div>";
                }
                $i++;
            }
        }



    }
    // public function jsqr.js()
    // {
    //     return $this->view->fetch();
    // }

}
