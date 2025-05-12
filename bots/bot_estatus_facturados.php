<?php
require "../php/conexionn.php";

$usuario  = SQL_val("SELECT id FROM USUARIOS_APP where nombre_usuario = 'EUROPRODUCCION'");

$obtener_pedidos = SQL_querytoarray("SELECT PEDIDOS.*, ESTILOS.estilo FROM PEDIDOS
INNER JOIN ESTILOS ON ESTILOS.id_estilos = PEDIDOS.id_estilos
WHERE PEDIDOS.estatus not in ('Eliminado','Facturado') and VoBo_facturacion != 'Terminado'");

$estilos_modificables = "";

foreach ($obtener_pedidos as $key => $pedido){
  $estilos = $pedido['estilo'];
  $estilos_modificables .= ",'$estilos'";
}
$estilos_con = substr($estilos_modificables,1);

$obtener_avance = EXTERN_SQL_querytoarray("SELECT ilocaliz,weekofyear(DFECHA), DFECHA,clicod,clinom,dnum,icod,ilocaliz,iean,aicantf  
FROM fdoc
left join faxinv on faxinv.dseq = fdoc.dseq
left join finv on finv.iseq = faxinv.iseq
LEFT JOIN fcli on fcli.cliseq = fdoc.cliseq
where aitipmv = 'F'
and ilocaliz IN ($estilos_con) group by ilocaliz");


foreach($obtener_pedidos as $key => $value){
    $clave = $value['clave'];
    $cliente = 1;
    foreach ($obtener_avance as $key_o => $value_o) {
      if($value_o["ilocaliz"] == $value['estilo']){
        $proceso = 'Terminado';
		    $factura = $value_o['dnum'];
  
        if($value['VoBo_facturacion'] != $proceso){
          echo "<br>";
          $estatus = "Facturado $proceso";
          $actualizar_pedido = $mysqli->prepare("UPDATE PEDIDOS SET VoBo_facturacion = ? WHERE clave = ?");
          $actualizar_pedido->bind_param("si", $proceso, $clave);
          try{
            $actualizar_pedido->execute();
            echo "Se actualizo el estatus de facturacion del pedido $clave";
            $insertar_historico = $mysqli->prepare("INSERT INTO ESTATUS_HISTORICO_PEDIDOS (clave_pedido, estatus, usuario,id_cliente) VALUES (?,?,?,?)");
            $insertar_historico->bind_param("isii", $clave, $estatus, $usuario,$cliente);
            $insertar_historico->execute();
            if($factura != ''){
              $insertar_factura_pedido = $mysqli->prepare("INSERT INTO FACTURAS_PEDIDOS (orden_factura, clave_pedido, id_cliente) VALUES (?,?,?)");
              $insertar_factura_pedido->bind_param("sii", $factura, $clave, $cliente);
              try{
                $insertar_factura_pedido->execute();
                echo "Se inserto una orden de factura";
      
              }catch(mysqli_sql_exception $e) {
                echo "Error factura: ". $e->getMessage();
              }
            }
    
          }catch(mysqli_sql_exception $e){
            echo "Error: ".$e->getMessage();
          }
        } 
  
  
      }
    }
    
    
  

}

