
<?php
require "../php/conexionn.php";

$usuario  = SQL_val("SELECT id FROM USUARIOS_APP where nombre_usuario = 'PROSCAI'");



//PROCESO DE CORTE
$obtener_pedidos = SQL_querytoarray("SELECT PEDIDOS.*, ESTILOS.estilo FROM PEDIDOS
INNER JOIN ESTILOS ON ESTILOS.id_estilos = PEDIDOS.id_estilos
WHERE PEDIDOS.estatus not in ('Eliminado','Facturado') and VoBo_confeccion !='Terminado' ");


$estilos_modificables = "";

foreach ($obtener_pedidos as $key => $pedido) {
  $estilos = $pedido['estilo'];
  $estilos_modificables .= ",'$estilos'";
}

$estilos_con = substr($estilos_modificables, 1);


$obtener_avance = EXTERN_SQL_querytoarray("SELECT
	CASE
			WHEN PLTIPMV = 'DF' THEN WEEKOFYEAR(PEFECHA)
            WHEN PLTIPMV = 'V' THEN WEEKOFYEAR(PEFECHA)
	END AS SCORTE,
	CASE
			WHEN PLTIPMV = 'DF' THEN PEFECHA
            WHEN PLTIPMV = 'V' THEN PEFECHA
	END AS FCORTE,
	CASE
			WHEN PLTIPMV = 'DF' THEN WEEKOFYEAR(PEVENCE)
            WHEN PLTIPMV = 'V' THEN weekofyear(TKTDATE) 
	END AS SMAQUILA,
	CASE
			WHEN PLTIPMV = 'DF' THEN PEVENCE
            WHEN PLTIPMV = 'V' THEN TKTDATE
	END AS FMAQUILA,
	CASE
			WHEN (PLTIPMV = 'DF' AND PRVCOD = 'M00026') THEN 'CORTE'
			WHEN (PLTIPMV = 'DF' AND PRVCOD != 'M00026') THEN 'CONFECCION'
            WHEN (PLTIPMV = 'V' and  tktempl != '') then 'CONFECCION'
	END AS PROCESO,
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
LEFT JOIN db164divfx.ftikets ON db164divfx.ftikets.tktnumop = db164divfx.fpenc.penum and TKTPROD = finv.icod and tktart = '+CONFEC' and  tktempl != ''
WHERE PLTIPMV IN ('DF','V')
AND ILOCALIZ in ($estilos_con)");

$actualizar_pedido_cadena  = "";

foreach ($obtener_pedidos as $key => $value) {
  $clave = $value['clave'];
  $cliente = 1;
  $proceso = 'Terminado';
  $orden = "";


  foreach ($obtener_avance as $key_o => $value_o) {


    if ($value_o["ILOCALIZ"] == $value['estilo']) {

      if ($value_o['PROCESO'] == '' || $value_o['PROCESO'] == null) {
        $proceso = 'Pendiente';
      } else if ($value_o['SURTIDO'] == 0) {
        $proceso = 'En proceso';
      }

      $orden = $value_o['PENUM'];

      if ($value['VoBo_confeccion'] != $proceso) {
        $actualizar_pedido = "UPDATE PEDIDOS SET VoBo_confeccion = '$proceso' WHERE clave = $clave;";
        $actualizar_pedido_cadena .= $actualizar_pedido;

        $estatus = "Confeccionado $proceso";

        $actualizar_pedido_cadena .= "INSERT INTO ESTATUS_HISTORICO_PEDIDOS (clave_pedido, estatus, usuario,id_cliente) VALUES ($clave,'$estatus',$usuario,$cliente); ";

        if ($orden != '') {
          $actualizar_pedido_cadena .= "INSERT INTO ORDENES_CONFECCION_PEDIDOS (orden_confeccion, clave_pedido, id_cliente) VALUES ('$orden',$clave,$cliente);";
        }
      }
    }
  }

  try {
    $mysqli->query($actualizar_pedido_cadena);

    echo "Exito";
  } catch (mysqli_sql_exception $e) {
    echo "Error " . $e->getMessage();
  }
}
