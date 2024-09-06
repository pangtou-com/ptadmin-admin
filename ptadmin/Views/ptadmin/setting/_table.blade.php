
<div class="container-item" id="tableData">
    @foreach($data as $key => $item)
        <div class="layui-card {!! $key ? "":"active" !!} ">
            <div class="layui-card-header">
                <div class="">{{$item['title']}}
                    @if($item['intro'])
                        <i class="layui-icon layui-icon-question" ptadmin-tips="{{$item['intro']}}"></i>
                    @endif
                </div>
                <div class="btn layui-btn-group" data-id="{{$item['id']}}">
                    <button class="layui-btn layui-btn-xs layui-bg-blue" ptadmin-event="field-create"><i class="layui-icon layui-icon-addition"></i></button>
                    <button class="layui-btn layui-btn-xs" ptadmin-event="edit"><i class="layui-icon layui-icon-edit"></i></button>
                    <button class="layui-btn layui-btn-xs layui-btn-danger" ptadmin-event="delete"><i class="layui-icon layui-icon-delete"></i></button>
                </div>
            </div>
            <div class="layui-card-body">
                    <table class="layui-table">
                        <colgroup>
                            <col width="200">
                            <col width="200">
                            <col>
                            <col width="90">
                            <col width="120">
                            <col width="90">
                        </colgroup>
                        <thead>
                        <tr>
                            <th>标题</th>
                            <th>标识</th>
                            <th>备注</th>
                            <th>排序</th>
                            <th>类型</th>
                            <th>操作</th>
                        </tr>
                        </thead>
                        <tbody>
                        @if(isset($item['children']) && $item['children'])
                            @foreach($item['children'] as $child)
                                <tr>
                                    <td>{{$child['title']}}</td>
                                    <td>{{$child['name']}}</td>
                                    <td>{{$child['intro']}}</td>
                                    <td>{{$child['weight']}}</td>
                                    <td>{{$child['type']}}</td>
                                    <td>
                                        <div class="layui-btn-group" data-id="{{$child['id']}}">
                                            <button class="layui-btn layui-btn-xs" ptadmin-event="field-edit"><i class="layui-icon layui-icon-edit"></i></button>
                                            <button class="layui-btn layui-btn-xs layui-btn-danger" ptadmin-event="field-del"><i class="layui-icon layui-icon-delete"></i></button>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        @else
                            <tr>
                                <td colspan="6" style="text-align: center;">数据为空</td>
                            </tr>
                        @endif
                        </tbody>
                    </table>

            </div>
        </div>
    @endforeach
</div>

<style>
    .layui-card-header{
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
</style>
