import datetime
import os

def registrar_execucao():
    # --- MUDANÇA AQUI ---
    # Descobre a pasta onde ESTE script (main.py) está
    diretorio_script = os.path.dirname(os.path.abspath(__file__))
    
    # Cria o caminho completo para o log
    nome_arquivo = os.path.join(diretorio_script, "log_automacao.txt")
    # --------------------

    agora = datetime.datetime.now()
    data_formatada = agora.strftime("%d/%m/%Y %H:%M:%S")
    linha_log = f"Automação executada em: {data_formatada}\n"
    
    # Agora ele usa o caminho completo
    with open(nome_arquivo, "a", encoding="utf-8") as arquivo:
        arquivo.write(linha_log)
        
    print(f"Log atualizado em: {nome_arquivo}")
    print(f"Conteúdo: {linha_log.strip()}")

if __name__ == "__main__":
    print("Rodando a tarefa...")
    registrar_execucao()