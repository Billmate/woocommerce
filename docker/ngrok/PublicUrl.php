<?php

$data = json_decode(file_get_contents('http://ngrok:4040/api/tunnels', true));
foreach ($data->tunnels as $tunnel) {
    if ($tunnel->proto === 'https') {
        echo($tunnel->public_url);
        break;
    }
}
