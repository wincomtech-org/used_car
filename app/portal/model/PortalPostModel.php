<?php
namespace app\portal\model;

use app\admin\model\RouteModel;
use think\Model;
use think\Db;

class PortalPostModel extends Model
{
    protected $type = [
        'more' => 'array',
    ];

    // 开启自动写入时间戳字段
    protected $autoWriteTimestamp = true;

    /**
     * 关联 user表
     * @return $this
     */
    public function user()
    {
        return $this->belongsTo('UserModel', 'user_id')->setEagerlyType(1);
    }

    /**
     * 关联分类表
     */
    public function categories()
    {
        return $this->belongsToMany('PortalCategoryModel', 'portal_category_post', 'category_id', 'post_id');
    }

    /**
     * post_content 自动转化
     * @param $value
     * @return string
     */
    public function getPostContentAttr($value)
    {
        return cmf_replace_content_file_url(htmlspecialchars_decode($value));
    }

    /**
     * post_content 自动转化
     * @param $value
     * @return string
     */
    public function setPostContentAttr($value)
    {
        return htmlspecialchars(cmf_replace_content_file_url(htmlspecialchars_decode($value), true));
    }

    /**
     * published_time 自动完成
     * @param $value
     * @return false|int
     */
    public function setPublishedTimeAttr($value)
    {
        return strtotime($value);
    }

    /**
     * 后台管理添加文章
     * @param array $data 文章数据
     * @param array|string $categories 文章分类 id
     * @return $this
     */
    public function adminAddArticle($data, $categories)
    {
        $data['user_id'] = cmf_get_current_admin_id();

        if (!empty($data['more']['thumbnail'])) {
            $data['more']['thumbnail'] = cmf_asset_relative_url($data['more']['thumbnail']);
        }

        $this->allowField(true)->data($data, true)->isUpdate(false)->save();

        if (isset($categories)) {
            if (is_string($categories)) {
                $categories = explode(',', $categories);
            }
            $this->categories()->save($categories);
        }

        if (!empty($data['post_keywords'])) {
            $data['post_keywords'] = str_replace('，', ',', $data['post_keywords']);
            $keywords = explode(',', $data['post_keywords']);
            $this->addTags($keywords, $this->id);
        }

        return $this;
    }

    /**
     * 后台管理编辑文章
     * @param array $data 文章数据
     * @param array|string $categories 文章分类 id
     * @return $this
     */
    public function adminEditArticle($data, $categories)
    {
        unset($data['user_id']);

        if (!empty($data['more']['thumbnail'])) {
            $data['more']['thumbnail'] = cmf_asset_relative_url($data['more']['thumbnail']);
        }

        $data['post_status'] = empty($data['post_status']) ? 0 : 1;
        $data['is_top']      = empty($data['is_top']) ? 0 : 1;
        $data['recommended'] = empty($data['recommended']) ? 0 : 1;

        $this->allowField(true)->isUpdate(true)->data($data, true)->save();

        if (isset($categories)) {
            if (is_string($categories)) {
                $categories = explode(',', $categories);
            }

            $oldCategoryIds        = $this->categories()->column('category_id');
            $sameCategoryIds       = array_intersect($categories, $oldCategoryIds);
            $needDeleteCategoryIds = array_diff($oldCategoryIds, $sameCategoryIds);
            $newCategoryIds        = array_diff($categories, $sameCategoryIds);

            if (!empty($needDeleteCategoryIds)) {
                $this->categories()->detach($needDeleteCategoryIds);
            }
            if (!empty($newCategoryIds)) {
                $this->categories()->attach(array_values($newCategoryIds));
            }
        }

        if (!empty($data['post_keywords'])) {
            $data['post_keywords'] = str_replace('，', ',', $data['post_keywords']);
            $keywords = explode(',', $data['post_keywords']);
            $this->addTags($keywords, $data['id']);
        }

        return $this;
    }

    public function addTags($keywords, $articleId)
    {
        $portalTagModel = new PortalTagModel();
        $tagIds = [];
        $data = [];

        if (!empty($keywords)) {
            $oldTagIds = Db::name('portal_tag_post')->where('post_id', $articleId)->column('tag_id');

            foreach ($keywords as $keyword) {
                $keyword = trim($keyword);
                if (!empty($keyword)) {
                    $findTag = $portalTagModel->where('name', $keyword)->find();
                    if (empty($findTag)) {
                        $tagId = $portalTagModel->insertGetId([
                            'name' => $keyword
                        ]);
                    } else {
                        $tagId = $findTag['id'];
                    }

                    if (!in_array($tagId, $oldTagIds)) {
                        array_push($data, ['tag_id' => $tagId, 'post_id' => $articleId]);
                    }

                    array_push($tagIds, $tagId);

                }
            }

            if (empty($tagIds) && !empty($oldTagIds)) {
                Db::name('portal_tag_post')->where('post_id', $articleId)->delete();
            }

            $sameTagIds = array_intersect($oldTagIds, $tagIds);
            $shouldDeleteTagIds = array_diff($oldTagIds, $sameTagIds);
            if (!empty($shouldDeleteTagIds)) {
                Db::name('portal_tag_post')->where(['post_id' => $articleId, 'tag_id' => ['in', $shouldDeleteTagIds]])->delete();
            }

            if (!empty($data)) {
                Db::name('portal_tag_post')->insertAll($data);
            }
        } else {
            Db::name('portal_tag_post')->where('post_id', $articleId)->delete();
        }
    }

