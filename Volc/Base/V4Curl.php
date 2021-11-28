<?php

/**
 * creator: maigohuang
 */

namespace App\Plugins\ImageX\Volc\Base;

use Exception;
use GuzzleHttp\Client;

use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Message\MessageFactory;
use GuzzleHttp\Message\Request;
use GuzzleHttp\Query;

abstract class V4Curl extends Singleton
{
    protected $client = null;
    protected $stack = null;
    protected $region = '';
    protected $ak = '';
    protected $sk = '';


    public function __construct()
    {
        parent::__construct();

        $this->region = func_get_arg(0);

        $messageFactory = new MessageFactory([
            'v4_credentials' => function ($request, $value) {
                $v4 = new SignatureV4();
                $credentials = $this->prepareCredentials($value);
                $v4->signRequest($request, $credentials);
            }
        ]);

        $config = $this->getConfig($this->region);
        $this->client = new Client([
            'message_factory' => $messageFactory,
//            'handler' => function($request) {
//                return;
//            },
            'base_url' => $config['host'],
        ]);

        $this->version = trim(file_get_contents(__DIR__ . '/../../VERSION'));
    }

    public function setAccessKey($ak)
    {
        if ($ak != "") {
            $this->ak = $ak;
        }
    }

    public function setHost($host)
    {
        if ($host != "") {
            $this->client = new Client([
                'handler' => $this->stack,
                'base_url' => $host,
            ]);
        }
    }

    public function setSecretKey($sk)
    {
        if ($sk != "") {
            $this->sk = $sk;
        }
    }

    abstract protected function getConfig($region);

    private function prepareCredentials(array $credentials)
    {
        if (!isset($credentials['ak']) || !isset($credentials['sk'])) {
            if ($this->ak != "" && $this->sk != "") {
                $credentials['ak'] = $this->ak;
                $credentials['sk'] = $this->sk;
            } elseif (getenv("VOLC_ACCESSKEY") != "" && getenv("VOLC_SECRETKEY") != "") {
                $credentials['ak'] = getenv("VOLC_ACCESSKEY");
                $credentials['sk'] = getenv("VOLC_SECRETKEY");
            } else {
                $json = json_decode(file_get_contents(getenv('HOME') . '/.volc/config'), true);
                if (is_array($json) && isset($json['ak']) && isset($json['sk'])) {
                    $credentials = array_merge($credentials, $json);
                }
            }
        }
        return $credentials;
    }

    public function getRequestUrl($api, array $config = [])
    {
        $config_api = isset($this->apiList[$api]) ? $this->apiList[$api] : false;

        $defaultConfig = $this->getConfig($this->region);
        $config = $this->configMerge($defaultConfig['config'], $config_api['config'], $config);
        $info = array_merge($defaultConfig, $config_api);

        $method = $info['method'];
        $request = new Request($method, $info['host'] . $info['url'] . '?' . http_build_query($config['query']));

        $credentials = $this->prepareCredentials($config['v4_credentials']);
        $v4 = new SignatureV4();

        return $v4->signRequestToUrl($request, $credentials);
    }


    public function request($api, array $config = [])
    {
        if (is_string($config['query'])) {
            $config['query'] = Query::fromString($config['query'])->toArray();
        }

        $config_api = isset($this->apiList[$api]) ? $this->apiList[$api] : false;
        $defaultConfig = $this->getConfig($this->region);
        $config = $this->configMerge($defaultConfig['config'], $config_api['config'], $config);
        $config['headers']['User-Agent'] = 'volc-sdk-php/' . $this->version;

        $info = array_merge($defaultConfig, $config_api);
        $info['config'] = $config;

        $v4_credentials = $config['v4_credentials'];
        unset($config['v4_credentials']);
        $config['v4_credentials'] = $v4_credentials;

        $method = $info['method'];
        try {
            $request = $this->client->createRequest($method, $info['url'], $config);
            $response = $this->client->send($request);
            return $response;
        } catch (ClientException $exception) {
            return $exception->getResponse();
        }
    }

    /**
     * @param array $policy
     * @param int $expire
     * @return array
     */
    public function signSts2($policy, $expire)
    {
        // 获取长期的aksk信息
        $credentials = $this->prepareCredentials([]);
        $now = time();

        $sts = [
            "AccessKeyID" => $this->generateAccessKeyId("AKTP"),
            "SecretAccessKey" => $this->generateSecretKey(),
            "ExpiredTime" => date('Y-m-d\TH:i:sP', $now + $expire),
            "CurrentTime" => date('Y-m-d\TH:i:sP', $now),
        ];

        $innerToken = $this->createInnerToken($credentials, $sts, $policy, $now + $expire);
        $sts["SessionToken"] = "STS2" . base64_encode(json_encode($innerToken));

        return $sts;
    }

