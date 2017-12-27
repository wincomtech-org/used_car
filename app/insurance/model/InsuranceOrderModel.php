<?php
namespace app\insurance\model;

use think\Db;
use app\insurance\model\InsuranceModel;

class InsuranceOrderModel extends InsuranceModel
{
    public function getLists($filter=[], $order='', $limit='',$extra=[])
    {
        $field = 'a.*,b.name insurance_name,c.name car_name,d.user_login';
        $where = ['a.delete_time' => 0];
        $join = [
            ['insurance b','a.insurance_id=b.id','LEFT'],
            ['usual_car c','a.car_id=c.id','LEFT'],
            ['user d','a.user_id=d.id','LEFT']
        ];

        if (!empty($extra)) {
            $where = array_merge($where,$extra);
        }

        // 保单状态
        if (!empty($filter['status'])) {
            $where['a.status'] = $filter['status'];
        }
        // 所属保险
        if (!empty($filter['insuranceId'])) {
            $where['a.insurance_id'] = intval($filter['insuranceId']);
        }
        // 所属公司
        if (!empty($filter['compId'])) {
            $where['a.company_id'] = intval($filter['compId']);
        }
        // 创建时间
        $startTime = empty($filter['start_time']) ? 0 : strtotime($filter['start_time']);
        $endTime   = empty($filter['end_time']) ? 0 : strtotime($filter['end_time']);
        if (!empty($startTime) && !empty($endTime)) {
            $where['a.create_time'] = [['>= time', $startTime], ['<= time', $endTime]];
        } else {
            if (!empty($startTime)) {
                $where['a.create_time'] = ['>= time', $startTime];
            }
            if (!empty($endTime)) {
                $where['a.create_time'] = ['<= time', $endTime];
            }
        }
        // 用户ID
        if (!empty($filter['user_id'])) {
            $where['a.user_id'] = intval($filter['user_id']);
        }
        // 用户
        $uname = empty($filter['uname']) ? '' : $filter['uname'];
        if (!empty($uname)) {
            $uid = intval($uname);
            if (empty($uid)) {
                $uid = Db::name('user')->whereOr(['user_nickname|user_login|user_email|mobile'=>$uname])->value('id');
                $uid = intval($uid);
            }
            $where['a.user_id'] = $uid;
        }
        // 车牌号 plateNo
        // 保单号
        $sn = empty($filter['sn']) ? '' : $filter['sn'];
        if (!empty($sn)) {
            $where['a.order_sn'] = ['like', "%$sn%"];
        }

        $series = $this->alias('a')->field($field)
            ->join($join)
            ->where($where)
            ->order('a.id DESC')
            ->paginate(config('pagerset.size'));

        return $series;
    }

    public function getPost($id)
    {
        // $post = $this->get($id)->toArray();
        $field = 'a.*,b.name insurance_name,c.name car_name,d.user_login';
        // $where = ['a.id' => $id];
        $join = [
            ['insurance b','a.insurance_id=b.id','LEFT'],
            ['usual_car c','a.car_id=c.id','LEFT'],
            ['user d','a.user_id=d.id','LEFT']
        ];
        $post = $this->alias('a')
            ->field($field)
            ->join($join)
            ->where('a.id',$id)
            ->find();

        return $post;
    }

    public function getOrderStatus($status='')
    {
        return $this->getStatus($status,'insurance_order_status');
    }


}