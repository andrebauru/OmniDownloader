# OmniDownloader

![OmniDownloader](https://via.placeholder.com/1200x400?text=OmniDownloader+v2.0)

> **Baixe vídeos e músicas de YouTube, TikTok, Instagram e 1000+ plataformas em um clique.**

[🔗 Sobre Andre Silva](https://github.com/andrebauru/andrebauru) • [🌐 Meu Site](https://andretsc.dev)

---

## Funcionalidades

- ✅ **Download de Vídeo (MP4):** Melhor qualidade com áudio via FFmpeg  
- ✅ **Download de Áudio (MP3):** 192 kbps  
- ✅ **Compatibilidade Chat:** Videos automaticamente otimizados para WhatsApp, Telegram, Instagram  
- ✅ **Preview:** Título, thumbnail e duração antes do download  
- ✅ **Busca Integrada:** YouTube, TikTok, SoundCloud (@usuário)  
- ✅ **Interface Responsiva:** Desktop, tablet e celular  
- ✅ **Cookies Automáticos:** Contorna bloqueios anti-bot do YouTube/Instagram/Twitter  
- ✅ **Múltiplos Idiomas:** PT-BR, EN, ES, 日本語  
- ✅ **SEO Otimizado:** Meta tags e JSON-LD  

---

## Plataformas Suportadas

### Funcionando ✅

| Plataforma | Vídeo | MP3 | Status |
|---|---|---|---|
| **YouTube** | ✅ | ✅ | Funcional com anti-bot |
| **TikTok** | ✅ | ✅ | Funcional |
| **Instagram** | ✅ | ✅ | Otimizado para buscas consecutivas |
| **SoundCloud** | ✅ | ✅ | Funcional |

### Suportadas (1000+)

Twitter/X, Facebook, Twitch, Reddit, Vimeo, Dailymotion, e mais plataformas via **yt-dlp**.

[📖 Veja TROUBLESHOOTING.md](TROUBLESHOOTING.md) para soluções de problemas comuns.

---

## Requisitos

- **PHP 8.0+**
- **yt-dlp** (atualizado)
- **FFmpeg** (para MP3 e compatibilidade chat)
- **Apache/Nginx** com suporte a rewrite

### Instalação Rápida

```bash
# yt-dlp (pip)
pip install yt-dlp

# ou (curl)
sudo curl -L https://github.com/yt-dlp/yt-dlp/releases/latest/download/yt-dlp \
  -o /usr/local/bin/yt-dlp && chmod +x /usr/local/bin/yt-dlp

# FFmpeg
sudo apt install ffmpeg  # Ubuntu/Debian
brew install ffmpeg      # macOS
```

### Rodar Localmente

```bash
php -S localhost:8080
```

Acesse `http://localhost:8080` no navegador.

---

## v2.0 — Melhorias Recentes

- 🚀 **Otimização automática de vídeo:** H.264 + AAC para compatibilidade total com apps de chat
- 🔐 **Sessão persistente Instagram:** Reutiliza cookies entre downloads (intervalo curto de 1-3s)
- 📊 **Delay otimizado:** Reduzido de 2-15s para 1-3s entre tentativas  
- 🎯 **Seleção de codec inteligente:** Prioriza H.264 automaticamente
- 📝 **Documentação consolidada:** Único arquivo TROUBLESHOOTING.md  
- 🏷️ **Versionamento:** Agora com V2.0

---

## Estrutura

```
├── index.php              # Página principal
├── download.php           # Handler de download
├── api.php               # API JSON
├── assets/
│   ├── css/style.css     # Estilos
│   └── js/app.js         # Lógica frontend
├── includes/
│   └── counter.php       # Contador de downloads
├── TROUBLESHOOTING.md    # Guia de problemas
└── test_ffmpeg.php       # Teste FFmpeg
```

---

## Configuração de Cookies (Opcional)

Para Instagram/Twitter mais rápido:

```bash
# Método 1: Variável de ambiente
export YTDLP_COOKIES_FILE="/caminho/para/cookies.txt"

# Método 2: Arquivo na raiz do projeto
# cookies.txt (formato Netscape)

# Método 3: Browser automático (servidor com Firefox/Chrome instalado)
export YTDLP_COOKIES_FROM_BROWSER=firefox
```
