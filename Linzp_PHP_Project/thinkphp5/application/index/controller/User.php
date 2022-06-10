<?php

namespace app\index\controller;

use think\Controller;
use app\index\model\User as UserModel;
use app\index\model\Admin as AdminModel;
use think\Db;

/**
 * Class User
 * @package app\index\controller
 * @describe User模板，对应 petdb.account 表，实现用户的登陆，注册，注销和修改密码操作
 */
class User extends Controller {

    /**
     * @describe 注册账号页面
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function register() {

        $catlist = Db::table("category")->select();
        $this->assign('catlist',$catlist);
        if (session('user.userId')) {
            $count = Db::table('cart')
                ->where('username',session('user.userId'))
                ->count();
            $this->assign('count',$count);
        } else {
            $this->assign('count',0);
        }
        return $this->fetch();
    }

    /**
     * @describe 处理注册账号逻辑，注册成功跳转到登陆页面
     */
    public function doregister() {

        $username = input('post.username');
        $password = input('post.password');
        $repassword = input('post.repassword');
        $truename = input('post.truename');
        $sex = input('post.sex');
        if ($sex=="male") {
            $sex="男";
        } else {
            $sex="女";
        }
        $email = input('post.email');
        $address = input('post.address');
        $phone = input('post.phone');
        if (empty($username)) {
            $this->error('用户名不能为空');
        }
        if (empty($password)) {
            $this->error('密码不能为空');
        }
        if ($password !=$repassword) {
            $this->error('确认密码错误');
        }
        $user = UserModel::getByUsername($username);
        if (!empty($user)) {
            $this->error('用户名已存在');
        }
        $data = array(
            'username' => $username,
            'password' => md5($password),
            'truename' => $truename,
            'sex' => $sex,
            'email' => $email,
            'address' => $address,
            'phone' => $phone
        );
        if ($result = UserModel::create($data)) {
            $this->success('注册成功，请登录','login');
        }
        else {
            $this->error('注册失败');
        }
    }

    /**
     * @describe 用户登陆页面
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function login() {

        $catlist = Db::table("category")->select();
        $this->assign('catlist',$catlist);
        if (session('user.userId')) {
            $count = Db::table('cart')
                ->where('username',session('user.userId'))
                ->count();
            $this->assign('count',$count);
        } else {
            $this->assign('count',0);
        }
        return $this->fetch();
    }

    /**
     * @describe 处理登陆操作代码
     */
    public function dologin() {

        $username = input('post.username');
        $password = input('post.password');
        $admin = input('post.admin');
        if ($admin != "admin") {
            $user = UserModel::getByUsername($username);
            if (empty($user) || $user['password'] != md5($password))
            {
                $this->error('帐号或密码错误');
            }
            session('user.userId',$user['username']);
            session('user.username',$user['truename']);
            session('user.usertype', "user");
            $this->redirect('Index/index');
        } else {
            $user = AdminModel::getByUsername($username);
            if (empty($user) || $user['password'] != md5($password)) {
                $this->error('帐号或密码错误');
            }
            session('user.userId',$user['username']);
            session('user.username',$user['username']);
            session('user.usertype', "admin");
            $this->redirect('admin/index/userlist');
        }
    }

    /**
     * @describe 用户注销
     */
    public function logout() {

        if (!session('user.userId')) {
            $this->error('请登录');
        }
        session_destroy();
        $this->success('退出登录成功','Index/index');
    }

    /**
     * @describe 修改用户密码页面
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function resetpassword() {

        $catlist = Db::table("category")->select();
        $this->assign('catlist',$catlist);
        if (session('user.userId')) {
            $count = Db::table('cart')
                ->where('username',session('user.userId'))
                ->count();
            $this->assign('count',$count);
        }
        else
            $this->assign('count',0);
        return $this->fetch();
    }

    /**
     * @describe 修改密码逻辑，验证两次密码是否一致
     */
    public function doresetpassword() {

        $username = session("user.userId");
        $pass1 = input("post.pass1");
        $pass2 = input("post.pass2");
        if ($pass1 != $pass2) {
            $this->error("两次密码不一致！","index/index/index");
        }
        $editsql = 'UPDATE account SET password=:pass';
        $editsql .= ' WHERE username=:username';
        $res = Db::execute($editsql, ['username' => $username, 'pass' => md5($pass1)]);
        $this->success('密码修改成功！', 'index/index/index');
    }
}