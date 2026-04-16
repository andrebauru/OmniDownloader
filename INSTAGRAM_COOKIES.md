## Instagram Download - Autenticacao com Cookies

### O Problema
Instagram bloqueia downloads de conteudo sem autenticacao para evitar abusos. O erro e:
```
ERROR: [Instagram] ...: Requested content is not available, rate-limit reached or login required. 
Use --cookies-from-browser or --cookies for the authentication.
```

### Solucoes

#### Solucao 1: Arquivo de Cookies (Recomendado para Servidores)
1. Em seu computador local, faca login no Instagram
2. Use a extensao "EditThisCookie" ou similar para exportar os cookies do Instagram
3. Crie um arquivo `cookies.txt` no mesmo diretorio do `download.php` com o seguinte formato:

```
# Netscape HTTP Cookie File
instagram.com	TRUE	/	FALSE	1893456000	sessionid	seu_sessionid_aqui
instagram.com	TRUE	/	FALSE	1893456000	ds_user_id	seu_user_id_aqui
instagram.com	TRUE	/	FALSE	1893456000	mid	seu_mid_aqui
.instagram.com	TRUE	/	FALSE	1893456000	ig_did	seu_ig_did_aqui
.instagram.com	TRUE	/	FALSE	1893456000	ig_nrcb	1
```

4. Copie o arquivo `cookies.txt` para o servidor no mesmo diretorio que `download.php`

#### Solucao 2: Variavel de Ambiente
Se preferir usar variavel de ambiente ao inves de arquivo:

```bash
export YTDLP_COOKIES_FILE="/caminho/completo/para/cookies.txt"
```

#### Solucao 3: Usar Browser Local (Apenas Desktop)
Se estiver rodando em um desktop/laptop:
1. O servidor ira tentar automaticamente acessar cookies do Chrome/Firefox/Edge
2. Certifique-se de ter feito login no Instagram em um desses navegadores

### Como Obter o Arquivo de Cookies

#### Metodo 1: Com a Extensao "EditThisCookie" (Chrome/Edge)
1. Instale a extensao "EditThisCookie" na Chrome Web Store
2. Vá para instagram.com e faca login
3. Clique no icone de "EditThisCookie"
4. Clique em "Export"
5. Cole o conteudo em um arquivo `cookies.txt`

#### Metodo 2: Com Python (yt-dlp)
```bash
# Instale cookies_from_browser se necessario
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

#### Metodo 3: Manualmente
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

Deve retornar sucesso ao baixar as informacoes do video.

### Troubleshooting

**Erro: "Cookies inválidos ou expirados"**
- Cookies do Instagram expiram frequentemente
- Regenere um novo arquivo `cookies.txt` fazendo login novamente

**Erro: "Rate limit reached"**
- Instagram limita requisicoes muito rapidas
- Espere alguns minutos entre tentativas
- Use um arquivo de cookies valido

**Conteudo privado nao funciona**
- O usuario que deve estar logado no arquivo de cookies
- O usuario deve ter permissao para ver o conteudo
