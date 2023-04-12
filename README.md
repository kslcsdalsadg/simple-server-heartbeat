Este script permite controlar si una máquina está funcionando y/o está conectada a Internet o no, permitiendo enviar un mensaje a un canal de Telegram en caso contrario.

El script se aloja en un espacio web que no resida físicamente en la máquina a comprobar, por razones obvias.

El funcionamiento es el siguiente:

1) La máquina que se desea controlar ejecuta el script remoto (mediante una llamada curl, por ejemplo) 

Al ejecutar esta llamada, el script almacena el instante en que se ha realizado la ejecución del script.

2) Posteriormente, se ejecuta el script (desde otra máquina) y se comprueba en qué instante la máquina a controlar lo ejecutó por última vez, actuando en consecuencia.

<B>PARÁMETROS</B>

<I>method</I>

Parámetro que indica si estamos conectando desde la máquina a controlar (PING) o controlando cuándo la máquina a controlar contactó por última vez (PONG)
Este parámetro es obligatorio.

<I>domain</I>

ID de la máquina a controlar (por ejemplo una IP, nombre de dominio o simplemente un valor que nos permita identificar a la máquina en cuestión)
Este parámetro es obligatorio.

<I>grace_period</I>

Tiempo (en segundos) a partir del cual se envía la notificación al canal de Telegram
Este parámetro es obligatorio.

<I>telegram_api_key</I>
<I>telegram_chat_id</I>

API Key e identificador del canal al cuál se envía el mensaje (https://core.telegram.org/api/obtaining_api_id).
Estos parámetros son obligatorios si el valor de "method" es "PONG" y opcionales en el caso de que sea "PING".

<B>NOTIFICACIONES</B>

El script envía hasta 2 tipos de notificaciones:

1) Cuando se detecta que la máquina a controlar no ha ejecutado el script en el tiempo previsto (superior al especificado por el parámetro "grace_period")

2) (opcional) Cuando, tras haberse notificado que la máquina a controlar no había ejecutado el script en el tiempo previsto, ésta vuelve a ejecutar el script. 

<B>EJEMPLOS DE USO</B>

<I>PING</I>

/usr/bin/curl -X POST -d "method=PING&domain=DOMAIN&telegram_api_key=API_KEY&telegram_chat_id=CHAT_ID" "https://XXXXX.com/php/heartbeat.php"


<I>PONG</I>

/usr/bin/curl -X POST -d "method=PONG&domain=DOMAIN&telegram_api_key=API_KEY&telegram_chat_id=CHAT_ID" "https://XXXXX.com/php/heartbeat.php"



