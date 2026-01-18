import mysql.connector
import os
import datetime
import sys

# --- CONFIGURAÇÕES ---
DB_CONFIG = {
    'host': '181.215.135.63',
    'database': 'medinfocus',
    'user': 'medinfocus',
    'password': 'k78Gh6epARhPsMZP'
}

# Caminho da montagem do Rclone (Onde as pastas nascem)
MOUNT_POINT = "/mnt/medinfocus_drive/MEDINFOCUS_ARQUIVOS"

def conectar_banco():
    return mysql.connector.connect(**DB_CONFIG)

def criar_workspace(nome_disciplina, descricao, id_usuario_responsavel):
    print(f"\n🚀 INICIANDO CRIAÇÃO: {nome_disciplina}")
    
    conn = None
    cursor = None
    
    # Sanitização básica do nome da pasta (remove caracteres proibidos)
    nome_seguro = "".join([c for c in nome_disciplina if c.isalnum() or c in (' ', '-', '_')]).strip()
    caminho_final = os.path.join(MOUNT_POINT, nome_seguro)

    try:
        # 1. TENTA CRIAR A PASTA FÍSICA NO DRIVE
        if os.path.exists(caminho_final):
            print(f"⚠️ A pasta '{nome_seguro}' já existe no Drive! Vamos apenas registrar no banco.")
        else:
            os.makedirs(caminho_final)
            print(f"✅ [FILESYSTEM] Pasta criada: {caminho_final}")

        # 2. TENTA REGISTRAR NO BANCO
        conn = conectar_banco()
        conn.autocommit = False # Inicia transação manual
        cursor = conn.cursor()

        sql = """
            INSERT INTO workspaces 
            (name, description, drive_folder_id, created_by, status, created_at)
            VALUES (%s, %s, %s, %s, 'ativo', NOW())
        """
        # Obs: drive_folder_id geralmente é o ID hash do Google (ex: 1A2b3C...), 
        # mas via Rclone Mount, operamos por caminho. Vamos salvar o nome_seguro 
        # para referência futura ou implementar uma busca de ID depois.
        valores = (nome_disciplina, descricao, nome_seguro, id_usuario_responsavel)
        
        cursor.execute(sql, valores)
        id_gerado = cursor.lastrowid
        
        conn.commit() # Confirma a gravação no banco
        print(f"✅ [DATABASE] Workspace registrado com ID: {id_gerado}")
        print("🎉 SUCESSO TOTAL! O Workspace está pronto.")

    except mysql.connector.Error as err:
        print(f"❌ ERRO DE BANCO: {err}")
        if conn: conn.rollback()
        # Opcional: Aqui poderíamos deletar a pasta criada se o banco falhou
        
    except OSError as err:
        print(f"❌ ERRO DE ARQUIVO (OS): {err}")
        # Se falhou criar a pasta, não precisamos fazer rollback no banco pois nem chegamos lá
        
    except Exception as e:
        print(f"❌ ERRO GERAL: {e}")
        if conn: conn.rollback()

    finally:
        if cursor: cursor.close()
        if conn and conn.is_connected(): conn.close()

if __name__ == "__main__":
    # DADOS DE TESTE
    # Vamos criar uma disciplina de teste
    # ID do Usuário 1 (Geralmente é o Admin)
    
    nome = "Anatomia Clinica 2026"
    desc = "Material de estudo para a turma de Anatomia"
    user_id = 1 
    
    criar_workspace(nome, desc, user_id)