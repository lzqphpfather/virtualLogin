<?php

namespace app\index\controller;

use lib\Http;
use think\Request;

class Index
{

    public function commit()
    {
        $request = Request::instance();
        $data = $request->param();

        if (isset($data['type']) && $data['type'] == 'mayi') {
            $sell = "cd /alidata/www/xunidenglu;git checkout master;git pull;git diff --name-status HEAD~1 > /tmp/mayi_diff.log;";
            shell_exec($sell);
            echo 'ok';
        } else {
            echo "权限不足";
        }

    }

    /**
     * 上海社保验证码
     * @author langziqiang
     */
    public function ShSocialYzm()
    {
        $request = Request::instance();
        $data = $request->param();

        Http::curl_get('http://www.12333sh.gov.cn/sbsjb/wzb/226.jsp', VIRTUAL_LOGIN_SH_INSURANCE_COOKIE_PATH . $data['uid'] . '.txt', VIRTUAL_LOGIN_SH_INSURANCE_COOKIE_PATH . $data['uid'] . '.txt');
        Http::curl_get('http://www.12333sh.gov.cn/sbsjb/wzb/229.jsp', VIRTUAL_LOGIN_SH_INSURANCE_COOKIE_PATH . $data['uid'] . '.txt', VIRTUAL_LOGIN_SH_INSURANCE_COOKIE_PATH . $data['uid'] . '.txt');
        $ShYzm = Http::curl_get('http://www.12333sh.gov.cn/sbsjb/wzb/Bmblist12.jsp', VIRTUAL_LOGIN_SH_INSURANCE_COOKIE_PATH . $data['uid'] . '.txt', VIRTUAL_LOGIN_SH_INSURANCE_COOKIE_PATH . $data['uid'] . '.txt');
        return $ShYzm;
    }

    /**
     * 上海公积金验证码
     * @return mixed
     * @author langziqiang
     */
    public function ShFundYzm()
    {
        $request = Request::instance();
        $data = $request->param();

        $rs = Http::curl_get('https://persons.shgjj.com/VerifyImageServlet', VIRTUAL_LOGIN_SH_FUND_COOKIE_PATH . $data['uid'] . '.txt', VIRTUAL_LOGIN_SH_FUND_COOKIE_PATH . $data['uid'] . '.txt');
        return $rs;
    }


    /**
     * 上海社保登录
     * @return string
     * @author langziqiang
     */
    public function doShSocialLogin()
    {
        $request = Request::instance();
        $data = $request->param();
        $uid = $data['uid'];
        unset($data['uid']);
        $param = http_build_query($data);

        $url = "http://www.12333sh.gov.cn/sbsjb/wzb/dologin.jsp";
        $res = Http::curl_post($url, $param, VIRTUAL_LOGIN_SH_INSURANCE_COOKIE_PATH . $uid . '.txt', true);
        $infoUrl = $res['redirect_url'];        //获取helpinfo的url

        $flag = substr($infoUrl, -1);        //获取最后一位参数
        if ($flag === '0') {
            return '1';
        } else {
            return '0';
        }
    }

    /**
     * 上海公积金登录
     * @return \think\response\Json
     * @author langziqiang
     */
    public function doShFundLogin()
    {
        $request = Request::instance();
        $data = $request->param();
        $uid = $data['uid'];
        unset($data['uid']);
        $param = http_build_query($data);

        $url = "https://persons.shgjj.com/MainServlet";
        $res = Http::curl_post($url, $param, VIRTUAL_LOGIN_SH_FUND_COOKIE_PATH . $uid . '.txt');

        $res = iconv('gb2312', 'UTF-8', $res);

        preg_match('/<title>(.*)<\/title>/isU', $res, $a);

        if (isset($a[1]) && !strpos($a[1], '登录')) {   //登录成功
            preg_match('/<td width=\"751\">(.*)<strong>/isU', $res, $matches1);
            preg_match('/<div[^>]*>公积金账号<\/div>[\s\r\n]*<\/td>[\s\r\n]*<td>(.*)<\/td>/U', $res, $matches2);
            preg_match('/<div[^>]*>所属单位<\/div>[\s\S]*<\/td>[\s\S]*<td>[\s\r\n\t]*([\s\S]*)<\/td>/iU', $res, $matches3);
            preg_match('/<div[^>]*>月缴存额<\/div>[\s\S]*<\/td>[\s\S]*<td>[\s\r\n\t]*([\s\S]*)<\/td>/iU', $res, $matches4);
            preg_match('/<div[^>]*>账户余额<\/div>[\s\S]*<\/td>[\s\S]*<td>[\s\r\n\t]*([\s\S]*)<\/td>/iU', $res, $matches5);
            preg_match('/<div[^>]*>当前账户状态<\/div>[\s\S]*<\/td>[\s\S]*<td>[\s\r\n\t]*([\s\S]*)[\s\r\n\t]*<input[^>]*>/iU', $res, $matches6);

            $arr['name'] = $matches1[1];
            $arr['account'] = $matches2[1];
            $arr['status'] = $matches6[1];
            $arr['company'] = trim($matches3[1]);
            $arr['yueMoney'] = $matches4[1];
            $arr['totalMoney'] = $matches5[1];

            return json($arr);
        } else {
            preg_match('/<font  color="#CC0000">(.*)<\/font>/U', $res, $matches6);
            return $matches6[1];
        }
    }

    /**
     * 获取上海公积金详情
     * @return \think\response\Json
     * @author langziqiang
     */
    public function getShFundDetail()
    {
        $request = Request::instance();
        $data = $request->param();

        $url = "https://persons.shgjj.com/MainServlet?ID=11";

        $rs = Http::curl_get($url, VIRTUAL_LOGIN_SH_FUND_COOKIE_PATH . $data['uid'] . '.txt', VIRTUAL_LOGIN_SH_FUND_COOKIE_PATH . $data['uid'] . '.txt');
        $res = iconv('gb2312', 'UTF-8', $rs);
        $res = str_replace(array("\r\n", "\r", "\n"), '', $res);

        preg_match('/<TABLE.* class="table">(.*)<\/table>/', $res, $matches1);
        preg_match_all('/<tr.*>(.*)<\/tr>/U', $matches1[1], $matches2);

        $count = count($matches2[1]);
        $no_tr = [0, 1, $count - 1, $count - 2, $count - 3];

        foreach ($matches2[1] as $k => $v) {
            if (in_array($k, $no_tr)) {
                continue;
            }

            preg_match_all('/<td><div.*>(.*)<\/div>/U', $v, $matches3);
            $info[$k] = $matches3[1];
        }
        return json($info);
    }

