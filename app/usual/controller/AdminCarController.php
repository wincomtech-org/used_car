<?php
namespace app\usual\controller;

use cmf\controller\AdminBaseController;
use app\usual\model\UsualCarModel;
use app\usual\model\UsualBrandModel;
// use app\usual\model\UsualItemModel;
// use app\admin\model\DistrictModel;
use app\trade\model\TradeReportCateModel;
use think\Db;

/**
* 车辆模块
* 二手车模块
*/
class AdminCarController extends AdminBaseController
{
    function _initialize()
    {
        parent::_initialize();
        $this->Model = new UsualCarModel();
    }

    /**
     * 车辆列表
     * @adminMenu(
     *     'name'   => '车辆管理',
     *     'parent' => 'usual/AdminCar/default',
     *     'display'=> true,
     *     'hasView'=> true,
     *     'order'  => 10000,
     *     'icon'   => '',
     *     'remark' => '车辆列表',
     *     'param'  => ''
     * )
     */
    public function index()
    {
        $param = $this->request->param();//接收筛选条件
        $brandId = $this->request->param('brandId',0,'intval');
        $param['plat'] = 2;

        $data        = $this->Model->getLists($param);

        $cateModel  = new UsualBrandModel();
        $brandTree   = $cateModel->adminCategoryTree($brandId);

        $this->assign('start_time', isset($param['start_time']) ? $param['start_time'] : '');
        $this->assign('end_time', isset($param['end_time']) ? $param['end_time'] : '');
        $this->assign('keyword', isset($param['keyword']) ? $param['keyword'] : '');
        $this->assign('brand_tree', $brandTree);
        $this->assign('articles', $data->items());
        $data->appends($param);
        $this->assign('pager', $data->render());

        return $this->fetch();
    }

    /**
     * 添加车辆
     * @adminMenu(
     *     'name'   => '添加车辆',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> true,
     *     'order'  => 10000,
     *     'icon'   => '',
     *     'remark' => '添加车辆',
     *     'param'  => ''
     * )
     */
    public function add()
    {
        $Brands = model('UsualBrand')->getBrands();
        $Models = model('UsualModels')->getModels();
        $Series = model('UsualSeries')->getSeries();
        $provId = $this->request->param('provId',1,'intval');
        $Provinces = model('admin/District')->getDistricts(0,$provId);
        // 车源类别
        $Types = $this->Model->getCarType();

        // 用于前台车辆条件筛选且与属性表name同值的字段码
        $searchCode = model('UsualItem')->getItemSearch();
        // dump($searchCode);die;
        // 从属性表里被推荐的
        $recItems = model('UsualItem')->getItemTable('is_rec',1);
        // 属性表里所有属性（不包含推荐的）
        $allItems = model('UsualItem')->getItemTable(null,'',true);
        // 检测报告
        $reportModel = new TradeReportCateModel();
        $reportCateTree = $reportModel->getCateTree();

        // 开店资料审核 config('verify_define_data');
        // 售卖状态
        $sell_status = $this->Model->getSellStatus();

        $this->assign('Brands', $Brands);
        $this->assign('Models', $Models);
        $this->assign('Series', $Series);
        $this->assign('Types', $Types);
        $this->assign('Provinces', $Provinces);

        $this->assign('searchCode', $searchCode);
        $this->assign('recItems', $recItems);
        $this->assign('allItems', $allItems);
        $this->assign('reportCateTree', $reportCateTree);

        $this->assign('sell_status', $sell_status);

        return $this->fetch();
    }

    /**
     * 添加车辆提交
     * @adminMenu(
     *     'name'   => '添加车辆提交',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 10000,
     *     'icon'   => '',
     *     'remark' => '添加车辆提交',
     *     'param'  => ''
     * )
     */
    public function addPost()
    {
        if ($this->request->isPost()) {
            $data   = $this->request->param();
            $post = $data['post'];
            $post['user_id'] = cmf_get_current_admin_id();
            $more = $data['post']['more'];
            if (empty($post['serie_id'])) {
                $post['serie_id'] = $post['serie_pid'];
            }

            $post = model('UsualItem')->ItemMulti($post,$more);
            // $postadd= model('UsualItem')->ItemMulti($post,$more);
            // $post   = array_merge($post,$postadd);

            $post = $this->Model->identiStatus($post);

            // 验证
            $result = $this->validate($post, 'Car.add');
            if ($result !== true) {
                $this->error($result);
            }
            // 处理文件图片
            if (!empty($data['photo'])) {
                $post['more']['photos'] = $this->Model->dealFiles($data['photo']);
            }
            if (!empty($data['file'])) {
                $post['more']['files'] = $this->Model->dealFiles($data['file']);
            }
            // $post['report'] = $data['report'];

            // 事务处理
            // 提交车子数据
            $this->Model->adminAddArticle($post);

            $this->success('添加成功!', url('AdminCar/edit', ['id' => $this->Model->id]));
        }
    }

