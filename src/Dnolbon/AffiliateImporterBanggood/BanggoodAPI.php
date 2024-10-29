<?php
namespace Dnolbon\AffiliateImporterBanggood;

class BanggoodAPI
{

    private $appId = '';
    private $appSecret = '';
    private $domain = 'https://api.banggood.com/';

    private $accessToken = '';
    private $tokenCacheFile = '';

    private $task = '';
    private $method = 'GET';
    private $params = [];
    private $lang = 'en';
    private $currency = 'USD';

    private $waitingTaskInfo = array();
    private $ch = null;
    private $curlExpireTime = 10;

    /**
     * @desc Construct
     * @access public
     * @param $appId
     * @param $appSecret
     */
    public function __construct($appId, $appSecret)
    {
        $this->appId = $appId;
        $this->appSecret = $appSecret;
        $this->ch = curl_init();
        $this->tokenCacheFile = __DIR__ . '/banggoodAPI.token.php';
    }

    /**
     * @desc category/getCategoryList
     * @access public
     */
    public function getCategoryList()
    {
        $this->task = 'category/getCategoryList';
        $this->method = 'GET';
        $result = $this->__doRequest();

        return $result;
    }

    /**
     * @desc send api request
     * @access private
     */
    private function __doRequest()
    {

        if (empty($this->params)) {
            $this->__requestError(array('params is empty'));
        }

        if ($this->task != 'getAccessToken') {
            if (empty($this->accessToken)) {
                $this->__getAccessToken();
            }
            $this->params['access_token'] = $this->accessToken;

            if (empty($this->params['lang'])) {
                $this->params['lang'] = $this->lang;
            }

            if (empty($this->params['currency'])) {
                $this->params['currency'] = $this->currency;
            }
        }

        $apiUrl = $this->domain . $this->task;

        if ($this->method == 'GET') {
            $quote = '?';
            foreach ($this->params as $k => $v) {
                $apiUrl .= $quote . $k . '=' . $v;
                $quote = '&';
            }
        }

        curl_setopt($this->ch, CURLOPT_URL, $apiUrl);
        curl_setopt($this->ch, CURLOPT_HEADER, 0);
        curl_setopt($this->ch, CURLOPT_USERAGENT, $_SERVER ['HTTP_USER_AGENT']);
        curl_setopt($this->ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($this->ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($this->ch, CURLOPT_SSL_VERIFYPEER, false);

        if ($this->method == 'POST') {
            curl_setopt($this->ch, CURLOPT_POST, 1);
            curl_setopt($this->ch, CURLOPT_POSTFIELDS, http_build_query($this->params));
        }

        if ($this->curlExpireTime > 0) {
            curl_setopt($this->ch, CURLOPT_TIMEOUT, $this->curlExpireTime);
        }

        $result = curl_exec($this->ch);
        $result = json_decode($result, true);

        // access_token expired ,get new access_token
        if ($result['code'] == 21020) {
            $this->accessToken = null;
            $result = $this->__getAccessToken(false);
        }

        return $result;
    }

    /**
     * @desc handle request error
     * @access private
     * @param $error
     */
    private function __requestError($error)
    {
        var_dump($error);
        exit;
    }

    /**
     * @desc get access_token
     * @access private
     * @param bool $useCache
     * @return array|mixed|object
     */
    private function __getAccessToken($useCache = true)
    {

        //get accessToken from cache
        if (file_exists($this->tokenCacheFile) && $useCache == true) {
            $accessTokenArr = @include($this->tokenCacheFile);
            if ($accessTokenArr['expireTime'] > $_SERVER['REQUEST_TIME']) {
                $this->accessToken = $accessTokenArr['accessToken'];
            }
        }

        //if access_token is empty, send request to get accessToken
        if (empty($this->accessToken)) {
            if (!empty($this->task)) {
                $this->waitingTaskInfo = array(
                    'task' => $this->task,
                    'method' => $this->method,
                    'params' => $this->params,
                );
            }

            $this->task = 'getAccessToken';
            $this->params = array('app_id' => $this->appId, 'app_secret' => $this->appSecret);
            $this->method = 'GET';

            $result = $this->__doRequest();
            if ($result['code'] == 0) {
                $expireTime = $_SERVER['REQUEST_TIME'] + $result['expires_in'];
                $accessTokenArr = array(
                    'accessToken' => $result['access_token'],
                    'expireTime' => $expireTime,
                    'expireDateTime' => date('Y-m-d H:i:s', $expireTime),
                );

                $cacheStr = "<?php \r\n";
                $cacheStr .= 'return ' . var_export($accessTokenArr, true) . ';';

                $fp = fopen($this->tokenCacheFile, 'wb+');
                fwrite($fp, $cacheStr);
                fclose($fp);

                $this->accessToken = $result['access_token'];

                //resend request
                if (!empty($this->waitingTaskInfo)) {
                    $this->task = $this->waitingTaskInfo['task'];
                    $this->params = $this->waitingTaskInfo['params'];
                    $this->method = $this->waitingTaskInfo['method'];

                    $this->waitingTaskInfo = array();
                    return $this->__doRequest();
                }
            } else {
                $this->__requestError($result);
            }
        }
        return null;
    }

    /**
     * @desc product/getProductInfo
     * @access public
     */
    public function getProductInfo()
    {
        $this->task = 'product/getProductInfo';
        $this->method = 'GET';
        $result = $this->__doRequest();

        return $result;
    }

    /**
     * @desc product/getProductList
     * @access public
     */
    public function getProductList()
    {
        $this->task = 'product/getProductList';
        $this->method = 'GET';
        $result = $this->__doRequest();

        return $result;
    }

    /**
     * @desc product/getProductStock
     * @access public
     */
    public function getProductStock()
    {
        $this->task = 'product/getProductStock';
        $this->method = 'GET';
        $result = $this->__doRequest();

        return $result;
    }

    /**
     * @desc product/getShipments
     * @access public
     */
    public function getShipments()
    {
        $this->task = 'product/getShipments';
        $this->method = 'GET';
        $result = $this->__doRequest();

        return $result;
    }

    /**
     * @desc order/importOrder
     * @access public
     */
    public function importOrder()
    {
        $this->task = 'order/importOrder';
        $this->method = 'POST';
        $result = $this->__doRequest();

        return $result;
    }

    /**
     * @desc order/getOrderInfo
     * @access public
     */
    public function getOrderInfo()
    {
        $this->task = 'order/getOrderInfo';
        $this->method = 'GET';
        $result = $this->__doRequest();

        return $result;
    }

    /**
     * @desc order/getOrderHistory
     * @access public
     */
    public function getOrderHistory()
    {
        $this->task = 'order/getOrderHistory';
        $this->method = 'GET';
        $result = $this->__doRequest();

        return $result;
    }

    /**
     * @desc order/getTrackInfo
     * @access public
     */
    public function getTrackInfo()
    {
        $this->task = 'order/getTrackInfo';
        $this->method = 'GET';
        $result = $this->__doRequest();

        return $result;
    }

    /**
     * @desc set params
     * @access public
     * @param array $params
     */
    public function setParams(array $params)
    {
        if (!empty($params)) {
            $this->params = $params;
        }
    }
}
