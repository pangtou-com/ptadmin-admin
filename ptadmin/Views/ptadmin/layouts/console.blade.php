@extends('ptadmin.layouts.base')
@section('content')
    <div class="dashboard">
        <div class="layui-row layui-col-space15">
            <div class="layui-col-md8">
                <div class="layui-card">
                    <div class="layui-card-body">
                        <div class="bless">
                            <p class="text">亲爱的
                                <span class="layui-badge layui-bg-orange">
                                    {{\PTAdmin\Admin\Utils\SystemAuth::user()->nickname}}</span>
                                    欢迎您！
                            </p>
                            <p class="text">
                                每一天，您的努力都在推动我们共同的事业向前发展。
                                在【PTAdmin】
                                您可以轻松访问各种工具和资源，帮助您高效完成工作。
                                如果在使用过程中遇到任何问题，或者只是想要分享一下您的想法，我们的团队随时欢迎您的反馈。
                                您的支持是我们的源动力。感谢您的贡献和努力！</p>
                            <div class="btn">
                                <a href="https://www.pangtou.com" class="layui-badge layui-bg-blue" target="_blank">我要反馈</a>
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
                <form class="layui-form" action="">
                        <div class="demo-upload"></div>
                        <div class="demo-upload2"></div>
                        <div class="demo-upload6"></div>
                        <div class="demo-upload3"></div>
                        <div class="demo-upload4"></div>
                        <div class="demo-upload5"></div>
                        <div class="layui-form-item">
                            <div class="layui-input-block">
                                <button type="submit" class="layui-btn" lay-submit lay-filter="demo1">立即提交</button>
                            </div>
                        </div>
                </form>
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
@endsection

