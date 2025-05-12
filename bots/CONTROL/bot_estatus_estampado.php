
<?php
require "../../php/conexionn.php";
set_time_limit(0);
$usuario  = SQL_val("SELECT id FROM USUARIOS_APP where nombre_usuario = 'PROSCAI'");

$obtener_pedidos = SQL_querytoarray("SELECT PEDIDOS.*, ESTILOS.estilo FROM PEDIDOS
INNER JOIN ESTILOS ON ESTILOS.id_estilos = PEDIDOS.id_estilos
WHERE PEDIDOS.estatus not in ('Eliminado','Facturado') and VoBo_estampado not in ('Terminado')");

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

$actualizar_pedido_cadena = "";
foreach ($obtener_pedidos as $key => $value) {
  $clave = $value['clave'];
  $cliente = 1;



  foreach ($obtener_avance as $key_o => $value_o) {
    if ($value_o["ILOCALIZ"] == $value['estilo']) {
      $proceso = '';
      $orden = '';
      if ($value_o['FENTRADA'] == '') {
        $proceso = 'En proceso';
      } else {
        $proceso = 'Terminado';
        $orden = $value_o['PENUM'];
      }


      if ($value['VoBo_estampado'] != $proceso) {
        $actualizar_pedido = "UPDATE PEDIDOS SET VoBo_estampado = '$proceso'  WHERE clave = $clave;";
        $actualizar_pedido_cadena .= $actualizar_pedido;

        $estatus = "Estampado $proceso";

        $actualizar_pedido_cadena .= "INSERT IGNORE INTO ESTATUS_HISTORICO_PEDIDOS (clave_pedido, estatus, usuario,id_cliente) VALUES ($clave,'$estatus',$usuario,$cliente); ";

        if ($orden != '') {
          $actualizar_pedido_cadena .= "INSERT IGNORE INTO ORDENES_ESTAMPADO_PEDIDOS (orden_estampado, clave_pedido, id_cliente) VALUES ('$orden',$clave,$cliente);";
        }
      }
    }

    
  }
}
try {
  if($actualizar_pedido_cadena != ''){
    $mysqli->multi_query($actualizar_pedido_cadena);
  }
  echo "Exito";
} catch (mysqli_sql_exception $e) {
  echo "Error " . $e->getMessage();
}