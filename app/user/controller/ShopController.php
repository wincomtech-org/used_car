<?php
namespace app\user\controller;

// use app\user\model\UserModel;
use cmf\controller\UserBaseController;
use think\Db;

// use think\Validate;

/**
 * 个人中心 服务商城
 * 需要登录验证的
 */
class ShopController extends UserBaseController
{
    public function _initialize()
    {
        parent::_initialize();
        $u_f_nav = $this->request->action();
        // $this->user = cmf_get_current_user();

        $this->assign('u_f_nav', $u_f_nav);
    }

    // 我的订单
    public function index()
    {
        $os = $this->request->param('status');

        $where = [];
        if ($os !== null) {
            $where['status'] = $os;
        }
        // config('shop_order_status');
        $orders = '';

        $this->assign('orders', $orders);
        $this->assign('os', $os);
        return $this->fetch();
    }
    // 订单详情
    public function detail()
    {
        return $this->fetch();
    }

    // 下单页 立即购买
    public function buy()
    {
        $data = $this->request->param();
        // $data['user_id'] = cmf_get_current_user_id();
        if (empty($data)) {
            $this->redirect('Shop/Index/index');
        }
// dump($data);die;
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
            $this->redirect('shop/Order/cartList');
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
// dump($data);
        // dump($map);
        // dump($carts);
        // dump($amount);
        // die;
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
                'addr' => $addr['address'] . ' ' . $addr['username'] . '收 ' . $addr['telephone'],
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
    public function score()
    {
        $data = $this->request->param();

        echo "暂未开放";die;
        dump($data);

        // return $this->fetch();
    }

    // PC端选地址
    public function pc_address()
    {
        return '暂无';
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
        if ($data['timestamp'] == session('timestamp')) {
            session('timestamp', null);
        } else {
            $this->redirect('user/Shop/Index', ['status' => 0]);
        }
        $orderId = $this->request->param('orderId');
        $userId  = cmf_get_current_user_id();

        // 判断是否为购物车传过来的
        // 检查购物车里有没有 有则需要在提交订单成功后删除,没有就不用管
        $ids      = $data['ids'];
        $cart_ids = array_column($ids, 'cart_id');
        if (empty($cart_ids)) {
            $jumpurl = url('shop/Post/details', ['id' => $ids[0]['goods_id']]);
            if (empty($ids[0]['spec_id'])) {
                $goods = Db::name('shop_goods')->field('id as goods_id,name as goods_name,price')->where(['id' => $ids[0]['goods_id']])->select()->toArray();
            } else {
                $goods = model('shop/ShopGoodsSpec')->getGoodsBySpec(['a.id' => $ids[0]['spec_id']]);
            }
        } else {
            $jumpurl = url('shop/Order/cartList');
            $goods   = model('shop/ShopCart')->getCartList(['a.id' => ['in', $cart_ids]]);
        }
// dump($goods);
// dump($data);
// dump($cart_ids);
// die;

        if (empty($orderId)) {
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
                // 'product_amount'  => '',
                'order_amount' => $amount,
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
                        'spec_id'    => isset($goods['spec_id']) ? $goods['spec_id'] : '0',
                        'goods_id'   => $goods['goods_id'],
                        'goods_name' => $goods['goods_name'],
                        'goods_type' => '1',
                        'number'     => $post['nums'],
                        'price'      => $goods['price'],
                    ];
                } else {
                    foreach ($goods as $val) {
                        $details[] = [
                            'order_id'   => $orderId,
                            'spec_id'    => $val['spec_id'],
                            'goods_id'   => $val['goods_id'],
                            'goods_name' => $val['goods_name'],
                            'number'     => $val['number'],
                            'price'      => $val['price'],
                        ];
                    }
                    Db::name('shop_cart')->where('id', 'in', $cart_ids)->delete();
                }
                Db::name('shop_order_detail')->insertAll($details);
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
            $order      = Db::name('shop_order')->field('*')->where('id', $orderId)->find();
            $order_list = Db::name('shop_order_detail')->field('*')->where('order_id', $orderId)->select();
            dump($order);
            dump($order_list);
            die;
        }
// dump($order);
// dump($orderId);
        $this->assign('order', $order);
        $this->assign('paysign', 'shop');
        $this->assign('orderId', $orderId);

