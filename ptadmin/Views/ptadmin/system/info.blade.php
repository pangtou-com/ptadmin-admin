{{--用户详情--}}
@extends('ptadmin.layouts.base')

@section("content")
    <div class="layui-fluid layui-bg-gray all_div">
        <div class="layui-row">
            <div class="layui-col-xs1 layui-bg-gray">
                <ul class="layui-menu" id="demo-menu">
                    <li class="layui-menu-item-checked getDiv" data-id="1">
                        <div class="layui-menu-body-title">基本资料</div>
                    </li>
                    @foreach($permissions as $permission)
                        <li>
                            <div class="layui-menu-body-title getDiv" data-id="2" data-url="{{ $permission['url'] }}"
                                 data-rid="{{ $permission['id'] }}">{{ $permission['title'] }}</div>
                        </li>
                    @endforeach

                </ul>
            </div>
            <div class="layui-col-xs10">
                <div class="layui-bg-gray div_message hidden_div">
                    @include('ptadmin.system.info_message')
                </div>
                <div class="layui-bg-gray div_login hidden_div" hidden="hidden">
                    <iframe frameborder="0" style="width: 100%;height: 900px" class="iframe_url" data-rid=""></iframe>
                </div>
            </div>
        </div>
    </div>
@endsection

@section("script")
    <script>
        layui.use(['PTTable', 'upload', 'layer', 'form', 'jquery'], function () {
            let {PTTable} = layui;
            var upload = layui.upload;
            var layer = layui.layer;
            PTTable.render({
                extend: {
                    index_url: '{{admin_route('systems')}}',
                },
                cols: [[
                    {field: 'id', title: 'ID', width: 80},
                    {field: 'username', title: '{{ L("systems", "username") }}'},
                    {field: 'nickname', title: '{!! L("systems", "nickname") !!}'},
                    {field: 'mobile', title: '{!! L("systems", "mobile") !!}'},
                    {field: 'status', title: '{!! L("systems", "status") !!}', templet: PTTable.format.switch},
                    {field: 'login_at', title: '{!! L("systems", "login_at") !!}'},
                    {field: 'login_ip', title: '{!! L("systems", "login_ip") !!}'},
                    {
                        fixed: 'right',
                        width: 120,
                        title: '{{ __("system.btn_handle") }}',
                        align: 'center',
                        operate: ['edit', 'del', 'link']
                    },
                ]]
            });

            var uploadInst = upload.render({
                elem: '#upload-avatar-btn',
                url: '/system/upload', // 实际使用时改成您自己的上传接口即可。
                done: function (res) {
                    // 若上传失败
                    if (res.code > 0) {
                        return layer.msg('上传失败');
                    }
                    $('#upload-avatar-img').attr('src', res.data.url)
                    $('.avatar_path').val(res.data.path)
                },
                // 进度条
                progress: function (n, elem, e) {
                    element.progress('filter-demo', n + '%'); // 可配合 layui 进度条元素使用
                    if (n == 100) {
                        layer.msg('上传完毕', {icon: 1});
                    }
                }
            });

            $('.getDiv').on('click', function () {

                if ($(this).data('id') == 1) {
                    $('.hidden_div').hide()
                    $('.div_message').show();
                    $('.iframe_url').data('rid', '');
                } else if ($(this).data('id') == 2 && $('.iframe_url').data('rid') != $(this).data('rid')) {
                    $('.hidden_div').hide()
                    let url = $(this).data('url');
                    $('.iframe_url').attr('src', url);
                    $('.iframe_url').data('rid', $(this).data('rid'));
                    $('.div_login').show()
                }
            })

        })
    </script>
    <style>
        .hidden_div {
            border-left: 1px solid #E9E9E9;
            margin-left: 1px;
            padding: 20px;

        }

        .div_login iframe {
            height: 100%;
            width: 100%;
        }

        .red {
            color: red;
        }

        .all_div {
            width: 100%;
            height: 950px
        }
    </style>
@endsection
