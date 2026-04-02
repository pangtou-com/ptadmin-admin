@extends('ptadmin.layouts.base')

@section("content")
    <div class="ptadmin-topic-manage-form">
        <form action="" class="layui-form"
        @csrf
        @method($dao->id ? 'put' : 'post')
            {!! \PTAdmin\Build\Layui::text('title', '分类标题',  $dao->title )->required() !!}
            {!! \PTAdmin\Build\Layui::text('subtitle', '副标题', $dao->subtitle  )  !!}
            {!! \PTAdmin\Build\Layui::number('weight', '权重',  $dao->weight )  !!}
            {!! \PTAdmin\Build\Layui::textarea('remark', '分类备注',   $dao->remark  ) !!}
            {!! \PTAdmin\Build\Layui::hidden('topic_id',  $dao->topic_id   ) !!}

            <div class="layui-form-item">
                <label class="layui-form-label">状态</label>
                <div class="layui-input-block">
                    <input type="radio" name="status" value="1" title="显示" {{ $dao->status == 1 ? 'checked' : '' }}>
                    <input type="radio" name="status" value="0" title="隐藏" {{ $dao->status == 0 ? 'checked' : '' }}>
                </div>
            </div>
            <div hidden class="attribute" data-value="{{ $attribute }}">

            </div>
            <div class="layui-form-item">
                <label class="layui-form-label">关联类型</label>
                <div class="layui-input-block">
                    <input type="radio" name="association_type" lay-filter="change-type" value="1"  title="筛选器" {{ $dao->association_type == 1 ? 'checked' : '' }}>
                    <input type="radio" name="association_type" lay-filter="change-type" value="2" title="目标文章" {{ $dao->association_type == 2 ? 'checked' : '' }}>
                </div>
            </div>

            <ul class="topic-association">
                <li class="item {{ $dao->association_type == 1 ? 'show' : '' }}">
                    <div class="layui-form-item">
                        <label class="layui-form-label">属性</label>
                        <div class="layui-input-block">
                            <input class="layui-input" id="tags" name="tags" value="{{ $dao->correlation[1] }}" />
                        </div>
                    </div>
                    <div class="layui-form-item">
                        <label class="layui-form-label">标签</label>
                        <div class="layui-input-block">
                            <input class="layui-input" id="recommend" name="recommend" value="{{ $dao->correlation[2] }}"/>
                        </div>
                    </div>
                    <div class="layui-form-item">
                        <label class="layui-form-label">栏目</label>
                        <div class="layui-input-block">
                            <input class="layui-input" id="column" name="column" value=" value="{{ $dao->correlation[3] }}""/>
                        </div>
                    </div>
                    {!! \PTAdmin\Build\Layui::number('show_num', '限制条数', $dao->show_num  )  !!}
                </li>

                <li class="item {{ $dao->association_type == 2 ? 'show' : '' }}" table>
{{--                    <div class="table-header">--}}

{{--                        <div class="left">--}}
{{--                            <div class="layui-input-group">--}}
{{--                            <input type="text" placeholder="请输入关键词搜索" class="layui-input">--}}
{{--                                <div class="layui-input-split layui-input-suffix search-association" style="cursor: pointer;">--}}
{{--                                    <i class="layui-icon layui-icon-search"></i>--}}
{{--                                </div>--}}
{{--                            </div>--}}
{{--                        </div>--}}
{{--                    </div>--}}
                    <table id="dataTable" lay-filter="dataTable"></table>
                    <!-- 数据格式为 1,2,3,4,5,6,7,8,9 -->
                    <input  name="selected_ids" type="hidden"  value="{{ $dao->correlation[4] }}" />
                </li>
            </ul>
        </form>
    </div>
@endsection

@section("script")
    <script>
        layui.use(['PTPage','table', 'PTForm','form','layCascader'], function () {
            const { PTForm ,table,form , layCascader, PTPage} = layui
            PTForm.init();
            const getIds = '{{ $dao->correlation[4]}}'

            let selected_ids =[]
            if(getIds && getIds.split(',').length>0){
                selected_ids = getIds.split(',').filter(item => item.trim() !== '').map(item => Number(item.trim()));
            }

            form.on('radio(change-type)', function(data){
                const elem = data.elem;
                const value = elem.value;
                $('.topic-association > .item').eq(value-1).show().siblings().hide();
            });

            const options = $('.attribute').data('value')
            const options1 = options.attribute;
            const options2 = options.tag;
            const options3 = options.categories
            let option3Value = '{{ $dao->correlation[3] }}'
            layCascader({
                elem: '#tags',
                clearable: true,
                collapseTags: true,
                minCollapseTagsNumber: 4,
                options: options1,
                placeholder:'请选择标签',
                props: {
                    multiple: true
                }
            });

            layCascader({
                elem: '#recommend',
                clearable: true,
                collapseTags: true,
                minCollapseTagsNumber: 4,
                options: options2,
                placeholder:'请选择推荐属性',
                props: {
                    multiple: true
                }
            });

            layCascader({
                elem: '#column',
                clearable: true,
                collapseTags: true,
                minCollapseTagsNumber: 4,
                options: options3,
                placeholder:'请选择栏目',
                props: {
                    multiple: true
                },
                value: option3Value.split(",").map(Number)
            });

            const page = PTPage.make({
                // event: events,
                urls: {
                    index_url: "{{admin_route('cms/archive-pages')}}",
                    create_url: "{{admin_route('cms/topic')}}",
                    edit_url: "{{admin_route('cms/topic')}}/{id}",
                    del_url: "{{admin_route('cms/topic')}}/{id}",
                    status_url: "",
                },
                table: {
                    cols: [[
                        {type: 'checkbox', fixed: 'left'},
                        {field: 'id', title: 'ID', width: 60},
                        {field: 'title', title: '文章名称'},
                    ]],
                    done: function (res) {
                        const td = $('[data-field="id"]')
                        $.each(td, function(index, item){
                           const $item = $(item)
                           const id = $item.attr('data-content')
                           if(id){
                                if(selected_ids.includes(+id)){
                                    $item.closest('tr').find('input[type="checkbox"]').next().eq(0).click()
                                    $item.closest('tr').find('input[type="checkbox"]').next().eq(1).click()
                                }
                            }
                        })
                    }
                }
            })
            page.on('checkbox', function(obj){
                // console.log(obj);
                if(obj.type === 'one'){
                    if(obj.checked){
                        selected_ids.push(obj.data.id)
                    }else{
                        selected_ids = selected_ids.filter(item => item !== obj.data.id)
                    }
                }
                if (obj.type === 'all') {
                    if(obj.checked){
                        const checkedData = table.checkStatus('dataTable').data;
                        const mapId = checkedData.map(item => item.id)
                        selected_ids = [...selected_ids,...mapId]
                    }
                    else{
                        selected_ids = []
                    }
                }
                // 去重
                selected_ids = [...new Set(selected_ids)]
                $('input[name="selected_ids"]').val(selected_ids.join(','))
            })
            $('.search-association').on('click',function(){
                console.log('搜索数据',  $('input[name="selected_ids"]').val());
            })

            PTForm.init();
        })
    </script>
@endsection
