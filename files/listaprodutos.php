<?php
require_once 'conexao.php';

$total_geral = 0;
$produtos_selecionados = [];
$ids_selecionados = [];
$mensagem = '';
$mostrar_resultado = false;

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['produtos_ids'])) {

    // Transforma os IDs em inteiros (seguro contra SQL Injection)
    $ids_seguros = array_map('intval', $_POST['produtos_ids']);

    // ===== COMPRA: diminui 1 da quantidade de cada produto =====
    if (isset($_POST['acao']) && $_POST['acao'] === 'comprar') {

        // Só diminui se ainda houver estoque (quantidade > 0)
        $stmt_update = $conexao->prepare(
            "UPDATE produtos SET quantidade = quantidade - 1 WHERE id = ? AND quantidade > 0"
        );
        $stmt_nome = $conexao->prepare("SELECT produto FROM produtos WHERE id = ?");

        $comprados = 0;
        $sem_estoque = [];

        foreach ($ids_seguros as $id) {
            $stmt_update->bind_param("i", $id);
            $stmt_update->execute();

            if ($stmt_update->affected_rows > 0) {
                $comprados++;
            } else {
                // Não tinha estoque: busca o nome para avisar o usuário
                $stmt_nome->bind_param("i", $id);
                $stmt_nome->execute();
                $res = $stmt_nome->get_result()->fetch_assoc();
                if ($res) {
                    $sem_estoque[] = $res['produto'];
                }
            }
        }

        $stmt_update->close();
        $stmt_nome->close();

        if ($comprados > 0) {
            $mensagem .= "<p style='color:green;'><strong>Compra realizada com sucesso! ($comprados produto(s))</strong></p>";
        }
        if (!empty($sem_estoque)) {
            $mensagem .= "<p style='color:red;'><strong>Sem estoque:</strong> "
                . htmlspecialchars(implode(', ', $sem_estoque)) . "</p>";
        }

    // ===== CÁLCULO DO TOTAL =====
    } else {
        $mostrar_resultado = true;
        $ids_selecionados = $ids_seguros;
        $ids_string = implode(',', $ids_seguros);

        $sql_calculo = "SELECT * FROM produtos WHERE id IN ($ids_string)";
        $resultado_calculo = $conexao->query($sql_calculo);

        if ($resultado_calculo) {
            while ($linha = $resultado_calculo->fetch_assoc()) {
                $preco = floatval($linha['preço']);
                $imposto = floatval($linha['imposto']);
                $total_geral += ($preco * $imposto);
                $produtos_selecionados[] = $linha['produto'];
            }
        }
    }

} elseif ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Enviou o formulário sem marcar nada
    $mostrar_resultado = true;
}

// Busca todos os produtos (depois da compra, para já mostrar a quantidade atualizada)
$sql = "SELECT * FROM produtos";
$resultado = $conexao->query($sql);
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lista de Produtos</title>
</head>
<body>
    <h2>Lista de Produtos</h2>
    <button><a href="produtos.php" style="text-decoration:none; color:black;">Cadastrar Novo</a></button>
    <br><br>

    <!-- Mensagem de resultado da compra -->
    <?php if ($mensagem != ''): ?>
        <div style="background: #f0f0f0; padding: 10px; border: 1px solid #ccc; width: 300px;">
            <?php echo $mensagem; ?>
        </div>
        <br>
    <?php endif; ?>

    <form method="POST" action="">
        <table border="1" cellpadding="5">
            <tr>
                <th>Selecionar</th>
                <th>ID</th>
                <th>Produto</th>
                <th>Quantidade</th>
                <th>Preço</th>
                <th>Imposto</th>
                <th>Ações</th>
            </tr>

            <?php 
            if ($resultado && $resultado->num_rows > 0) {
                while ($linha = $resultado->fetch_assoc()) {
                    ?>
                    <tr>
                        <td style="text-align: center;">
                            <input type="checkbox" name="produtos_ids[]" value="<?php echo $linha['id']; ?>">
                        </td>
                        <td><?php echo $linha['id']; ?></td>
                        <td><?php echo htmlspecialchars($linha['produto']); ?></td>
                        <td><?php echo $linha['quantidade']; ?></td>
                        <td>R$ <?php echo number_format($linha['preço'], 2, ',', '.'); ?></td>
                        <td>R$ <?php echo number_format($linha['imposto'], 2, ',', '.'); ?></td>
                        <td>
                            <a href="editar.php?id=<?php echo $linha['id']; ?>">Editar</a>
                        </td>
                    </tr>
                    <?php
                }
            } else {
                ?>
                <tr>
                    <td style="text-align: center;" colspan="7">Nenhum produto cadastrado</td>
                </tr>
                <?php 
            }
            ?>
        </table> 
        <br>
        <button type="submit" style="padding: 10px; background-color: green; color: white; cursor: pointer;">Calcular Total dos Selecionados</button>
    </form>

    <br><hr>

    <!-- Exibição do Resultado da Soma -->
    <?php if ($mostrar_resultado): ?>
        <div style="background: #f0f0f0; padding: 15px; border: 1px solid #ccc; width: 300px;">
            <h3>Resultado do Cálculo:</h3>
            <?php if ($total_geral > 0): ?>
                <p><strong>Itens escolhidos:</strong> <?php echo htmlspecialchars(implode(', ', $produtos_selecionados)); ?></p>
                <p style="font-size: 18px; color: green;"><strong>Total (Preço + Imposto): R$ <?php echo number_format($total_geral, 2, ',', '.'); ?></strong></p>

                <!-- Formulário de compra: reenvia os IDs selecionados -->
                <form method="POST" action="">
                    <?php foreach ($ids_selecionados as $id): ?>
                        <input type="hidden" name="produtos_ids[]" value="<?php echo $id; ?>">
                    <?php endforeach; ?>
                    <button type="submit" style="padding: 8px; background-color: blue; color: white; cursor: pointer;">Comprar produtos selecionados</button>
                </form>
            <?php else: ?>
                <p style="color: red;">Nenhum produto foi selecionado!</p>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <!--<button><a href="produtos.php">Voltar para pagina inicial</a></button>-->

    <br><br>
</body>
</html>