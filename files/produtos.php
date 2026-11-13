<?php
require_once "conexao.php";

$erro = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $produto    = trim($_POST['produto'] ?? '');
    $preco      = $_POST['preco'] ?? '';
    $quantidade = $_POST['quantidade'] ?? '';
    $imposto    = $_POST['imposto'] ?? '';

    if ($produto === '' || !is_numeric($preco) || !is_numeric($quantidade) || !is_numeric($imposto)) {
        $erro = "Dados inválidos. Confira os campos.";
    } else {
        $quantidade = (int)$quantidade;
        $preco      = (float)$preco;
        $imposto    = (float)$imposto;

        $stmt = $conexao->prepare(
            "INSERT INTO produtos (produto, quantidade, `preço`, imposto) VALUES (?, ?, ?, ?)"
        );
        // s = string, i = inteiro, d = decimal
        $stmt->bind_param("sidd", $produto, $quantidade, $preco, $imposto);

        if ($stmt->execute()) {
            $stmt->close();
            header("Location: listaprodutos.php");
            exit;
        } else {
            $erro = "Erro ao salvar: " . $stmt->error;
            $stmt->close();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Produtos</title>
</head>
<body>
    <h1>Cadastrar Produto</h1>

    <?php if ($erro): ?>
        <p style="color: red;"><strong><?php echo htmlspecialchars($erro); ?></strong></p>
    <?php endif; ?>

    <form method="POST" action="">
        <label for="produto">Produto:</label>
        <input type="text" name="produto" id="produto" placeholder="Produto" required>
        <br>
        <label for="preco">Preço:</label>
        <input type="number" step="0.01" name="preco" id="preco" placeholder="Preço" required>
        <br>
        <label for="quantidade">Quantidade:</label>
        <input type="number" name="quantidade" id="quantidade" placeholder="Quantidade" required>
        <br>
        <label for="imposto">Imposto:</label>
        <input type="number" step="0.01" name="imposto" id="imposto" placeholder="Imposto" required>
        <br><br>
        <button type="submit">Salvar</button>
        <button type="button" onclick="location.href='listaprodutos.php'">Ver lista</button>
    </form>
</body>
</html>