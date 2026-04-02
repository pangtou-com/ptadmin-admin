@extends('ptadmin.layouts.base')

@section("content")
    <div class="content">
        <div class="layui-card ptadmin-page-container">
            <div class="layui-card-body">
                <table id="dataTable" lay-filter="dataTable"></table>
            </div>
        </div>
    </div>
@endsection

@section("script")
<script>
    layui.use(['PTPage','form','common','table'], function () {
        const { PTPage,form,common,table} = layui;
        const bgColors = [
            '',
            'layui-bg-orange',
            'layui-bg-green',
            'layui-bg-cyan',
            'layui-bg-blue',
            'layui-bg-black',
        ]
        let page = PTPage.make({
            urls: {
                index_url: "{{admin_route("cms/ads")}}/{{$ad_position_id}}",
                create_url: "{{admin_route("cms/ad")}}",
                edit_url: "{{admin_route("cms/ad")}}/{id}",
                del_url: "{{admin_route("cms/ad")}}/{id}",
                status_url: "{{admin_route("cms/ad-status")}}/{id}",
                title: {
                    create: '新增广告',
                    edit: '编辑广告'
                }
            },
            btn_left: ['create','refresh'],
            table: {
                cols: [[
                    {field: 'id', title: 'ID', width:80},
                    {field: 'title', title: '广告名称', minWidth:300},
                    {field: 'ad_position_text', title: '所属广告位', width: 150, align: 'center', templet: (data) => {
                            let val = common.getTableColValue(data);
                            return `<span class="layui-badge ${bgColors[data['ad_position_id'] % bgColors.length]}">${val}</span>`;
                        }},
                    {field: 'click', title: '点击量', width:80},

                    {field: 'status', title: '状态', width:100, align: 'center', templet: PTPage.format.switch},
                    {fixed: 'right', width: 120, title:'{{__("system.btn_handle")}}', align:'center', operate: ['edit', 'del']},
                ]]
            }
        });

        page.on('create', function () {
            common.formOpen("{{admin_route("cms/ad")}}?ad_position_id={{$ad_position_id}}",'新增广告');
        });

    });

</script>
@endsection
