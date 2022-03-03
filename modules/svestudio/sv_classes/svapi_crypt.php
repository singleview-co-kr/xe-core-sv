<?php
class singleviewApiOpenSsl
{
	// php openssl과 호환하며, php와 python 보안 통신에 사용할 수 있음
	// https://github.com/arajapandi/php-python-encrypt-decrypt
	private $key = null; //'asdfa923aksadsYahoasdw998sdsads';
    private $iv = null;
    private $method = "AES-256-CFB";
    private $blocksize = 32;
    private $padwith = '`';

/**
 * @brief
 */
	public function __construct()
	{
		$this->_getConfigFile();
	}
/**
 * Get config file
 * @retrun string The path of the config file that contains database settings
 */
	private function _getConfigFile()
	{
		$sSecretKeyPath = _XE_PATH_.'files/svestudio/key.config.php';
		if(is_readable($sSecretKeyPath))
			require_once($sSecretKeyPath);

		$this->key = $aSecretConfig['sv_secret_key'];
		$this->iv = $aSecretConfig['sv_iv'];
	}
/**
 * @brief
 */
	public function translateMsgCode($sDecryptedSource)
	{
		if( is_null( $sDecryptedSource ) )
			return;
		$sDecrypted = $this->_decryptData($sDecryptedSource );
		//$sDecrypted = rtrim($sDecrypted,$this->_g_sPadding);
		return json_decode( $sDecrypted );
	}
/*
 * get hased key - if key is not set on init, then default key wil be used
 */
    private function __getKEY() 
	{
        if(empty($this->key)) 
            die('Key not set!');
        return substr(hash('sha256', $this->key), 0, 32);
    }
/*
 * get hashed IV value - if no IV values then it throw error
 */
    private function __getIV() 
	{
        if (empty($this->iv)) 
            die('IV not set!');
        return substr(hash('sha256', $this->iv), 0, 16);
    }
/*
 * Encrypt given string using AES encryption standard
 */
    public function encryptData($secret) 
	{
		$secret = json_encode( $secret );
		try 
		{
			$padded_secret = $secret . str_repeat($this->padwith, ($this->blocksize - strlen($secret) % $this->blocksize));
			$encrypted_string = openssl_encrypt($padded_secret, $this->method, $this->__getKEY(), OPENSSL_RAW_DATA, $this->__getIV());
            $encrypted_secret = base64_encode($encrypted_string);
            return $encrypted_secret;
        } 
		catch (Exception $e) 
		{
            die('Error : ' . $e->getMessage());
        }
    }
/*
 * Decrypt given string using AES standard
 */
    public function _decryptData($secret) 
	{
        try 
		{
            $decoded_secret = base64_decode($secret);
            $decrypted_secret = openssl_decrypt($decoded_secret, $this->method, $this->__getKEY(), OPENSSL_RAW_DATA, $this->__getIV());
            return rtrim($decrypted_secret, $this->padwith);
        }
		catch (Exception $e) 
		{
            die('Error : ' . $e->getMessage());
        }
    }
}

