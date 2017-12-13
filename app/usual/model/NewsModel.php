<?php
namespace app\usual\model;

// use app\usual\model\UsualModel;
use think\Model;
use think\Db;

class NewsModel extends Model
{
    // protected $type = [
    //     'more' => 'array',
    // ];
    // 开启自动写入时间戳字段
    // protected $autoWriteTimestamp = true;
    // function setCreateTimeAttr($value){ return strtotime($value);}

    public function getLists($filter=[], $order='', $limit='',$extra=[])
    {
        $field = 'id,user_id,deal_uid,title,object,action,app,create_time,content,status';

        // 筛选条件
        $where = [];
        if (!empty($extra)) {
            $where = array_merge($where,$extra);
        }
        // 更多
        if (!empty($filter['status'])) {
            $where['status'] = $filter['status'];
        }
        if (!empty($filter['appId'])) {
            $where['app'] = $filter['appId'];
        }
        if (!empty($filter['userId'])) {
            $where['user_id'] = $filter['userId'];
        }
        $startTime = empty($filter['start_time']) ? 0 : strtotime($filter['start_time']);
        $endTime   = empty($filter['end_time']) ? 0 : strtotime($filter['end_time']);
        if (!empty($startTime) && !empty($endTime)) {
            $where['create_time'] = [['>= time', $startTime], ['<= time', $endTime]];
        } else {
            if (!empty($startTime)) {
                $where['create_time'] = ['>= time', $startTime];
            }
            if (!empty($endTime)) {
                $where['create_time'] = ['<= time', $endTime];
            }
        }
        $keyword = empty($filter['keyword']) ? '' : $filter['keyword'];
        if (!empty($keyword)) {
            $where['title'] = ['like', "%$keyword%"];
        }

        // 排序
        $order = empty($order) ? 'id DESC' : $order;

        // 数据量
        $limit = empty($limit) ? config('pagerset.size') : $limit;

        // 查数据
        $series = $this->field($field)
            ->where($where)
            ->order($order)
            ->paginate($limit);

        return $series;
    }

    // 获取单条数据
    public function getPost($id)
    {
        $field = 'a.*,d.user_nickname,d.user_login';
        $join = [
            ['user d','a.user_id=d.id','LEFT'],
            // ['user e','a.deal_uid=e.id','LEFT'],
        ];
        $data = $this->alias('a')
            ->field($field)
            ->join($join)
            ->where('a.id',$id)
            ->find();
        $data['username'] = $data['user_nickname'] ? $data['user_nickname'] : $data['user_login'];
        if (!empty($data['object'])) {
            # insurance_order:1
            $objId = explode(':',$data['object'])[1];
            $data['objurl'] = cmf_url( $data['adminurl'], ['id'=>$objId] );
        }

        return $data;
    }

    public function newsCounts($status='')
    {
        if (empty($status)) {
            $count[0] = Db::name('news')->where('status',0)->count();
            $count[1] = Db::name('news')->where('status',1)->count();
            $count[2] = Db::name('news')->where('status',2)->count();
        } elseif ($status==0) {
            $count[0] = Db::name('news')->where('status',0)->count();
            $count[1] = $count[2] = 0;
        } elseif ($status==1) {
            $count[1] = Db::name('news')->where('status',1)->count();
            $count[0] = $count[2] = 0;
        } elseif ($status==2) {
            $count[2] = Db::name('news')->where('status',2)->count();
            $count[0] = $count[1] = 0;
        }

        return $count;
    }

    // 选择框
    public function cateOptions($selectId=null, $option='请选择')
    {
        $data = $this->distinct(true)->field('app')->select()->toArray();
        $setName = [
            'trade'     => '车辆买卖',
            'insurance' => '保险模块',
            'service'   => '车辆业务',
            'register'  => '注册',
            'user'  => '用户中心',
            'funds'  => '资金管理',
        ];

        if ($option===false) {
            return $data;
        } else {
            $options = (empty($option)) ? '':'<option value="">--'.$option.'--</option>';
            if (is_array($data)) {
                foreach ($data as $row) {
                    $options .= '<option value="'.$row['app'].'" '.($selectId==$row['app']?'selected':'').' >'.$setName[$row['app']].'</option>';
                }
            }
            return $options;
        }
    }

    public function getStatus($status='',$config=null)
    {
        $ufoconfig = empty($config) ? [0=>'未读',1=>'已读',2=>'已处理'] : config($config);
        $options = '';
        foreach ($ufoconfig as $key => $vo) {
            $options .= '<option value="'.$key.'" '.($status==$key?'selected':'').'>'.$vo.'</option>';
        }

        return $options;
    }

}