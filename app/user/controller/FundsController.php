<?php
namespace app\user\controller;

use cmf\controller\UserBaseController;
use app\user\model\UserModel;
use app\funds\model\UserFundsLogModel;
use app\funds\model\FundsApplyModel;
// use think\Validate;
use think\Db;

/**
* 个人中心
* 财务管理 资金动向
*/
class FundsController extends UserBaseController
{
    function _initialize()
    {
        parent::_initialize();
        $u_f_nav = $this->request->action();
        $this->user = cmf_get_current_user();

        $this->assign('u_f_nav',$u_f_nav);
    }

    // 列表页
    public function index()
    {
        $param = $this->request->param();
        $month = $this->request->param('month',0,'intval');
        $type = $this->request->param('type',0,'intval');

        $param['userId'] = $this->user['id'];
        // vendor('topthink/think-helper/src/Time');
        // Time::today();
        $param['start_time'] = strtotime("-$month month");
        // $param['end_time'] = time();

        $fundsModel = new UserFundsLogModel();
        $list = $fundsModel->getLists($param);

        // 押金 需去除 -1,-3,-5,-6,-7
        // $deposit = $fundsModel->sumCoin($this->user['id'],'1,3,5,6,7');
        // $deposit2 = $fundsModel->sumCoin($this->user['id'],'-1,-3,-5,-6,-7');
        // 收入
        $income = $fundsModel->sumCoin($this->user['id'],'',$param['start_time'],'gt');
        // 支出
        $expense = $fundsModel->sumCoin($this->user['id'],'',$param['start_time'],'lt');
        // 类型
        // $categorys = $fundsModel->getTypes($type);

        // $this->assign('categorys', $categorys);
        $this->assign('income', $income);
        $this->assign('expense', $expense);
        // $this->assign('type', $type);
        $this->assign('list', $list->items());
        $list->appends($param);
        $this->assign('pager', $list->render());

        return $this->fetch();
    }

    // 积分
    public function score()
    {
        // $param = $this->request->param();
        $where['user_id'] = $this->user['id'];

        $list = Db::name('user_score_log')->where($where)->order('id DESC')->paginate();
        // 历史累计积分
        $where['score'] = ['lt',0];
        $score = Db::name('user_score_log')->where($where)->sum('score');
        $score += $this->user['score'];
        // 剩余积分
        $remain = $this->user['score'];

        $this->assign('score', $score);
        $this->assign('remain', $remain);
        $this->assign('list', $list);
        // $list->appends($param);
        $this->assign('pager', $list->render());

        return $this->fetch();
    }
    // 优惠券
    public function coupon()
    {
        // $param = $this->request->param();
        $where['user_id'] = $this->user['id'];

        $list = Db::name('user_coupons_log')->where($where)->order('create_time DESC')->paginate();

        // 历史累计
        // $this->user['coupon'];

        // 可使用
        $where['status'] = 0;
        // 有BUG：AND (`due_time`=1515396639 OR `due_time`>=1515396639)
        // $coupon = Db::name('user_coupons_log')->where($where)->where(function($query){
        //     $query->where('due_time',0)->whereOr('due_time','>= time',time());
        // })->fetchSql(true)->count();
        // 使用原生的写，可用 union all
        $coupon = Db::query('SELECT COUNT(*) AS tp_count FROM `cmf_user_coupons_log` WHERE `user_id`=? AND `status`=0 AND (`due_time`=0 OR `due_time`>=?) LIMIT 1',[$this->user['id'],time()]);

        // 已过期
        $where['due_time'] = [['gt',0],['< time', time()]];
        // $remain = Db::name('user_coupons_log')->where($where)->fetchSql(true)->count();
        $remain = Db::name('user_coupons_log')->where($where)->count();

        $this->assign('coupon', $coupon[0]['tp_count']);
        $this->assign('remain', $remain);
        $this->assign('list', $list);
        // $list->appends($param);
        $this->assign('pager', $list->render());

        return $this->fetch();
    }

