@extends('ptadmin.layouts.base')

@section("content")
    <div class="ptadmin-topic-manage-form">
        <form action="{{admin_route('cms/topic')}}@if($dao->id)/{{$dao->id}}@endif"
              class="layui-form"
            @csrf
            @method($dao->id ? 'put' : 'post')
            {!! \PTAdmin\Build\Layui::text('title', '专题标题',   $dao->title)->required() !!}
            {!! \PTAdmin\Build\Layui::text('subtitle', '副标题',   $dao->subtitle) !!}
            {!! \PTAdmin\Build\Layui::images('banners', '轮播图',     $dao->banners  ) !!}
            {!! \PTAdmin\Build\Layui::text('seo_title', 'SEO标题',   $dao->seo_title  ) !!}
            {!! \PTAdmin\Build\Layui::text('seo_keyword', 'SEO关键词', $dao->seo_keyword) !!}
            {!! \PTAdmin\Build\Layui::text('seo_doc', 'SEO描述',    $dao->seo_doc   ) !!}
            {!! \PTAdmin\Build\Layui::text('url', '访问路径',    $dao->url   ) !!}
            {!! \PTAdmin\Build\Layui::number('num', '浏览量',    $dao->num   ) !!}
            <div class="layui-form-item">
                <label class="layui-form-label">状态</label>
                <div class="layui-input-block">
                    <input type="radio" name="status" value="1" title="显示" {{ $dao->status == 1 ? 'checked' : '' }}>
                    <input type="radio" name="status" value="0" title="隐藏" {{ $dao->status == 0 ? 'checked' : '' }}>
                </div>
            </div>
            {!! \PTAdmin\Build\Layui::number('weight', '权重',   $dao->weight      ) !!}
            {!! \PTAdmin\Build\Layui::select('topic_template', '模板文件',  $dao->topic_template )->setOptions(get_topic_template())->required() !!}
            {!! \PTAdmin\Build\Layui::textarea('remark', '专题备注',   $dao->remark  ) !!}
        </form>
    </div>
@endsection

@section("script")
    <script>
        layui.use(['PTForm','form' ,'PTMultipleSelect'], function () {
            const { PTForm ,form , PTMultipleSelect} = layui
            PTForm.init();
            PTMultipleSelect({
					ele: '.url-column',
					name: 'test',
					placeholder: '请选择您的数据',
					options: [
						{ label: '测试1', value: 1 },
						{ label: '测试2', value: 2 },
						{ label: '测试3', value: 3 },
						{ label: '测试4', value: 4 },
					],
					value: '',
			})
            form.on('radio(change-type)', function(data){
                const elem = data.elem;
                const value = elem.value;
                const urlElem =  $('.set-topic-nav-url > .layui-form-item')
                $(urlElem[value]).show().siblings().hide();
            });
        })
    </script>
@endsection
