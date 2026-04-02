<?php
namespace Addon\Cms\Http\Controllers\Home;

use Addon\Cms\Models\Category;
use Illuminate\Http\Request;

class ClientController
{

    public function index(Request $request){
        $cate = Category::query()->find(3);
        $cate_nav = Category::query()->where("parent_id", 3)->where("status", 1)->get()->toArray();
        $pid = $request->get("pid", $cate_nav[0]['id']);

        return view("default.clients.index", compact('cate', "cate_nav", 'pid'));
    }
}
