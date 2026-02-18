<?php

class BusinessCentralApi {
    private $clientId;
    private $clientSecret;
    private $scope;
    private $tenantId;
    private $baseUrl;
    private $accessToken;

    public function __construct($clientId, $clientSecret, $scope, $tenantId, $environment = 'ROFullTest', $apiPath = 'api/eos/guaa/v2.0') {
        $this->clientId = $clientId;
        $this->clientSecret = $clientSecret;
        $this->scope = $scope;
        $this->tenantId = $tenantId;
        $this->baseUrl = "https://api.businesscentral.dynamics.com/v2.0/{$tenantId}/{$environment}/{$apiPath}";
    }

    private function authenticate() {
        $url = "https://login.microsoftonline.com/{$this->tenantId}/oauth2/v2.0/token";

        $data = [
            'client_id' => $this->clientId,
            'client_secret' => $this->clientSecret,
            'scope' => $this->scope,
            'grant_type' => 'client_credentials'
        ];

        $curl = curl_init($url);
        curl_setopt_array($curl, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => http_build_query($data, '', '&', PHP_QUERY_RFC3986),
            CURLOPT_HTTPHEADER => [
                "Content-Type: application/x-www-form-urlencoded"
            ],
            CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_SSL_VERIFYHOST => 0,
        ]);

        $response = curl_exec($curl);
        curl_close($curl);

        $decoded = json_decode($response, true);

        if (isset($decoded['access_token'])) {
            $this->accessToken = $decoded['access_token'];
        } else {
            throw new Exception("Errore autenticazione: " . ($decoded['error_description'] ?? 'Token non ottenuto'));
        }
    }

    public function get($endpoint) {
        if (!$this->accessToken) {
            $this->authenticate();
        }

        //$url = "https://api.businesscentral.dynamics.com/v2.0/acb6aa33-e9bf-4632-8118-5e4ad89beea4/ROFullTest/api/eos/guaa/v2.0/companies(a16f81ea-4219-ee11-9cc3-6045bdaccbcb)/guaItemsInProduction";
        
        $headers = [
            "Authorization: Bearer {$this->accessToken}",
            "Content-Type: application/json"
        ];

        $curl = curl_init($endpoint);
        curl_setopt_array($curl, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => $headers
        ]);

        $response = curl_exec($curl);

        if (curl_errno($curl)) {
            throw new Exception("Errore cURL: " . curl_error($curl));
        }

        curl_close($curl);
        return json_decode($response, true);
    }
}
