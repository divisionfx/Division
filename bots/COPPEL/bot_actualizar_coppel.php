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

function VerificarConfeccionCoppel($estilo, $programa)
{
    $sql = "SELECT
        WEEKOFYEAR(
            CASE WHEN PLTIPMV IN ('DF', 'DC', 'V') THEN PEFECHA END
        ) AS SCORTE,
        CASE WHEN PLTIPMV IN ('DF', 'DC', 'V') THEN PEFECHA END AS FCORTE,
        WEEKOFYEAR(
            CASE 
                WHEN PLTIPMV IN ('DF', 'DC') THEN PEVENCE
                WHEN PLTIPMV = 'V' THEN TKTDATE 
            END
        ) AS SMAQUILA,
        CASE 
            WHEN PLTIPMV IN ('DF', 'DC') THEN PEVENCE
            WHEN PLTIPMV = 'V' THEN TKTDATE
        END AS FMAQUILA,
        CASE 
            WHEN PLTIPMV IN ('DF', 'DC') AND PRVCOD = 'M00026' THEN 'CORTE'
            WHEN PLTIPMV IN ('DF', 'DC') AND PRVCOD != 'M00026' THEN 'CONFECCION'
            WHEN PLTIPMV = 'V' AND tktempl != '' THEN 'CONFECCION'
        END AS PROCESO,
        peobs AS PROGRAMA,
        PRVCOD, PRVNOM, PENUM, ICOD, ILOCALIZ, IEAN,
        PLCANT AS SOLICITADO, PLSURT AS SURTIDO, (PLCANT - PLSURT) AS RESTANTE,
        IF(PLSURT = 0, '', 
            (SELECT MAX(dfecha) FROM fdoc 
            LEFT JOIN faxinv ON faxinv.dseq = fdoc.dseq 
            WHERE iseq = fplin.iseq AND drefer = fpenc.penum)
        ) AS FENTRADA
    FROM fpenc
    LEFT JOIN fplin ON fplin.PESEQ = fpenc.PESEQ
    LEFT JOIN finv ON finv.ISEQ = fplin.ISEQ
    LEFT JOIN fprv ON fprv.PRVSEQ = fpenc.PRVSEQ
    LEFT JOIN db164divfx.ftikets 
        ON db164divfx.ftikets.tktnumop = db164divfx.fpenc.penum 
        AND TKTPROD = finv.icod 
        AND tktart IN ('+CONFEC', '+TERMIN') 
        AND tktempl != ''
    WHERE PLTIPMV IN ('DF', 'V', 'DC') 
        AND ILOCALIZ LIKE '%$estilo%' 
        AND peobs LIKE '%$programa%' 
        AND PLCANT != 0";

    $obtener_avance = EXTERN_SQL_querytoarray($sql);

    $proceso = 'Pendiente';
    $orden = '';

    foreach ($obtener_avance as $value) {
        if ($value['SURTIDO'] == 0) {
            $proceso = 'En proceso';
            break;
        }
        $proceso = 'Terminado';
        $orden = $value['PENUM'];
    }

    return ["proceso" => $proceso, "orden" => $orden];
}

