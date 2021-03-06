<?php
namespace app\service\controller;

use cmf\controller\HomeBaseController;
use app\portal\service\PostService;
use app\service\model\ServiceCategoryModel;
// use app\usual\model\UsualCoordinateModel;
// use app\admin\model\DistrictModel;
use think\Db;
use think\Validate;

class IndexController extends HomeBaseController
{
    function _initialize()
    {
        parent::_initialize();
        // $crumbs = $this->getCrumbs();
        // $this->assign('crumbs',$crumbs);
    }

    // 服务列表
    public function index()
    {
        $extra = ['status'=>1];//,'platform'=>2

        $scModel = new ServiceCategoryModel();
        $services = $scModel->getLists('','','',$extra);
        $noobInfo = $scModel->getPost(1);

        $postService = new PostService();
        $articles = $postService->fromCateList(9,5);

        $this->assign('services',$services);
        $this->assign('noobInfo',$noobInfo);
        $this->assign('articles',$articles);
        return $this->fetch();
    }

    // 服务 platform=1自营的独立页面
    public function step1()
    {
        if (!cmf_is_user_login()) {
            $this->error('请登录',url('user/Login/index'));
        }

        $servId = $this->request->param('id/d',0);

        if (empty($servId)) {
            $this->error('数据非法！');
        }

        $scModel = new ServiceCategoryModel();
        $page = $scModel->getPost($servId);
        if (empty($page)) {
            abort(404,'数据不存在！');
        }

        $define_data = $page['define_data'];
        if (!empty($define_data)) {
            $new_data = $scModel->getDefineData($define_data,false);
            $this->assign('new_data',$new_data);
            if (in_array('service_point',$define_data)) {
                $provinces = model('admin/District')->getDistricts(10,1);
                $citys = model('admin/District')->getDistricts(146,10);
                $servicePoint = model('usual/UsualCoordinate')->getCoordinates(0, ['sc_id'=>$servId], false);
                $this->assign('provinces', $provinces);
                $this->assign('citys', $citys);
                $this->assign('servicePoint',$servicePoint);
                $this->assign('servicePointJson',json_encode($servicePoint));
            }
        }

        $this->assign('page',$page);

        return $this->fetch('step1_'. $page['platform']);
    }


    // 服务 platform=1提交
    public function step1Post()
    {
        # code...
    }

