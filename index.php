<?php
session_start();

if(isset($_SESSION['tipo'])){
    if($_SESSION['tipo'] == 'admin'){
        header("Location: admin/dashboard.php");
        exit();
    } else {
        header("Location: aluno/home.php");
        exit();
    }
}
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Portal Académico | IPCA</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <style>
        :root {
            --ipca-green: #006633; /* Cor institucional da imagem */
            --ipca-light-green: #008040;
        }
        
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f8f9fa;
            display: flex;
            flex-direction: column;
            min-height: 100vh;
        }

        /* Hero Section com foco nos botões */
        .hero-section {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #ffffff 0%, #e9ecef 100%);
            padding: 60px 20px;
        }

        .auth-card {
            background: white;
            padding: 3rem;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            text-align: center;
            max-width: 500px;
            width: 100%;
        }

        .ipca-logo-main {
            max-width: 250px;
            margin-bottom: 2rem;
        }

        .btn-auth {
            padding: 12px 30px;
            font-weight: 600;
            border-radius: 8px;
            transition: all 0.3s;
            width: 100%;
            margin-bottom: 1rem;
        }

        .btn-login {
            background-color: var(--ipca-green);
            color: white;
            border: none;
        }

        .btn-login:hover {
            background-color: var(--ipca-light-green);
            color: white;
            transform: translateY(-2px);
        }

        .btn-register {
            background-color: transparent;
            border: 2px solid var(--ipca-green);
            color: var(--ipca-green);
        }

        .btn-register:hover {
            background-color: #f0fdf4;
            color: var(--ipca-green);
        }

        /* Rodapé Estilo IPCA */
        footer {
            background-color: var(--ipca-green);
            color: white;
            padding: 40px 0 20px 0;
            font-size: 0.85rem;
        }

        .footer-logo { max-height: 50px; }
        
        .location-item { margin-bottom: 15px; }
        .location-item i { color: #ffffff; margin-bottom: 5px; display: block; }
        
        .social-icons a {
            color: white;
            font-size: 1.5rem;
            margin-left: 15px;
            text-decoration: none;
        }

        .bottom-bar {
            border-top: 1px solid rgba(255,255,255,0.2);
            margin-top: 30px;
            padding-top: 20px;
        }
    </style>
</head>
<body>

    <main class="hero-section">
        <div class="auth-card">
            <img src="img/logo-ipca.png" alt="IPCA Logo" class="ipca-logo-main">
            
            <div class="d-grid gap-2">
                <a href="auth/login.php" class="btn btn-auth btn-login">
                    <i class="fas fa-sign-in-alt me-2"></i> Iniciar Sessão
                </a>
                <a href="auth/registo.php" class="btn btn-auth btn-register">
                    <i class="fas fa-user-plus me-2"></i> Criar Nova Conta
                </a>
            </div>
        </div>
    </main>

    <footer>
        <div class="container">
            <div class="row align-items-start">
                <div class="col-md-4 mb-4">
                    <img src="img/logo-rodape" alt="IPCA" class="footer-logo mb-3">
                </div>
                <div class="col-md-8 text-md-end mb-4">
                    <img src="img/logo-rodape" alt="Parceiros" style="max-height: 60px;">
                </div>
            </div>

            <div class="row text-center mt-4">
                <div class="col-md-2 location-item">
                    <i class="fas fa-map-marker-alt"></i>
                    <strong>CAMPUS DO IPCA | BARCELOS</strong><br>Vila Frescaínha S. Martinho
4750-810 Barcelos
                </div>
                <div class="col-md-2 location-item">
                    <i class="fas fa-map-marker-alt"></i>
                    <strong> POLO DE BRAGA</strong><br>Av. Dr. Francisco Pires Gonçalves
4715-558 Braga
                </div>
                <div class="col-md-2 location-item">
                    <i class="fas fa-map-marker-alt"></i>
                    <strong>POLO DE FAMALICÃO</strong><br>Avenida de Tibães, nº1199
Vale S.Cosme
4770-568 V. N. Famalicã
                </div>
                <div class="col-md-2 location-item">
                    <i class="fas fa-map-marker-alt"></i>
                    <strong>POLO DE GUIMARÃES</strong><br>Avepark - Zona Industrial da Gandra, S. Cláudio do Barco
4806-909 Caldas das Taipas
                </div>
                <div class="col-md-2 location-item">
                    <i class="fas fa-map-marker-alt"></i>
                    <strong>POLO DEESPOSENDE</strong><br>Rua Sra. da Saúde
4740-289 Esposende
                </div>
                <div class="col-md-2 location-item">
                    <i class="fas fa-map-marker-alt"></i>
                    <strong>POLO DE VILA VERDE</strong><br>Rua do Conhecimento
4730-575 Soutelo
                </div>
            </div>

            <div class="row bottom-bar align-items-center">
                <div class="col-md-6">
                    <span>(+351) 253 802 190 | geral@ipca.pt</span>
                </div>
                <div class="col-md-6 text-md-end">
                    <div class="social-icons">
                        <a href="#"><i class="fab fa-facebook"></i></a>
                        <a href="#"><i class="fab fa-instagram"></i></a>
                        <a href="#"><i class="fab fa-linkedin"></i></a>
                        <a href="#"><i class="fab fa-youtube"></i></a>
                    </div>
                </div>
            </div>
            
            <div class="text-center mt-4">
                <small>Copyright © IPCA - Instituto Politécnico do Cávado e do Ave</small>
            </div>
        </div>
    </footer>

</body>
</html>