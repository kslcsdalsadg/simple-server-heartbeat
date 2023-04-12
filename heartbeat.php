<?php

    $GLOBALS['file_pathname'] = '.heartbeat.dat';
    
    function get_json_data() 
    {
        if (file_exists($GLOBALS['file_pathname'])) 
        { 
            return json_decode(file_get_contents($GLOBALS['file_pathname']), true); 
        }
        return array();
    }
    
    function set_json_data($json_data) 
    {
        $file = fopen($GLOBALS['file_pathname'], 'w');
        fwrite($file, json_encode($json_data, JSON_PRETTY_PRINT));
        fclose($file);    
    }
    
    function now() 
    {
        return date_timestamp_get(date_create());
    }
    
    function get_parameter($name, $array_of_regexp = null, $allow_empty_value = false)
    {
        $value = '';
        if (array_key_exists($name, $_GET)) 
        { 
            $value = $_GET[$name]; 
        }
        else if (array_key_exists($name, $_POST)) 
        { 
            $value = $_POST[$name]; 
        }
        if ((! $value) && (! $allow_empty_value))
        {
            exit(sprintf('El valor del parámetro \'%s\' no se acepta', $name)); 
        }
        if (($value) && ($array_of_regexp != null))
        {
            $matches = false;
            foreach ($array_of_regexp as $regexp)
            {
                if (preg_match($regexp, $value)) 
                { 
                    $matches = true; 
                    break; 
                }
            }
            if (! $matches) 
            { 
                exit(sprintf('El valor del parámetro \'%s\' no se acepta', $name)); 
            }
        }
        return $value;
    }

    function send_message($telegram_api_key, $telegram_chat_id, $message)
    {
        $response = file_get_contents('https://api.telegram.org/bot' . $telegram_api_key . '/sendMessage?' . http_build_query([ 'chat_id' => $telegram_chat_id, 'text' => $message ]) );
        if ($response)
        {
            $response = json_decode($response);
            return $response->{'ok'};
        }
        return false;
    }
    
    $method = get_parameter('method');
    if (($method != 'PING') && ($method != 'PONG')) { exit('El valor del parámetro \'method\' no se acepta'); } 
    $domain = get_parameter('domain', [ '/^(([a-z]|[a-z_][a-z0-9_\-]*[a-z0-9_])\.)*([a-z]|[a-z_][a-z0-9_\-]*[a-z0-9_])$/i', '/^(([0-9]|[1-9][0-9]|1[0-9]{2}|2[0-4][0-9]|25[0-5])\.){3}([0-9]|[1-9][0-9]|1[0-9]{2}|2[0-4][0-9]|25[0-5])$/i' ]);
    if ($method == 'PING')
    {
        $json_data = get_json_data();
        if ((array_key_exists($domain, $json_data)) && (intval($json_data[$domain]) == 0))
        {
            $telegram_api_key = get_parameter('telegram_api_key', [ '/^[a-z0-9:]+$/i' ], true);
            $telegram_chat_id = get_parameter('telegram_chat_id', [ '/^\-[0-9]+$/' ], true);
            if (($telegram_api_key) && ($telegram_chat_id)) 
            {
                send_message($telegram_api_key, $telegram_chat_id, sprintf('%s vuelve a estar online', $domain));
            }
        }
        $json_data[$domain] = now();
        set_json_data($json_data);
    }
    else
    {
        $grace_period = intval(get_parameter('grace_period', [ '/^[0-9]+$/' ]));
        $json_data = get_json_data();
        $message = '';
        $now = now();
        if (! array_key_exists($domain, $json_data)) { $message = sprintf('%s nunca ha informado sobre su estado', $domain); }
        else
        {
            $json_data[$domain] = intval($json_data[$domain]);
            if (($json_data[$domain] != 0) && ($json_data[$domain] + $grace_period < $now)) { $message = sprintf('El último ping de %s es de hace más de %d minutos', $domain, ($now - $json_data[$domain]) / 60); }
        }
        if (strlen($message) > 0)
        {
            $telegram_api_key = get_parameter('telegram_api_key', [ '/^[a-z0-9:]+$/i' ]);
            $telegram_chat_id = get_parameter('telegram_chat_id', [ '/^\-[0-9]+$/' ]);
            $ok = send_message($telegram_api_key, $telegram_chat_id, $message);
            if ($ok) 
            {
                $json_data[$domain] = 0;
                set_json_data($json_data);
            }
        }
    }
    
?>