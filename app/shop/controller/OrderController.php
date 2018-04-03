<?php
namespace app\shop\controller;

use cmf\controller\UserBaseController;
use think\Db;

/**
 * 商品订单类
 * 需要登录的
 */
class OrderController extends UserBaseController
{
    // 下单页 立即购买
    public function buy()
    {
        $data = $this->request->param();
        // $data['user_id'] = cmf_get_current_user_id();
        if (empty($data)) {
            $this->redirect('Shop/Index/index');
        }

        $amount = bcmul($data['price'], $data['number']);
        // 附加项
        $this->buyop($amount);

        $this->assign('data', [$data]);
        $this->assign('amount', $amount);
        return $this->fetch();
    }
    // 下单页 购物车结算
    public function buyCart()
    {
        if ($this->request->isPost()) {
            $data = $this->request->param('cartol/a');
        } else {
            $this->redirect('shop/Cart/cartList');
        }

        // 做判断 购物车数据有变化
        // $carts = session('user_cart');
        // $a1 = array_column($data,'id');
        // $a2 = array_column($carts,'id');
        // $diff = array_diff($a2,$a1);

        $ids   = array_column($data, 'id');
        $carts = model('shop/ShopCart')->getCartList(['a.id' => ['in', $ids]]);

        // 更新变化的
        $map    = [];
        $amount = '0.00';
        foreach ($carts as $key => $row) {
            if ($data[$row['id']]['number'] != $row['number']) {
                $map[]                 = ['id' => $row['id'], 'number' => $data[$row['id']]['number']];
                $carts[$key]['number'] = $data[$row['id']]['number'];
                $amount                = bcadd($amount, bcmul($data[$row['id']]['number'], $row['price']));
            } else {
                $amount = bcadd($amount, bcmul($row['number'], $row['price']));
            }
        }
        if (!empty($map)) {
            model('shop/ShopCart')->saveAll($map);
        }

        // 附加项
        $this->buyop($amount);

        $this->assign('data', $carts);
        $this->assign('amount', $amount);
        return $this->fetch('buy');
    }

    public function buyop($amount = 0)
    {
        $userId = cmf_get_current_user_id();
        // 收货地址
        $address = Db::name('shop_shipping_address')->where('user_id', $userId)->order('is_main DESC')->select()->toArray();

        // 默认地址 省市区？
        $addrFirst = [];
        if (!empty($address)) {
            $addr      = $address[0];
            $addrFirst = [
                'id'   => $addr['id'],
                'addr' => $addr['address'] .' '. $addr['username'] .'收 '. $addr['telephone'],
            ];
        }

        // 优惠券
        $coupon = Db::name('user_coupons_log')->field('id,coupon,reduce')->where(['status' => 0, 'user_id' => $userId, 'reduce' => ['elt', $amount]])->select();

        // 防止重复
        session('timestamp',time());

        $this->assign('addrFirst', $addrFirst);
        $this->assign('address', $address);
        $this->assign('coupon', $coupon);
    }

    // 积分兑换 可用立即购买的 buy()
    public function buyScore()
    {
        $this->error('暂未开放');
        $data = $this->request->param();
        // $buy_sign = 3;
        dump($data);

        // $this->assign('buy_sign',$buy_sign);
        // return $this->fetch();
    }

