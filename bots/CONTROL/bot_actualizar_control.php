<?php 
$host = 'oms.appeuro.mx';
$usuario = 'marco';
$bd = 'gpodsw_pruebas';
// $bd = 'gpodsw';
$contraseña = 'cv%j?y8=GI8h';
$port = '6033';

$mysqli = new mysqli($host, $usuario, $contraseña, $bd, $port);

$extern_host = 'oms.appeuro.mx';
$extern_usuario = 'divfapp';
$extern_bd = 'db164divfx';
$extern_contraseña = 'sDf4&8dH%3wEf&#';
$extern_port = '6033';


$extern_mysqli = new mysqli($extern_host, $extern_usuario, $extern_contraseña, $extern_bd, $extern_port);

$externac_host = 'oms.appeuro.mx';
$externac_usuario = 'marco';
$externac_bd = 'db164american';
$externac_contraseña = 'cv%j?y8=GI8h';
$externac_port = '6033';


$externac_mysqli = new mysqli($externac_host, $externac_usuario, $externac_contraseña, $externac_bd, $externac_port);


$externhg_host = 'oms.appeuro.mx';
$externhg_usuario = 'marco';
$externhg_bd = 'handheld';
$externhg_contraseña = 'cv%j?y8=GI8h';
$externhg_port = '6033';


$externhg_mysqli = new mysqli($externhg_host, $externhg_usuario, $externhg_contraseña, $externhg_bd, $externhg_port);