    /**
     * 获取上海社保详情
     * @return \think\response\Json
     * @author langziqiang
     */
    public function getShSocialInfo()
    {
        $request = Request::instance();
        $data = $request->param();

        Http::curl_get('http://www.12333sh.gov.cn/sbsjb/wzb/helpinfo.jsp?id=0', VIRTUAL_LOGIN_SH_INSURANCE_COOKIE_PATH . $data['uid'] . '.txt');
        $res = Http::curl_post('http://www.12333sh.gov.cn/sbsjb/wzb/sbsjbcx12.jsp', '', VIRTUAL_LOGIN_SH_INSURANCE_COOKIE_PATH . $data['uid'] . '.txt', VIRTUAL_LOGIN_SH_INSURANCE_COOKIE_PATH . $data['uid'] . '.txt');

        preg_match('/<xml id=\'dataisxxb_sum1\'>(.*)<\/xml>/isU', $res, $nameAccount);
        preg_match('/<xml id=\'dataisxxb_sum2\'>(.*)<\/xml>/isU', $res, $socialDetail);
        preg_match('/<xml id=\'dataisxxb_sum12\'>(.*)<\/xml>/isU', $res, $companyDetail);
        preg_match('/<xml id=\'dataisxxb_sum4\'>(.*)<\/xml>/isU', $res, $totalMonth);
        $name_account = $this->xmlToArray($nameAccount);
        $socialDetail = $this->xmlToArray($socialDetail);
        $companyDetail = $this->xmlToArray($companyDetail);
        $totalMonth = $this->xmlToArray($totalMonth);
        $data = [
            'nameAccount' => $name_account,
            'detail' => $socialDetail,
            'company' => $companyDetail,
            'totalMonth' => $totalMonth,
        ];
        return json($data);
    }

    /**
     * 上海公积金重置密码
     * @return string
     * @author langziqiang
     */
    public function ShFundResetPassword()
    {
        $request = Request::instance();
        $data = $request->param();
        $param = http_build_query($data);


        $url = 'https://persons.shgjj.com/MainServlet?ID=14';
        $res = Http::curl_post($url, $param, VIRTUAL_LOGIN_SH_FUND_COOKIE_PATH . $data['uid'] . '.txt');

        $res = iconv('gb2312', 'UTF-8', $res);
        preg_match('/<font  color=\"#CC0000\">(.*)<\/font>/U', $res, $matches);

        if (isset($matches[1])) {
            return '1';
        } else {
            return '0';
        }
    }

    /**
     * xml转数组
     * @param $arr
     * @return mixed
     * @author langziqiang
     */
    public function xmlToArray($arr)
    {
        $data = iconv('GBK', 'UTF-8', $arr[1]);

        $xmlstring = simplexml_load_string($data, 'SimpleXMLElement', LIBXML_NOCDATA);
        $val = json_decode(json_encode($xmlstring), true);
        return $val;
    }

    /**
     * 北京公积金验证码
     * @author langziqiang
     */
    public function BjFundYzm()
    {
        $request = Request::instance();
        $data = $request->param();

        $indexUrl = "https://www.bjgjj.gov.cn/wsyw/wscx/gjjcx-login.jsp";
        Http::curl_get($indexUrl, VIRTUAL_LOGIN_BJ_FUND_COOKIE_PATH . $data['uid'] . '.txt', VIRTUAL_LOGIN_BJ_FUND_COOKIE_PATH . $data['uid'] . '.txt');

        $url = "https://www.bjgjj.gov.cn/wsyw/servlet/PicCheckCode1";
        $rs = Http::curl_get($url, VIRTUAL_LOGIN_BJ_FUND_COOKIE_PATH . $data['uid'] . '.txt', VIRTUAL_LOGIN_BJ_FUND_COOKIE_PATH . $data['uid'] . '.txt');
        echo $rs;
    }

    public function doBjFundLogin()
    {
        $request = Request::instance();
        $data = $request->param();
        $uid = $data['uid'];
        unset($data['uid']);
        $param = http_build_query($data);

        $url = "https://www.bjgjj.gov.cn/wsyw/wscx/gjjcx-choice.jsp";
        $res = Http::curl_post($url, $param, VIRTUAL_LOGIN_BJ_FUND_COOKIE_PATH . $uid . '.txt', VIRTUAL_LOGIN_BJ_FUND_COOKIE_PATH . $uid . '.txt');

        $res = iconv('gbk', 'UTF-8', $res);
        $res = str_replace(array("\r\n", "\r", "\n"), '', $res);
        //匹配script是否有gjjcx-login.jsp，跳转链接
        preg_match('/<script>(.*)<\/script>/U', $res, $matches1);

        //如果有表示失败了，没有表示成功了
        if (isset($matches1[1]) && strpos($matches1[1], 'window.location') !== false) {
            //失败
            if (strpos($matches1[1], '校验码')) {
                return '0';
            } else {
                return '1';
            }
        } else {
            //登录成功，但是页面由于密码受限制，禁止访问
            if (preg_match('/输入联名卡号进行登录/', $res)) {
                return '2';
            }

            //成功
            preg_match('/<table.* id=\"new-mytable\".*>(.*)<\/table>/U', $res, $matches2);
            preg_match_all('/<tr>(.*)<\/tr>/U', $matches2[1], $matches3);

            $history_info = $com_info = [];
            foreach ($matches3[1] as $k => $v) {
                if ($k == 0) {
                    continue;
                }
                preg_match_all('/<td.*>(.*)<\/td>/U', $v, $matches4);
                //获取跳转的链接
                preg_match('/window\.open\("(.*)"/U', $matches4[1][1], $matches5);

                //如果历史只有一家公司
                if (count($matches3[1]) == 2) {
                    $com_info = $this->getBjFundNowInfo($matches5[1], $uid);
                }
                //如果历史公司超过一家
                if (count($matches3[1]) == 3) {
                    if ($k == 1) {
                        $history_info = $this->getBjFundHistoryInfo($matches5[1], $uid);
                    } else {
                        $com_info = $this->getBjFundNowInfo($matches5[1], $uid);
                    }
                }
            }
            $detail = array_merge($history_info, $com_info['detail']);
            return json(['info' => $com_info['info'], 'detail' => $detail]);
        }

    }

