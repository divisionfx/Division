<?php
require "../php/conexionn.php";

// $obtener_pedidos = SQL_querytoarray("SELECT PEDIDOS.*, ESTILOS.estilo FROM PEDIDOS
// INNER JOIN ESTILOS ON ESTILOS.id_estilos = PEDIDOS.id_estilos
// WHERE PEDIDOS.estatus not in ('Eliminado','Facturado') 
// AND VoBo_estampado not in ('Eliminado', 'Pendiente')");


$usuario  = SQL_val("SELECT id FROM USUARIOS_APP where nombre_usuario = 'EUROPRODUCCION'");

$obtener_pedidos = SQL_querytoarray("SELECT PEDIDOS.*, ESTILOS.estilo FROM PEDIDOS
INNER JOIN ESTILOS ON ESTILOS.id_estilos = PEDIDOS.id_estilos
WHERE PEDIDOS.estatus not in ('Eliminado','Facturado') and VoBo_facturacion != 'Terminado'");


foreach($obtener_pedidos as $key => $value){
    $clave = $value['clave'];
    $cliente = 1;
    $resultado_factura = verificarFacturado($value['estilo']);
    $estado_envivo = $resultado_factura['proceso'];
    $factura = $resultado_factura['factura'];
    if($value['VoBo_facturacion'] != $estado_envivo){
      echo "<br>";
      $estatus = "Facturado $estado_envivo";
      $actualizar_pedido = $mysqli->prepare("UPDATE PEDIDOS SET VoBo_facturacion = ? WHERE clave = ?");
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





//OBTENER ESTAMPADO

$obtener_pedidos = SQL_querytoarray("SELECT PEDIDOS.*, ESTILOS.estilo FROM PEDIDOS
INNER JOIN ESTILOS ON ESTILOS.id_estilos = PEDIDOS.id_estilos
WHERE PEDIDOS.estatus not in ('Eliminado','Facturado') and VoBo_estampado not in ('Pendiente','Terminado')  ");


foreach($obtener_pedidos as $key => $value){
    $clave = $value['clave'];
    $cliente = 1;
    $respuessta_confeccion = verificarConfeccion($value['estilo']);
    $estado_envivo = $respuessta_confeccion['proceso'];
    $orden = $respuessta_confeccion['orden'];
    if($value['VoBo_confeccion'] != $estado_envivo){
      $actualizar_pedido = $mysqli->prepare("UPDATE PEDIDOS SET VoBo_confeccion = ? WHERE clave = ?");
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
    $respuesta_estampado = verificarEstampado($value['estilo']);
    $estado_envivo = $respuesta_estampado['proceso'];
    $orden = $respuesta_estampado['orden'];

    echo "Estado: $estado_envivo || ".$value['estilo']."";


    
    if($estado_envivo != 'Autorizado'){
      if($value['VoBo_estampado'] != $estado_envivo){
        $estatus = "Estampado $estado_envivo";
        $actualizar_pedido = $mysqli->prepare("UPDATE PEDIDOS SET VoBo_estampado = ? WHERE clave = ?");
        $actualizar_pedido->bind_param("si", $estado_envivo, $clave);
        echo "<br>";
        try{
          $actualizar_pedido->execute();
          echo "Se actualizo el estatus de estampado del pedido $clave";
          try{
            $insertar_historico = $mysqli->prepare("INSERT INTO ESTATUS_HISTORICO_PEDIDOS (clave_pedido, estatus, usuario,id_cliente) VALUES (?,?,?,?)");
            $insertar_historico->bind_param("isii", $clave, $estatus, $usuario,$cliente);
            $insertar_historico->execute();

            echo "Se inserto el historico de la orden";
          }catch(mysqli_sql_exception $e){
            echo "Error orden historico: ". $e->getMessage();
          }
          if($orden != ''){
            $insertar_orden_estampado = $mysqli->prepare("INSERT INTO ORDENES_ESTAMPADO_PEDIDOS (orden_estampado, clave_pedido,id_cliente) VALUES (?,?,?)");
            $insertar_orden_estampado->bind_param("sii", $orden, $clave, $cliente);
            try{
              $insertar_orden_estampado->execute();
              echo "Se inserto una orden en el estampado";

            }catch(mysqli_sql_exception $e) {
              echo "Error orden: ". $e->getMessage();
            }
          }

        }catch(mysqli_sql_exception $e){
          echo "Error: ".$e->getMessage();
        }
      } 


    }

 
    $resultado_factura = verificarFacturado($value['estilo']);
    $estado_envivo = $resultado_factura['proceso'];
    $factura = $resultado_factura['factura'];
    if($value['VoBo_facturacion'] != $estado_envivo){
      echo "<br>";
      $estatus = "Facturado $estado_envivo";
      $actualizar_pedido = $mysqli->prepare("UPDATE PEDIDOS SET VoBo_facturacion = ? WHERE clave = ?");
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


//PROCESO DE CORTE
$obtener_pedidos = SQL_querytoarray("SELECT PEDIDOS.*, ESTILOS.estilo FROM PEDIDOS
INNER JOIN ESTILOS ON ESTILOS.id_estilos = PEDIDOS.id_estilos
WHERE PEDIDOS.estatus not in ('Eliminado','Facturado') and VoBo_confeccion !='Terminado' ");

foreach($obtener_pedidos as $key => $value){
  $clave = $value['clave'];
  $cliente = 1;
  $respuessta_confeccion = verificarConfeccion($value['estilo']);
  $estado_envivo = $respuessta_confeccion['proceso'];
  $orden = $respuessta_confeccion['orden'];
  if($value['VoBo_confeccion'] != $estado_envivo){
    $actualizar_pedido = $mysqli->prepare("UPDATE PEDIDOS SET VoBo_confeccion = ? WHERE clave = ?");
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


}





$obtener_pedidos = SQL_querytoarray("SELECT * FROM PEDIDOS_COPPEL
    INNER JOIN ESTILOS_COPPEL EC ON EC.id_estilo = PEDIDOS_COPPEL.id_estilo");


foreach($obtener_pedidos as $key => $value){
  $clave = $value['clave_pedido'];
  $cliente = 3;

    $respuessta_confeccion = verificarConfeccion($value['estilo']);
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
   
    $respuesta_estampado = verificarEstampado($value['estilo']);
    $estado_envivo = $respuesta_estampado['proceso'];
    $orden = $respuesta_estampado['orden'];

    if($estado_envivo != 'Autorizado'){
      if($value['estatus_estampado'] != $estado_envivo){
        $estatus = "Estampado $estado_envivo";
        $actualizar_pedido = $mysqli->prepare("UPDATE PEDIDOS_COPPEL SET estatus_estampado = ? WHERE clave_pedido = ?");
        $actualizar_pedido->bind_param("si", $estado_envivo, $clave);
        echo "<br>";
        try{
          $actualizar_pedido->execute();
          echo "Se actualizo el estatus de estampado del pedido $clave";
          try{
            $insertar_historico = $mysqli->prepare("INSERT INTO ESTATUS_HISTORICO_PEDIDOS (clave_pedido, estatus, usuario,id_cliente) VALUES (?,?,?,?)");
            $insertar_historico->bind_param("isii", $clave, $estatus, $usuario,$cliente);
            $insertar_historico->execute();
  
            echo "Se inserto el historico de la orden";
          }catch(mysqli_sql_exception $e){
            echo "Error orden historico: ". $e->getMessage();
          }
          if($orden != ''){
            $insertar_orden_estampado = $mysqli->prepare("INSERT INTO ORDENES_ESTAMPADO_PEDIDOS (orden_estampado, clave_pedido, id_cliente) VALUES (?,?,?)");
            $insertar_orden_estampado->bind_param("sii", $orden, $clave, $cliente);
            try{
              $insertar_orden_estampado->execute();
              echo "Se inserto una orden en el estampado";
    
            }catch(mysqli_sql_exception $e) {
              echo "Error orden: ". $e->getMessage();
            }
          }
        }catch(mysqli_sql_exception $e){
          echo "Error: ".$e->getMessage();
        }
      } 
    }
   

 
    $resultado_factura = verificarFacturado($value['estilo']);
    $estado_envivo = $resultado_factura['proceso'];
    $factura = $resultado_factura['factura'];
    if($value['estatus_facturacion'] != $estado_envivo){
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