    /**
     * 编辑车辆
     * @adminMenu(
     *     'name'   => '编辑车辆',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> true,
     *     'order'  => 10000,
     *     'icon'   => '',
     *     'remark' => '编辑车辆',
     *     'param'  => ''
     * )
     */
    public function edit()
    {
        $id = $this->request->param('id', 0, 'intval');
        $post = $this->Model->getPost($id);
// dump($post['report']);die;
        $Brands = model('UsualBrand')->getBrands($post['brand_id']);
        $Models = model('UsualModels')->getModels($post['model_id']);
        $Series = model('UsualSeries')->getSeries($post['serie_id']);
        $Series2 = model('UsualSeries')->getSeries($post['serie_id'],0,2);
        $Provinces = model('admin/District')->getDistricts($post['province_id']);
        $Citys = model('admin/District')->getDistricts($post['city_id'],$post['province_id']);
        // 车源类别
        $Types = $this->Model->getCarType($post['type']);
        // 开店资料审核 config('verify_define_data');


        // 用于前台车辆条件筛选且与属性表name同值的字段码
        $searchCode = model('UsualItem')->getItemSearch();
        // 从属性表里被推荐的
        $recItems = model('UsualItem')->getItemTable('is_rec',1);
        // 属性表里所有属性（不包含推荐的）
        $allItems = model('UsualItem')->getItemTable(null,'',true);
        // 检测报告
        $reportModel = new TradeReportCateModel();
        $reportCateTree = $reportModel->getCateTree();
// dump($post);die;
        // 个人审核资料
        $verifyinfo = lothar_verify($post['user_id'],'openshop','all');
        // 售卖状态
        $sell_status = $this->Model->getSellStatus($post['sell_status']);

        $this->assign('verifyinfo',$verifyinfo);

        $this->assign('Brands', $Brands);
        $this->assign('Models', $Models);
        $this->assign('Series', $Series);
        $this->assign('Series2', $Series2);
        $this->assign('Provinces', $Provinces);
        $this->assign('Citys', $Citys);
        $this->assign('Types', $Types);

        $this->assign('searchCode', $searchCode);
        $this->assign('recItems', $recItems);
        $this->assign('allItems', $allItems);
        $this->assign('reportCateTree', $reportCateTree);
        $this->assign('reportIds', $post['report']);

        $this->assign('sell_status', $sell_status);
        $this->assign('post', $post);

        return $this->fetch();
    }

    /**
     * 编辑车辆提交
     * @adminMenu(
     *     'name'   => '编辑车辆提交',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 10000,
     *     'icon'   => '',
     *     'remark' => '编辑车辆提交',
     *     'param'  => ''
     * )
     */
    public function editPost()
    {
        if ($this->request->isPost()) {
            $data   = $this->request->param();
            $post = $data['post'];
            $more = $data['post']['more'];
            // $report = $data['post']['report'];

            if (empty($post['serie_id'])) {
                $post['serie_id'] = $post['serie_pid'];
            }
            $post = model('UsualItem')->ItemMulti($post,$more);
            $post = $this->Model->identiStatus($post);

            // 验证
            $result = $this->validate($post,'Car.edit');
            if ($result !== true) {
                $this->error($result);
            }
            // 处理文件图片
            if (!empty($data['photo'])) {
                $post['more']['photos'] = $this->Model->dealFiles($data['photo']);
            }
            if (!empty($data['file'])) {
                $post['more']['files'] = $this->Model->dealFiles($data['file']);
            }

            /*个人审核资料填写*/
            $verify = $data['verify'];
            // 直接拿官版的
            if (!empty($data['identity_card'])) {
                $verify['more']['identity_card'] = $this->Model->dealFiles($data['identity_card']);
            }
            // 验证数据的完备性
            $result = $this->validate($verify,'usual/Verify.openshop');

            // 事务处理？
            // 更新车子数据
            $this->Model->adminEditArticle($post);
            // 更新车主数据，如果审核通过，不予再审核
            if ($result===true) {
                if (empty($verify['id'])) {
                    // cmf_auth_check($verify['user_id']);
                    $verify['auth_code'] = 'openshop';
                    $verify['create_time'] = time();
                    model('usual/Verify')->adminAddArticle($verify);
                } else {
                    model('usual/Verify')->adminEditArticle($verify);
                }
            }

            $this->success('保存成功!');
        }
    }

