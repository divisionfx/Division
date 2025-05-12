<?php
require '../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require '../assets/PHPMailer/src/Exception.php';
require '../assets/PHPMailer/src/PHPMailer.php';
require '../assets/PHPMailer/src/SMTP.php';


$host = 'oms.appeuro.mx';
$usuario = 'gpodsw';
$bd = 'gpodsw';
$contraseña = 'cv%j?VR=GI8h';
$port = '6033';
$mysqli = new mysqli($host, $usuario, $contraseña, $bd, $port);

$extern_host = 'oms.appeuro.mx';
$extern_usuario = 'divfapp';
$extern_bd = 'db164divfx';
$extern_contraseña = 'sDf4&8dH%3wEf&#';
$extern_port = '6033';

$extern_mysqli = new mysqli($extern_host, $extern_usuario, $extern_contraseña, $extern_bd, $extern_port);

function EXTERN_SQL_querytoarray($query)
{
    global $extern_mysqli;

    $result = $extern_mysqli->query($query);
    $aResult = array();
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            array_push($aResult, $row);
        }
    }
    return $aResult;
}

function SQL_val($query, $default = "")
{
    try {
        global $mysqli;

        $result = $mysqli->query($query);
        if (!$result) {
            $sReturn = $default;
        } else {
            $row = $result->fetch_array();
            if (!empty($row)) {
                $sReturn = $row[0];
            } else {
                $sReturn = $default;
            }
        }
    } catch (Exception $e) {
        $sReturn = $default;
    }
    return $sReturn;
}

function SQL_querytoarray($query)
{
    try {
        global $mysqli;

        $result = $mysqli->query($query);
        $aResult = array();
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                array_push($aResult, $row);
            }
        }
    } catch (mysqli_sql_exception $e) {
        $aResult = null;
    }
    return $aResult;
}

