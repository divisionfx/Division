
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





$obtener_datos = SQL_querytoarray("SELECT B2B.pedido, B2B.departamento,B2B.estatus,B2B.tda,B2BC.camion,B2BC.cve_cita,B2BC.bultos,B2B.fecha_vencimiento as fec, 
(SELECT SUM(C_PEDIDOS_B2B_DETALLADOS.piezas) FROM C_PEDIDOS_B2B_DETALLADOS WHERE C_PEDIDOS_B2B_DETALLADOS.pedido = B2BD.pedido group by C_PEDIDOS_B2B_DETALLADOS.pedido) as total
FROM C_PEDIDOS_B2B B2B
LEFT JOIN C_PEDIDOS_B2B_DETALLADOS B2BD ON B2BD.pedido = B2B.pedido
LEFT JOIN C_PEDIDOS_CAMIONES_B2B B2BC ON B2BC.pedido = B2B.pedido
WHERE (fecha_vencimiento BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY)) AND B2BC.cve_cita is null group by B2BD.pedido");








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
        <h2>Detalle de Pedidos sin cita confirmada</h2>
        <p>Estimado/ Colaborador,</p>
        <p>A continuación, se presentan los pedidos sin cita confirmada proximos a vencer</p>
        <p>Considerar que todos los pedidos mostrados son pedidos que no tienen clave de cita confirmada en B2B</p>
        <table class="table table-bordered table-hover">
            <thead>
                <tr>
                <th>Fecha Vencimiento</th>    
                <th>Pedido</th>
                <th>Estatus</th>
                    <th>Departamento</th>
                    <th>Tienda</th>
                    <th>Piezas</th>
                </tr>
            </thead>
            <tbody>';
$g_total = 0;
foreach ($obtener_datos as $key => $value) {
    $pedido = $value['pedido'];
    $departamento = $value['departamento'];
    $tda = $value['tda'];
    $estatus = $value['estatus'];
    $fecha_v = $value['fec'];
    $total = $value['total'];
    $g_total += intval($total);
    $message .= '<tr>';
    $message .= "<td>$fecha_v</td>";
    $message .= "<td>$pedido</td>";
    $message .= "<td>$estatus</td>";
    $message .= "<td>$departamento</td>";
    $message .= "<td>$tda</td>";
    $message .= "<td>$total</td>";
    $message .= '</tr>';
}


$message .= '<tr>';
$message .= "<td colspan='5'>Total: </td>";
$message .= "<td>$g_total Pzas.</td>";
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
$email->addAddress("dserrano@americancotton.com.mx");
$email->addCc("malfie@divisionfx.com.mx");
$email->addCc("analista@divisionfx.com.mx");
$email->addCc("administracion@divisionfx.com.mx");
$email->isHTML(true);

$email->Subject = "Reporte de Pedidos sin cita proximos a vencer";
$email->Body = $message;
$email->CharSet = 'UTF-8';
$email->Encoding = 'base64';
try{
    if(!empty($obtener_datos)){
        $email->send();
    }
   
}catch(Exception $e){

}



