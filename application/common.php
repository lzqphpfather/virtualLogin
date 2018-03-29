<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006-2016 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: 流年 <liu21st@gmail.com>
// +----------------------------------------------------------------------

// 应用公共文件
////虚拟登陆上海社保cookie保存路径
define("VIRTUAL_LOGIN_SH_INSURANCE_COOKIE_PATH",WEB_PATH."/static/uploads/cookie/virtual_sh_insurance");

//虚拟登陆上海公积金cookie保存路径
define("VIRTUAL_LOGIN_SH_FUND_COOKIE_PATH",WEB_PATH."/static/uploads/cookie/virtual_sh_fund");

//虚拟登陆北京社保cookie保存路径
define("VIRTUAL_LOGIN_BJ_INSURANCE_COOKIE_PATH",WEB_PATH."/static/uploads/cookie/virtual_bj_insurance");

//虚拟登陆上海公积金cookie保存路径
define("VIRTUAL_LOGIN_BJ_FUND_COOKIE_PATH",WEB_PATH."/static/uploads/cookie/virtual_bj_fund");

//虚拟登陆深圳社保cookie保存路径
define("VIRTUAL_LOGIN_SZ_INSURANCE_COOKIE_PATH",WEB_PATH."/static/uploads/cookie/virtual_sz_insurance");

//虚拟登陆深圳公积金cookie保存路径
define("VIRTUAL_LOGIN_SZ_FUND_COOKIE_PATH",WEB_PATH."/static/uploads/cookie/virtual_sz_fund");

//虚拟登陆广州社保cookie保存路径
define("VIRTUAL_LOGIN_GZ_INSURANCE_COOKIE_PATH",WEB_PATH."/static/uploads/cookie/virtual_gz_insurance");

//虚拟登陆广州公积金cookie保存路径
define("VIRTUAL_LOGIN_GZ_FUND_COOKIE_PATH",WEB_PATH."/static/uploads/cookie/virtual_gz_fund");

//虚拟登陆成都社保cookie保存路径
define("VIRTUAL_LOGIN_CD_INSURANCE_COOKIE_PATH",WEB_PATH."/static/uploads/cookie/virtual_cd_insurance");

//虚拟登陆成都公积金cookie保存路径
define("VIRTUAL_LOGIN_CD_FUND_COOKIE_PATH",WEB_PATH."/static/uploads/cookie/virtual_cd_fund");


/**
 * 时间字符串格式化
 * @param $str 时间字符串
 * @return false|int
 * @author langziqiang
 */
function getTime($str){
    $year = substr($str,0,4);
    $month= substr($str,4,2);

    return strtotime($year.'-'.$month);
}

/**
 * 处理金额样式
 * @param $str
 * @return bool|string
 * @author langziqiang
 */
function getCdSocialMoney($str){
    $str = str_replace(',','',$str);

    $money = substr($str,3);
    return $money;
}