class singleviewApiMcrypt
{
	// php mcrypt와 호환하지만 php 7.2부터 mcrypt 폐기됨
	// php 내부 암호화에만 사용할 수 있음
	var $_g_sSecret = null; //"332SECRETabc1234"; // secret key
	var $_g_sIv = null; //"HELLOWORLD123456";  // initialization vector
	var $_g_sPadding = "{";  //same padding as python
/**
 * @brief
 */
	public function __construct()
	{
		$this->_getConfigFile();
	}
/**
 * Get config file
 * @retrun string The path of the config file that contains database settings
 */
	private function _getConfigFile()
	{
		$sSecretKeyPath = _XE_PATH_.'files/svestudio/key.config.php';
		if(is_readable($sSecretKeyPath))
			require_once($sSecretKeyPath);

		$this->_g_sSecret = $aSecretConfig['sv_secret_key'];
		$this->_g_sIv = $aSecretConfig['sv_iv'];
	}
/**
 * @brief
 */
	public function translateMsgCode($sDecryptedSource)
	{
		if( is_null( $sDecryptedSource ) )
			return;
		$sDecrypted = $this->_decryptData($sDecryptedSource );
		$sDecrypted = rtrim($sDecrypted,$this->_g_sPadding);
		return json_decode( $sDecrypted );
	}
/**
 * @brief contruct json object from python
 * object(stdClass)#142 (2) { ["task"]=> array(1) { [0]=> string(13) "getNpayReview" } ["start_ymd"]=> array(1) { [0]=> string(8) "20191011" } }
 */
	public function buildQueryArray($oReceivedParams)
	{
//		if( is_null( $oReceivedParams ) )
//			return [];
		$aParams = [];
		foreach( $oReceivedParams as $sKey => $aVal ) 
		{
			$aParams[$sKey] = $aVal[0];
//var_dump( $sKey );
//echo '<BR>';
//var_dump( $aVal[0] );
//echo '<BR>';
		}
		return $aParams;
	}
/**
 * @brief
 */
	public function encryptData($data)
	{
		$data = json_encode( $data );
		$key = $this->_g_sSecret;
		$iv = $this->_g_sIv;
		$cypher = mcrypt_module_open(MCRYPT_RIJNDAEL_128, '', MCRYPT_MODE_CBC, '');
		if(is_null($iv)) 
		{
			$ivlen = mcrypt_enc_get_iv_size($cypher);
			$iv = substr($data, 0, $ivlen);
			$data = substr($data, $ivlen);
		}
		// initialize encryption handle
		$encrypted = false;
		if( mcrypt_generic_init($cypher, $key, $iv) != -1 )
		{
				// http://php.net/manual/en/function.mcrypt-generic.php
				$encrypted = mcrypt_generic($cypher, $data);
				// clean up
				mcrypt_generic_deinit($cypher);
				mcrypt_module_close($cypher);
		}
		$sTempRst = base64_encode( $encrypted );
		return rtrim($sTempRst,$this->_g_sPadding);
	}
/**
 * @brief
 */
	private function _decryptData($data)
	{
		$data = base64_decode( $data );
		$key = $this->_g_sSecret;
		$iv = $this->_g_sIv;
		$cypher = mcrypt_module_open(MCRYPT_RIJNDAEL_128, '', MCRYPT_MODE_CBC, '');
		if(is_null($iv)) 
		{
			$ivlen = mcrypt_enc_get_iv_size($cypher);
			$iv = substr($data, 0, $ivlen);
			$data = substr($data, $ivlen);
		}
		// initialize encryption handle
		if( mcrypt_generic_init($cypher, $key, $iv) != -1 )
		{
				// http://php.net/manual/en/function.mdecrypt-generic.php
				$decrypted = mdecrypt_generic($cypher, $data);
				// clean up
				mcrypt_generic_deinit($cypher);
				mcrypt_module_close($cypher);
				return $decrypted;
		}
		return false;
	}
}

// for test
// https://chakkhan.com/modules/svestudio/sv_classes/svapi_crypt.php
// https://yuhangen.co.kr/modules/svestudio/b2c.php?@v=5kV1VZZalJB446ZtVOBkJsRodXM37axdFyeKlhUhK4A%3D
//define('__XE__',   TRUE);
//define('_XE_PATH_', str_replace('modules/svestudio/sv_classes/svapi_crypt.php', '', str_replace('\\', '/', __FILE__)));
//$cypher = new singleviewApiOpenSsl();
//$secret = 'TestString From PHP345';
//echo "================Encrypt Test ===================<BR>";
//echo "<BR>".
//var_dump($secret);
//echo '<BR>';
//$php_encrypted = $cypher->encryptData($secret);
//var_dump($php_encrypted);
//echo '<BR>';
//echo $cypher->_decryptData($php_encrypted);
/* End of file b2c.php */
/* Location: ./modules/svestudio/b2c.php */