<?php

/**
 * 1.接口只能生成已发布的小程序的二维码
 * 2.本接口为 wxacode.getUnlimited，通过该接口生成的小程序码，永久有效，数量暂无限制
 * 3.文档地址 https://developers.weixin.qq.com/miniprogram/dev/api-backend/open-api/qr-code/wxacode.getUnlimited.html
 */

define("APPID","");  // 小程序的appid
define("SECRET",""); // 小程序的appSecret


generateCode(); // 请求生成小程序码并保存到本地


function generateCode(){

    $token = getToken(APPID,SECRET);

    // 创建目录,用来存放小程序码图片
    $rootPath = "qrcode";
    if(!is_dir($rootPath)){
        mkdir($rootPath, 0777, true);
    }

    // 为二维码创建一个文件，随机命名
    $qrcodeName = $rootPath.'/'.time().'.png';
    fopen($qrcodeName, "w");

    //自定义参数；最大32个可见字符，只支持数字，大小写英文以及部分特殊字符：!#$&'()*+,/:;=?@-._~
    $postdata['scene'] = "test=1";
    // 扫码跳转的页面；必须是已经发布的小程序存在的页面，不传则默认跳主页面
    $postdata['page'] = 'pages/index/index';
    // 宽度，单位 px，最小 280px，最大 1280px
    $postdata['width'] = 430;
    // 线条颜色
    $postdata['auto_color'] = false;
    //auto_color 为 false 时生效，使用 rgb 设置颜色
    $postdata['line_color'] = ['r'=>'154','g'=>'55','b'=>'86'];
    // 是否有底色，为true时是透明的
    $postdata['is_hyaline'] = false;


    $url="https://api.weixin.qq.com/wxa/getwxacodeunlimit?access_token=".$token;
    // post请求
    $result = curlPost($url, json_encode($postdata));

    $data = json_decode($result,true);

    if(!empty($data['errcode'])){
        return "生成二维码失败".$data['errmsg'];
    }

    // 保存二维码
    $res = file_put_contents($qrcodeName,$result);

    var_dump($res);
    return $res;
}




// 请求 AccessToken
function getToken(string $appid,string $secret){

    $url = 'https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid='.$appid.'&secret='.$secret;
    $data = file_get_contents($url);
    $data = json_decode(htmlspecialchars_decode($data), true);

    if($data['errcode']){
        throw new Exception($data['errmsg']);
    }

    return $data['access_token'];
}



function curlPost($url, $data = array(), $timeout = 60){

    $cacert = getcwd() . '/cacert.pem'; //CA根证书
    $SSL = substr($url, 0, 8) == "https://" ? true : false;

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout-2);

    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // 信任任何证书
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2); // 关闭SSL验证

    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array('Expect:')); //避免data数据过长问题
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    //curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data)); //data with URLEncode

    $ret = curl_exec($ch);

    curl_close($ch);
    return $ret;
}