        return $this->fetch('pay');
    }



/*订单处理*/
    // 确认收货
    public function receipt()
    {
        return $this->fetch();
    }

    // 评价
    public function evaluate()
    {
        $id = $this->request->param('id', 0, 'intval');
        if (empty($id)) {
            $this->error('非法请求');
        }

        // 是否有订单号、确认收货
        $goods = model('shop/ShopGoods')->getPost($id);
        // 评价表
        $evaluate = '';

        $this->assign('goods', $goods);
        $this->assign('evaluate', $evaluate);
        return $this->fetch();
    }
    public function evaluatePost()
    {
        $data = $this->request->param();
        $post = $data['eval'];
// dump($data);die;

        if (!empty($data['evaluate_image'])) {
            $post['evaluate_image'] = model('usual/Com')->dealFiles($data['evaluate_image']);
            $post['evaluate_image'] = json_encode($post['evaluate_image']);
        }
        $post['user_id']     = cmf_get_current_user_id();
        $post['create_time'] = time();
// dump($post);die;
        $result = Db::name('shop_evaluate')->insertGetId($post);
        if ($result > 0) {
            $this->success('评价成功', url('index', ['status' => null]));
        }
        $this->error('评价失败', url('index', ['status' => 3]));
    }

    // 物流信息
    public function logistics()
    {
        return $this->fetch();
    }

    // 退换货
    public function returns()
    {
        $list = Db::name('shop_order')->where('refund_status', 1)->select();

        $this->assign('list', $list);
        return $this->fetch();
    }
    // 退换货详情
    public function returns_detail()
    {
        $id = $this->request->param('id', 0, 'intval');

        $post = Db::name('shop_order')->where('id', $id)->find();

        $this->assign('post', $post);
        return $this->fetch();
    }

    // 消息管理
    public function news()
    {
        return $this->fetch();
    }



/*收货地址管理*/
    public function address()
    {
        $userId = cmf_get_current_user_id();
        $list   = Db::name('shop_shipping_address')->where('user_id', $userId)->order('is_main DESC')->select();
// dump($list);die;
        $this->assign('list', $list);
        return $this->fetch();
    }

    public function addressEdit()
    {
        $id   = $this->request->param('id/d');
        $post = Db::name('shop_shipping_address')->where('id', $id)->find();
        if (empty($post)) {
            $this->error('数据不存在了');
        }
        $this->assign($post);
        return $this->fetch();
    }

    public function addressPost()
    {
        $data    = $this->request->param();
        $id      = $this->request->param('id/d', 0, 'intval');
        $is_main = $this->request->param('is_main/d', 0, 'intval');
        // 验证数据
        // dump($data);die;
        $addrSql = Db::name('shop_shipping_address');
        if ($is_main == 1) {
            $find = $addrSql->where('is_main', 1)->value('id');
        }

        $post = [
            'province_id' => '0',
            'city_id'     => '0',
            'area_id'     => '0',
            'address'     => $data['address'],
            'username'    => $data['username'],
            'telephone'   => $data['telephone'],
            'is_main'     => $is_main,
            'user_id'     => cmf_get_current_user_id(),
        ];

        Db::startTrans();
        try {
            if ($id > 0) {
                $addrSql->where('id', $id)->update($post);
            } else {
                $addrSql->insert($post);
            }
            if ($find > 0) {
                $addrSql->where('id', $find)->setField('is_main', 0);
            }
            Db::commit();
        } catch (\Exception $e) {
            Db::rollback();
        }

        $this->success('添加成功', url('address'));
    }

    public function addressDelete()
    {
        $id = $this->request->param('id/d');
        if (empty($id)) {
            $this->error('数据非法');
        }
        Db::name('shop_shipping_address')->where('id', $id)->delete();
        $this->success('删除成功');
    }
}
