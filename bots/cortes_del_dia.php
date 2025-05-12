
<?php
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
			if(!empty($row)) {
		  	$sReturn = $row[0];

			}
			else {
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






$cantidad_total = 0;
$cantidad_final = 0;

$fecha = SQL_val("SELECT DATE(now()) - INTERVAL 1 DAY FROM dual");

$obtener_ordenes = EXTERN_SQL_querytoarray("SELECT CASE
			WHEN PLTIPMV = 'DF' THEN PEFECHA
            WHEN PLTIPMV = 'V' THEN PEFECHA
	END AS FCORTE,

	PRVCOD, PRVNOM, PENUM, ICOD, ILOCALIZ, IEAN,
	SUM(PLCANT) SOLICITADO, PLSURT SURTIDO, PLCANT - PLSURT RESTANTE,PLTALLA,
	IF(PLSURT =0,'',(SELECT max(dfecha) FROM fdoc
							LEFT JOIN faxinv on faxinv.dseq = fdoc.dseq
							where iseq = fplin.iseq
							and drefer = fpenc.penum)) 'FENTRADA'
FROM fpenc
LEFT JOIN fplin ON fplin.PESEQ = fpenc.PESEQ
LEFT JOIN finv ON finv.ISEQ = fplin.ISEQ
LEFT JOIN fprv ON fprv.PRVSEQ = fpenc.PRVSEQ
WHERE PLTIPMV IN ('DF','V','DC') AND PEFECHA =  '$fecha' GROUP BY PENUM;");


$obtener_datos = SQL_querytoarray("SELECT 
C.nombre_cliente as cliente,
CO.estatus,
CASE
	WHEN CO.id_cliente = 1 THEN P.clave
    WHEN CO.id_cliente = 3 THEN PC.clave_pedido
    WHEN CO.id_cliente = 5 THEN HP.clave
END AS clave, 
CASE
	WHEN CO.id_cliente = 1 THEN (SELECT estilo FROM ESTILOS WHERE id_estilos = P.id_estilos)
    WHEN CO.id_cliente = 3 THEN (SELECT estilo FROM ESTILOS_COPPEL WHERE id_estilo = PC.id_estilo)
    WHEN CO.id_cliente = 5 THEN HP.estilo
END AS estilo, 

CASE
	WHEN CO.id_cliente = 1 THEN (SELECT sum(cantidad_cortada) FROM ARTICULOS WHERE id_articulo in (SELECT id_articulo  FROM ARTICULOS_PEDIDOS APP WHERE APP.clave_pedido = P.clave))
    WHEN CO.id_cliente = 3 THEN (SELECT sum(cantidad_cortada) FROM ARTICULOS_COPPEL WHERE id_articulo in (SELECT id_articulo  FROM ARTICULOS_PEDIDOS_COPPEL APC WHERE APC.clave_pedido = PC.clave_pedido))
    WHEN CO.id_cliente = 5 THEN (SELECT sum(cantidad_cortada)  FROM HANGTEN_ARTICULOS WHERE id_articulo in (SELECT id_articulo  FROM HANGTEN_ARTICULOS_PEDIDOS HPP WHERE HPP.clave_pedido = HP.clave))
END AS cantidad
FROM CORTE_OBSERVACIONES CO
LEFT JOIN PEDIDOS P ON P.clave = CO.clave_pedido
LEFT JOIN PEDIDOS_COPPEL PC ON PC.clave_pedido = CO.clave_pedido
LEFT JOIN HANGTEN_PEDIDOS HP ON HP.clave = CO.clave_pedido
LEFT JOIN CLIENTES C ON C.id_cliente = CO.id_cliente
where CO.estatus = 'Terminado' and CO.fecha_creacion like '%$fecha%'");





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
            max-width: 600px;
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
        <h2>Detalle de Cantidad Cortada</h2>
        <p>Estimado/ Colaborador,</p>
        <p>A continuación, se presentan los detalles de los cortes del día:</p>
        <p>PORTAL:</p>
        <table class="table table-bordered table-hover">
            <thead>
                <tr>
                    <th>Cliente</th>
                    <th>Estilo</th>
                    <th>Cantidad</th>
                </tr>
            </thead>
            <tbody>';

foreach ($obtener_datos as $key => $value) {
    $cliente = $value['cliente'];
    $estilo = $value['estilo'];
    $cantidad_cortada = $value['cantidad'];


    $message .= '<tr>';
    $message .= "<td>$cliente</td>";
    $message .= "<td>$estilo</td>";
    $message .= "<td>$cantidad_cortada</td>";

    $cantidad_total += $cantidad_cortada;

    $message .= '</tr>';
}


$message .= '<tr>';
$message .= "<td colspan='2'>Total: </td>";
$message .= "<td><strong>$cantidad_total</strong></td>";



$message .= '</tr>';

$message .= ' </tbody>
        </table>';


$message .= '<p>PROSCAI:</p>
<table class="table table-bordered table-hover">
            <thead>
                <tr>
                    <th>Ubicacion</th>
                    <th>Estilo</th>
                    <th>Orden</th>
                    <th>Cantidad</th>
                </tr>
            </thead>
            <tbody>';

foreach ($obtener_ordenes as $key => $value) {
    $proveedor = $value['PRVNOM'];
    $estilo = $value['ILOCALIZ'];
    $orden = $value['PENUM'];
    $orden_cortada = $value['SOLICITADO'];


    $message .= '<tr>';
    $message .= "<td>$proveedor</td>";
    $message .= "<td>$estilo</td>";
    $message .= "<td>$orden</td>";
    $message .= "<td>$orden_cortada</td>";
    $cantidad_final += $orden_cortada;

    $message .= '</tr>';
}


$message .= '<tr>';
$message .= "<td colspan='3'>Total: </td>";
$message .= "<td><strong>$cantidad_final</strong></td>";



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
$email->addAddress("sistemas3@americancotton.com.mx");
// $email->addAddress("desarrollo@divisionfx.com.mx");
$email->addAddress("sistemas@americancotton.com.mx");
$email->addCc("malfie@divisionfx.com.mx");
$email->addCc("ymedina@americancotton.com.mx");
$email->addCc("calvarez@divisionfx.com.mx");
$email->isHTML(true);
// Asunto
$email->Subject = "Reporte de Cortes del Dia $fecha";
// Contenido HTML
$email->Body = $message;

$email->CharSet = 'UTF-8';
$email->Encoding = 'base64';
try{
    if($cantidad_final != 0 and $cantidad_total != 0){
        $email->send();
    }
   
}catch(Exception $e){

}



