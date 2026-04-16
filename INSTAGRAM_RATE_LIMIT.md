# Instagram Rate Limit: Solução para Múltiplos Downloads

## O Problema

Após fazer um download do Instagram com sucesso, tentativas subsequentes falham com erro:

```
Instagram bloqueou temporariamente por excesso de requisicoes.
Aguarde 5-10 minutos antes de tentar novamente.
```

## Por que Acontece?

O Instagram implementa **rate limiting (limitação de requisições)** para:
- Evitar scraping automatizado
- Proteger seus servidores contra abuso
- Detectar ferramentas de download

## Soluções

### ✅ Solução 1: Aguardar Entre Downloads (Recomendada)

**Aguarde 5-10 minutos** entre cada download do Instagram.

```
Download 1: OK ✓
[Aguardar 5-10 minutos]
Download 2: OK ✓
[Aguardar 5-10 minutos]
Download 3: OK ✓
```

### ✅ Solução 2: Usar Conta Authenticada

Instagram é mais permissivo com contas logadas. Configure `cookies.txt`:

1. Instale a extensão [EditThisCookie](https://chrome.google.com/webstore/detail/editthiscookie/fngmhnnpilhplaeedifhccceomclgfbg)
2. Acesse Instagram.com e faça login
3. Clique no ícone da extensão → Exporte os cookies
4. Cole em um arquivo chamado `cookies.txt` no diretório do aplicativo
5. Agora os downloads serão muito mais rápidos

### ✅ Solução 3: VPN ou Trocar IP

Se precisa fazer muitos downloads rapidamente:
- Use uma VPN para trocar seu IP
- Espere algumas horas antes de continuar
- Tente em outro momento do dia

## Como Saber se Estou em Rate Limit?

Você verá a mensagem de erro contendo:
- "bloqueou temporariamente"
- "rate limit"
- "aguarde"
- "too many requests"

## Dicas Extras

1. **Vídeos Públicos são Melhores**: Vídeos de perfis privados geralmente requerem autenticação
2. **Story vs Feed**: Stories têm limites diferentes de posts no feed
3. **Reels vs Vídeos**: Reels têm proteção adicional
4. **Conteúdo Pessoal**: Você sempre pode baixar seus próprios vídeos sem problemas

## Monitoramento

O aplicativo registra todos os erros em `/tmp/omnidownloader_error.log` no servidor.
Se continuar tendo problemas, verifique esse arquivo ou entre em contato.

## Resumo

| Método | Velocidade | Funciona? | Dificuldade |
|--------|-----------|----------|------------|
| Aguardar 5-10 min | Lenta | ✅ Sempre | ⭐ Muito Fácil |
| Usar Cookies.txt | Rápido | ✅ Melhor | ⭐⭐ Fácil |
| Trocar IP/VPN | Rápido | ✅ Funciona | ⭐⭐⭐ Médio |

**Para a maioria dos usuários: Simplesmente aguarde alguns minutos entre downloads!**
