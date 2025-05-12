<?php
require "../php/conexionn.php";

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
require '../assets/PHPMailer/src/Exception.php';
require '../assets/PHPMailer/src/PHPMailer.php';
require '../assets/PHPMailer/src/SMTP.php';

$cantidad_total = 0;
$cantidad_final = 0;

$fecha = SQL_val("SELECT date(date(now())-1) from dual");

$obtener_ordenes = EXTERN_SQL_querytoarray("SELECT DFECHA,clinom,dnum,icod,ilocaliz,DCANTF
	FROM fdoc
	left join faxinv on faxinv.dseq = fdoc.dseq
	left join finv on finv.iseq = faxinv.iseq
    LEFT JOIN fplin ON fplin.iseq = finv.iseq
    left join fpenc on fpenc.peseq = fplin.peseq
	LEFT JOIN fcli on fcli.cliseq = fdoc.cliseq
	LEFT JOIN fcoment on fcoment.comseqfact = fdoc.dseq
	where aitipmv in ('F') 
    and DFECHA =  date(date(now())-1)
    GROUP BY dnum,clinom");



$obtener_dia_actual = SQL_val("SELECT date(date(now())-1) from dual");

$message = '
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detalle de Cantidad Cortada</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f8f9fa;
        }
        .container {
            width: 100%;
            max-width: 700px;
            margin: 20px auto;
            background-color: #ffffff;
            padding: 20px;
            border: 1px solid #dee2e6;
            border-radius: 5px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }
        .table {
            width: 100%;
            margin-bottom: 1rem;
            background-color: transparent;
            border-collapse: collapse;
        }
        .table th, .table td {
            padding: 0.75rem;
            vertical-align: top;
            border-top: 1px solid #dee2e6;
        }
        .table thead th {
            vertical-align: bottom;
            border-bottom: 2px solid #dee2e6;
        }
        .table tbody + tbody {
            border-top: 2px solid #dee2e6;
        }
        .table-sm th, .table-sm td {
            padding: 0.3rem;
        }
        .table-bordered {
            border: 1px solid #dee2e6;
        }
        .table-bordered th, .table-bordered td {
            border: 1px solid #dee2e6;
        }
        .table-bordered thead th, .table-bordered thead td {
            border-bottom-width: 2px;
        }
        .table-hover tbody tr:hover {
            background-color: rgba(0, 0, 0, 0.075);
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>Detalle de Facturas</h2>
        <p>Estimado/ Colaborador,</p>
        <p>A continuación, se presentan los detalles de las facturas del día:</p>
        <table class="table table-bordered table-hover">
            <thead>
                <tr>
                    <th>Cliente</th>
                    <th>Factura</th>
                    <th>Estilo</th>
                    <th>Total $</th>
                </tr>
            </thead>
            <tbody>';

foreach ($obtener_ordenes as $key => $value) {
    $cliente = $value['clinom'];
    $orden = $value['dnum'];
    $estilo = $value['ilocaliz'];
    $cantidad_factura = $value['DCANTF'];


    $message .= '<tr>';
    $message .= "<td>$cliente</td>";
    $message .= "<td>$orden</td>";
    $message .= "<td>$estilo</td>";
    $message .= "<td> $ $cantidad_factura</td>";

    $cantidad_total += $cantidad_factura;

    $message .= '</tr>';
}


$message .= '<tr>';
$message .= "<td colspan='3'>Total: </td>";
$message .= "<td><strong>$ $cantidad_total</strong></td>";



$message .= '</tr>';

$message .= ' </tbody>
        </table>';


     $message .='<p>Gracias por su atención.</p>
        <p>Atentamente,</p>
        <p>Division Fx</p>
    </div>
</body>
</html>
';

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
// $email->addAddress("sistemas3@americancotton.com.mx");
// $email->addCc("malfie@divisionfx.com.mx");
$email->isHTML(true);
// Asunto
$email->Subject = "Reporte de Facturas del Dia $obtener_dia_actual";
// Contenido HTML
$email->Body = $message;

$email->CharSet = 'UTF-8';
$email->Encoding = 'base64';
if ($email->send()) {
    echo $message;
} else {
}


