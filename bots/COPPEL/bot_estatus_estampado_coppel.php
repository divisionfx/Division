<?php
require "../../php/conexionn.php";

$usuario  = SQL_val("SELECT id FROM USUARIOS_APP where nombre_usuario = 'PROSCAI'");

$obtener_pedidos = SQL_querytoarray("SELECT EC.estilo,concat_ws(' ',PC.nombre_programa, PC.consecutivo_programa) as programa, PC.consecutivo_programa , PEC.* FROM PEDIDOS_COPPEL PEC
LEFT JOIN ARTICULOS_PEDIDOS_COPPEL APC ON APC.clave_pedido = PEC.clave_pedido
LEFT JOIN PROGRAMAS_COPPEL PC ON PC.id_programa = APC.id_programa 
LEFT JOIN ESTILOS_COPPEL EC ON EC.id_estilo = PEC.id_estilo
WHERE PC.estado not in ('Eliminado') and estatus_estampado != 'Terminado' group by PEC.clave_pedido");



function VerificarEstampadoCoppel($estilo, $programa)
{

  $obtener_avance = EXTERN_SQL_querytoarray(" SELECT
	CASE
			WHEN PLTIPMV = 'E' THEN WEEKOFYEAR(PEFECHA)
            WHEN PLTIPMV = 'V' THEN WEEKOFYEAR(PEFECHA)
	END AS SCORTE,
	CASE
			WHEN PLTIPMV = 'E' THEN PEFECHA
            WHEN PLTIPMV = 'V' THEN PEFECHA
	END AS FCORTE,
	CASE
			WHEN PLTIPMV = 'E' THEN WEEKOFYEAR(PEVENCE)
            WHEN PLTIPMV = 'V' THEN weekofyear(TKTINICIO) 
	END AS SMAQUILA,
	CASE
			WHEN PLTIPMV = 'E' THEN PEVENCE
            WHEN PLTIPMV = 'V' THEN TKTINICIO
			WHEN (PLTIPMV = 'E') THEN 'ESTAMPADO'
            WHEN (PLTIPMV = 'V'AND TKTART ='+ESTAMP' and  tktempl != '') then 'ESTAMPADO'
	END AS PROCESO,
    peobs PROGRAMA,
    if(pltipmv ='V',TKTMAQUINA,fprv.PRVCOD) PRVCOD,
	if(pltipmv ='V',prv2.prvnom,fprv.prvnom) PRVCOD,
    fprv.PRVNOM, PENUM, ICOD, ILOCALIZ, IEAN,
	PLCANT SOLICITADO, TKTCANT SURTIDO, PLCANT - TKTCANT RESTANTE,
	IF(TKTSURT =0,'',(SELECT max(TKTDATEEND) FROM fdoc
							LEFT JOIN faxinv on faxinv.dseq = fdoc.dseq
                            LEFT JOIN db164divfx.ftikets ON db164divfx.ftikets.tktnumop = db164divfx.fpenc.penum and TKTPROD = finv.icod and tktart IN( '+ESTAMP','+TERMIN')
							where iseq = fplin.iseq
							and drefer = fpenc.penum)) 'FENTRADA' 
FROM fpenc
LEFT JOIN fplin ON fplin.PESEQ = fpenc.PESEQ
LEFT JOIN finv ON finv.ISEQ = fplin.ISEQ
LEFT JOIN fprv ON fprv.PRVSEQ = fpenc.PRVSEQ
LEFT JOIN ftikets t ON t.tktnumop = fpenc.penum and TKTPROD = finv.icod and tktart IN('+ESTAMP')
LEFT JOIN fprv prv2 on prv2.prvcod = t.TKTMAQUINA
and  tktempl != ''
WHERE PLTIPMV IN ('E','V') 
 AND ILOCALIZ like '%$estilo%'
 and peobs LIKE '%$programa%'
 AND PLCANT != 0");


  if (!empty($obtener_avance)) {
    $proceso = 'Pendiente';
		$orden = "";
		foreach ($obtener_avance as $key => $value) {

      echo $value['SURTIDO'];
			
			 if ($value['SURTIDO'] == 0) {
				$proceso = 'En proceso';
				break;
			}else{
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


foreach ($obtener_pedidos as $key => $value) {
  $clave = $value['clave_pedido'];
  $cliente = 3;

  $respuesta_estampado = VerificarEstampadoCoppel($value['estilo'], $value['consecutivo_programa']);
  $estado_envivo = $respuesta_estampado['proceso'];
  $orden = $respuesta_estampado['orden'];

  if ($value['estatus_estampado'] != $estado_envivo) {
    $estatus = "Estampado $estado_envivo";
    $actualizar_pedido = $mysqli->prepare("UPDATE PEDIDOS_COPPEL SET estatus_estampado = ? WHERE clave_pedido = ?");
    $actualizar_pedido->bind_param("si", $estado_envivo, $clave);
    echo "<br>";
    try {
      $actualizar_pedido->execute();
      echo "Se actualizo el estatus de estampado del pedido $clave";
      try {
        $insertar_historico = $mysqli->prepare("INSERT INTO ESTATUS_HISTORICO_PEDIDOS (clave_pedido, estatus, usuario,id_cliente) VALUES (?,?,?,?)");
        $insertar_historico->bind_param("isii", $clave, $estatus, $usuario, $cliente);
        $insertar_historico->execute();

        echo "Se inserto el historico de la orden";
      } catch (mysqli_sql_exception $e) {
        echo "Error orden historico: " . $e->getMessage();
      }
      if ($orden != '') {
        $insertar_orden_estampado = $mysqli->prepare("INSERT INTO ORDENES_ESTAMPADO_PEDIDOS (orden_estampado, clave_pedido,id_cliente) VALUES (?,?,?)");
        $insertar_orden_estampado->bind_param("sii", $orden, $clave, $cliente);
        try {
          $insertar_orden_estampado->execute();
          echo "Se inserto una orden en el estampado";
        } catch (mysqli_sql_exception $e) {
          echo "Error orden: " . $e->getMessage();
        }
      }
    } catch (mysqli_sql_exception $e) {
      echo "Error: " . $e->getMessage();
    }
  }
}
