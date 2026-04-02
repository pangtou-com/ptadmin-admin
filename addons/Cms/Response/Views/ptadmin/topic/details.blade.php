
@extends('ptadmin.layouts.base')
@section("content")
    <div class="ptadmin-topic-details layui-card">
        <div class="layui-panel">
                <x-hint>
                    <div><strong>专题详情</strong></div>
                </x-hint>
                <div class="layui-card-header ptadmin-card-header">
                    <div class="layui-btn-group">
                        <button type="button" class="layui-btn layui-btn-sm">
                            预留按钮
                        </button>
                        <button type="button" class="layui-btn layui-btn-sm">
                            预留按钮
                        </button>
                    </div>
                    <a href="#" class="layui-btn layui-btn-sm layui-bg-blue"  lay-submit lay-filter="back">返回列表</a>
                </div>

                <div class="layui-card-body ptadmin-card-info">
                        <div class="layui-row layui-col-space12">
                            <div class="layui-col-sm12 layui-col-md6 layui-col-lg3 item">
                                <div class="label">标题：</div>
                                <div class="value">{{ $dao->title }}</div>
                            </div>

                            <div class="layui-col-sm12 layui-col-md6 layui-col-lg3 item">
                                <div class="label">副标题：</div>
                                <div class="value">{{ $dao->subtitle }}</div>
                            </div>

                            <div class="layui-col-sm12 layui-col-md6 layui-col-lg3 item">
                                <div class="label">浏览：</div>
                                <div class="value">{{ $dao->num }}</div>
                            </div>

                            <div class="layui-col-sm12 layui-col-md6 layui-col-lg3 item">
                                <div class="label">访问路径：</div>
                                <div class="value">{{ $dao->url }}</div>
                            </div>

                            <div class="layui-col-sm12 layui-col-md6 layui-col-lg3 item">
                                <div class="label">模板文件：</div>
                                <div class="value">{{ $dao->topic_template }}</div>
                            </div>

                            <div class="layui-col-sm12 layui-col-md6 layui-col-lg3 item">
                                <div class="label">权重：</div>
                                <div class="value">{{ $dao->weight }}</div>
                            </div>

                            <div class="layui-col-sm12 layui-col-md6 layui-col-lg3 item">
                                <div class="label">SEO标题：</div>
                                <div class="value">{{ $dao->seo_title }}</div>
                            </div>

                            <div class="layui-col-sm12 layui-col-md6 layui-col-lg3 item">
                                <div class="label">SEO关键词：</div>
                                <div class="value">{{ $dao->seo_keyword }}</div>
                            </div>

                            <div class="layui-col-sm12 layui-col-md6 layui-col-lg3 item">
                                <div class="label">SEO描述：</div>
                                <div class="value">{{ $dao->seo_doc }}</div>
                            </div>

                            <div class="layui-col-sm12 layui-col-md6 layui-col-lg3 item">
                                <div class="label">状态：</div>
                                <div class="value">{{ $dao->status == 1 ? '显示' : '隐藏' }}</div>
                            </div>

                            <div class="layui-col-sm12 item middle">
                                <div class="label">备注信息：</div>
                                <div class="value"> {{ $dao->remark }} </div>
                            </div>

                            <div class="layui-col-sm12 item middle">
                                <div class="label">轮播图：</div>
                                <div class="value">
                                    <div class="ptadmin-image-list">
                                        @foreach($dao->banners as $banner)
                                            <div class="ptadmin-image image-html">
                                                <img class="layui-img-content" src="{{ $banner }}"/>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                        </div>
                </div>

                <div class="layui-tab layui-tab-brief">
                    <ul class="layui-tab-title">
                        <li class="layui-this">专题导航</li>
                        <li>专题分类</li>
                    </ul>
                    <div class="layui-tab-content">
                        <div class="layui-tab-item layui-show">
                            <div class="p-20">
                                <table class="layui-hide" id="topic_nav"></table>
                            </div>
                        </div>
                        <div class="layui-tab-item">
                            <div class="p-20">
                                <table class="layui-hide" id="topic_col"></table>
                            </div>
                        </div>
                    </div>
                </div>
        </div>
    </div>
@endsection

@section("script")
    <script>

        layui.use(['PTForm','form', 'PTPage', 'common'], function () {
            const {  form, PTPage } = layui
            form.on('submit(back)', function (data) {
                location.href = '{{admin_route('cms/topics')}}'
            });

            PTPage.make({
                // event: events,
                urls: {
                    index_url: "{{admin_route('cms/topic/navigations')}}/"+{{ $dao->id }},
                },
                search: false,
                table: {
                    elem: '#topic_nav',
                    cols: [[
                        {field: 'id', title: 'ID', width: 60},
                        {field: 'title', title: '导航标题'},
                        {field: 'subtitle', title: '导航副标题'},
                        {field: 'remark', title: '备注信息'},
                        {field: 'weight', title: '排序', width: 80},
                        {field: 'navigation_type', title: '类型', templet: function (data) {
                                return data.navigation_type === 1 ? 'url' : '已有栏目';
                            }
                        },
                        {field: 'status', title: '有效状态', width: 90, templet: function (data) {
                                return data.status === 1 ? '有效' : '无效';
                            }
                        },
                    ]],
                    done: function (res) { }
                }
            })

            PTPage.make({
                // event: events,
                urls: {
                    index_url: "{{admin_route('cms/topic/associations')}}/"+{{ $dao->id }},
                },
                search: false,
                table: {
                    elem: '#topic_col',
                    cols: [[
                        {field: 'id', title: 'ID', width: 80},
                        {field: 'title', title: '分类标题', width: 650},
                        {field: 'subtitle', title: '副标题', width: 650},
                        {field: 'notes', title: '备注信息', width: 600},
                        {field: 'association_type', title: '关联类型', width: 120, templet: function (data) {
                                return data.association_type === 1 ? '筛选器' : '目标文章';
                            }
                        },
                        {field: 'weight', title: '排序', width: 80},
                        {field: 'status', title: '有效状态', width: 90, templet: function (data) {
                                return data.status === 1 ? '有效' : '无效';
                            }
                        },
                    ]],
                    done: function (res) { }
                }
            })

        })

    </script>
@endsection

@section("head")
    <style>
        .layui-table-tree-iconCustom i {
            margin-right: 5px;
        }
        .p-20 {
            padding: 20px;
        }
    </style>
@endsection
