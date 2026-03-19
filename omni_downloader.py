import tkinter as tk
from tkinter import filedialog, messagebox, ttk
import os
import threading
import subprocess
import zipfile
import json
import time
import re
import urllib.request as _urllib_req

try:
    import requests
except ImportError:
    requests = None

try:
    import yt_dlp
except ImportError:
    yt_dlp = None

try:
    import vlc
except (ImportError, FileNotFoundError, OSError):
    vlc = None

try:
    import mutagen
except ImportError:
    mutagen = None


# --- Configuracoes ---
APP_NAME = "AndreTsC Youtube MP3 Video List Download"
FFMPEG_DOWNLOAD_URL = "https://www.gyan.dev/ffmpeg/builds/ffmpeg-release-essentials.zip"
SCRIPT_DIR = os.path.dirname(os.path.abspath(__file__))
FFMPEG_LOCAL_DIR = os.path.join(SCRIPT_DIR, "ffmpeg_bin")
CONFIG_FILE = os.path.join(SCRIPT_DIR, "config.json")

# --- Cores do Tema Profissional ---
PRIMARY_COLOR = "#0D47A1"      # Azul escuro profissional
PRIMARY_LIGHT = "#1976D2"      # Azul claro
SECONDARY_COLOR = "#37474F"    # Cinza azulado
SUCCESS_COLOR = "#2E7D32"      # Verde escuro
WARNING_COLOR = "#E65100"      # Laranja escuro
ERROR_COLOR = "#C62828"        # Vermelho escuro
BG_COLOR = "#ECEFF1"           # Fundo cinza claro
CARD_COLOR = "#FFFFFF"         # Branco para cards
TEXT_COLOR = "#263238"         # Texto escuro
TEXT_LIGHT = "#607D8B"         # Texto secundario
HEADER_BG = "#0D47A1"          # Fundo do header
TREEVIEW_BG = "#FAFAFA"        # Fundo da listagem
TREEVIEW_SELECTED = "#1565C0"  # Cor de selecao


def get_ffmpeg_path():
    """Retorna o caminho do FFmpeg se encontrado."""
    try:
        subprocess.run(["ffmpeg", "-version"], capture_output=True, check=True,
                       creationflags=subprocess.CREATE_NO_WINDOW if os.name == 'nt' else 0)
        return "ffmpeg"
    except (subprocess.CalledProcessError, FileNotFoundError):
        pass

    local_ffmpeg = os.path.join(FFMPEG_LOCAL_DIR, "bin", "ffmpeg.exe")
    if os.path.exists(local_ffmpeg):
        return local_ffmpeg

    if os.path.exists(FFMPEG_LOCAL_DIR):
        for item in os.listdir(FFMPEG_LOCAL_DIR):
            potential_path = os.path.join(FFMPEG_LOCAL_DIR, item, "bin", "ffmpeg.exe")
            if os.path.exists(potential_path):
                return potential_path
    return None


def get_gallery_dl_path():
    """Retorna o caminho do gallery-dl se encontrado."""
    try:
        result = subprocess.run(
            ["gallery-dl", "--version"], capture_output=True, check=True,
            creationflags=subprocess.CREATE_NO_WINDOW if os.name == 'nt' else 0)
        return "gallery-dl"
    except (subprocess.CalledProcessError, FileNotFoundError):
        pass
    # Busca em Scripts do Python (pip install coloca lá)
    import sys
    scripts_dir = os.path.join(os.path.dirname(sys.executable), "Scripts")
    for name in ["gallery-dl.exe", "gallery-dl"]:
        candidate = os.path.join(scripts_dir, name)
        if os.path.exists(candidate):
            return candidate
    return None


def download_ffmpeg(progress_callback=None):
    """Baixa e extrai o FFmpeg automaticamente."""
    if requests is None:
        return False, "Biblioteca 'requests' nao instalada. Execute: pip install requests"

    try:
        if progress_callback:
            progress_callback("Baixando FFmpeg...", "blue")

        os.makedirs(FFMPEG_LOCAL_DIR, exist_ok=True)
        zip_path = os.path.join(FFMPEG_LOCAL_DIR, "ffmpeg.zip")

        response = requests.get(FFMPEG_DOWNLOAD_URL, stream=True)
        response.raise_for_status()

        total_size = int(response.headers.get('content-length', 0))
        downloaded = 0

        with open(zip_path, 'wb') as f:
            for chunk in response.iter_content(chunk_size=8192):
                f.write(chunk)
                downloaded += len(chunk)
                if progress_callback and total_size > 0:
                    percent = (downloaded / total_size) * 100
                    progress_callback(f"Baixando FFmpeg: {percent:.1f}%", "blue")

        if progress_callback:
            progress_callback("Extraindo FFmpeg...", "blue")

        with zipfile.ZipFile(zip_path, 'r') as zip_ref:
            zip_ref.extractall(FFMPEG_LOCAL_DIR)

        os.remove(zip_path)

        if progress_callback:
            progress_callback("FFmpeg instalado!", "success")

        return True, "FFmpeg instalado com sucesso!"

    except Exception as e:
        return False, f"Erro ao baixar FFmpeg: {str(e)}"


def get_ffmpeg_location():
    """Retorna o diretorio do FFmpeg para uso com yt-dlp."""
    ffmpeg_path = get_ffmpeg_path()
    if ffmpeg_path and ffmpeg_path != "ffmpeg":
        return os.path.dirname(ffmpeg_path)
    return None


def format_duration(seconds):
    """Formata segundos para MM:SS ou HH:MM:SS."""
    if seconds is None:
        return "--:--"
    try:
        duration = int(seconds)
        hours = duration // 3600
        minutes = (duration % 3600) // 60
        secs = duration % 60
        if hours > 0:
            return f"{hours}:{minutes:02}:{secs:02}"
        return f"{minutes}:{secs:02}"
    except (ValueError, TypeError):
        return "--:--"


def sanitize_filename(filename):
    """Remove caracteres inválidos, emojis e limita o tamanho do nome do arquivo para evitar erros no Windows."""
    # Remove emojis e caracteres não-ASCII visíveis
    sanitized = re.sub(r'[\\/:*?"<>|]', '_', filename)
    sanitized = re.sub(r'[\U00010000-\U0010ffff]', '', sanitized)  # Remove emojis unicode
    sanitized = re.sub(r'[\u2600-\u26FF\u2700-\u27BF]', '', sanitized)  # Símbolos e pictogramas
    sanitized = re.sub(r'[^\w\s\-.,_\(\)\[\]]', '', sanitized)  # Remove outros caracteres especiais
    sanitized = sanitized.strip('. ')
    # Limita o tamanho do nome do arquivo (Windows: máx 255, mas deixa margem para extensão e pasta)
    max_len = 80
    if len(sanitized) > max_len:
        sanitized = sanitized[:max_len]
    return sanitized if sanitized else "video"


