<?php
namespace app\shop\model;

use think\Model;

/**
* 商品属性模型 cmf_shop_goods_attr
*/
class ShopGoodsAttrModel extends Model
{
    protected $type = [
        'more' => 'array',
    ];
    // 开启自动写入时间戳字段
    protected $autoWriteTimestamp = true;
    // protected $hidden = ['delete_time', 'update_time'];
    // 关联商品表 cmf_shop_goods
    public function attrGoods()
    {
        return $this->belongsToMany('ShopGoodsModel', 'shop_gav', 'goods_id', 'attr_id');
    }
    // 关联分类表 cmf_shop_category_attr
    public function attrCates()
    {
        return $this->belongsToMany('ShopGoodsCategoryModel', 'shop_category_attr', 'category_id', 'attr_id');
    }

    /*添加属性*/
    public function addAttr($data)
    {
        if (!empty($data['more']['thumbnail'])) {
            $data['more']['thumbnail'] = cmf_asset_relative_url($data['more']['thumbnail']);
        }

        $this->allowField(true)->data($data, true)->isUpdate(false)->save();
 
        return $this;
    }

    /*编辑属性*/
    public function editAttr($data)
    {
        if (!empty($data['more']['thumbnail'])) {
            $data['more']['thumbnail'] = cmf_asset_relative_url($data['more']['thumbnail']);
        }

        $this->allowField(true)->isUpdate(true)->data($data, true)->save();
 
        return $this;
    }
    /*得到所有显示属性*/
    public function getAttrs($status=1)
    {
        $where=[];
        if($status==1){
            $where=['status'=>1];
        } 
        $list=$this->field('id,name')->where($where)->order('list_order asc,id asc')->select();
        
        return $list;
    }

    /*删除属性*/
    public function deleteAttr($data)
    {
        # code...
    }
     
}