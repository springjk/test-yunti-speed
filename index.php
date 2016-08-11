<?php
require __DIR__ . '/vendor/autoload.php';

use GuzzleHttp\Client;
use Sunra\PhpSimple\HtmlDomParser;

class YunTi
{
    // /Library/Preferences/SystemConfiguration/preferences.plist
    const SITE_URL = 'https://www.cryptpline.com';
    const LOGIN_PATH = '/users/sign_in';
    const SERVERS_PATH = '/admin/servers';

    private $parser;
    private $client;
    private $token;
    private $servers;

    public function __construct()
    {
        $this->parser = new HtmlDomParser();

        $options = [
            'base_uri' => self::SITE_URL,
            'timeout' => 15,
            'cookies' => true
            // 'debug' => true
        ];

        $this->client = new Client($options);

        date_default_timezone_set('Asia/Shanghai');
    }

    public function run()
    {

        if (is_file('servers.txt')) {
            $text = file_get_contents('servers.txt', 'r') or die('Unable to open file!');

            $this->servers = json_decode($text, true);

        } else {

            if (is_file('auth.txt')) {
                $text = file_get_contents('auth.txt', 'r') or die('Unable to open file!');

                $auth = json_decode($text, true);
            } else {
                $auth = $this->inputAuth();
            }

            $this->saveAuth($auth);

            $this->login($auth['username'], $auth['password']);

            $this->servers = $this->getServers();

            $this->storageServers($this->servers);
        }

        $this->pingTest($this->servers);

    }

    public function getCsrfToken()
    {
        $result = $this->client->get(self::LOGIN_PATH);

        $html = $result->getBody();

        $html_dom = $this->parser->str_get_html($html);

        $token = $html_dom->find('meta[name=csrf-token]', 0)->getAttribute('content');

        if (empty($token)) {
            throw new \Exception('Get csrf token error');
        }

        $this->token = $token;

        return $token;
    }

    private function login($username, $password)
    {
        $this->getCsrfToken();

        $result = $this->client->post(self::LOGIN_PATH, [
            'form_params' => [
                'user[login]' => $username,
                'user[password]' => $password,
                'user[remember_me]' => 1,
                'authenticity_token' => $this->token,
                'commit' => '登录',
                'utf8' => '✓',
            ]
        ]);

        $html = $result->getBody();

        $html_dom = $this->parser->str_get_html($html);

        $login_info = $html_dom->find('div.alert-success, div.alert-danger', 0)->plaintext;

        # cut utf-8 chinese
        preg_match_all('/[\x{4e00}-\x{9fa5}]+/u', $login_info, $matches);

        $login_info = $matches[0][0];

        if ($login_info == '登录成功') {
            return true;
        } else {
            throw new \Exception($login_info);
        }
    }

    private function getServers()
    {

        $result = $this->client->get(self::SERVERS_PATH);

        $html =  $result->getBody();

        $html_dom = $this->parser->str_get_html($html);

        $servers = [];

        foreach ($html_dom->find('table tr') as $key => $tr) {
            # ignore th
            if ($key != 0) {
                $name = trim($tr->find('td[rowspan=2]', -1)->plaintext);

                if (empty($name)) {
                    $name = $servers[$key-2]['name'];
                }

                $ip = trim($tr->find('td', -4)->plaintext);

                $type = trim($tr->find('td', -2)->plaintext);

                $servers[] = compact('name', 'ip', 'type');
            }
        }
        return $servers;
    }

    public function storageServers(array $servers)
    {
        $file = fopen('servers.txt', 'w') or die('Unable to write file!' . PHP_EOL);

        fwrite($file, json_encode($servers));

        fclose($file);
    }

    public function inputAuth()
    {
        do{
            fwrite(STDOUT, '请输入您的云梯登陆账号：' . PHP_EOL);
            $username = trim(fgets(STDIN));

            if ($username) {
                fwrite(STDOUT, '请输入您的云梯登陆密码：' . PHP_EOL);
                $password = trim(fgets(STDIN));
            } else {
                fwrite(STDOUT, '抱歉，账号不能为空，请重新输入：' . PHP_EOL);
            }

            $auth = compact('username', 'password');
        }while(!$auth);

        return $auth;
    }

    public function saveAuth(array $auth)
    {
        $file = fopen('auth.txt', 'w') or die('Unable to write file!' . PHP_EOL);

        fwrite($file, json_encode($auth));

        fclose($file);
    }

    public function pingTest($servers)
    {

        foreach ($servers as $server) {
            exec('ping -c 3 ' . $server['ip'] . ' &', $result);

            // for windows
            //$result = explode('= ', $result[10]);
            // preg_match_all('/\d+/', $result[5], $arr);
            // print_r($result);
            // print_r($arr);
            //echo $server[1].' '.$server[2].' '.$result[10],PHP_EOL;

            // for mac
            preg_match_all('/[1-9]\d*\.\d{3}/', end($result), $arr);

            echo $server['name'] . '--' . $server['type'] . '--' . $server['ip'] . '--' . $arr[0][1] . PHP_EOL;

            if (isset($arr[0][1])) {
                $avg_array[] = $arr[0][1];
            } else {
                $avg_array[] = '无法连接';
            }

            if (min($avg_array) == $arr[0][1]) {
                $fastest_vpn_name = '云梯 ' . $server['name'] . ' ' . $server['type'];
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

        echo $this->colorize($fastest_vpn_info . PHP_EOL, "SUCCESS");

        echo '正在连接' . $fastest_vpn_name . PHP_EOL;

        exec($scpt_code, $result);

        echo $this->colorize('完成' . PHP_EOL, "SUCCESS");

        // 测速
        // ping google.com
        // down file

    }


    public function colorize($text, $status)
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
}

$yunti = new YunTi();

$yunti->run();

// 测速
// ping google.com
// down file
