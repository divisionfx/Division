<?php
require "../../php/conexionn.php";

$usuario  = SQL_val("SELECT id FROM USUARIOS_APP where nombre_usuario = 'PROSCAI'");


function verificarFacturadoCoppel($estilo, $programa)
{

	$obtener_avance = EXTERN_SQL_querytoarray("SELECT weekofyear(DFECHA), DFECHA,clicod,clinom,dnum,icod,ilocaliz,iean,aicantf,peobs,coml1
	FROM fdoc
	left join faxinv on faxinv.dseq = fdoc.dseq
	left join finv on finv.iseq = faxinv.iseq
    LEFT JOIN fplin ON fplin.iseq = finv.iseq
    left join fpenc on fpenc.peseq = fplin.peseq
	LEFT JOIN fcli on fcli.cliseq = fdoc.cliseq
	LEFT JOIN fcoment on fcoment.comseqfact = fdoc.dseq
	where aitipmv = 'FC'
	and ilocaliz like '%$estilo%'
    and coml1 like '%$programa%'
    group by DNUM");


	if (!empty($obtener_avance)) {
		$obtener_avance = $obtener_avance[0];
		$proceso = 'Terminado';
		$factura = $obtener_avance['dnum'];
	} else {
		$proceso = 'Pendiente';
		$factura = '';
	}

	return [
		"proceso" => $proceso,
		"factura" => $factura,
	];
}


$obtener_pedidos = SQL_querytoarray("SELECT EC.estilo,concat_ws(' ',PC.nombre_programa, PC.consecutivo_programa) as programa , PEC.* FROM PEDIDOS_COPPEL PEC
LEFT JOIN ARTICULOS_PEDIDOS_COPPEL APC ON APC.clave_pedido = PEC.clave_pedido
LEFT JOIN PROGRAMAS_COPPEL PC ON PC.id_programa = APC.id_programa 
LEFT JOIN ESTILOS_COPPEL EC ON EC.id_estilo = PEC.id_estilo
WHERE PC.estado not in ('Eliminado', 'Facturado') and estatus_facturacion != 'Terminado' group by PEC.clave_pedido");


foreach($obtener_pedidos as $key => $value){
    $clave = $value['clave_pedido'];
    $cliente = 3;
    $resultado_factura = verificarFacturadoCoppel($value['estilo'],$value['programa']);
    $estado_envivo = $resultado_factura['proceso'];
    $factura = $resultado_factura['factura'];
    if($value['estatus_facturacion'] != $estado_envivo){
      echo "<br>";
      $estatus = "Facturado $estado_envivo";
      $actualizar_pedido = $mysqli->prepare("UPDATE PEDIDOS_COPPEL SET estatus_facturacion = ? WHERE clave_pedido = ?");
      $actualizar_pedido->bind_param("si", $estado_envivo, $clave);
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