    /**
    * 充值 无pay.html
        // 判断是否为手机端、微信端
        $map = [
            'action'  => 'recharge',
            'order_sn'  => cmf_get_order_sn('recharge_'),
            'coin'  => $amount,
            'id'  => $data['id'],
        ];
        $this->showPay($map);
    */
    public function recharge()
    {
        // 微信扫码支付
        // echo urlencode('weixin://wxpay/bizpayurl?pr=CtSJGVk');die;
        // $amount = $this->request->param('amount',null);
        // if (!empty($amount)) {
        //     $id = Db::name('funds_apply')->max('id');
        //     import('payment/wxpaynative/WorkPlugin',EXTEND_PATH);
        //     $work = new \WorkPlugin(cmf_get_order_sn('recharge_'), $amount, $id+1, 'recharge');
        //     $qrcode = $work->work();
        //     echo $qrcode;exit;
        // }

        /*v2 S*/
        // 查询充值单？直接后台管理员给用户充值
        // $post = [
        //     'type'      => 'recharge',
        //     'user_id'   => $user['id'],
        //     'order_sn'  => $data['out_trade_no'],
        //     'coin'      => $total_fee,
        //     'payment'   => $paytype,
        //     'create_time' => time(),
        //     'status'    => $status,
        // ];
        // $orderId = Db::name('funds_apply')->insertGetId($post);

        $this->assign('paysign','recharge');
        $this->assign('orderId','null');
        /*v2 E*/

        return $this->fetch();
    }
    // 提交充值 paytype,action,coin
    public function rechargePost()
    {
        $data = $this->request->param();
        $valid = [
            'user_id'   => $this->user['id'],
            'coin'      => empty($data['r_money'])?null:$data['r_money'],
            'payment'   => empty($data['payment_way'])?null:$data['payment_way'],
            'type'      => 'recharge',
        ];

        // 验证
        $result = $this->validate($valid, 'Funds.recharge');
        if ($result!==true) {
            $this->error($result);
        }

        // 判断是否二次支付：首单，订单在支付后生成，先去支付
        $map = [
            'paytype'  => $valid['payment'],
            'action'  => 'recharge',
            'coin'  => $valid['coin'],
        ];
        // 转向支付接口
        $this->success('前往支付中心……',cmf_url('funds/Pay/pay',$map));
    }

