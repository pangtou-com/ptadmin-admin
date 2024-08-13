@extends('ptadmin.layouts.base')

@section("content")
    <div class="layui-fluid">
        <div class="layui-card-body">
            <form action="{{admin_route('local-addon')}}@if($dao->id)/{{$dao->id}}@endif" id="form" class="layui-form">
                @csrf
                @method($dao->id ? 'put' : 'post')
                <div class="layui-form-item">
                    <div class="layui-inline">
                        <label class="layui-form-label">
                            <color class="red_point">*</color>{!! L("local_addons", "title") !!}</label>
                        <div class="layui-input-inline layui-input-wrap">
                            <input type="text" name="title" lay-verify="required" autocomplete="off" lay-affix="clear"
                                   class="layui-input" placeholder="请输入{!! L("local_addons", "title") !!}">
                        </div>
                    </div>
                    <div class="layui-inline">
                        <label class="layui-form-label">
                            <color class="red_point">*</color>{!! L("local_addons", "code") !!}</label>
                        <div class="layui-input-inline layui-input-wrap">
                            <input type="text" name="code" lay-verify="required" autocomplete="off" lay-affix="clear"
                                   class="layui-input" placeholder="请输入{!! L("local_addons", "code") !!}">
                        </div>
                    </div>
                </div>

                <div class="layui-form-item">
                    <div class="layui-inline">
                        <label class="layui-form-label">
                            <color class="red_point">*</color>{!! L("local_addons", "developer") !!}</label>
                        <div class="layui-input-inline layui-input-wrap">
                            <input type="text" name="author" lay-verify="required" autocomplete="off" lay-affix="clear"
                                   class="layui-input" value="PTAdmin"
                                   placeholder="请输入{!! L("local_addons", "developer") !!}">
                        </div>
                    </div>
                    <div class="layui-inline">
                        <label class="layui-form-label">{!! L("local_addons", "email") !!}</label>
                        <div class="layui-input-inline layui-input-wrap">
                            <input type="text" name="email" autocomplete="off" lay-affix="clear" class="layui-input"
                                   placeholder="请输入{!! L("local_addons", "email") !!}">
                        </div>
                    </div>
                </div>

                <div class="layui-form-item">
                    <div class="layui-inline">
                        <label class="layui-form-label">{!! L("local_addons", "homepage") !!}</label>
                        <div class="layui-input-inline layui-input-wrap">
                            <input type="text" name="homepage" autocomplete="off" lay-affix="clear" class="layui-input"
                                   placeholder="请输入{!! L("local_addons", "homepage") !!}">
                        </div>
                    </div>
                    <div class="layui-inline">
                        <label class="layui-form-label">{!! L("local_addons", "docs") !!}</label>
                        <div class="layui-input-inline layui-input-wrap">
                            <input type="text" name="docs" autocomplete="off" lay-affix="clear" class="layui-input"
                                   placeholder="请输入{!! L("local_addons", "docs") !!}">
                        </div>
                    </div>
                </div>

                <div class="layui-form-item">
                    <div class="layui-inline">
                        <label class="layui-form-label">
                            <color class="red_point">*</color>{!! L("local_addons", "version") !!}</label>
                        <div class="layui-input-inline layui-input-wrap">
                            <input type="text" name="version" lay-verify="required" value="1.0.0" autocomplete="off"
                                   lay-affix="clear" class="layui-input"
                                   placeholder="请输入{!! L("local_addons", "version") !!}">
                        </div>
                    </div>
                    <div class="layui-inline">
                        <label class="layui-form-label">
                            <color class="red_point">*</color>{!! L("local_addons", "require_version") !!}</label>
                        <div class="layui-input-inline layui-input-wrap">
                            <input type="text" name="require_version" lay-verify="required" value="1.0.0"
                                   autocomplete="off" lay-affix="clear" class="layui-input"
                                   placeholder="请输入{!! L("local_addons", "require_version") !!}">
                        </div>
                    </div>
                </div>

                <div class="layui-form-item">
                    <label class="layui-form-label">
                        <color class="red_point">*</color>{!! L("local_addons", "intro") !!}</label>
                    <div class="layui-input-block">
                        <textarea name="intro" placeholder="请输入{!! L("local_addons", "intro") !!}"
                                  class="layui-textarea" lay-verify="required" lay-affix="clear"></textarea>
                    </div>
                </div>
                <x-hint>
                    <color class="red_point">注意：</color>
                    插件地址'/addons/你的{!! L("local_addons", "code") !!}'
                </x-hint>
                <x-hint>
                    <color class="red_point">注意：</color>
                    插件的静态文件请放在'/public/addons/你的{!! L("local_addons", "code") !!}'
                </x-hint>
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
        });
    </script>
    <style>
        .red_point {
            color: red;
        }
    </style>
@endsection