// PROCESO DE CORTE
$obtener_pedidos = SQL_querytoarray("SELECT 
    EC.estilo, 
    CONCAT_WS(' ', PC.nombre_programa, PC.consecutivo_programa) AS programa,  
    PC.consecutivo_programa, 
    PEC.*
FROM PEDIDOS_COPPEL PEC
LEFT JOIN ARTICULOS_PEDIDOS_COPPEL APC ON APC.clave_pedido = PEC.clave_pedido
LEFT JOIN PROGRAMAS_COPPEL PC ON PC.id_programa = APC.id_programa 
LEFT JOIN ESTILOS_COPPEL EC ON EC.id_estilo = PEC.id_estilo
WHERE PC.estado NOT IN ('Eliminado') 
    AND estatus_confeccion != 'Terminado' 
GROUP BY PEC.clave_pedido");

foreach ($obtener_pedidos as $value) {
    $clave = $value['clave_pedido'];
    $cliente = 3;
    $respuessta_confeccion = VerificarConfeccionCoppel($value['estilo'], $value['consecutivo_programa']);

    if (!empty($respuessta_confeccion)) {
        $estado_envivo = $respuessta_confeccion['proceso'];
        $orden = $respuessta_confeccion['orden'];

        if ($value['estatus_confeccion'] != $estado_envivo) {
            try {
                $actualizar_pedido = $mysqli->prepare("UPDATE PEDIDOS_COPPEL SET estatus_confeccion = ? WHERE clave_pedido = ?");
                $actualizar_pedido->bind_param("si", $estado_envivo, $clave);
                $actualizar_pedido->execute();

                $estatus = "Confeccionado $estado_envivo";
                echo "<br>Se actualizó el estatus de confección del pedido $clave";

                $insertar_historico = $mysqli->prepare("INSERT INTO ESTATUS_HISTORICO_PEDIDOS (clave_pedido, estatus, usuario, id_cliente) VALUES (?, ?, ?, ?)");
                $insertar_historico->bind_param("isii", $clave, $estatus, $usuario, $cliente);
                $insertar_historico->execute();
                echo "<br>Se insertó el histórico de la orden";

                if (!empty($orden)) {
                    $insertar_orden_estampado = $mysqli->prepare("INSERT INTO ORDENES_CONFECCION_PEDIDOS (orden_confeccion, clave_pedido, id_cliente) VALUES (?, ?, ?)");
                    $insertar_orden_estampado->bind_param("sii", $orden, $clave, $cliente);
                    $insertar_orden_estampado->execute();
                    echo "<br>Se insertó una orden en la confección";
                }
            } catch (mysqli_sql_exception $e) {
                echo "<br>Error: " . $e->getMessage();
            }
        }
    } else {
        echo "<br>No se encontró nada";
    }
}


////////////////////////////////////////////////////// C O R T E ////////////////////////////////////////////////////////////////////////


////////////////////////////////////////////////// E S T A M P A D O ////////////////////////////////////////////////////////////////////

$obtener_pedidos_estampado = SQL_querytoarray("SELECT 
        EC.estilo,
        CONCAT_WS(' ', PC.nombre_programa, PC.consecutivo_programa) AS programa, 
        PC.consecutivo_programa, 
        PEC.* 
    FROM PEDIDOS_COPPEL PEC
    LEFT JOIN ARTICULOS_PEDIDOS_COPPEL APC ON APC.clave_pedido = PEC.clave_pedido
    LEFT JOIN PROGRAMAS_COPPEL PC ON PC.id_programa = APC.id_programa 
    LEFT JOIN ESTILOS_COPPEL EC ON EC.id_estilo = PEC.id_estilo
    WHERE PC.estado NOT IN ('Eliminado') 
    AND estatus_estampado != 'Terminado' 
    GROUP BY PEC.clave_pedido
");

function VerificarEstampadoCoppel($estilo, $programa) {
    $query = "SELECT
            CASE WHEN PLTIPMV IN ('E', 'V') THEN WEEKOFYEAR(PEFECHA) END AS SCORTE,
            CASE WHEN PLTIPMV IN ('E', 'V') THEN PEFECHA END AS FCORTE,
            CASE 
                WHEN PLTIPMV = 'E' THEN WEEKOFYEAR(PEVENCE) 
                WHEN PLTIPMV = 'V' THEN WEEKOFYEAR(TKTINICIO) 
            END AS SMAQUILA,
            CASE 
                WHEN PLTIPMV = 'E' THEN PEVENCE
                WHEN PLTIPMV = 'V' THEN TKTINICIO
            END AS FMAQUILA,
            CASE 
                WHEN PLTIPMV = 'E' THEN 'ESTAMPADO'
                WHEN PLTIPMV = 'V' AND TKTART = '+ESTAMP' AND tktempl != '' THEN 'ESTAMPADO'
            END AS PROCESO,
            peobs AS PROGRAMA,
            IF(PLTIPMV = 'V', TKTMAQUINA, fprv.PRVCOD) AS PRVCOD,
            IF(PLTIPMV = 'V', prv2.prvnom, fprv.prvnom) AS PRVNOM,
            PENUM, ICOD, ILOCALIZ, IEAN,
            PLCANT AS SOLICITADO, TKTCANT AS SURTIDO, (PLCANT - TKTCANT) AS RESTANTE,
            IF(TKTSURT = 0, '', 
                (SELECT MAX(TKTDATEEND) 
                 FROM fdoc
                 LEFT JOIN faxinv ON faxinv.dseq = fdoc.dseq
                 LEFT JOIN db164divfx.ftikets 
                    ON db164divfx.ftikets.tktnumop = db164divfx.fpenc.penum 
                    AND TKTPROD = finv.icod 
                    AND tktart IN('+ESTAMP', '+TERMIN')
                 WHERE iseq = fplin.iseq 
                 AND drefer = fpenc.penum)
            ) AS FENTRADA
        FROM fpenc
        LEFT JOIN fplin ON fplin.PESEQ = fpenc.PESEQ
        LEFT JOIN finv ON finv.ISEQ = fplin.ISEQ
        LEFT JOIN fprv ON fprv.PRVSEQ = fpenc.PRVSEQ
        LEFT JOIN ftikets t ON t.tktnumop = fpenc.penum 
            AND TKTPROD = finv.icod 
            AND tktart IN('+ESTAMP')
        LEFT JOIN fprv prv2 ON prv2.prvcod = t.TKTMAQUINA
        WHERE PLTIPMV IN ('E', 'V') 
        AND ILOCALIZ LIKE '%$estilo%' 
        AND peobs LIKE '%$programa%' 
        AND PLCANT != 0";

    $obtener_avance = EXTERN_SQL_querytoarray($query);

    if (!empty($obtener_avance)) {
        $proceso = 'Pendiente';
        $orden = "";
        
        foreach ($obtener_avance as $value) {
            if ($value['SURTIDO'] == 0) {
                $proceso = 'En proceso';
                break;
            } else {
                $proceso = 'Terminado';
            }
            $orden = $value['PENUM'];
        }
    } else {
        $proceso = 'Pendiente';
        $orden = '';
    }

    return [
        "proceso" => $proceso,
        "orden" => $orden
    ];
}

foreach ($obtener_pedidos_estampado as $pedido) {
    $clave = $pedido['clave_pedido'];
    $cliente = 3;

    $respuesta_estampado = VerificarEstampadoCoppel($pedido['estilo'], $pedido['consecutivo_programa']);
    $estado_envivo = $respuesta_estampado['proceso'];
    $orden = $respuesta_estampado['orden'];

    if ($pedido['estatus_estampado'] != $estado_envivo) {
        $estatus = "Estampado $estado_envivo";

        try {
            $actualizar_pedido = $mysqli->prepare("UPDATE PEDIDOS_COPPEL SET estatus_estampado = ? WHERE clave_pedido = ?");
            $actualizar_pedido->bind_param("si", $estado_envivo, $clave);
            $actualizar_pedido->execute();
            echo "Se actualizó el estatus de estampado del pedido $clave<br>";

            try {
                $insertar_historico = $mysqli->prepare("INSERT INTO ESTATUS_HISTORICO_PEDIDOS (clave_pedido, estatus, usuario, id_cliente) 
                    VALUES (?, ?, ?, ?)");
                $insertar_historico->bind_param("isii", $clave, $estatus, $usuario, $cliente);
                $insertar_historico->execute();
                echo "Se insertó el histórico de la orden<br>";
            } catch (mysqli_sql_exception $e) {
                echo "Error orden histórico: " . $e->getMessage() . "<br>";
            }

            if (!empty($orden)) {
                try {
                    $insertar_orden_estampado = $mysqli->prepare("INSERT INTO ORDENES_ESTAMPADO_PEDIDOS (orden_estampado, clave_pedido, id_cliente) 
                        VALUES (?, ?, ?)");
                    $insertar_orden_estampado->bind_param("sii", $orden, $clave, $cliente);
                    $insertar_orden_estampado->execute();
                    echo "Se insertó una orden en el estampado<br>";
                } catch (mysqli_sql_exception $e) {
                    echo "Error orden estampado: " . $e->getMessage() . "<br>";
                }
            }
        } catch (mysqli_sql_exception $e) {
            echo "Error actualización pedido: " . $e->getMessage() . "<br>";
        }
    }
}


////////////////////////////////////////////////// E S T A M P A D O ////////////////////////////////////////////////////////////////////


////////////////////////////////////////////////// F A C T U R A D O ////////////////////////////////////////////////////////////////////

function verificarFacturadoCoppel($estilo, $programa) {
  $query = "SELECT WEEKOFYEAR(DFECHA) AS SEMANA, DFECHA, clicod, clinom, dnum AS FACTURA, icod, ilocaliz,iean,aicantf,peobs,coml1
      FROM fdoc
      LEFT JOIN faxinv ON faxinv.dseq = fdoc.dseq
      LEFT JOIN finv ON finv.iseq = faxinv.iseq
      LEFT JOIN fplin ON fplin.iseq = finv.iseq
      LEFT JOIN fpenc ON fpenc.peseq = fplin.peseq
      LEFT JOIN fcli ON fcli.cliseq = fdoc.cliseq
      LEFT JOIN fcoment ON fcoment.comseqfact = fdoc.dseq
      WHERE aitipmv = 'FC'
      AND ilocaliz LIKE '%$estilo%'
      AND coml1 LIKE '%$programa%'
      LIMIT 1";

  $obtener_avance = EXTERN_SQL_querytoarray($query);

  if (!empty($obtener_avance)) {
      $proceso = 'Terminado';
      $factura = $obtener_avance[0]['FACTURA'];
  } else {
      $proceso = 'Pendiente';
      $factura = '';
  }

  return [
      "proceso" => $proceso,
      "factura" => $factura,
  ];
}

$obtener_pedidos_facturado = SQL_querytoarray("SELECT EC.estilo,CONCAT_WS(' ', PC.nombre_programa, PC.consecutivo_programa) AS programa,PEC.* 
  FROM PEDIDOS_COPPEL PEC
  LEFT JOIN ARTICULOS_PEDIDOS_COPPEL APC ON APC.clave_pedido = PEC.clave_pedido
  LEFT JOIN PROGRAMAS_COPPEL PC ON PC.id_programa = APC.id_programa 
  LEFT JOIN ESTILOS_COPPEL EC ON EC.id_estilo = PEC.id_estilo
  WHERE PC.estado NOT IN ('Eliminado', 'Facturado') 
  AND estatus_facturacion != 'Terminado' 
  GROUP BY PEC.clave_pedido");

foreach ($obtener_pedidos_facturado as $pedido) {
  $clave = $pedido['clave_pedido'];
  $cliente = 3;

  $resultado_factura = verificarFacturadoCoppel($pedido['estilo'], $pedido['programa']);
  $estado_envivo = $resultado_factura['proceso'];
  $factura = $resultado_factura['factura'];

  if ($pedido['estatus_facturacion'] != $estado_envivo) {
      $estatus = "Facturado $estado_envivo";

      try {
          $actualizar_pedido = $mysqli->prepare("UPDATE PEDIDOS_COPPEL SET estatus_facturacion = ? WHERE clave_pedido = ?");
          $actualizar_pedido->bind_param("si", $estado_envivo, $clave);
          $actualizar_pedido->execute();
          echo "Se actualizó el estatus de facturación del pedido $clave<br>";

          // Insertar en histórico
          try {
              $insertar_historico = $mysqli->prepare("INSERT INTO ESTATUS_HISTORICO_PEDIDOS (clave_pedido, estatus, usuario, id_cliente) 
                  VALUES (?, ?, ?, ?)");
              $insertar_historico->bind_param("isii", $clave, $estatus, $usuario, $cliente);
              $insertar_historico->execute();
              echo "Se insertó el histórico de la orden<br>";
          } catch (mysqli_sql_exception $e) {
              echo "Error en el histórico: " . $e->getMessage() . "<br>";
          }

          // Insertar orden de facturación si existe
          if (!empty($factura)) {
              try {
                  $insertar_factura_pedido = $mysqli->prepare("INSERT INTO FACTURAS_PEDIDOS (orden_factura, clave_pedido, id_cliente) 
                      VALUES (?, ?, ?)");
                  $insertar_factura_pedido->bind_param("sii", $factura, $clave, $cliente);
                  $insertar_factura_pedido->execute();
                  echo "Se insertó una orden de factura<br>";
              } catch (mysqli_sql_exception $e) {
                  echo "Error al insertar la factura: " . $e->getMessage() . "<br>";
              }
          }
      } catch (mysqli_sql_exception $e) {
          echo "Error en la actualización del pedido: " . $e->getMessage() . "<br>";
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

$usuario = SQL_val("SELECT id FROM USUARIOS_APP where nombre_usuario = 'EUROPRODUCCION'");



if (!empty($datos_json)) {
  foreach ($datos_json as $key => $value) {
    $clave = $value['clave'];
    $estado = $value['estado'];
    $pedido = $value['Pedido'];
    $cli = $value['id_cliente'];

    if($cli != '' || $cli != null){
      if ($cli == 3) {
        $obtener_estatus_pedido = SQL_val("SELECT estatus_tela FROM PEDIDOS_COPPEL WHERE clave_pedido = $clave");
  
        if ($obtener_estatus_pedido != $estado) {
  
          $estatus = "El estatus de tela fue cambiado a $estado";
          $actualizar_estatus = $mysqli->prepare("UPDATE PEDIDOS_COPPEL SET estatus_tela = ?, orden_tela = ? WHERE clave_pedido = ?");
          $actualizar_estatus->bind_param("sii", $estado, $pedido, $clave);
          try {
            $actualizar_estatus->execute();
            echo "Se actualizó el estado del pedido $clave";
            echo " || ";
            $insertar_historico = $mysqli->prepare("INSERT INTO ESTATUS_HISTORICO_PEDIDOS (clave_pedido, estatus, usuario,id_cliente) VALUES (?,?,?,?)");
            $insertar_historico->bind_param("isii", $clave, $estatus, $usuario,$cli);
  
            try {
              $insertar_historico->execute();
              echo "Se actualizó el historico del pedido $clave";
              echo " || ";
            } catch (mysqli_sql_exception $e) {
              echo "Error hisotorico: " . $e->getMessage();
            }
  
            $orden = $value['Pedido'];
            if ($orden != 'N/D') {
  
              $insertar_orden = $mysqli->prepare("INSERT INTO ORDENES_TELA_PEDIDOS (orden_tela, clave_pedido,id_cliente) VALUES (?,?,?)");
              $insertar_orden->bind_param("sii", $orden, $clave,$cli);
              try {
                $insertar_orden->execute();
                echo "Se le asignó una orden a la tela con el estado $estado";
                echo " || ";
              } catch (mysqli_sql_exception $e) {
                echo "Error tela: " . $e->getMessage();
              }
            }
  
  
          } catch (mysqli_sql_exception $e) {
            echo "Ocurrio un error: " . $e->getMessage();
          }
        }
  
      }
    }

   
  }
}


/////////////////////////////////////////////////////// R A I Z /////////////////////////////////////////////////////////////////////////

$obtener_pedidos_raiz = SQL_querytoarray("SELECT PEC.clave_pedido, EC.estilo 
    FROM PEDIDOS_COPPEL PEC
    LEFT JOIN ARTICULOS_PEDIDOS_COPPEL APC ON APC.clave_pedido = PEC.clave_pedido
    LEFT JOIN PROGRAMAS_COPPEL PC ON PC.id_programa = APC.id_programa 
    INNER JOIN ESTILOS_COPPEL EC ON EC.id_estilo = PEC.id_estilo
    LEFT JOIN CODIGOS_PROSCAI_PRODUCTO CPP ON CPP.clave_pedido = PEC.clave_pedido
    WHERE CPP.codigo IS NULL");

if (empty($obtener_pedidos_raiz)) {
    echo "No hay pedidos pendientes.";
    exit;
}


$estilos_modificables = array_column($obtener_pedidos_raiz, 'estilo');
$estilos_modificables = implode("','", $estilos_modificables);

$obtener_codigos = EXTERN_SQL_querytoarray("SELECT ilocaliz, icod, iean 
    FROM finv 
    WHERE iean != '' 
    AND ilocaliz IN ('$estilos_modificables')");

if (empty($obtener_codigos)) {
    echo "No se encontraron códigos en ProsCai.";
    exit;
}

// Indexar códigos para búsqueda rápida
$codigos_indexados = [];
foreach ($obtener_codigos as $codigo) {
    $codigos_indexados[$codigo['ilocaliz']] = $codigo;
}

$id_cliente = 3;

foreach ($obtener_pedidos_raiz as $pedido) {
    $clave = $pedido['clave_pedido'];
    $estilo = $pedido['estilo'];

    if (isset($codigos_indexados[$estilo])) {
        $codigo = $codigos_indexados[$estilo]['icod'];

        $insertar_codigo = $mysqli->prepare("INSERT INTO CODIGOS_PROSCAI_PRODUCTO (codigo, clave_pedido, id_cliente) 
            VALUES (?, ?, ?)");

        $insertar_codigo->bind_param("sii", $codigo, $clave, $id_cliente);

        try {
            $insertar_codigo->execute();
            echo "Código insertado correctamente para el pedido $clave <br>";
        } catch (mysqli_sql_exception $e) {
            echo "Error en la inserción: " . $e->getMessage() . "<br>";
        }
    }
}


/////////////////////////////////////////////////////// R A I Z /////////////////////////////////////////////////////////////////////////