    /**
     * 获取北京公积金历史数据（非当前公司）
     * @param $url  请求链接
     * @param $uid  用户id
     * @return array
     * @author langziqiang
     */
    public function getBjFundHistoryInfo($url, $uid)
    {
        $res = $this->getBjFundInfo($url, $uid);
        preg_match("/javascript:window\.open\(\'gjj_cxls.jsp\?(.*)\'/U", $res, $matches);
        $historyUrl = 'https://www.bjgjj.gov.cn/wsyw/wscx/gjj_cxls.jsp?' . $matches[1];

        $rs = Http::curl_get($historyUrl, VIRTUAL_LOGIN_BJ_FUND_COOKIE_PATH . $uid . '.txt', VIRTUAL_LOGIN_BJ_FUND_COOKIE_PATH . $uid . '.txt');

        $rs = iconv('gbk', 'UTF-8', $rs);

        $rs = str_replace(array("\r\n", "\r", "\n"), '', $rs);

        preg_match('/<table.*id="new\-mytable3"[^>]*>(.*)<\/table>/U', $rs, $matches1);
        preg_match_all('/<tr.*>(.*)<\/tr>/U', $matches1[1], $matches2);

        foreach ($matches2[1] as $k => $v) {
            if ($k == 0) {
                continue;
            }
            preg_match_all('/<td.*>(.*)<\/td>/U', $v, $matches3);
            $data[$k]['fund_month'] = strtotime(str_replace('&nbsp;', '', $matches3[1][0]));
            $data[$k]['info'] = preg_replace('# #', '', str_replace('&nbsp;', '', $matches3[1][2]));

            preg_match('/<div.*>(.*)<\/div>/U', $matches3[1][3], $matches4);
            if ($matches4[1] > 0) {
                $data[$k]['money'] = str_replace(',', '', $matches4[1]);
            } else {
                preg_match('/<div.*>(.*)<\/div>/U', $matches3[1][4], $matches5);
                $data[$k]['money'] = -str_replace(',', '', $matches5[1]);
            }
            $data[$k]['company'] = '';
        }

        return array_column($data, null, 'fund_month');
    }

    /**
     * 获取北京公积金校验码
     * @return string
     * @author langziqiang
     */
    public function getBjFundActive()
    {
        $request = Request::instance();
        $data = $request->param();

        $header = array(
            'Host:www.bjgjj.gov.cn',
            'Connection:keep-alive',
            'Content-Length:0',
            'Pragma:no-cache',
            'Cache-Control:no-cache',
            'Origin:https://www.bjgjj.gov.cn',
            'User-Agent:Mozilla/5.0 (Windows NT 10.0; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/59.0.3071.115 Safari/537.36',
            'Content-Type:text/html;',
            'Accept:*/*',
            'Referer:https://www.bjgjj.gov.cn/wsyw/wscx/gjjcx-login.jsp',
            'Accept-Encoding:gzip, deflate, br',
            'Accept-Language:zh-CN,zh;q=0.8',
        );

        $url = 'https://www.bjgjj.gov.cn/wsyw/wscx/asdwqnasmdnams.jsp';
        $rs = Http::curl_post($url, '', VIRTUAL_LOGIN_BJ_FUND_COOKIE_PATH . $data['uid'] . '.txt', '', $header);
        return iconv('gb2312', 'UTF-8', $rs);
    }

    /**
     * 获取北京公积金个人基本信息
     * @param $url
     * @param $uid
     * @return mixed|string
     * @author langziqiang
     */
    public function getBjFundInfo($url, $uid)
    {
        $newUrl = 'https://www.bjgjj.gov.cn/wsyw/wscx/' . $url;

        $header = array(
            'Host:www.bjgjj.gov.cn',
            'Connection:keep-alive',
            'Upgrade-Insecure-Requests:1',
            'User-Agent:Mozilla/5.0 (Windows NT 10.0; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/59.0.3071.115 Safari/537.36',
            'Accept:text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,image/apng,*/*;q=0.8',
            'Referer:https://www.bjgjj.gov.cn/wsyw/wscx/gjjcx-choice.jsp',
            'Accept-Encoding:gzip, deflate, br',
            'Accept-Language:zh-CN,zh;q=0.8',
        );

        $res = Http::curl_get($newUrl, VIRTUAL_LOGIN_BJ_FUND_COOKIE_PATH . $uid . '.txt', '', $header);
        $res = iconv('gbk', 'UTF-8', $res);
        $res = str_replace(array("\r\n", "\r", "\n"), '', $res);
        return $res;
    }

    /**
     * 获取北京公积金基本信息
     * @param $url
     * @param $uid
     * @return array
     * @author langziqiang
     */
    public function getBjFundNowInfo($url, $uid)
    {
        $rs = $this->getBjFundInfo($url, $uid);

        preg_match('/<table.*color=#0077a9 >(.*)<\/table>/U', $rs, $matches);
        preg_match_all('/<tr.*>(.*)<\/tr>/U', $matches[1], $matches2);

        $info = [];
        foreach ($matches2[1] as $k => $v) {
            if (in_array($k, array(5, 6, 7))) {
                continue;
            }
            $res = preg_match_all('/<td.*>(.*)<\/td>/U', $v, $matches3);

            if (preg_match('/姓名/', $matches3[1][0])) {
                $info['name'] = $matches3[1][1];
            } elseif (preg_match('/当前余额/', $matches3[1][0])) {
                preg_match('/<div.*>(.*)元<\/div>/', $matches3[1][1], $res);
                $info['totalMoney'] = str_replace(array(",", "\n\r", "\r", "\n"), '', $res[1]);
                $info['totalMoney'] = (float)$info['totalMoney'];
            }

            if (preg_match('/证件号/', $matches3[1][2])) {
                $info['account'] = $matches3[1][3];
            } elseif (preg_match('/单位名称/', $matches3[1][2])) {
                $info['company'] = $matches3[1][3];
            } elseif (preg_match('/帐户状态/', $matches3[1][2])) {
                $info['status'] = $matches3[1][3];
            }
        }


        $detail = $this->getBjFundDetail($rs, $info['company']);
        $sameCompanyInfo = $this->getBjSameComapnyDetail($rs, $uid, $info['company']);
        $allData = array_merge((array)$detail, (array)$sameCompanyInfo);
        return ['info' => $info, 'detail' => array_column($allData, null, 'fund_month')];
    }

