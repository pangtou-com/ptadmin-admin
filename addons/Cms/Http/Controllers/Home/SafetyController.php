<?php

namespace Addon\Cms\Http\Controllers\Home;

use Addon\Cms\Models\Safety;

class SafetyController
{

    private $icon = ["🔒", "⚡", "🎯", "📊"];
    public function index(){
        return view("default.safety.zysqaqfw");
    }

    public function view($id){
        /** @var Safety $data */
        $data = Safety::query()->findOrFail($id);
        $data->overview = explode("\n", $data->overview);
        $data->overview_desc = $data->overview_desc ? explode("\n", $data->overview_desc) : [];
        $data->process = $this->handle($data->process);
        $data->advantage = $this->handle($data->advantage);

        return view("default.safety.index", compact('data'));
    }

    protected function handle($data): array
    {
        $results = [];
        foreach ($data as $key =>  $datum) {
            if ($datum['desc']) {
                $desc = explode("\n", $datum['desc']);
                $datum['desc'] = $desc;
            } else {
                $datum['desc'] = [];
            }
            $datum['icon'] = $this->icon[$key % count($this->icon)];
            $results[] = $datum;
        }
        return $results;
    }
}