    public function adminDeletePage($data)
    {
        if (isset($data['id'])) {
            $id = $data['id']; //获取删除id
            $res = $this->where(['id' => $id])->find();

            if ($res) {
                $res = json_decode(json_encode($res), true); //转换为数组
                $recycleData = [
                    'object_id'   => $res['id'],
                    'create_time' => time(),
                    'table_name'  => 'portal_post#page',
                    'name'        => $res['post_title'],

                ];

                Db::startTrans(); //开启事务
                $transStatus = false;
                try {
                    Db::name('portal_post')->where(['id' => $id])->update([
                        'delete_time' => time()
                    ]);
                    Db::name('recycle_bin')->insert($recycleData);

                    $transStatus = true;
                    // 提交事务
                    Db::commit();
                } catch (\Exception $e) {

                    // 回滚事务
                    Db::rollback();
                }
                return $transStatus;
            } else {
                return false;
            }
        } elseif (isset($data['ids'])) {
            $ids = $data['ids'];
            $res = $this->where(['id' => ['in', $ids]])
                ->select();
            if ($res) {
                $res = json_decode(json_encode($res), true);
                foreach ($res as $key => $value) {
                    $recycleData[$key]['object_id']   = $value['id'];
                    $recycleData[$key]['create_time'] = time();
                    $recycleData[$key]['table_name']  = 'portal_post';
                    $recycleData[$key]['name']        = $value['post_title'];
                }

                Db::startTrans(); //开启事务
                $transStatus = false;
                try {
                    Db::name('portal_post')->where(['id' => ['in', $ids]])
                        ->update([
                            'delete_time' => time()
                        ]);
                    Db::name('recycle_bin')->insertAll($recycleData);
                    $transStatus = true;
                    // 提交事务
                    Db::commit();
                } catch (\Exception $e) {
                    // 回滚事务
                    Db::rollback();
                }
                return $transStatus;
            } else {
                return false;
            }
        } else {
            return false;
        }
    }

    /**
     * 后台管理添加页面
     * @param array $data 页面数据
     * @return $this
     */
    public function adminAddPage($data)
    {
        $data['user_id'] = cmf_get_current_admin_id();

        if (!empty($data['more']['thumbnail'])) {
            $data['more']['thumbnail'] = cmf_asset_relative_url($data['more']['thumbnail']);
        }

        $data['post_status'] = empty($data['post_status']) ? 0 : 1;
        $data['post_type']   = 2;
        $this->allowField(true)->data($data, true)->save();

        return $this;
    }

    /**
     * 后台管理编辑页面
     * @param array $data 页面数据
     * @return $this
     */
    public function adminEditPage($data)
    {
        $data['user_id'] = cmf_get_current_admin_id();

        if (!empty($data['more']['thumbnail'])) {
            $data['more']['thumbnail'] = cmf_asset_relative_url($data['more']['thumbnail']);
        }

        $data['post_status'] = empty($data['post_status']) ? 0 : 1;
        $data['post_type']   = 2;
        $this->allowField(true)->isUpdate(true)->data($data, true)->save();

        $routeModel = new RouteModel();
        $routeModel->setRoute($data['post_alias'], 'portal/Page/index', ['id' => $data['id']], 2, 5000);

        $routeModel->getRoutes(true);
        return $this;
    }



// 前台
    /*首页*/
    public function getIndexPortalList($cateId=1, $order='DESC', $limit=9)
    {
        $ckey = 'giportall'.$cateId.$order.$limit;
        if (cache('?'.$ckey)) {
            $lists = cache($ckey);
        } else {
            $field = 'a.id,a.post_title,a.post_excerpt,a.more';
            $join = [
                ['__PORTAL_CATEGORY_POST__ b', 'a.id=b.post_id']
            ];
            $where = [
                'a.delete_time' => 0,
                'a.post_status' => 1,
                'a.post_type' => 1,
                'b.category_id' => ['eq', $cateId],
            ];
            $lists = $this->alias('a')
                    ->field($field)
                    ->join($join)
                    ->where($where)
                    ->order('recommended DESC,id '.$order)
                    ->limit($limit)
                    ->select()->toArray();
            cache($ckey, $lists, 3600);
        }

        return $lists;
    }

}