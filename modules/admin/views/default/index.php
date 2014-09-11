<?php
use app\modules\admin\components\Controller;
/* @var Controller $this */
/* @var array $server */
?>
<table class="table table-bordered table-hover">
    <caption><h2>系统信息</h2></caption>
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
                <p>magic_quote_gpc：<?=$server['magic_quote_gpc']?></p>
                <p>allow_url_fopen：<?=$server['allow_url_fopen']?></p>
            </td>
        </tr>
    </tbody>
</table>