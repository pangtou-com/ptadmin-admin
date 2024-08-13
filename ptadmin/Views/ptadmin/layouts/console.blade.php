@extends('ptadmin.layouts.base')
@section('content')
    <div class="dashboard">
        <div class="layui-row layui-col-space15">
            <div class="layui-col-md8">
                <div class="layui-card">
                    <div class="layui-card-body">
                        <div class="bless">
                            <p class="text">亲爱的 <span
                                        class="layui-badge layui-bg-orange">{{\PTAdmin\Admin\Utils\SystemAuth::user()->nickname}}</span>
                                欢迎您！</p>
                            <p class="text"> 每一天，您的努力都在推动我们共同的事业向前发展。
                                在【PTAdmin】
                                您可以轻松访问各种工具和资源，帮助您高效完成工作。
                                如果在使用过程中遇到任何问题，或者只是想要分享一下您的想法，我们的团队随时欢迎您的反馈。
                                您的支持是我们的源动力。感谢您的贡献和努力！</p>
                            <div class="btn">
                                <a href="https://www.pangtou.com" class="layui-badge layui-bg-blue"
                                   target="_blank">我要反馈</a>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="layui-card">
                    <div class="layui-card-header">统计面板</div>
                    <div class="layui-card-body">
                        <div class="layui-row layui-col-space15 dashboard-card-four  dashboard-card-icon-bg"></div>
                    </div>
                </div>

            </div>
            <div class="layui-col-md4">
                <div class="layui-card">
                    <div class="layui-card-header">【PTAdmin】 系统信息</div>
                    <div class="layui-card-body">
                        <table class="layui-table">
                            <tbody>
                            <tr>
                                <td class="table-title">站点名称</td>
                                <td>
                                    <a href="/" target="_blank">我的网站</a>
                                </td>
                                <td class="table-title">系统名称</td>
                                <td>
                                    <a href="https://www.pangtou.com" target="_blank">【PTAdmin】平台</a>
                                </td>
                            </tr>
                            <tr>
                                <td class="table-title">当前版本</td>
                                <td>
                                    <span class="layui-badge layui-bg-gray">V1.0</span>
                                </td>
                                <td class="table-title">最新版本</td>
                                <td>
                                    检测更新
                                </td>
                            </tr>
                            <tr>
                                <td class="table-title">授权信息</td>
                                <td>
                                    检测授权
                                </td>
                                <td class="table-title">帮助文档</td>
                                <td>
                                    <a class="layui-badge" href="https://docs.pangtou.com" target="_blank">查看</a>
                                </td>
                            </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="layui-card">
                    <div class="layui-card-header">快捷导航</div>
                    <div class="layui-card-body">
                        <div class="quick-nav">
                            @if($quick_nav)
                                @foreach($quick_nav as $nav)
                                    <div class="quick-nav-box" ptadmin-href="{{admin_route($nav['route'])}}">
                                        <div>
                                            <i class="{{$nav['icon'] == '' ? "layui-icon layui-icon-picture-fine" : $nav['icon']}}"></i>
                                        </div>
                                        <p class="text">{{$nav['title']}}</p>
                                    </div>
                                @endforeach
                            @endif

                            <div class="quick-nav-box" ptadmin-event="quick_add">
                                <div><i class="layui-icon layui-icon-add-1"></i></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('script')
    <script>
        layui.use(['common', 'PTForm', 'PTIcon'], function () {
            const {
                common,
                PTForm,
                PTIcon
            } = layui;

            PTForm.init()

            /** 新增快捷导航 */
            const quickAdd = function () {
                common.formOpen("{{admin_route('quick-nav')}}", '设置快捷导航');
            }

            const render_demo_card = (data) => {
                let colStr = data.col ? '' : 'layui-col-md3 layui-col-sm6'
                if (data.col) {
                    for (const key in data.col) {
                        colStr += `layui-col-${key}${data.col[key]} `
                    }
                }
                const bg_color = data.bg_color ? data.bg_color : ['#ff99c3', '#8167f5', '#ff5722', '#16b777']
                let waitAppend = ''
                $.each(data.data, (idx, item) => {
                    waitAppend += `<div class="${colStr}">` +
                        `<div class="dashboard-card" style="background:${bg_color[idx]}">` +
                        `<div class="icon-box" style="background:${bg_color[idx]}">` +
                        `<span class=" ${item.icon}"></span>` +
                        '</div>' +
                        `<div class="content">
            <p class="title">${item.title}</p>
            <p class="number">${item.number}</p>
            <p class="compare">${item.compare.label} <span class="${item.compare.is_compare ? 'icon-up-arrow' : 'icon-down-arrow'}"></span></p>
            </div>
          </div>
         </div>`
                })
                $(data.ele).html(waitAppend)
            }

            render_demo_card({
                ele: '.dashboard-card-four',
                bg_color: ['#ff99c3', '#8167f5', '#ff5722', '#16b777'],
                col: {
                    md: 3,
                    sm: 6,
                },
                data: [{
                    icon: 'layui-icon layui-icon-user',
                    title: '用户总数',
                    number: '100',
                    compare: {
                        label: '昨日',
                        is_compare: true
                    }
                },
                    {
                        icon: 'layui-icon layui-icon-log',
                        title: '今日加入用户',
                        number: '100',
                        compare: {
                            label: '昨日',
                            is_compare: true
                        }
                    },
                    {
                        icon: 'layui-icon layui-icon-app',
                        title: '安装插件',
                        number: '100',
                        compare: {
                            label: '昨日',
                            is_compare: true
                        }
                    },
                    {
                        icon: 'layui-icon layui-icon-light',
                        title: '附件大小',
                        number: '100',
                        compare: {
                            label: '昨日',
                            is_compare: true
                        }
                    }
                ]
            })
            $('[ptadmin-event="quick_add"]').on('click', quickAdd);
        })
    </script>
@endsection