    // PC端选地址
    public function pc_address()
    {
        $this->error('暂未开放');
        return $this->fetch();
    }
    //手机端选择地址页
    public function wap_address()
    {
        return $this->fetch();
    }



/*生成订单*/
    // 支付页
    public function pay()
    {
        // dump($GLOBALS);die;
        $orderId = $this->request->param('orderId');

        if (empty($orderId)) {
            // 防止非POST方式
            if (!$this->request->isPost()) {
                $this->redirect('shop/Index/index');
            }

            // 由立即购买、购物车结算发起
            $data = $this->request->param();
            // 防止重复提交
            if (empty($data)) {
                $this->redirect('shop/Index/index');
            }
            // if ($data['timestamp'] == session('timestamp')) {
            //     session('timestamp', null);
            // } else {
            //     $this->redirect('user/Shop/Index', ['status' => 0]);
            // }
            $userId  = cmf_get_current_user_id();

            // 判断是否为购物车传过来的
            // 检查购物车里有没有 有则需要在提交订单成功后删除,没有就不用管
            $ids      = $data['ids'];
            $cart_ids = array_column($ids, 'cart_id');
            if (empty($cart_ids)) {
                $jumpurl = url('shop/Post/details', ['id' => $ids[0]['goods_id']]);
                if (empty($ids[0]['spec_id'])) {
                    $goods = Db::name('shop_goods')->field('id as goods_id,name as goods_name,price,thumbnail')->where(['id' => $ids[0]['goods_id']])->select()->toArray();
                } else {
                    $goods = model('shop/ShopGoodsSpec')->getGoodsBySpec(['a.id' => $ids[0]['spec_id']]);
                }
            } else {
                $jumpurl = url('shop/Cart/cartList');
                $goods   = model('shop/ShopCart')->getCartList(['a.id' => ['in', $cart_ids]]);
            }
// dump($goods);
// dump($data);
// dump($cart_ids);
// die;

            // shop_order ： id 或 order_sn 决定，索引 buyer_uid,seller_uid
            // shop_order_detail ： id 或 goods_id,spec_id 决定，索引 order_id
            $post = $data['order'];
            if (empty($post['address_id'])) {
                $this->error('请选择收货地址');
            }

            $amount = $post['order_amount'];
            // 满减优惠券
            $coupId = intval($post['coupId']);
            if ($coupId>0) {
                $coupon = Db::name('user_coupons_log')->field('id,coupon,reduce,user_id,status')->where('id', $coupId)->find();
                if (!empty($coupon)) {
                    $amount = (bccomp($coupon['reduce'], $amount) == -1) ? bcsub($amount, $coupon['coupon']) : $this->error('您的优惠券不符合满减', $jumpurl);
                }
            } 
// dump($post);
// dump($coupon);
// dump($amount);
// die;
            $order_sn = cmf_get_order_sn('shop_');
            // 订单表
            $order = [
                'buyer_uid'    => $userId,
                'address_id'   => $post['address_id'],
                // 'seller_uid'  => '',
                // 'seller_username'  => '',
                'order_sn'     => $order_sn,
                'nums'         => $post['nums'],
                'product_amount'  => $post['order_amount'],
                'order_amount' => $amount,
                'coupon_id'    => $coupId,
                // 'shipping_id'  => '',
                // 'shipping_fee'  => '',
                // 'description'  => '',//买家留言
                'ip'           => get_client_ip(),
                'create_time'  => $_SERVER['REQUEST_TIME'],
            ];

            // 订单详情表
            $details = [];

            // 启动事务
            Db::startTrans();
            try {
                $orderId = Db::name('shop_order')->insertGetId($order);
                if (empty($cart_ids)) {
                    $details = [
                        'order_id'   => $orderId,
                        'spec_id'    => (isset($goods['spec_id'])?$goods['spec_id']:0),
                        'goods_id'   => $goods['goods_id'],
                        'goods_type' => '1',
                        'goods_name' => $goods['goods_name'],
                        'thumbnail'  => $goods['thumbnail'],
                        'number'     => $post['nums'],
                        'price'      => $goods['price'],
                    ];
                } else {
                    foreach ($goods as $val) {
                        $details[] = [
                            'order_id'   => $orderId,
                            'spec_id'    => (isset($val['spec_id'])?$val['spec_id']:0),
                            'goods_id'   => $val['goods_id'],
                            'goods_name' => $val['goods_name'],
                            'thumbnail'  => $val['thumbnail'],
                            'number'     => $val['number'],
                            'price'      => $val['price'],
                        ];
                    }
                    Db::name('shop_cart')->where('id', 'in', $cart_ids)->delete();
                    session('user_cart',null);
                }
                // dump($details);die;
                Db::name('shop_order_detail')->insertAll($details);
                Db::name('user_coupons_log')->where('id',$coupId)->setField('status',1);
                Db::commit();
            } catch (\Exception $e) {
                Db::rollback();
            }

            // 不用try{}catch(){}，但是变量如何传入？
            // Db::transaction(function(){
            //     Db::name('shop_order')->insertGetId($order);
            //     Db::name('shop_order_detail')->insertAll($details);
            // });

        } else {
            $order      = Db::name('shop_order')->field('order_sn,order_amount')->where('id', $orderId)->find();
            // $order_list = Db::name('shop_order_detail')->field('*')->where('order_id', $orderId)->select();
            // dump($order);
            // dump($order_list);
            // die;
        }
// dump($order);
// dump($orderId);
        $this->assign('order', $order);
        $this->assign('paysign', 'shop');
        $this->assign('orderId', $orderId);

        return $this->fetch('pay');
    }

}