    /**
     * 获取最新公司的隐藏详情信息
     * @param string $rs 页面代码
     * @param int $uid 用户id
     * @param string $company 公司
     * @return array
     * @author langziqiang
     */
    private function getBjSameComapnyDetail($rs, $uid, $company)
    {

        preg_match("/javascript:window\.open\(\'gjj_cxls.jsp\?(.*)\'/U", $rs, $matches4);
        $historyUrl = 'https://www.bjgjj.gov.cn/wsyw/wscx/gjj_cxls.jsp?' . $matches4[1];
        $sameCompanyHistory = Http::curl_get($historyUrl, VIRTUAL_LOGIN_BJ_FUND_COOKIE_PATH . $uid . '.txt', VIRTUAL_LOGIN_BJ_FUND_COOKIE_PATH . $uid . '.txt');
        $sameCompanyHistory = iconv('gbk', 'UTF-8', $sameCompanyHistory);
        $sameCompanyHistory = str_replace(array("\r\n", "\r", "\n"), '', $sameCompanyHistory);

        preg_match('/<table.*id="new\-mytable3"[^>]*>(.*)<\/table>/U', $sameCompanyHistory, $matches1);
        preg_match_all('/<tr.*>(.*)<\/tr>/U', $matches1[1], $matches2);

        //如果当前公司详情里面table表格不为空(有数据)
        if (count($matches2[1]) > 1) {
            foreach ($matches2[1] as $k => $v) {
                if ($k == 0) {
                    continue;
                }
                preg_match_all('/<td.*>(.*)<\/td>/U', $v, $matches3);
                $data[$k]['fund_month'] = strtotime(str_replace('&nbsp;', '', $matches3[1][0]));
                $data[$k]['info'] = $matches3[1][2];

                preg_match('/<div.*>(.*)<\/div>/U', $matches3[1][3], $money);
                if ($money[1] > 0) {
                    $data[$k]['money'] = str_replace(',', '', $money[1]);
                } else {
                    preg_match('/<div.*>(.*)<\/div>/U', $matches3[1][4], $money2);
                    $data[$k]['money'] = -str_replace(',', '', $money2[1]);
                }
                $data[$k]['company'] = $company;
            }
            return array_column($data, null, 'fund_month');
        } else {
            return [];
        }
    }

    /**
     * 获取北京公积金详情
     * @param $res
     * @return mixed
     * @author langziqiang
     */
    public function getBjFundDetail($res, $company)
    {
        preg_match('/<table.*id="tab-style"[^>]>(.*)<\/table>/U', $res, $matches);
        preg_match_all('/<tr.*>(.*)<\/tr>/U', $matches[1], $matches1);

        foreach ($matches1[1] as $k => $v) {
            if ($k == 0) {
                continue;
            }
            preg_match_all('/<td.*>(.*)<\/td>/U', $v, $matches2);

            $info[$k]['fund_month'] = strtotime(str_replace('&nbsp;', '', $matches2[1][0]));
            $info[$k]['info'] = str_replace(array("\r\n", "\r", "\n"), '', $matches2[1][2]);
            if (str_replace('&nbsp;', '', $matches2[1][3]) > 0) {
                $info[$k]['money'] = str_replace(array("&nbsp;", ","), '', $matches2[1][3]);
            } else {
                $info[$k]['money'] = -str_replace(array("&nbsp;", ","), '', $matches2[1][4]);
            }
            $info[$k]['company'] = $company;
        }

        return array_column($info, null, 'fund_month');
    }

    /**
     * 北京社保验证码
     * @return mixed
     * @author langziqiang
     */
    public function BjSocialYzm()
    {
        $request = Request::instance();
        $data = $request->param();

        Http::curl_get("http://www.bjrbj.gov.cn/csibiz/indinfo/login.jsp", VIRTUAL_LOGIN_BJ_INSURANCE_COOKIE_PATH . $data['uid'] . '.txt', VIRTUAL_LOGIN_BJ_INSURANCE_COOKIE_PATH . $data['uid'] . '.txt');
        $rs = Http::curl_get("http://www.bjrbj.gov.cn/csibiz/indinfo/validationCodeServlet.do", VIRTUAL_LOGIN_BJ_INSURANCE_COOKIE_PATH . $data['uid'] . '.txt', VIRTUAL_LOGIN_BJ_INSURANCE_COOKIE_PATH . $data['uid'] . '.txt');
        return $rs;
    }

    /**
     * 北京登录发送手机验证码
     * @return string
     * @author langziqiang
     */
    public function BjsendPhoneYzm()
    {
        $request = Request::instance();
        $data = $request->param();
        $uid = $data['uid'];
        unset($data['uid']);

        $param = http_build_query($data);
        $url = "http://www.bjrbj.gov.cn/csibiz/indinfo/passwordSetAction!getTelSafeCode";

        $rs = Http::curl_post($url, $param, VIRTUAL_LOGIN_BJ_INSURANCE_COOKIE_PATH . $uid . '.txt');
        return trim($rs, '"');
    }

    /**
     * 北京社保登陆
     * @author langziqiang
     */
    public function doBjSocialLogin()
    {
        $request = Request::instance();
        $data = $request->param();
        $uid = $data['uid'];
        unset($data['uid']);

        $param = http_build_query($data);

        $url = "http://www.bjrbj.gov.cn/csibiz/indinfo/login_check";
        Http::curl_post($url, $param, VIRTUAL_LOGIN_BJ_INSURANCE_COOKIE_PATH . $uid . '.txt');
        $result = Http::curl_get("http://www.bjrbj.gov.cn/csibiz/indinfo/menu.jsp?open=null", VIRTUAL_LOGIN_BJ_INSURANCE_COOKIE_PATH . $uid . '.txt');

        if (preg_match('/登录失败/', $result)) {
            return '0';
        }

        //个人基本信息
        Http::curl_get("http://www.bjrbj.gov.cn/csibiz/indinfo/search/ind/ind_new_info_index.jsp", VIRTUAL_LOGIN_BJ_INSURANCE_COOKIE_PATH . $uid . '.txt');
        $info = Http::curl_post("http://www.bjrbj.gov.cn/csibiz/indinfo/search/ind/indNewInfoSearchAction", '', VIRTUAL_LOGIN_BJ_INSURANCE_COOKIE_PATH . $uid . '.txt');

        //详情信息
        Http::curl_get("http://www.bjrbj.gov.cn/csibiz/indinfo/search/ind/ind_pay_index.jsp", VIRTUAL_LOGIN_BJ_INSURANCE_COOKIE_PATH . $uid . '.txt');
        $info = $this->getBjSocialInfo($info);
        $detail = $this->getBjSocialDetail($uid);

        return json(['info' => $info, 'detail' => $detail]);

    }

