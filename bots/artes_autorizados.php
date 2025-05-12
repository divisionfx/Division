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




$obtener_h = SQL_querytoarray("SELECT P.cambios_estampado,PP.programa,P.clave,E.estilo,EHP.estatus,P.observaciones_estampado FROM ESTATUS_HISTORICO_PEDIDOS EHP 
LEFT JOIN PEDIDOS P ON P.clave = EHP.clave_pedido
LEFT JOIN PROGRAMAS_PEDIDOS PP ON PP.id_programa = P.id_programa
LEFT JOIN ESTILOS E ON E.id_estilos = P.id_estilos
Where fecha_cambio_estatus > date(now()) and clave != ''  and clave != '' and EHP.estatus like '%El arte del pedido%'");



$cuerpo = "";
$contador = 0; 
foreach ($obtener_h as $key => $value) {

    $programa  =$value['programa'];
    $estilo = $value['estilo'];
    $observacion = $value['observaciones_estampado'];
    $cambios = $value['cambios_estampado'];
    $cuerpo .= '<tr>';
    $cuerpo .= "<td style='padding: 0.75rem; border: 1px solid #dee2e6;'>$programa</td>";
    $cuerpo .= "<td style='padding: 0.75rem; border: 1px solid #dee2e6;'>$estilo</td>";
    $cuerpo .= "<td style='padding: 0.75rem; border: 1px solid #dee2e6;'>$cambios</td>";
    if($observacion != ''){
        $cuerpo .= "<td style='padding: 0.75rem; border: 1px solid #dee2e6;'>$observacion</td>";
    }else{
        $cuerpo .= "<td style='padding: 0.75rem; border: 1px solid #dee2e6;'> </td>";
    }
    $cuerpo .= '</tr>';
    $contador++;
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
                <h1 style="color: #083666;">Artes autorizados del dia</h1>
                <p>Estimado Colaborador,</p>
                <p>A continuación, se presentan los estilos que ya cuentan con artes autorizados el dia de hoy '.date("m-d-Y").'</p>
            </div>
        </div>
        <table style="width: 100%; margin-bottom: 1rem; background-color: transparent; border-collapse: collapse;">
            <thead>
                <tr>
                    <th style="background: #083666; color: #ffffff; padding: 0.75rem; border: 1px solid #dee2e6;">Programa</th>
                    <th style="background: #083666; color: #ffffff; padding: 0.75rem; border: 1px solid #dee2e6;">Estilo</th>
                    <th style="background: #083666; color: #ffffff; padding: 0.75rem; border: 1px solid #dee2e6;">Cambio</th>
                    <th style="background: #083666; color: #ffffff; padding: 0.75rem; border: 1px solid #dee2e6;">Observaciones</th>
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
    $email->addAddress("desarrollo@divisionfx.com.mx");
    $email->addAddress("malfie@divisionfx.com.mx");
    $email->addAddress("calvarez@divisionfx.com.mx");
    $email->addAddress("ymedina@americancotton.com.mx");
    $email->addAddress("administracion@americancotton.com.mx");
    $email->addAddress("ymillan@americancotton.com.mx");
    $email->addAddress("mchavez@americancotton.com.mx");


    $email->isHTML(true);
    $email->Subject = "Artes autorizados del día ".date("m-d-Y")."";
    $email->Body = $message;

    $email->CharSet = 'UTF-8';
    $email->Encoding = 'base64';
    if($contador != 0){
        $email->send();
        echo 'El correo ha sido enviado con éxito.';
    }else{
        echo 'No hay artes autorizados';
    }
   
} catch (Exception $e) {
    echo "El correo no pudo ser enviado. Error: $e";
}

