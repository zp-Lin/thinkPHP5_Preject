<?php

namespace app\index\controller;

use app\index\model\Message;
use think\Controller;
use think\Db;

/**
 * Class Index
 * @package app\index\controller
 * @describe index控制器
 */
class Index extends Controller {

    /**
     * @describe 检测是否已经登录
     */
    private function checkLogin() {
        if (!session('user.userId')) {
            $this->error('请登录','User/login');
        }
    }

    /**
     * @describe 主页页面，展示产品列表信息，
     * @param string $cid
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function index($cid='') {

        // 类别id
        if ($cid=='') {
            session('product.catid',1);
        }
        else {
            session('product.catid',intval($cid));
        }

        // 渲染类别信息
        $catlist = Db::table("category")->select();
        $this->assign('catlist',$catlist);

        // 渲染购物车中产品数量
        if (session('user.userId')) {
            $count = Db::table('cart')
                ->where('username',session('user.userId'))
                ->count();
            $this->assign('count',$count);
        } else {
            $this->assign('count',0);
        }

        // 渲染产品列表信息，分页现实
        $list = Db::table('product')
            ->where('categoryid','=',session('product.catid'))
            ->order('productid')
            ->paginate(4);
        $this->assign('list',$list);
        return $this->fetch("productlist");
    }

    /**
     * @describe 展示产品信息
     * @param $pid
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function showproduct($pid) {

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
        $list = Db::view('category','name')
            ->view('product',['productid','productname','image','unitprice','descn'=>'pdescn'],
                'category.categoryid = product.categoryid')
            ->where('productid',$pid)->find();
        $this->assign('list',$list);
        return $this->fetch("showproduct");
    }

    /**
     * @describe 购物车页面
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function cart() {

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
        $list = Db::view('cart','productid,quantity')
            ->view('product',['productname','image','unitprice'],
                'cart.productid = product.productid')
            ->where('username',session('user.userId'))->select();

        // 计算总金额
        $sum=0.0;
        for ($i=0;$i<count($list);$i++)
        {
            $s=$list[$i]["unitprice"]*$list[$i]["quantity"];
            $list[$i]["sum"]=$s;
            $sum+=$list[$i]["unitprice"]*$list[$i]["quantity"];
        }
        $this->assign('sumprice',$sum);
        $this->assign('list',$list);
        return $this->fetch("cart");
    }

    /**
     * @describe 购物车商品数量减一
     * @param $pid
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function decrease($pid) {

        $count = Db::table('cart')
            ->where('username', session('user.userId'))
            ->where('productid', $pid)
            ->value('quantity');
        if ($count>1) {
            Db::execute('UPDATE cart SET quantity=quantity-1 WHERE username=:username AND productid=:productid',
                ['username' => session('user.userId'), 'productid'=> $pid]);
        }
        return $this->cart();
    }

    /**
     * @describe 购物车商品数量加一
     * @param $pid
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function increase($pid) {

        Db::execute('UPDATE cart SET quantity=quantity+1 WHERE username=:username AND productid=:productid',
            ['username' => session('user.userId'), 'productid' => $pid]);
        return $this->cart();
    }


    /**
     * @describe 删除购物车中的商品
     * @param $pid
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function delformcart($pid) {

        Db::execute('DELETE FROM cart WHERE username=:username AND productid=:productid',
            ['username' => session('user.userId'), 'productid'=> $pid]);
        return $this->cart();
    }

    /**
     * @describe 购物车结算
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function checkout() {

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
        $list = Db::view('cart','productid,quantity')
            ->view('product',['productname','image','unitprice'],
                'cart.productid = product.productid')
            ->where('username',session('user.userId'))->select();
        $sum=0.0;
        for ($i=0;$i<count($list);$i++) {
            $s=$list[$i]["unitprice"]*$list[$i]["quantity"];
            $list[$i]["sum"]=$s;
            $sum+=$list[$i]["unitprice"]*$list[$i]["quantity"];
        }
        $this->assign('sum',$sum);
        return $this->fetch("checkout");
    }

    /**
     * @describe 购物车结算逻辑
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function docheckout() {

        $cardno = input('post.cardno');
        $expdate = input('post.expdate');
        $list = Db::view('cart','productid,quantity')
            ->view('product',['productname','image','unitprice'],
                'cart.productid = product.productid')
            ->where('username',session('user.userId'))->select();
        $sum=0.0;
        for($i=0;$i<count($list);$i++) {
            $s=$list[$i]["unitprice"]*$list[$i]["quantity"];
            $list[$i]["sum"]=$s;
            $sum+=$list[$i]["unitprice"]*$list[$i]["quantity"];
        }
        $result = Db::execute('call addorder( :username,:cardno,:expdate);',
            ['username' => session('user.userId'),
                'cardno' => $cardno,'expdate' => $expdate]);

        $str='结算成功！';
        $str.="此次交易将从你的信用卡 $cardno 中划款合计￥ $sum 元。 ";
        $this->success($str,'Index/index');
    }

    /**
     * @describe 我的收藏界面
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function favorite() {

        $catlist = Db::table("category")->select();
        $this->assign("catlist",$catlist);
        if(session("user.userId")) {
            $count = Db::table("cart")->where("username",session("user.userId"))->count();
            $this->assign("count",$count);
        } else {
            $this->assign("count",0);
        }
        if(session("user.userId")) {
            $count = Db::table("favorite")->where("username",session("user.userId"))->count();
            $this->assign("fcount",$count);
        } else {
            $this->assign("fcount",0);
        }
        $list = Db::view("favorite","username,productid,star")
            ->view("product",["productname","image","unitprice"],"favorite.productid=product.productid")
            ->where("username",session("user.userId"))->select();
        $this->assign("list",$list);
        return $this->fetch("favorite");
    }

    /**
     * @describe 添加产品至我的收藏
     * @param $pid
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function addprodtofavorite($pid) {

        $this->checkLogin();
        $count = Db::table("favorite")->where("username",session("user.userId"))
            ->where("productid",$pid)->count();
        if($count==0) {
            Db::execute('INSERT INTO favorite(username,productid,star) VALUES (:username,:productid,1)',
                ["username" => session("user.userId"),"productid" => $pid]);
        }
        return $this->index(session("product.catid"));
    }

    /**
     * @describe 减少收藏星级
     * @param $pid
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function decreasefavorite($pid)
    {
        $count = Db::table("favorite")->where("username",session("user.userId"))
            ->where("productid",$pid)->value("star");
        if($count>1) {
            Db::execute('UPDATE favorite SET star=star-1 WHERE username=:username AND productid=:productid',
                ["username" => session("user.userId"),"productid" => $pid]);
        }
        return $this->favorite();
    }

    /**
     * @describe 增加收藏星级
     * @param $pid
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function increasefavorite($pid)
    {
        $count = Db::table("favorite")->where("username",session("user.userId"))
            ->where("productid",$pid)->value("star");
        if($count<5) {
            Db::execute('UPDATE favorite SET star=star+1 WHERE username=:username AND productid=:productid',
                ["username" => session("user.userId"),"productid" => $pid]);
        }
        return $this->favorite();
    }

    /**
     * @describe 删除收藏
     * @param $pid
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function delformfavorite($pid)
    {
        Db::execute('DELETE FROM favorite WHERE username=:username AND productid=:productid',
            ["username" => session("user.userId"),"productid" => $pid]);
        return $this->favorite();
    }

    /**
     * @describe 添加产品至购物车
     * @param $pid
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function addprodtocart($pid) {
        $this->doaddtocart($pid);
        return $this->index(session("product.catid"));
    }

    /**
     * @describe 添加产品至购物车
     * @param $pid
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function addprotocart2($pid) {
        $this->doaddtocart($pid);
        return $this->favorite();
    }

    /**
     * @describe 将产品添加至购物车逻辑，插入数据库的 petdb.cart 表
     * @param $pid
     */
    public function doaddtocart($pid) {

        // $this->checkLogin();
        if(!session('user.userId')) {
            $this->error('请登录','User/login');
        }
        $count = Db::table('cart')
            ->where('username', session('user.userId'))
            ->where('productid', $pid)
            ->count();
        if ($count>0) {
            Db::execute('UPDATE cart SET quantity=quantity+1 WHERE username=:username AND productid=:productid',
                ['username' => session('user.userId'), 'productid'=> $pid]);
        } else {
            Db::execute('INSERT INTO cart (username, productid, Quantity) VALUES (:username,:productid,1)',
                ['username' => session('user.userId'), 'productid'=> $pid]);
        }
    }

}
