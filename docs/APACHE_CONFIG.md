# Configuração Apache - Porta 80

## Status: ALTERADO PARA PORTA 80

O Apache foi reconfigurado para rodar na porta 80 (padrão HTTP) em vez de 8081.

### Mudança Realizada

**Arquivo**: `C:\xampp\apache\conf\httpd.conf`
**Linha 56**:
- De: `Listen 8081`
- Para: `Listen 80`

### Como Usar

Agora você pode acessar o projeto sem especificar porta:

```
http://localhost/Prototipo-PHP/auth.html
```

Em vez de:

```
http://localhost:8081/Prototipo-PHP/auth.html  (NÃO PRECISA MAIS)
```

### Status do Apache

- Porto: **80**
- Status: **RODANDO**
- Acesso: **SEM PORTA**
