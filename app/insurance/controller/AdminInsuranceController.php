<?php
namespace app\insurance\controller;

use cmf\controller\AdminBaseController;
// use app\insurance\model\InsuranceModel;
use think\Db;

class AdminInsuranceController extends AdminBaseController
{
    // function _initialize()
    // {
    //     parent::_initialize();
    // }

    public function index()
    {
        $id = $this->request->param('id',0,'intval');
        $data = Db::name('usual_company')->where('id',$id)->value('name');
        return $data.'的车险服务：保险业务模块';
        return $this->fetch();
    }
}