<?php

namespace app\admin\controller;
use app\index\model\User as UserModel;
use think\Controller;
use think\Db;

/**
 * Class Index
 * @package app\admin\controller
 * @describe 管理员模块主控制器
 */
class Index extends Controller {

    /**
     * @describe 检测是否已经登录
     */
    private function checkLogin() {
        if (!session('user.userId')) {
            $this->error('请登录','index/User/login');
        }
        if (session('user.usertype')!="admin") {
            $this->error('请用管理员方式登录','index/User/login');
        }
    }

    /**
     * @describe 管理员主页面
     * @return mixed
     * @throws \think\exception\DbException
     */
    public function index() {

        $count = Db::table('account')->count();
        $this->assign('count',$count);
        $list = Db::table('account')
            ->order('username')
            ->paginate(5);
        $this->assign('list',$list);
        return $this->fetch("index");
    }

    /**
     * @describe 添加产品页面
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function addproduct() {

        $this->checkLogin();
        $catlist = Db::table("category")->select();
        $this->assign('catlist',$catlist);
        return $this->fetch();
    }

    /**
     * @describe 添加产品处理逻辑
     */
    public function doaddproduct()
    {
        $proname = input('post.proname');
        $category = input('post.category');
        $price = input('post.price');
        $des = input('post.des');
        $file = request()->file('proimage');
        if ($file == Null) {
            $this->error("图片不能为空!");
        }
        $info = $file->validate(['ext'=>'jpg,png,gif'])
            ->move(ROOT_PATH . 'public' . DS. 'static' .DS. 'prodimages','');
        if($info){
            $imagefile=$info->getFilename();
        }
        else{
            $imagefile="";
        }
        $addsql='INSERT product(categoryid,productname,descn,image,unitprice)';
        $addsql.=' VALUES(:categoryid,:productname,:descn,:image,:unitprice)';
        $res=Db::execute($addsql,
            ['categoryid' => $category,
                'productname' => $proname,
                'descn' => $des,
                'image' => "prodimages/".$imagefile,
                'unitprice' => $price]);
        if ($res==1) {
            $this->success("新增宠物成功！",'Index/prodlist');
        } else {
            $this->error("新增失败！");
        }
    }

    /**
     * @describe 产品列表
     * @return mixed
     * @throws \think\exception\DbException
     */
    public function prodlist() {

        $this->checkLogin();
        $count = Db::table('product')->count();
        $this->assign('count',$count);
        $list = Db::view('category','categoryid,name')
            ->view('product',['productname','image','productid','unitprice'],
                'category.categoryid = product.categoryid')
            ->order('productid')
            ->paginate(5);
        $this->assign('list',$list);
        return $this->fetch("prodlist");
    }

    /**
     * @describe 删除产品
     * @param $pid
     * @return mixed
     * @throws \think\exception\DbException
     */
    public function delproduct($pid) {

        $this->checkLogin();
        Db::execute('DELETE FROM product WHERE productid=:productid',
            ['productid' => $pid]);
        return $this->prodlist();
    }

    /**
     * @describe 编辑宠物
     * @param $pid
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function editproduct($pid) {

        $this->checkLogin();
        $catlist =Db::table("category")->select();
        $this->assign('catlist',$catlist);
        $prod=Db::table('product')
            ->where('productid',$pid)
            ->find();
        $this->assign('proditem',$prod);
        return $this->fetch();
    }

    /**
     * 处理编辑宠物逻辑
     */
    public function doeditproduct()
    {
        $pid =input('post.pid');
        $proname=input('post.proname');
        $category=input('post.category');
        $price=input('post.price');
        $des=input('post.des');
        $file=request()->file('proimage');
        if(!empty($file)) {
            $info=$file->validate(['exit'=>'jpg.png.gif'])
                ->move(ROOT_PATH . 'public' . DS .'static' . DS . 'prodimages', '');
            if($info) {
                $imagefile=$info->getFilename();
            } else {
                $imagefile="";
            }
        } else {
            $imagefile="";
        }

        /**
         * @describe 略有修改,当 category 没有修改时,categoryid会为 0,将 categoryid 重新赋值
         * 出现编辑宠物后宠物列表丢失当前编辑的宠物
         * if ($category == 0) {
         *  $result = Db::table("product")
         *       ->where('productid',"=", $pid)
         *       ->select();
         *   $category = $result[0]["categoryid"];
         * }
         *
         * 错误原因：视图跳转连接时少了一个 “{”
         */

        $editsql='UPDATE product SET categoryid=:categoryid,productname=:productname,';
        if($imagefile=="") {
            $editsql.='descn=:descn,unitprice=:unitprice';
        } else {
            $editsql.='descn=:descn,image=:image,unitprice=:unitprice';
        }
        $editsql.=' WHERE productid=:productid';
        if($imagefile=="") {
            $res=Db::execute($editsql,
                [   'categoryid'=>$category,
                    'productname'=>$proname,
                    'descn'=>$des,
                    'productid'=>$pid,
                    'unitprice'=>$price]);
        } else {
            $res=Db::execute($editsql,
                [   'categoryid'=>$category,
                    'productname'=>$proname,
                    'descn'=>$des,
                    'image'=>"prodimages/".$imagefile,
                    'productid'=>$pid,
                    'unitprice'=>$price]);
        }
        if($res==1) {
            $this->success("修改宠物成功！",'prodlist');
        } else {
            $this->error("修改失败！");
        }
    }

