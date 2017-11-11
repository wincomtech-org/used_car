<?php
namespace app\usual\model;

use app\usual\model\UsualCategoryModel;

class UsualBrandModel extends UsualCategoryModel
{
    //自定义初始化
    // protected function initialize()
    // {
    //     //需要调用`Model`的`initialize`方法
    //     parent::initialize();
    //     //TODO:自定义的初始化
    // }

    public function getLists($filter, $isPage = false)
    {
        $where = ['delete_time' => 0];
        // $categories = $this->field('id,name,description,list_order')->order("list_order ASC")->where($where)->select()->toArray();
        $categories = $this->field('id,name,description,list_order')->order("list_order ASC,id DESC")->where($where)->paginate(config('pagerset.size'));
        return $categories;
    }

    public function getBrands($selectId=0, $parentId=0, $level=1, $default_option=false)
    {
        // $data = $this->all()->toArray();
        $data = $this->field(['id','name'])->select()->toArray();
        $options = $default_option ?'<option value="0">--请选择--</option>':'';
        if (is_array($data)) {
            foreach ($data as $v) {
                $options .= '<option value="'.$v['id'].'" '.($selectId==$v['id']?'selected':'').' >'.$v['name'].'</option>';
            }
        }
        return $options;
    }
}