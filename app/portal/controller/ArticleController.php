<?php
// +----------------------------------------------------------------------
// | ThinkCMF [ WE CAN DO IT MORE SIMPLE ]
// +----------------------------------------------------------------------
// | Copyright (c) 2013-2017 http://www.thinkcmf.com All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: 老猫 <thinkcmf@126.com>
// +----------------------------------------------------------------------
namespace app\portal\controller;

use cmf\controller\HomeBaseController;
use app\portal\model\PortalCategoryModel;
use app\portal\service\PostService;
use app\portal\model\PortalPostModel;
use think\Db;

class ArticleController extends HomeBaseController
{
    public function index()
    {

        $portalCategoryModel = new PortalCategoryModel();
        $postService         = new PostService();

        $articleId  = $this->request->param('id', 0, 'intval');
        $cateId = $this->request->param('cid', 0, 'intval');
        $article    = $postService->publishedArticle($articleId, $cateId);
        if (empty($articleId)) {
            abort(404, '文章不存在!');
        }


        // 关于我们左侧菜单
        if (!empty($cateId)) {
            $menu = $postService->fromCateList($cateId);
            $this->assign('menu', $menu);
            $this->assign('id', $articleId);
            $this->assign('cid', $cateId);
        }

        // 右侧同级的文章
        $parentId = Db::name('portal_category')->where('id',$cateId)->value('parent_id');
        // $peerIds = Db::name('portal_category')->where('parent_id',$parentId)->column('id');
        $peers = Db::name('portal_category')->field('id,name')->where('parent_id',$parentId)->select();
        $rightList = [];
        foreach ($peers as $vo) {
            $vo['list'] = $postService->fromCateList($vo['id'],3);;
            $rightList[] = $vo;
        }

        // 面包屑
        $crumbs = $this->getCrumbs('portal_category',$cateId,$article['post_title']);

        // 上下文
        $prevArticle = $postService->publishedPrevArticle($articleId, $cateId);
        $nextArticle = $postService->publishedNextArticle($articleId, $cateId);



        $tplName = 'article';
        if (empty($cateId)) {
            $categories = $article['categories'];
            if (count($categories) > 0) {
                $this->assign('category', $categories[0]);
            } else {
                abort(404, '文章未指定分类!');
            }
        } else {
            $category = $portalCategoryModel->where('id', $cateId)->where('status', 1)->find();
            if (empty($category)) {
                abort(404, '文章不存在!');
            }
            $this->assign('category', $category);
            $tplName = empty($category["one_tpl"]) ? $tplName : $category["one_tpl"];
        }

        Db::name('portal_post')->where(['id' => $articleId])->setInc('post_hits');

        hook('portal_before_assign_article', $article);

        $this->assign('crumbs', $crumbs);
        $this->assign('article', $article);
        $this->assign('prev_article', $prevArticle);
        $this->assign('next_article', $nextArticle);
        $this->assign('rightList', $rightList);

        $tplName = empty($article['more']['template']) ? $tplName : $article['more']['template'];
        return $this->fetch("/$tplName");
    }

    // 文章点赞
    public function doLike()
    {
        $this->checkUserLogin();
        $articleId = $this->request->param('id', 0, 'intval');

        $canLike = cmf_check_user_action("posts$articleId", 1);

        if ($canLike) {
            Db::name('portal_post')->where(['id' => $articleId])->setInc('post_like');
            $this->success("赞好啦！");
        } else {
            $this->error("您已赞过啦！");
        }
    }

    public function myIndex()
    {
        //获取登录会员信息
        $user = cmf_get_current_user();
        $this->assign('user_id', $user['id']);
        return $this->fetch('user/index');
    }

    //用户添加
    public function add()
    {
        return $this->fetch('user/add');
    }

    public function addPost()
    {
        if ($this->request->isPost()) {
            $data   = $this->request->param();
            $post   = $data['post'];
            $result = $this->validate($post, 'AdminArticle');
            if ($result !== true) {
                $this->error($result);
            }

            $portalPostModel = new PortalPostModel();

            if (!empty($data['photo_names']) && !empty($data['photo_urls'])) {
                $data['post']['more']['photos'] = [];
                foreach ($data['photo_urls'] as $key => $url) {
                    $photoUrl = cmf_asset_relative_url($url);
                    array_push($data['post']['more']['photos'], ["url" => $photoUrl, "name" => $data['photo_names'][$key]]);
                }
            }

            if (!empty($data['file_names']) && !empty($data['file_urls'])) {
                $data['post']['more']['files'] = [];
                foreach ($data['file_urls'] as $key => $url) {
                    $fileUrl = cmf_asset_relative_url($url);
                    array_push($data['post']['more']['files'], ["url" => $fileUrl, "name" => $data['file_names'][$key]]);
                }
            }
            $portalPostModel->adminAddArticle($data['post'], $data['post']['categories']);

            $this->success('添加成功!', url('Article/myIndex', ['id' => $portalPostModel->id]));
        }
    }

    public function select()
    {
        $ids                 = $this->request->param('ids');
        $selectedIds         = explode(',', $ids);
        $portalCategoryModel = new PortalCategoryModel();

        $tpl = <<<tpl
<tr class='data-item-tr'>
    <td>
        <input type='checkbox' class='js-check' data-yid='js-check-y' data-xid='js-check-x' name='ids[]'
                               value='\$id' data-name='\$name' \$checked>
    </td>
    <td>\$id</td>
    <td>\$spacer <a href='\$url' target='_blank'>\$name</a></td>
    <td>\$description</td>
</tr>
tpl;

        $categoryTree = $portalCategoryModel->adminCategoryTableTree($selectedIds, $tpl);

        $where      = ['delete_time' => 0];
        $categories = $portalCategoryModel->where($where)->select();

        $this->assign('categories', $categories);
        $this->assign('selectedIds', $selectedIds);
        $this->assign('categories_tree', $categoryTree);
        return $this->fetch('user/select');
    }
}
