<?php

class IcerikmatikApi {

    /**
     * API base
     *
     * @var string
     */
    private $baseUri = 'https://panel.icerikmatik.com/api';

    /**
     * API access key
     *
     * @var string
     */
    private $apiToken = '';

    /**
     * API methods
     *
     * @var array
     */
    private $methods = [

    ];

    public function __construct($apiToken = '') {
        $this->apiToken = $apiToken;
    }

    /**
     * Make api request
     *
     * @param string $uri
     * @param array $params request parameters
     * @return stdClass
     */
    public function getRequest($uri, array $params = [], $post = true) {
        if (!isset($params['api_token']))
            $params['api_token'] = $this->apiToken;

        if (!$params['api_token'])
            return false;

        $ch = curl_init();

        $url = "{$this->baseUri}/{$uri}";
        $curlParams = [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => false
        ];

        if (true === $post) {
            $curlParams[CURLOPT_POST] = true;
            $curlParams[CURLOPT_POSTFIELDS] = $params;
        } else {
            $url .= '?' . http_build_query($params);
        }

        $curlParams[CURLOPT_URL] = $url;
        curl_setopt_array($ch, $curlParams);

        $response = curl_exec($ch);
        $info = curl_getinfo($ch);
        curl_close($ch);

        if (200 == $info['http_code'])
            return json_decode($response) ? : $response;

        return (object) ['status' => 'error', 'message' => $response];
    }

    public function getRequestUri($uri, array $params = [], $post = true) {
        if (!isset($params['api_token']))
            $params['api_token'] = $this->apiToken;

        if (!$params['api_token'])
            return false;

        return "{$this->baseUri}/{$uri}" . (true === $post ? '' : ('?' . http_build_query($params)));
    }

    public function __call($name, $args) {
        switch ($name) {
            case 'projects':
                $uri = 'projects';
                break;
            case 'posts':
                $uri = 'posts';
                break;
            case 'postTitles':
                $uri = 'post-titles';
                break;
            default:
                echo 'Wrong api method';
                exit;
        }

        return call_user_func([$this, 'getRequest'], $args);
    }
}
