<?php
require "../../php/conexionn.php";

$usuario  = SQL_val("SELECT id FROM USUARIOS_APP where nombre_usuario = 'PROSCAI'");

function VerificarConfeccionCoppel($estilo, $programa)
{
	$obtener_avance = EXTERN_SQL_querytoarray("SELECT
	CASE
			WHEN PLTIPMV = 'DF' THEN WEEKOFYEAR(PEFECHA)
			WHEN PLTIPMV = 'DC' THEN WEEKOFYEAR(PEFECHA)
            WHEN PLTIPMV = 'V' THEN WEEKOFYEAR(PEFECHA)
	END AS SCORTE,
	CASE
			WHEN PLTIPMV = 'DF' THEN PEFECHA
			WHEN PLTIPMV = 'DC' THEN PEFECHA
            WHEN PLTIPMV = 'V' THEN PEFECHA
	END AS FCORTE,
	CASE
			WHEN PLTIPMV = 'DF' THEN WEEKOFYEAR(PEVENCE)
			WHEN PLTIPMV = 'DC' THEN WEEKOFYEAR(PEVENCE)
            WHEN PLTIPMV = 'V' THEN weekofyear(TKTDATE) 
	END AS SMAQUILA,
	CASE
			WHEN PLTIPMV = 'DF' THEN PEVENCE
			WHEN PLTIPMV = 'DC' THEN PEVENCE
            WHEN PLTIPMV = 'V' THEN TKTDATE
	END AS FMAQUILA,
	CASE
			WHEN (PLTIPMV = 'DF' AND PRVCOD = 'M00026') THEN 'CORTE'
			WHEN (PLTIPMV = 'DF' AND PRVCOD != 'M00026') THEN 'CONFECCION'
			WHEN (PLTIPMV = 'DC' AND PRVCOD = 'M00026') THEN 'CORTE'
			WHEN (PLTIPMV = 'DC' AND PRVCOD != 'M00026') THEN 'CONFECCION'
            WHEN (PLTIPMV = 'V' and  tktempl != '') then 'CONFECCION'
	END AS PROCESO,
    peobs PROGRAMA,
	PRVCOD, PRVNOM, PENUM, ICOD, ILOCALIZ, IEAN,
	PLCANT SOLICITADO, PLSURT SURTIDO, PLCANT - PLSURT RESTANTE,
	IF(PLSURT =0,'',(SELECT max(dfecha) FROM fdoc
							LEFT JOIN faxinv on faxinv.dseq = fdoc.dseq
							where iseq = fplin.iseq
							and drefer = fpenc.penum)) 'FENTRADA'
FROM fpenc
LEFT JOIN fplin ON fplin.PESEQ = fpenc.PESEQ
LEFT JOIN finv ON finv.ISEQ = fplin.ISEQ
LEFT JOIN fprv ON fprv.PRVSEQ = fpenc.PRVSEQ
LEFT JOIN db164divfx.ftikets ON db164divfx.ftikets.tktnumop = db164divfx.fpenc.penum and TKTPROD = finv.icod and tktart IN('+CONFEC','+TERMIN') and  tktempl != ''
WHERE PLTIPMV IN ('DF','V','DC')
 AND ILOCALIZ like '%$estilo%'
 and peobs LIKE '%$programa%'
  AND PLCANT != 0");






	if (!empty($obtener_avance)) {
		$proceso = 'Pendiente';
		$orden = "";
		foreach ($obtener_avance as $key => $value) {
			
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
		$orden = "";
	}

	return [
		"proceso" => $proceso,
		"orden" => $orden
	];
}



//PROCESO DE CORTE
$obtener_pedidos = SQL_querytoarray("SELECT EC.estilo,concat_ws(' ',PC.nombre_programa, PC.consecutivo_programa) as programa,  PC.consecutivo_programa, PEC.* FROM PEDIDOS_COPPEL PEC
LEFT JOIN ARTICULOS_PEDIDOS_COPPEL APC ON APC.clave_pedido = PEC.clave_pedido
LEFT JOIN PROGRAMAS_COPPEL PC ON PC.id_programa = APC.id_programa 
LEFT JOIN ESTILOS_COPPEL EC ON EC.id_estilo = PEC.id_estilo
WHERE PC.estado not in ('Eliminado') and estatus_confeccion != 'Terminado' group by PEC.clave_pedido");


foreach($obtener_pedidos as $key => $value){
  $clave = $value['clave_pedido'];
  $cliente = 3;


  $respuessta_confeccion = VerificarConfeccionCoppel($value['estilo'],$value['consecutivo_programa']);


  if(!empty($respuessta_confeccion)){

    
    $estado_envivo = $respuessta_confeccion['proceso'];
    $orden = $respuessta_confeccion['orden'];
    if($value['estatus_confeccion'] != $estado_envivo){
      $actualizar_pedido = $mysqli->prepare("UPDATE PEDIDOS_COPPEL SET estatus_confeccion = ? WHERE clave_pedido = ?");
      $actualizar_pedido->bind_param("si", $estado_envivo, $clave);
      $estatus = "Confeccionado $estado_envivo";
      echo "<br>";
      try{
        $actualizar_pedido->execute();
        echo "Se actualizo el estatus de confeccion del pedido $clave";
        try{
          $insertar_historico = $mysqli->prepare("INSERT INTO ESTATUS_HISTORICO_PEDIDOS (clave_pedido, estatus, usuario,id_cliente) VALUES (?,?,?,?)");
          $insertar_historico->bind_param("isii", $clave, $estatus, $usuario,$cliente);
          $insertar_historico->execute();
          echo "Se inserto el historico de la orden";
        }catch(mysqli_sql_exception $e){
          echo "Error orden historico: ". $e->getMessage();
        }
        if($orden != ''){
          $insertar_orden_estampado = $mysqli->prepare("INSERT INTO ORDENES_CONFECCION_PEDIDOS (orden_confeccion, clave_pedido, id_cliente) VALUES (?,?,?)"); 
          $insertar_orden_estampado->bind_param("sii", $orden, $clave, $cliente);
          try{
            $insertar_orden_estampado->execute();
            echo "Se inserto una orden en la confeccion";
  
          }catch(mysqli_sql_exception $e) {
            echo "Error orden: ". $e->getMessage();
          }
        }
      }catch(mysqli_sql_exception $e){
        echo "Error: ".$e->getMessage();
      }
    }
  }else{
    echo "No se encontro nada";
  }
  


}

echo "Hola";