    /**
     * 获取北京社保基本信息
     * @param $info
     * @return array
     * @author langziqiang
     */
    public function getBjSocialInfo($info)
    {
        $res = str_replace(array("\r\n", "\r", "\n"), '', $info);
        preg_match('/姓\s*名\s*<\/td>\s*<td[^>]*>(.*)<\/td>/U', $res, $matches1);
        preg_match('/码\s*）\s*<\/td>\s*<td[^>]*>(.*)<\/td>/U', $res, $matches2);

        return ['name' => $matches1[1], 'account' => $matches2[1]];
    }

    /**
     * 获取北京社保详细信息
     * @param $uid
     * @author langziqiang
     */
    public function getBjSocialDetail($uid)
    {
        $time = date('Y', time());   //当前年份
        $arr = [$time, $time - 1, $time - 2, $time - 3, $time - 4, $time - 5];  //获取6年的数据

        $type = ['unemployment', 'maternity', 'medicalcare', 'injuries', 'oldage'];
        $data = [];

        foreach ($arr as $k => $v) {
            foreach ($type as $c => $o) {
                $data[$v][] = $this->getBjTypeDetail($o, $v, $uid);
            }
        }

        $newArr = [];
        foreach ($data as $c => $o) {
            foreach ($o as $a => $b) {
                foreach ($b as $q => $w) {
                    if (!array_key_exists($w['social_month'], $newArr)) {
                        $newArr[$w['social_month']] = $w;
                    } else {
                        $newArr[$w['social_month']] = array_merge($newArr[$w['social_month']], $w);
                    }
                }
            }
        }

        return $newArr;
    }

    /**
     * 获取北京社保对应的五险的金额
     * @param $type
     * @param $year
     * @param $uid
     * @author langziqiang
     */
    public function getBjTypeDetail($type, $year, $uid)
    {
        $url = "http://www.bjrbj.gov.cn/csibiz/indinfo/search/ind/indPaySearchAction!" . $type . "?searchYear=" . $year;

        $rs = Http::curl_post($url, '', VIRTUAL_LOGIN_BJ_INSURANCE_COOKIE_PATH . $uid . '.txt');

        $rs = str_replace(array("\r\n", "\r", "\n"), '', $rs);

        if (preg_match('/没有找到符合条件的个人用户信息/', $rs)) {
            return [];
        }

        preg_match('/<table width="93%"[^>]*>(.*)<\/table>/U', $rs, $matches1);

        preg_match_all('/<tr.*>(.*)<\/tr>/U', $matches1[1], $matches2);
        foreach ($matches2[1] as $k => $v) {
            if (in_array($k, [0, 1])) {
                continue;
            }

            preg_match_all('/<td.*>(.*)<\/td>/U', $v, $matches3);

            if (!is_numeric(trim($matches3[1][2]))) {
                continue;
            }

            $data[$k]['social_month'] = strtotime($matches3[1][0]);  //缴纳月份
            //判断类型，保存公司以及个人的金额
            switch ($type) {
                case 'oldage':
                    $data[$k]['com_yanglao'] = $matches3[1][3];
                    $data[$k]['self_yanglao'] = $matches3[1][4];
                    $data[$k]['basic_social'] = $matches3[1][2];
                    $data[$k]['company'] = $matches3[1][5];
                    break;
                case 'injuries':
                    $data[$k]['com_gongshang'] = $matches3[1][2];
                    break;
                case 'maternity':
                    $data[$k]['com_shengyu'] = $matches3[1][2];
                    break;
                case 'medicalcare':
                    $data[$k]['com_yiliao'] = trim($matches3[1][3]);
                    $data[$k]['self_yiliao'] = trim($matches3[1][4]);
                    break;
                case 'unemployment':
                    $data[$k]['com_shiye'] = $matches3[1][2];
                    $data[$k]['self_shiye'] = $matches3[1][3];
                    break;
            }
        }
        return $data;
    }

    /**
     * 获取广州lt
     * @return mixed
     * @author langziqiang
     */
    public function GzSocialLt()
    {
        $request = Request::instance();
        $data = $request->param();

        @unlink(VIRTUAL_LOGIN_GZ_INSURANCE_COOKIE_PATH . $data['uid'] . '.txt');
        $loginUrl = "http://gzlss.hrssgz.gov.cn/cas/login";
        $rs = Http::curl_get($loginUrl, VIRTUAL_LOGIN_GZ_INSURANCE_COOKIE_PATH . $data['uid'] . '.txt', VIRTUAL_LOGIN_GZ_INSURANCE_COOKIE_PATH . $data['uid'] . '.txt');

        $str = str_replace(array("\r\n", "\r", "\n"), '', $rs);

        preg_match('/ame="lt"\s*value="(.*)"/U', $rs, $matches);
        preg_match('/modulus="(.*)"/U', $str, $matches1);
        return json(['lt' => $matches[1], 'modulus' => $matches1[1]]);

    }

    /**
     * 获取广州验证码
     * @return mixed
     * @author langziqiang
     */
    public function GzSocialYzm()
    {
        $request = Request::instance();
        $data = $request->param();

        $yzmUrl = "http://gzlss.hrssgz.gov.cn/cas/captcha.jpg";
        $rs = Http::curl_get($yzmUrl, VIRTUAL_LOGIN_GZ_INSURANCE_COOKIE_PATH . $data['uid'] . '.txt', VIRTUAL_LOGIN_GZ_INSURANCE_COOKIE_PATH . $data['uid'] . '.txt');
        return $rs;
    }

