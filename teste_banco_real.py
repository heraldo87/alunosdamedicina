import mysql.connector
from mysql.connector import Error

# Credenciais recuperadas do seu config.php
DB_CONFIG = {
    'host': '181.215.135.63',
    'database': 'medinfocus',
    'user': 'medinfocus',
    'password': 'k78Gh6epARhPsMZP'
}

print("--- INICIANDO TESTE DE CONEXÃO AO MYSQL ---")

try:
    connection = mysql.connector.connect(**DB_CONFIG)
    
    if connection.is_connected():
        db_info = connection.get_server_info()
        print(f"✅ Conectado ao MySQL Server versão {db_info}")
        
        cursor = connection.cursor()
        
        # 1. Listar Tabelas
        cursor.execute("SHOW TABLES;")
        tables = cursor.fetchall()
        
        if not tables:
            print("⚠️ AVISO: O banco 'medinfocus' existe, mas não tem tabelas.")
        else:
            print(f"\n📂 Tabelas encontradas ({len(tables)}):")
            for table in tables:
                nome_tabela = table[0]
                print(f"  - {nome_tabela}")
                
                # 2. Para cada tabela, mostrar colunas (estrutura)
                cursor.execute(f"DESCRIBE {nome_tabela};")
                colunas = cursor.fetchall()
                print(f"    Estrutura: {[col[0] for col in colunas]}")

except Error as e:
    print(f"❌ ERRO DE CONEXÃO: {e}")

finally:
    if 'connection' in locals() and connection.is_connected():
        cursor.close()
        connection.close()
        print("\n🔒 Conexão encerrada.")