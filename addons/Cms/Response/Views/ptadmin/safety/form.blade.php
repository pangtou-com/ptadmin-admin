@extends('ptadmin.layouts.base')

@section("content")
    <link rel="stylesheet" href="//unpkg.com/element-plus/dist/index.css"/>
    @include("cms::ptadmin.safety._style")
    @verbatim
        <div id="app" v-cloak>
            <div class="app-container">
                <el-form v-loading="loading" ref="formRef" label-position="top" :rules="formRule" :model="formData">
                    <div class="box">
                        <div class="left">
                            <el-row :gutter="10">
                                <el-col :span="12">
                                    <el-form-item label="标题" prop="title">
                                        <el-input v-model="formData.title" placeholder="请输入标题"></el-input>
                                    </el-form-item>
                                    <el-form-item label="副标题" prop="subtitle">
                                        <el-input v-model="formData.subtitle" placeholder="请输入副标题"></el-input>
                                    </el-form-item>
                                </el-col>
                                <el-col :span="12">
                                    <el-form-item label="服务类型" prop="type">
                                        <el-select v-model="formData.type" placeholder="请选择服务类型">
                                            <el-option v-for="item in safetyType" :value="item.value" :label="item.label"></el-option>
                                        </el-select>
                                    </el-form-item>
                                    <el-form-item label="图片标题" prop="cover_title">
                                        <el-input v-model="formData.cover_title" placeholder="请输入图片标题"></el-input>
                                    </el-form-item>
                                </el-col>
                            </el-row>
                            <el-form-item label="服务概述" prop="overview">
                                <el-input type="textarea" v-model="formData.overview"  placeholder="请输入服务概述"></el-input>
                            </el-form-item>
                            <el-form-item label="描述列表" prop="overview_desc">
                                <el-input type="textarea" v-model="formData.overview_desc"  placeholder="请输入服务概述列表内容，使用换行表示多条记录"></el-input>
                            </el-form-item>
                        </div>
                        <div class="right">
                            <el-form-item label="封面图" prop="cover">
                                <el-upload
                                    class="avatar-uploader"
                                    action="/system/upload"
                                    :show-file-list="false"
                                    :on-success="handleAvatarSuccess('cover')"
                                >

                                    <img v-if="formData.cover" :src="formData.cover" class="avatar" />
                                    <el-icon v-else class="avatar-uploader-icon"><Plus /></el-icon>
                                </el-upload>
                            </el-form-item>
                            <el-form-item label="banner图" prop="banner">
                                <el-upload
                                    class="avatar-uploader"
                                    action="/system/upload"
                                    :show-file-list="false"
                                    :on-success="handleAvatarSuccess('banner')"
                                >
                                    <img v-if="formData.banner" :src="formData.banner" class="avatar" />
                                    <el-icon v-else class="avatar-uploader-icon"><Plus /></el-icon>
                                </el-upload>
                            </el-form-item>
                        </div>
                    </div>
                    <el-divider content-position="center">服务流程</el-divider>
                    <div class="service-box">
                        <el-table :data="formData.process" border="">
                            <el-table-column prop="title" label="流程标题">
                                <template #default="source">
                                    <el-input v-model="source.row.title" placeholder="请输入服务流程标题"></el-input>
                                </template>
                            </el-table-column>
                            <el-table-column prop="title" label="流程内容">
                                <template #default="source">
                                    <el-input type="textarea" v-model="source.row.desc" placeholder="请输入服务流程内容"></el-input>
                                </template>
                            </el-table-column>
                            <el-table-column label="操作" width="80" >
                                <template #default="source">
                                    <el-button v-if="source.$index == 0" icon="plus" @click="handleAddList('process')"></el-button>
                                    <el-button v-else icon="delete" @click="handleDelList('process', source.index)"></el-button>
                                </template>
                            </el-table-column>
                        </el-table>
                    </div>
                    <el-divider content-position="center">服务优势</el-divider>
                    <div class="service-box">
                        <el-table :data="formData.advantage" border="">
                            <el-table-column prop="title" label="优势标题">
                                <template #default="source">
                                    <el-input v-model="source.row.title" placeholder="请输入服务优势标题"></el-input>
                                </template>
                            </el-table-column>
                            <el-table-column prop="title" label="优势内容">
                                <template #default="source">
                                    <el-input type="textarea" v-model="source.row.desc" placeholder="请输入服务优势标题"></el-input>
                                </template>
                            </el-table-column>
                            <el-table-column label="操作" width="80" >
                                <template #default="source">
                                    <el-button v-if="source.$index == 0" icon="plus" @click="handleAddList('advantage')"></el-button>
                                    <el-button v-else icon="delete" @click="handleDelList('advantage', source.index)"></el-button>
                                </template>
                            </el-table-column>
                        </el-table>
                    </div>

                    <div style="text-align: center; margin-top: 20px">
                        <el-button-group>
                            <el-button type="primary" @click="handleSubmit">保存</el-button>
                        </el-button-group>
                    </div>
                </el-form>
            </div>
        </div>
    @endverbatim
@endsection


@section("script")
    <script src="//unpkg.com/vue@3/dist/vue.global.js"></script>
    <script src="//unpkg.com/element-plus"></script>
    <script src="//unpkg.com/element-plus/dist/locale/zh-cn.js"></script>
    <script src="//unpkg.com/@element-plus/icons-vue"></script>
    <script>
        const current_id = {{$id ?? 0}};
        const safety_type = @json(config("cms.safety_type"));
    </script>
    @include("cms::ptadmin.safety._script_store")
@endsection
