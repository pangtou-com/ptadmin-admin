<!DOCTYPE html>
<html lang="zh-cn">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>PTAdmin 管理系统</title>
    <link rel="icon" href="{{asset("ptadmin/images/favicon.png")}}">
    <link rel="stylesheet" href="{{asset("ptadmin/bin/css/layui.css")}}">
    <style>
        :root {
            --button-hover-text-color: #ffffff;
            --button-hover-border-color: #eebe77;
            --button-hover-bg-color: #eebe77;
        }
        html, body {
            padding: 0;
            margin: 0;
            font-family: "微软雅黑 Bold", "微软雅黑 Regular", "微软雅黑", sans-serif;
            font-size: 62.5%;
            width: 100%;
            height: 100%;
        }
        a {
            text-decoration: none;
        }
        .install {
            width: 100%;
            height: 100vh;
            background: #5c93ec url("{{asset('/ptadmin/images/install_bg.svg')}}") no-repeat;
            background-size: cover;
            display: flex;
            justify-content: center;
            align-items: center;
        }
        .install .card {
            padding: 2rem;
            background-color: white;
            box-sizing: border-box;
            width: 50%;
            height: auto;
            border-radius: 5px;
        }
        .install .title {
            color: #5c93ec;
            font-size: 3rem;
            font-weight: bold;
            display: flex;
            justify-content: center;
            align-items: center;
        }
        .install .title img {
            margin-right: 10px;
            width: 30px;
        }
        .install .step {
            display: flex;
            padding: 20px 0;
            justify-content: space-evenly;
            align-items: center;
        }
        .install .step .step-title {
            font-size: 2rem;
            width: 25%;
            text-align: center;
        }
        .install .step .step-title i {
            margin-right: 5px;
        }
        .install .step .step-title.is-wait {
            color: #a8abb2;
        }
        .install .step .step-title.is-process {
            color: #303133;
        }
        .install .step .step-title.is-finish {
            color: #409eff;
        }
        .install .content {
            border: 1px solid #e4e7ed;
            border-radius: 5px;
            height: 500px;
            overflow-y: auto;
            padding: 20px;
            width: calc(100% - 40px);
        }
        .install .footer {
            padding: 20px 0;
        }

        .pt-button {
            display: inline-flex;
            justify-content: center;
            align-items: center;
            line-height: 1;
            height: 32px;
            width: auto;
            white-space: nowrap;
            cursor: pointer;
            color: white;
            text-align: center;
            box-sizing: border-box;
            outline: 0;
            transition: 0.1s;
            font-weight: 500;
            vertical-align: middle;
            background-color: var(--button-bg-color);
            border: 1px solid var(--button-bg-color);
            padding: 8px 15px;
            font-size: 14px;
            border-radius: 4px;
        }
        .pt-button:hover, .pt-button:focus {
            color: var(--button-hover-text-color);
            border-color: var(--button-hover-border-color);
            background-color: var(--button-hover-bg-color);
            outline: none;
        }

        .group {
            display: inline-block;
            vertical-align: middle;
        }
        .group .button {
            float: left;
            position: relative;
        }
        .group .button:first-child {
            border-top-right-radius: 0;
            border-bottom-right-radius: 0;
        }
        .group .button:not(:last-child) {
            margin-right: -1px;
        }

        .button-info {
            --button-bg-color: #909399;
        }
        .button-info:first-child {
            border-right-color: rgba(255, 255, 255, 0.5);
        }

        .checkbox__original {
            opacity: 0;
            outline: 0;
            position: absolute;
            margin: 0;
            width: 0;
            height: 0;
            z-index: -1;
        }

        .requirement {
            display: flex;
            height: 40px;
            width: 100%;
        }
        .requirement li {
            list-style: none;
            line-height: 40px;
            text-align: center;
            width: 100%;
        }
        .requirement .li-title {
            background-color: #5c93ec;
            color: white;
            font-size: 16px;
        }
        .requirement.lists {
            border-bottom: 1px solid #e4e7ed;
        }
        .red {
            color: red;
        }
        .error{
            background: red;
            color: #fff;
        }
        .console-box{
            width: 100%;
            height: 100%;
            background: rgba(31, 31, 32, 0.9);
            color: white;
        }
        .console-box .title {
            padding: 20px 20px 5px;
        }
        .console-box .item {
            padding: 0 20px 20px;
            height: calc(100% - 80px);
            overflow-y: auto;
        }
        .console-box .item .time{
            color: #b7d5a0;
            font-size: 20px;
        }
        .console-box .item li{
            line-height: 2!important;
            height: auto!important;
        }
        .console-box .item li span{
            display: inline-block;
            width: 60px;
            margin-right: 10px;
        }
        .loading-dots {
            display: inline-block;
            font-size: 16px;
            letter-spacing: 3px;
        }
        .loading-dots span {
            animation: loading 1.5s infinite;
            opacity: 0;
        }
        .loading-dots span:nth-child(1) {
            animation-delay: 0s;
        }
        .loading-dots span:nth-child(2) {
            animation-delay: 0.3s;
        }
        .loading-dots span:nth-child(3) {
            animation-delay: 0.6s;
        }
        @keyframes loading {
            0% {
                opacity: 0;
            }
            50% {
                opacity: 1;
            }
            100% {
                opacity: 0;
            }
        }
    </style>
</head>
<body>
    <div class="install">
        <div class="card">
            <div class="title">
                <img src="{{asset("ptadmin/images/logo.png")}}" alt="PTAdmin" />
                欢迎使用PTAdmin
            </div>
            <div class="step">
                @foreach($tabs as $key => $val)
                    <div class="step-title @if($key === $step) is-process @elseif($key < $step) is-finish @else is-wait @endif ">
                        <i class="layui-icon {{$val['icon']}}"></i><span>{{$val['title']}}</span>
                    </div>
                @endforeach
            </div>
            <div class="content">
                @yield("content")
            </div>
            <div class="footer">
                @yield("button")
            </div>
        </div>
    </div>
</body>
</html>
<script src="{{asset("ptadmin/bin/layui.js")}}"></script>
@yield('script')

