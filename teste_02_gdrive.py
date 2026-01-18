import os
import shutil
import datetime

# --- CONFIGURAÇÃO ---
# EDITE AQUI: Coloque o caminho absoluto da pasta do Google Drive no seu VPS
# Exemplo: "/mnt/gdrive/AlunosDaMedicina" ou "/home/usuario/gdrive"
CAMINHO_DO_DRIVE = "/caminho/para/seu/google_drive_aqui" 

NOME_PASTA_TESTE = "teste_python_workspace"
NOME_ARQUIVO = "teste_drive.txt"

print(f"--- INICIANDO TESTE NO DRIVE: {CAMINHO_DO_DRIVE} ---")

def executar_teste():
    # Verifica se o caminho base do Drive existe
    if not os.path.exists(CAMINHO_DO_DRIVE):
        print(f"❌ ERRO: O diretório '{CAMINHO_DO_DRIVE}' não foi encontrado.")
        print("Verifique se o Google Drive está montado e se o caminho está correto no script.")
        return

    caminho_completo_pasta = os.path.join(CAMINHO_DO_DRIVE, NOME_PASTA_TESTE)
    caminho_arquivo = os.path.join(caminho_completo_pasta, NOME_ARQUIVO)

    try:
        # 1. Criar Pasta
        if os.path.exists(caminho_completo_pasta):
            print("ℹ️  A pasta de teste já existia. Tentando recriar...")
            # shutil.rmtree(caminho_completo_pasta) # Cuidado ao descomentar em drives de rede
        else:
            os.makedirs(caminho_completo_pasta, exist_ok=True)
        
        print(f"✅ [1/3] Pasta criada/acessada no Drive: {NOME_PASTA_TESTE}")

        # 2. Criar Arquivo
        timestamp = datetime.datetime.now().strftime("%Y-%m-%d %H:%M:%S")
        with open(caminho_arquivo, "w", encoding="utf-8") as f:
            f.write(f"Teste de escrita no Google Drive via Python VPS.\nData: {timestamp}")
        
        print(f"✅ [2/3] Arquivo gravado com sucesso.")

        # 3. Ler Arquivo (para garantir que não foi só cache)
        with open(caminho_arquivo, "r", encoding="utf-8") as f:
            conteudo = f.read()
        
        print(f"✅ [3/3] Leitura confirmada. Conteúdo: '{conteudo.strip()}'")
        print("\n🎉 SUCESSO! O Python consegue manipular seu Google Drive.")

    except OSError as e:
        print(f"❌ ERRO DE SISTEMA DE ARQUIVOS: {e}")
        print("Dica: Isso geralmente acontece por falta de permissão ou falha na montagem do Drive.")
    except Exception as e:
        print(f"❌ ERRO GERAL: {e}")

if __name__ == "__main__":
    executar_teste()