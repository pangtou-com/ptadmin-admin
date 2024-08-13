@extends('ptadmin.layouts.base')

@section("content")
    <div class="layui-fluid">
        <div class="layui-card-body">
            <form action="{{admin_route('local-install')}}" id="form" class="layui-form">
                @csrf
                @method('post')

                <div class="layui-form-item">
                    <label class="layui-form-label">
                        <color class="red_point">*</color>{!! L("addons", "filepath") !!}</label>
                    <div class="layui-input-block">
                        <button type="button" class="layui-btn demo-class-accept" lay-options="{
                                accept: 'file',
                                exts: 'zip|rar'
                              }">
                            <i class="layui-icon layui-icon-upload"></i>
                            <span class="filepath_text">上传文件</span>
                            <input type="hidden" name="filepath" class="filepath_value" value="">
                        </button>
                    </div>
                </div>

                <x-hint>
                    <color class="red_point">温馨提醒：</color>
                    选择你的安装包(压缩包)
                </x-hint>
                {{--                <div class="layui-form-item">--}}
                {{--                    <label class="layui-form-label"></label>--}}
                {{--                    <div class="layui-input-block">--}}
                {{--                        <button type="button" class="layui-btn layui-btn-info layui-btn-radius" lay-submit lay-filter="PT-submit">提交</button>--}}
                {{--                    </div>--}}
                {{--                </div>--}}
            </form>
        </div>
    </div>

@endsection

@section("script")
    <script>
        layui.use(["PTForm", 'form', 'jquery', 'upload', 'layer', 'element', 'util'], function () {
            const {PTForm} = layui
            PTForm.init();
            var upload = layui.upload;
            var index1

            upload.render({
                elem: '.demo-class-accept', // 绑定多个元素
                url: '{{admin_route('local-install')}}', // 此处配置你自己的上传接口即可
                accept: 'file', // 普通文件
                before: function (obj) {
                    index1 = layer.load()
                },
                done: function (res) {
                    layer.close(index1)
                    if (res.code != 0) {
                        layer.msg(res.message)
                        return
                    }
                    layer.msg('安装成功')
                    setTimeout(function () {
                        let index2 = parent.layer.getFrameIndex(window.name); //先得到当前iframe层的索引
                        parent.layer.close(index2);
                    }, 2000)
                }
            });
        });
    </script>
    <style>
        .red_point {
            color: red;
        }
    </style>
@endsection

