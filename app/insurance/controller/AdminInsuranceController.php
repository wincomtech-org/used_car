<?php
namespace app\insurance\controller;

use cmf\controller\AdminBaseController;
// use app\insurance\model\InsuranceModel;
// use app\usual\model\UsualCategoryModel;
use think\Db;

class AdminInsuranceController extends AdminBaseController
{
    // function _initialize()
    // {
    //     parent::_initialize();
    // }

    public function index()
    {
        $param = $this->request->param();//接收筛选条件
        $companyId = $this->request->param('companyId',0,'intval');

        $data = model('Insurance')->getLists($param);
        $companys = model('usual/UsualCompany')->getCompanys($companyId);

        $this->assign('start_time', isset($param['start_time']) ? $param['start_time'] : '');
        $this->assign('end_time', isset($param['end_time']) ? $param['end_time'] : '');
        $this->assign('keyword', isset($param['keyword']) ? $param['keyword'] : '');
        $this->assign('companys', $companys);
        $this->assign('companyId', $companyId);
        $this->assign('lists', $data->items());
        $data->appends($param);
        $this->assign('pager', $data->render());

        return $this->fetch();
    }

    public function add()
    {
        $companys = model('usual/UsualCompany')->getCompanys();
        $coverages = model('InsuranceCoverage')->getCoverage();

        $this->assign('companys', $companys);
        $this->assign('coverages', $coverages);
        return $this->fetch();
    }
    public function addPost()
    {
        if ($this->request->isPost()) {
            $data   = $this->request->param();
            // $data   = $_POST;
            $post   = $data['post'];
            $result = $this->validate($post,'Insurance.add');
            if ($result !== true) {
                $this->error($result);
            }
            model('Insurance')->adminAddArticle($post);

            // 钩子
            // $post['id'] = model('Insurance')->id;
            // $hookParam          = [
            //     'is_add'  => true,
            //     'article' => $post
            // ];
            // hook('portal_admin_after_save_article', $hookParam);

            $this->success('添加成功!', url('AdminInsurance/edit', ['id' => model('Insurance')->id]));
        }
    }

    public function edit()
    {
        $id = $this->request->param('id', 0, 'intval');
        $post = model('Insurance')->getPost($id);
        // $post = model('Insurance')->where('id', $id)->find();
        $companys = model('usual/UsualCompany')->getCompanys($post['company_id']);
        $coverageIds = empty($post['more']['coverage'])?[]:$post['more']['coverage'];
        $coverages = model('InsuranceCoverage')->getCoverage($coverageIds);

        $this->assign('companys', $companys);
        $this->assign('coverages', $coverages);
        $this->assign('post', $post);
        return $this->fetch();
    }
    public function editPost()
    {
        if ($this->request->isPost()) {
            $data   = $this->request->param();
            // $data   = $_POST;
            // dump($data);die;
            $post   = $data['post'];
            $result = $this->validate($post, 'Insurance.edit');
            if ($result !== true) {
                $this->error($result);
            }
            $p=model('Insurance')->adminEditArticle($post);

            // 钩子
            // $hookParam = [
            //     'is_add'  => false,
            //     'article' => $post
            // ];
            // hook('portal_admin_after_save_article', $hookParam);

            $this->success('保存成功!');
        }
    }

    // 删除 回收机制
    public function delete()
    {
        $param = $this->request->param();

        if (isset($param['id'])) {
            $id           = $this->request->param('id', 0, 'intval');
            $resultPortal = model('Insurance')
                ->where(['id' => $id])
                ->update(['delete_time' => time()]);
            if ($resultPortal) {
                $result       = model('Insurance')->where(['id' => $id])->find();
                $data         = [
                    'object_id'   => $result['id'],
                    'create_time' => time(),
                    'table_name'  => 'Insurance',
                    'name'        => $result['name']
                ];
                Db::name('recycleBin')->insert($data);
            }
            $this->success("删除成功！", '');
        }

        if (isset($param['ids'])) {
            $ids     = $this->request->param('ids/a');
            $recycle = model('Insurance')->where(['id' => ['in', $ids]])->select();
            $result  = model('Insurance')->where(['id' => ['in', $ids]])->update(['delete_time' => time()]);
            if ($result) {
                foreach ($recycle as $value) {
                    $data = [
                        'object_id'   => $value['id'],
                        'create_time' => time(),
                        'table_name'  => 'Insurance',
                        'name'        => $value['name']
                    ];
                    Db::name('recycleBin')->insert($data);
                }
                $this->success("删除成功！", '');
            }
        }
    }

    public function publish()
    {
        $param           = $this->request->param();

        if (isset($param['ids']) && isset($param["yes"])) {
            $ids = $this->request->param('ids/a');
            model('Insurance')->where(['id' => ['in', $ids]])->update(['status' => 1, 'published_time' => time()]);
            $this->success("启用成功！", '');
        }

        if (isset($param['ids']) && isset($param["no"])) {
            $ids = $this->request->param('ids/a');
            model('Insurance')->where(['id' => ['in', $ids]])->update(['status' => 0]);
            $this->success("禁用成功！", '');
        }
    }
    public function top()
    {
        $param           = $this->request->param();
        if (isset($param['ids']) && isset($param["yes"])) {
            $ids = $this->request->param('ids/a');
            model('Insurance')->where(['id' => ['in', $ids]])->update(['is_top' => 1]);
            $this->success("置顶成功！", '');

        }
        if (isset($param['ids']) && isset($param["no"])) {
            $ids = $this->request->param('ids/a');
            model('Insurance')->where(['id' => ['in', $ids]])->update(['is_top' => 0]);
            $this->success("取消置顶成功！", '');
        }
    }
    public function recommend()
    {
        $param           = $this->request->param();

        if (isset($param['ids']) && isset($param["yes"])) {
            $ids = $this->request->param('ids/a');
            model('Insurance')->where(['id' => ['in', $ids]])->update(['is_rec' => 1]);
            $this->success("推荐成功！", '');

        }
        if (isset($param['ids']) && isset($param["no"])) {
            $ids = $this->request->param('ids/a');
            model('Insurance')->where(['id' => ['in', $ids]])->update(['is_rec' => 0]);
            $this->success("取消推荐成功！", '');

        }
    }


    public function listOrder()
    {
        parent::listOrders(Db::name('Insurance'));
        $this->success("排序更新成功！", '');
    }

    public function move()
    {

    }

    public function copy()
    {

    }
}