<?php
namespace app\usual\controller;

// use app\admin\model\RouteModel;
use cmf\controller\AdminBaseController;
use app\usual\model\UsualItemModel;
use think\Db;
// use app\admin\model\ThemeModel;


class AdminItemController extends AdminBaseController
{
    function _initialize()
    {
        // parent::_initialize();
        // $data = $this->request->param();
        $this->UsualModel = new UsualItemModel();
    }

    /**
     * 车型分类列表
     * @adminMenu(
     *     'name'   => '分类管理',
     *     'parent' => 'usual/AdminItem/default',
     *     'display'=> true,
     *     'hasView'=> true,
     *     'order'  => 10000,
     *     'icon'   => '',
     *     'remark' => '车型分类列表',
     *     'param'  => ''
     * )
     */
    public function index()
    {
        // dump(CMF_ROOT);die;
        $config = [
            // 'm'         => request()->controller(),
            'm'         => 'AdminItem',
            'url'       => '',
            'add'       => true,
            'edit'      => true,
            'delete'    => false,
            'table2'    => ''
        ];
        $categoryTree    = $this->UsualModel->adminCategoryTableTree(0,'',$config);

        $this->assign('category_tree', $categoryTree);
        return $this->fetch();
    }

    /**
     * 添加车型分类
     * @adminMenu(
     *     'name'   => '添加车型分类',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> true,
     *     'order'  => 10000,
     *     'icon'   => '',
     *     'remark' => '添加车型分类',
     *     'param'  => ''
     * )
     */
    public function add()
    {
        $parentId           = $this->request->param('parent', 0, 'intval');
        $categoriesTree     = $this->UsualModel->adminCategoryTree($parentId);

        $this->assign('categories_tree', $categoriesTree);
        return $this->fetch();
    }

    /**
     * 添加车型分类提交
     * @adminMenu(
     *     'name'   => '添加车型分类提交',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 10000,
     *     'icon'   => '',
     *     'remark' => '添加车型分类提交',
     *     'param'  => ''
     * )
     */
    public function addPost()
    {
        $data = $this->request->param();

        $result = $this->validate($data, 'UsualItem');
        if ($result !== true) {
            $this->error($result);
        }
        if (model('UsualItem')->where('name',$data['name'])->value('id')) {
            $this->error('此名称已存在！');
        }
        $result = $this->UsualModel->addCategory($data);
        if ($result === false) {
            $this->error('添加失败!');
        }

        $this->success('添加成功!', url('AdminItem/index'));

    }

    /**
     * 编辑车型分类
     * @adminMenu(
     *     'name'   => '编辑车型分类',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> true,
     *     'order'  => 10000,
     *     'icon'   => '',
     *     'remark' => '编辑车型分类',
     *     'param'  => ''
     * )
     */
    public function edit()
    {
        $id = $this->request->param('id', 0, 'intval');
        if ($id > 0) {
            $category = UsualItemModel::get($id)->toArray();

            $categoriesTree      = $this->UsualModel->adminCategoryTree($category['parent_id'], $id);

            // $routeModel = new RouteModel();
            // $alias      = $routeModel->getUrl('portal/List/index', ['id' => $id]);
            // $category['alias'] = $alias;

            $this->assign($category);
            $this->assign('categories_tree', $categoriesTree);
            return $this->fetch();
        } else {
            $this->error('操作错误!');
        }

    }

    /**
     * 编辑车型分类提交
     * @adminMenu(
     *     'name'   => '编辑车型分类提交',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 10000,
     *     'icon'   => '',
     *     'remark' => '编辑车型分类提交',
     *     'param'  => ''
     * )
     */
    public function editPost()
    {
        $data = $this->request->param();
        // 字段验证
        // $validate = new \app\usual\validate\UsualItemValidate();
        // $result = $validate->scene('edit')->check($data);
        $result = $this->validate($data, 'UsualItem');
        // $result = $this->validate($data, ['name'=>'require','parent_id'=>'number|checkParentId']);
        // 提交结果
        $result = $this->UsualModel->editCategory($data);
        if ($result === false) {
            $this->error('保存失败!');
        }
        $this->success('保存成功!');
    }

    /**
     * 车型分类选择对话框
     * @adminMenu(
     *     'name'   => '车型分类选择对话框',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> true,
     *     'order'  => 10000,
     *     'icon'   => '',
     *     'remark' => '车型分类选择对话框',
     *     'param'  => ''
     * )
     */
    public function select()
    {
        $ids                 = $this->request->param('ids');
        $selectedIds         = explode(',', $ids);

        $tpl = <<<tpl
<tr class='data-item-tr'>
    <td>
        <input type='checkbox' class='js-check' data-yid='js-check-y' data-xid='js-check-x' name='ids[]' value='\$id' data-name='\$name' \$checked>
    </td>
    <td>\$id</td>
    <td>\$spacer <a href='\$url' target='_blank'>\$name</a></td>
</tr>
tpl;

        $categoryTree = $this->UsualModel->adminCategoryTableTree($selectedIds, $tpl);

        $where      = ['delete_time' => 0];
        $categories = $this->UsualModel->where($where)->select();

        $this->assign('categories', $categories);
        $this->assign('selectedIds', $selectedIds);
        $this->assign('categories_tree', $categoryTree);
        return $this->fetch();
    }

    /**
     * 车型分类排序
     * @adminMenu(
     *     'name'   => '车型分类排序',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 10000,
     *     'icon'   => '',
     *     'remark' => '车型分类排序',
     *     'param'  => ''
     * )
     */
    public function listOrder()
    {
        parent::listOrders(Db::name('UsualItem'));
        $this->success("排序更新成功！", '');
    }

    /**
     * 删除车型分类
     * @adminMenu(
     *     'name'   => '删除车型分类',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 10000,
     *     'icon'   => '',
     *     'remark' => '删除车型分类',
     *     'param'  => ''
     * )
     */
    public function delete()
    {
        $id = $this->request->param('id');
        //获取删除的内容
        $findCategory = $this->UsualModel->where('id', $id)->find();
        if (empty($findCategory)) {
            $this->error('分类不存在!');
        }

        $categoryChildrenCount = $this->UsualModel->where('parent_id', $id)->count();
        if ($categoryChildrenCount > 0) {
            $this->error('此分类有子类无法删除，请改名!');
        }

        // $categoryPostCount = Db::name('usual_car')->where('brand_id',$id)->whereOr('serie_id',$id)->count();
        // if ($categoryPostCount > 0) {
        //     $this->error('此分类有车子无法删除，请改名!');
        // }

        // $data   = [
        //     'object_id'   => $findCategory['id'],
        //     'create_time' => time(),
        //     'table_name'  => 'usual_brand',
        //     'name'        => $findCategory['name']
        // ];
        $result = $this->UsualModel
            ->where('id', $id)
            ->update(['delete_time' => time()]);
        if ($result) {
            // Db::name('recycleBin')->insert($data);
            $this->success('删除成功!');
        } else {
            $this->error('删除失败');
        }
    }
}