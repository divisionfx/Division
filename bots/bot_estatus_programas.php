<?php
require "../php/conexionn.php";

$json = file_get_contents('https://produccion.appeuro.mx/europroduccion/Doc/Json/EstadoProgramaControl.json');

// Decodificar el JSON en un arreglo asociativo
$datos = json_encode($json, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
$cadena = json_decode($datos, true);
$cadena_limpia = preg_replace('/^[\pZ\pC]+/u', '', $cadena);
// Decodifica la cadena JSON en un arreglo asociativo
$datos_json = json_decode($cadena_limpia, true);

$usuario = SQL_val("SELECT id FROM USUARIOS_APP where nombre_usuario = 'EUROPRODUCCION'");



if (!empty($datos_json)) {
  foreach ($datos_json as $key => $value) {
    $clave = $value['clave'];
    $estado = $value['estado'];
    $pedido = $value['Pedido'];
    $cli = $value['id_cliente'];

    if($cli != '' || $cli != null){
      if ($cli == 1) {
        $obtener_estatus_pedido = SQL_val("SELECT validacion FROM PEDIDOS WHERE clave = $clave");
  
        if ($obtener_estatus_pedido != $estado) {
  
          $estatus = "El estatus de tela fue cambiado a $estado";
          $actualizar_estatus = $mysqli->prepare("UPDATE PEDIDOS SET validacion = ?, orden_tela = ? WHERE clave = ?");
          $actualizar_estatus->bind_param("sii", $estado, $pedido, $clave);
          try {
            $actualizar_estatus->execute();
            echo "Se actualizó el estado del pedido $clave";
            echo " || ";
            $insertar_historico = $mysqli->prepare("INSERT INTO ESTATUS_HISTORICO_PEDIDOS (clave_pedido, estatus, usuario) VALUES (?,?,?)");
            $insertar_historico->bind_param("isi", $clave, $estatus, $usuario);
  
            try {
              $insertar_historico->execute();
              echo "Se actualizó el historico del pedido $clave";
              echo " || ";
            } catch (mysqli_sql_exception $e) {
              echo "Error hisotorico: " . $e->getMessage();
            }
  
            $orden = $value['Pedido'];
            if ($orden != 'N/D') {
  
              $insertar_orden = $mysqli->prepare("INSERT INTO ORDENES_TELA_PEDIDOS (orden_tela, clave_pedido,id_cliente) VALUES (?,?,?)");
              $insertar_orden->bind_param("sii", $orden, $clave,$cli);
              try {
                $insertar_orden->execute();
                echo "Se le asignó una orden a la tela con el estado $estado";
                echo " || ";
              } catch (mysqli_sql_exception $e) {
                echo "Error tela: " . $e->getMessage();
              }
            }
  
  
          } catch (mysqli_sql_exception $e) {
            echo "Ocurrio un error: " . $e->getMessage();
          }
        }
      } else if ($cli == 5) {
        $obtener_estatus_pedido = SQL_val("SELECT VoBo_tela FROM HANGTEN_PEDIDOS WHERE clave = $clave");
  
        if ($obtener_estatus_pedido != $estado) {
  
          $estatus = "El estatus de tela fue cambiado a $estado";
          $actualizar_estatus = $mysqli->prepare("UPDATE HANGTEN_PEDIDOS SET VoBo_tela = ? , orden_tela = ? WHERE clave = ?");
          $actualizar_estatus->bind_param("sii", $estado, $pedido, $clave);
          try {
            $actualizar_estatus->execute();
            echo "Se actualizó el estado del pedido $clave";
            echo " || ";
            $insertar_historico = $mysqli->prepare("INSERT INTO ESTATUS_HISTORICO_PEDIDOS (clave_pedido, estatus, usuario,id_cliente) VALUES (?,?,?,?)");
            $insertar_historico->bind_param("isii", $clave, $estatus, $usuario,$cli);
  
            try {
              $insertar_historico->execute();
              echo "Se actualizó el historico del pedido $clave";
              echo " || ";
            } catch (mysqli_sql_exception $e) {
              echo "Error hisotorico: " . $e->getMessage();
            }
  
            $orden = $value['Pedido'];
            if ($orden != 'N/D') {
  
              $insertar_orden = $mysqli->prepare("INSERT INTO ORDENES_TELA_PEDIDOS (orden_tela, clave_pedido,id_cliente) VALUES (?,?,?)");
              $insertar_orden->bind_param("sii", $orden, $clave,$cli);
              try {
                $insertar_orden->execute();
                echo "Se le asignó una orden a la tela con el estado $estado";
                echo " || ";
              } catch (mysqli_sql_exception $e) {
                echo "Error tela: " . $e->getMessage();
              }
            }
  
  
          } catch (mysqli_sql_exception $e) {
            echo "Ocurrio un error: " . $e->getMessage();
          }
        }
  
      } else if ($cli == 3) {
        $obtener_estatus_pedido = SQL_val("SELECT estatus_tela FROM PEDIDOS_COPPEL WHERE clave_pedido = $clave");
  
        if ($obtener_estatus_pedido != $estado) {
  
          $estatus = "El estatus de tela fue cambiado a $estado";
          $actualizar_estatus = $mysqli->prepare("UPDATE PEDIDOS_COPPEL SET estatus_tela = ?, orden_tela = ? WHERE clave_pedido = ?");
          $actualizar_estatus->bind_param("sii", $estado, $pedido, $clave);
          try {
            $actualizar_estatus->execute();
            echo "Se actualizó el estado del pedido $clave";
            echo " || ";
            $insertar_historico = $mysqli->prepare("INSERT INTO ESTATUS_HISTORICO_PEDIDOS (clave_pedido, estatus, usuario,id_cliente) VALUES (?,?,?,?)");
            $insertar_historico->bind_param("isii", $clave, $estatus, $usuario,$cli);
  
            try {
              $insertar_historico->execute();
              echo "Se actualizó el historico del pedido $clave";
              echo " || ";
            } catch (mysqli_sql_exception $e) {
              echo "Error hisotorico: " . $e->getMessage();
            }
  
            $orden = $value['Pedido'];
            if ($orden != 'N/D') {
  
              $insertar_orden = $mysqli->prepare("INSERT INTO ORDENES_TELA_PEDIDOS (orden_tela, clave_pedido,id_cliente) VALUES (?,?,?)");
              $insertar_orden->bind_param("sii", $orden, $clave,$cli);
              try {
                $insertar_orden->execute();
                echo "Se le asignó una orden a la tela con el estado $estado";
                echo " || ";
              } catch (mysqli_sql_exception $e) {
                echo "Error tela: " . $e->getMessage();
              }
            }
  
  
          } catch (mysqli_sql_exception $e) {
            echo "Ocurrio un error: " . $e->getMessage();
          }
        }
  
      }
    }

   
  }
}

