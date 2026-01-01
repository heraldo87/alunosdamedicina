<?php
/**
 * API DE CALENDÁRIO - MEDINFOCUS
 * Responsável por fornecer dados de calendários e eventos em formato JSON.
 */

header('Content-Type: application/json; charset=utf-8');
session_start();

// Importa a conexão com o banco
// Ajuste o caminho conforme sua estrutura de pastas real
require_once '../php/config.php'; 

// Verifica se o usuário está logado (Segurança básica)
if (!isset($_SESSION['usuario_id']) && !isset($_SESSION['user_id'])) {
    // Em produção, descomente a linha abaixo para bloquear acesso não autorizado
    // echo json_encode(['erro' => true, 'msg' => 'Acesso não autorizado']); exit;
}

$acao = $_GET['acao'] ?? '';
$metodo = $_SERVER['REQUEST_METHOD'];

try {
    // ------------------------------------------------------------------
    // 1. LISTAR TODOS OS CALENDÁRIOS (Categorias)
    // Ex: GET api/calendario.php?acao=listar_calendarios
    // ------------------------------------------------------------------
    if ($acao === 'listar_calendarios' && $metodo === 'GET') {
        
        // Busca apenas calendários ativos
        $stmt = $pdo->prepare("SELECT id, nome, descricao, cor, publico FROM calendarios WHERE ativo = 1 ORDER BY nome ASC");
        $stmt->execute();
        $calendarios = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode(['sucesso' => true, 'dados' => $calendarios]);
        exit;
    }

    // ------------------------------------------------------------------
    // 2. LISTAR EVENTOS DE UM CALENDÁRIO ESPECÍFICO
    // Ex: GET api/calendario.php?acao=listar_eventos&calendario_id=1&inicio=2024-01-01&fim=2024-02-01
    // ------------------------------------------------------------------
    if ($acao === 'listar_eventos' && $metodo === 'GET') {
        
        $calendarioId = filter_input(INPUT_GET, 'calendario_id', FILTER_VALIDATE_INT);
        $inicio = $_GET['inicio'] ?? date('Y-m-01'); // Padrão: dia 1 do mês atual
        $fim = $_GET['fim'] ?? date('Y-m-t');       // Padrão: último dia do mês atual
        
        if (!$calendarioId) {
            echo json_encode(['erro' => true, 'msg' => 'ID do calendário inválido']);
            exit;
        }

        // Query otimizada buscando por intervalo de datas
        $sql = "SELECT id, titulo, descricao, data_inicio, data_fim, local, dia_inteiro 
                FROM datas_calendario 
                WHERE calendario_id = :cal_id 
                AND (
                    (data_inicio BETWEEN :inicio AND :fim) OR 
                    (data_fim BETWEEN :inicio AND :fim) OR
                    (data_inicio < :inicio AND data_fim > :fim)
                )
                ORDER BY data_inicio ASC";
        
        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':cal_id', $calendarioId);
        $stmt->bindValue(':inicio', $inicio . ' 00:00:00'); // Garante hora cheia
        $stmt->bindValue(':fim', $fim . ' 23:59:59');       // Garante final do dia
        $stmt->execute();
        
        $eventos = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Formata para o padrão que bibliotecas de calendário (como FullCalendar) gostam
        $eventosFormatados = array_map(function($evt) {
            return [
                'id' => $evt['id'],
                'title' => $evt['titulo'],
                'start' => $evt['data_inicio'],
                'end' => $evt['data_fim'],
                'description' => $evt['descricao'],
                'location' => $evt['local'],
                'allDay' => (bool)$evt['dia_inteiro']
            ];
        }, $eventos);

        echo json_encode(['sucesso' => true, 'dados' => $eventosFormatados]);
        exit;
    }

    // ------------------------------------------------------------------
    // 3. CRIAR NOVO EVENTO (Simples)
    // Ex: POST em api/calendario.php
    // ------------------------------------------------------------------
    if ($metodo === 'POST') {
        // Lê o JSON recebido (caso use fetch/axios no front com JSON body)
        $input = json_decode(file_get_contents('php://input'), true);
        
        // Se não for JSON, tenta pegar do $_POST normal
        if (!$input) $input = $_POST;

        $calendarioId = $input['calendario_id'] ?? null;
        $titulo = $input['titulo'] ?? null;
        $dataInicio = $input['data_inicio'] ?? null;
        
        if (!$calendarioId || !$titulo || !$dataInicio) {
            echo json_encode(['erro' => true, 'msg' => 'Campos obrigatórios faltando']);
            exit;
        }

        $sql = "INSERT INTO datas_calendario (calendario_id, titulo, descricao, data_inicio, data_fim, local, criado_por) 
                VALUES (:cal, :tit, :desc, :ini, :fim, :loc, :user)";
        
        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':cal', $calendarioId);
        $stmt->bindValue(':tit', strip_tags($titulo));
        $stmt->bindValue(':desc', $input['descricao'] ?? null);
        $stmt->bindValue(':ini', $dataInicio);
        $stmt->bindValue(':fim', $input['data_fim'] ?? null); // Pode ser null
        $stmt->bindValue(':loc', $input['local'] ?? null);
        $stmt->bindValue(':user', $_SESSION['usuario_id'] ?? 1); // Fallback para 1 se não logado (teste)
        
        if ($stmt->execute()) {
            echo json_encode(['sucesso' => true, 'msg' => 'Evento criado com sucesso', 'id' => $pdo->lastInsertId()]);
        } else {
            echo json_encode(['erro' => true, 'msg' => 'Erro ao inserir no banco']);
        }
        exit;
    }

} catch (PDOException $e) {
    // Retorna erro JSON limpo, sem expor detalhes do banco
    http_response_code(500);
    echo json_encode(['erro' => true, 'msg' => 'Erro interno no servidor de banco de dados.']);
}