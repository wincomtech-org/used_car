<?php
namespace app\usual\controller;

use cmf\controller\AdminBaseController;
// use app\usual\model\UsualModel;
// use think\Db;

/**
* 认证模块
*/
class AdminAuthController extends AdminBaseController
{
    // function _initialize()
    // {
    //     parent::_initialize();
    // }

    public function index()
    {
        $id = $this->request->param('id',0,'intval');
        $data = model('usual_company')->where('id',$id)->value('name');
        return $data.'的认证模块： 认证项目';
        return $this->fetch();
    }
}