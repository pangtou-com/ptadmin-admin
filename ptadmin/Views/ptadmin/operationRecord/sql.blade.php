<table class="layui-table">
    <tbody>
    <tr>
        <td class="table-bg">执行SQL总条数：</td>
        <td id="td_upgrade_msg">{{count($data)}}</td>
        <td class="table-bg">总耗时(ms)：</td>
        <td>{!! array_sum(array_column($data, 'Time')) !!}</td>
    </tr>
    <tr>
        <td colspan="4">
            @foreach($data as $key => $val)
                <div style="padding: 10px">
                    <p>File: {{$val['File']}} 【Line: {{$val['Line']}}】</p>
                    <p>Sql: {{$val['Sql']}} 【Time: {{$val['Time']}}】</p>
                </div>
            @endforeach
        </td>
    </tr>

    </tbody>
</table>



