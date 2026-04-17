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
- 🔐 **Sessão persistente Instagram:** Reutiliza cookies entre downloads
- 📊 **Delay otimizado:** Reduzido para eficiência máxima  
- 🎯 **Seleção de codec inteligente:** Prioriza H.264 automaticamente
- 📝 **Documentação consolidada:** Único arquivo README.md com tudo  
- 🏷️ **Versionamento:** Agora com v2.0

---

## Estrutura

```
├── index.php              # Página principal
├── download.php           # Handler de download
├── api.php               # API JSON
├── config.php            # Configurações e setup
├── assets/
│   ├── css/style.css     # Estilos
│   └── js/app.js         # Lógica frontend
├── includes/
│   └── counter.php       # Contador de downloads
├── test_instagram_multiple.php  # Teste diagnóstico
└── README.md             # Este arquivo
```

---

## 🚨 SOLUÇÃO PARA MÚLTIPLOS DOWNLOADS NO INSTAGRAM

### O Problema Real

Mesmo após v2.0, Instagram **BLOQUEIA** após o primeiro download se:
- Você não tem um arquivo `cookies.txt` válido
- Tenta fazer múltiplos downloads muito rapidamente (< 90 segundos)
- A sessão anterior expirou

### ✅ SOLUÇÃO DEFINITIVA

#### Passo 1: Criar arquivo `cookies.txt`

**ISTO É OBRIGATÓRIO PARA MÚLTIPLOS DOWNLOADS:**

1. Abra Instagram.com no navegador
2. Instale a extensão **"EditThisCookie"** (Chrome, Edge)
3. Faça login no Instagram
4. Clique no ícone da extensão → Exporte
5. Salve como `cookies.txt` **na raiz do OmniDownloader** (`D:\Programacao\Downloader\cookies.txt`)

#### Passo 2: Formato Correto do cookies.txt

```
# Netscape HTTP Cookie File
instagram.com	TRUE	/	FALSE	1735689600	sessionid	abc123xyz...
instagram.com	TRUE	/	FALSE	1735689600	ds_user_id	123456789
instagram.com	TRUE	/	FALSE	1735689600	mid	valor_aqui
.instagram.com	TRUE	/	FALSE	1735689600	ig_did	uuid-aqui
.instagram.com	TRUE	/	FALSE	1735689600	ig_nrcb	1
```

#### Passo 3: Testar

```bash
# Execute o script de teste
php test_instagram_multiple.php
```

Deve mostrar: ✓ cookies.txt encontrado ✓ Contém dados do Instagram

### 🚀 Agora Múltiplos Downloads Funcionam!

Com `cookies.txt` na raiz:

```
Download 1: ✓ OK (usa cookies)
Aguarde 90+ segundos
Download 2: ✓ OK (usa cookies)
Aguarde 90+ segundos
Download 3: ✓ OK
```

### ⏰ Por que 90 segundos?

Instagram detecta múltiplas requisições do mesmo IP/User-Agent:
- < 30s: **Bloqueio praticamente garantido**
- 30-60s: **Pode bloquear**
- 60-90s: **Risco menor**
- 90s+: **Geralmente seguro**

---

## 💻 COMPATIBILIDADE DE VÍDEO PARA APPS DE CHAT

### O Problema: "Arquivo Inválido"

Quando você tenta anexar um vídeo MP4 no WhatsApp, Telegram ou outros apps de chat, recebe a mensagem **"Arquivo inválido"**. Isso acontece porque apps de chat são muito seletivos com codecs.

### ✅ Solução Implementada

O OmniDownloader **automaticamente reconverte** todos os vídeos para máxima compatibilidade:

**Antes (Problemático)**
```
Vídeo original → H.265 (HEVC) + AAC → ❌ Arquivo Inválido no WhatsApp
Vídeo original → VP9 + Opus → ❌ Arquivo Inválido no Telegram
```

**Depois (Compatível)**
```
Vídeo original → FFmpeg → H.264 + AAC → ✅ Funciona em todos os apps!
```

### Codecs Usados

| Elemento | Codec | Motivo |
|----------|-------|--------|
| **Vídeo** | H.264 | Suportado por 100% dos devices (Android, iOS, Windows, Mac) |
| **Áudio** | AAC | Padrão de áudio dos apps de chat |
| **Container** | MP4 | Compatível com WhatsApp, Telegram, Instagram, etc |

### Especificações Técnicas

```
Video:
  - Codec: H.264
  - Preset: fast (rápido, sem perder qualidade)
  - CRF: 28 (qualidade boa, arquivo menor)
  - Bitrate: até 5000k
  
Audio:
  - Codec: AAC
  - Bitrate: 128k
  
Otimizações:
  - faststart: Começa a reproduzir sem baixar tudo
  - Compatível com streaming
```

### Limitações de Apps de Chat

**WhatsApp**
- ✅ H.264 + AAC
- ✅ Limite: 16MB por vídeo
- ❌ H.265, VP9, AV1 (não suporta)

**Telegram**
- ✅ H.264 + AAC
- ✅ Limite: 2GB por arquivo
- ⚠️ Recodifica vídeos automaticamente

**Instagram**
- ✅ H.264 + AAC
- ✅ Limite: varia por tipo de post
- ⚠️ Recodifica para sua qualidade padrão

### Tempo de Conversão

A conversão acontece **automaticamente** durante o download:

| Duração | Tempo Estimado |
|---------|----------------|
| 30s | 5-10 segundos |
| 1 min | 10-20 segundos |
| 5 min | 1-2 minutos |
| 10+ min | 3-5 minutos |

---

## 🔐 CONFIGURAÇÃO DE COOKIES

### Método 1: Arquivo na Raiz (Recomendado)

1. Em seu computador, faça login no Instagram
2. Use a extensão "EditThisCookie" para exportar os cookies
3. Crie arquivo `cookies.txt` na raiz:

```
# Netscape HTTP Cookie File
instagram.com	TRUE	/	FALSE	1893456000	sessionid	seu_sessionid_aqui
instagram.com	TRUE	/	FALSE	1893456000	ds_user_id	seu_user_id_aqui
instagram.com	TRUE	/	FALSE	1893456000	mid	seu_mid_aqui
```

### Método 2: Variável de Ambiente

```bash
export YTDLP_COOKIES_FILE="/caminho/completo/para/cookies.txt"
```

### Método 3: Browser Local

Se estiver em desktop:
```bash
export YTDLP_COOKIES_FROM_BROWSER=firefox
```

---

## 🛠️ TROUBLESHOOTING

### "Login required" error

- cookies.txt inválido ou expirado
- Exporte novamente do navegador

### "Rate limit reached" error

- Você tentou dois downloads com < 90 segundos
- Aguarde 5-10 minutos e tente novamente

### cookies.txt não é detectado

- Certifique-se que está em: `D:\Programacao\Downloader\cookies.txt`
- Execute `test_instagram_multiple.php` para diagnosticar

### Arquivo inválido em apps de chat

- A reconversão automática deveria funcionar
- Se não funcionar, tente reduzir a resolução do download
- Divida em partes usando FFmpeg diretamente
