# 📅 Página de Agendamentos - Guia de Uso

## ✨ O que foi adicionado:

### 1. **Menu de Navegação**
- Novo link "Agendar" na barra de navegação
- Acesso rápido à página de agendamentos

### 2. **Formulário Completo de Agendamento**
Campos disponíveis:
- ✅ **Nome do Responsável** - Obrigatório
- ✅ **Email** - Obrigatório
- ✅ **Telefone/WhatsApp** - Obrigatório
- ✅ **Nome da Criança** - Obrigatório
- ✅ **Idade da Criança** - Seletor (0-2 anos até 13-15 anos)
- ✅ **Serviço Desejado** - Dropdown com 7 opções:
  - 🪥 Limpeza Infantil
  - 🦷 Fluoretação
  - 🍬 Tratamento de Cárie
  - 🌟 Selante Dentário
  - 📎 Aparelho Infantil
  - 🎉 Orientação de Higiene
  - 🩺 Avaliação Inicial
- ✅ **Data Preferida** - Date picker (mínimo: hoje)
- ✅ **Hora Preferida** - Seletor com 11 horários
- ✅ **Observações** - TextArea opcional

### 3. **Funcionalidades JavaScript**
- ✅ Validação de campos obrigatórios
- ✅ Data mínima = hoje
- ✅ Mensagem de sucesso após envio
- ✅ Limpeza automática do formulário
- ✅ Scroll para mensagem de confirmação

### 4. **Seção de Informações Importantes**
- Duração da consulta
- Documentos necessários
- Horário de chegada
- Acompanhamento necessário
- Telefone de contato

## 🎨 Design
- Totalmente responsivo (mobile, tablet, desktop)
- Integrado com as cores do site
- Animações suaves
- Cards formatados com Bootstrap 5
- Botões customizados

## 🔧 Como Conectar a um Backend

Para enviar dados realmente, substitua a função `console.log` no script por:

```javascript
// Exemplo com PHP
fetch('processar_agendamento.php', {
    method: 'POST',
    headers: {
        'Content-Type': 'application/json',
    },
    body: JSON.stringify(dados)
})
.then(response => response.json())
.then(data => console.log('Sucesso:', data))
.catch(error => console.error('Erro:', error));
```

## 📧 Próximos Passos Sugeridos:

1. **Criar arquivo PHP** para processar agendamentos
2. **Configurar envio de email** de confirmação
3. **Integrar com WhatsApp API** (optional)
4. **Adicionar banco de dados** para armazenar agendamentos
5. **Criar área de administração** para gerenciar agendamentos

---

**Site pronto para receber agendamentos!** 🎉
