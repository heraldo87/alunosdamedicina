import os
import datetime

# CAMINHO DA PASTA MONTADA
# O rclone montou o drive em /mnt/medinfocus_drive
# Dentro dele deve haver a pasta que você compartilhou
CAMINHO_BASE = "/mnt/medinfocus_drive/MEDINFOCUS_ARQUIVOS"
NOME_ARQUIVO = "teste_final_python.txt"

print(f"--- TESTANDO ESCRITA NO GOOGLE DRIVE ---")
print(f"Alvo: {CAMINHO_BASE}")

if not os.path.exists(CAMINHO_BASE):
    print(f"❌ ERRO: A pasta '{CAMINHO_BASE}' não aparece.")
    print("Verifique se o nome da pasta compartilhada é exatamente MEDINFOCUS_ARQUIVOS")
    # Lista o que tem na raiz para ajudar a debugar
    print("Conteúdo da raiz do mount:")
    print(os.listdir("/mnt/medinfocus_drive"))
    exit()

arquivo_completo = os.path.join(CAMINHO_BASE, NOME_ARQUIVO)

try:
    # 1. ESCREVENDO
    horario = datetime.datetime.now().strftime("%d/%m/%Y às %H:%M:%S")
    texto = f"Olá! Este arquivo foi gerado pelo Python direto no VPS.\nHora: {horario}\nStatus: Operacional."

    with open(arquivo_completo, 'w', encoding='utf-8') as f:
        f.write(texto)
    print("✅ [1/2] Arquivo criado com sucesso!")

    # 2. LENDO
    with open(arquivo_completo, 'r', encoding='utf-8') as f:
        conteudo = f.read()
    print(f"✅ [2/2] Leitura confirmada:\n    '{conteudo.strip()}'")

    print("\n🚀 CONCLUSÃO: O sistema está pronto para migrar para Python.")

except Exception as e:
    print(f"❌ ERRO CRÍTICO: {e}")