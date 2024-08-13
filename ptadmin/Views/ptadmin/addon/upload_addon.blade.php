@extends('ptadmin.layouts.base')

@section("content")
    <div class="layui-fluid">
        <div class="layui-card-body">
            <form action="{{admin_route('local-addon-upload')}}/{{$dao->id}}" id="form" class="layui-form">
                @csrf
                @method('put')
                <div class="layui-card">
                    <div class="layui-form-item">
                        <label class="layui-form-label">择提交表</label>
                        <div class="layui-input-block" style="height: 200px; overflow-y: auto;">
                            @foreach(\Zane\Assisted\Service\CreateSql::getAllTable() as $key => $table)
                                <div class="layui-form" style="display: flex">
                                    <div style="min-width: 200px">
                                        <input type="checkbox" value="{{ $key }}" title="{{ $table['label'] }}"
                                               class="all_name all_name_{{ $key }}" lay-filter="all_name">
                                    </div>

                                    <input type="checkbox" name="table[]" value="{{ $table['value'] }}" title="结构"
                                           class="table_name table_name_{{ $key }}" lay-filter="table_name"
                                           data-key="{{ $key }}">
                                    <input type="checkbox" name="set_data[]" value="{{ $table['value'] }}" title="数据"
                                           class="set_data set_data_{{ $key }}" lay-filter="data_name"
                                           data-key="{{ $key }}">
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
                <div class="layui-form-item">
                    <label class="layui-form-label"></label>
                    <div class="layui-input-block">
                        <input type="hidden" name="code" value="{{ $dao->code }}">
                        <button type="button" class="layui-btn layui-btn-info save_sql" lay-submit>生成sql文件</button>
                    </div>
                </div>

                <div class="layui-form-item">
                    <label class="layui-form-label">上传的目录</label>
                    <div class="layui-input-block">
                        @foreach($dirsFiles['dir'] as $dirFile)
                            @if($dirFile != '.' && $dirFile != '..' &&  is_dir($directory.'/'.$dirFile))
                                <div>
                                    <i class="layui-icon layui-icon-export"></i>&emsp;/{{$dirFile}}
                                </div>
                            @endif
                        @endforeach
                    </div>
                </div>

                <div class="layui-form-item">
                    <label class="layui-form-label">上传的文件</label>
                    <div class="layui-input-block fileKids">
                        @foreach($dirsFiles['file'] as $dirFile)
                            @if($dirFile != '.' && $dirFile != '..' &&  !is_dir($directory.'/'.$dirFile))
                                <div>
                                    <i class="layui-icon layui-icon-file"></i>&emsp;{{$dirFile}}
                                </div>
                            @endif
                        @endforeach
                    </div>
                </div>


                <div class="layui-form-item">
                    <label class="layui-form-label"></label>
                    <div class="layui-input-block">
                        <button type="button" class="layui-btn layui-btn-info layui-btn-radius" lay-submit
                                lay-filter="PT-submit">提交
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

@endsection

@section("script")
    <script>
        layui.use(["PTForm", 'form', 'jquery', 'upload', 'layer', 'element', 'util'], function () {
            const {PTForm} = layui
            PTForm.init();
            var form = layui.form;
            var $ = layui.jquery;
            var layer = layui.layer

            form.on('checkbox(all_name)', function (data) {
                let table_name = '.table_name_' + data.elem.value;
                let set_data = '.set_data_' + data.elem.value;
                $(set_data).each(function (index, item) {
                    item.checked = data.elem.checked;
                });
                $(table_name).each(function (index, item) {
                    item.checked = data.elem.checked;
                });
                form.render('checkbox');
            });

            form.on('checkbox(table_name)', function (data) {
                let all_name = '.all_name_' + $(this).data('key');
                let set_data = '.set_data_' + $(this).data('key');
                let set_dataType = data.elem.checked
                if (data.elem.checked) {
                    $(set_data).each(function (index, item) {
                        set_dataType = item.checked ? set_dataType : false;
                    });
                }
                $(all_name).each(function (index, item) {
                    console.log(set_dataType)
                    item.checked = set_dataType;
                });
                form.render('checkbox');
            });

            form.on('checkbox(data_name)', function (data) {
                let all_name = '.all_name_' + $(this).data('key');
                let table_name = '.table_name_' + $(this).data('key');
                let table_nameType = data.elem.checked
                if (data.elem.checked) {
                    $(table_name).each(function (index, item) {
                        table_nameType = item.checked ? table_nameType : false;
                    });
                }
                $(all_name).each(function (index, item) {
                    console.log(table_nameType)
                    item.checked = table_nameType;
                });
                form.render('checkbox');
            });

            $('.save_sql').click(function () {
                let tables = new Array();
                let set_data = new Array();
                $.each($(".table_name"), function (index, value) {
                    if (value.checked) {
                        tables.push(value.value)
                    }
                })

                $.each($(".set_data"), function (index, value) {
                    if (value.checked) {
                        set_data.push(value.value)
                    }
                })

                if (tables.length < 1 && set_data.length < 1) {
                    layer.msg('请选择数据库');
                    return
                }
                let index = layer.load()
                let url = '{{admin_route('local-addon-sql')}}'
                let obj = {
                    tables: tables,
                    set_data: set_data,
                    code: '{{$dao->code}}'
                }
                $.post(url, obj, function (data) {
                    let html = '';
                    $.each(data.data.file, function (index, value) {
                        html += '<div>' +
                            '<i class="layui-icon layui-icon-file"></i>&emsp;' + value +
                            '</div>'
                    })
                    $('.fileKids').html(html)
                    layer.close(index);
                    layer.msg('生成成功');
                })
            })
        });
    </script>
    <style>
        .red_point {
            color: red;
        }
    </style>
@endsection
