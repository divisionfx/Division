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
$usuario  = SQL_val("SELECT id FROM USUARIOS_APP where nombre_usuario = 'PROSCAI'");

////////////////////////////////////////////////////// C O R T E ////////////////////////////////////////////////////////////////////////

$obtener_pedidos_corte = SQL_querytoarray("SELECT HPP.clave, HPP.VoBo_confeccion, EC.estilo
    FROM HANGTEN_PROGRAMAS HP
    LEFT JOIN HANGTEN_PEDIDOS HPP ON HPP.id_programa = HP.id_referencia
    INNER JOIN ESTILOS_COPPEL EC ON EC.id_estilo = HPP.id_estilo
    WHERE HP.estatus NOT IN ('Eliminado', 'Facturado') 
    AND HPP.VoBo_confeccion != 'Terminado'
");

if (empty($obtener_pedidos_corte)) {
    echo "No hay pedidos pendientes.";
    exit;
}

$estilos_modificables = array_column($obtener_pedidos_corte, 'estilo');
$estilos_modificables = implode("','", $estilos_modificables);

$obtener_avance = EXTERN_SQL_querytoarray("SELECT ILOCALIZ, PENUM,
        CASE 
            WHEN PLTIPMV = 'DF' AND PRVCOD = 'M00026' THEN 'CORTE'
            WHEN PLTIPMV = 'DF' AND PRVCOD != 'M00026' THEN 'CONFECCION'
            WHEN PLTIPMV = 'V' AND tktempl != '' THEN 'CONFECCION'
            ELSE NULL
        END AS PROCESO,
        PLSURT SURTIDO
    FROM fpenc
    LEFT JOIN fplin ON fplin.PESEQ = fpenc.PESEQ
    LEFT JOIN finv ON finv.ISEQ = fplin.ISEQ
    LEFT JOIN fprv ON fprv.PRVSEQ = fpenc.PRVSEQ
    LEFT JOIN db164divfx.ftikets ON db164divfx.ftikets.tktnumop = fpenc.penum 
        AND TKTPROD = finv.icod 
        AND tktart = '+CONFEC' 
        AND tktempl != ''
    WHERE PLTIPMV IN ('DF','V')
    AND ILOCALIZ IN ('$estilos_modificables')
");

if (empty($obtener_avance)) {
    echo "No hay avances registrados.";
    exit;
}

$avance_indexado = [];
foreach ($obtener_avance as $avance) {
    $avance_indexado[$avance['ILOCALIZ']] = $avance;
}

$id_cliente = 5;

foreach ($obtener_pedidos_corte as $pedido) {
    $clave = $pedido['clave'];
    $estilo = $pedido['estilo'];
    $proceso = 'Terminado';
    $orden = "";

    if (isset($avance_indexado[$estilo])) {
        $avance = $avance_indexado[$estilo];

        if (empty($avance['PROCESO'])) {
            $proceso = 'Pendiente';
        } elseif ($avance['SURTIDO'] == 0) {
            $proceso = 'En proceso';
        }

        $orden = $avance['PENUM'];
    }

    if ($pedido['VoBo_confeccion'] != $proceso) {
        $estatus = "Confeccionado $proceso";

        try {
  
            $actualizar_pedido = $mysqli->prepare("UPDATE HANGTEN_PEDIDOS 
                SET VoBo_confeccion = ? 
                WHERE clave = ?");
            $actualizar_pedido->bind_param("si", $proceso, $clave);
            $actualizar_pedido->execute();

            $insertar_historico = $mysqli->prepare("INSERT INTO ESTATUS_HISTORICO_PEDIDOS (clave_pedido, estatus, usuario, id_cliente) 
                VALUES (?, ?, ?, ?)");
            $insertar_historico->bind_param("isii", $clave, $estatus, $usuario, $id_cliente);
            $insertar_historico->execute();


            if (!empty($orden)) {
                $insertar_orden_estampado = $mysqli->prepare("INSERT INTO ORDENES_CONFECCION_PEDIDOS (orden_confeccion, clave_pedido, id_cliente) 
                    VALUES (?, ?, ?)");
                $insertar_orden_estampado->bind_param("sii", $orden, $clave, $id_cliente);
                $insertar_orden_estampado->execute();
            }
        } catch (mysqli_sql_exception $e) {
            echo "Error en el procesamiento del pedido $clave: " . $e->getMessage() . "<br>";
            continue;
        }
    }
}



////////////////////////////////////////////////////// C O R T E ////////////////////////////////////////////////////////////////////////


////////////////////////////////////////////////// E S T A M P A D O ////////////////////////////////////////////////////////////////////

$obtener_pedidos_estampado = SQL_querytoarray("SELECT HPP.clave, HPP.VoBo_estampado, EC.estilo
    FROM HANGTEN_PROGRAMAS HP
    LEFT JOIN HANGTEN_PEDIDOS HPP ON HPP.id_programa = HP.id_referencia
    INNER JOIN ESTILOS_COPPEL EC ON EC.id_estilo = HPP.id_estilo
    WHERE HP.estatus NOT IN ('Eliminado', 'Facturado') 
    AND HPP.VoBo_estampado != 'Terminado'");

if (empty($obtener_pedidos_estampado)) {
    echo "No hay pedidos pendientes de estampado.";
    exit;
}

// Extraer estilos sin concatenación manual
$estilos_modificables = array_column($obtener_pedidos_estampado, 'estilo');
$estilos_modificables = implode("','", $estilos_modificables);

$obtener_avance = EXTERN_SQL_querytoarray("SELECT ILOCALIZ, PENUM,
        CASE 
            WHEN PLTIPMV = 'E' THEN 'ESTAMPADO'
            WHEN PLTIPMV = 'V' AND TKTART = '+ESTAMP' AND tktempl != '' THEN 'ESTAMPADO'
            ELSE NULL
        END AS PROCESO,
        TKTSURT SURTIDO,
        (SELECT MAX(TKTDATEEND) 
         FROM fdoc
         LEFT JOIN faxinv ON faxinv.dseq = fdoc.dseq
         LEFT JOIN db164divfx.ftikets 
           ON db164divfx.ftikets.tktnumop = fpenc.penum 
           AND TKTPROD = finv.icod 
           AND tktart IN ('+ESTAMP', '+TERMIN')
         WHERE iseq = fplin.iseq 
           AND drefer = fpenc.penum
        ) AS FENTRADA
    FROM fpenc
    LEFT JOIN fplin ON fplin.PESEQ = fpenc.PESEQ
    LEFT JOIN finv ON finv.ISEQ = fplin.ISEQ
    LEFT JOIN fprv ON fprv.PRVSEQ = fpenc.PRVSEQ
    LEFT JOIN ftikets t 
      ON t.tktnumop = fpenc.penum 
      AND TKTPROD = finv.icod 
      AND tktart IN ('+ESTAMP', '+TERMIN')
    LEFT JOIN fprv prv2 ON prv2.prvcod = t.TKTMAQUINA
    WHERE PLTIPMV IN ('E', 'V')
    AND ILOCALIZ IN ('$estilos_modificables')
    GROUP BY ILOCALIZ");

if (empty($obtener_avance)) {
    echo "No hay avances registrados.";
    exit;
}

// Indexar avances por estilo para búsqueda rápida
$avance_indexado = [];
foreach ($obtener_avance as $avance) {
    $avance_indexado[$avance['ILOCALIZ']] = $avance;
}

$id_cliente = 5;

foreach ($obtener_pedidos_estampado  as $pedido) {
    $clave = $pedido['clave'];
    $estilo = $pedido['estilo'];
    $proceso = 'En proceso';
    $orden = "";

    if (isset($avance_indexado[$estilo])) {
        $avance = $avance_indexado[$estilo];

        if (!empty($avance['FENTRADA'])) {
            $proceso = 'Terminado';
            $orden = $avance['PENUM'];
        }
    }

    if ($pedido['VoBo_estampado'] != $proceso) {
        $estatus = "Estampado $proceso";

        try {
            $actualizar_pedido = $mysqli->prepare("UPDATE HANGTEN_PEDIDOS SET VoBo_estampado = ? WHERE clave = ?");
            $actualizar_pedido->bind_param("si", $proceso, $clave);
            $actualizar_pedido->execute();


            $insertar_historico = $mysqli->prepare("INSERT INTO ESTATUS_HISTORICO_PEDIDOS (clave_pedido, estatus, usuario, id_cliente) 
                VALUES (?, ?, ?, ?)");
            $insertar_historico->bind_param("isii", $clave, $estatus, $usuario, $id_cliente);
            $insertar_historico->execute();

            // Insertar orden si existe
            if (!empty($orden)) {
                $insertar_orden_estampado = $mysqli->prepare("INSERT INTO ORDENES_ESTAMPADO_PEDIDOS (orden_estampado, clave_pedido, id_cliente) 
                    VALUES (?, ?, ?)");
                $insertar_orden_estampado->bind_param("sii", $orden, $clave, $id_cliente);
                $insertar_orden_estampado->execute();
            }
        } catch (mysqli_sql_exception $e) {
            echo "Error en el procesamiento del pedido $clave: " . $e->getMessage() . "<br>";
            continue;
        }
    }
}


////////////////////////////////////////////////// E S T A M P A D O ////////////////////////////////////////////////////////////////////


////////////////////////////////////////////////// F A C T U R A D O ////////////////////////////////////////////////////////////////////

$obtener_pedidos_factura = SQL_querytoarray("SELECT HPP.clave, HPP.VoBo_facturacion, EC.estilo
    FROM HANGTEN_PROGRAMAS HP
    LEFT JOIN HANGTEN_PEDIDOS HPP ON HPP.id_programa = HP.id_referencia
    INNER JOIN ESTILOS_COPPEL EC ON EC.id_estilo = HPP.id_estilo
    WHERE HP.estatus NOT IN ('Eliminado', 'Facturado') 
    AND HPP.VoBo_facturacion != 'Terminado'");

if (empty($obtener_pedidos_factura)) {
    echo "No hay pedidos pendientes de facturación.";
    exit;
}

// Extraer estilos de pedidos sin concatenación manual
$estilos_modificables = array_column($obtener_pedidos_factura, 'estilo');
$estilos_modificables = implode("','", $estilos_modificables);

$obtener_consignacion = EXTERN_SQL_querytoarray("SELECT 
        WEEKOFYEAR(DFECHA) AS SEMANA, DFECHA, clicod, clinom, 
        dnum AS FACTURA, icod, ilocaliz, iean, aicantf, 
        peobs, coml1, aitipmv
    FROM fdoc
    LEFT JOIN faxinv ON faxinv.dseq = fdoc.dseq
    LEFT JOIN finv ON finv.iseq = faxinv.iseq
    LEFT JOIN fplin ON fplin.iseq = finv.iseq
    LEFT JOIN fpenc ON fpenc.peseq = fplin.peseq
    LEFT JOIN fcli ON fcli.cliseq = fdoc.cliseq
    LEFT JOIN fcoment ON fcoment.comseqfact = fdoc.dseq
    WHERE aitipmv IN ('MC', 'F')
    AND ilocaliz IN ('$estilos_modificables')");

if (empty($obtener_consignacion)) {
    echo "No hay consignaciones registradas.";
    exit;
}

// Indexar avances por estilo para búsqueda rápida
$avance_indexado = [];
foreach ($obtener_consignacion as $avance) {
    $avance_indexado[$avance['ilocaliz']] = $avance;
}

$id_cliente = 5;

foreach ($obtener_pedidos_factura as $pedido) {
    $clave = $pedido['clave'];
    $estilo = $pedido['estilo'];
    $proceso = 'Terminado';
    $factura = "";

    if (isset($avance_indexado[$estilo])) {
        $factura = $avance_indexado[$estilo]['FACTURA'];
    }

    if ($pedido['VoBo_facturacion'] != $proceso) {
        $estatus = "Facturado $proceso";

        try {
           
            $actualizar_pedido = $mysqli->prepare("UPDATE HANGTEN_PEDIDOS 
                SET VoBo_facturacion = ? 
                WHERE clave = ?");
            $actualizar_pedido->bind_param("si", $proceso, $clave);
            $actualizar_pedido->execute();

            
            $insertar_historico = $mysqli->prepare("INSERT INTO ESTATUS_HISTORICO_PEDIDOS (clave_pedido, estatus, usuario, id_cliente) 
                VALUES (?, ?, ?, ?)");
            $insertar_historico->bind_param("isii", $clave, $estatus, $usuario, $id_cliente);
            $insertar_historico->execute();

            if (!empty($factura)) {
                $insertar_factura_pedido = $mysqli->prepare("INSERT INTO FACTURAS_PEDIDOS (orden_factura, clave_pedido, id_cliente) 
                    VALUES (?, ?, ?)");
                $insertar_factura_pedido->bind_param("sii", $factura, $clave, $id_cliente);
                $insertar_factura_pedido->execute();
            }
        } catch (mysqli_sql_exception $e) {
            echo "Error en el procesamiento del pedido $clave: " . $e->getMessage() . "<br>";
            continue;
        }
    }
}


////////////////////////////////////////////////// F A C T U R A D O ////////////////////////////////////////////////////////////////////



////////////////////////////////////////////////// P R O G R A M A S ////////////////////////////////////////////////////////////////////

$json = file_get_contents('https://produccion.appeuro.mx/europroduccion/Doc/Json/EstadoProgramaControl.json');

// Decodificar el JSON en un arreglo asociativo
$datos = json_encode($json, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
$cadena = json_decode($datos, true);
$cadena_limpia = preg_replace('/^[\pZ\pC]+/u', '', $cadena);
// Decodifica la cadena JSON en un arreglo asociativo
$datos_json = json_decode($cadena_limpia, true);
$usuario  = SQL_val("SELECT id FROM USUARIOS_APP where nombre_usuario = 'EUROPRODUCCION'");
if(!empty($datos_json)){
  foreach ($datos_json as $key => $value) {
    $clave= $value['clave'];   
    $estado = $value['estado'];
		$pedido = $value['Pedido'];
    $cli = $value['id_cliente'];

    if($cli == 5 ){
      $obtener_estatus_pedido = SQL_val("SELECT VoBo_tela FROM HANGTEN_PEDIDOS WHERE clave = $clave");

      if($obtener_estatus_pedido != $estado){
			
        $estatus = "El estatus de tela fue cambiado a $estado";
        $actualizar_estatus = $mysqli->prepare("UPDATE HANGTEN_PEDIDOS SET VoBo_tela = ? WHERE clave = ?");
        $actualizar_estatus->bind_param("si", $estado,$clave);
        try{
          $actualizar_estatus->execute();
          echo "Se actualizó el estado del pedido $clave";
          echo " || ";
          $insertar_historico = $mysqli->prepare("INSERT INTO ESTATUS_HISTORICO_PEDIDOS (clave_pedido, estatus, usuario) VALUES (?,?,?)");
          $insertar_historico->bind_param("isi", $clave, $estatus, $usuario);
  
          try{
            $insertar_historico->execute();
            echo "Se actualizó el historico del pedido $clave";
            echo " || ";
          }catch(mysqli_sql_exception $e){
            echo "Error hisotorico: ".$e->getMessage();
          }
          
          $orden = $value['Pedido'];
          if($orden != 'N/D'){
            
            $insertar_orden = $mysqli->prepare("INSERT INTO ORDENES_TELA_PEDIDOS (orden_tela, clave_pedido) VALUES (?,?)");
            $insertar_orden->bind_param("si", $orden, $clave);
            try{
              $insertar_orden->execute();
              echo "Se le asignó una orden a la tela con el estado $estado";
              echo " || ";
            }catch(mysqli_sql_exception $e){
              echo "Error tela: ".$e->getMessage();
            }
          }
            
          
        }catch(mysqli_sql_exception $e){
          echo "Ocurrio un error: ".$e->getMessage();
        }
      }
    }

    
    
  }
}



/////////////////////////////////////////////////////// R A I Z /////////////////////////////////////////////////////////////////////////

$obtener_pedidos_raiz = SQL_querytoarray("SELECT HP.clave, HP.estilo, CPP.codigo, CPP.id_cliente 
    FROM HANGTEN_PEDIDOS HP
    LEFT JOIN CODIGOS_PROSCAI_PRODUCTO CPP ON CPP.clave_pedido = HP.clave
    WHERE CPP.codigo IS NULL
    GROUP BY HP.clave");

if (empty($obtener_pedidos_raiz)) {
    echo "No hay pedidos sin código asignado.";
    exit;
}

$estilos_modificables = array_column($obtener_pedidos_raiz, 'estilo');
$estilos_modificables = implode("','", $estilos_modificables);

$obtener_codigos = EXTERN_SQL_querytoarray("SELECT ilocaliz, icod, iean 
    FROM finv 
    WHERE iean != '' 
    AND ilocaliz IN ('$estilos_modificables')");

if (empty($obtener_codigos)) {
    echo "No hay códigos disponibles para los estilos en finv.";
    exit;
}

$codigos_indexados = [];
foreach ($obtener_codigos as $codigo) {
    $codigos_indexados[$codigo['ilocaliz']] = $codigo['icod'];
}

$id_cliente = 5;

foreach ($obtener_pedidos_raiz as $pedido) {
    $clave = $pedido['clave'];
    $estilo = $pedido['estilo'];

    if (isset($codigos_indexados[$estilo])) {
        $codigo = $codigos_indexados[$estilo];

        try {
            $verificar_existencia = $mysqli->prepare("SELECT COUNT(*) FROM CODIGOS_PROSCAI_PRODUCTO 
                WHERE codigo = ? AND clave_pedido = ?");
            $verificar_existencia->bind_param("si", $codigo, $clave);
            $verificar_existencia->execute();
            $verificar_existencia->bind_result($existe);
            $verificar_existencia->fetch();
            $verificar_existencia->close();

            if ($existe == 0) {
                $insertar_codigo = $mysqli->prepare("INSERT INTO CODIGOS_PROSCAI_PRODUCTO (codigo, clave_pedido, id_cliente) 
                    VALUES (?, ?, ?)");
                $insertar_codigo->bind_param("sii", $codigo, $clave, $id_cliente);
                $insertar_codigo->execute();
                echo "Código $codigo insertado exitosamente para el pedido $clave. <br>";
            } else {
                echo "El código $codigo ya existe para el pedido $clave. <br>";
            }
        } catch (mysqli_sql_exception $e) {
            echo "Error en la inserción del código para el pedido $clave: " . $e->getMessage() . "<br>";
        }
    }
}


/////////////////////////////////////////////////////// R A I Z /////////////////////////////////////////////////////////////////////////