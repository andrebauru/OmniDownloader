# OmniDownloader — Guia de Solução de Problemas

## Índice
1. [Compatibilidade de Vídeo para Apps de Chat](#compatibilidade-de-vídeo-para-apps-de-chat)
2. [Instagram — Múltiplos Downloads (SOLUÇÃO REAL)](#instagram--múltiplos-downloads-solução-real)
3. [Instagram — Autenticação com Cookies](#instagram--autenticação-com-cookies)

---

## Instagram — Múltiplos Downloads (SOLUÇÃO REAL)

### ⚠️ O Problema Real (v2.0)

Mesmo com a v2.0, Instagram **AINDA BLOQUEIA** após o primeiro download se:
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

**DICA:** Os valores exatos não importam, apenas que tenha `sessionid` e `ds_user_id` válidos

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
Aguarde 90+ segundos (ou mais para ser seguro)
Download 2: ✓ OK (usa cookies)
Aguarde 90+ segundos
Download 3: ✓ OK
```

### ⏰ Por que 90 segundos?

Instagram detecta múltiplas requisições do mesmo IP/User-Agent muito rapidamente:
- < 30s: **Bloqueio praticamente garantido**
- 30-60s: **Pode bloquear**
- 60-90s: **Risco menor**
- 90s+: **Geralmente seguro**

### 📊 Comparação: Com vs Sem cookies.txt

| Cenário | Funciona? | Próximo Download |
|---------|-----------|-----------------|
| **Sem cookies.txt** | ✗ 1º download falha | N/A |
| **Com cookies.txt** | ✓ 1º sucesso | Espere 90+ segundos |
| **Com cookies.txt** | ✓ Múltiplos | Espere 90+ segundos |

### 🔧 Troubleshooting

**"Login required" error**
- cookies.txt inválido ou expirado
- Crie um novo exportando novamente

**"Rate limit reached" error**
- Você tentou dois downloads com < 90 segundos
- Aguarde 5-10 minutos e tente novamente

**cookies.txt não está sendo detectado**
- Certifique-se que está em: `D:\Programacao\Downloader\cookies.txt`
- Execute `test_instagram_multiple.php` para diagnosticar

---

## Compatibilidade de Vídeo para Apps de Chat

### O Problema: "Arquivo Inválido"

Quando você tenta anexar um vídeo MP4 no WhatsApp, Telegram ou outros apps de chat, recebe a mensagem **"Arquivo inválido"**.

Isso acontece porque **apps de chat são muito seletivos** com codecs de vídeo e áudio. Eles não aceitam todos os formatos que um reprodutor normal (VLC, Windows Media Player) aceita.

### ✅ Solução Implementada

O OmniDownloader agora **automaticamente reconverte** todos os vídeos para máxima compatibilidade:

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
  - Bitrate: até 5000k (limite para não ficar grande demais)
  
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

*Tempos podem variar conforme especificações do seu PC*

### Se Ainda Não Funcionar

Se mesmo após reconversão o arquivo não for aceito:

1. **Reduza a resolução**: A conversão respeita a resolução original
   - Se for 4K, tente download em HD primeiro

2. **Divida em partes**: Use FFmpeg diretamente
   ```bash
   ffmpeg -i video.mp4 -c:v h264 -crf 28 -c:a aac video-small.mp4
   ```

3. **Contate suporte do app**: Pode ser limitação da sua rede/dispositivo

### Especificação Completa do FFmpeg

```bash
ffmpeg -i input.mp4 \
  -c:v h264 \
  -preset fast \
  -crf 28 \
  -maxrate 5000k \
  -bufsize 10000k \
  -c:a aac \
  -b:a 128k \
  -movflags +faststart \
  output.mp4
```

---

## Instagram — Autenticação com Cookies

### O Problema

Instagram bloqueia downloads de conteúdo sem autenticação para evitar abusos. O erro é:

```
ERROR: [Instagram] ...: Requested content is not available, 
rate-limit reached or login required. 
Use --cookies-from-browser or --cookies for the authentication.
```

### ✅ Soluções

#### Solução 1: Arquivo de Cookies (Recomendado para Servidores)

1. Em seu computador local, faça login no Instagram
2. Use a extensão "EditThisCookie" ou similar para exportar os cookies do Instagram
3. Crie um arquivo `cookies.txt` no mesmo diretório do `download.php` com o seguinte formato:

```
# Netscape HTTP Cookie File
instagram.com	TRUE	/	FALSE	1893456000	sessionid	seu_sessionid_aqui
instagram.com	TRUE	/	FALSE	1893456000	ds_user_id	seu_user_id_aqui
instagram.com	TRUE	/	FALSE	1893456000	mid	seu_mid_aqui
.instagram.com	TRUE	/	FALSE	1893456000	ig_did	seu_ig_did_aqui
.instagram.com	TRUE	/	FALSE	1893456000	ig_nrcb	1
```

4. Copie o arquivo `cookies.txt` para o servidor no mesmo diretório que `download.php`

#### Solução 2: Variável de Ambiente

Se preferir usar variável de ambiente ao invés de arquivo:

```bash
export YTDLP_COOKIES_FILE="/caminho/completo/para/cookies.txt"
```

#### Solução 3: Usar Browser Local (Apenas Desktop)

Se estiver rodando em um desktop/laptop:
1. O servidor irá tentar automaticamente acessar cookies do Chrome/Firefox/Edge
2. Certifique-se de ter feito login no Instagram em um desses navegadores

### Como Obter o Arquivo de Cookies

#### Método 1: Com a Extensão "EditThisCookie" (Chrome/Edge)

1. Instale a extensão "EditThisCookie" na Chrome Web Store
2. Vá para instagram.com e faça login
3. Clique no ícone de "EditThisCookie"
4. Clique em "Export"
5. Cole o conteúdo em um arquivo `cookies.txt`

#### Método 2: Com Python (yt-dlp)

```bash
# Instale cookies_from_browser se necessário
pip install browser-cookie3

# Exporte cookies
python3 -c "
import browser_cookie3
import json
cj = browser_cookie3.load(domain_name='instagram.com')
for cookie in cj:
    print(f'{cookie.domain}\tTRUE\t/\tFALSE\t{int(cookie.expires)}\t{cookie.name}\t{cookie.value}')
" > cookies.txt
```

#### Método 3: Manualmente

1. Abra chrome://cookies/ no Chrome (ou equivalente no seu navegador)
2. Procure por "instagram.com"
3. Copie os valores de:
   - sessionid
   - ds_user_id
   - mid
   - ig_did
4. Crie o arquivo `cookies.txt` manualmente

### Testando

Após adicionar o arquivo `cookies.txt`, teste com:

```bash
php test_instagram.php
```

Deve retornar sucesso ao baixar as informações do vídeo.

### Troubleshooting

**Erro: "Cookies inválidos ou expirados"**
- Cookies do Instagram expiram frequentemente
- Regenere um novo arquivo `cookies.txt` fazendo login novamente

**Erro: "Rate limit reached"**
- Instagram limita requisições muito rápidas
- Espere alguns minutos entre tentativas
- Use um arquivo de cookies válido

**Conteúdo privado não funciona**
- O usuário que deve estar logado no arquivo de cookies
- O usuário deve ter permissão para ver o conteúdo

---

## Instagram — Rate Limit (Bloqueio Temporário)

### O Problema

Após fazer um download do Instagram com sucesso, tentativas subsequentes falham com erro:

```
Instagram bloqueou temporariamente por excesso de requisições.
Aguarde 5-10 minutos antes de tentar novamente.
```

### Por que Acontece?

O Instagram implementa **rate limiting (limitação de requisições)** para:
- Evitar scraping automatizado
- Proteger seus servidores contra abuso
- Detectar ferramentas de download

### ✅ Soluções (v2.0 — Otimizado!)

#### Solução 1: Aguardar Pouco Tempo Entre Downloads (Recomendada)

**A partir da v2.0**, o OmniDownloader implementa **session persistence e delays otimizados**:

- ⚡ **Intervalo curto de 1-3 segundos** entre tentativas (reduzido de 2-15s)
- 🔐 **Reutiliza cookies de sessão** entre downloads (válido por 24h)
- 🎯 **Seleção inteligente de formato** (H.264 para evitar rejeições)

**Novo fluxo:**
```
Download 1: OK ✓ (1-3s)
Download 2: OK ✓ (1-3s)
Download 3: OK ✓
```

Sem necessidade de aguardar 5-10 minutos!

#### Solução 2: Usar Conta Autenticada (Mais Seguro)

Instagram é mais permissivo com contas logadas. Configure `cookies.txt`:

1. Instale a extensão [EditThisCookie](https://chrome.google.com/webstore/detail/editthiscookie/fngmhnnpilhplaeedifhccceomclgfbg)
2. Acesse Instagram.com e faça login
3. Clique no ícone da extensão → Exporte os cookies
4. Cole em um arquivo chamado `cookies.txt` no diretório do aplicativo
5. Agora os downloads serão praticamente ilimitados

#### Solução 3: VPN ou Trocar IP (Para Muitos Downloads)

Se precisa fazer muitos downloads rapidamente:
- Use uma VPN para trocar seu IP
- Espere algumas horas antes de continuar
- Tente em outro momento do dia

### Como Saber se Estou em Rate Limit?

Você verá a mensagem de erro contendo:
- "bloqueou temporariamente"
- "rate limit"
- "aguarde"
- "too many requests"

### Dicas Extras

1. **Vídeos Públicos são Melhores**: Vídeos de perfis privados geralmente requerem autenticação
2. **Story vs Feed**: Stories têm limites diferentes de posts no feed
3. **Reels vs Vídeos**: Reels têm proteção adicional
4. **Conteúdo Pessoal**: Você sempre pode baixar seus próprios vídeos sem problemas

### Monitoramento

O aplicativo registra todos os erros em `/tmp/omnidownloader_error.log` no servidor.
Se continuar tendo problemas, verifique esse arquivo ou entre em contato.

### Resumo Comparativo (v2.0)

| Método | Velocidade | Funciona? | Dificuldade | Nota |
|--------|-----------|----------|------------|------|
| Padrão (v2.0) | ⚡ Rápido | ✅ Sempre | ⭐ Automático | Novo! Delay 1-3s |
| Usar Cookies.txt | ⚡⚡ Muito Rápido | ✅ Melhor | ⭐⭐ Fácil | Mais seguro |
| Trocar IP/VPN | ⚡ Rápido | ✅ Funciona | ⭐⭐⭐ Médio | Para volume alto |

**Para a maioria dos usuários: Simplesmente faça os downloads! O novo sistema da v2.0 cuida de tudo.**

---

## Scripts de Teste Disponíveis

### test_instagram.php
Verifica se o yt-dlp consegue acessar Instagram com a configuração atual.

```bash
php test_instagram.php
```

### test_ffmpeg.php
Verifica se FFmpeg está disponível e testa suporte a codecs H.264 + AAC.

```bash
php test_ffmpeg.php
```

---

## Resumo Geral

| Problema | Solução | Tempo |
|----------|---------|-------|
| "Arquivo inválido" em chat | FFmpeg reconverter (automático) | +5-30s |
| "Login required" no Instagram | Adicionar cookies.txt | Uma vez |
| "Rate limit" no Instagram | Aguardar 5-10 min | Manual |
| FFmpeg não encontrado | Instalar FFmpeg | Uma vez |

**Todos os problemas têm solução simples!**
