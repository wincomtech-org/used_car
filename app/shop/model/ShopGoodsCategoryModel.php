<?php
namespace app\shop\model;

use app\usual\model\UsualCategoryModel;
use think\Db;
use think\Model;
use tree\Tree;

/**
 * 商品属性模型 cmf_shop_goods_category
 */
class ShopGoodsCategoryModel extends UsualCategoryModel
{
    // 获取列表数据 table表
    public function shopGoodsCategoryTableTree($currentIds = 0, $tpl = '')
    {
        $where = ['delete_time' => 0];
        // if (!empty($currentCid)) {
        //     $where['id'] = ['neq', $currentCid];
        // }
        $categories = $this->order("list_order ASC")->where($where)->select()->toArray();

        $tree       = new Tree();
        $tree->icon = ['&nbsp;&nbsp;│', '&nbsp;&nbsp;├─', '&nbsp;&nbsp;└─'];
        $tree->nbsp = '&nbsp;&nbsp;';

        if (!is_array($currentIds)) {
            $currentIds = [$currentIds];
        }

        $newCategories = [];
        foreach ($categories as $item) {
            $item['checked']    = in_array($item['id'], $currentIds) ? 'checked' : '';
            $item['url']        = cmf_url('shop/Index/index', ['cateId' => $item['id']]);
            $item['is_rec']     = $item['is_rec']==1 ? '<a data-toggle="tooltip" title="已推荐"><i class="fa fa-thumbs-up"></i></a>' : '<a style="color:#F00" data-toggle="tooltip" title="未推荐"><i class="fa fa-thumbs-down"></i></a>' ;
            $item['status']     = $item['status']==1 ? '<a data-toggle="tooltip" title="显示"><i class="fa fa-check"></i></a>' : '<a style="color:#F00" data-toggle="tooltip" title="隐藏"><i class="fa fa-close"></i></a>';
            $item['str_action'] = '<a href="' . url("AdminCategory/add", ["parent" => $item['id']]) . '">添加子分类</a> &nbsp; '
            . '<a href="' . url("AdminCategory/attrs", ["cid" => $item['id']]) . '">关联属性</a> &nbsp; '
            . '<a href="' . url("AdminCategory/edit", ["id" => $item['id']]) . '">' . lang('EDIT') . '</a> &nbsp; '
            . '<a class="js-ajax-delete" href="' . url("AdminCategory/delete", ['id' => $item['id']]) . '">' . lang('DELETE') . '</a>'
            ;
            array_push($newCategories, $item);
        }

        $tree->init($newCategories);

        if (empty($tpl)) {
            $tpl = "<tr>
                        <td><input name='list_orders[\$id]' type='text' size='3' value='\$list_order' class='input-order'></td>
                        <td>\$id</td>
                        <td>\$spacer <a href='\$url' target='_blank'>\$name</a></td>
                        <td>\$description</td>
                        <td>\$is_rec</td>
                        <td>\$status</td>
                        <td>\$str_action</td>
                    </tr>";
        }
        $treeStr = $tree->getTree(0, $tpl);

        return $treeStr;
    }

    // 获取单条数据
    public function getPost($id)
    {
        $post = $this->get($id)->toArray();

        return $post;
    }

    /**
     * 添加文章分类
     * @param $data
     * @return bool
     */
    public function addCategory($data)
    {
        $transStatus = true;
        self::startTrans();
        try {
            if (!empty($data['more']['thumbnail'])) {
                $data['more']['thumbnail'] = cmf_asset_relative_url($data['more']['thumbnail']);
            }
            $this->allowField(true)->save($data);
            $id = $this->id;
            if (empty($data['parent_id'])) {
                $this->where(['id' => $id])->update(['path' => '0-' . $id]);
            } else {
                $parentPath = $this->where('id', intval($data['parent_id']))->value('path');
                $this->where(['id' => $id])->update(['path' => "$parentPath-$id"]);
            }
            self::commit();
        } catch (\Exception $e) {
            self::rollback();
            $transStatus = false;
        }

        return $transStatus;
    }

