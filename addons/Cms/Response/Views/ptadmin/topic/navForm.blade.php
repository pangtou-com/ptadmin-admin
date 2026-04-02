@extends('ptadmin.layouts.base')

@section("content")
    <div class="ptadmin-topic-manage-form">
        <form action="" class="layui-form"
            @csrf
            @method($dao->id ? 'put' : 'post')
            {!! \PTAdmin\Build\Layui::text('title', '导航标题',  $dao->title )->required() !!}
            {!! \PTAdmin\Build\Layui::text('subtitle', '副标题',  $dao->subtitle   ) !!}
            {!! \PTAdmin\Build\Layui::hidden('topic_id',  $dao->topic_id   ) !!}
            {!! \PTAdmin\Build\Layui::number('weight', '权重',  $dao->weight   ) !!}
            {!! \PTAdmin\Build\Layui::textarea('remark', '导航备注',  $dao->remark     ) !!}

            <div class="layui-form-item">
                <label class="layui-form-label">状态</label>
                <div class="layui-input-block">
                    <input type="radio" name="status" value="1" title="显示" {{ $dao->status == 1 ? 'checked' : '' }}>
                    <input type="radio" name="status" value="0" title="隐藏" {{ $dao->status == 0 ? 'checked' : '' }}>
                </div>
            </div>
            <div class="layui-form-item">
                <label class="layui-form-label">导航链接</label>
                <div class="layui-input-block">
                    <input type="radio" name="navigation_type" lay-filter="change-type" value="1" title="自定义URL" {{ $dao->navigation_type == 1 ? 'checked' : '' }}>
                    <input type="radio" name="navigation_type" lay-filter="change-type" value="2" title="已有栏目"  {{ $dao->navigation_type == 2 ? 'checked' : '' }}>
                </div>
            </div>

            <div class="set-topic-nav-url">
                <div class="layui-form-item" {{ $dao->navigation_type != 1 ? 'hidden' : '' }}>
                    <label class="layui-form-label">自定义URL</label>
                    <div class="layui-input-block">
                        <input type="text" name="url" placeholder="请输入" class="layui-input" value="{{ $dao->url }}">
                    </div>
                </div>
                <div class="category_json" data-value="{{ $categories }}"></div>
                <div class="layui-form-item" {{ $dao->navigation_type != 2 ? 'hidden' : '' }}>
                    <label class="layui-form-label">选择栏目</label>
                    <div class="layui-input-block">
                        <input class="layui-input" id="column" name="column" value=""/>
                    </div>
                </div>
            </div>
        </form>
    </div>
@endsection

@section("script")
    <script>
        layui.use(['PTForm','form' ,'layCascader'], function () {
            const { PTForm ,form , layCascader} = layui
            let categories = $(".category_json").data('value');
            let optionValue = '{{ $dao->column_ids }}'
            PTForm.init();
            layCascader({
                elem: '#column',
                clearable: true,
                collapseTags: true,
                minCollapseTagsNumber: 4,
                options: categories,
                placeholder:'请选择栏目',
                props: {
                    multiple: true
                },
                value: optionValue.split(",").map(Number)
            });

            form.on('radio(change-type)', function(data){
                const elem = data.elem;
                const value = elem.value;
                const urlElem =  $('.set-topic-nav-url > .layui-form-item')
                if(value==='1'){
                    $(urlElem[0]).show().siblings().hide();
                }
                if(value ==='2'){
                    $(urlElem[1]).show().siblings().hide();
                }
            });
        })
    </script>
@endsection
