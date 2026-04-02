@extends('ptadmin.layouts.base')

@section("content")
    <div class="ptadmin-score-detection-container">
        <x-hint>
            <div><strong>评分检测</strong></div>
            <p>1、Lorem ipsum dolor sit amet consectetur, adipisicing elit. Laudantium aut voluptatum nemo repudiandae totam dolore accusamus. Quibusdam unde labore sed sint, natus, consequatur pariatur libero inventore dolorem architecto, necessitatibus assumenda!</p>
            <p>2、Lorem ipsum dolor, sit amet consectetur adipisicing elit. Inventore quidem nesciunt fugit ipsum magni molestias voluptate placeat reiciendis iure! Dolores odit ducimus eveniet, in vitae ut doloremque similique ipsa blanditiis.</p>
        </x-hint>

        <div class="score-detection-list">
            <div class="layui-tab layui-tab-brief">
                <ul class="layui-tab-title">
                    <li class="layui-this">全部</li>
                </ul>
                <div class="layui-tab-content">
                    <div class="layui-tab-item layui-show">

                        <div class="layui-row layui-col-space15">
                        @foreach($categories['detail'] as $key => $category)
                        <!-- 列表 -->
                            <div class="layui-col-xs12 layui-col-sm3 layui-col-xl2 content">
                                <div class="image">
                                    <img src="/ptadmin/images/logo.png" alt="" >
                                </div>
                                <div class="info-box">
                                    <div class="title">{{ $category['title'] }}</div>
                                    <div class="author">
                                        <div class="name">作者：PTAdmin</div>
                                        <div class="score score_{{$category['path']}}">评分：{{ $category['score'] >=0 ? $category['score'].'分' : '暂未评分'  }}</div>
                                    </div>
                                    <div class="footer">
{{--                                        <div class="status layui-form">--}}
{{--                                            <span>状态：</span>--}}
{{--                                            <input type="checkbox" name="" lay-skin="switch" title="开启|关闭">--}}
{{--                                        </div>--}}
                                        <div class="layui-btn-group">
                                            <button type="button" ptadmin-event="handleDetail" class="layui-btn layui-btn-sm" data-path="{{ $category['path'] }}" >
                                                <i class="layui-icon layui-icon-eye layui-font-12"></i>
                                            </button>
                                            <button type="button" ptadmin-event="handleDetection" class="layui-btn layui-btn-sm layui-bg-blue" data-path="{{ $category['path'] }}" data-name="{{ $category['title'] }}">
                                                <i class="layui-icon layui-icon-util layui-font-12"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                        </div>
                    </div>

                </div>
            </div>
        </div>

    </div>
@endsection

@section("script")
    <script>
        layui.use(['layer', 'element'], function () {
            const { layer ,  element } = layui

            $('[ptadmin-event="handleDetail"]').on('click',function(){
                let path = $(this).data('path');
                location.href = "{{admin_route('cms/score-detection/details')}}?path="+path
            })

            const scanBox = `<div class="ptadmin-scan-box">
                                    <div class="header" id="scan-header">
                                        <div class="title scan-header-title"></div>
                                        <div class="number">文件数量：<span class="scan-header-number"></span></div>
                                    </div>

                                    <div class="progress">
                                        <div class="detecting"><span class="scan-header-detecting-title">检测</span><span class="scan-header-detecting"></span></div>
                                        <div class="layui-progress layui-progress-big" lay-showPercent="true" lay-filter="demo-filter-progress">
                                            <div class="layui-progress-bar" lay-percent="0%"></div>
                                        </div>
                                    </div>

                                    <div class="footer">
                                        <div class="detected">已检测：<span class="scan-header-detecting-number">0</span></div>
                                        <div class="btn">
                                            <button type="button" ptadmin-event="save-scan" class="layui-btn layui-btn-xs layui-bg-red getMessagePath" attr_path="">检测</button>
                                            <button type="button" ptadmin-event="close-scan" class="layui-btn layui-btn-xs layui-bg-gray">关闭</button>
                                        </div>
                                    </div>
                                </div>`

            function getScore(path, files, name){
                $('.scan-header-detecting-title').html('进度：')

                $.each(files, function (index, value){
                    if(index < 1){
                        $.ajax({
                            url: '{{admin_route('cms/score-detection')}}',
                            data: {path:path, name:name},
                            type:'put',
                            success:function(res){


                            },
                            error:function (e){
                                layer.msg(e);
                            }
                        })
                    }
                    $('.scan-header-detecting-number').html(index+1)
                    $('.scan-header-detecting').html(value.score+'%')
                    element.progress('demo-filter-progress', value.score+'%');
                })

                element.render();
            }
            $('[ptadmin-event="handleDetection"]').on('click', function(){
                let path = $(this).data('path');
                let name = $(this).data('name');
                let allFiles = new Array();
                const layerScan = layer.open({
                    type: 1,
                    closeBtn:0,
                    area: ['500px', '200px'],
                    title: false,
                    shade: 0.6,
                    shadeClose:false,
                    anim: 0,
                    resize:false,
                    content: scanBox,
                    move: '#scan-header',
                    success:function(layero, index, that){
                        console.log(layero);
                        // 关闭事件
                        layero.find('[ptadmin-event="close-scan"]').on('click', function(){
                            layer.close(index);
                            location.href = "{{admin_route('cms/score-detection')}}"
                        });
                        $.ajax({
                            url: '{{admin_route('cms/score-detection')}}',
                            data: {path:path, name:name},
                            type:'post',
                            success:function(res){
                                $('.scan-header-title').html(name)
                                $('.scan-header-number').html(res.data.num)
                                allFiles = res.data.file
                                $('.getMessagePath').attr('attr_path', path);
                            },
                            error:function (e){
                                layer.msg(e);
                            }
                        })
                        layero.find('[ptadmin-event="save-scan"]').on('click', function(){
                            getScore(path, allFiles,name)
                        });
                    }
                });
            })


        })
    </script>
@endsection