    public function editCategory($data)
    {
        $result      = true;
        $id          = intval($data['id']);
        $parentId    = intval($data['parent_id']);
        $oldCategory = $this->where('id', $id)->find();

        if (empty($parentId)) {
            $newPath = '0-' . $id;
        } else {
            $parentPath = $this->where('id', intval($data['parent_id']))->value('path');
            if ($parentPath === false) {
                $newPath = false;
            } else {
                $newPath = "$parentPath-$id";
            }
        }

        if (empty($oldCategory) || empty($newPath)) {
            $result = false;
        } else {
            $data['path'] = $newPath;
            if (!empty($data['more']['thumbnail'])) {
                $data['more']['thumbnail'] = cmf_asset_relative_url($data['more']['thumbnail']);
            }
            $this->isUpdate(true)->allowField(true)->save($data, ['id' => $id]);

            $children = $this->field('id,path')->where('path', 'like', "%-$id-%")->select();
            if (!empty($children)) {
                foreach ($children as $child) {
                    $childPath = str_replace($oldCategory['path'] . '-', $newPath . '-', $child['path']);
                    $this->isUpdate(true)->allowField(true)->save(['path' => $childPath], ['id' => $child['id']]);
                }
            }
        }

        return $result;
    }



/*以下为具体方法*/
    // 获取指定级别的顶部ID
    public function getTopid($cateId = 0, $level = 99)
    {
        $find = $this->where('id', $cateId)->value('parent_id');
        if ($level == 0) {
            return $cateId;
        } else {
            if ($find == 0) {
                return $cateId;
            } else {
                return $this->getTopid($find, $level - 1);
            }
        }
    }

    /*分类树数组*/
    public function getGoodsTreeArray($cateId = 0)
    {
        $cateId = empty($cateId)?0:$cateId;
        $tree  = new Tree();
        $where = [
            'delete_time' => 0,
            // 'status'      => 1,
        ];
        $field = 'id,name,parent_id,path';

        $categories = $this->field($field)->order("list_order ASC")->where($where)->select()->toArray();
        // model('admin/NavMenu')->parseNavMenu4Home($categories);
        $tree->init($categories);
        $cateTree = $tree->getTreeArray($cateId);

        return $cateTree;
    }

    /*
     * 获取子集分类
     * 无子集返回同级（分类数据不要查数据库了）
     */
    public function getCateChildren($cateId=0)
    {
        $where = [
            'status' => 1,
        ];
        $field = 'id,name';

        $child = $this->where(['parent_id'=>$cateId])->count();
        if ($child>0) {
            $categories = $this->field($field)->order('list_order')->where($where)->where(['parent_id'=>$cateId])->select()->toArray();
        } else {
            $table = Db::getTable('shop_goods_category');
            // $father = $this->where('id',$cateId)->value('parent_id');
            // $categories = $this->field($field)->order('list_order')->where($where)->where(['parent_id'=>$father])->select();
            $categories = Db::query(sprintf('SELECT `id`,`name` FROM `%s` WHERE `status`=1 AND `parent_id`=(SELECT `parent_id` FROM `%s` WHERE `id`=%s) ORDER BY list_order',$table,$table,$cateId));
        }
        
        return $categories;
    }
    /*
     * 获取所有子集
     * 不需要递归
     * 带自定义条件、排序、数据条数
    */
    public function getCateChildrens($cateId=0, $condition=['is_rec'=>1], $order='is_rec desc', $limit=25)
    {
        $where = [
            'status' => 1,
        ];
        $field = 'id,name';
        $sort = 'list_order';
        
        if ($cateId==0) {
            $categories = $this->field($field)
                ->where($where)->where($condition)
                ->order($order)->order($sort)
                ->limit($limit)->select()->toArray();
        } else {
            $child = $this->where(['parent_id'=>$cateId])->count();
            if ($child>0) {
                // $path = $this->where(['id'=>$cateId])->value('path');
                // $categories = $this->field($field)->order($sort)->where($where)->where(['path'=>['like',$path.'-%']])->select();
                $subSql = Db::name('shop_goods_category')->field('path')->where('id',$cateId)->buildSql();
                $categories = Db::name('shop_goods_category')->field($field)
                    ->where($where)->where("path LIKE concat($subSql,'-%') ")
                    ->order($order)->order($sort)
                    ->limit($limit)
                    ->select()->toArray();
            } else {
                $path = $this->where(['id'=>$cateId])->value('path');
                $path = substr($path,0,-strlen($cateId));
                $categories = $this->field($field)
                    ->where($where)->where(['path'=>['like',$path.'%']])
                    ->order($order)->order($sort)
                    ->limit($limit)
                    ->select()->toArray();
            }
        }

        return $categories;
    }
    // 获取同级分类
    public function getCateSibling($cateId=0)
    {
        # code...
    }

