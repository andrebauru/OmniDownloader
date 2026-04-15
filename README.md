# OmniDownloader

**OmniDownloader** é uma aplicação web em PHP para baixar vídeos e áudio de **YouTube**, **TikTok**, **Instagram** e mais de **1000 plataformas** suportadas pelo `yt-dlp`.

> Cole o link, escolha o formato e o download começa automaticamente com o diálogo de salvar do navegador.

---

## Funcionalidades

- **Download de Vídeo (MP4):** Baixa em melhor qualidade disponível com áudio combinado via FFmpeg.
- **Download de Áudio (MP3):** Extrai o áudio em MP3 com qualidade de 192 kbps.
- **Preview de Vídeo:** Ao colar a URL, exibe título e thumbnail do vídeo automaticamente.
- **Preview de Áudio:** Reprodução inline de vídeos do YouTube e SoundCloud antes do download.
- **Botão Colar:** Cola automaticamente a URL da área de transferência com um clique.
- **Diálogo de Salvar Nativo:** O arquivo é transmitido diretamente ao navegador, que abre o popup "Salvar como".
- **Interface Responsiva:** Funciona em desktops, tablets e celulares com layout adaptável.
- **Busca Integrada:** Pesquise vídeos diretamente no YouTube, TikTok e SoundCloud sem sair do app.
- **Suporte TikTok por Usuário:** Busque vídeos usando `@nomedousuario` (ex: `@charlidamelio`).
- **Proteção Básica contra SSRF:** Bloqueia URLs apontando para IPs privados/reservados.
- **Tratamento Automático de Cookies:** Detecta e tenta usar cookies de navegadores locais (Chrome, Edge, Firefox, Brave) para contornar bloqueios anti-bot do YouTube, Instagram e Twitter automaticamente, sem intervenção do usuário.
- **Múltiplos Idiomas:** Suporte para Português, English, Español e 日本語.
- **SEO Otimizado:** Meta tags, JSON-LD (WebSite, WebApplication, FAQPage) para melhor indexação.

---

## Plataformas Suportadas

| Plataforma | Vídeo | MP3 | Download Automático com Cookies |
|---|---|---|---|
| YouTube | ✅ | ✅ | ✅ (Anti-bot automático) |
| TikTok | ✅ | ✅ | ✅ (com preview de usuário) |
| Instagram | ✅ | ✅ | ✅ (Reels, IGTV, posts com vídeo) |
| Twitter/X | ✅ | ✅ | ✅ (com cookies) |
| SoundCloud | ✅ | ✅ | ✅ (com preview) |
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
- **Suporte a Cookies (opcional):** Para contornar bloqueios anti-bot do YouTube/Instagram/Twitter automaticamente, o servidor precisa ter acesso aos cookies dos navegadores instalados. Em ambientes Docker/VPS sem navegadores gráficos, as tentativas sem cookies ainda funcionam (muitos vídeos).

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

### Configurar Cookies (Opcional)

Para ativar a extração automática de cookies:

```bash
# Variável de ambiente (extrai cookies de Chrome/Firefox/etc no servidor)
export YTDLP_COOKIES_FROM_BROWSER=firefox

# Ou arquivo de cookies local (formato Netscape)
export YTDLP_COOKIES_FILE=/path/to/cookies.txt

# Ou coloque cookies.txt na raiz do projeto
# O código tentará automaticamente esses métodos
```

### Rodar localmente (PHP built-in server)

```bash
php -S localhost:8080
```

Acesse `http://localhost:8080` no navegador.

---

## Recentes Melhorias (v2.0)

- ✅ **Suporte TikTok por usuário:** Pesquise vídeos usando `@nomedousuario` 
- ✅ **Tratamento de cookies automático:** Detecta e tenta Chrome, Edge, Firefox, Brave sequencialmente
- ✅ **Preview de áudio:** Reprodução inline para YouTube e SoundCloud
- ✅ **Suporte a Instagram e Twitter:** Retry automático com cookies para essas plataformas
- ✅ **Responsividade melhorada:** Layout otimizado para mobile
- ✅ **SEO otimizado:** Meta tags, JSON-LD schemas, open graph
- ✅ **Interface multilíngue:** PT, EN, ES, 日本語

---

## Direitos Autorais

© Andre Silva
