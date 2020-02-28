<?php
class Enc {
    static public function get_keys() {
        $config = array(
        "private_key_type"=>OPENSSL_KEYTYPE_RSA,
        "private_key_bits"=>512
        );
        
        $res = openssl_pkey_new($config);
        $privKey = '';
        openssl_pkey_export($res,$privKey);
        
        $arr = array(
            "countryName" => "UA",
            "stateOrProvinceName" => "Zaporizhzhya region",
            "localityName" => "Zaporizhia",
            "organizationName" => "Organization",
            "organizationalUnitName" => "Soft",
            "commonName" => "localhost",
            "emailAddress" => "afonin.it@gmail.com"
        );
        $csr = openssl_csr_new($arr,$privKey);
        
        $cert = openssl_csr_sign($csr,NULL, $privKey,10);
        openssl_x509_export($cert,$str_cert);
        
        $public_key = openssl_pkey_get_public($str_cert);
        $public_key_details = openssl_pkey_get_details($public_key);
        
        $public_key_string = $public_key_details['key'];
        
        return array('private'=>$privKey,'public'=>$public_key_string);
    }
        
    
    static public function encrypt($public_key, $str) {
        openssl_public_encrypt($str,$result,$public_key);
        return $result;
    }

    static public function decrypt($private_key, $str) {
        openssl_private_decrypt($str,$result,$private_key);
        return $result;
    }
}