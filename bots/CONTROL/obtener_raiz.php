<?php 

require "../../php/conexionn.php";


$obtener_pedidos = SQL_querytoarray("SELECT PEDIDOS.*, ESTILOS.estilo,CPP.codigo,CPP.id_cliente FROM PEDIDOS
INNER JOIN ESTILOS ON ESTILOS.id_estilos = PEDIDOS.id_estilos
LEFT JOIN CODIGOS_PROSCAI_PRODUCTO CPP ON CPP.clave_pedido = PEDIDOS.clave
WHERE CPP.codigo is null
GROUP BY PEDIDOS.clave");


$estilos_modificables = "";

foreach ($obtener_pedidos as $key => $pedido){
  $estilos = $pedido['estilo'];
  $estilos_modificables .= ",'$estilos'";
}

$estilos_modificables = substr($estilos_modificables,1);


$obtener_codigos = EXTERN_SQL_querytoarray("SELECT ilocaliz,icod,iean from finv where iean != '' and ilocaliz in($estilos_modificables)");

foreach ($obtener_pedidos as $key => $value) {
    $id_cliente = 1;
    foreach ($obtener_codigos as $key_cod => $value_cod) {

        if($value['estilo'] == $value_cod['ilocaliz'] && $value_cod['iean'] != ''){
            $clave = $value['clave'];
            $codigo = $value_cod['icod'];
            
            $insertar_codigo = $mysqli->prepare('INSERT INTO CODIGOS_PROSCAI_PRODUCTO (codigo,clave_pedido,id_cliente) VALUES (?,?,?)');
            $insertar_codigo->bind_param("sii",$codigo,$clave,$id_cliente);
            try{
                $insertar_codigo->execute();
                echo "Exito";

            }catch(mysqli_sql_exception $e){
                echo "Algo paso ".$e->getMessage();
            }
        }
    }
    
}
