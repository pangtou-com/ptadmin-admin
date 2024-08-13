@extends('ptadmin.layouts.base')

@section("content")
    <div class="icon">
        <div class="icon-left layui-hide-xs">
            <p class="icon-this" data-icon="layui">Layui Icon</p>
        </div>
        <div class="icon-content">
            <div class="layui-show-xs-block layui-hide-sm icon-header">
                <div class="layui-tab layui-tab-brief" lay-filter="icon">
                    <ul class="layui-tab-title">
                        <li class="layui-this" data-icon="layui">Layui Icon</li>
                    </ul>
                </div>
            </div>
            <div class="icon-lists icon-show" data-icon="layui">
                @include("ptadmin.layouts.icon_layui")
            </div>
        </div>
    </div>
@endsection

@section('script')
<script>
    layui.use(function () {
        const element = layui.element

        element.on('tab(icon)', function(data){
            const type = $(this).attr('data-icon')
            changeLists(type)
        })

        $('.icon-left').on('click', 'p', function () {
            $(this).addClass('icon-this').siblings().removeClass('icon-this')
            const type = $(this).attr('data-icon')
            changeLists(type)
        })

        $(".ptadmin-docs-icon").on('click', '>div',function () {
            $(".ptadmin-docs-icon").find('div').removeClass('pt-this')
            $(this).addClass('pt-this')
        })

        const changeLists = (type) => {
            $(".icon-lists").each(function () {
                if ($(this).attr('data-icon') === type) {
                    $(this).addClass('icon-show')
                } else {
                    $(this).removeClass('icon-show')
                }
            })
        }
    })
</script>
@endsection