    /**
     * 登陆广州社保
     * @author langziqiang
     */
    public function doGzSocialLogin()
    {
        $request = Request::instance();
        $data = $request->param();
        $uid = $data['uid'];
        unset($data['userid']);
        unset($data['pwd']);
        unset($data['uid']);

        $param = http_build_query($data);
        $loginUrl = "http://gzlss.hrssgz.gov.cn/cas/login";

        $header = array(
            'Host:	gzlss.hrssgz.gov.cn',
            'Content-Length:	647',
            'Cache-Control:	max-age=0',
            'Origin:	http://gzlss.hrssgz.gov.cn',
            'Upgrade-Insecure-Requests:	1',
            'User-Agent:	Mozilla/5.0 (Windows NT 10.0; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/59.0.3071.115 Safari/537.36',
            'Content-Type:	application/x-www-form-urlencoded',
            'Accept:	text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,image/apng,*/*;q=0.8',
            'Referer:	http://gzlss.hrssgz.gov.cn/cas/login',
            'Accept-Encoding:	gzip, deflate',
            'Accept-Language:	zh-CN,zh;q=0.8'
        );
        /*多个302重定向模拟
         *1.请求登录页
         */
        $rs = Http::curl_post($loginUrl, $param, VIRTUAL_LOGIN_GZ_INSURANCE_COOKIE_PATH . $uid . '.txt', '', $header, VIRTUAL_LOGIN_GZ_INSURANCE_COOKIE_PATH . $uid . '.txt');

        //判断登陆结果
        if (preg_match('/errors/', $rs)) {
            $html = str_replace(array("\r\n", "\r", "\n"), '', $rs);
            if (preg_match('/账号或密码错误/', $html)) {
                return '1';                 //账号或者密码错误
            } else {                          //验证码错误
                return '2';
            }
        }

        //请求main.html
        preg_match('/<a[^>]*>(.*)<\/a>/U', $rs, $url_1);
        $a = Http::curl_get($url_1[1], VIRTUAL_LOGIN_GZ_INSURANCE_COOKIE_PATH . $uid . '.txt', VIRTUAL_LOGIN_GZ_INSURANCE_COOKIE_PATH . $uid . '.txt');

        //请求社保综合页面
        $url = "http://gzlss.hrssgz.gov.cn/gzlss_web/business/front/foundationcentre/getPersonPayHistoryInfoByPage.xhtml?querylog=true&businessocde=SBGRJFLSCX&visitterminal=PC";
        $page = Http::curl_get($url, VIRTUAL_LOGIN_GZ_INSURANCE_COOKIE_PATH . $uid . '.txt', VIRTUAL_LOGIN_GZ_INSURANCE_COOKIE_PATH . $uid . '.txt');

        //请求五险页面
        $page = str_replace(array("\r\n", "\r", "\n"), '', $page);
        preg_match('/<select\s*name="aac001"[^>]*>\s*<option[^>]*>(.*)<\/option>/U', $page, $matches);
        $url = 'http://gzlss.hrssgz.gov.cn/gzlss_web/business/front/foundationcentre/viewPage/viewPersonPayHistoryInfo.xhtml?aac001=' . $matches[1] . '&xzType=1&startStr=&endStr=&querylog=true&businessocde=291QB-GRJFLS&visitterminal=PC';
        $page = Http::curl_get($url, VIRTUAL_LOGIN_GZ_INSURANCE_COOKIE_PATH . $uid . '.txt', VIRTUAL_LOGIN_GZ_INSURANCE_COOKIE_PATH . $uid . '.txt');


        if (preg_match('/经达到5次/', $page)) {
            return '3';
        }
        $data = $this->getGzSocialDetail($page);

        //请求医疗页面
        $arr = array_keys($data['detail']);
        sort($arr);
        $start = date('Ym', $arr[0]);
        $end = date('Ym', end($arr));
        $yiliaoUrl = "http://gzlss.hrssgz.gov.cn/gzlss_web/business/front/foundationcentre/getHealthcarePersonPayHistorySumup.xhtml?query=1&querylog=true&businessocde=291QB_YBGRJFLSCX&visitterminal=PC&aac001=3000981880&startStr=" . $start . "&endStr=" . $end;
        $yiliao = Http::curl_get($yiliaoUrl, VIRTUAL_LOGIN_GZ_INSURANCE_COOKIE_PATH . $uid . '.txt', VIRTUAL_LOGIN_GZ_INSURANCE_COOKIE_PATH . $uid . '.txt');
        if (preg_match('/经达到5次/', $page)) {
            return '3';
        }

        $this->getGzSocialMedical($yiliao, $data);
        return json($data);
    }

    /**
     * 获取广州医疗保险数据
     * @param $html
     * @param $data
     * @author langziqiang
     */
    public function getGzSocialMedical($html, &$data)
    {
        $html = str_replace(array("\r\n", "\r", "\n"), '', $html);
        preg_match_all('/<tr temp="职工社会医疗保险"">(.*)<\/tr>/U', $html, $matches5);
        foreach ($matches5[1] as $k => $v) {
            preg_match_all('/<td[^>]*>(.*)<\/td>/U', $v, $matches6);
            $start = substr(trim($matches6[1][1]), 0, 6);
            $end = substr(trim($matches6[1][2]), 0, 6);
            $month = (int)trim($matches6[1][3]);
            preg_match('/getValue\((.*)\)/U', $matches6[1][6], $com_yiliao);
            preg_match('/getValue\((.*)\)/U', $matches6[1][7], $self_yiliao);
            for ($year = $start; $year <= $end;) {
                $year = strtotime(substr_replace($year, '-', 4, 0));
                $data['detail'][$year]['com_yiliao'] = $com_yiliao[1] / $month;
                $data['detail'][$year]['self_yiliao'] = $self_yiliao[1] / $month;
                $year = date('Ym', strtotime("+1 month", $year));
            }
        }
    }

