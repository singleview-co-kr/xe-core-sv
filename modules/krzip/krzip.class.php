<?php

class krzip extends ModuleObject
{
    public static $instance_sequence = 0;
    
    public function getKrzipConfig()
    {
        $defaults = array(
            'server_url' => 'https://api.poesis.kr/post/search.php',
            'map_provider' => 'naver',
            'address_format' => 'postcodify',
            'display_postcode' => 'Y',
            'display_address' => 'Y',
            'display_details' => 'Y',
            'display_extra_info' => 'Y',
            'display_jibeon_address' => 'N',
            'postcode_format' => 5,
            'server_request_format' => 'CORS',
            'require_exact_query' => 'N',
            'use_full_jibeon' => 'N',
        );
        
        $config = getModel('module')->getModuleConfig('krzip');
        
        foreach ($defaults as $key => $value)
        {
            if (!$config->{'krzip_' . $key})
            {
                $config->{'krzip_' . $key} = $value;
            }
        }
        
        if ($config->krzip_server_url === substr($defaults['server_url'], 6))
        {
            $args->krzip_server_url = $defaults['server_url'];
        }
        
        return $config;
    }
    
    public function moduleInstall()
    {
        return new BaseObject();
    }
    
    public function checkUpdate()
    {
        return false;
    }
    
    public function moduleUpdate()
    {
        return new BaseObject(0, 'success_updated');
    }
    
    public function recompileCache()
    {
        // no-op
    }
}