@section('script')
    <script>

        layui.use(['common', 'PTForm', 'PTIcon','PTAttachment','form'], function () {
            const {
                common,
                PTForm,
                PTIcon,
                PTAttachment,
                form
            } = layui;
            form.on('submit(demo1)', function(data){
				var field = data.field; // 获取表单字段值
				console.log(field);
				return false;
			});
            const options = {
			elem:`.demo-upload`,
			theme:'avatar',
			direct:true,
			selector:true,
			edit:true,
			area:200,
			field:'first',
			required:true,
			attribute:{
				url:'{{admin_route('upload')}}',
				multiple:false, // 单图
				size:60,
				accept: 'images',
			},
			done:function(data){
				console.log('返回的参数',data);
			},
			// 单图回显
			// data:{
			// 		id:0,
			// 		title:'测试测试123645',
			// 		thumb:'https://pic3.zhimg.com/v2-5fb13110e1de13d4c11e6e7f5b8026da_r.jpg',
			// 		url:'https://pic3.zhimg.com/v2-5fb13110e1de13d4c11e6e7f5b8026da_r.jpg',
			// 		is_annex:false,
			// 		suffix:'png'
			// 	},
		}
		const options2 = {
			elem:`.demo-upload2`,
			theme:'avatar',
			direct:true,
			selector:false,
			saveRemote:false,
			edit:false,
			attribute:{
				url:'{{admin_route('upload')}}',
				multiple:true,
				number:5,
				accept: 'video'
			},
			field:'second',
			done:function(data){
				console.log('返回的参数',data);
			},
			data:[
				{
					id:0,
					title:'测试视频 点击查看',
					thumb:'https://pic3.zhimg.com/v2-5fb13110e1de13d4c11e6e7f5b8026da_r.jpg',
					url:'https://prod-streaming-video-msn-com.akamaized.net/a8c412fa-f696-4ff2-9c76-e8ed9cdffe0f/604a87fc-e7bc-463e-8d56-cde7e661d690.mp4',
					is_annex:false,
					suffix:'mp4'
				},
				{
					id:0,
					title:'测试视频 点击查看',
					thumb:'https://pic1.zhimg.com/v2-fda5ab4414155c0c171ac5f87bc82ded_r.jpg?source=1940ef5c',
					url:'https://prod-streaming-video-msn-com.akamaized.net/35960fe4-724f-44fc-ad77-0b91c55195e4/bfd49cd7-a0c6-467e-ae34-8674779e689b.mp4',
					is_annex:false,
					suffix:'mp4'
				},
			]
		}
        const options6 = {
			elem:`.demo-upload6`,
			direct:true,
			selector:true,
			edit:true,
			saveRemote:false,
			remote:true,
			field:'ahsfajshfkajs',
			attribute:{
				multiple:false,
				url:'{{admin_route('upload')}}',
				accept: 'file',// 所有文件格式
			},
			data:[
				{
					id:3,
					title:'测试测试3',
					url:'https://pic2.zhimg.com/v2-0dda71bc9ced142bf7bb2d6adbebe4f0_r.jpg',
					is_annex:false,
					suffix:'jpg'
				},
			],
            confirm:function(data){
				console.log('确认选择资源',data);
			},
            remoteInput:function(data){
				console.log('远程获取输入成功',data);
			}
		}
		const options3 = {
			elem:`.demo-upload3`,
			direct:true,
			selector:true,
			edit:true,
			saveRemote:false,
			remote:true,
			field:'third',
			attribute:{
				multiple:true,
				url:'{{admin_route('upload')}}',
				number:9,
				accept: 'file',// 所有文件格式
			},
			data:[
				{
					id:3,
					title:'测试测试3',
					url:'https://pic2.zhimg.com/v2-0dda71bc9ced142bf7bb2d6adbebe4f0_r.jpg',
					is_annex:false,
					suffix:'jpg'
				},
				{
					id:4,
					title:'测试测试4',
					url:'https://img.zcool.cn/community/017f51563447666ac7259e0f1522ea.jpg@1280w_1l_2o_100sh.jpg',
					is_annex:true,
					suffix:'jpg'
				},
				{
					id:5,
					title:'测试测试5',
					url:'https://img.tukuppt.com/ad_preview/00/15/09/5e715a320b68e.jpg!/fw/980',
					is_annex:true,
					suffix:'jpg'
				}
			],
            confirm:function(data){
				console.log('确认选择资源',data);
			},
		}
		const options4 = {
			elem:`.demo-upload4`,
			theme:'avatar',
			direct:false,
			selector:true,
			edit:true,
			area:100,
			field:'four',
			required:true,
			attribute:{
				multiple:true,
				size:60,
				number:5,
				accept: 'file', //  支持的文件格式
                allowFiles:'image',
			},
			done:function(data){
				console.log('返回的参数',data);
			},
			confirm:function(data){
				console.log('确认选择资源',data);
			},
			data:[
                    {
                        id:99999,
                        title:'测试测试123645',
                        thumb:'https://pic3.zhimg.com/v2-5fb13110e1de13d4c11e6e7f5b8026da_r.jpg',
                        url:'https://pic3.zhimg.com/v2-5fb13110e1de13d4c11e6e7f5b8026da_r.jpg',
                        is_annex:false,
                        suffix:'png'
                    }
                ],
		}
        const options5 = {
			elem:`.demo-upload5`,
			theme:'avatar',
			direct:true,
			selector:true,
			edit:true,
			area:120,
			field:'five',
			required:true,
			attribute:{
				url:'{{admin_route('upload')}}',
				multiple:false,
				accept: 'file',
			},
			done:function(data){
				console.log('返回的参数',data);
			},
			// 单图回显
			data:{
					id:0,
					title:'测试测试123645',
					thumb:'https://pic3.zhimg.com/v2-5fb13110e1de13d4c11e6e7f5b8026da_r.jpg',
					url:'https://pic3.zhimg.com/v2-5fb13110e1de13d4c11e6e7f5b8026da_r.jpg',
					is_annex:false,
					suffix:'png'
				},
		}
		PTAttachment.make(options)
		PTAttachment.make(options2)
		PTAttachment.make(options3)
		PTAttachment.make(options4)
		PTAttachment.make(options5)
		PTAttachment.make(options6)
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