function SQL_val($query, $default = ""){
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

function EXTERN_SQL_val($query, $default = "")
{
	global $extern_mysqli;

	$result = $extern_mysqli->query($query);
	if (!$result) {
		$sReturn = $default;
	} else {
		$row = $result->fetch_array();
		$sReturn = $row[0];
	}
	return $sReturn;
}
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

set_time_limit(0);
$usuario = SQL_val("SELECT id FROM USUARIOS_APP WHERE nombre_usuario = 'PROSCAI'");

////////////////////////////////////////////////////// C O R T E ////////////////////////////////////////////////////////////////////////

$obtener_pedidos_corte = SQL_querytoarray("SELECT PEDIDOS.*, ESTILOS.estilo 
    FROM PEDIDOS 
    INNER JOIN ESTILOS ON ESTILOS.id_estilos = PEDIDOS.id_estilos
    WHERE PEDIDOS.estatus NOT IN ('Eliminado', 'Facturado') 
    AND VoBo_confeccion != 'Terminado'");

$estilos_corte = implode(",", array_map(fn($pedido) => "'{$pedido['estilo']}'", $obtener_pedidos_corte));

$obtener_avance_corte = EXTERN_SQL_querytoarray("SELECT
    CASE WHEN PLTIPMV IN ('DF', 'V') THEN WEEKOFYEAR(PEFECHA) END AS SCORTE,
    CASE WHEN PLTIPMV IN ('DF', 'V') THEN PEFECHA END AS FCORTE,
    CASE WHEN PLTIPMV = 'DF' THEN WEEKOFYEAR(PEVENCE) WHEN PLTIPMV = 'V' THEN WEEKOFYEAR(TKTDATE) END AS SMAQUILA,
    CASE WHEN PLTIPMV = 'DF' THEN PEVENCE WHEN PLTIPMV = 'V' THEN TKTDATE END AS FMAQUILA,
    CASE 
        WHEN (PLTIPMV = 'DF' AND PRVCOD = 'M00026') THEN 'CORTE'
        WHEN (PLTIPMV = 'DF' AND PRVCOD != 'M00026') THEN 'CONFECCION'
        WHEN (PLTIPMV = 'V' AND tktempl != '') THEN 'CONFECCION'
    END AS PROCESO,
    PRVCOD, PRVNOM, PENUM, ICOD, ILOCALIZ, IEAN,
    PLCANT AS SOLICITADO, PLSURT AS SURTIDO, (PLCANT - PLSURT) AS RESTANTE,
    IF(PLSURT = 0, '', (SELECT MAX(dfecha) FROM fdoc 
        LEFT JOIN faxinv ON faxinv.dseq = fdoc.dseq 
        WHERE iseq = fplin.iseq AND drefer = fpenc.penum)) AS FENTRADA
FROM fpenc
LEFT JOIN fplin ON fplin.PESEQ = fpenc.PESEQ
LEFT JOIN finv ON finv.ISEQ = fplin.ISEQ
LEFT JOIN fprv ON fprv.PRVSEQ = fpenc.PRVSEQ
LEFT JOIN db164divfx.ftikets ON db164divfx.ftikets.tktnumop = db164divfx.fpenc.penum 
    AND TKTPROD = finv.icod 
    AND tktart IN ('+CONFEC', '+TERMIN') 
    AND tktempl != ''
WHERE PLTIPMV IN ('DF', 'V')
AND ILOCALIZ IN ($estilos_corte)");

$actualizar_pedido_corte = [];

foreach ($obtener_pedidos_corte as $pedido) {
    $clave = $pedido['clave'];
    $cliente = 1;
    $proceso = 'Terminado';
    $orden = "";

    foreach ($obtener_avance_corte as $avance) {
        if ($avance["ILOCALIZ"] == $pedido['estilo']) {
            $proceso = $avance['PROCESO'] ?? 'Pendiente';
            if ($avance['SURTIDO'] == 0) {
                $proceso = 'En proceso';
            }
            $orden = $avance['PENUM'];

            if ($pedido['VoBo_confeccion'] != $proceso) {
                $actualizar_pedido_corte[] = "UPDATE PEDIDOS SET VoBo_confeccion = '$proceso' WHERE clave = $clave;";
                $estatus = "Confeccion $proceso";
                $actualizar_pedido_corte[] = "INSERT IGNORE INTO ESTATUS_HISTORICO_PEDIDOS (clave_pedido, estatus, usuario, id_cliente) 
                    VALUES ($clave, '$estatus', $usuario, $cliente);";
                
                if ($orden) {
                    $actualizar_pedido_corte[] = "INSERT IGNORE INTO ORDENES_CONFECCION_PEDIDOS (orden_confeccion, clave_pedido, id_cliente) 
                        VALUES ('$orden', '$clave', '$cliente');";
                }
            }
        }
    }
}

try {
    if (!empty($actualizar_pedido_corte)) {
        $mysqli->multi_query(implode(" ", $actualizar_pedido_corte));
    }
    echo "Éxito";
} catch (mysqli_sql_exception $e) {
    echo "Error " . $e->getMessage();
}

////////////////////////////////////////////////////// C O R T E ////////////////////////////////////////////////////////////////////////


////////////////////////////////////////////////// E S T A M P A D O ////////////////////////////////////////////////////////////////////

$obtener_pedidos_estampado = SQL_querytoarray("SELECT PEDIDOS.*, ESTILOS.estilo 
    FROM PEDIDOS 
    INNER JOIN ESTILOS ON ESTILOS.id_estilos = PEDIDOS.id_estilos
    WHERE PEDIDOS.estatus NOT IN ('Eliminado', 'Facturado') 
    AND VoBo_estampado NOT IN ('Terminado')");

    $estilos_array = [];

    if (!empty($obtener_pedidos_estampado) && is_array($obtener_pedidos_estampado)) {
        foreach ($obtener_pedidos_estampado as $pedido) {
            if (!empty($pedido['estilo'])) {
                $estilos_array[] = "'{$pedido['estilo']}'";
            }
        }
    } else {
        echo "No se encontraron pedidos con estampado pendiente.";
    }

    $estilos_estampado = !empty($estilos_array) ? implode(",", $estilos_array) : "''";

$obtener_avance_estampado = EXTERN_SQL_querytoarray("SELECT
    CASE WHEN PLTIPMV IN ('E', 'V') THEN WEEKOFYEAR(PEFECHA) END AS SCORTE,
    CASE WHEN PLTIPMV IN ('E', 'V') THEN PEFECHA END AS FCORTE,
    CASE WHEN PLTIPMV = 'E' THEN WEEKOFYEAR(PEVENCE) WHEN PLTIPMV = 'V' THEN WEEKOFYEAR(TKTINICIO) END AS SMAQUILA,
    CASE WHEN PLTIPMV = 'E' THEN PEVENCE WHEN PLTIPMV = 'V' THEN TKTINICIO END AS FMAQUILA,
    CASE 
        WHEN PLTIPMV = 'E' THEN 'ESTAMPADO' 
        WHEN PLTIPMV = 'V' AND TKTART = '+ESTAMP' AND tktempl != '' THEN 'ESTAMPADO' 
    END AS PROCESO,
    IF(PLTIPMV = 'V', TKTMAQUINA, fprv.PRVCOD) AS PRVCOD,
    IF(PLTIPMV = 'V', prv2.PRVNOM, fprv.PRVNOM) AS PRVNOM,
    PENUM, ICOD, ILOCALIZ, IEAN,
    PLCANT AS SOLICITADO, TKTSURT AS SURTIDO, (TKTCANT - TKTSURT) AS RESTANTE,
    IF(TKTSURT = 0, '', (SELECT MAX(TKTDATEEND) FROM fdoc
        LEFT JOIN faxinv ON faxinv.dseq = fdoc.dseq
        LEFT JOIN db164divfx.ftikets ON db164divfx.ftikets.tktnumop = db164divfx.fpenc.penum 
            AND TKTPROD = finv.icod 
            AND TKTART IN('+ESTAMP', '+TERMIN')
        WHERE iseq = fplin.iseq AND drefer = fpenc.penum)) AS FENTRADA
FROM fpenc
LEFT JOIN fplin ON fplin.PESEQ = fpenc.PESEQ
LEFT JOIN finv ON finv.ISEQ = fplin.ISEQ
LEFT JOIN fprv ON fprv.PRVSEQ = fpenc.PRVSEQ
LEFT JOIN ftikets t ON t.tktnumop = fpenc.penum 
    AND TKTPROD = finv.icod 
    AND TKTART IN('+ESTAMP', '+TERMIN')
LEFT JOIN fprv prv2 ON prv2.prvcod = t.TKTMAQUINA
WHERE PLTIPMV IN ('E', 'V') 
AND ILOCALIZ IN ($estilos_estampado) 
GROUP BY ILOCALIZ");

$actualizar_pedido_estampado = [];

foreach ($obtener_pedidos_estampado as $pedido) {
$clave = $pedido['clave'];
    $cliente = 1;
    $proceso = 'En proceso';
    $orden = '';

    foreach ($obtener_avance_estampado as $avance) {
        if ($avance["ILOCALIZ"] == $pedido['estilo']) {
            $proceso = empty($avance['FENTRADA']) ? 'En proceso' : 'Terminado';
            $orden = $avance['PENUM'];

            if ($pedido['VoBo_estampado'] !== $proceso) {
                $actualizar_pedido_estampado[] = "UPDATE PEDIDOS SET VoBo_estampado = '$proceso' WHERE clave = $clave;";
                $estatus = "Estampado $proceso";
                $actualizar_pedido_estampado[] = "INSERT IGNORE INTO ESTATUS_HISTORICO_PEDIDOS (clave_pedido, estatus, usuario, id_cliente) 
                    VALUES ($clave, '$estatus', $usuario, $cliente);";
                
                if ($orden) {
                    $actualizar_pedido_estampado[] = "INSERT IGNORE INTO ORDENES_ESTAMPADO_PEDIDOS (orden_estampado, clave_pedido, id_cliente) 
                        VALUES ('$orden', $clave, $cliente);";
                }
            }
        }
    }
}

try {
    if (!empty($actualizar_pedido_estampado)) {
        $mysqli->multi_query(implode(" ", $actualizar_pedido_estampado));
    }
    echo "Éxito";
} catch (mysqli_sql_exception $e) {
    echo "Error " . $e->getMessage();
}

////////////////////////////////////////////////// E S T A M P A D O ////////////////////////////////////////////////////////////////////


////////////////////////////////////////////////// F A C T U R A D O ////////////////////////////////////////////////////////////////////

$obtener_pedidos_factura = SQL_querytoarray("SELECT PEDIDOS.*, ESTILOS.estilo 
    FROM PEDIDOS 
    INNER JOIN ESTILOS ON ESTILOS.id_estilos = PEDIDOS.id_estilos
    WHERE PEDIDOS.estatus NOT IN ('Eliminado', 'Facturado') 
    AND VoBo_facturacion != 'Terminado'");

if (!is_array($obtener_pedidos_factura) || empty($obtener_pedidos_factura)) {
    echo "No hay pedidos con factura.";
    exit;
}

$estilos_factura = implode(",", array_map(fn($pedido) => isset($pedido['estilo']) ? "'{$pedido['estilo']}'" : "NULL", $obtener_pedidos_factura));


$obtener_avance_factura = EXTERN_SQL_querytoarray("SELECT 
    ilocaliz, WEEKOFYEAR(DFECHA) AS SEMANA, DFECHA, clicod, clinom, dnum, icod, iean, aicantf  
FROM fdoc
LEFT JOIN faxinv ON faxinv.dseq = fdoc.dseq
LEFT JOIN finv ON finv.iseq = faxinv.iseq
LEFT JOIN fcli ON fcli.cliseq = fdoc.cliseq
WHERE aitipmv = 'F' 
AND ilocaliz IN ($estilos_factura) 
GROUP BY ilocaliz");

$actualizar_pedido_factura = [];

foreach ($obtener_pedidos_factura as $pedido) {
    foreach ($obtener_avance_factura as $avance) {
        if ($avance["ilocaliz"] == $pedido['estilo']) {
            $clave = $pedido['clave'];
            $cliente = 1;
            $proceso = 'Terminado';
            $factura = $avance['dnum'] ?? '';

            if ($pedido['VoBo_facturacion'] !== $proceso) {
                $estatus = "Facturado $proceso";

                $actualizar_pedido_factura[] = "UPDATE PEDIDOS SET VoBo_facturacion = '$proceso' WHERE clave = $clave;";
                $actualizar_pedido_factura[] = "INSERT IGNORE INTO ESTATUS_HISTORICO_PEDIDOS (clave_pedido, estatus, usuario, id_cliente) 
                    VALUES ($clave, '$estatus', $usuario, $cliente);";

                if (!empty($factura)) {
                    $actualizar_pedido_factura[] = "INSERT IGNORE INTO FACTURAS_PEDIDOS (orden_factura, clave_pedido, id_cliente) 
                        VALUES ('$factura', $clave, $cliente);";
                }
            }
        }
    }
}

try {
    if (!empty($actualizar_pedido_factura)) {
        $mysqli->multi_query(implode(" ", $actualizar_pedido_factura));
    }
    echo "Éxito";
} catch (mysqli_sql_exception $e) {
    echo "Error " . $e->getMessage();
}

////////////////////////////////////////////////// F A C T U R A D O ////////////////////////////////////////////////////////////////////