    /**
     * @describe 用户列表展示界面
     * @return mixed
     * @throws \think\exception\DbException
     */
    public function userlist() {

        $this->checkLogin();
        $count=Db::table('account')->count();
        $this->assign('count',$count);
        $list=Db::table('account')
            ->order('username')
            ->paginate(5);
        $this->assign('list',$list);
        return $this->fetch("userlist");
    }

    /**
     * @describe 删除用户
     * @param $uid
     * @return mixed
     * @throws \think\exception\DbException
     */
    public function deluser($uid) {
        $this->checkLogin();
        Db::execute('DELETE FROM account WHERE username=:username',
            [ 'username' => $uid]);
        return $this->userlist();
    }

    /**
     * @describe 添加用户
     * @return mixed
     */
    public function adduser() {
        $this->checkLogin();
        return $this->fetch();
    }

    /**
     * 处理添加用户逻辑
     */
    public function doadduser() {
        $username = input('post.username');
        $truename = input('post.truename');
        $sex = input('post.sex');
        if ($sex=="male") {
            $sex="男";
        } else {
            $sex="女";
        }
        $email = "用户未填";
        $address = "用户未填";
        $phone = "用户未填";
        if (empty($username)) {
            $this->error('用户名不能为空');
        }
        $user =UserModel::getByUsername($username);
        if (!empty($user)) {
            $this->error('用户名已存在');
        }
        $data = array(
            'username' => $username,
            'password' => md5($username),
            'truename' => $truename,
            'sex' => $sex,
            'email' => $email,
            'address' => $address,
            'phone' => $phone
        );
        if ($result = UserModel::create($data)) {
            $this->success('新增用户成功，密码等于用户名','userlist');
        }
        else {
            $this->error('新增用户失败');
        }
    }

    /**
     * @describe 编辑用户界面
     * @param $uid
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function edituser($uid) {

        $this->checkLogin();
        $user = Db::table('account')
            ->where('username',$uid)
            ->find();
        $this->assign('useritem', $user);
        return $this->fetch();
    }

    /**
     * @describe 处理编辑用户界面
     */
    public function doedituser() {
        $username = input('post.username');
        $truename = input('post.truename');
        $sex = input('post.sex');
        if ($sex == "male") {
            $sex = "男";
        } else {
            $sex = "女";
        }
        $email = input('post.email');
        $address = input('post.address');
        $phone = input('post.phone');
        $data = array(
            'username' => $username,
            'truename' => $truename,
            'sex' => $sex,
            'email' => $email,
            'address' => $address,
            'phone' => $phone
        );
        $editsql = 'UPDATE account SET truename=:truename,sex=:sex,';
        $editsql .= 'email=:email,address=:address,phone=:phone';
        $editsql .= ' WHERE username=:username';
        $res = Db::execute($editsql, $data);
        $this->success('修改用户成功', 'userlist');
    }

    /**
     * @describe 重置用户密码为用户名
     * @param $uid
     */
    public function resetpass($uid) {

        $editsql = 'UPDATE account SET password=:pass';
        $editsql .= ' WHERE username=:username';
        $res = Db::execute($editsql, ['username' => $uid, 'pass' => md5($uid)]);
        $this->success('密码已经设置成功，新密码等于用户名', 'userlist');
    }

    /**
     * @describe 修改管理员密码
     * @return mixed
     */
    public function resetpassword() {
        $this->checkLogin();
        return $this->fetch();
    }

    /**
     * @describe 处理修理管理员密码逻辑
     */
    public function doresetpassword() {

        $pass1 = input("post.pass1");
        $pass2 = input("post.pass2");
        $admin = session('user.username');
        if ($pass1 != $pass2) {
            $this->error("两次密码不一致！","admin/index/resetpassword");
        }
        $editsql = 'UPDATE admin SET password=:pass';
        $editsql .= ' WHERE username=:username';
        $res = Db::execute($editsql, ['username' => $admin, 'pass' => md5($pass1)]);
        $this->success('密码修改成功！', 'userlist');
    }
}