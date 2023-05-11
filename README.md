<H1>simple-server-heartbeat</H1>

Este script permite controlar si una máquina o servicio está funcionando (y/o está conectada a Internet o no), permitiendo enviar un mensaje a un canal de Telegram en caso contrario.

El script se aloja en un espacio web que no resida físicamente en la máquina a comprobar, por razones obvias.

El funcionamiento es el siguiente:

1) Una máquina (A) que es la que queremos controlar que está funcionando y tiene conectividad, ejecuta el script remoto (mediante una llamada curl, por ejemplo),

2) Una máquina (B), diferente de A) que ejecuta el script (con otros parámetros) y que lanza el aviso en caso de que se detecte un error en la conectividad de la primera.

<B>PARÁMETROS</B>

<I>method</I>

Parámetro obligatorio que indica si estamos conectando desde la máquina "A" (<I>PING</I>) o desde la máquina "B" (<I>PONG</I>)

El valor de este parámetro se calcula implícitamente según el método HTTP utilizado (PUT para el PING, POST para el PONG)

<I>domains</I>

Lista de dominios, con los siguientes valores:

* domain

El script permite controlar multitud de máquinas.
Este parámetro, que es obligatorio, es el identificador de la máquina a controlar (por ejemplo la dirección IP, nombre de dominio o simplemente un valor que nos permita identificar a la máquina en cuestión)

* gracePeriod

Parámetro obligatorio que indica el tiempo (en segundos) a partir del cual se envía la notificación al canal de Telegram.

* message

Este parámetro (opcional para el modo <I>PING</I>) es un mensaje que se incluirá en el mensaje de Telegram en caso de fallo, y puede tomar cualquier valor, por ejemplo el nivel de batería del ordenador que realiza la llamada, etc.

* channels

Lista de canales a los que notificar acerca del dominio en cuestión:
Para cada canal se requiere: 

* telegram_api_key
* telegram_chat_id

API Key e identificador del canal al cuál se envía el mensaje (https://core.telegram.org/api/obtaining_api_id).
Estos parámetros son obligatorios si el valor de "method" es <I>PONG</I> y opcionales en el caso de que sea <I>PING</I>.

<B>NOTIFICACIONES</B>

El script envía hasta 2 tipos de notificaciones:

En el <I>PONG</I>, cuando se detecta que "A" no ha ejecutado el script en el tiempo previsto (superior al especificado por el parámetro "grace_period"),

Opcionlmente en el <I>PING</I>, cuando, tras haberse notificado que "A" no había ejecutado el script en el tiempo previsto, ésta vuelve a ejecutar el script. 

<B>Otras consideraciones</B>

Para la ejecución del script de control (<I>PONG</I>), se puede usar el servicio gratuíto de https://cron-job.org, por ejemplo.