    // 提现
    public function withdraw()
    {
        $usualSettings = cmf_get_option('usual_settings');
        $this->assign('withdraw_switch',$usualSettings['withdraw_switch']);
        return $this->fetch();
    }
    public function withdrawPost()
    {
        $usualSettings = cmf_get_option('usual_settings');
        if ($usualSettings['withdraw_switch'] != 1) {
            $this->error('管理员已关闭了提现功能');
        }
        $data = $this->request->post();
        // dump($data);die;

        // 预处理数据
        $data['account'] = $data[$data['payment_way']]['account'];

        $post = [
            'user_id'   => $this->user['id'],
            'account'   => $data['account'],
            'username'  => $data['w_name'],
            'coin'      => $data['w_money'],
            'payment'   => $data['payment_way'],
            'create_time'=> time(),
        ];
        $post['coin'] = intval($post['coin']);

        $applyModel = new FundsApplyModel();
        $count = $applyModel->counts($this->user['id']);
        if ($count>=1) {
            $this->error('您有提现待处理的数据',url('user/Funds/apply',['status'=>0,'type'=>'withdraw']));
        }
        $count = $applyModel->counts($this->user['id'],'time');
        if ($count>=$usualSettings['withdraw_num']) {
            $this->error('每天仅限'.$usualSettings['withdraw_num'].'次，请明天再来');
        }

        bcscale(2);
        // 验证
        $result = $this->validate($post,'Funds.withdraw');
        if ($result!==true) {
            $this->error($result);
        }
        if (empty($data['w_pwd'])) {
            $this->error('请输入密码');
        }
        if (cmf_compare_password($data['w_pwd'],$this->user['paypwd'])===false) {
            $this->error('您的密码不对');
        }
        // 积分是无法提现的 $this->user['score']
        if ($post['coin']>$this->user['coin']) {
            $this->error('余额不足');
        }
        $remain = bcsub($this->user['coin'], $post['coin']);
        $freeze = bcadd($this->user['freeze'], $post['coin']);

        // 数据库操作
        Db::startTrans();
        $transStatus = true;
        try{
            // ->setInc('freeze',$post['coin'])
            Db::name('user')->where('id',$this->user['id'])->setDec('coin',$post['coin']);
            Db::name('user')->where('id',$this->user['id'])->setInc('freeze',$post['coin']);
            // Db::name('funds_apply')->insert($post);
            $result = Db::name('funds_apply')->insertGetId($post);
            // lothar_put_funds_log($this->user['id'], 9, -$post['coin'], $remain);

            $log = model('usual/News')->newsObject('withdraw', $result, $this->user['id']);
            lothar_put_news($log);
            // 提交事务
            Db::commit();
        } catch (\Exception $e) {
            // 回滚事务
            Db::rollback();
            $transStatus = false;
        }

        if (empty($transStatus)) {
            $this->error('提交失败了');
        } else {
            // $userNew   = Db::name('user')->where('id',$this->user['id'])->find();
            $this->user['coin'] = $remain;
            $this->user['freeze'] = $freeze;
            cmf_update_current_user($this->user);
            $this->success('提交成功',url('withdrawView',['id'=>$result]));
        }
    }
    public function withdrawView()
    {
        $id = $this->request->param('id',0,'intval');
        $apply = Db::name('funds_apply')->where('id',$id)->find();
        if (empty($apply)) {
            abort(404, ' 数据不存在!');
        }

        $this->assign('apply',$apply);

        return $this->fetch('withdraw_view');
    }
    public function withdrawReset()
    {
        $id = $this->request->param('id',0,'intval');
        if (empty($id)) {
            $this->error('数据非法！');
        } else {
            Db::nmae('funds_apply')->where('id',$id)->setField('status',0);
            $this->success('数据重置成功',url('Funds/withdraw'));
        }
    }
    public function withdrawCancel()
    {
        $id = $this->request->param('id',0,'intval');
        if (empty($id)) {
            $this->error('数据非法！');
        } else {
            $coin = Db::name('funds_apply')->where('id',$id)->value('coin');
            bcscale(2);
            $remain = bcadd($coin,$this->user['coin']);
            $freeze = bcsub($this->user['freeze'], $coin);
            // 更改数据
            Db::startTrans();
            $transStatus = true;
            try{
                // ->setDec('freeze',$coin)
                Db::name('user')->where('id',$this->user['id'])->setInc('coin',$coin);
                Db::name('user')->where('id',$this->user['id'])->setDec('freeze',$coin);
                Db::name('funds_apply')->where('id',$id)->setField('status',-2);
                // lothar_put_funds_log($this->user['id'], -9, $coin, $remain);
                Db::commit();
            } catch (\Exception $e) {
                Db::rollback();
                $transStatus = false;
            }

            if (empty($transStatus)) {
                $this->error('取消失败了');
            } else {
                $this->user['coin'] = $remain;
                $this->user['freeze'] = $freeze;
                cmf_update_current_user($this->user);
                $this->success('取消成功，钱款被退回',url('user/Funds/index',['type'=>-9]));
            }
        }
    }

    /*
    * 申请列表
    * @param $type=withdraw|openshop
    */
    public function apply()
    {
        $status = $this->request->param('status/d');
        $type = $this->request->param('type','','strval');

        $where = [
            'user_id'=>$this->user['id'],
        ];
        if (isset($status)) {
            $where['status'] = $status;
        }
        if (!empty($type)) {
            $where['type'] = $type;
        } else {
            $where['type'] = ['in','withdraw,openshop'];
        }
        $list = Db::name('funds_apply')->where($where)->order('id','DESC')->paginate(15);

        $this->assign('list', $list);
        $this->assign('pager', $list->render());

        return $this->fetch();
    }
    // 支付押金
    public function applyPay()
    {
        return '预留';
    }




    public function cancel()
    {
        return $this->fetch();
    }

    public function del()
    {
        parent::dels(Db::name('user_funds_log'));
        $this->success("刪除成功！", '');
    }

    // 更多……  保留代码
    public function more()
    {
        return $this->fetch();
    }
}