    public function appointPost()
    {
        if (!cmf_is_user_login()) {
            $this->error('请登录',url('user/Login/index'));
        }

        // 获取数据
        $data = $this->request->param();
        $userId = cmf_get_current_user_id();
        if (empty($data)) {
            $this->error('数据为空！',url('Index/index'));
        }

        $post = $data['post'];//包括单图
        $post['user_id'] = $userId;
        // $more = $data['more'];//省份、城市

        // 所属模型的数据
        $servCates = Db::name('service_category')->field('platform,name,price,is_pay,define_data')->where('id',$post['model_id'])->find();

        // 防止重复提交 / 查重
        /*if (!empty($post['plateNo'])) {
            $find = Db::name('service')->field('id,model_id,user_id')->where('plateNo',$post['plateNo'])->find();
            if (!empty($find)) {
                if ($find['user_id']!=$userId) {
                    $this->error('该车牌号已被其他用户占领：用户ID：'.$find['user_id'],null,'',5);
                }
                // 查重 依据 用户ID、车牌号、业务模型、业务状态、今日？
                
                // if ($servCates['platform']==1) {
                //     if (empty($find['company_id'])) {
                //         $this->error('去选公司',url('Index/step2',['id'=>$find['id']]));
                //     } else {
                //         $this->error('请不要重复提交！');
                //     }
                // } else {
                //     $this->error('您已提交过');
                // }
                // $this->error('您已提交过');
            }
        }*/

        // 数据验证
        $rule = [
            'model_id' => 'require|number',
            'user_id' => 'integer',
            'username|姓名' => 'chsAlpha|max:32',
            'telephone' => 'require',
            'birthday|生日' => 'dateFormat:Y-m-d|after:-88 year|before:-1 day',
            'seller_name|卖家姓名' => 'chsAlpha|max:32',
            'plateNo' => 'require',
            'car_vin' => 'require',
            'reg_time|注册日期' => 'dateFormat:Y-m-d H:i|before:-1 day',
            'appoint_time|预约时间' => 'dateFormat:Y-m-d H:i|after:30 minute',
            'service_point' => 'require',
            'contact' => 'require',
            'contact' => 'require',
            'address' => 'require',
            'seller_contact' => 'require',
            'seller_birthday' => 'require',
            'more.driving_license' => 'require',
            'more.qualified' => 'require',
            'more.loan_invoice' => 'require',
            'car_mileage' => 'require',
            'tire_size' => 'require',
        ];
        // 筛选需要验证的字段
        $filter_var = json_decode($servCates['define_data']);
        $all_var = array_keys(config('service_define_data'));
        foreach ($all_var as $value) {
            if (!in_array($value,$filter_var)) {
                // if (isset($rule[$value])) {
                    unset($rule[$value]);
                    unset($rule['more.'.$value]);
                // }
            }
        }

        // 开始验证
        $validate = new Validate();
        $validate->rule($rule);
        $validate->message([
            'model_id.require' => '模型数据丢失',
            'model_id.number' => '模型数据非法',
            'user_id.integer' => '您的数据不合法!',
            'username.chsDash' => '姓名只能是汉字、字母',
            'username.max' => '姓名最大长度为32个字符',
            'telephone.require' => '电话必填',
            'birthday.dateFormat' => '生日格式不正确',
            'birthday.after' => '出生日期也太早了吧？',
            'birthday.before' => '出生日期也太晚了吧？',
            'seller_name.chsDash' => '姓名只能是汉字、字母',
            'seller_name.max' => '姓名最大长度为32个字符',
            'plateNo.require' => '请填写车牌号',
            // 'plateNo.isPlateNo' => '车牌号格式不对',
            'car_vin.require' => '请填写车架号',
            'reg_time.dateFormat' => '注册日期格式不正确',
            'reg_time.before' => '注册日期太晚了吧？',
            'appoint_time.dateFormat' => '预约时间格式不正确',
            'appoint_time.after' => '预约时间也太早了吧？',
            'service_point.require' => '服务点未选择',
            'contact' => '请填写联系方式',
            'address' => '请填写详情地址',
            'seller_contact' => '请填写联系方式',
            'seller_birthday' => '请填写卖家生日',
            'more.driving_license.require' => '请上传行驶证',
            'more.qualified.require' => '请上传合格证',
            'more.loan_invoice.require' => '请上传贷款发票',
            'car_mileage.require' => '请填写公里数',
            'tire_size.require' => '请填写轮胎大小',
        ]);
        if (!$validate->check($post)) {
            $this->error($validate->getError());
            // $this->error($validate->getError(),url('Index/step1',['id'=>$post['model_id']]));
        }

        // 处理图片 直接拿官版的 如何检测用户未上传？
        if (!empty($data['identity_card'])) {
            $post['more']['identity_card'] = lothar_dealFiles($data['identity_card']);
        }
        // 处理服务点
        // if (!empty($post['service_point'])) {
        //     $coord = Db::name('usual_coordinate')->where('id',$post['service_point'])->find();
        // }
        // 服务费
        $post['order_amount'] = $servCates['price'];

        // 提交 事务处理
        $extra = [
            'service_point' => empty($post['service_point'])?'':$post['service_point'],
            'name'          => $servCates['name']
        ];
        Db::startTrans();
        $transStatus = true;
        try{
            $id = model('Service')->addAppoint($post);
            // 服务点ID service_point
            $log = model('usual/News')->newsObject('service',$id,$userId,$extra);
            lothar_put_news($log);
            Db::commit();
        } catch (\Exception $e) {
            Db::rollback();
            $transStatus = false;
        }

        if ($transStatus===false) {
            $this->error('提交失败');
        }

        // 在这里判断是否需要支付 是否生成订单号 order_sn ？ 
        if ($servCates['is_pay']==1) {
            $payUrl = url('service/Order/pay',['id'=>$id,'modelId'=>$post['model_id'],'name'=>$servCates['name'],'order_amount'=>$servCates['price']]);
            $this->success('去支付……',$payUrl);
        } else {
            $this->success('提交成功，请等待工作人员回复',url('user/Service/details',['id'=>$id,'mid'=>$post['model_id']]));
            // $this->success('提交成功，请等待工作人员回复',url('user/Service/index',['mid'=>$post['model_id']]));
        }
        
        // if ($servCates['platform']==1) {
        //     $this->success('提交成功，请等待工作人员回复',url('user/Service/index',['mid'=>$post['model_id']]));
        // } else {
        //     $this->success('正在帮您转入选公司页面……',url('service/Index/step2',['id'=>$id]));
        // }
    }




/*选公司这段已废除，业务逻辑将根据服务点确定公司*/
    // 进入选公司
    public function step2()
    {
        $id = $this->request->param('id',0,'intval');
        $extra = ['is_yewu'=>1];
        $companys = model('usual/UsualCompany')->getPostList($extra);

        $this->assign('id',$id);
        $this->assign('companys',$companys);
        return $this->fetch();
    }
    // 选公司后提交
    public function step3()
    {
        $id = $this->request->param('id',0,'intval');
        $compId = $this->request->param('compId',0,'intval');
        if (empty($id)) {
            $this->error('非法数据！');
        }
        $modelId = Db::name('service')->where('id',$id)->value('model_id');

        $result = Db::name('service')->where('id',$id)->setField('company_id',$compId);
        $this->success('提交成功,请耐心等待工作人员处理',url('user/Service/index',['mid'=>$modelId]));
    }


}