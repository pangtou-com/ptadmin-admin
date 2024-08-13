@extends('ptadmin.layouts.base')

@section("content")
    <div class="layui-fluid">
        <div class="layui-card-body">
            <div class="layui-form box"><input type="checkbox" lay-filter="perm" value="all" title="全选"></div>
            <form class="ptadmin-perm layui-form" action="{{admin_route('roles-permission')}}/{{$id}}">
                @method('post')

                {!! $view !!}

                {!! pt_submit() !!}
            </form>
        </div>
    </div>
    <style>
        .box {
            padding-left: 10px;
        }

        .children {
            margin-left: 30px;
        }

        .box, .children, .header {
            margin-bottom: 10px;
        }
    </style>
@endsection

@section("script")
    <script>
        layui.use(['form', 'common'], function () {
            const {form, common} = layui
            form.on('submit(PT-submit)', function (obj) {
                let url = $('form').attr('action');
                common.post(url, {ids: obj.field}, 'post', function (res) {
                    if (res.code === 0) {
                        let index = parent.layer.getFrameIndex(window.name);
                        parent.layer.close(index);
                    } else {
                        layer.msg(res.message, {icon: 2});
                    }
                });
            })

            form.on('checkbox(perm)', function (obj) {
                const {elem, value} = obj
                const checked = elem.checked
                // 全选设置
                if (value === 'all') {
                    $(".ptadmin-perm input[type='checkbox']").prop("checked", checked)
                    form.render('checkbox')
                    return
                }

                // 下级
                const children = $(`.children[data-parent-id=${value}]`)
                if (children.length > 0) {
                    children.find("input[type='checkbox']").prop("checked", checked)
                }

                // 上级需要半选状态
                const parent = $(elem).attr('data-parent-id')
                if (parseInt(parent) !== 0) {
                    const children = $(`.children[data-parent-id=${parent}] input[type='checkbox']`).length
                    const childrenChecked = $(`.children[data-parent-id=${parent}] input[type='checkbox']:checked`).length
                    const parentEl = $(`#idx_${parent}`)
                    if (children !== childrenChecked) {
                        parentEl.prop("checked", false)
                        parentEl.prop("indeterminate", childrenChecked !== 0)
                    } else {
                        if (checked) {
                            parentEl.prop("checked", true)
                        } else {
                            parentEl.prop("indeterminate", false)
                        }
                    }
                }
                form.render('checkbox')
            });

        })
    </script>
@endsection
