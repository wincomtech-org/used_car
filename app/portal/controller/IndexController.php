<?php
namespace app\portal\controller;

use cmf\controller\HomeBaseController;
// use app\service\model\ServiceCategoryModel;
// use app\insurance\model\InsuranceModel;
use app\usual\model\UsualCarModel;
use app\portal\model\PortalPostModel;
// use think\Db;

class IndexController extends HomeBaseController
{
    public function index()
    {
        // 我们的服务
        // $ourcore = [];

        // 保险业务
        $insurances = model('insurance/Insurance')->getIndexInsuranceList();

        // 车辆数据
        $ufoModel = new UsualCarModel();
        $carType = config('usual_car_type');
        $Type = array_merge(['最新上架','新车推荐'],$carType);
        $newCar = $ufoModel->getIndexCarList('',['a.published_time'=>'desc']);
        $TuiCar = $ufoModel->getIndexCarList('',['a.is_rec'=>'DESC']);
        $cars = array_merge([$newCar],[$TuiCar]);
        // $cars = array_push($newCar,$TuiCar);
        // $cars = $newCar + $TuiCar;
        foreach ($carType as $key => $value) {
            $cars = array_merge($cars,[$ufoModel->getIndexCarList($key)]);
        }
        // 统一处理
        $newCars = [];
        foreach ($cars as $key => $value) {
            $newCars[] = [
                'type_name' =>$Type[$key],
                'children'  =>$value
            ];
        }

        // 车辆服务 使用Db不能直接转化 json 数组
        $services = model('service/ServiceCategory')->getIndexServiceList();

        // 买车流程
        $portalM = new PortalPostModel();
        $article_flows = $portalM->getIndexPortalList(4,'ASC',7);
        // 车辆服务文章
        // $article_services = $portalM->getIndexPortalList(3,'ASC',7);
        // 新闻资讯
        $article_news = $portalM->getIndexPortalList();


        // $this->assign('ourcore',$ourcore);
        $this->assign('insurances',$insurances);
        $this->assign('Type',$Type);
        $this->assign('cars',$newCars);
        $this->assign('services',$services);
        $this->assign('article_flows',$article_flows);
        // $this->assign('article_services',$article_services);
        $this->assign('article_news',$article_news);
        return $this->fetch(':index');
    }
}
