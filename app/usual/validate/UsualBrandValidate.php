<?php
namespace app\usual\validate;

use think\Validate;
// use think\Db;
// use app\admin\model\RouteModel;

class UsualBrandValidate extends Validate
{
    protected $rule = [
        'name'  => 'require|checkName',
        'parent_id'  => 'checkParentId',
        // 'alias' => 'checkAlias',
    ];
    protected $message = [
        'name.require'  => '名称不能为空',
        'name.checkName'=> '名称已存在',
        'parent_id'     => '超过了2级',
    ];

    // 场景验证 ， 指定场景需要验证的字段
    protected $scene = [
       'add'  => ['name','parent_id'],
       'edit' => ['name'=>'require','parent_id'],
    ];

    // 自定义验证规则
    // 检查名称是否存在
    protected function checkName($value)
    {
        $find = model('UsualBrand')->where('name',$value)->value('id');
        if ($find) {return false;}
        return true;
    }

    protected function checkParentId($value)
    {
        $find = model('UsualBrand')->where(["id" => $value])->value('parent_id');
        if ($find>0) {
            return false;
        }
        return true;
    }

    protected function checkAlias($value, $rule, $data)
    {
        if (empty($value)) {return true;}

        $routeModel = new RouteModel();
        if (isset($data['id']) && $data['id'] > 0){
            $fullUrl    = $routeModel->buildFullUrl('portal/List/index', ['id' => $data['id']]);
        }else{
            $fullUrl    = $routeModel->getFullUrlByUrl($data['alias']);
        }
        if (!$routeModel->exists($value, $fullUrl)) {
            return true;
        } else {
            return "别名已经存在!";
        }
        return true;
    }
}