    /**
     * 获取广州四险的详情
     * @param $html
     * @return array
     * @author langziqiang
     */
    public function getGzSocialDetail($html)
    {
        $html = str_replace(array("\r\n", "\r", "\n"), '', $html);

        preg_match('/<table[^>]*>(.*)<\/table>/', $html, $matches);
        preg_match_all('/<tr[^>]*>(.*)<\/tr>/U', $matches[1], $matches1);

        $data = [];
        $count = count($matches1[1]);
        foreach ($matches1[1] as $k => $v) {
            if (in_array($k, [1, 2, 3, 4, $count - 1, $count - 2, $count - 3, $count - 4])) {
                continue;
            }
            //保存姓名，账号
            if ($k == 0) {
                preg_match('/<td[^>]*>(.*)<\/td>/U', $v, $matches2);
                preg_match_all('/<span[^>]*>(.*)<\/span>/U', $matches2[1], $matches3);
                $data['info']['name'] = str_replace(array("姓名：", "&nbsp;"), '', $matches3[1][1]);
                $data['info']['account'] = str_replace(array("证件号码：", "&nbsp;"), '', $matches3[1][2]);
                continue;
            }

            preg_match_all('/<td[^>]*>(.*)<\/td>/U', $v, $matches4);

            for ($year = $matches4[1][0]; $year <= $matches4[1][1];) {
                $year = strtotime(substr_replace($year, '-', 4, 0));

                //如果累计月数不为0
                if (!$matches4[1][2] == 0) {
                    if ($matches4[1][4] != 0) {
                        $data['detail'][$year]['social_month'] = $year;
                        $data['detail'][$year]['basic_social'] = $matches4[1][3];
                        $data['detail'][$year]['com_yanglao'] = sprintf("%.2f", $matches4[1][4] / $matches4[1][2]);
                        $data['detail'][$year]['self_yanglao'] = sprintf("%.2f", $matches4[1][5] / $matches4[1][2]);
                        $data['detail'][$year]['company'] = $matches4[1][11];
                    }
                    if ($matches4[1][6] != 0) {
                        $data['detail'][$year]['com_shiye'] = sprintf("%.2f", $matches4[1][6] / $matches4[1][2]);
                        $data['detail'][$year]['self_shiye'] = sprintf("%.2f", $matches4[1][7] / $matches4[1][2]);
                        $data['detail'][$year]['com_gongshang'] = sprintf("%.2f", $matches4[1][8] / $matches4[1][2]);
                    }
                    if ($matches4[1][9] != 0) {
                        $data['detail'][$year]['com_shengyu'] = sprintf("%.2f", $matches4[1][9] / $matches4[1][2]);
                    }
                }

                $year = date('Ym', strtotime("+1 month", $year));
            }
        }

        return $data;
    }

    /**
     * 获取成都社保页面
     * @return mixed
     * @author langziqiang
     */
    public function CdSocialHtml()
    {
        $request = Request::instance();
        $data = $request->param();

        $rs = Http::curl_get("http://jypt.cdhrss.gov.cn:8048/portal.php?id=1", VIRTUAL_LOGIN_CD_INSURANCE_COOKIE_PATH . $data['uid'] . '.txt', VIRTUAL_LOGIN_CD_INSURANCE_COOKIE_PATH . $data['uid'] . '.txt');

        $res = str_replace(array("\r\n", "\r", "\n"), '', $rs);
        \cache($data['uid'] . 'CdSocial', $res, 100);
        preg_match('/m=8&i=1&id=(.*)"/U', $res, $matches2);

        //获取页面js(动态)
        $url = "http://jypt.cdhrss.gov.cn:8048/del.php?op=1&m=8&i=1&id=" . $matches2[1];
        $rs = Http::curl_get($url, VIRTUAL_LOGIN_CD_INSURANCE_COOKIE_PATH . $data['uid'] . '.txt');
        \cache($data['uid'] . 'CdSocialDiv', $rs, 100);
        \cache($data['uid'] . 'id', $matches2[1], 100);

        return $rs;

    }

    /**
     * 返回第一个div的内容和有效的div内容
     * @return mixed
     * @author langziqiang
     */
    public function getCdSocialFirstDiv()
    {
        $request = Request::instance();
        $data = $request->param();

        $divNum = \cache($data['uid'] . 'CdSocialDiv');
        $info = \cache($data['uid'] . 'CdSocial');

        preg_match_all('/login_form(\d+)/', $divNum, $matches);

        //组装多个div
        if ($matches[1]) {
            preg_match('/id="div1"[^>]*>(.*)<\/form>\s*<\/div>/U', $info, $matches1);
            $img1 = str_replace('<img src="/', '<img src="http://jypt.cdhrss.gov.cn:8048/', $matches1[1]);
            preg_match('/id="div' . $matches[1][1] . '"[^>]*>(.*)<\/form>\s*<\/div>/U', $info, $matches2);
            $img2 = str_replace('<img src="/', '<img src="http://jypt.cdhrss.gov.cn:8048/', $matches2[1]);
            $img3 = str_replace('action="mlogin_action.php?id=1"', '', $img2);

        }

        return '<div class="main" id="div1" style="display:none">' . $img1 . '</form></div><div class="main" id="div' . $matches[1][1] . '" style="display:none">' . $img3 . '</form></div>';
    }

    /**
     * 获取成都社保页面input
     * @return mixed
     * @author langziqiang
     */
    public function getCdSocialDel()
    {
        $request = Request::instance();
        $data = $request->param();

        $id = \cache($data['uid'] . 'id');
        $url = "http://jypt.cdhrss.gov.cn:8048/del.php?op=1&m=" . $data['id'] . "&i=2&id=" . $id;

        $rs = Http::curl_post($url, '', VIRTUAL_LOGIN_CD_INSURANCE_COOKIE_PATH . $data['uid'] . '.txt');
        return $rs;
    }

    /**
     * 成都社保登陆
     * @return \think\response\Json
     * @author langziqiang
     */
    public function doCdSocialLogin()
    {
        $request = Request::instance();
        $data = $request->param();
        $uid = $data['uid'];
        unset($data['uid']);
        unset($data['username']);
        unset($data['userpw']);
        $param = http_build_query($data);

        $url = "http://jypt.cdhrss.gov.cn:8048/mlogin_action.php?id=1";
        $rs = Http::curl_post($url, $param, VIRTUAL_LOGIN_CD_INSURANCE_COOKIE_PATH . $uid . '.txt');

        //获取账号，密码，验证码
        preg_match("/name='username'\s*value='(.*)'[^>]*>/U", $rs, $matches1);
        preg_match("/name='password'\s*value='(.*)'[^>]*>/U", $rs, $matches2);
        preg_match("/name='checkCode'\s*value='(.*)'[^>]*>/U", $rs, $matches3);

        //做成都社保的第二次登陆
        $info['username'] = $matches1[1];
        $info['password'] = $matches2[1];
        $info['checkCode'] = $matches3[1];
        $info['redirect_uri'] = 'http://insurance.cdhrss.gov.cn/GetTokenAction.do';
        $info['client_id'] = 'yhtest';
        $info['response_type'] = 'code';
        $info['password1'] = '';
        $info['state'] = null;
        $info['e'] = null;
        $info['m'] = null;
        $info['sfz'] = '';

        $url = "http://jypt.cdhrss.gov.cn:8045/yhjypt/oauth/authorizeNoCaAction!getCode.do";
        $param = http_build_query($info);
        $rs = Http::curl_post($url, $param, VIRTUAL_LOGIN_CD_INSURANCE_COOKIE_PATH . $uid . '.txt');
        //登录失败
        if (preg_match('/8048/', $rs)) {
            return '0';
        }

        preg_match('/<a[^>]*>(.*)<\/a>/U', $rs, $matches4);
        $insuranceUrl = $matches4[1];
        $insuranceUrl = str_replace('code', 'state=null&code', $insuranceUrl);

        $rs = Http::curl_get($insuranceUrl, VIRTUAL_LOGIN_CD_INSURANCE_COOKIE_PATH . $uid . '.txt', VIRTUAL_LOGIN_CD_INSURANCE_COOKIE_PATH . $uid . '.txt');

        $message = $this->getCdSocialInfo($uid);
        $detail = $this->getCdSocialDetail($uid);
        return json(['info' => $message, 'detail' => $detail]);
    }

