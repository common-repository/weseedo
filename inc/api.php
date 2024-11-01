<?php

class WeSeeDoApi {

    protected $apiEndpoint = "https://signaling.webrtcoplossingen.nl";

    protected $token;

    public function getToken()
    {
        $endpoint = $this->apiEndpoint."/api/oauth2/token";

        $params = array(
            "grant_type" => "client_credentials",
            "client_id" => get_option('weseedo_api_client'),
            "client_secret" => get_option('weseedo_api_secret'));

        $curl = curl_init($endpoint);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_HEADER,'Content-Type: application/x-www-form-urlencoded');
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);

        $postData = "";
        foreach($params as $k => $v)
        {
            $postData .= $k . '='.urlencode($v).'&';
        }

        $postData = rtrim($postData, '&');

        curl_setopt($curl, CURLOPT_POSTFIELDS, $postData);

        $json_response = curl_exec($curl);

        $status = curl_getinfo($curl, CURLINFO_HTTP_CODE);

        // evaluate for success response
        if ($status != 200) {
            return false;
        }
        $response = json_decode($json_response);
        curl_close($curl);

        $this->token = $response->access_token;
    }

    public function getAccounts()
    {
        if (!$this->token) {
            // try to get token
            $this->getToken();
        }

        if (!$this->token) {
            // check token again
            return false;
        }
        echo "Call get Accounts<br>";
        $authorization = "Authorization: Bearer ".$this->token;

        $url = $this->apiEndpoint."/api/accounts";
        $ch = curl_init($url);

        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "GET");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSLVERSION,4);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);

        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                $authorization,
                'Content-Type: application/json',
            )
        );
        $json = curl_exec($ch);
        curl_close($ch);

        return json_decode($json);

    }

    public function getStatus($account)
    {
        if (!$this->token) {
            // try to get token
            $this->getToken();
        }

        if (!$this->token) {
            // check token again
            return "offline";
        }

        $authorization = "Authorization: Bearer ".$this->token;


        // determine account based on id reference
        $url = $this->apiEndpoint."/api/accounts/".$account;
        $ch = curl_init($url);

        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "GET");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        curl_setopt($ch, CURLOPT_SSLVERSION,4);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);

        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                $authorization,
                'Content-Type: application/json',
            )
        );
        $json = curl_exec($ch);
        curl_close($ch);

        $account = json_decode($json);

        if ($account->users) {
            foreach ($account->users as $user) {
                if (property_exists($user,"status") && $user->status == "online") return "online";
            }
        }

        return "offline";
    }


}
