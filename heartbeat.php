<?php

    $GLOBALS['file_pathname'] = '.heartbeat.dat';
     
    function get_json_data() 
    {
        if (file_exists($GLOBALS['file_pathname'])) 
        { 
            try
            {
                if (filesize($GLOBALS['file_pathname']) == 0) 
                {
                    unlink($GLOBALS['file_pathname']);
                }
                else 
                {
                    return json_decode(file_get_contents($GLOBALS['file_pathname']), true); 
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
        $attempt = 1;
        while ($attempt < 15)
        {
            if (! file_put_contents($GLOBALS['file_pathname'], json_encode($json_data, JSON_PRETTY_PRINT), LOCK_EX)) 
            {
                $attempt ++; 
                sleep(1);
                continue;
                
            }
            break;
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
            exit(sprintf('El valor del parámetro \'%s\' no puede ser vacío', $name)); 
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
                exit(sprintf('El valor del parámetro \'%s\' es \'%s\' y no se acepta', $name, $value)); 
            }
        }
        return $value;
    }

    function _send_message($channel, $message)
    {
        if (($channel->apiKey) && ($channel->chatId)) 
        {
            $api_key = check_parameter('telegram-api-key', $channel->apiKey, [ '/^[a-z0-9:_]+$/i' ]);
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
            exit('El valor del paràmetro \'channels\' no se acepta');
        }
        $ok = false;
        foreach ($channels as $channel)
        {
            if (! is_object($channel))
            {
                exit('El valor del parámetro \'channel\' no se acepta');
            }
            $ok |= _send_message($channel, $message);
        }
        return $ok;
    }
    
    $now = now();
    $parameters = parse_parameters();
    if (! is_object($parameters))
    {
        exit('El valor de los paràmetros no se acepta (' . json_last_error() . ')');
    }
    $method = $_SERVER['REQUEST_METHOD'];
    if (($method != 'POST') && ($method != 'PUT')) 
    { 
        exit('El valor del parámetro \'method\' no se acepta'); 
    } 
    $domains = $parameters->domains;
    if (! is_array($domains)) 
    {
        exit('El valor del paràmetro \'domains\' no se acepta');
    }
    foreach ($domains as $domain)
    {
        if (! is_object($domain))
        {
            exit('El valor del parámetro \'domain\' no se acepta');
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
                    send_message($domain->channels, sprintf('%s vuelve a estar conectada a Internet', $name));
                }
            }
            $message = check_parameter('message', $domain->message, [ '/^[\w\s():%]+$/i' ], true);
            $json_data[$name] = $message ? array('when' => $now, 'message' => $message) : $now;
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
                    $message = sprintf('La última conexión de %s es de hace más de %d minutos', $name, ($now - $when) / 60); 
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
                    exit("El mensaje no se pudo enviar");
                }
            }
        }
    }
    exit(0);
    
?>

