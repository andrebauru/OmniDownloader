# OmniDownloader

**OmniDownloader** é uma aplicação desktop com interface gráfica (Python/Tkinter) para pesquisar e baixar mídias do **YouTube**, **TikTok**, **Instagram** e outras plataformas suportadas pelo `yt-dlp`.

> Baixe vídeos, MP3s e carrosseis de fotos do TikTok com apenas alguns cliques.

---

## Funcionalidades

- **Busca Híbrida e Cancelável:** Aceita termos de busca (YouTube) ou links diretos de qualquer plataforma suportada. A busca pode ser interrompida a qualquer momento.
- **Download de Vídeo:** Baixa o vídeo em melhor qualidade disponível (com áudio combinado via FFmpeg).
- **Download de MP3:** Extrai o áudio em MP3 com qualidade de 192 kbps. A URL original é salva nos metadados do arquivo.
- **Carrossel TikTok:** Suporte nativo a posts de fotos/carrossel do TikTok — baixa todas as imagens e o áudio separadamente via `gallery-dl`.
- **URLs encurtadas:** Resolve automaticamente links curtos (ex: `vt.tiktok.com/...`) antes de processar.
- **Preview de Áudio:** Ouça os primeiros segundos de um vídeo antes de baixar (requer VLC Media Player).
- **Menu de Contexto no Windows:** Integração opcional ao Explorer para abrir a URL de um MP3 diretamente no navegador (`add_context_menu.reg`).
- **Colar com um clique:** Clique com o botão esquerdo na caixa de busca (quando vazia) para colar automaticamente a URL da área de transferência.
- **Menu de contexto na busca:** Clique com o botão direito para Colar / Copiar / Selecionar Tudo / Limpar.
- **Pasta de destino configurável:** A última pasta usada é salva em `config.json`.

---

## Plataformas Suportadas

| Plataforma | Vídeo | MP3 | Carrossel/Fotos |
|---|---|---|---|
| YouTube | ✅ | ✅ | — |
| TikTok | ✅ | ✅ | ✅ |
| Instagram | ✅ | ✅ | — |
| Outras (yt-dlp) | ✅ | ✅ | — |

---

## Arquivos do Projeto

| Arquivo | Descrição |
|---|---|
| `omni_downloader.py` | Aplicação principal |
| `open_url_from_mp3.py` | Abre a URL salva em um MP3 no navegador |
| `convert_icon.py` | Converte `icon.png` → `icon.ico` |
| `build.bat` | Compila o executável `.exe` com PyInstaller |
| `config.json` | Salva a última pasta de downloads |
| `icon.png` / `icon.ico` | Ícones da aplicação |
| `ffmpeg_bin/` | Binários do FFmpeg (merge de vídeo/áudio) |
| `add_context_menu.reg` | Adiciona entrada no menu de contexto do Windows |
| `remove_context_menu.reg` | Remove a entrada do menu de contexto |

---

## Como Compilar (Gerar o .exe)

1. **Instale as dependências Python:**
   ```
   pip install pyinstaller Pillow mutagen yt-dlp python-vlc gallery-dl
   ```

2. **Execute o script de compilação:**
   ```
   build.bat
   ```
   Ou dê duplo-clique em `build.bat`. O executável será gerado em `dist/`.

---

## Requisitos

- Python 3.10+
- FFmpeg (incluído em `ffmpeg_bin/`)
- `gallery-dl` instalado (para carrosseis TikTok)
- VLC Media Player (opcional, para preview de áudio)

---

## Direitos Autorais

© Andre Silva
