<?php
/**
 * API DE AVISOS - MEDINFOCUS
 * Gerencia a recuperação e criação de murais e mensagens de aviso.
 */

header('Content-Type: application/json; charset=utf-8');
session_start();

// Conexão com o banco (ajuste o caminho se necessário)
require_once '../php/config.php';

// Segurança básica de sessão (opcional para testes, recomendado para produção)
// if (!isset($_SESSION['usuario_id'])) { http_response_code(401); exit; }

$acao = $_GET['acao'] ?? '';
$metodo = $_SERVER['REQUEST_METHOD'];

try {
    
    // ------------------------------------------------------------------
    // 1. LISTAR MURAIS (Categorias de Avisos)
    // Ex: GET api/avisos.php?acao=listar_murais
    // Retorna: "Secretaria", "Coordenação", "Atlética"...
    // ------------------------------------------------------------------
    if ($acao === 'listar_murais' && $metodo === 'GET') {
        $stmt = $pdo->prepare("SELECT id, nome, descricao, cor_tema FROM murais WHERE ativo = 1 ORDER BY nome ASC");
        $stmt->execute();
        $murais = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode(['sucesso' => true, 'dados' => $murais]);
        exit;
    }

    // ------------------------------------------------------------------
    // 2. LISTAR AVISOS DE UM MURAL
    // Ex: GET api/avisos.php?acao=listar_avisos&mural_id=1
    // Ordenação inteligente: Fixados primeiro, depois os mais novos.
    // ------------------------------------------------------------------
    if ($acao === 'listar_avisos' && $metodo === 'GET') {
        $muralId = filter_input(INPUT_GET, 'mural_id', FILTER_VALIDATE_INT);
        
        if (!$muralId) {
            echo json_encode(['erro' => true, 'msg' => 'ID do mural inválido']);
            exit;
        }

        // Busca avisos cruzando com tabela de usuários para mostrar o nome do autor
        // A ordenação 'fixado DESC' garante que avisos importantes fiquem no topo (1 vem antes de 0)
        $sql = "SELECT a.id, a.titulo, a.conteudo, a.prioridade, a.fixado, a.data_criacao,
                       u.nome as autor_nome
                FROM avisos a
                LEFT JOIN usuarios u ON a.criado_por = u.id
                WHERE a.mural_id = :mural
                ORDER BY a.fixado DESC, a.data_criacao DESC
                LIMIT 50"; // Limite de segurança para performance
        
        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':mural', $muralId);
        $stmt->execute();
        $avisos = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode(['sucesso' => true, 'dados' => $avisos]);
        exit;
    }

    // ------------------------------------------------------------------
    // 3. CRIAR NOVO AVISO
    // Ex: POST em api/avisos.php
    // Body JSON: { mural_id: 1, titulo: "...", conteudo: "...", prioridade: "alta", fixado: true }
    // ------------------------------------------------------------------
    if ($metodo === 'POST') {
        $input = json_decode(file_get_contents('php://input'), true);
        if (!$input) $input = $_POST;

        $muralId = $input['mural_id'] ?? null;
        $titulo = $input['titulo'] ?? null;
        $conteudo = $input['conteudo'] ?? null;
        
        if (!$muralId || !$titulo || !$conteudo) {
            echo json_encode(['erro' => true, 'msg' => 'Preencha título, conteúdo e selecione um mural.']);
            exit;
        }

        // Tratamento de dados
        $prioridade = in_array($input['prioridade'] ?? '', ['baixa','normal','alta','urgente']) 
                      ? $input['prioridade'] 
                      : 'normal';
        
        // Converte booleano ou string 'true'/'1' para 1 ou 0
        $fixado = (!empty($input['fixado']) && $input['fixado'] !== 'false') ? 1 : 0;
        
        $usuarioId = $_SESSION['usuario_id'] ?? 1; // Fallback para 1 (Admin)

        $sql = "INSERT INTO avisos (mural_id, titulo, conteudo, prioridade, fixado, criado_por) 
                VALUES (:mural, :titulo, :conteudo, :prio, :fix, :user)";
        
        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':mural', $muralId);
        $stmt->bindValue(':titulo', strip_tags($titulo));
        $stmt->bindValue(':conteudo', strip_tags($conteudo)); // Remove HTML para segurança
        $stmt->bindValue(':prio', $prioridade);
        $stmt->bindValue(':fix', $fixado);
        $stmt->bindValue(':user', $usuarioId);
        
        if ($stmt->execute()) {
            echo json_encode(['sucesso' => true, 'msg' => 'Aviso publicado com sucesso!']);
        } else {
            echo json_encode(['erro' => true, 'msg' => 'Falha ao salvar aviso.']);
        }
        exit;
    }

} catch (PDOException $e) {
    http_response_code(500);
    // Log do erro real no servidor, retorno genérico para o cliente
    error_log("Erro PDO Avisos: " . $e->getMessage());
    echo json_encode(['erro' => true, 'msg' => 'Erro interno ao processar avisos.']);
}