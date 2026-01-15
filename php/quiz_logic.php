<?php
// php/quiz_logic.php

// Garante que a conexão existe se este arquivo for chamado diretamente
require_once __DIR__ . '/config.php'; 

/**
 * Busca todos os quizzes ativos para listagem
 */
function listarQuizzesAtivos($pdo) {
    try {
        // Busca quizzes ordenados pelos mais recentes
        $sql = "SELECT * FROM quiz_quizzes WHERE ativo = 1 ORDER BY data_criacao DESC";
        $stmt = $pdo->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        // Em produção, logar erro. Aqui retornamos vazio.
        return [];
    }
}

/**
 * Busca os detalhes de um Quiz específico (Título, Tempo, etc)
 */
function buscarInfoQuiz($pdo, $id) {
    $stmt = $pdo->prepare("SELECT * FROM quiz_quizzes WHERE id = ? AND ativo = 1");
    $stmt->execute([$id]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

/**
 * Busca perguntas e alternativas para o jogo
 * Retorna um array estruturado: [Pergunta -> [Alternativas]]
 */
function buscarPerguntasQuiz($pdo, $quizId) {
    try {
        // 1. Buscar Perguntas
        $sqlPerguntas = "SELECT * FROM quiz_perguntas WHERE quiz_id = ? ORDER BY ordem ASC";
        $stmt = $pdo->prepare($sqlPerguntas);
        $stmt->execute([$quizId]);
        $perguntas = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // 2. Para cada pergunta, buscar as alternativas
        foreach ($perguntas as &$pergunta) {
            $sqlAlt = "SELECT id, texto_alternativa, e_correta FROM quiz_alternativas WHERE pergunta_id = ?";
            $stmtAlt = $pdo->prepare($sqlAlt);
            $stmtAlt->execute([$pergunta['id']]);
            $pergunta['alternativas'] = $stmtAlt->fetchAll(PDO::FETCH_ASSOC);
        }

        return $perguntas;
    } catch (PDOException $e) {
        return [];
    }
}

/**
 * Busca o melhor desempenho do aluno em um quiz específico
 * Adicionado para o sistema de Gamificação (Micropasso 8)
 */
function buscarMelhorDesempenho($pdo, $quizId, $usuarioId) {
    try {
        $sql = "SELECT MAX(nota_final) as melhor_nota, COUNT(*) as tentativas, MAX(data_realizacao) as ultima_vez 
                FROM quiz_historico 
                WHERE quiz_id = ? AND usuario_id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$quizId, $usuarioId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        return null;
    }
}
?>