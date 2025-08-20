#!/bin/bash
cd /www/wwwroot/alunosdamedicina.com/
echo "=== Deploy Iniciado ==="
echo "Fazendo backup de alterações locais..."
git stash 2>/dev/null || true
echo "Baixando atualizações do GitHub..."
git pull origin main
echo "Ajustando permissões..."
chown -R root:root .git
chown -R www:www *.php includes/ uploads/ php/ .htaccess .gitignore 2>/dev/null || true
echo "=== Deploy Concluído: $(date) ==="
