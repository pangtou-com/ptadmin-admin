<style>
[v-cloak]{
    display: none;
}
.app-container{
    padding: 20px;
}
.box{
    display: flex;
    width: 100%;
}
.box .left{
    flex: 1;
    margin-right: 10px;
}
.box .right{
    width: 200px;
}

.service-box{
    display: flex;
    margin-bottom: 10px;
}
.service-box .item{
    display: flex;
    margin-right: 10px;
    align-items: center;
}
.service-box .item span{
    display: inline-block;
    width: 100px;
    color: var(--el-text-color-regular)
}

.avatar-uploader .el-upload {
    border: 1px dashed var(--el-border-color);
    border-radius: 6px;
    cursor: pointer;
    position: relative;
    overflow: hidden;
    transition: var(--el-transition-duration-fast);
}
.avatar-uploader img{
    max-width: 100px;
    max-height: 100px;
}

.avatar-uploader .el-upload:hover {
    border-color: var(--el-color-primary);
}

.el-icon.avatar-uploader-icon {
    font-size: 28px;
    color: #8c939d;
    width: 100px;
    height: 100px;
    text-align: center;
}

</style>
