<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Portal do Aluno - IPCA</title>
    <style>
        :root {
            --ipca-green: #007a33;
            --ipca-green-hover: #005f27;
            --bg-light: #f4f7f6;
            --card-bg: #ffffff;
            --text-dark: #333333;
            --text-muted: #666666;
            --success-bg: #d4edda;
            --success-text: #155724;
            --danger-bg: #f8d7da;
            --danger-text: #721c24;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: var(--bg-light);
            margin: 0;
            padding: 0;
            color: var(--text-dark);
        }

        /* Navbar */
        .navbar {
            background-color: var(--ipca-green);
            padding: 1rem 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            color: white;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }

        .navbar-brand {
            display: flex;
            align-items: center;
            gap: 15px;
            font-size: 1.5rem;
            font-weight: bold;
        }

        /* Ajusta o src para o logo real do IPCA */
        .navbar-brand img {
            height: 40px; 
            background: white; /* Fundo branco para contraste se o logo for escuro */
            padding: 2px;
            border-radius: 4px;
        }

        .btn-logout {
            background-color: white;
            color: var(--ipca-green);
            padding: 8px 16px;
            text-decoration: none;
            border-radius: 5px;
            font-weight: 600;
            transition: background 0.3s;
        }

        .btn-logout:hover {
            background-color: #e2e2e2;
        }
    </style>
</head>
<body>

<nav class="navbar">
    <div class="navbar-brand">
        <img src="../img/logo-ipca.png" alt="Logótipo IPCA">
        Portal do Aluno
    </div>
    <a href="../auth/logout.php" class="btn-logout">Sair</a>
</nav>