    /**
     * 成都社保基本信息
     *
     * @param $uid
     * @return array
     * @author langziqiang
     */
    public function getCdSocialInfo($uid)
    {
        $rs = Http::curl_get("http://insurance.cdhrss.gov.cn/QueryInsuranceInfo.do?flag=1", VIRTUAL_LOGIN_CD_INSURANCE_COOKIE_PATH . $uid . '.txt', VIRTUAL_LOGIN_CD_INSURANCE_COOKIE_PATH . $uid . '.txt');


        $res = str_replace(array("\r\n", "\r", "\n"), '', $rs);
        preg_match('/名[^>]*>\s*<td[^>]*>(.*)<\/td>/U', $res, $name);
        preg_match('/号码[^>]*>\s*<td[^>]*>(.*)<\/td>/U', $res, $account);

        return ['name' => $name[1], 'account' => $account[1]];
    }

    /**
     * 成都社保详细信息
     * @param $uid
     * @return array
     * @author langziqiang
     */
    public function getCdSocialDetail($uid)
    {

        $rs = Http::curl_get("http://insurance.cdhrss.gov.cn/QueryInsuranceInfo.do?flag=2", VIRTUAL_LOGIN_CD_INSURANCE_COOKIE_PATH . $uid . '.txt', VIRTUAL_LOGIN_CD_INSURANCE_COOKIE_PATH . $uid . '.txt');

        $array = ['old' => 3, 'shiye' => 8, 'yiliao' => 5, 'gongshang' => 7, 'shengyu' => 10];

        //分别请求5个页面，获得5险的数据
        foreach ($array as $k => $v) {
            $data[] = $this->getCdSocialTypeDetail($v, $uid);
        }


        //组装社保的五险详情
        $newArr = [];
        foreach ($data as $c => $o) {
            foreach ($o as $a => $b) {
                if (!array_key_exists($b['social_month'], $newArr)) {
                    $newArr[$b['social_month']] = $b;
                } else {
                    $newArr[$b['social_month']] = array_merge($newArr[$b['social_month']], $b);
                }
            }
        }

        return $newArr;
    }

    /**
     * 成都社保根据险种类型获取详细信息
     * @param int $id get请求所需参数
     * @param int $uid userid
     * @return mixed
     * @author langziqiang
     */
    public function getCdSocialTypeDetail($id, $uid)
    {
        $url = "http://insurance.cdhrss.gov.cn/QueryInsuranceInfo.do?flag=" . $id;

        $rs = Http::curl_get($url, VIRTUAL_LOGIN_CD_INSURANCE_COOKIE_PATH . $uid . '.txt');
        $res = str_replace(array("\r\n", "\r", "\n"), '', $rs);
        preg_match('/<table[^>]*>(.*)<\/table>/U', $res, $matches1);

        preg_match_all('/<tr>(.*)<\/tr>/U', $matches1[1], $matches2);

        foreach ($matches2[1] as $k => $v) {
            if (in_array($k, [0, 1])) {
                continue;
            }

            preg_match_all('/<td[^>]*>(.*)<\/td>/U', $v, $matches3);

            //区别医疗所在列不同
            if ($id == 5) {
                $data[$k]['social_month'] = getTime($matches3[1][0]);
            } else {
                $data[$k]['social_month'] = getTime($matches3[1][1]);
            }

            switch ($id) {
                case 3 :
                    $data[$k]['company'] = $matches3[1][2];
                    $data[$k]['basic_social'] = getCdSocialMoney($matches3[1][3]);
                    $data[$k]['self_yanglao'] = getCdSocialMoney($matches3[1][5]);
                    $data[$k]['com_yanglao'] = getCdSocialMoney($matches3[1][4]);
                    break;
                case 8 :
                    $data[$k]['com_shiye'] = getCdSocialMoney($matches3[1][4]);
                    $data[$k]['self_shiye'] = getCdSocialMoney($matches3[1][5]);
                    break;
                case 5 :
                    $data[$k]['com_yiliao'] = getCdSocialMoney($matches3[1][3]);
                    $data[$k]['self_yiliao'] = getCdSocialMoney($matches3[1][4]);
                    break;
                case 7 :
                    $data[$k]['com_gongshang'] = $matches3[1][4];
                    break;
                case 10 :
                    $data[$k]['com_shengyu'] = getCdSocialMoney($matches3[1][4]);
                    break;
            }

        }
        return $data;
    }


    /**
     * 获取成都公积金loginid
     * @return mixed
     * @author langziqiang
     */
    public function getCdFundLoginId()
    {
        $request = Request::instance();
        $data = $request->param();

        //第一步：请求首页，保存cookie
        $indexUrl = "https://www.cdzfgjj.gov.cn:9802/cdnt/login.jsp";
        $rs = Http::curl_get($indexUrl, VIRTUAL_LOGIN_CD_FUND_COOKIE_PATH . $data['uid'] . '.txt', VIRTUAL_LOGIN_CD_FUND_COOKIE_PATH . $data['uid'] . '.txt');

        $LoginIdUrl = "https://www.cdzfgjj.gov.cn:9802/cdnt/infor/queryAction!getMsg.do";
        $rs = Http::curl_post2($LoginIdUrl, '', VIRTUAL_LOGIN_CD_FUND_COOKIE_PATH . $data['uid'] . '.txt');
        return $rs;
    }

    /**
     * 获取成都公积金token
     * @return mixed
     * @author langziqiang
     */
    public function getCdFundToken()
    {
        $request = Request::instance();
        $data = $request->param();
        $uid = $data['uid'];
        unset($data['uid']);
        $param = http_build_query($data);

        $url = "https://www.cdzfgjj.gov.cn:9802/cdnt/infor/queryAction!getToken.do";
        $rs = Http::curl_post2($url, $param, VIRTUAL_LOGIN_CD_FUND_COOKIE_PATH . $uid . '.txt');
        return $rs;
    }
}
