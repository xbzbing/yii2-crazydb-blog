<?php
use yii\web\View;
/* @var View $this */
/* @var array $server */

$this->title = "控制台";
?>
<div class="box">
    <div class="box-header">
        <i class="fa fa-info"></i>
        <h3 class="box-title">系统信息</h3>
    </div>
    <div class="box-body table-responsive no-padding">
    <table class="table table-hover">
        <tbody>
            <tr>
                <td >操作系统软件</td>
                <td ><?=$server['serverOs']?> - <?=$server['serverSoft']?></td>
            </tr>
            <tr>
                <td >数据库及大小</td>
                <td ><?=$server['mysqlVersion']?> (<?=$server['dbsize']?>)</td>
            </tr>
            <tr>
                <td >上传许可</td>
                <td ><?=$server['fileupload']?></td>
            </tr>
            <tr>
                <td >主机名</td>
                <td ><?=$server['serverUri']?></td>
            </tr>
            <tr>
                <td >当前使用内存</td>
                <td ><?=$server['excuteUseMemory']?></td>
            </tr>
            <tr>
                <td >PHP环境</td>
                <td >
                    <p>版本：<?=$server['phpVersion']?></p>
                </td>
            </tr>
        </tbody>
    </table>
    </div>
</div>