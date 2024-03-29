<?php

    $GLOBALS['data_pathname'] = '.heartbeat.dat';
    $GLOBALS['errors_pathname'] = '.heartbeat.err';
     
    function set_errors_log_and_exit($message)
    {
       if ($message != 0) 
        { 
            file_put_contents($GLOBALS['errors_pathname'], $message); 
        }
        exit($message);
    }

    function get_json_data() 
    {
        if (file_exists($GLOBALS['data_pathname'])) 
        { 
            try
            {
                if (filesize($GLOBALS['data_pathname']) == 0) 
                {
                    unlink($GLOBALS['data_pathname']);
                }
                else 
                {
                    return json_decode(file_get_contents($GLOBALS['data_pathname']), true); 
                }
            }
            catch (Exception $e) 
            {
            }
        }
        return array();
    }
    
    function set_json_data($json_data) 
    {
        $temp_filename = sprintf('%s.%d', $GLOBALS['data_pathname'], getmypid());
        if (file_put_contents($temp_filename, json_encode($json_data, JSON_PRETTY_PRINT))) 
        {
            rename($temp_filename, $GLOBALS['data_pathname']);
        }
    }
    
    function now() 
    {
        return date_timestamp_get(date_create());
    }
    
    function parse_parameters() 
    {
        return json_decode(file_get_contents('php://input'));
    }

    function check_parameter($name, $value, $array_of_regexp = null, $allow_empty_value = false)
    {
        if ((! $value) && (! $allow_empty_value))
        {
            set_errors_log_and_exit(sprintf('El valor del parámetro "%s" no puede ser vacío', $name)); 
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
                set_errors_log_and_exit(sprintf('El valor del parámetro "%s" es "%s", que no se acepta', $name, $value)); 
            }
        }
        return $value;
    }

    function _send_message($channel, $message)
    {
        if (($channel->apiKey) && ($channel->chatId)) 
        {
            $api_key = check_parameter('telegram-api-key', $channel->apiKey, [ '/^[a-z0-9:_\-]+$/i' ]);
            $chat_id = check_parameter('telegram-chat-id', $channel->chatId, [ '/^\-[0-9]+$/' ]);
            $response = file_get_contents('https://api.telegram.org/bot' . $api_key . '/sendMessage?' . http_build_query([ 'chat_id' => $chat_id, 'text' => $message ]));
            if ($response)
            {
                $response = json_decode($response);
                return $response->{'ok'};
            }            
        }
        return false;
    }
    
    function send_message($channels, $message) 
    {
        if (! is_array($channels))
        {
            set_errors_log_and_exit('El valor del paràmetro "channels" no se acepta');
        }
        $ok = false;
        foreach ($channels as $channel)
        {
            if (! is_object($channel))
            {
                set_errors_log_and_exit('El valor del parámetro "channel" no se acepta');
            }
            $ok |= _send_message($channel, $message);
        }
        return $ok;
    }
    
    $now = now();
    $parameters = parse_parameters();
    if (! is_object($parameters))
    {
        set_errors_log_and_exit('El valor de los paràmetros no se acepta (error ' . json_last_error() . ')');
    }
    $method = $_SERVER['REQUEST_METHOD'];
    if (($method != 'POST') && ($method != 'PUT')) 
    { 
        set_errors_log_and_exit('El valor del parámetro "method" no se acepta'); 
    } 
    $domains = $parameters->domains;
    if (! is_array($domains)) 
    {
        set_errors_log_and_exit('El valor del paràmetro "domains" no se acepta');
    }
    foreach ($domains as $domain)
    {
        if (! is_object($domain))
        {
            set_errors_log_and_exit('El valor del parámetro "domain" no se acepta');
        }
        $name = check_parameter('domain-name', $domain->name, [ '/^[a-z0-9:_\-\.]+$/i' ]);
        if ($method == 'PUT')
        {
            $json_data = get_json_data();
            if (array_key_exists($name, $json_data))
            {
                $when = is_array($json_data[$name]) ? $json_data[$name]['when'] : $json_data[$name];
                if (intval($when) == 0)
                {
                    $message = sprintf('%s vuelve a estar conectada a Internet', $name);
                    if ($parameters->rebooted == '1')
                    {
                        $message = sprintf('Después del reinicio, %s', $message);
                    }
                    send_message($domain->channels, $message);
                }
            }
            $message = check_parameter('message', $domain->message, [ '/^[\w\s():\.,%º]+$/i' ], true);
            $json_data[$name] = $message ? array('when' => $now, 'whens' => date('d-m-Y H:i:s', $now), 'message' => $message) : $now;
            set_json_data($json_data);
        }
        else
        {
            $grace_period = intval(check_parameter('grace-period', $domain->gracePeriod, [ '/^[0-9]+$/' ]));
            $json_data = get_json_data();
            $message = '';
            if (! array_key_exists($name, $json_data)) 
            {
                $message = sprintf('%s nunca ha informado sobre su estado', $name); 
            }
            else
            {
                $when = intval(is_array($json_data[$name]) ? $json_data[$name]['when'] : $json_data[$name]);
                if (($when != 0) && ($when + $grace_period < $now)) 
                { 
                    $message = sprintf('La última conexión de "%s" es de hace más de %d minutos', $name, ($now - $when) / 60); 
                    if ((is_array($json_data[$name])) && ($json_data[$name]['message']))
                    {
                        $message .= sprintf("\n%s", $json_data[$name]['message']);
                    }
                }
            }
            if (strlen($message) > 0)
            {
                $ok = send_message($domain->channels, $message);
                if ($ok) 
                {
                    $json_data[$name] = 0;
                    set_json_data($json_data);
                }
                else
                {
                    set_errors_log_and_exit("El mensaje no se pudo enviar");
                }
            }
        }
    }
    set_errors_log_and_exit(0);
    
?>
