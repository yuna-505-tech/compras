<?php
 //COXECAO COM BANCO DE DADOS
 //CREDENCIAIS DO NOSSO BANCO DE DADOS
 $servidor = "localhost";
 $usuario = "root";
 $senha = "";
 $banco = "banco_dados";

 //CRIANDO A CONEXAO
 $conexao = mysqli_connect($servidor, $usuario, $senha, $banco);

 //VERIFICANDO A COENXAO

 if($conexao->connect_error){
    die("conexao falhou: " . $conexao->connect_error);
 }
 //echo "conexao fucionou com sucesso";
?>