$eans_portales = SQL_querytoarray("SELECT PP.programa,trim(E.estilo) estilo, A.ean,A.color,A.talla,PP.año,TD.talla_proscai FROM ARTICULOS A
LEFT JOIN ARTICULOS_PEDIDOS AP ON AP.id_articulo = A.id_articulo
LEFT JOIN PEDIDOS P ON P.clave = AP.clave_pedido
LEFT JOIN PROGRAMAS_PEDIDOS PP ON PP.id_programa = P.id_programa
LEFT JOIN ESTILOS E ON E.id_estilos = P.id_estilos
LEFT JOIN TALLAS_DEPARTAMENTOS TD ON TD.talla = trim(A.talla) and PP.id_departamento = TD.id_departamento
WHERE A.ean != '' and LENGTH(A.ean) = 13
  AND E.estilo != '' 
  AND PP.año >= 2024
  order by PP.año,PP.mes;");

$eans_proscai = EXTERN_SQL_querytoarray("SELECT icod,iean,ialta,ilocaliz,ium FROM finv where ialta >= '2024-01-01' and ilocaliz != '' and  icod LIKE 'U%' AND LENGTH(icod) = 13");


$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();
$sheet->setTitle('Detalle de EANS');

$sheet->setCellValue('A1', 'Raiz')
    ->setCellValue('B1', 'UM')
    ->setCellValue('C1', 'UPS')
    ->setCellValue('D1', 'Ean');




$count = 0;
$row = 2;
$cuerpo = "";
foreach ($eans_portales as $key => $value) {
    $ean = $value['ean'];
    $estilo = trim($value['estilo']);
    $programa = $value['programa'];
    $talla_p = $value['talla_proscai'];
    
    foreach ($eans_proscai as $key_p => $value_p) {
        $estilo_proscai = trim($value_p['ilocaliz']);
        $ean_proscai = $value_p['iean'];
        $codigo = $value_p['icod'];
        $codigo_cortado = substr($codigo, -3);
        $um = $value_p['ium'];
        
        if ($estilo == $estilo_proscai and $codigo_cortado == $talla_p) {
            if ($ean_proscai == '') {
                $cuerpo .= '<tr>';
                $cuerpo .= "<td style='padding: 0.75rem; border: 1px solid #dee2e6;'>$programa</td>";
                $cuerpo .= "<td style='padding: 0.75rem; border: 1px solid #dee2e6;'>$estilo_proscai</td>";
                $cuerpo .= "<td style='padding: 0.75rem; border: 1px solid #dee2e6;'>$codigo</td>";

                $cuerpo .= "<td style='padding: 0.75rem; border: 1px solid #dee2e6;'>$um</td>";
                $cuerpo .= "<td style='padding: 0.75rem; border: 1px solid #dee2e6;'>$ean</td>";
                $cuerpo .= "<td style='padding: 0.75rem; border: 1px solid #dee2e6;'>$ean</td>";
                $cuerpo .= '</tr>';

                $sheet->setCellValue('A' . $row, $codigo)
                    ->setCellValue('B' . $row, $um)
                    ->setCellValue('C' . $row, $ean)
                    ->setCellValue('D' . $row, $ean);
                $count++;
                $row++;
                unset($eans_proscai[$key_p]);
                break;
            }
        }
    }
}

$message = '
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detalle de Cantidad Cortada</title>
</head>
<body style="font-family: Arial, sans-serif; margin: 0; padding: 0; background-color: #f8f9fa;">
    <div style="width: 100%; max-width: 1000px; margin: 20px auto; background-color: #ffffff; padding: 10px; border: 1px solid #dee2e6; border-radius: 5px; box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);">
        <div style="text-align: start; padding-bottom: 30px; display: flex;">
            <div style="padding-top: 10px;">
                <h1 style="color: #083666;">Eans existentes no cargados en PROSCAI</h1>
                <p>Estimado Colaborador,</p>
                <p>A continuación, se presentan los códigos EAN que están registrados en el portal de Division Fx pero no se encuentran en PROSCAI:</p>
            </div>
        </div>
        <table style="width: 100%; margin-bottom: 1rem; background-color: transparent; border-collapse: collapse;">
            <thead>
                <tr>
                    <th colspan="5" style="text-align: end; padding: 0.75rem; border: 1px solid #dee2e6;">Total de codigos EAN por asignar en PROSCAI:</th>
                    <th style="padding: 0.75rem; border: 1px solid #dee2e6;"><strong>' . $count . '</strong></th>
                </tr>
                <tr>
                    <th style="background: #083666; color: #ffffff; padding: 0.75rem; border: 1px solid #dee2e6;">Programa</th>
                    <th style="background: #083666; color: #ffffff; padding: 0.75rem; border: 1px solid #dee2e6;">Estilo</th>
                    <th style="background: #083666; color: #ffffff; padding: 0.75rem; border: 1px solid #dee2e6;">Raiz U</th>
                    <th style="background: #083666; color: #ffffff; padding: 0.75rem; border: 1px solid #dee2e6;">UM</th>
                    <th style="background: #083666; color: #ffffff; padding: 0.75rem; border: 1px solid #dee2e6;">UPS faltante</th>
                    <th style="background: #083666; color: #ffffff; padding: 0.75rem; border: 1px solid #dee2e6;">Ean faltante</th>
                </tr>
            </thead>
            <tbody>';

$message .= $cuerpo;

$message .= '</tbody>
        </table>
        <p>Gracias por su atención.</p>
        <p>Atentamente,</p>
        <p>EuroBot Division Fx</p>
    </div>
</body>
</html>
';


$excel_adjunto = tempnam(sys_get_temp_dir(), 'detalle_') . '.xlsx';
$writer = new Xlsx($spreadsheet);
$writer->save($excel_adjunto);





try {
    
    $email = new PHPMailer(TRUE);
    $email->isSMTP();
    $email->SMTPAuth = true;
    $email->Host = "zoho.com";
    $email->Port = "587";
    $email->SMTPSecure = 'tls';
    $email->Host = 'smtppro.zoho.com';
    $email->SMTPAuth = true;
    $email->Username = "eurobot@americancotton.com.mx";
    $email->Password = "[_6+Ol@DmQJ6";
    $email->setFrom('eurobot@americancotton.com.mx');
    $email->addAddress("sistemas3@americancotton.com.mx");
    $email->addAddress("soporte5@americancotton.com.mx");
    $email->addAddress("desarrollo@divisionfx.com.mx");
    $email->addAddress("tickets@americancotton.com.mx");


    // $email->isHTML(true);
    $email->Subject = "Ean pendientes por cargar en Proscai " . date("m-d-Y") . "";
    $email->Body ="CARGA DE CODIGOS EAN";

    $email->addAttachment($excel_adjunto, 'carga_eans_proscai.xlsx');

    $email->CharSet = 'UTF-8';
    $email->Encoding = 'base64';
    if ($row > 2) {
        $email->send();
        echo 'El correo ha sido enviado con éxito.';
    }
} catch (Exception $e) {
    echo "El correo no pudo ser enviado. Error: $e";
} finally {
    unlink($excel_adjunto);
}


try {
    
    $email = new PHPMailer(TRUE);
    $email->isSMTP();
    $email->SMTPAuth = true;
    $email->Host = "zoho.com";
    $email->Port = "587";
    $email->SMTPSecure = 'tls';
    $email->Host = 'smtppro.zoho.com';
    $email->SMTPAuth = true;
    $email->Username = "eurobot@americancotton.com.mx";
    $email->Password = "[_6+Ol@DmQJ6";
    $email->setFrom('eurobot@americancotton.com.mx');
    $email->addAddress("sistemas3@americancotton.com.mx");
    $email->addAddress("administracion@americancotton.com.mx");
    $email->addAddress("cadenas@divisionfx.com.mx");
    $email->addAddress("gpaillez@americancotton.com.mx");
    $email->addAddress("ymillan@americancotton.com.mx");



    $email->isHTML(true);
    $email->Subject = "Ean pendientes por cargar en Proscai " . date("m-d-Y") . "";
    $email->Body = $message;

    $email->CharSet = 'UTF-8';
    $email->Encoding = 'base64';
    if ($row > 2) {
        $email->send();
        echo 'El correo ha sido enviado con éxito.';
    }
} catch (Exception $e) {
    echo "El correo no pudo ser enviado. Error: $e";
} finally {
    unlink($excel_adjunto);
}
