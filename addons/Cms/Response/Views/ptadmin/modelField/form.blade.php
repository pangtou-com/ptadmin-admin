@extends("ptadmin.layouts.base")

@section("content")
    <div class="layui-fluid">
        <x-hint>
            <p>1、字段标题：字段展示名称</p>
            <p>2、字段名称：示例为数据表字段名称，必须为英文字母，不支持中文，不支持特殊字符，新增后无法修改</p>
            <p>3、字段类型：字段类型支持：text、textarea、select、radio、checkbox、datetime等</p>
        </x-hint>

        <div class="layui-card-body">
            <form action="{{admin_route('cms/model/field/'.($dao->id ?? ""))}}" id="form" class="layui-form">
                @csrf
                @method(isset($dao) ? 'put' : 'post')
                {!! \PTAdmin\Build\Layui::text('title', "字段标题", $dao->title ?? "")->required() !!}
                {!! \PTAdmin\Build\Layui::text('name', "字段名称", $dao->name ?? "")->required()->disabled(isset($dao->name)) !!}
                {!! \PTAdmin\Build\Layui::select('type', "字段类型", $dao->type ?? "")->options(\PTAdmin\Easy\Easy::getComponentsOptions())->required()->disabled(isset($dao->id)) !!}
                {!! \PTAdmin\Build\Layui::text('default_val', "默认值", $dao->default_val ?? "")!!}
                {{--扩展区域实现--}}
                <div id="extend"></div>
                {!! \PTAdmin\Build\Layui::radio('is_release', "是否投稿", $dao->is_release ?? "")->setOptions(\PTAdmin\Admin\Enum\WhetherEnum::getMaps())!!}
                {!! \PTAdmin\Build\Layui::radio('is_search', "是否搜索", $dao->is_search ?? "")->setOptions(\PTAdmin\Admin\Enum\WhetherEnum::getMaps())!!}
                {!! \PTAdmin\Build\Layui::radio('is_table', "列表展示", $dao->is_table ?? "")->setOptions(\PTAdmin\Admin\Enum\WhetherEnum::getMaps())!!}
                {!! \PTAdmin\Build\Layui::radio('is_required', "是否必填", $dao->is_required ?? "")->setOptions(\PTAdmin\Admin\Enum\WhetherEnum::getMaps())!!}
                {!! \PTAdmin\Build\Layui::radio('status', "状态", $dao->status ?? 1)->default(1)->setOptions(\PTAdmin\Admin\Enum\StatusEnum::getMaps())!!}
                {!! \PTAdmin\Build\Layui::number('weight', "权重", $dao->weight ?? 99)!!}
                {!! \PTAdmin\Build\Layui::textarea('intro', "备注信息", $dao->intro ?? "") !!}
                {!! \PTAdmin\Build\Layui::hidden('mod_id', $mod_id)!!}

                {!! pt_submit() !!}
            </form>
        </div>
    </div>
    @include('cms::ptadmin.modelField._extend', ['setup' => $dao->setup ?? []])
@endsection

@section("script")
<script>
    // 模板映射
    const templateMap = {
        text: 'text_html',
        textarea: 'text_html',
        select: 'select_html',
        radio: 'select_html',
        checkbox: 'select_html',
        datetime: 'datetime_html'
    }
    layui.use(['PTForm', 'form'], function () {
        const { PTForm, form } = layui;
        form.on('select(type)', function (data) {
            render(data.value)
        });


        const render = function (value) {
            const extend = $("#extend")
            const template = templateMap[value] || `${value}_html`

            const html = $(`#${template}`)
            extend.html("")
            if (html === undefined) {
                return
            }
            extend.html(html.html())

            /**
             * 选项配置
             */
            $('#options_config').on('click', '.group-btn>button', function () {
                $(this).addClass('layui-btn-normal')
                    .removeClass('layui-btn-primary')
                    .siblings()
                    .removeClass('layui-btn-normal')
                    .addClass('layui-btn-primary')

                $('input[name="setup[type]"]').val($(this).attr('data-type'))
                handleSelectChange()
            })

            // 初始化选项事件
            const initOptions = function () {
                const val = $('input[name="setup[type]"]').val()
                const btn = $('.group-btn>button')
                for (let i = 0; i < btn.length; i++) {
                    if ($(btn[i]).attr('data-type') === val) {
                        $(btn[i]).addClass('layui-btn-normal').removeClass('layui-btn-primary')
                    } else {
                        $(btn[i]).addClass('layui-btn-primary').removeClass('layui-btn-normal')
                    }
                }
            }

            /**
             * 处理配置选项改变的情况
             */
            const handleSelectChange = function () {
                const val = $('input[name="setup[type]"]').val()
                const box = $('.box>div')
                for (let i = 0; i < box.length; i++) {
                    const obj = $(box[i])
                    obj.css('display', obj.hasClass(`t-${val}`) ? 'block' : 'none')
                }

                handleKeyValEvent(val === 'key-val')
            }

            /**
             * 处理键值对事件
             */
            const handleKeyValEvent = function (type = true) {
                const box = $('.t-key-val')
                if (!type) {
                    box.off('click')
                    return
                }
                box.on('click', '*[ptadmin-event]', function () {
                    const html = $(`#key_val_html`)
                    const _this = this
                    const eve = $(this).attr('ptadmin-event')
                    const events = {
                        "key-add": function () {
                            $($(_this).parent().parent().parent()).append(html.html())
                        },
                        "key-del": function () {
                            $(_this).parent().parent().remove()
                        }
                    }

                    events[eve]()
                })
            }

            // 初始化配置
            PTForm.init();
            form.render();
            initOptions()
            handleSelectChange()
        }

        render("{{$dao->type ?? 'text'}}")
    })

</script>
@endsection
