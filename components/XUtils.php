<?php
/**
 * @author xbzbing <xbzbing@gmail.com>
 * @link http://www.crazydb.com
 *
 * 工具类 一些杂七杂八的东西
 * Class XUtils
 * 主要包含一些数据库数据的获取（缓存）@todo 系统自定义缓存
 */
namespace app\components;

use yii;
use yii\helpers\HtmlPurifier;


class XUtils{

    /**
     * 格式化时间，三天内显示具体发布时间，超过三天，仅显示发布日期
     * @param $timestamp integer
     * @param $limit integer default=3
     * @return string 格式化时间的选项
     */
    public static function XDateFormatter( $timestamp, $limit = 3 ){
        $time = $limit*60*60*24 + $timestamp;
        if( $time > time() ){
            return date( 'm-d H:i', $timestamp );
        }else{
            return date( 'Y-m-d', $timestamp );
        }
    }

    /**
     * 格式化容量显示
     * @param $size
     * @param int $dec
     * @return string
     */
    public static function dataFormat( $size, $dec = 2 ) {
        $a = array ( "B" , "KB" , "MB" , "GB" , "TB" , "PB" );
        $pos = 0;
        while ( $size >= 1024 ) {
            $size /= 1024;
            $pos ++;
        }
        return round( $size, $dec ) . " " . $a[$pos];
    }

    /**
     * 截取字符串，并自动闭合html标签
     * @param $string
     * @param $start
     * @param $width
     * @param string $trimmarker
     * @param string $encode
     * @return string
     */
    public static function strimwidthWithTag( $string, $start, $width, $trimmarker = '...', $encode = 'utf-8' ){
        if(function_exists('mb_strimwidth')){
            $string = mb_strimwidth($string, $start, $width, $trimmarker, $encode);
        }else{
            $string = self::dm_strimwidth($string, $start, $width, $trimmarker, $encode);
        }
        $string = HTMLPurifier::process($string);
        return $string;
    }
    /**
     * 获取按指定宽度截断的字符串
     * @param string $str 要截短的字符串
     * @param int $start 开始位置的偏移
     * @param int $width 所需修剪的宽度
     * @param string $trimmarker 当字符串被截短的时候，将此字符串添加到截短后的末尾
     * @param string $encoding 字符编码,如果省略，则使用内部字符编码。Not Used.
     * @return string
     */
    public function dm_strimwidth($str ,$start , $width ,$trimmarker='...',$encoding='utf-8' ){
        $output = preg_replace('/^(?:[x00-x7F]|[xC0-xFF][x80-xBF]+){0,'.$start.'}((?:[x00-x7F]|[xC0-xFF][x80-xBF]+){0,'.$width.'}).*/s','1',$str);
        return $output.$trimmarker;
    }
    /**
     * 获取客户端IP地址
     */
    static public function getClientIP() {
        static $ip = null;
        if( $ip !== null )
            return $ip;
        $ips = [];
        if(isset( $_SERVER['HTTP_X_FORWARDED_FOR'])){
            $ips = explode( ',', $_SERVER['HTTP_X_FORWARDED_FOR']);
        }
        if(isset( $_SERVER['HTTP_CLIENT_IP'])){
            $ips[] = $_SERVER['HTTP_CLIENT_IP'];
        }
        if(isset( $_SERVER['REMOTE_ADDR'])){
            $ips[] = $_SERVER['REMOTE_ADDR'];
        }
        //将第一个有效IP地址作为用户IP地址
        //@todo 异常IP记录
        foreach($ips as $value){
            $value = filter_var(trim($value),FILTER_VALIDATE_IP);
            if($value){
                $ip = $value;
                break;
            }
        }
        $ip = $ip ? $ip : '0.0.0.0';
        return $ip;
    }
}