    /**
     * @param array $credentials
     * @param array $sts
     * @param array $policy
     * @param int $expire
     * @return array
     */
    private function createInnerToken($credentials, $sts, $policy, $expire)
    {
        $inner = [
            "LTAccessKeyId" => $credentials["ak"],
            "AccessKeyId" => $sts["AccessKeyID"],
            "ExpiredTime" => $expire,
        ];

        $key = md5($credentials["sk"], true);
        $inner["SignedSecretAccessKey"] = $this->aesEncrypt($sts["SecretAccessKey"], $key);

        if (sizeof($policy) > 0) {
            $inner["PolicyString"] = json_encode($policy);
        } else {
            $inner["PolicyString"] = "";
        }

        $signStr = sprintf("%s|%s|%d|%s|%s",
            $inner["LTAccessKeyId"],
            $inner["AccessKeyId"],
            $inner["ExpiredTime"],
            $inner["SignedSecretAccessKey"],
            $inner["PolicyString"]);

        $inner["Signature"] = hash_hmac('sha256', $signStr, $key);

        return $inner;
    }

    public function newAllowStatement(array $actions, array $resources)
    {
        return [
            "Effect" => "Allow",
            "Action" => $actions,
            "Resource" => $resources,
        ];
    }

    public function newDenyStatement(array $actions, array $resources)
    {
        return [
            "Effect" => "Deny",
            "Action" => $actions,
            "Resource" => $resources,
        ];
    }

    protected function configMerge($c1, $c2, $c3)
    {
        $result = $c1;
        foreach ($c2 as $k => $v) {
            if (isset($result[$k]) && is_array($result[$k]) && is_array($v)) {
                $result[$k] = array_merge($result[$k], $v);
            } else {
                $result[$k] = $v;
            }
        }

        foreach ($c3 as $k => $v) {
            if (isset($result[$k]) && is_array($result[$k]) && is_array($v)) {
                $result[$k] = array_merge($result[$k], $v);
            } else {
                $result[$k] = $v;
            }
        }
        return $result;
    }

    protected function generateAccessKeyId($prefix)
    {
        // 随机128bit,转化成16进制后再base64编码
        $accessKeyId = $prefix . base64_encode(bin2hex(random_bytes(16)));

        $accessKeyId = str_replace("=", "", $accessKeyId);
        $accessKeyId = str_replace("/", "", $accessKeyId);
        $accessKeyId = str_replace("+", "", $accessKeyId);
        $accessKeyId = str_replace("-", "", $accessKeyId);
        return $accessKeyId;
    }

    protected function generateSecretKey()
    {
        return base64_encode(bin2hex(random_bytes(30)));
    }

    protected function aesEncrypt($src, $pwd)
    {
        return base64_encode(openssl_encrypt($src, "AES-128-CBC", $pwd, OPENSSL_RAW_DATA | OPENSSL_ZERO_PADDING, $pwd));
    }

    /**
     * @param string $authAlgorithm
     * @param int $expireSeconds
     * @return mixed
     * @throws Exception
     */
    protected function createHlsDrmAuthToken($authAlgorithm, $expireSeconds)
    {
        if ($expireSeconds == 0) {
            throw new Exception("invalid exception");
        }
        $credentials = $this->prepareCredentials($this->getConfig($this->region));
        try {
            $token = $this->createAuth($authAlgorithm, "2.0", $credentials['ak'], $credentials['sk'], $this->region, $expireSeconds);
            $query = array(
                "DrmAuthToken" => $token,
                "X-Expires" => strval($expireSeconds),
            );
            return parse_url($this->getRequestUrl("GetHlsDecryptionKey", ['query' => $query]))['query'];
        } catch (Exception $e) {
            throw $e;
        }
    }


    /**
     * @param string $dsa
     * @param string $version
     * @param string $accessKey
     * @param string $secretKey
     * @param $region
     * @param int $expireSeconds
     * @return string
     * @throws Exception
     */
    private function createAuth($dsa, $version, $accessKey, $secretKey, $region, $expireSeconds)
    {
        if ($accessKey == "") {
            throw new Exception("invalid accessKey");
        }
        if ($secretKey == "") {
            throw new Exception("invalid secretKey");
        }
        $timestamp = time() + $expireSeconds;
        $deadline = gmdate("Ymd\THis\Z", $timestamp);
        $kDate = hash_hmac('sha256', $deadline, $secretKey, true);
        $kRegion = hash_hmac('sha256', $region, $kDate, true);
        $kService = hash_hmac('sha256', "vod", $kRegion, true);
        $kCredentials = hash_hmac('sha256', "request", $kService, true);
        $dateKey = bin2hex($kCredentials);
        $data = $dsa . "&" . $version . "&" . $timestamp;
        switch ($dsa) {
            case "HMAC-SHA1":
                $sign = base64_encode(hash_hmac('sha1', $data, $dateKey, true));
                break;
            default:
                throw new Exception("invalid dsa");
        }
        return $dsa . ":" . $version . ":" . $timestamp . ":" . $accessKey . ":" . $sign;
    }
}
