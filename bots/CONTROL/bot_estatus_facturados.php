<?php
require "../../php/conexionn.php";
set_time_limit(0);
$usuario  = SQL_val("SELECT id FROM USUARIOS_APP where nombre_usuario = 'PROSCAI'");

$obtener_pedidos = SQL_querytoarray("SELECT PEDIDOS.*, ESTILOS.estilo FROM PEDIDOS
INNER JOIN ESTILOS ON ESTILOS.id_estilos = PEDIDOS.id_estilos
WHERE PEDIDOS.estatus not in ('Eliminado','Facturado') and VoBo_facturacion != 'Terminado'");

$estilos_modificables = "";

foreach ($obtener_pedidos as $key => $pedido) {
  $estilos = $pedido['estilo'];
  $estilos_modificables .= ",'$estilos'";
}
$estilos_con = substr($estilos_modificables, 1);

$obtener_avance = EXTERN_SQL_querytoarray("SELECT ilocaliz,weekofyear(DFECHA), DFECHA,clicod,clinom,dnum,icod,ilocaliz,iean,aicantf  
FROM fdoc
left join faxinv on faxinv.dseq = fdoc.dseq
left join finv on finv.iseq = faxinv.iseq
LEFT JOIN fcli on fcli.cliseq = fdoc.cliseq
where aitipmv = 'F'
and ilocaliz IN ($estilos_con) group by ilocaliz");

$actualizar_pedido_cadena = "";
foreach ($obtener_pedidos as $key => $value) {
  $clave = $value['clave'];
  $cliente = 1;
  foreach ($obtener_avance as $key_o => $value_o) {
    if ($value_o["ilocaliz"] == $value['estilo']) {
      $proceso = 'Terminado';
      $factura = $value_o['dnum'];

      if ($value['VoBo_facturacion'] != $proceso) {

        $estatus = "Facturado $proceso";
        $actualizar_pedido_cadena .= " UPDATE PEDIDOS SET VoBo_facturacion = '$proceso'  WHERE clave = $clave;";

        $actualizar_pedido_cadena .= " INSERT IGNORE INTO ESTATUS_HISTORICO_PEDIDOS (clave_pedido, estatus, usuario,id_cliente) VALUES ($clave,'$estatus',$usuario,$cliente); ";

        if ($factura != '') {
          $actualizar_pedido_cadena .= " INSERT IGNORE INTO FACTURAS_PEDIDOS (orden_factura, clave_pedido, id_cliente) VALUES ('$factura',$clave,$cliente);";
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