<?php
require "../../php/conexionn.php";

$usuario  = SQL_val("SELECT id FROM USUARIOS_APP where nombre_usuario = 'EUROPRODUCCION'");

$obtener_pedidos = SQL_querytoarray("SELECT HPP.* FROM HANGTEN_PROGRAMAS HP
LEFT JOIN HANGTEN_PEDIDOS HPP ON HPP.id_programa = HP.id_referencia
WHERE estatus not in ('Eliminado', 'Facturado') and VoBo_estampado not in ('Terminado')");

$estilos_modificables = "";

foreach ($obtener_pedidos as $key => $pedido) {
  $estilos = $pedido['estilo'];
  $estilos_modificables .= ",'$estilos'";
}

$estilos_con = substr($estilos_modificables, 1);

$obtener_avance = EXTERN_SQL_querytoarray("SELECT
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
  if(pltipmv ='V',TKTMAQUINA,fprv.PRVCOD) PRVCOD,
if(pltipmv ='V',prv2.prvnom,fprv.prvnom) PRVCOD,
  fprv.PRVNOM, PENUM, ICOD, ILOCALIZ, IEAN,
PLCANT SOLICITADO, TKTSURT SURTIDO, TKTCANT - TKTSURT RESTANTE,
IF(TKTSURT =0,'',(SELECT max(TKTDATEEND) FROM fdoc
            LEFT JOIN faxinv on faxinv.dseq = fdoc.dseq
                          LEFT JOIN db164divfx.ftikets ON db164divfx.ftikets.tktnumop = db164divfx.fpenc.penum and TKTPROD = finv.icod and tktart IN( '+ESTAMP','+TERMIN')
            where iseq = fplin.iseq
            and drefer = fpenc.penum)) 'FENTRADA' 
FROM fpenc
LEFT JOIN fplin ON fplin.PESEQ = fpenc.PESEQ
LEFT JOIN finv ON finv.ISEQ = fplin.ISEQ
LEFT JOIN fprv ON fprv.PRVSEQ = fpenc.PRVSEQ
LEFT JOIN ftikets t ON t.tktnumop = fpenc.penum and TKTPROD = finv.icod and tktart IN( '+ESTAMP','+TERMIN')
LEFT JOIN fprv prv2 on prv2.prvcod = t.TKTMAQUINA
and  tktempl != ''
WHERE PLTIPMV IN ('E','V')
	AND ilocaliz in ($estilos_con) group by ilocaliz");


foreach ($obtener_pedidos as $key => $value) {
  $clave = $value['clave'];
  $cliente = 5;



  foreach ($obtener_avance as $key_o => $value_o) {
    if ($value_o["ILOCALIZ"] == $value['estilo']) {
      $proceso = '';
      $orden = '';
      if ($value_o['FENTRADA'] == '') {
        $proceso = 'En proceso';
      }else{
        $proceso = 'Terminado';
        $orden = $value_o['PENUM'];
      }

     
        if ($value['VoBo_estampado'] != $proceso) {
          $estatus = "Estampado $proceso";
          $actualizar_pedido = $mysqli->prepare("UPDATE HANGTEN_PEDIDOS SET VoBo_estampado = ? WHERE clave = ?");
          $actualizar_pedido->bind_param("si", $proceso, $clave);
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
  }



  
}
