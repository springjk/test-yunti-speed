<?php

// 各数字所代表的颜色如下：

// 字背景颜色范围:40----49
// 40:黑
// 41:深红
// 42:绿
// 43:黄色
// 44:蓝色
// 45:紫色
// 46:深绿
// 47:白色

// 字颜色:30-----------39
// 30:黑
// 31:红
// 32:绿
// 33:黄
// 34:蓝色
// 35:紫色
// 36:深绿
// 37:白色

function colorize($text, $status)
{
    $out = '';
    switch ($status) {
        case "SUCCESS":
            $out = "[32m"; //Green
            break;
        case "FAILURE":
            $out = "[31m"; //Red
            break;
        case "WARNING":
            $out = "[33m"; //Yellow
            break;
        case "NOTE":
            $out = "[34m"; //Blue
            break;
        default:
            throw new Exception("Invalid status: " . $status);
    }
    return chr(27) . "$out" . "$text" . chr(27) . "[0m";
}

//get cookie
function get_cookie($url, $cookie)
{
    $ch = curl_init();
    //设置选项，包括URL
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_HEADER, 0);
    curl_setopt($ch, CURLOPT_COOKIEJAR, $cookie); //设置Cookie信息保存在指定的文件中
    //执行并获取HTML文档内容
    $output = curl_exec($ch);
    //释放curl句柄
    curl_close($ch);
    //打印获得的数据

    return $output;

}

//模拟登录
function login_post($url, $cookie, $post)
{
    $curl = curl_init();//初始化curl模块
    curl_setopt($curl, CURLOPT_URL, $url);//登录提交的地址
    curl_setopt($curl, CURLOPT_HEADER, 0);//是否显示头信息
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);//是否自动显示返回的信息
    curl_setopt($curl, CURLOPT_COOKIEFILE, $cookie);//读取cookie
    curl_setopt($curl, CURLOPT_COOKIEJAR, $cookie); //设置Cookie信息保存在指定的文件中
    curl_setopt($curl, CURLOPT_REFERER, "https://www.ytpub.com/users/sign_in");   //来路
    curl_setopt($curl, CURLOPT_POST, 1);//post方式提交
    curl_setopt($curl, CURLOPT_FOLLOWLOCATION, 1);
    curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($post));//要提交的信息
    $result = curl_exec($curl);//执行cURL抓取页面内容
    curl_close($curl);
    return $result;
}

//登录成功后获取数据
function get_content($url, $cookie)
{
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_HEADER, 0);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_COOKIEFILE, $cookie);//读取cookie
    $result = curl_exec($ch);//执行cURL抓取页面内容
    curl_close($ch);
    return $result;
}


function getNeedBetween($kw, $mark1, $mark2)
{
    $st = stripos($kw, $mark1);
    $ed = stripos($kw, $mark2);
    if (($st == false || $ed == false) || $st >= $ed)
        return 0;
    $kw = substr($kw, ($st + strlen($mark1)), ($ed - $st - strlen($mark2) + 1));
    return $kw;
}


$servers = file_get_contents('servers.txt');

$servers = json_decode($servers, true);