def load_config():
    """Carrega as configuracoes do arquivo JSON."""
    if os.path.exists(CONFIG_FILE):
        try:
            with open(CONFIG_FILE, 'r', encoding='utf-8') as f:
                config = json.load(f)
                if 'last_download_path' in config:
                    if not os.path.exists(config['last_download_path']):
                        config['last_download_path'] = os.path.expanduser("~\\Downloads")
                return config
        except (json.JSONDecodeError, IOError):
            return {}
    return {}


def save_config(config):
    """Salva as configuracoes no arquivo JSON."""
    try:
        with open(CONFIG_FILE, 'w', encoding='utf-8') as f:
            json.dump(config, f, indent=4)
    except IOError as e:
        print(f"Erro ao salvar config: {e}")


class YouTubeMP3Downloader:
    def __init__(self, master):
        self.master = master
        master.title(APP_NAME)

        # Define o icone da aplicacao
        try:
            icon_path = os.path.join(SCRIPT_DIR, 'icon.png')
            if os.path.exists(icon_path):
                icon_img = tk.PhotoImage(file=icon_path)
                master.iconphoto(True, icon_img)
        except tk.TclError:
            print("Nao foi possivel definir o icone do aplicativo. Verifique se o arquivo 'icon.png' e valido.")

        # Permite redimensionar e inicia maximizado
        master.resizable(True, True)
        master.minsize(800, 600)

        # Tenta iniciar maximizado
        try:
            master.state('zoomed')
        except:
            master.geometry("1200x800")

        master.configure(bg=BG_COLOR)

        # Configuracoes
        self.config = load_config()
        self.search_term = tk.StringVar()
        initial_path = self.config.get("last_download_path", os.path.expanduser("~\\Downloads"))
        self.output_path = tk.StringVar(value=initial_path)

        self.search_results = []
        self.current_page = 0
        self.items_per_page = 15
        self.max_pages = 3

        # Controles de busca
        self.search_thread = None
        self.stop_search_event = threading.Event()

        # VLC para preview
        self.vlc_instance = None
        self.vlc_player = None
        self.is_previewing = threading.Event()

        self.setup_styles()
        self.create_widgets()

    def setup_styles(self):
        """Configura os estilos visuais profissionais."""
        style = ttk.Style()
        style.theme_use('clam')

        # Frame
        style.configure("Card.TFrame", background=CARD_COLOR)

        # LabelFrame
        style.configure("Card.TLabelframe", background=CARD_COLOR, borderwidth=0)
        style.configure("Card.TLabelframe.Label", background=CARD_COLOR, foreground=PRIMARY_COLOR,
                       font=("Segoe UI", 11, "bold"))

        # Entry
        style.configure("TEntry", fieldbackground=CARD_COLOR, padding=10,
                       font=("Segoe UI", 11))

        # Botoes
        style.configure("Primary.TButton", background=PRIMARY_COLOR, foreground="white",
                       font=("Segoe UI", 10, "bold"), padding=(20, 12))
        style.map("Primary.TButton", background=[('active', PRIMARY_LIGHT)])

        style.configure("Success.TButton", background=SUCCESS_COLOR, foreground="white",
                       font=("Segoe UI", 11, "bold"), padding=(30, 14))
        style.map("Success.TButton", background=[('active', '#388E3C')])

        style.configure("Preview.TButton", background=PRIMARY_LIGHT, foreground="white",
                       font=("Segoe UI", 11, "bold"), padding=(30, 14))
        style.map("Preview.TButton", background=[('active', PRIMARY_COLOR)])

        style.configure("Stop.TButton", background=ERROR_COLOR, foreground="white",
                       font=("Segoe UI", 10, "bold"), padding=(20, 12)) # Padding igual ao Primary
        style.map("Stop.TButton", background=[('active', '#B71C1C')])

        style.configure("Video.TButton", background="#6A1B9A", foreground="white",
                       font=("Segoe UI", 11, "bold"), padding=(30, 14))
        style.map("Video.TButton", background=[('active', '#7B1FA2')])

        style.configure("Nav.TButton", background=SECONDARY_COLOR, foreground="white",
                       font=("Segoe UI", 10), padding=(15, 8))
        style.map("Nav.TButton", background=[('active', '#455A64')])

        # Labels
        style.configure("Page.TLabel", background=CARD_COLOR, foreground=TEXT_LIGHT,
                       font=("Segoe UI", 10))

        # Progressbar
        style.configure("Custom.Horizontal.TProgressbar", background=SUCCESS_COLOR,
                       troughcolor="#E0E0E0", thickness=25)

        # Treeview - Visual profissional
        style.configure("Custom.Treeview",
                       background=TREEVIEW_BG,
                       foreground=TEXT_COLOR,
                       fieldbackground=TREEVIEW_BG,
                       font=("Segoe UI", 10),
                       rowheight=35)
        style.configure("Custom.Treeview.Heading",
                       background=PRIMARY_COLOR,
                       foreground="white",
                       font=("Segoe UI", 10, "bold"),
                       padding=(10, 8))
        style.map("Custom.Treeview",
                 background=[('selected', TREEVIEW_SELECTED)],
                 foreground=[('selected', 'white')])

    def create_widgets(self):
        """Cria todos os widgets da interface."""
        # ... (Header e main_container permanecem os mesmos) ...
        # Header
        header_frame = tk.Frame(self.master, bg=HEADER_BG, height=70)
        header_frame.pack(fill="x")
        header_frame.pack_propagate(False)

        title_label = tk.Label(header_frame, text=APP_NAME,
                              bg=HEADER_BG, fg="white", font=("Segoe UI", 20, "bold"))
        title_label.pack(expand=True)

        # Container principal com peso para redimensionar
        main_container = tk.Frame(self.master, bg=BG_COLOR)
        main_container.pack(fill="both", expand=True, padx=25, pady=20)
        main_container.columnconfigure(0, weight=1)
        main_container.rowconfigure(1, weight=1)  # Linha dos resultados expande

        # Frame de busca
        search_frame = ttk.LabelFrame(main_container, text=" Pesquisar no YouTube / Inserir Link (YouTube, TikTok, Instagram...) ", style="Card.TLabelframe")
        search_frame.grid(row=0, column=0, sticky="ew", pady=(0, 15))

        search_inner = tk.Frame(search_frame, bg=CARD_COLOR)
        search_inner.pack(fill="x", padx=20, pady=20)

        self.search_entry = ttk.Entry(search_inner, textvariable=self.search_term,
                                      font=("Segoe UI", 12))
        self.search_entry.pack(side=tk.LEFT, expand=True, fill="x", padx=(0, 15), ipady=8)
        self.search_entry.bind("<Return>", lambda e: self.start_search())
        self.search_entry.bind("<Button-1>", self._on_search_entry_click)
        self.search_entry.bind("<Button-3>", self._show_search_context_menu)

        self.search_button = ttk.Button(search_inner, text="Pesquisar",
                                        command=self.start_search, style="Primary.TButton")
        self.search_button.pack(side=tk.RIGHT)

        # ... (O resto da criacao de widgets permanece o mesmo) ...
        # Frame de resultados (expande)
        results_frame = ttk.LabelFrame(main_container, text=" Resultados da Pesquisa ", style="Card.TLabelframe")
        results_frame.grid(row=1, column=0, sticky="nsew", pady=(0, 15))

        results_inner = tk.Frame(results_frame, bg=CARD_COLOR)
        results_inner.pack(fill="both", expand=True, padx=20, pady=20)
        results_inner.columnconfigure(0, weight=1)
        results_inner.rowconfigure(0, weight=1)

        # Treeview com colunas
        tree_frame = tk.Frame(results_inner, bg=CARD_COLOR)
        tree_frame.grid(row=0, column=0, sticky="nsew")
        tree_frame.columnconfigure(0, weight=1)
        tree_frame.rowconfigure(0, weight=1)

        # Scrollbars
        scrollbar_y = ttk.Scrollbar(tree_frame, orient=tk.VERTICAL)
        scrollbar_y.pack(side=tk.RIGHT, fill=tk.Y)

        scrollbar_x = ttk.Scrollbar(tree_frame, orient=tk.HORIZONTAL)
        scrollbar_x.pack(side=tk.BOTTOM, fill=tk.X)

        # Treeview
        self.results_tree = ttk.Treeview(
            tree_frame,
            columns=("num", "duracao", "titulo"),
            show="headings",
            selectmode="extended",
            style="Custom.Treeview",
            yscrollcommand=scrollbar_y.set,
            xscrollcommand=scrollbar_x.set
        )

        # Configurar colunas
        self.results_tree.heading("num", text="#", anchor="center")
        self.results_tree.heading("duracao", text="Duracao", anchor="center")
        self.results_tree.heading("titulo", text="Titulo do Video", anchor="w")

        self.results_tree.column("num", width=50, minwidth=50, anchor="center", stretch=False)
        self.results_tree.column("duracao", width=100, minwidth=80, anchor="center", stretch=False)
        self.results_tree.column("titulo", width=600, minwidth=300, anchor="w", stretch=True)

        self.results_tree.pack(side=tk.LEFT, fill="both", expand=True)
        scrollbar_y.config(command=self.results_tree.yview)
        scrollbar_x.config(command=self.results_tree.xview)

        # Tags para linhas alternadas
        self.results_tree.tag_configure('oddrow', background='#F5F5F5')
        self.results_tree.tag_configure('evenrow', background='#FFFFFF')

        # Paginacao
        pagination_frame = tk.Frame(results_inner, bg=CARD_COLOR)
        pagination_frame.grid(row=1, column=0, pady=(15, 0))

        self.prev_button = ttk.Button(pagination_frame, text="< Anterior",
                                      command=self.prev_page, style="Nav.TButton", state=tk.DISABLED)
        self.prev_button.pack(side=tk.LEFT, padx=5)

        self.page_label = ttk.Label(pagination_frame, text="Pagina 0/0", style="Page.TLabel")
        self.page_label.pack(side=tk.LEFT, padx=20)

        self.next_button = ttk.Button(pagination_frame, text="Proxima >",
                                      command=self.next_page, style="Nav.TButton", state=tk.DISABLED)
        self.next_button.pack(side=tk.LEFT, padx=5)

        # Frame inferior (pasta + progresso + botoes)
        bottom_frame = tk.Frame(main_container, bg=BG_COLOR)
        bottom_frame.grid(row=2, column=0, sticky="ew")
        bottom_frame.columnconfigure(0, weight=1)

        # Frame de destino
        path_frame = ttk.LabelFrame(bottom_frame, text=" Pasta de Destino ", style="Card.TLabelframe")
        path_frame.grid(row=0, column=0, sticky="ew", pady=(0, 15))

        path_inner = tk.Frame(path_frame, bg=CARD_COLOR)
        path_inner.pack(fill="x", padx=20, pady=15)

        self.path_entry = ttk.Entry(path_inner, textvariable=self.output_path,
                                    font=("Segoe UI", 10), state="readonly")
        self.path_entry.pack(side=tk.LEFT, expand=True, fill="x", padx=(0, 15), ipady=6)

        self.browse_button = ttk.Button(path_inner, text="Procurar",
                                        command=self.browse_folder, style="Primary.TButton")
        self.browse_button.pack(side=tk.RIGHT)

        # Barra de progresso
        progress_frame = tk.Frame(bottom_frame, bg=BG_COLOR)
        progress_frame.grid(row=1, column=0, sticky="ew", pady=(0, 15))

        self.progress = ttk.Progressbar(progress_frame, orient="horizontal",
                                        mode="determinate", style="Custom.Horizontal.TProgressbar")
        self.progress.pack(fill="x")

        # Botoes de acao
        buttons_frame = tk.Frame(bottom_frame, bg=BG_COLOR)
        buttons_frame.grid(row=2, column=0, pady=(0, 10))

        self.preview_button = ttk.Button(buttons_frame, text="Preview (30s)",
                                         command=self.toggle_preview, style="Preview.TButton")
        self.preview_button.pack(side=tk.LEFT, padx=15)

        self.download_button = ttk.Button(buttons_frame, text="Baixar MP3",
                                          command=self.start_download_thread, style="Success.TButton")
        self.download_button.pack(side=tk.LEFT, padx=15)

        self.download_video_button = ttk.Button(buttons_frame, text="Baixar Vídeo",
                                                command=self.start_download_video_thread, style="Video.TButton")
        self.download_video_button.pack(side=tk.LEFT, padx=15)

        # Status bar
        status_frame = tk.Frame(self.master, bg=SECONDARY_COLOR, height=40)
        status_frame.pack(side=tk.BOTTOM, fill="x")
        status_frame.pack_propagate(False)

        self.status_label = tk.Label(status_frame, text="YouTube: busca por texto ou link | TikTok/Instagram: cole o link direto | Clique na caixa para colar URL",
                                     bg=SECONDARY_COLOR, fg="white", font=("Segoe UI", 10), anchor="w")
        self.status_label.pack(side=tk.LEFT, padx=20, expand=True, fill="x")


    def browse_folder(self):
        folder_selected = filedialog.askdirectory(initialdir=self.output_path.get())
        if folder_selected:
            self.output_path.set(folder_selected)
            self.update_status(f"Pasta selecionada: {folder_selected}", "info")
            self.config["last_download_path"] = folder_selected
            save_config(self.config)

    def _on_search_entry_click(self, event):
        """Auto-cola URL do clipboard ao clicar com o botão esquerdo na caixa de busca (apenas se vazia)."""
        try:
            clipboard_text = self.master.clipboard_get().strip()
            if re.match(r'https?://', clipboard_text):
                current = self.search_term.get().strip()
                if not current:  # Só cola automaticamente se o campo estiver vazio
                    self.search_term.set(clipboard_text)
                    self.search_entry.icursor(tk.END)
        except tk.TclError:
            pass  # Clipboard vazio ou indisponível

    def _show_search_context_menu(self, event):
        """Exibe menu de contexto com opções de colar/copiar na caixa de busca."""
        menu = tk.Menu(self.master, tearoff=0)
        menu.add_command(label="Colar", command=self._paste_to_search)
        menu.add_command(label="Copiar", command=self._copy_from_search)
        menu.add_command(label="Selecionar Tudo", command=self._select_all_search)
        menu.add_separator()
        menu.add_command(label="Limpar", command=lambda: self.search_term.set(""))
        try:
            menu.tk_popup(event.x_root, event.y_root)
        finally:
            menu.grab_release()

    def _paste_to_search(self):
        try:
            self.search_term.set(self.master.clipboard_get().strip())
        except tk.TclError:
            pass

    def _copy_from_search(self):
        text = self.search_term.get()
        if text:
            self.master.clipboard_clear()
            self.master.clipboard_append(text)

    def _select_all_search(self):
        self.search_entry.select_range(0, tk.END)
        self.search_entry.focus_set()

    def _is_tiktok_url(self, url):
        return bool(re.search(r'tiktok\.com', str(url), re.IGNORECASE))

    def _normalize_url(self, url):
        """Normaliza URLs de plataformas com formatos não suportados pelo yt-dlp.
        - TikTok /photo/: remove apenas parâmetros de rastreamento (preserva /photo/ para gallery-dl)
        - Remove parâmetros de rastreamento de URLs TikTok/Instagram
        """
        if not re.match(r'https?://', url):
            return url  # termo de busca, não URL

        if re.search(r'tiktok\.com', url, re.IGNORECASE):
            # Remove parâmetros de rastreamento (?_r=...&_t=... etc)
            # NÃO converte /photo/ → /video/ aqui: preservamos /photo/ para gallery-dl
            url = re.sub(r'\?.*$', '', url)

        if re.search(r'instagram\.com', url, re.IGNORECASE):
            url = re.sub(r'\?.*$', '', url)

        return url.strip('/')

    def update_status(self, message, msg_type="info"):
        """Atualiza a barra de status."""
        colors = {
            "info": "white",
            "success": "#81C784",
            "warning": "#FFB74D",
            "error": "#EF9A9A",
            "blue": "#90CAF9"
        }
        self.status_label.config(text=message, fg=colors.get(msg_type, "white"))
        self.master.update_idletasks()

    def _download_with_gallery_dl(self, url, output_path, safe_title):
        """Baixa carrossel TikTok (imagens + áudio) usando gallery-dl.
        Retorna (sucesso: bool, mensagem: str, arquivos: list)."""
        gdl = get_gallery_dl_path()
        if not gdl:
            return False, "gallery-dl não encontrado.\nInstale com: pip install gallery-dl", []

        # gallery-dl salva na pasta: output_path/tiktok/<user>/<arquivos>
        # Usamos --directory para especificar o destino direto
        filename_pattern = f"{safe_title}_{{num:>02}}.{{extension}}"
        cmd = [
            gdl,
            "--directory", output_path,
            "--filename", filename_pattern,
            "--no-mtime",
            url
        ]

        try:
            flags = subprocess.CREATE_NO_WINDOW if os.name == 'nt' else 0
            result = subprocess.run(
                cmd, capture_output=True, text=True, encoding='utf-8',
                errors='replace', timeout=120,
                creationflags=flags
            )
            # Coleta arquivos salvos na pasta de destino com o prefixo do título
            arquivos = [
                f for f in os.listdir(output_path)
                if f.startswith(safe_title) and os.path.isfile(os.path.join(output_path, f))
            ]
            if result.returncode == 0 or arquivos:
                return True, f"{len(arquivos)} arquivo(s) salvos.", arquivos
            else:
                stderr = result.stderr.strip().splitlines()
                return False, stderr[-1] if stderr else "Erro desconhecido no gallery-dl", []
        except subprocess.TimeoutExpired:
            return False, "Timeout ao baixar com gallery-dl", []
        except Exception as e:
            return False, str(e), []

    def start_search(self):
        """Inicia a busca em uma nova thread e atualiza a UI."""
        if yt_dlp is None:
            messagebox.showerror("Erro", "yt-dlp nao esta instalado!\n\nExecute: pip install yt-dlp")
            return

        term = self.search_term.get().strip()
        if not term:
            self.update_status("Digite um termo de busca ou URL.", "warning")
            return

        # Normaliza URL antes de processar (TikTok /photo/ → /video/, remove tracking params)
        term = self._normalize_url(term)
        self.search_term.set(term)

        # Determina mensagem de status de acordo com a plataforma
        if re.search(r'facebook\.com', term, re.IGNORECASE):
            status_msg = "Obtendo informações do Facebook..."
        elif re.search(r'tiktok\.com', term, re.IGNORECASE):
            status_msg = "Obtendo informações do TikTok..."
        elif re.search(r'instagram\.com', term, re.IGNORECASE):
            status_msg = "Obtendo informações do Instagram..."
        elif re.match(r'https?://', term):
            status_msg = "Obtendo informações do link..."
        else:
            status_msg = "Pesquisando no YouTube..."

        # Prepara a UI para a busca
        self.search_button.config(text="Parar", command=self.stop_search, style="Stop.TButton")
        self.update_status(status_msg, "blue")
        for item in self.results_tree.get_children():
            self.results_tree.delete(item)

        # Reseta o evento de parada e os resultados
        self.stop_search_event.clear()
        self.search_results = []
        self.current_page = 0

        # Inicia a thread de busca
        if re.search(r'facebook\.com', term, re.IGNORECASE):
            self.search_thread = threading.Thread(target=self.search_facebook, args=(term,), daemon=True)
        elif re.search(r'udemy\.com', term, re.IGNORECASE):
            self.search_thread = threading.Thread(target=self.search_udemy, args=(term,), daemon=True)
        else:
            self.search_thread = threading.Thread(target=self.search_youtube, args=(term,), daemon=True)
        self.search_thread.start()

    def search_udemy(self, term):
        """
        Busca e extrai informações de vídeo da Udemy usando yt-dlp, com cookies do Chrome.
        """
        import shutil
        class SearchCancelled(Exception):
            pass
        def progress_hook(d):
            if self.stop_search_event.is_set():
                if d['status'] == 'downloading':
                    raise SearchCancelled()
        try:
            # Caminho do arquivo de cookies do Chrome (Windows padrão)
            chrome_cookie_path = os.path.expandvars(r'%LOCALAPPDATA%\Google\Chrome\User Data\Default\Cookies')
            temp_cookie_file = os.path.join(SCRIPT_DIR, 'chrome_cookies_udemy.txt')
            # Usa o utilitário yt-dlp para exportar cookies do Chrome
            # (yt-dlp pode ler cookies do Chrome diretamente, mas para ofuscação, copia para um arquivo temporário)
            if os.path.exists(chrome_cookie_path):
                try:
                    shutil.copy(chrome_cookie_path, temp_cookie_file)
                except Exception:
                    temp_cookie_file = chrome_cookie_path
            else:
                temp_cookie_file = None
            ydl_opts = {
                'quiet': True,
                'extract_flat': False,
                'force_generic_extractor': False,
                'ignoreerrors': True,
                'progress_hooks': [progress_hook],
                'cookiefile': temp_cookie_file if temp_cookie_file else None,
                'headers': {
                    'User-Agent': 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
                    'Referer': 'https://www.udemy.com/',
                },
            }
            entries = []
            with yt_dlp.YoutubeDL(ydl_opts) as ydl:
                if self.stop_search_event.is_set():
                    raise SearchCancelled()
                info = ydl.extract_info(term, download=False)
                if self.stop_search_event.is_set():
                    raise SearchCancelled()
                if info and 'entries' in info:
                    entries = info['entries']
                elif info and 'id' in info:
                    entries = [info]
            for entry in entries:
                if self.stop_search_event.is_set():
                    break
                if entry and entry.get('id'):
                    video_url = entry.get('webpage_url') or entry.get('url') or term
                    self.search_results.append({
                        'id': entry['id'],
                        'title': entry.get('title', 'Sem título'),
                        'duration': entry.get('duration'),
                        'url': video_url
                    })
            self.master.after(0, self.display_results)
        except SearchCancelled:
            self.master.after(0, lambda: self.reset_search_ui("Busca cancelada pelo usuário.", "warning"))
        except Exception as e:
            error_msg = str(e).splitlines()[-1]
            self.master.after(0, lambda: self.reset_search_ui(f"Erro na busca: {error_msg}", "error"))
    def search_facebook(self, term):
        """
        Busca e extrai informações de vídeo do Facebook usando yt-dlp.
        """
        class SearchCancelled(Exception):
            pass
        def progress_hook(d):
            if self.stop_search_event.is_set():
                if d['status'] == 'downloading':
                    raise SearchCancelled()
        try:
            ydl_opts = {
                'quiet': True,
                'extract_flat': False,
                'force_generic_extractor': False,
                'ignoreerrors': True,
                'progress_hooks': [progress_hook],
            }
            entries = []
            with yt_dlp.YoutubeDL(ydl_opts) as ydl:
                if self.stop_search_event.is_set():
                    raise SearchCancelled()
                info = ydl.extract_info(term, download=False)
                if self.stop_search_event.is_set():
                    raise SearchCancelled()
                if info and 'entries' in info:
                    entries = info['entries']
                elif info and 'id' in info:
                    entries = [info]
            for entry in entries:
                if self.stop_search_event.is_set():
                    break
                if entry and entry.get('id'):
                    video_url = entry.get('webpage_url') or entry.get('url') or term
                    self.search_results.append({
                        'id': entry['id'],
                        'title': entry.get('title', 'Sem título'),
                        'duration': entry.get('duration'),
                        'url': video_url
                    })
            self.master.after(0, self.display_results)
        except SearchCancelled:
            self.master.after(0, lambda: self.reset_search_ui("Busca cancelada pelo usuário.", "warning"))
        except Exception as e:
            error_msg = str(e).splitlines()[-1]
            self.master.after(0, lambda: self.reset_search_ui(f"Erro na busca: {error_msg}", "error"))
    def stop_search(self):
        """Sinaliza para a thread de busca parar."""
        if self.search_thread and self.search_thread.is_alive():
            self.update_status("Parando a busca...", "warning")
            self.stop_search_event.set()

    def reset_search_ui(self, status_msg=None, msg_type="info"):
        """Reseta o botao de busca para o estado inicial."""
        self.search_button.config(text="Pesquisar", command=self.start_search, style="Primary.TButton")
        if status_msg:
            self.update_status(status_msg, msg_type)

    def search_youtube(self, term):
        """
        Executa a busca em segundo plano. Detecta se 'term' e uma URL ou termo de busca.
        Pode ser interrompida pelo 'stop_search_event'.
        """
        class SearchCancelled(Exception):
            pass

        def progress_hook(d):
            if self.stop_search_event.is_set():
                # Apenas yt-dlp com 'download' real (nao extract_info) para de fato
                # lancar uma excecao aqui. Para busca, a verificacao e feita fora do with.
                if d['status'] == 'downloading':
                    raise SearchCancelled()

        try:
            # Detecta se e uma URL direta (qualquer plataforma) ou termo de busca
            is_url = bool(re.match(r'https?://', term))

            # Resolve URLs encurtadas (vt.tiktok.com, vm.tiktok.com, etc.) ANTES de normalizar.
            if is_url and re.search(r'(vt\.tiktok\.com|vm\.tiktok\.com|t\.co|bit\.ly)', term, re.IGNORECASE):
                try:
                    req = _urllib_req.Request(term, headers={'User-Agent': 'Mozilla/5.0'}, method='HEAD')
                    with _urllib_req.urlopen(req, timeout=10) as resp:
                        resolved = resp.url
                    term = self._normalize_url(resolved)
                    self.master.after(0, lambda t=term: self.search_term.set(t))
                    print(f"[INFO] URL encurtada resolvida: '{term}'")
                except Exception as e:
                    print(f"[WARN] Falha ao resolver redirect: {e}")

            is_url = bool(re.match(r'https?://', term))
            is_youtube_url = bool(re.search(r'(youtube\.com|youtu\.be)', term))

            # Detecta se é um carrossel TikTok (/photo/): preserva URL original para gallery-dl
            # mas usa a versão /video/ para extrair metadados via yt-dlp
            is_tiktok_photo = bool(re.search(r'tiktok\.com/@[^/]+/photo/', term, re.IGNORECASE))
            photo_url = term if is_tiktok_photo else None
            term_for_ydlp = re.sub(r'/photo/(\d+)', r'/video/\1', term) if is_tiktok_photo else term

            # Para URLs nao-YouTube usar extract_flat=False para metadata completa
            use_extract_flat = (not is_url) or is_youtube_url

            ydl_opts = {
                'quiet': True,
                'no_warnings': True,
                'extract_flat': use_extract_flat,
                'force_generic_extractor': False,
                'ignoreerrors': True,
                'progress_hooks': [progress_hook],
            }

            print(f"[INFO] Buscando: '{term_for_ydlp[:80]}' | carousel={is_tiktok_photo} | flat={use_extract_flat}")

            entries = []
            with yt_dlp.YoutubeDL(ydl_opts) as ydl:
                if self.stop_search_event.is_set():
                    raise SearchCancelled()

                if is_url:
                    info = ydl.extract_info(term_for_ydlp, download=False)
                else:
                    info = ydl.extract_info(f"ytsearch{self.items_per_page * self.max_pages}:{term}", download=False)

                if self.stop_search_event.is_set():
                    raise SearchCancelled()

                if info and 'entries' in info:
                    entries = info['entries']
                elif info and ('id' in info or 'url' in info or 'webpage_url' in info):
                    entries = [info]

            # Processa as entries encontradas
            for entry in entries:
                if self.stop_search_event.is_set():
                    break
                if not entry:
                    continue
                if not (entry.get('id') or entry.get('url') or entry.get('webpage_url')):
                    continue

                if is_youtube_url or not is_url:
                    video_url = f"https://www.youtube.com/watch?v={entry['id']}"
                else:
                    # Para carrossel TikTok: URL de download = photo_url (para gallery-dl)
                    # Para outros: URL normal da entry
                    video_url = photo_url or entry.get('webpage_url') or entry.get('url') or term

                entry_id = entry.get('id') or str(len(self.search_results))

                self.search_results.append({
                    'id': entry_id,
                    'title': entry.get('title') or entry.get('id') or 'Sem titulo',
                    'duration': entry.get('duration'),
                    'url': video_url,
                    'is_carousel': is_tiktok_photo,
                })

            self.master.after(0, self.display_results)

        except SearchCancelled:
            self.master.after(0, lambda: self.reset_search_ui("Busca cancelada pelo usuário.", "warning"))
        except Exception as e:
            error_msg = str(e).splitlines()[-1]
            self.master.after(0, lambda: self.reset_search_ui(f"Erro na busca: {error_msg}", "error"))

    def display_results(self):
        """Exibe os resultados da pagina atual no Treeview."""
        for item in self.results_tree.get_children():
            self.results_tree.delete(item)

        if not self.search_results:
            self.reset_search_ui("Nenhum resultado encontrado.", "warning")
            self.update_pagination()
            return

        start_idx = self.current_page * self.items_per_page
        end_idx = start_idx + self.items_per_page
        page_results = self.search_results[start_idx:end_idx]

        for i, video in enumerate(page_results):
            num = start_idx + i + 1
            duration = format_duration(video['duration'])
            title = video['title']

            tag = 'oddrow' if i % 2 else 'evenrow'
            self.results_tree.insert("", tk.END, values=(num, duration, title), tags=(tag,))

        status = f"Encontrados {len(self.search_results)} videos." if len(self.search_results) > 0 else "Nenhum video encontrado."
        self.reset_search_ui(status, "success" if len(self.search_results) > 0 else "warning")
        self.update_pagination()

    # ... (o resto das funcoes como paginacao, preview, download, etc permanecem as mesmas) ...
    def update_pagination(self):
        """Atualiza os botoes de paginacao."""
        total = len(self.search_results)
        total_pages = (total + self.items_per_page - 1) // self.items_per_page if total else 0
        total_pages = min(total_pages, self.max_pages)

        page_text = f"Pagina {self.current_page + 1}/{total_pages}" if total_pages > 0 else "Pagina 0/0"
        self.page_label.config(text=page_text)

        self.prev_button.config(state=tk.NORMAL if self.current_page > 0 else tk.DISABLED)
        self.next_button.config(state=tk.NORMAL if self.current_page < total_pages - 1 else tk.DISABLED)

    def prev_page(self):
        if self.current_page > 0:
            self.current_page -= 1
            self.display_results()

    def next_page(self):
        total = len(self.search_results)
        total_pages = min((total + self.items_per_page - 1) // self.items_per_page, self.max_pages)
        if self.current_page < total_pages - 1:
            self.current_page += 1
            self.display_results()

    def get_selected_videos(self):
        """Retorna lista de videos selecionados do Treeview."""
        selected_items = self.results_tree.selection()
        videos = []

        for item in selected_items:
            values = self.results_tree.item(item, 'values')
            if values:
                try:
                    num = int(values[0]) - 1  # Indice baseado em 0
                    if 0 <= num < len(self.search_results):
                        videos.append(self.search_results[num])
                except (ValueError, IndexError):
                    continue # Ignora item invalido
        return videos

    def toggle_preview(self):
        """Alterna entre iniciar e parar preview."""
        if self.is_previewing.is_set():
            self.stop_preview()
        else:
            self.start_preview()

    def start_preview(self):
        """Inicia preview do audio selecionado."""
        if yt_dlp is None:
            messagebox.showerror("Erro", "yt-dlp nao esta instalado!\n\nExecute: pip install yt-dlp")
            return

        if vlc is None:
            messagebox.showerror("Erro",
                "Para usar o Preview, voce precisa:\n\n"
                "1. Instalar o VLC Media Player:\n"
                "   https://www.videolan.org/vlc/\n\n"
                "2. Instalar a biblioteca Python:\n"
                "   pip install python-vlc")
            return

        selected = self.get_selected_videos()
        if not selected:
            self.update_status("Selecione um video para preview.", "warning")
            return

        if len(selected) > 1:
            self.update_status("Selecione apenas UM video para preview.", "warning")
            return

        video = selected[0]
        self.is_previewing.set()
        self.preview_button.config(text="Parar", style="Stop.TButton")
        self.update_status(f"Carregando: {video['title'][:60]}...", "blue")
        self.disable_buttons_for_preview()

        preview_thread = threading.Thread(target=self._play_preview, args=(video,), daemon=True)
        preview_thread.start()

    def stop_preview(self):
        """Para o preview."""
        self.is_previewing.clear()
        if self.vlc_player:
            self.vlc_player.stop()
            self.vlc_player = None
        self._reset_preview_button()
        self.update_status("Preview interrompido.", "info")

    def _play_preview(self, video):
        """Reproduz preview em thread separada."""
        try:
            ydl_opts = {
                'format': 'bestaudio[ext=m4a]/bestaudio',
                'quiet': True,
                'no_warnings': True,
                'noplaylist': True,
            }

            stream_url = None
            with yt_dlp.YoutubeDL(ydl_opts) as ydl:
                info = ydl.extract_info(video['url'], download=False)
                stream_url = info.get('url')

            if not stream_url:
                self.master.after(0, lambda: self.update_status("Nao foi possivel obter stream.", "error"))
                self.master.after(0, self._reset_preview_button)
                return

            if not self.is_previewing.is_set():
                self.master.after(0, self._reset_preview_button)
                return

            if not self.vlc_instance:
                self.vlc_instance = vlc.Instance('--no-video', '--quiet', '--no-xlib',
                                                  '--verbose=-1', '--log-verbose=-1')
            self.vlc_player = self.vlc_instance.media_player_new()

            media = self.vlc_instance.media_new(stream_url)
            self.vlc_player.set_media(media)
            self.vlc_player.play()

            title_short = video['title'][:60]
            self.master.after(0, lambda: self.update_status(f"Tocando: {title_short}...", "success"))

            start_time = time.time()
            while self.is_previewing.is_set() and (time.time() - start_time) < 30:
                time.sleep(0.1)

        except Exception as e:
            error_msg = str(e)[:50]
            self.master.after(0, lambda: self.update_status(f"Erro no preview: {error_msg}", "error"))
        finally:
            try:
                if self.vlc_player:
                    self.vlc_player.stop()
                    self.vlc_player = None
            except Exception:
                pass
            self.is_previewing.clear()
            self.master.after(0, lambda: self.update_status("Preview finalizado.", "info"))
            self.master.after(0, self._reset_preview_button)

    def _reset_preview_button(self):
        """Reseta o botao de preview."""
        self.is_previewing.clear()
        self.preview_button.config(text="Preview (30s)", style="Preview.TButton")
        self.enable_buttons()

    def disable_buttons_for_preview(self):
        """Desabilita botoes durante preview."""
        self.download_button.config(state=tk.DISABLED)
        self.download_video_button.config(state=tk.DISABLED)
        self.search_button.config(state=tk.DISABLED)
        self.browse_button.config(state=tk.DISABLED)
        self.prev_button.config(state=tk.DISABLED)
        self.next_button.config(state=tk.DISABLED)

    def disable_buttons(self):
        self.download_button.config(state=tk.DISABLED)
        self.download_video_button.config(state=tk.DISABLED)
        self.browse_button.config(state=tk.DISABLED)
        self.search_button.config(state=tk.DISABLED)
        self.prev_button.config(state=tk.DISABLED)
        self.next_button.config(state=tk.DISABLED)
        self.preview_button.config(state=tk.DISABLED)

    def enable_buttons(self):
        self.download_button.config(state=tk.NORMAL)
        self.download_video_button.config(state=tk.NORMAL)
        self.browse_button.config(state=tk.NORMAL)
        self.search_button.config(state=tk.NORMAL)
        self.preview_button.config(state=tk.NORMAL)
        self.update_pagination()

    def start_download_thread(self):
        if yt_dlp is None:
            messagebox.showerror("Erro", "yt-dlp nao esta instalado!\n\nExecute: pip install yt-dlp")
            return

        selected = self.get_selected_videos()
        if not selected:
            self.update_status("Selecione pelo menos um video para baixar.", "warning")
            return

        self.disable_buttons()
        self.progress["value"] = 0
        self.update_status(f"Preparando download de {len(selected)} video(s)...", "blue")

        download_thread = threading.Thread(target=self.download_selected_videos, args=(selected,), daemon=True)
        download_thread.start()

    def download_selected_videos(self, videos):
        """Baixa os videos selecionados como MP3."""
        path = self.output_path.get()

        if not os.path.exists(path):
            try:
                os.makedirs(path)
            except OSError as e:
                self.master.after(0, lambda: self.update_status(f"Erro ao criar pasta: {e}", "error"))
                self.master.after(0, self.enable_buttons)
                return

        ffmpeg_path = get_ffmpeg_path()
        if not ffmpeg_path:
            self.master.after(0, lambda: self.update_status("FFmpeg nao encontrado. Baixando...", "warning"))
            success, msg = download_ffmpeg(progress_callback=lambda m, t: self.master.after(0, lambda: self.update_status(m, t)))
            if not success:
                self.master.after(0, lambda: self.update_status(f"Erro: {msg}", "error"))
                self.master.after(0, lambda: messagebox.showerror("Erro", msg))
                self.master.after(0, self.enable_buttons)
                return

        ffmpeg_location = get_ffmpeg_location()

        total = len(videos)
        completed = 0
        errors = []

        for i, video in enumerate(videos):
            try:
                title_short = video['title'][:50]
                self.master.after(0, lambda t=title_short, n=i+1: self.update_status(f"Baixando ({n}/{total}): {t}...", "blue"))

                safe_title = sanitize_filename(video['title'])
                is_tiktok = self._is_tiktok_url(video['url'])
                is_carousel = video.get('is_carousel', False)

                if is_carousel:
                    # Carrossel TikTok: usa gallery-dl para baixar imagens + áudio
                    ok, msg, files = self._download_with_gallery_dl(video['url'], path, safe_title)
                    if not ok:
                        errors.append(f"{video['title'][:30]}: {msg[:60]}")
                elif is_tiktok:
                    # TikTok vídeo normal: usa yt-dlp
                    ydl_opts = {
                        'format': 'bestaudio/best',
                        'outtmpl': os.path.join(path, f'{safe_title}.%(ext)s'),
                        'postprocessors': [{
                            'key': 'FFmpegExtractAudio',
                            'preferredcodec': 'mp3',
                            'preferredquality': '192',
                        }],
                        'quiet': True,
                        'no_warnings': True,
                        'noplaylist': True,
                        'restrictfilenames': False,
                    }
                    if ffmpeg_location:
                        ydl_opts['ffmpeg_location'] = ffmpeg_location
                    with yt_dlp.YoutubeDL(ydl_opts) as ydl:
                        ydl.download([video['url']])
                else:
                    ydl_opts = {
                        'format': 'bestaudio/best',
                        'outtmpl': os.path.join(path, f'{safe_title}.%(ext)s'),
                        'postprocessors': [{
                            'key': 'FFmpegExtractAudio',
                            'preferredcodec': 'mp3',
                            'preferredquality': '192',
                        }],
                        'quiet': True,
                        'no_warnings': True,
                        'noplaylist': True,
                        'restrictfilenames': False,
                    }
                    if ffmpeg_location:
                        ydl_opts['ffmpeg_location'] = ffmpeg_location
                    with yt_dlp.YoutubeDL(ydl_opts) as ydl:
                        ydl.download([video['url']])
                    if mutagen:
                        try:
                            mp3_path = os.path.join(path, f'{safe_title}.mp3')
                            if os.path.exists(mp3_path):
                                audio = mutagen.File(mp3_path, easy=True)
                                if audio is not None:
                                    if audio.tags is None:
                                        audio.add_tags()
                                    audio.tags['comment'] = video['url']
                                    audio.save()
                        except Exception as meta_e:
                            print(f"Nao foi possivel salvar metadados para {safe_title}: {meta_e}")

                completed += 1
                progress = (completed / total) * 100
                self.master.after(0, lambda p=progress: self.progress.configure(value=p))

            except Exception as e:
                errors.append(f"{video['title'][:30]}: {str(e)[:50]}")
                completed += 1

        self.master.after(0, lambda: self.progress.configure(value=100))

        if errors:
            self.master.after(0, lambda: self.update_status(
                f"Concluido com {len(errors)} erro(s). {completed - len(errors)}/{total} baixados.", "warning"))
            self.master.after(0, lambda: messagebox.showwarning("Aviso",
                f"Alguns downloads falharam:\n\n" + "\n".join(errors[:5])))
        else:
            self.master.after(0, lambda: self.update_status(
                f"Download concluido! {completed} arquivo(s) salvos.", "success"))
            self.master.after(0, lambda: messagebox.showinfo("Sucesso",
                f"{completed} MP3(s) salvos em:\n{path}"))

        self.master.after(0, self.enable_buttons)

    def start_download_video_thread(self):
        """Inicia download de vídeo com áudio em thread separada."""
        if yt_dlp is None:
            messagebox.showerror("Erro", "yt-dlp nao esta instalado!\n\nExecute: pip install yt-dlp")
            return

        selected = self.get_selected_videos()
        if not selected:
            self.update_status("Selecione pelo menos um video para baixar.", "warning")
            return

        self.disable_buttons()
        self.progress["value"] = 0
        self.update_status(f"Preparando download de {len(selected)} video(s)...", "blue")

        download_thread = threading.Thread(
            target=self.download_selected_videos_with_video, args=(selected,), daemon=True)
        download_thread.start()

    def download_selected_videos_with_video(self, videos):
        """Baixa os vídeos selecionados (vídeo + áudio). Para TikTok carrossel, baixa todos os slides."""
        path = self.output_path.get()

        if not os.path.exists(path):
            try:
                os.makedirs(path)
            except OSError as e:
                self.master.after(0, lambda: self.update_status(f"Erro ao criar pasta: {e}", "error"))
                self.master.after(0, self.enable_buttons)
                return

        ffmpeg_path = get_ffmpeg_path()
        if not ffmpeg_path:
            self.master.after(0, lambda: self.update_status("FFmpeg nao encontrado. Baixando...", "warning"))
            success, msg = download_ffmpeg(
                progress_callback=lambda m, t: self.master.after(0, lambda: self.update_status(m, t)))
            if not success:
                self.master.after(0, lambda: self.update_status(f"Erro: {msg}", "error"))
                self.master.after(0, lambda: messagebox.showerror("Erro", msg))
                self.master.after(0, self.enable_buttons)
                return

        ffmpeg_location = get_ffmpeg_location()

        total = len(videos)
        completed = 0
        errors = []

        for i, video in enumerate(videos):
            try:
                title_short = video['title'][:50]
                self.master.after(0, lambda t=title_short, n=i+1:
                    self.update_status(f"Baixando video ({n}/{total}): {t}...", "blue"))

                safe_title = sanitize_filename(video['title'])
                is_tiktok = self._is_tiktok_url(video['url'])
                is_carousel = video.get('is_carousel', False)

                if is_carousel:
                    # Carrossel TikTok: usa gallery-dl para baixar imagens
                    ok, msg, files = self._download_with_gallery_dl(video['url'], path, safe_title)
                    if not ok:
                        errors.append(f"{video['title'][:30]}: {msg[:60]}")
                elif is_tiktok:
                    ydl_opts = {
                        'format': 'bestvideo+bestaudio/best',
                        'outtmpl': os.path.join(path, f'{safe_title}.%(ext)s'),
                        'merge_output_format': 'mp4',
                        'quiet': True,
                        'no_warnings': True,
                        'noplaylist': True,
                        'restrictfilenames': False,
                    }
                    if ffmpeg_location:
                        ydl_opts['ffmpeg_location'] = ffmpeg_location
                    with yt_dlp.YoutubeDL(ydl_opts) as ydl:
                        ydl.download([video['url']])
                else:
                    ydl_opts = {
                        'format': 'bestvideo+bestaudio/best',
                        'outtmpl': os.path.join(path, f'{safe_title}.%(ext)s'),
                        'merge_output_format': 'mp4',
                        'quiet': True,
                        'no_warnings': True,
                        'noplaylist': True,
                        'restrictfilenames': False,
                    }
                    if ffmpeg_location:
                        ydl_opts['ffmpeg_location'] = ffmpeg_location
                    with yt_dlp.YoutubeDL(ydl_opts) as ydl:
                        ydl.download([video['url']])

                completed += 1
                progress = (completed / total) * 100
                self.master.after(0, lambda p=progress: self.progress.configure(value=p))

            except Exception as e:
                errors.append(f"{video['title'][:30]}: {str(e)[:50]}")
                completed += 1

        self.master.after(0, lambda: self.progress.configure(value=100))

        if errors:
            self.master.after(0, lambda: self.update_status(
                f"Concluido com {len(errors)} erro(s). {completed - len(errors)}/{total} baixados.", "warning"))
            self.master.after(0, lambda: messagebox.showwarning("Aviso",
                f"Alguns downloads falharam:\n\n" + "\n".join(errors[:5])))
        else:
            self.master.after(0, lambda: self.update_status(
                f"Download concluido! {completed} video(s) salvos.", "success"))
            self.master.after(0, lambda: messagebox.showinfo("Sucesso",
                f"{completed} video(s) salvos em:\n{path}"))

        self.master.after(0, self.enable_buttons)


if __name__ == "__main__":
    # Define um AppUserModelID para o aplicativo no Windows.
    # Isso garante que o icone correto seja exibido na barra de tarefas.
    if os.name == 'nt':
        try:
            from ctypes import windll
            myappid = f'AndreSilva.YoutubeDownloader.{APP_NAME.replace(" ", "")}' # cria um ID unico
            windll.shell32.SetCurrentProcessExplicitAppUserModelID(myappid)
        except (ImportError, AttributeError):
            # Nao e Windows, ctypes nao esta disponivel ou a funcao nao existe.
            pass

    root = tk.Tk()
    app = YouTubeMP3Downloader(root)
    root.mainloop()
