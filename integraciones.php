<?php
require_once __DIR__ . '/funciones.php';

function pedir_json(string $url, ?array $cuerpo = null, array $cabeceras = []): array
{
    $curl = curl_init($url);
    curl_setopt_array($curl, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CONNECTTIMEOUT => 5,
        CURLOPT_TIMEOUT => 12,
        CURLOPT_HTTPHEADER => array_merge(['Accept: application/json'], $cabeceras),
        CURLOPT_PROTOCOLS => CURLPROTO_HTTPS,
    ]);
    if (getenv('SSL_CERT_FILE')) curl_setopt($curl, CURLOPT_CAINFO, getenv('SSL_CERT_FILE'));
    if ($cuerpo !== null) {
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($cuerpo, JSON_THROW_ON_ERROR));
    }
    $respuesta = curl_exec($curl);
    $codigo = curl_getinfo($curl, CURLINFO_RESPONSE_CODE);
    curl_close($curl);
    if ($respuesta === false || $codigo < 200 || $codigo >= 300) {
        throw new RuntimeException('El servicio externo no está disponible.');
    }
    $datos = json_decode($respuesta, true, 512, JSON_THROW_ON_ERROR);
    if (!is_array($datos)) throw new RuntimeException('Respuesta externa no válida.');
    return $datos;
}

function enviar_confirmacion(int $id): string
{
    $reserva = con_agenda(function (PDO $pdo) use ($id): array {
        $reserva = solicitud($id);
        if (!$reserva || $reserva['estado'] !== 'confirmada' || in_array($reserva['whatsapp_estado'], ['enviado', 'enviando'], true)) {
            throw new DomainException('No hay una confirmación pendiente de envío.');
        }
        $consulta = $pdo->prepare("UPDATE solicitudes SET whatsapp_estado = 'enviando' WHERE id_solicitud = ?");
        $consulta->execute([$id]);
        return $reserva;
    });
    $token = getenv('WHATSAPP_TOKEN');
    $telefono_id = getenv('WHATSAPP_PHONE_NUMBER_ID');
    $plantilla = getenv('WHATSAPP_TEMPLATE_NAME');
    $mensaje_id = null;
    if (!$token || !$telefono_id || !$plantilla) {
        $estado = 'no_configurado';
        $mensaje = 'WhatsApp no está configurado. La reserva sí quedó confirmada; contacta al cliente por tu cuenta.';
    } else {
        try {
            $version = getenv('WHATSAPP_API_VERSION') ?: 'v23.0';
            if (!preg_match('/^v\d+\.\d+$/', $version) || !ctype_digit($telefono_id)) throw new RuntimeException('Configuración no válida.');
            $respuesta = pedir_json("https://graph.facebook.com/$version/$telefono_id/messages", [
                'messaging_product' => 'whatsapp',
                'to' => $reserva['telefono'],
                'type' => 'template',
                'template' => [
                    'name' => $plantilla,
                    'language' => ['code' => getenv('WHATSAPP_TEMPLATE_LANGUAGE') ?: 'es_AR'],
                    'components' => [['type' => 'body', 'parameters' => [
                        ['type' => 'text', 'text' => $reserva['nombre']],
                        ['type' => 'text', 'text' => date('d/m/Y', strtotime($reserva['fecha_evento']))],
                        ['type' => 'text', 'text' => substr($reserva['hora_inicio'], 0, 5) . ' a ' . substr($reserva['hora_fin'], 0, 5)],
                    ]]],
                ],
            ], ['Content-Type: application/json', 'Authorization: Bearer ' . $token]);
            $mensaje_id = $respuesta['messages'][0]['id'] ?? null;
            if (!is_string($mensaje_id) || $mensaje_id === '') throw new RuntimeException('No se recibió un identificador de mensaje.');
            $estado = 'enviado';
            $mensaje = 'WhatsApp aceptó el mensaje de confirmación. Esto no asegura su entrega al teléfono.';
        } catch (Throwable $error) {
            $estado = 'error';
            $mensaje = 'La reserva quedó confirmada, pero WhatsApp falló. Revisa la configuración y contacta al cliente.';
        }
    }
    $consulta = db()->prepare('UPDATE solicitudes SET whatsapp_estado = ?, whatsapp_id = ? WHERE id_solicitud = ?');
    $consulta->execute([$estado, $mensaje_id, $id]);
    return $mensaje;
}

function descripcion_clima(int $codigo): string
{
    return match (true) {
        $codigo === 0 => 'Despejado',
        $codigo <= 3 => 'Parcialmente nublado o nublado',
        in_array($codigo, [45, 48], true) => 'Niebla',
        $codigo >= 51 && $codigo <= 57 => 'Llovizna',
        $codigo >= 61 && $codigo <= 67 => 'Lluvia',
        $codigo >= 71 && $codigo <= 77 => 'Nieve',
        $codigo >= 80 && $codigo <= 82 => 'Chaparrones',
        in_array($codigo, [85, 86], true) => 'Nevadas',
        $codigo >= 95 => 'Tormenta',
        default => 'Sin descripción',
    };
}