if (!isset($servers['expires']) || $servers['expires'] < time()) {

    $login_url = 'https://www.ytpub.com/users/sign_in';


    $cookie = 'cookie.dat';
    $a = get_cookie($login_url, $cookie);
    // preg_match_all('/<meta(.*?)csrf-token',$a, $b);

    $csrf_token = getNeedBetween($a, '/>' . PHP_EOL . '<meta content="', '" name="csrf-token"');

    $post_data = [
        'user[login]' => 'springjk',
        'user[password]' => '445092433',
        'user[remember_me]' => 1,
        'authenticity_token' => $csrf_token,
        'commit' => '登录',
        'utf8' => '✓',
    ];

    $c = login_post($login_url, $cookie, $post_data);
    $d = get_content('https://www.ytpub.com/admin/servers', $cookie);


    $html = $d;


    $html = eregi_replace(">[\r\n\t ]+<", "><", $html); // 去掉多余的空字符
    eregi("<table[^>]*>(.+)</table>", $html, $regs); // 提取表体
    $array = split("</tr>", $regs[1]); // 按行分解成数组
    array_pop($array); // 去处尾部多余的元素
    for ($i = 0; $i < count($array); $i++) {
        $array[$i] = split("</td>", $array[$i]); // 分裂各列
        array_pop($array[$i]); // 去处尾部多余的元素
    }
    for ($i = 0; $i < count($array); $i++) {
        for ($j = 0; $j < count($array[$j]); $j++) {
            if (eregi("colspan.*([0-9]+)", $array[$i][$j], $regs)) { // 如果跨列
                $t = array();
                while (--$regs[1] > 0) // 补足差额
                    array_push($t, "");
                $array[$i] = array_merge(array_slice($array[$i], 0, $j + 1), $t, array_splice($array[$i], $j + 1));
            }
            if (eregi("rowspan.*([0-9]+)", $array[$i][$j], $regs)) { // 如果跨行
                if (!isset($t)) // 跨列、跨行不同时存在
                    $t = array("");
                else
                    array_push($t, "");
                $k = $regs[1];
                while (--$k > 0) // 补足差额
                    $array[$i + $k] = array_merge(array_slice($array[$i + $k], 0, $j), $t, array_splice($array[$i + $k], $j));
            }
            unset($t);
        }
    }

    // 除去html标记
    for ($i = 0; $i < count($array); $i++) {
        for ($j = 0; $j < count($array[$i]); $j++) {
            $array[$i][$j] = trim(strip_tags($array[$i][$j]));
        }
    }

    $servers = array_filter($array);
    $k = 0;
    foreach ($servers as $key => $value) {
        $count = count($value);
        switch ($count) {
            case 6:
                $k = $key;
                break;
            case 5:
                array_unshift($servers[$key], $servers[$k][0]);
                break;
            case 4:
                array_unshift($servers[$key], $servers[$k][1]);
                array_unshift($servers[$key], $servers[$k][0]);
                break;
            default:
                // do noting
                break;
        }
    }

    $servers_stroge = [
        'data' => $servers,
        'expires' => time() + 3600 * 24 * 7,
    ];

    file_put_contents('servers.txt', json_encode($servers_stroge));

} else {
    $servers = $servers['data'];
}

foreach ($servers as $server) {
    exec('ping -c 3 ' . $server[2], $result);

    // for windows
    //$result = explode('= ', $result[10]);
    // preg_match_all('/\d+/', $result[5], $arr);
    // print_r($result);
    // print_r($arr);
    //echo $server[1].' '.$server[2].' '.$result[10],PHP_EOL;

    // for mac
    preg_match_all('/[1-9]\d*\.\d{3}/', end($result), $arr);

    echo $server[1] . '--' . $server[4] . '--' . $server[2] . '--' . $arr[0][1] . PHP_EOL;

    if (isset($arr[0][1])) {
        $avg_array[] = $arr[0][1];
    } else {
        $avg_array[] = '无法连接';
    }

    if (min($avg_array) == $arr[0][1]) {
        $fastest_vpn_name = '云梯 ' . $server[1] . ' ' . $server[4];
        $fastest_vpn_info = '延迟最低的线路为：' . $fastest_vpn_name . ' AVG: ' . $arr[0][1];
    }
}

$scpt_code_templete = [
    'tell application "System Events"',
    'tell current location of network preferences',
    'set VPNservice to service "' . $fastest_vpn_name . '" -- name of the VPN service',
    'if exists VPNservice then connect VPNservice',
    'end tell',
    'end tell',
];
$scpt_code = 'osascript';
foreach ($scpt_code_templete as $key => $value) {
    $scpt_code .= ' -e \'' . $value . '\'';
}

echo colorize($fastest_vpn_info . PHP_EOL, "SUCCESS");

echo '正在连接' . $fastest_vpn_name . PHP_EOL;

exec($scpt_code, $result);

echo colorize('完成' . PHP_EOL, "SUCCESS");

// 测速
// ping google.com
// down file

