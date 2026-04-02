<?php

namespace Addon\Cms\Http\Controllers\Home;

use PTAdmin\Admin\Service\SettingService;

class HomeController
{
    public function about(){

        return view("default.about");
    }
}
