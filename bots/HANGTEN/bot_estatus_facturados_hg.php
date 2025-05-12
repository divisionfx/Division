<?php
require "../../php/conexionn.php";

$usuario  = SQL_val("SELECT id FROM USUARIOS_APP where nombre_usuario = 'PROSCAI'");

$obtener_pedidos = SQL_querytoarray("SELECT  HPP.* FROM HANGTEN_PROGRAMAS HP
LEFT JOIN HANGTEN_PEDIDOS HPP ON HPP.id_programa = HP.id_referencia
WHERE estatus not in ('Eliminado', 'Facturado') and VoBo_facturacion != 'Terminado' ");

$estilos_modificables = "";

foreach ($obtener_pedidos as $key => $pedido){
  $estilos = $pedido['estilo'];
  $estilos_modificables .= ",'$estilos'";
}

$estilos_con = substr($estilos_modificables,1);

$obtener_consignacion = EXTERN_SQL_querytoarray("SELECT weekofyear(DFECHA), DFECHA,clicod,clinom,dnum,icod,ilocaliz,iean,aicantf,peobs,coml1,aitipmv
	FROM fdoc
	left join faxinv on faxinv.dseq = fdoc.dseq
	left join finv on finv.iseq = faxinv.iseq
    LEFT JOIN fplin ON fplin.iseq = finv.iseq
    left join fpenc on fpenc.peseq = fplin.peseq
	LEFT JOIN fcli on fcli.cliseq = fdoc.cliseq
	LEFT JOIN fcoment on fcoment.comseqfact = fdoc.dseq
	where aitipmv in ('MC','F')
	and ilocaliz in ($estilos_con)");


foreach($obtener_pedidos as $key => $value){
  $clave = $value['clave'];
  $cliente = 5;
  foreach ($obtener_consignacion as $key_o => $value_o) {
    if($value_o["ilocaliz"] == $value['estilo']){
      $proceso = 'Terminado';
      $factura = $value_o['dnum'];

      if($value['VoBo_facturacion'] != $proceso){
        echo "<br>";
        $estatus = "Facturado $proceso";
        $actualizar_pedido = $mysqli->prepare("UPDATE HANGTEN_PEDIDOS SET VoBo_facturacion = ? WHERE clave = ?");
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
