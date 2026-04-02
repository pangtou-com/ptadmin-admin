@extends('ptadmin.layouts.base')

@section("content")
<div class="layui-card ptadmin-page-container">
    <div class="layui-card-body">
        <input type="hidden" value="{{$categoryId ?? 0}}" name="category_id" id="category_id">
        <table id="dataTable" lay-filter="dataTable"></table>
        <script type="text/html" id="expand">
            <div class="ptadmin-page-expand-image-box">
                @{{# if(d.cover !== ""){ }}
                    <i class="layui-icon-picture layui-icon image" data-url="@{{d.cover}}"></i>
                @{{# } }}
                @{{# if(d.attribute_text  && d.attribute_text.length > 0){ }}
                    <div class="tags">【@{{d.attribute_text[0]}}】</div>
                @{{# } }}

                <div class="content">@{{= d.title }}{{-- <a href="@{{= d.a_url }}">链接1</a>--}}</div>
            </div>
        </script>
        <script type="text/html" id="batch-operation">
            <div class="ptadmin-page-batch-operation">
                <a class="layui-btn layui-btn-sm" lay-event="batch">
                    批量操作 <i class="layui-icon layui-icon-down"></i>
                </a>
            </div>
        </script>
    </div>
</div>
@endsection

@section("script")
<script>
    (function ($win) {
        const cms = {
            current: undefined,
            init: function () {
                layui.use(['PTPage','layer','table','dropdown','form'], function () {
                    const { PTPage , layer ,table ,dropdown,form} = layui;
                    const viewData =  {
                        attrStr:'',
                        attrStrLayerTitle:''
                    }
                    const events = {
                        batch:function(obj){
                             dropdown.render({
                                 elem: obj.elem,
                                 show: true,
                                 data: [
                                    { title: '新增属性', id:0, event:"attr" },
                                    { title: '删除属性', id:1, event:"attr" },
                                    { type:'-' },
                                    { title: '复制文档', id:2, event:"copyDoc" },
                                    { title: '移动文档', id:3, event:"moveDoc" },
                                    { title: '删除文档', id:4, event:"delDoc" },
                                    { type:'-' },
                                    { title: '取消发布', id:5, event:"cancel" },
                                    { title: '发布文档', id:6, event:"release" },
                                ],
                                click:function(obj){
                                    const tableData  = table.checkStatus('dataTable').data
                                    if(!tableData.length){
                                        layer.msg('请选择要操作的数据');
                                        return
                                    }
                                    if(obj.event === 'attr'){
                                        const ids = tableData.map(item => item.id);
                                        viewData.attrStrLayerTitle = obj.id === 0 ? '新增属性':'删除属性'
                                        viewData.attrStr = '<form class="layui-form" action="" style="padding-right: 15px;"  lay-filter="attr-from">'
                                                            +    '<div class="layui-form-item">'
                                                            +       '<label class="layui-form-label">文档属性</label>'
                                                            +       '<div class="layui-input-block">'
                                                            +           '<input type="radio" name="docAttr" value="0" title="占位" checked>'
                                                            +           '<input type="radio" name="docAttr" value="1" title="占位">'
                                                            +        '</div>'
                                                            +    '</div>'
                                                            +    '<div class="layui-form-item">'
                                                            +       '<label class="layui-form-label">文档ID</label>'
                                                            +       '<div class="layui-input-block">'
                                                            +           `<input type="text" name="ids" lay-verify="required" class="layui-input" value="${ids.join(',')}">`
                                                            +        '</div>'
                                                            +    '</div>'
                                                            +'</form>'
                                    }
                                    events[obj.event].call(undefined,obj);
                                }
                             });
                        },
                        attr:function(obj){
                            const checkedData = table.checkStatus('dataTable').data;
                            layer.open({
                                type: 1,
                                title: viewData.attrStrLayerTitle,
                                btn: ['确定', '关闭'] ,
                                area: ['420px', '240px'], // 宽高
                                btn1: function(index, layero, that){
                                    const formFiled = form.val("attr-from") // 表单数据
                                    // 新增
                                    if (obj.id === 0) {
                                        layer.close(index);
                                    }
                                    // 删除
                                    if (obj.id === 1) {
                                        layer.confirm('确认删除吗？', { icon: 3 }, function(idx){
                                            console.log('确认');
                                            layer.closeAll()
                                        });
                                    }

                                },
                                success:function(){
                                    form.render()
                                },
                                content: viewData.attrStr
                            });
                        },
                        copyDoc:function(){
                            console.log('复制文档');
                        },
                        moveDoc:function(){
                            console.log('移动文档');
                        },
                        delDoc:function(){
                            layer.confirm('确认删除吗？', { icon: 3 }, function(){
                                console.log('确认');
                            });
                        },
                        cancel:function(){
                            layer.confirm('确认取消发布吗？', { icon: 3 }, function(){
                                console.log('确认');
                            });
                        },
                        release:function(){
                            console.log('发布文档');
                        },
                    }

                    // 默认列
                    const default_col = [
                        {checkbox: true, fixed: true},
                        {field: 'id', title: 'ID', width: 60},
                        {field: 'title', title: '文章标题1', search: true, templet: "#expand"},
                        {field: 'category.title', title: '所属栏目', search: {
                                type: 'select',
                                options: @json($category)
                            }, width: 120},
                        {field: 'mod.title', title: '所属模型', width:  130},
                        {field: 'views', title: '访问量', width: 80},
                        {field: 'attribute_text', title: '推荐属性', width:  130},
                        {field: 'spider', title: '收录状态', width: 130},

                        {field: 'status_text', title: '状态', templet: PTPage.format.tags({
                                key: "status",
                                map: {0: 'default', 1: 'warning', 2: 'success', 7: 'info', 8: 'primary', 9: 'danger'}
                        }), width:  130},
                        {
                            fixed: 'right', width: 160, title: '{{ __("system.btn_handle") }}', align: 'center',
                            operate: ['edit', 'del']
                        },
                    ]

                    cms.current = PTPage.make({
                        btn_left: null,
                        btn_right: null,
                        urls: {
                            index_url: "{{admin_route('cms/archive-pages')}}",
                            edit_url: "{{admin_route('cms/archive')}}/{id}",
                            del_url: "{{admin_route('cms/archive')}}/{id}?category_id={{$categoryId ?? 0}}",
                        },
                        table: {
                            cols: [default_col],
                            css: [
                                '.layui-table-page{display: flex;justify-content: space-between;}.layui-table-pageview{order:2;}' // 让分页栏居中
                            ].join(''),
                            pagebar:'#batch-operation',
                            done:function(){
                                let tips = ''
                                $(".ptadmin-page-expand-image-box > .image").on({
                                    mouseenter: function () {
                                        const imgUrl = $(this).data('url')
                                        const img = `<div style="width:100px;text-align: center;" class="image-box"><img style="max-width:100%;max-height:100%" src="${imgUrl}"></div>`
                                        tips = layer.tips(img, this,{area:'200', tips:[4,'#fff'], time:0});
                                    },
                                    mouseleave: function () {
                                        layer.close(tips);
                                    }
                                });

                                table.on('pagebar(dataTable)', function(obj){
                                    const params = {
                                        obj,
                                        elem:this
                                    }
                                    events[obj.event].call(undefined, params)
                                });
                            }
                        }
                    });
                });
            },
            refresh: function () {
                cms.current.getCurrentTable.reload()
            }
        }
        cms.init()
        $win.cms = cms
    })(window)
</script>
@endsection
