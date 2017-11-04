<?php
namespace app\usual\controller;

use cmf\controller\AdminBaseController;
use think\Db;
// use app\usual\model\VerifyModel;
// use think\Config;

/**
* 认证模块
*/
class AdminVerifyController extends AdminBaseController
{
    /*function _initialize()
    {
        // parent::_initialize();
        // dump(config('database.database'));
        // dump(config('Verify_status'));
    }*/

    public function index()
    {
        $param = $this->request->param();//接收筛选条件
        $auth_code = $this->request->param('auth_code');
        $auth_status = $this->request->param('auth_status',0,'intval');

        $data = model('Verify')->getLists($param);
        $data->appends($param);
        $categoryTree = model('VerifyModel')->getOptions($auth_code);
        $statusTree = model('Verify')->getVerifyStatus($auth_status);

        $this->assign('start_time', isset($param['start_time']) ? $param['start_time'] : '');
        $this->assign('end_time', isset($param['end_time']) ? $param['end_time'] : '');
        $this->assign('uname', isset($param['uname']) ? $param['uname'] : '');
        $this->assign('category_tree', $categoryTree);
        $this->assign('status_tree', $statusTree);
        $this->assign('lists', $data->items());
        $this->assign('page', $data->render());

        return $this->fetch();
    }

    public function add()
    {
        $this->assign('category_tree', model('VerifyModel')->getOptions(0,0,0,true));
        $this->assign('define_data',model('VerifyModel')->getDefineData('',''));
        $this->assign('status_tree', model('Verify')->getVerifyStatus());
        return $this->fetch();
    }
    public function addPost()
    {
        if ($this->request->isPost()) {
            $data   = $this->request->param();
            $post   = $data['post'];
            if (empty($post['user_id'])) {
                $username = $this->request->param('username/s');
                $user_id = Db::name('user')->whereOr(['user_nickname|user_login|user_email|mobile'=>['eq', $username]])->value('id');
                if (empty($user_id)) {
                    $this->error('系统未检测到该用户');
                }
                $post['user_id'] = intval($user_id);
            }

            $result = $this->validate($post,'Verify.add');
            if ($result !== true) {
                $this->error($result);
            }
            model('Verify')->adminAddArticle($post);

            // 钩子
            // $post['id'] = model('Verify')->id;
            // $hookParam          = [
            //     'is_add'  => true,
            //     'article' => $post
            // ];
            // hook('portal_admin_after_save_article', $hookParam);

            $this->success('添加成功!', url('AdminVerify/edit', ['id'=>model('Verify')->id]));
        }
    }

    public function edit()
    {
        $id = $this->request->param('id', 0, 'intval');
        $post = model('Verify')->getPost($id);

        $this->assign('category_tree', model('VerifyModel')->getOptions($post['auth_code'],0,0,true));
        $this->assign('define_data',model('VerifyModel')->getDefineData('',''));
        $this->assign('status_tree', model('Verify')->getVerifyStatus($post['auth_status']));
        $this->assign('post', $post);
        return $this->fetch();
    }
    public function editPost()
    {
        if ($this->request->isPost()) {
            $data   = $this->request->param();
            $username = $this->request->param('username/s');
            $user_id = Db::name('user')->whereOr(['user_nickname|user_login|user_email|mobile'=>['eq', $username]])->value('id');
            if (empty($user_id)) {
                $this->error('系统未检测到该用户');
            }

            $post   = $data['post'];
            $post['user_id'] = intval($user_id);
            $result = $this->validate($post, 'Verify.edit');
            if ($result !== true) {
                $this->error($result);
            }

            // $post['more']['photos'] = model('Verify')->dealFiles(['names'=>$data['photo_names'],'urls'=>$data['photo_urls']]);
            // $post['more']['files'] = model('Verify')->dealFiles(['names'=>$data['file_names'],'urls'=>$data['file_urls']]);

            model('Verify')->adminEditArticle($post);

            $this->success('保存成功!');
        }
    }

    public function delete()
    {
        $param = $this->request->param();

        if (isset($param['id'])) {
            $id           = $this->request->param('id', 0, 'intval');
            $resultPortal = model('Verify')
                ->where(['id' => $id])
                ->update(['delete_time' => time()]);
            if ($resultPortal) {
                $result       = model('Verify')->where(['id' => $id])->find();
                $data         = [
                    'object_id'   => $result['id'],
                    'create_time' => time(),
                    'table_name'  => 'Verify',
                    'name'        => $result['order_sn']
                ];
                Db::name('recycleBin')->insert($data);
            }
            $this->success("删除成功！", '');
        }

        if (isset($param['ids'])) {
            $ids     = $this->request->param('ids/a');
            $recycle = model('Verify')->where(['id' => ['in', $ids]])->select();
            $result  = model('Verify')->where(['id' => ['in', $ids]])->update(['delete_time' => time()]);
            if ($result) {
                foreach ($recycle as $value) {
                    $data = [
                        'object_id'   => $value['id'],
                        'create_time' => time(),
                        'table_name'  => 'Verify',
                        'name'        => $value['order_sn']
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
            model('Verify')->where(['id' => ['in', $ids]])->update(['status' => 1, 'published_time' => time()]);
            $this->success("启用成功！", '');
        }

        if (isset($param['ids']) && isset($param["no"])) {
            $ids = $this->request->param('ids/a');
            model('Verify')->where(['id' => ['in', $ids]])->update(['status' => 0]);
            $this->success("禁用成功！", '');
        }
    }
    public function top()
    {
        $param           = $this->request->param();
        if (isset($param['ids']) && isset($param["yes"])) {
            $ids = $this->request->param('ids/a');
            model('Verify')->where(['id' => ['in', $ids]])->update(['is_top' => 1]);
            $this->success("置顶成功！", '');

        }
        if (isset($_POST['ids']) && isset($param["no"])) {
            $ids = $this->request->param('ids/a');
            model('Verify')->where(['id' => ['in', $ids]])->update(['is_top' => 0]);
            $this->success("取消置顶成功！", '');
        }
    }
    public function recommend()
    {
        $param           = $this->request->param();

        if (isset($param['ids']) && isset($param["yes"])) {
            $ids = $this->request->param('ids/a');
            model('Verify')->where(['id' => ['in', $ids]])->update(['is_rec' => 1]);
            $this->success("推荐成功！", '');

        }
        if (isset($param['ids']) && isset($param["no"])) {
            $ids = $this->request->param('ids/a');
            model('Verify')->where(['id' => ['in', $ids]])->update(['is_rec' => 0]);
            $this->success("取消推荐成功！", '');

        }
    }
    public function status()
    {
        $ids = $this->request->param('ids/a');
        $s = $this->request->param('s/d');
        if (!empty($ids) && isset($s)) {
            model('Verify')->where(['id'=>['in',$ids]])->update(['status'=>$s]);
            $this->success('状态修改成功');
        }
    }


    public function listOrder()
    {
        parent::listOrders(Db::name('Verify'));
        $this->success("排序更新成功！", '');
    }

    public function move()
    {

    }

    public function copy()
    {

    }
}