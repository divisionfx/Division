<?php
// require "../php/conexionn.php";


// $obtener_estilos = SQL_querytoarray("SELECT P.id_programa,P.clave, E.estilo,P.cantidad_total,AE.clave_pedido,AE.cantidad,AE.fecha_entrega FROM ARTICULOS_PEDIDOS AP 
// LEFT JOIN PEDIDOS P ON P.clave = AP.clave_pedido
// LEFT JOIN ARTICULOS A ON A.id_articulo = AP.id_articulo
// INNER JOIN ESTILOS E ON E.id_estilos = P.id_estilos
// LEFT JOIN AGENDA_ENTREGAS AE ON AE.clave_pedido = P.clave
// WHERE (cantidad < cantidad_total or cantidad is null) 
// GROUP BY clave");

// $estilos_busqueda = array();
// foreach ($obtener_estilos as $key => $value) {
//   array_push($estilos_busqueda,$value['estilo']);
// }

// $estilos_string = implode("','",$estilos_busqueda);

// $obtener_agendas = OMS_SQL_querytoarray("SELECT * FROM citas WHERE estilo in ('$estilos_string') and estilo NOT LIKE '%CJLISC%' AND estilo NOT LIKE '%CTELIS%' AND estilo NOT LIKE '%TPOLP%'");


// foreach ($obtener_estilos as $key => $value) {
//   $id_programa = $value['id_programa'];
//   $clave = $value['clave'];
//   $estilo = $value['estilo'];
//   $fecha_entrega = $value['fecha_entrega'];
//   $id_cliente = 1;
//   $hora = '00:00:00';
//   $ubicacion = "POR CONFIRMAR";

//   foreach ($obtener_agendas as $key_e => $value_e) {
   
//     if($estilo == $value_e['estilo'] and $value_e['No_Camion'] == 'CANCELADO'){
//       if ($estilo == $value_e['estilo'] and $value_e['fecha_cita'] ==  $fecha_entrega ){
//         if($value_e['fecha_cita'] != ''){
//           $f_cita = $value_e['fecha_cita'];
//           $actualizar_entrega = $mysqli->prepare("DELETE FROM AGENDA_ENTREGAS WHERE clave_pedido = ? and fecha_entrega = ?");
//           $cancelado = "CANCELADO";
//           $actualizar_entrega->bind_param("is", $clave,$f_cita);
  
//           try{
//             $actualizar_entrega->execute();
//             echo "Se eliminó la agenda el pedido $clave";
//           }catch (mysqli_sql_exception $e){
//             echo "Error 2: ".$e;
//             echo "<br>";

//           }
//         }
        
//       }
//     }
    
//     if($estilo == $value_e['estilo'] and $value_e['No_Camion'] != 'CANCELADO'){
//       $fecha = $value_e['fecha_cita'];
//       $cantidad = $value_e['total'];
//       $camion = $value_e['No_Camion'];

//       if($fecha != ''){
//         $insertar_agenda = $mysqli->prepare("INSERT INTO AGENDA_ENTREGAS (id_programa,clave_pedido,fecha_entrega,hora_programada,cantidad,ubicacion,id_cliente,camion) VALUES (?,?,?,?,?,?,?,?)");
//         $insertar_agenda->bind_param("iissisis", $id_programa, $clave, $fecha, $hora, $cantidad, $ubicacion, $id_cliente, $camion);
//         try {
//             $insertar_agenda->execute();
//             echo "Se agendó el pedido $clave";
//         } catch (mysqli_sql_exception $e) {
//             // echo "Error: " . $e->getMessage();
//         }
//       }
     
      
//     }
//   }



// }


