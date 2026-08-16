<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>SABIÁ — Login</title>
  <link rel="stylesheet" href="style.css" />
  <style>
    .login-container {
      max-width: 420px;
      margin: 80px auto;
      padding: 24px;
    }
    .login-tabs {
      display: flex;
      border-bottom: 2px solid #dde2e8;
      margin-bottom: 20px;
    }
    .tab-btn {
      flex: 1;
      padding: 12px;
      background: none;
      border: none;
      font-weight: bold;
      cursor: pointer;
      color: #6c757d;
      font-size: 15px;
    }
    .tab-btn.active {
      color: #158a2f;
      border-bottom: 3px solid #158a2f;
    }
    .field {
      margin-bottom: 15px;
    }
    .field label {
      display: block;
      margin-bottom: 5px;
      font-weight: bold;
    }
    .field input {
      width: 100%;
      padding: 10px;
      border: 1px solid #ccc;
      border-radius: 5px;
      box-sizing: border-box;
    }
  </style>
</head>
<body>

 <header style="width: 100%; display: flex; flex-direction: column; align-items: center; justify-content: center; text-align: center; padding: 20px 15px; background: linear-gradient(135deg, #0f7536 0%, #17a2b8 100%); color: #ffffff;">
  
  <!-- TÍTULO E SUBTÍTULO CENTRALIZADOS -->
  <div style="display: flex; flex-direction: column; align-items: center; margin-bottom: 12px;">
    <div style="font-size: 32px; font-weight: 900; letter-spacing: 2px;">
      <span style="color:#f1ab08;">SABIÁ</span>
    </div>
    <div style="font-size: 14px; opacity: 0.95; font-weight: 500; margin-top: 4px;">
      Sistema de Acompanhamento e Busca de Informações Acadêmicas — 2026
    </div>
  </div>

  <!-- USUÁRIO E BOTÃO LOGOUT CENTRALIZADOS -->
  <div style="display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 8px;">
    
    </div>
  </header>

  <div class="card login-container">
    <h2 style="text-align:center; color:#158a2f; margin-bottom:15px;">Acesso ao Sistema</h2>

    <div class="login-tabs">
      <button type="button" class="tab-btn active" id="btnProf" onclick="selecionarPerfil('professor')">Professor</button>
      <button type="button" class="tab-btn" id="btnAdmin" onclick="selecionarPerfil('admin')">Administrador</button>
    </div>

    <form onsubmit="event.preventDefault(); fazerLogin();">
      <input type="hidden" id="nivel" value="professor">

      <div class="field">
        <label id="lblUsuario">Usuário do Professor</label>
        <input type="text" id="usuario" placeholder="Digite seu usuário" required />
      </div>

      <div class="field">
        <label>Senha</label>
        <input type="password" id="senha" placeholder="Digite sua senha" required />
      </div>

      <button type="submit" class="btn btn-primary" style="width:100%; margin-top:10px;">Entrar</button>
    </form>
  </div>

  <script>
    function selecionarPerfil(perfil) {
      document.getElementById('nivel').value = perfil;
      if (perfil === 'admin') {
        document.getElementById('btnAdmin').classList.add('active');
        document.getElementById('btnProf').classList.remove('active');
        document.getElementById('lblUsuario').innerText = 'Usuário do Administrador';
      } else {
        document.getElementById('btnProf').classList.add('active');
        document.getElementById('btnAdmin').classList.remove('active');
        document.getElementById('lblUsuario').innerText = 'Usuário do Professor';
      }
    }

    async function fazerLogin() {
      const payload = {
        usuario: document.getElementById('usuario').value,
        senha: document.getElementById('senha').value,
        nivel: document.getElementById('nivel').value
      };

      try {
        const res = await fetch('api_login.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify(payload)
        });
        const data = await res.json();

        if (data.success) {
          window.location.href = 'index.php';
        } else {
          alert(data.message || 'Erro ao realizar login.');
        }
      } catch (e) {
        alert('Erro na conexão com o servidor.');
      }
    }
  </script>
</body>
</html>