@extends("ptadmin.layouts.base")

@section("content")
    <div class="layui-fluid">
        <div class="layui-card-body">
            <x-hint>
                <p>配置标识：建议标识名称设置为英文单词,且不允许重复，用于模版调用和开发调用。<span style="color: red">请谨慎删除或修改配置标识</span> </p>
            </x-hint>
            @php
                $parentId = (int)request()->get('parent_id', 0);
                if ($dao->parent_id == 0 && $parentId) {
                    $dao->parent_id = $parentId;
                }
                $form = \PTAdmin\Build\Layui::make($dao);
                $form->select('parent_id')->setOptions(\PTAdmin\Admin\Models\SettingGroup::getParentLists($dao->id))->default($parentId)->disabled((isset($dao->parent_id) && 0 === $dao->parent_id) || 1 === $dao->is_system);
                $form->text('title')->required();
                $form->text('name')->required()->disabled(isset($dao->name) && 1 === $dao->is_system);
                $form->number('weight')->default(99);
                $form->textarea('intro');
            @endphp
            {!! $form !!}

        </div>
    </div>
@endsection


@section("script")
<script>
    (function ($win) {
        layui.use(["PTForm", "form", "common"], function () {
            const { PTForm, form, common } = layui
            PTForm.init();
            $win['form_submit'] = function () {
                return new Promise((resolve, reject) => {
                    const elem = $("form").eq(0)
                    const verifyElem = elem.find('*[lay-verify]')
                    const isValid = form.validate(verifyElem)
                    if (!isValid) {
                        reject()
                        return
                    }
                    const field = form.getValue(null, elem);
                    const method = field['_method'] || "post";
                    common.post($('form').attr('action'), common.formFilter(field), method, function (res) {
                        resolve(res)
                    });
                })
            }
        });
    })(window)
</script>
@endsection


