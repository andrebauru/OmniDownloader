import sys
import webbrowser
try:
    import mutagen
except ImportError:
    # A caixa de erro será mais visível para o usuário do que um print
    import tkinter as tk
    from tkinter import messagebox
    root = tk.Tk()
    root.withdraw() # Oculta a janela principal do Tkinter
    messagebox.showerror("Erro de Dependencia", "A biblioteca 'mutagen' e necessaria.\nInstale com: pip install mutagen")
    sys.exit(1)

def open_url_from_mp3(file_path):
    """
    Extrai uma URL do campo de comentario dos metadados de um arquivo MP3 e a abre no navegador.
    """
    try:
        audio = mutagen.File(file_path, easy=True)
        if audio and 'comment' in audio.tags:
            url = audio.tags['comment'][0]
            # Verifica se o comentario parece ser uma URL
            if url.startswith('http://') or url.startswith('https://'):
                webbrowser.open(url)
            else:
                # Informa ao usuario que a URL nao foi encontrada ou e invalida
                import tkinter as tk
                from tkinter import messagebox
                root = tk.Tk()
                root.withdraw()
                messagebox.showwarning("URL Nao Encontrada", "Nao foi encontrada uma URL valida no campo de comentario deste arquivo MP3.")
        else:
            import tkinter as tk
            from tkinter import messagebox
            root = tk.Tk()
            root.withdraw()
            messagebox.showinfo("Nenhuma Informacao", "Este arquivo MP3 nao contem uma URL de video no campo de comentario.")

    except Exception as e:
        import tkinter as tk
        from tkinter import messagebox
        root = tk.Tk()
        root.withdraw()
        messagebox.showerror("Erro ao Ler Arquivo", f"Nao foi possivel ler os metadados do arquivo:\n{e}")

if __name__ == "__main__":
    if len(sys.argv) > 1:
        mp3_file_path = sys.argv[1]
        open_url_from_mp3(mp3_file_path)
    else:
        # Erro se o script for chamado sem um caminho de arquivo
        import tkinter as tk
        from tkinter import messagebox
        root = tk.Tk()
        root.withdraw()
        messagebox.showerror("Erro de Uso", "Este script deve ser chamado com o caminho de um arquivo MP3 como argumento.")