    /**
     * [cateCrumbs 分类面包屑，递归获取]
     * @param  integer $cateId [description]
     * @param  integer $level  [description]
     * @return [type]          [description]
     */
    public function cateCrumbs($cateId = 0, $level = 5, $crumb = '')
    {
        $find = $this->field('name,parent_id')->where('id', $cateId)->find();

        $crumb = $find['name'] . ($crumb ? ' > ' . $crumb : '');
        if ($find['parent_id'] == 0) {
            return $crumb;
        } else {
            return $this->cateCrumbs($find['parent_id'], $level, $crumb);
        }
    }



/*以下是与分类相关的*/
    /**
     * 获取当前分类下的规格
     * @param  integer $cateId [description]
     * @return [type]          [description]
     */
    public function getSpecByCate($cateId = 1)
    {
        // 判断当前分类规格是否为空，若为空则继承上级，若上级没有关联下级或者没有上级则返回空。
        $specs            = [];
        $category_specIds = Db::name('shop_category_spec')->where('cate_id', $cateId)->column('spec_id');
        if (!empty($category_specIds)) {
            $specs  = Db::name('shop_spec')->field('id,name')->where('id', 'in', $category_specIds)->select();
        } else {
            $pid    = Db::name('shop_goods_category')->where('id', $cateId)->value('parent_id');
            if ($pid>0) {
                $father = Db::name('shop_goods_category')->where('id', $pid)->value('spec_subset');
                if ($father == 1) {
                    $category_specIds = Db::name('shop_category_spec')->where('cate_id', $pid)->column('spec_id');
                    $specs            = Db::name('shop_spec')->field('id,name')->where(['id' => ['in', $category_specIds]])->select();
                    // $subSql = Db::name('shop_category_spec')->field('spec_id')->where('cate_id',$pid)->buildSql();
                    // $specs  = Db::name('shop_spec')->field('id,name')->where('id','in',$subSql)->select();
                }
            }
        }

        return $specs;
    }

    /**
     * 获取当前分类下的属性[属性值]
     * 表字段不含 parent_id和path，故不能用 Tree类 处理
     * @param  integer $cateId     [description]
     * @param  array   $condition  [description]
     * @param  boolean $attr_value [description]
     * @return [type]              [description]
     */
    public function getAttrByCate($cateId=1, $condition=['is_query'=>1], $attr_value=true)
    {
        $attrs = $attrs2 = [];
        $mq1   = Db::name('shop_category_attr');
        $mq2   = Db::name('shop_goods_category');
        $mq3   = Db::name('shop_goods_attr');
        $mq4   = Db::name('shop_goods_av');
        $atk   = (is_null($cateId)) ? true : false; //为空时使用推荐属性？

        // 属性处理
        if ($atk === true) {
            // $attrs = $mq3->field('id,name')->where('status',1)->limit(9)->select();
        } else {
            $category_attrIds = $mq1->where('cate_id', $cateId)->where($condition)->column('attr_id');
            if (!empty($category_attrIds)) {
                $attrs = $mq3->field('id,name')->where(['id' => ['in', $category_attrIds]])->select();
            } else {
                $pid    = $mq2->where('id', $cateId)->value('parent_id');
                $father = $mq2->where('id', $pid)->value('attr_subset');
                if ($pid > 0 && $father == 1) {
                    $category_attrIds = $mq1->where('cate_id', $pid)->where($condition)->column('attr_id');
                    $attrs            = $mq3->field('id,name')->where(['id' => ['in', $category_attrIds]])->select();
                }
            }
        }

        // 属性值处理
        if (!empty($attrs)) {
            foreach ($attrs as $row) {
                $attrs2[$row['id']] = $row;
            }
            if ($attr_value === true) {
                $attr_ids = array_column($attrs2, 'id');
                $values = $mq4->field('id,name,attr_id')->where(['attr_id'=>['in',$attr_ids]])->select();
                foreach ($values as $row) {
                    $values2[$row['attr_id']][] = ['id'=>$row['id'],'name'=>$row['name']];
                }
                foreach ($attr_ids as $key) {
                    $attrs2[$key]['value'] = isset($values2[$key]) ? $values2[$key] : [];
                }
            }
        }

        return $attrs2;
        // return array_values($attrs2);
    }

}
