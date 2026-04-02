<?php

namespace Addon\Cms\Http\Controllers\Home;

use Addon\Cms\Models\Archive;
use Addon\Cms\Models\Category;
use Addon\Cms\Service\ArchiveService;
use Illuminate\Http\Request;

class HelpController
{
    protected $archiveService;
    public function __construct(ArchiveService $archiveService)
    {
        $this->archiveService = $archiveService;
    }

    public function index(Request $request){
        $cate = Category::query()->find(2)->toArray();
        $cate_nav = Category::query()->where("parent_id", 2)->where("status", 1)->get()->toArray();
        $pid = $request->get("pid", $cate_nav[0]['id']);
        $lists = $this->archiveService->page(['category_id' => [$pid]]);

        return view("default.help.index", compact("cate_nav", "pid", 'cate', "lists"));
    }

    public function detail($id){
        $detail = $this->archiveService->getDetail($id);
        $next = Archive::query()
            ->where('category_id', $detail['category_id'])
            ->where('id', '>', $detail['id'])
            ->orderBy('id', 'asc')->first();

        $prev = Archive::query()
            ->where('category_id', $detail['category_id'])
            ->where('id', '<', $detail['id'])
            ->orderBy('id', 'desc')->first();


        return view("default.help.detail", compact("detail", 'next', 'prev'));
    }
}
