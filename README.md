# OmniDownloader

**OmniDownloader** é uma aplicação web em PHP para baixar vídeos e áudio de **YouTube**, **TikTok**, **Instagram** e mais de **1000 plataformas** suportadas pelo `yt-dlp`.

> Cole o link, escolha o formato e o download começa automaticamente com o diálogo de salvar do navegador.

---

## Funcionalidades

- **Download de Vídeo (MP4):** Baixa em melhor qualidade disponível com áudio combinado via FFmpeg.
- **Download de Áudio (MP3):** Extrai o áudio em MP3 com qualidade de 192 kbps.
- **Preview de Vídeo:** Ao colar a URL, exibe título e thumbnail do vídeo automaticamente.
- **Botão Colar:** Cola automaticamente a URL da área de transferência com um clique.
- **Diálogo de Salvar Nativo:** O arquivo é transmitido diretamente ao navegador, que abre o popup "Salvar como".
- **Interface Responsiva:** Funciona em desktops, tablets e celulares.
- **Proteção Básica contra SSRF:** Bloqueia URLs apontando para IPs privados/reservados.

---

## Plataformas Suportadas

| Plataforma | Vídeo | MP3 | Carrossel/Fotos |
|---|---|---|---|
| YouTube | ✅ | ✅ | — |
| TikTok | ✅ | ✅ | ✅ |
| Instagram | ✅ | ✅ | — |
| Outras (yt-dlp) | ✅ | ✅ | — |

---

## Estrutura do Projeto

| Arquivo | Descrição |
|---|---|
| `index.php` | Página principal (formulário + UI) |
| `download.php` | Processa o download e faz o stream do arquivo |
| `api.php` | API JSON para buscar metadados do vídeo |
| `assets/css/style.css` | Estilos responsivos |
| `assets/js/app.js` | Lógica de frontend (preview, loading, erros) |
| `.htaccess` | Configuração Apache (segurança, cache, compressão) |

---

## Requisitos do Servidor

- PHP 8.0+
- `yt-dlp` instalado e disponível no `PATH` do servidor
- `ffmpeg` instalado (necessário para merge de vídeo/áudio e extração de MP3)
- Apache com `mod_rewrite` (ou Nginx com configuração equivalente)

### Instalar yt-dlp e FFmpeg (Linux/Ubuntu)

```bash
# yt-dlp
pip install yt-dlp
# ou
curl -L https://github.com/yt-dlp/yt-dlp/releases/latest/download/yt-dlp -o /usr/local/bin/yt-dlp
chmod +x /usr/local/bin/yt-dlp

# FFmpeg
apt install ffmpeg
```

### Rodar localmente (PHP built-in server)

```bash
php -S localhost:8080
```

Acesse `http://localhost:8080` no navegador.

---

## Direitos Autorais

© Andre Silva
