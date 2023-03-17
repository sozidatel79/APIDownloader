<?php

class Curl {
    private $ch;
    private $response;
    private $headers = [];
    private $params = [];

    public function __construct() {
        $this->ch = curl_init();
        curl_setopt($this->ch, CURLOPT_RETURNTRANSFER, true);
    }

    /**
     * @param string $url
     * @param bool $verify_ssl
     * @return $this|string
     */
    public function get(string $url, bool $verify_ssl = false) {
        curl_setopt($this->ch, CURLOPT_URL, $url . '?' . http_build_query($this->getParams()));
        curl_setopt($this->ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($this->ch, CURLOPT_HTTPHEADER, $this->getHeaders());
        curl_setopt($this->ch, CURLOPT_SSL_VERIFYPEER, $verify_ssl);
        curl_setopt($this->ch, CURLOPT_TIMEOUT, 10000);
        $this->response = curl_exec($this->ch);

        if (!$this->response) {
            return curl_error($this->ch);
        }

        return $this->response;
    }

    /**
     * @param string $url
     * @param bool $verify_ssl
     * @return $this|string
     */
    public function post(string $url, bool $verify_ssl = false) {
        curl_setopt($this->ch, CURLOPT_URL, $url);
        curl_setopt($this->ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($this->ch, CURLOPT_HTTPHEADER, $this->getHeaders());
        curl_setopt($this->ch, CURLOPT_SSL_VERIFYPEER, $verify_ssl);
        curl_setopt($this->ch, CURLOPT_POST, true);
        curl_setopt($this->ch, CURLOPT_POSTFIELDS, $this->getParams());
        curl_setopt($this->ch, CURLOPT_TIMEOUT, 10000);
        $this->response = curl_exec($this->ch);

        if (!$this->response) {
            return curl_error($this->ch);
        }

        return $this->response;
    }

    public function setHeaders(array $headers = []): self {
        $this->headers = $headers;
        return $this;
    }

    public function getHeaders(): array {
        return $this->headers;
    }

    public function setParams(array $params = []): self {
        $this->params = $params;
        return $this;
    }

    public function getParams(): array {
        return $this->params;
    }

    public function close() {
        curl_close($this->ch);
    }
}