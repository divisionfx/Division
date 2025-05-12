<?php 
$host = 'oms.appeuro.mx';
$usuario = 'marco';
$bd = 'gpodsw_pruebas';
// $bd = 'gpodsw';
$contraseña = 'cv%j?y8=GI8h';
$port = '6033';

$mysqli = new mysqli($host, $usuario, $contraseña, $bd, $port);

$extern_host = 'oms.appeuro.mx';
$extern_usuario = 'divfapp';
$extern_bd = 'db164divfx';
$extern_contraseña = 'sDf4&8dH%3wEf&#';
$extern_port = '6033';


$extern_mysqli = new mysqli($extern_host, $extern_usuario, $extern_contraseña, $extern_bd, $extern_port);

$externac_host = 'oms.appeuro.mx';
$externac_usuario = 'marco';
$externac_bd = 'db164american';
$externac_contraseña = 'cv%j?y8=GI8h';
$externac_port = '6033';


$externac_mysqli = new mysqli($externac_host, $externac_usuario, $externac_contraseña, $externac_bd, $externac_port);


$externhg_host = 'oms.appeuro.mx';
$externhg_usuario = 'marco';
$externhg_bd = 'handheld';
$externhg_contraseña = 'cv%j?y8=GI8h';
$externhg_port = '6033';


$externhg_mysqli = new mysqli($externhg_host, $externhg_usuario, $externhg_contraseña, $externhg_bd, $externhg_port);


function SQL_val($query, $default = ""){
	try {
	global $mysqli;

	$result = $mysqli->query($query);
	if (!$result) {
		$sReturn = $default;
	} else {
		
			$row = $result->fetch_array();
			if(!empty($row)) {
		  	$sReturn = $row[0];

			}
			else {
				$sReturn = $default;

			}
	
	}
} catch (Exception $e) {
	$sReturn = $default;

}
	return $sReturn;
}
function SQL_querytoarray($query)
{
	try {
	global $mysqli;

	$result = $mysqli->query($query);
	$aResult = array();
	if ($result) {
		while ($row = $result->fetch_assoc()) {
			array_push($aResult, $row);
		}
	}
} catch (mysqli_sql_exception $e) {
	$aResult = null;

}
	return $aResult;
}

function EXTERN_SQL_val($query, $default = "")
{
	global $extern_mysqli;

	$result = $extern_mysqli->query($query);
	if (!$result) {
		$sReturn = $default;
	} else {
		$row = $result->fetch_array();
		$sReturn = $row[0];
	}
	return $sReturn;
}
function EXTERN_SQL_querytoarray($query)
{
	global $extern_mysqli;

	$result = $extern_mysqli->query($query);
	$aResult = array();
	if ($result) {
		while ($row = $result->fetch_assoc()) {
			array_push($aResult, $row);
		}
	}
	return $aResult;
}

set_time_limit(0);
$usuario = SQL_val("SELECT id FROM USUARIOS_APP WHERE nombre_usuario = 'PROSCAI'");


////////////////////////////////////////////////// P R O G R A M A S ////////////////////////////////////////////////////////////////////

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
