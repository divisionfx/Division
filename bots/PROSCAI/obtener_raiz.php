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


$obtener_pedidos = SQL_querytoarray("SELECT HP.*,CPP.codigo, CPP.id_cliente FROM HANGTEN_PEDIDOS HP
LEFT JOIN CODIGOS_PROSCAI_PRODUCTO CPP ON CPP.clave_pedido = HP.clave
WHERE CPP.codigo is null
GROUP BY HP.clave;");


$estilos_modificables = "";

foreach ($obtener_pedidos as $key => $pedido){
  $estilos = $pedido['estilo'];
  $estilos_modificables .= ",'$estilos'";
}

$estilos_modificables = substr($estilos_modificables,1);


$obtener_codigos = EXTERN_SQL_querytoarray("SELECT ilocaliz,icod,iean from finv where iean != '' and ilocaliz in($estilos_modificables)");


foreach ($obtener_pedidos as $key => $value) {
    $id_cliente = 5;
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



$obtener_pedidos = SQL_querytoarray("SELECT PEC.* , EC.estilo ,CPP.codigo FROM PEDIDOS_COPPEL PEC
LEFT JOIN ARTICULOS_PEDIDOS_COPPEL APC ON APC.clave_pedido = PEC.clave_pedido
LEFT JOIN PROGRAMAS_COPPEL PC ON PC.id_programa = APC.id_programa 
INNER JOIN ESTILOS_COPPEL EC ON EC.id_estilo = PEC.id_estilo
LEFT JOIN CODIGOS_PROSCAI_PRODUCTO CPP ON CPP.clave_pedido = PEC.clave_pedido
WHERE CPP.codigo is null
GROUP BY PEC.clave_pedido");


$estilos_modificables = "";

foreach ($obtener_pedidos as $key => $pedido){
  $estilos = $pedido['estilo'];
  $estilos_modificables .= ",'$estilos'";
}

$estilos_modificables = substr($estilos_modificables,1);


$obtener_codigos = EXTERN_SQL_querytoarray("SELECT ilocaliz,icod,iean from finv where iean != '' and ilocaliz in($estilos_modificables)");

foreach ($obtener_pedidos as $key => $value) {
    $id_cliente = 3;
    foreach ($obtener_codigos as $key_cod => $value_cod) {

        if($value['estilo'] == $value_cod['ilocaliz'] && $value_cod['iean'] != ''){
            $clave = $value['clave_pedido'];
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