    /**
     * 车辆删除 回收机制
     * @adminMenu(
     *     'name'   => '车辆删除',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 10000,
     *     'icon'   => '',
     *     'remark' => '车辆删除',
     *     'param'  => ''
     * )
     */
    public function delete()
    {
        $param           = $this->request->param();

        if (isset($param['id'])) {
            $id           = $this->request->param('id', 0, 'intval');
            $result       = $this->Model->where(['id' => $id])->find();
            $data         = [
                'object_id'   => $result['id'],
                'create_time' => time(),
                'table_name'  => 'UsualCar',
                'name'        => $result['name']
            ];
            $resultPortal = $this->Model
                ->where(['id' => $id])
                ->update(['delete_time' => time()]);
            if ($resultPortal) {
                Db::name('recycleBin')->insert($data);
            }
            $this->success("删除成功！", '');
        }

        if (isset($param['ids'])) {
            $ids     = $this->request->param('ids/a');
            $recycle = $this->Model->where(['id' => ['in', $ids]])->select();
            $result  = $this->Model->where(['id' => ['in', $ids]])->update(['delete_time' => time()]);
            if ($result) {
                foreach ($recycle as $value) {
                    $data = [
                        'object_id'   => $value['id'],
                        'create_time' => time(),
                        'table_name'  => 'UsualCar',
                        'name'        => $value['name']
                    ];
                    Db::name('recycleBin')->insert($data);
                }
                $this->success("删除成功！", '');
            }
        }
    }

    /**
     * 车辆发布
     * @adminMenu(
     *     'name'   => '车辆发布',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 10000,
     *     'icon'   => '',
     *     'remark' => '车辆发布',
     *     'param'  => ''
     * )
     */
    public function publish()
    {
        $param           = $this->request->param();

        if (isset($param['ids']) && isset($param["yes"])) {
            $ids = $this->request->param('ids/a');
            $this->Model->where(['id' => ['in', $ids]])->update(['status' => 1, 'published_time' => time()]);
            $this->success("发布成功！", '');
        }

        if (isset($param['ids']) && isset($param["no"])) {
            $ids = $this->request->param('ids/a');
            $this->Model->where(['id' => ['in', $ids]])->update(['status' => 0]);
            $this->success("隐藏成功！", '');
        }
    }

    /**
     * 车辆置顶
     * @adminMenu(
     *     'name'   => '车辆置顶',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 10000,
     *     'icon'   => '',
     *     'remark' => '车辆置顶',
     *     'param'  => ''
     * )
     */
    public function top()
    {
        $param           = $this->request->param();
        if (isset($param['ids']) && isset($param["yes"])) {
            $ids = $this->request->param('ids/a');
            $this->Model->where(['id' => ['in', $ids]])->update(['is_top' => 1]);
            $this->success("置顶成功！", '');

        }
        if (isset($param['ids']) && isset($param["no"])) {
            $ids = $this->request->param('ids/a');
            $this->Model->where(['id' => ['in', $ids]])->update(['is_top' => 0]);
            $this->success("取消置顶成功！", '');
        }
    }

    /**
     * 车辆推荐
     * @adminMenu(
     *     'name'   => '车辆推荐',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 10000,
     *     'icon'   => '',
     *     'remark' => '车辆推荐',
     *     'param'  => ''
     * )
     */
    public function recommend()
    {
        $param           = $this->request->param();
        if (isset($param['ids']) && isset($param["yes"])) {
            $ids = $this->request->param('ids/a');
            $this->Model->where(['id' => ['in', $ids]])->update(['is_rec' => 1]);
            $this->success("推荐成功！", '');

        }
        if (isset($param['ids']) && isset($param["no"])) {
            $ids = $this->request->param('ids/a');
            $this->Model->where(['id' => ['in', $ids]])->update(['is_rec' => 0]);
            $this->success("取消推荐成功！", '');

        }
    }

    /**
     * 车辆认证
     * @adminMenu(
     *     'name'   => '车辆认证',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 10000,
     *     'icon'   => '',
     *     'remark' => '车辆认证',
     *     'param'  => ''
     * )
     */
    public function audit()
    {
        # code...
    }

    /**
     * 通用排序
     * @adminMenu(
     *     'name'   => '通用排序',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 10000,
     *     'icon'   => '',
     *     'remark' => '通用排序',
     *     'param'  => ''
     * )
     */
    public function listOrder()
    {
        parent::listOrders(Db::name('UsualCar'));
        $this->success("排序更新成功！", '');
    }

    public function move()
    {

    }

    public function copy()
    {

    }

}