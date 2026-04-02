<?php

namespace Addon\Cms\Http\Controllers\Home;

use Addon\Cms\Models\Archive;
use Addon\Cms\Models\Category;
use Addon\Cms\Service\ArchiveService;
use App\Exceptions\DataEmptyException;
use Illuminate\Support\Facades\DB;

class ProductController
{
    protected $archiveService;
    public function __construct(ArchiveService $archiveService)
    {
        $this->archiveService = $archiveService;
    }

    public function index($name){
        /** @var Category $currentCate */
        $currentCate = Category::query()->where("dir_name", $name)->first();
        if (!$currentCate) {
            throw new DataEmptyException();
        }
        $category = Category::query()->where("parent_id", $currentCate->parent_id)->get();
        $parent = Category::query()->where("id", $currentCate->parent_id)->first();
        $lists = Archive::query()->where("category_id", $currentCate->id)
            ->joinWhere("product", "cms_archives.extend_id", "=", DB::raw("`product`.`id`"))->get();

        // DDoS高防和CDN 页面
        if (in_array($currentCate->dir_name, ['ddos', 'cdn'], true)) {
            $data = [
                'ddos' => [
                    'title' => '高防IP专注于解决服务器外业务遭受大流量DDoS攻击的防护服务',
                    'note' => '支持网站和非网站类业务的DDoS、CC防护，用户通过配置转发规则，将攻击流量引至高防IP并清洗，保障业务稳定可用，具有灾备能力，线路更稳定，访问速度更快（源服务器用任何地方的都支持本业务）'
                ],
                'cdn' => [
                    'title' => 'CDN的全称是Content Delivery Network，即内容分发网络',
                    'note' => 'CDN是构建在现有网络基础之上的智能虚拟网络，依靠部署在各地的边缘服务器，通过中心平台的负载均衡、内容分发、调度等功能模块，使用户就近获取所需内容，降低网络拥塞，提高用户访问响应速度和命中率。CDN的关键技术主要有内容存储和分发技术'
                ]

            ];
            $parent = $data[$currentCate->dir_name];
            return view("default.product.".$currentCate->dir_name, compact("currentCate", "category", "lists", "parent"));
        }
        return view("default.product.index", compact("currentCate", "category", "lists", "parent"));
    }

    public function one()
    {
        return view("default.protect.one");
    }
    public function cloud()
    {
        return view("default.protect.cloud");
    }
}
